<?php
$end        = false;
$turn_break = false;
$ra_done = '';
$log_msg = '';
$next=array();

// 몬스터 생존 여부
$sum = sql_fetch("SELECT SUM(hp_now) AS sum 
                    FROM {$battle_table}_unit 
                    WHERE hp_now > 0 
                        AND unit_type = 'mo' 
                        AND ra_id = '{$ra_id}'");

if (empty($sum['sum']) || (int)$sum['sum'] <= 0) {$ra_done = 'ch_win';}

// 캐릭터 생존 여부
$sum = sql_fetch("SELECT SUM(hp_now) AS sum 
                    FROM {$battle_table}_unit 
                    WHERE hp_now > 0 
                        AND unit_type = 'ch' 
                        AND ra_id = '{$ra_id}'");

if (empty($sum['sum']) || (int)$sum['sum'] <= 0) {$ra_done = 'mo_win';}



if (!$ra_done) {//레이드 종료 아니면 
    $nt = sql_fetch("SELECT rm_id, unit_type FROM {$battle_table}_unit 
            WHERE hp_now > 0
            AND ra_id = '{$ra_id}'
            AND is_stun = 0
            AND tt_done = 0
            ORDER BY unit_type ASC
            LIMIT 1");
    if(empty($nt['rm_id'])){
        // 모두 행동 완료 - 턴 증가
        k_turn_change_realtime($ra_id);
        // turn 모드: 턴 변경 시 제한시간 리셋
        k_reset_time_limit($ra_id);
        $turn_break = true;
    }elseif($nt['unit_type'] === 'ch'){
        // 다음 행동이 캐릭터면 루프 종료
        $turn_break = true;
    }
    // unit_type === 'mo'이면 turn_break = false 유지 (몬스터 행동 계속)
} elseif ($ra_done === 'mo_win') {
    $end        = true;
    $turn_break = true;
    
} elseif ($ra_done === 'ch_win') {
    $end        = true;
    $turn_break = true;
}


$next['ra'] = k_count_up($ra_id);
$next['ra']['ra_done'] = $ra_done;

$ra_turn  = ses($next['ra'], 'ra_turn', 0, 'int');
$ra_count = ses($next['ra'], 'ra_count', 0, 'int');

if ($log_msg !== '') {
    $option = ", lo_2 = '{$ra_turn}', lo_3 = '{$ra_count}'";
    insert_k_log($log_msg, $ra_id, 'system', $option);
}

if ($end) {
    k_raid_end($ra_id, $ra_done);
}
?>
