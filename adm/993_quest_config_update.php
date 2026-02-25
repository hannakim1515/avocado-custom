<?php
$sub_menu = "993100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

$act_button = ses($_POST, 'act_button', '', 'raw');

// 분기종료 처리
if ($act_button == '분기종료') {
    check_token();
    // 1. 종료되지 않은 멤버 퀘스트 일괄 종료 처리
    $res_member_quest = sql_query("SELECT qu_id, qu_ch_id, qu_take_max 
                                   FROM {$g5['k_quest_table']} 
                                   WHERE qu_type = 'member' AND qu_state != 'done'");
    
    while ($qu = sql_fetch_array($res_member_quest)) {
        $temp_qu_id = $qu['qu_id'];
        
        // 수행중인 경우 실패 처리
        sql_query("UPDATE {$g5['k_quest_has_table']} 
                   SET qh_state = '실패', qh_endtime = '".G5_TIME_YMDHIS."' 
                   WHERE qu_id = '{$temp_qu_id}' AND qh_state = '수행중'");
        
        // 완료자 수 확인
        $done_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['k_quest_has_table']} 
                               WHERE qu_id = '{$temp_qu_id}' AND qh_state = '완료'");
        
        // 완료자가 수행 가능 인원보다 적으면 남은 보상 아이템 반환 (qu_id 연결 해제)
        if ($qu['qu_ch_id'] && $qu['qu_take_max'] > 0 && $done_cnt['cnt'] < $qu['qu_take_max']) {
            sql_query("UPDATE {$g5['inventory_table']} SET qu_id = 0 WHERE qu_id = '{$temp_qu_id}'");
        }
        
        // 퀘스트 종료 처리
        sql_query("UPDATE {$g5['k_quest_table']} SET qu_state = 'done', event_id = 'end' WHERE qu_id = '{$temp_qu_id}'");
    }
    
    // 2. 모든 캐릭터의 멤버 퀘스트 수행/등록 카운트 초기화
    sql_query("UPDATE {$g5['character_table']} SET ch_receive_quest = 0, ch_give_quest = 0");
    
    // 3. 마지막 리셋 날짜 업데이트
    sql_query("UPDATE {$g5['k_quest_config_table']} SET qc_last_reset_date = CURDATE()");
    
    goto_url('./993_quest_config.php');
}

// 설정 저장 처리
check_token();

// 테이블 존재 여부 확인 (개선된 로직)
$table_check_result = sql_query("SHOW TABLES LIKE '{$g5['k_quest_config_table']}'");
if (!$table_check_result || sql_num_rows($table_check_result) == 0) {
    alert('퀘스트 설정 테이블이 존재하지 않습니다.\\n\\n993_quest_config.php 페이지에서 자동 설치를 진행하거나\\nquest/database.sql 파일을 수동으로 실행해주세요.', './993_quest_config.php');
}

// POST 값 안전하게 획득
$qc_title                   = ses($_POST, 'qc_title', '퀘스트', 'string');
$qc_register_reward_type    = ses($_POST, 'qc_register_reward_type', 'money', 'string');
$qc_register_reward_value   = ses($_POST, 'qc_register_reward_value', 0, 'int');
$qc_register_reward_item_id = ses($_POST, 'qc_register_reward_item_id', 0, 'int');
$qc_complete_reward_type    = ses($_POST, 'qc_complete_reward_type', 'money', 'string');
$qc_complete_reward_value   = ses($_POST, 'qc_complete_reward_value', 0, 'int');
$qc_complete_reward_item_id = ses($_POST, 'qc_complete_reward_item_id', 0, 'int');
$qc_quarter_receive_max     = ses($_POST, 'qc_quarter_receive_max', 3, 'int');
$qc_quarter_give_max        = ses($_POST, 'qc_quarter_give_max', 2, 'int');
$qc_member_take_max         = ses($_POST, 'qc_member_take_max', 3, 'int');
$qc_reset_days              = ses($_POST, 'qc_reset_days', 7, 'int');
$qc_main_display_count      = ses($_POST, 'qc_main_display_count', 3, 'int');
$qc_member_quest_enabled    = ses($_POST, 'qc_member_quest_enabled', 1, 'int');
$qc_member_quest_type_arr   = ses($_POST, 'qc_member_quest_type', array(), 'array');
$qc_main_color              = ses($_POST, 'qc_main_color', '#d4a373', 'string');
$qc_sub_color               = ses($_POST, 'qc_sub_color', '#8EACBB', 'string');
$qc_member_color            = ses($_POST, 'qc_member_color', '#509164', 'string');
$qc_main_text_color         = ses($_POST, 'qc_main_text_color', 'white', 'string');
$qc_sub_text_color          = ses($_POST, 'qc_sub_text_color', 'white', 'string');
$qc_member_text_color       = ses($_POST, 'qc_member_text_color', 'white', 'string');

// 멤버 퀘스트 타입 유효성 검사 (최소 1개 선택 필수)
$valid_types = array('mmb', 'direct');
$qc_member_quest_type_arr = array_intersect($qc_member_quest_type_arr, $valid_types);
if (empty($qc_member_quest_type_arr)) {
    $qc_member_quest_type_arr = array('mmb'); // 기본값
}
$qc_member_quest_type = sql_escape_string(implode(',', $qc_member_quest_type_arr));

// 보상 타입에 따라 값 결정 (등록 보상)
if ($qc_register_reward_type == 'item') {
    $qc_register_reward_value = $qc_register_reward_item_id;
}

// 보상 타입에 따라 값 결정 (완료 보상)
if ($qc_complete_reward_type == 'item') {
    $qc_complete_reward_value = $qc_complete_reward_item_id;
}

// 유효성 검사
if (!in_array($qc_register_reward_type, array('exp', 'money', 'item'))) {
    $qc_register_reward_type = 'money';
}
if (!in_array($qc_complete_reward_type, array('exp', 'money', 'item'))) {
    $qc_complete_reward_type = 'money';
}
if ($qc_reset_days < 1) $qc_reset_days = 1;
if ($qc_member_take_max < 1) $qc_member_take_max = 1;
if (empty($qc_title)) $qc_title = '퀘스트';

// 기존 설정이 있는지 확인
$exists = sql_fetch("SELECT qc_id FROM {$g5['k_quest_config_table']} LIMIT 1");

if ($exists['qc_id']) {
    // UPDATE
    $sql = "
        UPDATE {$g5['k_quest_config_table']} SET
            qc_title = '{$qc_title}',
            qc_register_reward_type = '{$qc_register_reward_type}',
            qc_register_reward_value = '{$qc_register_reward_value}',
            qc_complete_reward_type = '{$qc_complete_reward_type}',
            qc_complete_reward_value = '{$qc_complete_reward_value}',
            qc_quarter_receive_max = '{$qc_quarter_receive_max}',
            qc_quarter_give_max = '{$qc_quarter_give_max}',
            qc_member_take_max = '{$qc_member_take_max}',
            qc_reset_days = '{$qc_reset_days}',
            qc_main_display_count = '{$qc_main_display_count}',
            qc_member_quest_enabled = '{$qc_member_quest_enabled}',
            qc_member_quest_type = '{$qc_member_quest_type}',
            qc_main_color = '{$qc_main_color}',
            qc_sub_color = '{$qc_sub_color}',
            qc_member_color = '{$qc_member_color}',
            qc_main_text_color = '{$qc_main_text_color}',
            qc_sub_text_color = '{$qc_sub_text_color}',
            qc_member_text_color = '{$qc_member_text_color}'
        WHERE qc_id = '{$exists['qc_id']}'
    ";
} else {
    // INSERT (테이블에 데이터가 없는 경우)
    $sql = "
        INSERT INTO {$g5['k_quest_config_table']} SET
            qc_title = '{$qc_title}',
            qc_register_reward_type = '{$qc_register_reward_type}',
            qc_register_reward_value = '{$qc_register_reward_value}',
            qc_complete_reward_type = '{$qc_complete_reward_type}',
            qc_complete_reward_value = '{$qc_complete_reward_value}',
            qc_quarter_receive_max = '{$qc_quarter_receive_max}',
            qc_quarter_give_max = '{$qc_quarter_give_max}',
            qc_member_take_max = '{$qc_member_take_max}',
            qc_reset_days = '{$qc_reset_days}',
            qc_main_display_count = '{$qc_main_display_count}',
            qc_member_quest_enabled = '{$qc_member_quest_enabled}',
            qc_member_quest_type = '{$qc_member_quest_type}',
            qc_last_reset_date = CURDATE(),
            qc_main_color = '{$qc_main_color}',
            qc_sub_color = '{$qc_sub_color}',
            qc_member_color = '{$qc_member_color}',
            qc_main_text_color = '{$qc_main_text_color}',
            qc_sub_text_color = '{$qc_sub_text_color}',
            qc_member_text_color = '{$qc_member_text_color}'
    ";
}

// SQL 실행 및 오류 검증
$result = sql_query($sql);
if (!$result) {
    // MySQLi 오류 가져오기
    global $g5;
    $mysqli_error = '';
    if (isset($g5['connect_db']) && $g5['connect_db']) {
        $mysqli_error = mysqli_error($g5['connect_db']);
    }
    
    alert('설정 저장 중 오류가 발생했습니다.\\n\\nMySQL 오류: ' . $mysqli_error);
}

goto_url('./993_quest_config.php');
