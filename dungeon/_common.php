<?php
include_once('../common.php');

if($config['cf_dungeon_open']) {
	// 게이트변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

?>