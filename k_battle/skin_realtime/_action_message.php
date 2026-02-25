<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// 파라미터 기본값 처리
$msg   = ses($_REQUEST, 'msg', '', 'raw');
$type  = ses($_REQUEST, 'type', '');


$ra = array();

// 메시지 있을 때만 로그 기록
if ($msg !== '' && $ra_id) {
    $ra = k_count_up($ra_id);
    $ra_turn  = ses($ra, 'ra_turn', 0, 'int');
    $ra_count = ses($ra, 'ra_count', 0, 'int');
    $system_msg='';
    $option = ", lo_2 = '{$ra_turn}', lo_3 = '{$ra_count}'";

    if ($type === 'ch-msg' && isset($character['ch_thumb'], $character['ch_name'])) {
        $thumb = h($character['ch_thumb']);
        $name  = h($character['ch_name']);
        $msg   = "<p class=\"msg-prof\"><img src=\"{$thumb}\"><span class=\"msg-name\">{$name}</span><span class=\"msg-text\">".h($msg)."</span></p>";
    }
    if($type=='system'){$system_msg = $msg;}
    insert_k_log($msg, $ra_id, $type, $option, $system_msg);
}

// k_count_up를 안 탔을 수도 있으니 빈 배열이면 기본값 세팅
if (!is_array($ra) || empty($ra)) {
    $ra = array(
        'ra_turn'  => 0,
        'ra_count' => 0
    );
}

// Pusher broadcast (ra_system이 pusher일 때)
$ra_full = sql_fetch("SELECT ra_system FROM {$battle_table} WHERE {$ar_title} = '{$ra_id}'");
if (ses($ra_full, 'ra_system', '') === 'pusher') {
    broadcast_pusher_event('raid-' . $ra_id, 'raid-message', array(
        'type' => $type,
        'ra_turn' => ses($ra, 'ra_turn', 0, 'int'),
        'ra_count' => ses($ra, 'ra_count', 0, 'int')
    ));
}

echo json_encode($ra);
exit;
