<?php
include_once('./_common.php');

if($character['ma_id'] == $ma_id) {
	$sql = " insert into {$g5['map_comment_table']}
			set ma_id = '{$ma_id}',
				ch_id = '{$character['ch_id']}',
				mc_content = '{$comment}',
				mc_datetime = '".date('Y-m-d H:i:s')."'";
	sql_query($sql);
}
include(G5_PATH."/map/map_comment_list.php");
?>