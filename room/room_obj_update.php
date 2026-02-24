<?php
include_once('./_common.php');

if($ro_top) { 
	sql_query("update {$g5['room_table']} set ro_top='{$ro_top}', ro_left='{$ro_left}' where ro_id = '{$ro_id}'");
}
if($ro_order) { 
	sql_query("update {$g5['room_table']} set ro_order='{$ro_order}' where ro_id = '{$ro_id}'");
} 
?>
