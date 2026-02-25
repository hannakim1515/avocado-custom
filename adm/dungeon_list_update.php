<?php
$sub_menu = "730100";
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

		$ma_ids = $_POST['ma_ids'][$k] = "||".implode("||", $_POST['ma_ids'][$k])."||";

		$sql = " update {$g5['dungeon_table']}
					set dg_title         = '{$_POST['dg_title'][$k]}',
						ma_ids			= '{$ma_ids}',
						dg_rank         = '{$_POST['dg_rank'][$k]}',
						dg_per_s         = '{$_POST['dg_per_s'][$k]}',
						dg_per_e         = '{$_POST['dg_per_e'][$k]}',
						dg_count         = '{$_POST['dg_count'][$k]}',
						dg_use         = '{$_POST['dg_use'][$k]}'
				  where dg_id               = '{$_POST['dg_id'][$k]}' ";
		sql_query($sql);
	}

} else if ($_POST['act_button'] == "선택삭제") {
	auth_check($auth[$sub_menu], 'd');
	check_token();

	for ($i=0; $i<count($_POST['chk']); $i++) {
		$k = $_POST['chk'][$i];
		$temp_dg_id = trim($_POST['dg_id'][$k]);
		if (!$temp_dg_id) { return; }

		$dg = sql_fetch("select dg_mon_img from {$g5['dungeon_table']} where dg_id = '{$tmp_dg_id}'");
		
		//$prev_file_path = str_replace(G5_URL, G5_PATH, $dg['dg_mon_img']);
		//@unlink($prev_file_path);
		
		sql_query(" delete from {$g5['dungeon_table']} where dg_id = '{$temp_dg_id}'");
		//sql_query(" delete from {$g5['dungeon_state_table']} where dg_id = '{$temp_dg_id}'");
		//sql_query(" delete from {$g5['dungeon_member_table']} where dg_id = '{$temp_dg_id}'");
		//sql_query(" delete from {$g5['dungeon_log_table']} where dg_id = '{$temp_dg_id}'");
	}
}

goto_url('./dungeon_list.php?'.$qstr);
?>
