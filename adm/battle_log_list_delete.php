<?php
$sub_menu = "910210";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_token();

print_r($_POST);

for ($i=0; $i<count($_POST['chk']); $i++) {
	$k = $_POST['chk'][$i];
	$temp_bl_id = trim($_POST['bl_id'][$k]);
	if (!$temp_bl_id) { return; }

	sql_query(" delete from {$g5['battle_log_table']} where bl_id = '{$temp_bl_id}'");
}


goto_url('./battle_log_list.php?'.$qstr);
?>
