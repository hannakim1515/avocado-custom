<?php
$sub_menu = "993200";
include_once('./_common.php');

check_token();

// 파라미터 처리
$qu_id = ses($_POST, 'qu_id', 0, 'int');
$qu_type = ses($_POST, 'qu_type', 'sub', 'string');
$qu_state = ses($_POST, 'qu_state', 'active', 'string');
$qu_complete_type = ses($_POST, 'qu_complete_type', 'log', 'string');
$qu_submit_type = ses($_POST, 'qu_submit_type', 'mmb', 'string');
$qu_request_item = ses($_POST, 'qu_request_item', 0, 'int');
$qu_request_item_count = ses($_POST, 'qu_request_item_count', 1, 'int');
$qu_blind = ses($_POST, 'qu_blind', 0, 'int');
$qu_title = ses($_POST, 'qu_title', '', 'string');
$qu_content = ses($_POST, 'qu_content', '', 'string');
$qu_content2 = ses($_POST, 'qu_content2', '', 'string');
$qu_end_msg = ses($_POST, 'qu_end_msg', '', 'string');
$qu_money = ses($_POST, 'qu_money', 0, 'int');
$qu_exp = ses($_POST, 'qu_exp', 0, 'int');
$it_id = ses($_POST, 'it_id', 0, 'int');
$ti_id = ses($_POST, 'ti_id', 0, 'int');
$qu_take_max = ses($_POST, 'qu_take_max', 0, 'int');
$qu_view = ses($_POST, 'qu_view', 0, 'int');
$event_week = ses($_POST, 'event_week', '', 'string');

// qu_state 유효성 검사
if (!in_array($qu_state, array('hidden', 'active', 'done'))) {
    $qu_state = 'active';
}

// 아이템 제출이 아닌 경우 요구 아이템 초기화
if ($qu_complete_type != 'item') {
    $qu_request_item = 0;
    $qu_request_item_count = 0;
}

$sql_common = " qu_type     = '{$qu_type}',
                qu_state    = '{$qu_state}',
                qu_complete_type = '{$qu_complete_type}',
                qu_submit_type = '{$qu_submit_type}',
                qu_request_item = '{$qu_request_item}',
                qu_request_item_count = '{$qu_request_item_count}',
                qu_blind    = '{$qu_blind}',
                qu_title    = '{$qu_title}',
                qu_content  = '{$qu_content}',
                qu_content2 = '{$qu_content2}',
                qu_end_msg  = '{$qu_end_msg}',
                qu_money    = '{$qu_money}',
                qu_exp      = '{$qu_exp}',
                it_id       = '{$it_id}',
                ti_id       = '{$ti_id}',
                qu_take_max = '{$qu_take_max}',
                qu_view     = '{$qu_view}'
                ";


if($w == '') { 
    $sql = " INSERT INTO {$g5['k_quest_table']}
                SET {$sql_common},
                    qu_datetime = '".G5_TIME_YMDHIS."'";
    sql_query($sql);
} else {
    $qu = sql_fetch("SELECT qu_id FROM {$g5['k_quest_table']} WHERE qu_id = '{$qu_id}'");

    if(!$qu['qu_id']) {
        alert("퀘스트 정보가 존재하지 않습니다.");
    }
    $sql = " UPDATE {$g5['k_quest_table']}
                SET {$sql_common}
                WHERE qu_id = '{$qu['qu_id']}'";
    sql_query($sql);

}
goto_url('./993_quest_list.php?'.$qstr, false);
?>
