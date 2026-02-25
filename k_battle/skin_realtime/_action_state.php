<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// 파라미터 기본값 방어 
$type = ses($_REQUEST, 'type', '');

$response = false;

// 관리자 체크 + 타입 체크 + ra_id 필수
if ($is_admin === 'super' && $type !== '' && $ra_id !== '') {

    if ($type === 'start') {
        // 로그/버프 초기화
        sql_query("DELETE FROM {$battle_table}_log  WHERE ra_id = '{$ra_id}'");
        sql_query("DELETE FROM {$battle_table}_buff WHERE ra_id = '{$ra_id}'");

        sql_query("UPDATE {$battle_table}_unit
                      SET tt_done = 0,
                          is_stun = 0,
                          is_aggr = 0
                    WHERE ra_id = '{$ra_id}'");

        // 시작 로그
        $log_msg = '레이드를 시작합니다.';
        $option  = ", lo_2 = '1', lo_3 = '1'";
        insert_k_log($log_msg, $ra_id, 'system', $option, $log_msg);

        // 첫 유닛 선정
        $next = k_next_unit_realtime($ra_id, 'ch');

        // 제한시간 초기화
        $time_start = time();

        // 레이드 기본 상태 초기화
        $next_rm_id = ses($next, 'rm_id', 0, 'int');
        sql_query("UPDATE {$battle_table}
                      SET ra_state = 1,
                          ra_turn  = 1,
                          ra_count = 2,
                          now_turn = '{$next_rm_id}',
                          ra_time_start = '{$time_start}'
        WHERE {$ar_title} = '{$ar_value}'");

        if (!empty($next['unit_name'])) {
            $log_msg = $next['unit_name'] . '의 차례';
            $option  = ", lo_2 = '1', lo_3 = '2'";
            insert_k_log($log_msg, $ra_id, 'system', $option);
        }
        
        $response = true;

    }else{
        k_raid_end($ra_id, $type);
    }
}

// Pusher broadcast (상태 변경 시)
if ($response) {
    $ra_full = sql_fetch("SELECT ra_system FROM {$battle_table} WHERE ra_id = '{$ra_id}'");
    if (ses($ra_full, 'ra_system', '') === 'pusher') {
        broadcast_pusher_event('raid-' . $ra_id, 'raid-state', array(
            'type' => $type,
            'timestamp' => time()
        ));
    }
}

echo json_encode($response);
exit;
