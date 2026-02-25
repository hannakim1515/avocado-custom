<?php
$sub_menu = "993200";
include_once('./_common.php');

check_demo();

// 파라미터 처리
$act_button = ses($_POST, 'act_button', '', 'raw');
$chk = ses($_POST, 'chk', array(), 'array');

if (!count($chk)) {
    alert($act_button." 하실 항목을 하나 이상 체크하세요.");
}

if ($act_button == "선택수정") {

    auth_check($auth[$sub_menu], 'w');

    for ($i=0; $i<count($chk); $i++) {

        // 실제 번호를 넘김
        $k = (int)$chk[$i];
        
        $qu_id_k = ses($_POST['qu_id'], $k, 0, 'int');
        $qu_type_k = ses($_POST['qu_type'], $k, 'sub', 'string');
        $qu_blind_k = ses($_POST['qu_blind'], $k, 0, 'int');
        $qu_title_k = ses($_POST['qu_title'], $k, '', 'string');
        $qu_content_k = ses($_POST['qu_content'], $k, '', 'string');
        $qu_content2_k = ses($_POST['qu_content2'], $k, '', 'string');
        $qu_end_msg_k = ses($_POST['qu_end_msg'], $k, '', 'string');
        $qu_money_k = ses($_POST['qu_money'], $k, 0, 'int');
        $qu_exp_k = ses($_POST['qu_exp'], $k, 0, 'int');
        $it_id_k = ses($_POST['it_id'], $k, 0, 'int');
        $ti_id_k = ses($_POST['ti_id'], $k, 0, 'int');
        $qu_take_now_k = ses($_POST['qu_take_now'], $k, 0, 'int');
        $qu_take_max_k = ses($_POST['qu_take_max'], $k, 0, 'int');
        $qu_state_k = ses($_POST['qu_state'], $k, 'active', 'string');
        
        // qu_state 유효성 검사
        if (!in_array($qu_state_k, array('hidden', 'active', 'done'))) {
            $qu_state_k = 'active';
        }
        
        // 상태가 done이거나 event_id에 end가 포함된 경우 종료 처리
        if($qu_state_k == 'done'){
            // 퀘스트 정보 조회
            $qu = sql_fetch("SELECT * FROM {$g5['k_quest_table']} WHERE qu_id = '{$qu_id_k}'");
            
            // 종료 퀘스트 실패처리
            sql_query("UPDATE {$g5['k_quest_has_table']} 
                       SET qh_state = '실패', qh_endtime = '".G5_TIME_YMDHIS."' 
                       WHERE qu_id = '{$qu_id_k}' AND qh_state = '수행중'");

            // 멤버 퀘스트인 경우 남은 보상 아이템 반환
            if ($qu && $qu['qu_type'] == 'member' && $qu['qu_ch_id']) {
                move_quest_item($qu_id_k, 'return');
            }
        }

        $sql = " UPDATE {$g5['k_quest_table']}
                    SET qu_type     = '{$qu_type_k}',
                        qu_blind    = '{$qu_blind_k}',
                        qu_title    = '{$qu_title_k}',
                        qu_content  = '{$qu_content_k}',
                        qu_content2 = '{$qu_content2_k}',
                        qu_money    = '{$qu_money_k}',
                        qu_exp      = '{$qu_exp_k}',
                        qu_end_msg  = '{$qu_end_msg_k}',
                        it_id       = '{$it_id_k}',
                        ti_id       = '{$ti_id_k}',
                        qu_state    = '{$qu_state_k}',
                        qu_take_now = '{$qu_take_now_k}',
                        qu_take_max = '{$qu_take_max_k}' 
                  WHERE qu_id       = '{$qu_id_k}' ";
        sql_query($sql);
    }

} else if ($act_button == "선택삭제") {
    auth_check($auth[$sub_menu], 'd');
    check_token();

    for ($i=0; $i<count($chk); $i++) {
        $k = (int)$chk[$i];
        $temp_qu_id = ses($_POST['qu_id'], $k, 0, 'int');
        if (!$temp_qu_id) { continue; }

        // 관련 수행 데이터도 삭제
        sql_query("DELETE FROM {$g5['k_quest_has_table']} WHERE qu_id = '{$temp_qu_id}'");
        sql_query("DELETE FROM {$g5['k_quest_table']} WHERE qu_id = '{$temp_qu_id}'");
    }

} else if ($act_button == "일괄종료") {
    // 일괄종료: event_id='end', 수행중→실패, 멤버 퀘스트 남은 보상 반환
    auth_check($auth[$sub_menu], 'w');
    check_token();

    for ($i=0; $i<count($chk); $i++) {
        $k = (int)$chk[$i];
        $temp_qu_id = ses($_POST['qu_id'], $k, 0, 'int');
        if (!$temp_qu_id) { continue; }

        // 퀘스트 정보 조회
        $qu = sql_fetch("SELECT * FROM {$g5['k_quest_table']} WHERE qu_id = '{$temp_qu_id}'");
        if (!$qu['qu_id']) { continue; }

        // 수행중인 경우 실패 처리
        sql_query("UPDATE {$g5['k_quest_has_table']} 
                   SET qh_state = '실패', qh_endtime = '".G5_TIME_YMDHIS."' 
                   WHERE qu_id = '{$temp_qu_id}' AND qh_state = '수행중'");

        // 멤버 퀘스트인 경우 남은 보상 아이템 반환
        if ($qu['qu_type'] == 'member' && $qu['qu_ch_id']) {
            move_quest_item($temp_qu_id, 'return');
        }

        // 퀘스트 종료 처리
        sql_query("UPDATE {$g5['k_quest_table']} SET qu_state = 'done', event_id = 'end' WHERE qu_id = '{$temp_qu_id}'");
    }

} else if ($act_button == "일괄완료") {
    // 일괄완료: event_id='end', 수행중→보상 지급 후 완료, 멤버 퀘스트 남은 보상 반환
    auth_check($auth[$sub_menu], 'w');
    check_token();

    for ($i=0; $i<count($chk); $i++) {
        $k = (int)$chk[$i];
        $temp_qu_id = ses($_POST['qu_id'], $k, 0, 'int');
        if (!$temp_qu_id) { continue; }

        // 퀘스트 정보 조회
        $qu = sql_fetch("SELECT * FROM {$g5['k_quest_table']} WHERE qu_id = '{$temp_qu_id}'");
        if (!$qu['qu_id']) { continue; }

        // 수행중인 사람들 조회
        $res_qh = sql_query("SELECT qh.qh_id, qh.ch_id 
                             FROM {$g5['k_quest_has_table']} qh 
                             WHERE qh.qu_id = '{$temp_qu_id}' AND qh.qh_state = '수행중'");
        
        while ($qh = sql_fetch_array($res_qh)) {
            // complete_quest 함수로 퀘스트 완료 처리
            complete_quest($qh['qh_id'], $qh['ch_id'], '');
        }

        // 멤버 퀘스트인 경우 남은 보상 아이템 반환
        if ($qu['qu_type'] == 'member' && $qu['qu_ch_id']) {
            move_quest_item($temp_qu_id, 'return');
        }

        // 퀘스트 종료 처리
        sql_query("UPDATE {$g5['k_quest_table']} SET qu_state = 'done', event_id = 'end' WHERE qu_id = '{$temp_qu_id}'");
    }
}

goto_url('./993_quest_list.php?'.$qstr."&cate=".$cate."&state=".$state);
?>
