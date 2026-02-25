<?php
$sub_menu = '980330';
include_once('./_common.php');

check_admin_token();

// 입력 방어
$chk = ses($_POST, 'chk', array(), 'array');
$lo_id_post = ses($_POST, 'lo_id', array(), 'array');
$act_button = ses($_POST, 'act_button', '', 'raw');

$count = count($chk);
if (!$count) {
    alert($act_button.' 하실 항목을 하나 이상 체크하세요.');
}

// 선택된 키에서 lo_id 정수만 수집
$ids = array();
foreach ($chk as $k) {
    $id = ses($lo_id_post, $k, 0, 'int');
    if ($id > 0) { $ids[] = $id; }
}

// 유효 id 없으면 알림
if (!$ids) {
    alert('삭제할 대상이 없습니다.');
}

// 중복 제거
$ids = array_values(array_unique($ids));

// IN 절 구성 후 일괄 삭제
$in = implode(',', $ids);
sql_query("DELETE FROM {$g5['k_upgrade_log_table']} WHERE lo_id IN ({$in})");

goto_url('./980_k_equip_upgrade_log.php?'.$qstr);
?>
