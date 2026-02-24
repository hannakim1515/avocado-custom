<?php
header("Content-Type: application/json");
include_once('./_common.php');

$ro = sql_fetch("select * from {$g5['room_table']} where ro_id = '{$ro_id}'");

if($ro['ro_id']) {
	$change_ro = array();

	switch($dir) {
		case "prev" :
			// 이전일 경우
			$change_ro = sql_fetch("select * from {$g5['room_table']} where ro_order < '{$ro['ro_order']}' and ch_id = '{$ch_id}' order by ro_order desc limit 0, 1");
			if($change_ro['ro_id']) {
				// 이전의 데이터와 ro_order 교체
				sql_query("update {$g5['room_table']} set ro_order='{$ro['ro_order']}' where ro_id = '{$change_ro['ro_id']}'");
				sql_query("update {$g5['room_table']} set ro_order='{$change_ro['ro_order']}' where ro_id = '{$ro['ro_id']}'");
			}
		break;
		case "next" :
			// 다음일 경우
			$change_ro = sql_fetch("select * from {$g5['room_table']} where ro_order > '{$ro['ro_order']}' and ch_id = '{$ch_id}' order by ro_order asc limit 0, 1");
			if($change_ro['ro_id']) {
				// 이전의 데이터와 ro_order 교체
				sql_query("update {$g5['room_table']} set ro_order='{$ro['ro_order']}' where ro_id = '{$change_ro['ro_id']}'");
				sql_query("update {$g5['room_table']} set ro_order='{$change_ro['ro_order']}' where ro_id = '{$ro['ro_id']}'");
			}
		break;
	}
	echo $result;

	echo(json_encode(array(
		"other" => $change_ro['ro_id'],
		"other_order" => $ro['ro_order'],
		"my" => $ro['ro_id'],
		"my_order" => $change_ro['ro_order']
	)));
}
?>
