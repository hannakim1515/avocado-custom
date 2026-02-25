<?php
$sub_menu = '720200';
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_token();

$count = count($_POST['chk']);
if(!$count)
    alert($_POST['act_button'].' 하실 항목을 하나 이상 체크하세요.');

if ($_POST['act_button'] == "선택수정") {
	
	for ($i=0; $i<count($_POST['chk']); $i++) {
		$sql_common = "";

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$sql_common = " sh_level		= '{$_POST['sh_level'][$k]}',
						sh_use			= '{$_POST['sh_use'][$k]}'";
		
		if($_POST['old_sh_use'][$k] != $_POST['sh_use'][$k]) {
			if($_POST['sh_use'][$k] == '1') {
				$sql_common .= ", sh_datetime = '".date('Y-m-d H:i:s')."' ";
			} else {
				$sql_common .= ", sh_datetime = '' ";
			}
		}

		$sql = " update {$g5['skill_has_table']} set {$sql_common} where sh_id = '{$_POST['sh_id'][$k]}' ";
		sql_query($sql);
	}
} else if ($_POST['act_button'] == "선택삭제") {

	$count = count($_POST['chk']);
	for ($i=0; $i<$count; $i++) {
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$sql = " delete from {$g5['skill_has_table']} where sh_id = '{$_POST['sh_id'][$k]}' ";
		sql_query($sql);
	}
}


goto_url('./skill_has_list.php?'.$qstr);
?>