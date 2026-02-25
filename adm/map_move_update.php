<?php
$sub_menu = "710200";
include_once('./_common.php');
check_demo();
auth_check($auth[$sub_menu], 'w');
$ma_id = $_REQUEST['ma_id'];

$ma_move = "||".implode("||",$ma_move)."||";
$sql = " update {$g5['map_table']}
		 set ma_move = '{$ma_move}' 
		where ma_id = '{$ma_id}'";
sql_query($sql);

goto_url('./map_move_list.php?ma_id='.$ma_id, false);
?>