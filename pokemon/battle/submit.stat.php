<?php
include_once('../../common.php');

// 1) 입력 수신 & 정규화
$ph_id = isset($_POST['ph_id']) ? (int)$_POST['ph_id'] : 0;
$norm = function($v){ $n = (int)$v; return max(0, min(9999, $n)); };
$a = $norm($_POST['a'] ?? null);
$b = $norm($_POST['b'] ?? null);
$c = $norm($_POST['c'] ?? null);
$d = $norm($_POST['d'] ?? null);
$s = $norm($_POST['s'] ?? null);
$h = $norm($_POST['h'] ?? null);
$h_max = ($h*2)+110;

// 간단 이스케이프(그누보드 환경에 sql_escape_string()이 있으면 그걸로 교체 권장)
$esc = static function($v){ return addslashes((string)$v); };

// 2) 기본 검증
if (!$ph_id) { alert('잘못된 접근입니다. (ph_id)'); }

// 3) 소유권 검증
$po = get_pokemon($ph_id);
if (!$po['ph_id'] || $po['ch_id'] != $character['ch_id']) {
    alert('잘못된 접근입니다. (owner)');
}

// 4) 기존 배틀 엔트리 조회
$ph = get_battle_pokemon($ph_id, false);

// 5) 트랜잭션 시작
sql_query('START TRANSACTION');

$ok = true;
$tbl = $g5['pokemon_battle_table'];

if (!empty($ph['ph_id'])) {
    // UPDATE
    $q = "
        UPDATE {$tbl}
           SET a = '{$a}',
               b = '{$b}',
               c = '{$c}',
               d = '{$d}',
               s = '{$s}',
               h = '{$h}',
               hp_max = '{$h_max}',
               hp_now = '{$h_max}',
               battle_hp_now='{$h_max}'
               
         WHERE ph_id = '{$ph['ph_id']}'
         LIMIT 1
    ";
    $ok = $ok && (bool)sql_query($q);
} else {
    // INSERT (괄호/따옴표 오류 수정 + 문자열 이스케이프)
    $po_id    = (int)$po['po_id'];
    $po_name  = $esc($po['po_name'] ?? '');
    $po_dot   = $esc($po['po_dot']  ?? '');
    $ch_id    = (int)$character['ch_id'];

    $q = "
        INSERT INTO {$tbl}
            (ph_id, po_id, po_name, po_dot, ch_id, a, b, c, d, s, h, hp_max, battle_hp_now, hp_now)
        VALUES
            ('{$po['ph_id']}', '{$po_id}', '{$po_name}', '{$po_dot}',
             '{$ch_id}', '{$a}', '{$b}', '{$c}', '{$d}', '{$s}', '{$h}', '{$h_max}', '{$h_max}', '{$h_max}')
    ";
    $ok = $ok && (bool)sql_query($q);

    // 6-1) 기본 스킬(없을 때만) 세팅
    if ($ok) {
        $cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['pokemon_waza_has_table']} WHERE ph_id='{$po['ph_id']}'");
        if ((int)$cnt['cnt'] === 0) {
            $default_wa = [
                ['wa_id' => 1,  'pp_max' => 20],
                ['wa_id' => 20, 'pp_max' => 20],
                ['wa_id' => 44, 'pp_max' => 3],
                ['wa_id' => 50, 'pp_max' => 10],
            ];
            $ord = 1;
            foreach ($default_wa as $wa) {
                $wa_id = (int)$wa['wa_id'];
                $pp_max=(int)$wa['pp_max']; 
                $ord = (int)$ord;
                $q2 = "
                    INSERT INTO {$g5['pokemon_waza_has_table']} (ph_id, wa_order, wa_id, pp_max, pp_now)
                    SELECT '{$po['ph_id']}', '{$ord}', '{$wa_id}', '{$pp_max}', '{$pp_max}'
                    FROM DUAL
                    WHERE NOT EXISTS (
                        SELECT 1 FROM {$g5['pokemon_waza_has_table']}
                         WHERE ph_id='{$po['ph_id']}' AND wa_order='{$ord}'
                    )
                ";
                $ok = $ok && (bool)sql_query($q2);
                if(!$ok) break;
                $ord++;
            }
        }
    }
}

// 7) 커밋/롤백
if ($ok) {
    sql_query('COMMIT');
    goto_url(G5_URL.'/pokemon/battle/?ph_id='.$ph_id);
} else {
    sql_query('ROLLBACK');
    alert('처리 중 오류가 발생했습니다. (트랜잭션 롤백)');
}
?>