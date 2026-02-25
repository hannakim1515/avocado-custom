<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

if($ds['ds_state'] == "E") {
	include(G5_PATH."/dungeon/inc/detail_monster.end.php");
} else {
	include(G5_PATH."/dungeon/inc/detail_monster.live.php");
}

?>
