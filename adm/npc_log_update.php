<?php
$sub_menu = '400900';
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_admin_token();

$count = count($_POST['chk']);
if(!$count)
	alert($_POST['act_button'].' 하실 항목을 하나 이상 체크하세요.');

for ($i=0; $i<count($_POST['chk']); $i++) {
	// 실제 번호를 넘김
	$k = $_POST['chk'][$i];
	if ($_POST['act_button'] == "선택삭제") {
		$sql = " delete from {$g5['npc_log_table']} where nl_id = '{$_POST['nl_id'][$k]}' ";
		sql_query($sql);
	}
}

goto_url('./npc_log.php?'.$qstr.'&amp;ns_id='.$ns_id);
?>