<?php
$sub_menu = "710300";
include_once('./_common.php');
$ma_id = $_REQUEST['ma_id'];
check_demo();
auth_check($auth[$sub_menu], 'w');

if ($_POST['act_button'] == "《 지역이동") {
	for($i=0; $i < count($ch_insert); $i++) {
		$sql = " update {$g5['character_table']}
				 set ma_id = '{$ma_id}' 
				where ch_id = '{$ch_insert[$i]}'";
		sql_query($sql);
	}
}
if ($_POST['act_button'] == "지역이탈 》") {
	for($i=0; $i < count($ch_expert); $i++) {
		$sql = " update {$g5['character_table']}
				 set ma_id = '0' 
				where ch_id = '{$ch_expert[$i]}'";
		sql_query($sql);
	}
}

goto_url('./map_member_list.php?ma_id='.$ma_id);
?>
