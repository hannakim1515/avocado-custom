<?php
$sub_menu = "730200";
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {

	auth_check($auth[$sub_menu], 'w');

	for ($i=0; $i<count($_POST['chk']); $i++) {

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$sql = " update {$g5['dungeon_state_table']}
					set ds_hurt         = '{$_POST['ds_hurt'][$k]}',
						ds_hp         = '{$_POST['ds_hp'][$k]}'
				  where ds_id               = '{$_POST['ds_id'][$k]}' ";
		sql_query($sql);
	}

} else if ($_POST['act_button'] == "선택삭제") {
	auth_check($auth[$sub_menu], 'd');
	check_token();

	for ($i=0; $i<count($_POST['chk']); $i++) {
		$k = $_POST['chk'][$i];
		$temp_ds_id = trim($_POST['ds_id'][$k]);
		if (!$temp_ds_id) { return; }
		sql_query(" delete from {$g5['dungeon_state_table']} where ds_id = '{$temp_ds_id}'");
	}
}

goto_url('./dungeon_state_list.php?'.$qstr);
?>
