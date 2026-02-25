<?php
$sub_menu = "730200";
include_once('./_common.php');
check_demo();

$dm = sql_fetch("select * from {$g5['dungeon_member_table']} where dm_id = '{$dm_id}'");
if(!$dm['dm_id']) { 
	alert("멤버 정보를 확인할 수 없습니다.");
}

sql_query(" delete from {$g5['dungeon_member_table']} where dm_id = '{$dm_id}'");
goto_url('./dungeon_state_list.php?'.$qstr);
?>
