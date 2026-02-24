<?php
include_once('./_common.php');

$ro = sql_fetch("select * from {$g5['room_table']} where ro_id = '{$ro_id}'");

if($ro['ro_id']) {
	$check_url = "/data/character/".$member['mb_id'];
	$prev_file_path = str_replace(G5_URL, G5_PATH, $ro['ro_img']);
	if(strstr($prev_file_path, $check_url)) {
		@unlink($prev_file_path);
	}
	sql_query(" delete from {$g5['room_table']} where ro_id = '{$ro_id}'");
	echo "Y";
}
?>
