<?php
$sub_menu = "993300";
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
        $k = (int)$chk[$i];
        
        $qh_id_k = ses($_POST['qh_id'], $k, 0, 'int');
        $qh_state_k = ses($_POST['qh_state'], $k, '수행중', 'string');
        
        if (!$qh_id_k) { continue; }
        
        // 유효한 상태값인지 확인
        if (!in_array($qh_state_k, array('수행중', '완료', '실패'))) {
            $qh_state_k = '수행중';
        }
        
        // 현재 상태 조회
        $qh_current = sql_fetch("SELECT qh_state FROM {$g5['k_quest_has_table']} WHERE qh_id = '{$qh_id_k}'");
        
        // 상태가 변경되었을 때만 업데이트
        if ($qh_current['qh_state'] != $qh_state_k) {
            $time = G5_TIME_YMDHIS;
            
            // 완료/실패로 변경 시 종료 시간 설정
            if ($qh_state_k == '완료' || $qh_state_k == '실패') {
                sql_query("UPDATE {$g5['k_quest_has_table']} 
                           SET qh_state = '{$qh_state_k}', qh_endtime = '{$time}' 
                           WHERE qh_id = '{$qh_id_k}'");
            } else {
                // 수행중으로 변경 시 종료 시간 초기화
                sql_query("UPDATE {$g5['k_quest_has_table']} 
                           SET qh_state = '{$qh_state_k}', qh_endtime = NULL 
                           WHERE qh_id = '{$qh_id_k}'");
            }
        }
    }

} else if ($act_button == "선택삭제") {
    auth_check($auth[$sub_menu], 'd');
    check_token();

    for ($i=0; $i<count($chk); $i++) {
        $k = (int)$chk[$i];
        $temp_qh_id = ses($_POST['qh_id'], $k, 0, 'int');
        if (!$temp_qh_id) { continue; }

        sql_query("DELETE FROM {$g5['k_quest_has_table']} WHERE qh_id = '{$temp_qh_id}'");
    }
}

$sfl = ses($_POST, 'sfl', '', 'raw');
$stx = ses($_POST, 'stx', '', 'string');
$sst = ses($_POST, 'sst', '', 'raw');
$sod = ses($_POST, 'sod', '', 'raw');
$page = ses($_POST, 'page', 1, 'int');
$cate = ses($_POST, 'cate', '', 'raw');

goto_url('./993_quest_has_list.php?sfl='.$sfl.'&stx='.$stx.'&sst='.$sst.'&sod='.$sod.'&page='.$page.'&cate='.$cate);
?>
