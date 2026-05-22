<?php
if (!$type) $type = '1on1';

$max_turn = 20;
$ma_id = $ma_id ?? ''; // 없으면 빈값

// p1/p2: 이벤트 로그에서 '포켓몬' ID로 읽어와야 함 (lo_4, lo_6)
$p1_id = (int)$lo_after['lo_4'];
$p2_id = (int)$lo_after['lo_6'];

$p1 = get_battle_pokemon($p1_id);
$p2 = get_battle_pokemon($p2_id);

// 이번 턴에 사용할 기술 wh_id
$p1['wh_id'] = (int)$lo_after['lo_7'];
$p2['wh_id'] = (int)$lo_after['lo_8'];

// ----------------- 트랜잭션 시작 -----------------
sql_query("START TRANSACTION");

// 1) 턴 배정 (TX 안에서)
$now_turn = claim_next_turn($bo_table, $wr_id, $type);
if ($now_turn <= 0 || $now_turn > $max_turn) { sql_query("ROLLBACK"); return; }

// 2) 참가자 행 잠금 (항상 오름차순으로)
$ids = [$p1_id, $p2_id];
sort($ids, SORT_NUMERIC);
if (!sql_query("
    SELECT ph_id
    FROM {$g5['pokemon_battle_table']}
    WHERE ph_id IN ('{$ids[0]}','{$ids[1]}')
    FOR UPDATE
", false)) {
    sql_query("ROLLBACK"); return;
}

// 2-1) 이벤트 로그 행 잠금 (올바른 ID 사용)
$lo_row = sql_fetch("
    SELECT *
    FROM {$g5['pokemon_event_log_table']}
    WHERE lo_id = '{$lo_after['lo_id']}'
    FOR UPDATE
");

// 3) 최신 상태로 전투 로직
list($fir, $sec) = determine_turn_order($p1, $p2);

if ($now_turn == 1) {
    // 양측 현재 HP/battle HP를 최대치로 초기화
    $fir['hp_now'] = $fir['hp_max'];
    $sec['hp_now'] = $sec['hp_max'];

    if (!sql_query("
        UPDATE {$g5['pokemon_battle_table']}
           SET battle_hp_now = hp_max,
               hp_now        = hp_max
         WHERE ph_id IN ('{$fir['ph_id']}', '{$sec['ph_id']}')
    ", false)) {
        sql_query("ROLLBACK"); return;
    }
}

list($fir, $fir_wa, $sec, $sec_wa) = get_battle_waza($fir, $sec, $type);

// 4) 선/후공 처리 + 로그 + HP 반영 (savepoint 모드)
if (!get_battle_result($fir, $sec, $fir_wa, $sec_wa, $now_turn, $type, $max_turn, true)) {
    sql_query("ROLLBACK"); return;
}

// 5) 기술 사용 이력 제거
sql_query("
    UPDATE {$g5['pokemon_event_log_table']}
       SET lo_7 = '', lo_8 = ''
     WHERE lo_id = '{$lo_after['lo_id']}'
");

// 6) 커밋
sql_query("COMMIT");
