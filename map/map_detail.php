<?php
include_once('./_common.php');

$ma = get_map($ma_id);

if(!$ma['ma_id']) {
	echo '<div class="map-pannel"><div class="map-detail"><p class="map-notice">지역 정보를 찾을 수 없습니다.</p></div></div>';
	exit;
}

/* 최상위 지역도 던전 개최 지역으로 지정될 수 있다. 기존에는 그룹 패널을 먼저
 * 반환해 게이트가 있어도 입장 버튼이 사라졌으므로, 던전 상태를 가장 먼저 확인한다. */
$dg = array();
if($use_dungeon_map && function_exists('get_map_dungeon')) $dg = get_map_dungeon($ma['ma_id']);
if(!empty($dg['ds_state']) && $dg['ds_state'] == 'S') {
	include(G5_PATH.'/map/inc/info_dungeon.php');
} elseif($ma['ma_parent'] == $ma['ma_id']) {
	include(G5_PATH.'/map/inc/info_group.php');
} else {
	include(G5_PATH.'/map/inc/info_default.php');
}

?>

