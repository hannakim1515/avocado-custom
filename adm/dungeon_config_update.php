<?php
$sub_menu = "730100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

$sql = " update {$g5['config_table']}
			set cf_dungeon_time	= '{$_POST['cf_dungeon_time']}',
			    cf_dungeon_reset	= '{$_POST['cf_dungeon_reset']}',
				cf_dungeon_map	= '{$_POST['cf_dungeon_map']}',
			    cf_dungeon_open	= '{$_POST['cf_dungeon_open']}',
			    cf_dungeon_count	= '{$_POST['cf_dungeon_count']}',
				cf_dungeon_enter	= '{$_POST['cf_dungeon_enter']}'";
sql_query($sql);


goto_url('./dungeon_list.php?'.$qstr);
?>
