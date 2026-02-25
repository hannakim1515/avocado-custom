<?php
$end        = false;
$turn_break = isset($turn_break) ? $turn_break : false;

$next    = k_next_unit_realtime($ra_id);
$ra_done = (is_array($next) && isset($next['ra_done'])) ? $next['ra_done'] : '';
$log_msg = '';

if ($ra_done === 'next') {
    if (isset($next['unit_type']) && $next['unit_type'] === 'ch') {
        $turn_break = true;
        // speed 모드: 캐릭터 턴으로 변경 시 제한시간 리셋
        k_reset_time_limit($ra_id);
    }
    $unit_name = ses($next, 'unit_name', '');
    $log_msg   = $unit_name . '의 차례';
} elseif ($ra_done === 'mo_win') {
    $end        = true;
    $turn_break = true;
} elseif ($ra_done === 'ch_win') {
    $end        = true;
    $turn_break = true;
}

$next['ra']            = k_count_up($ra_id);
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
