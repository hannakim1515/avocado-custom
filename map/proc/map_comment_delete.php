<?php
include_once('./_common.php');

$mc = sql_fetch("select * from {$g5['map_comment_table']} where mc_id = '{$mc_id}'");

if(!$is_admin && (!$mc['mc_id'] || $mc['ch_id'] != $character['ch_id'])) {
	echo "올바른 방법으로 이용해 주시길 바랍니다.";
	exit;
}
$ma_id = $mc['ma_id'];
sql_query("delete from {$g5['map_comment_table']} where mc_id = '{$mc['mc_id']}'");
include(G5_PATH."/map/map_comment_list.php");
?>