<?php
include_once('./_common.php');

$ma = get_map($ma_id);

if($ma['ma_parent'] == $ma['ma_id']) { 
	include(G5_PATH.'/map/inc/info_group.php');
} else {
	if($use_dungeon_map && function_exists('get_map_dungeon')) {
		$dg = get_map_dungeon($ma['ma_id']);
		if($dg['ds_state'] == 'S') {
			include(G5_PATH.'/map/inc/info_dungeon.php');
		} else {
			include(G5_PATH.'/map/inc/info_default.php');
		}
	} else {
		include(G5_PATH.'/map/inc/info_default.php');
	}
}

?>

