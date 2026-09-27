<?php
include_once('./_common.php');

$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;
$ma = get_map($ma_id);

if(!$ma['ma_id']) {
	exit;
}

$open_dungeon = array();
if($use_dungeon_map && function_exists('get_map_dungeon')) {
	$open_dungeon = get_map_dungeon($ma['ma_id']);
}
$is_current_map = ((int)$character['ma_id'] === (int)$ma['ma_id']);
?>

<div class="map-pannel map-vn-pannel">
	<? if(!empty($open_dungeon['ds_id']) && $open_dungeon['ds_state'] == 'S') { ?>
		<div class="map-vn-dungeon-notice">
			<strong>던전 발생</strong>
			<span><?=get_text($open_dungeon['dg_title'])?> · <?=get_text($open_dungeon['dg_mon_name'])?></span>
			<? if($is_current_map && $character['ch_state'] == '승인') { ?>
				<button type="button" class="map-vn-action-choice dungeon-enter" onclick="if(confirm('열린 던전에 입장하시겠습니까?')) location.href='<?=G5_URL?>/dungeon/applicate.php?ds_id=<?=$open_dungeon['ds_id']?>';">
					<span>던전 입장</span>
					<em>현재 지역에서 입장할 수 있습니다.</em>
				</button>
			<? } ?>
		</div>
	<? } ?>

	<? if($character['ch_state'] == '승인' && $is_current_map) { ?>
		<? $map_vn_mode = true; ?>
		<? include(G5_PATH."/map/inc/action_list.php"); ?>
	<? } else { ?>
		<div class="map-actions map-actions-vn">
			<button type="button" class="map-vn-action-choice" onclick="map_move(<?=$ma_id?>);">
				<span><?=get_text($ma['ma_name'])?>로 이동</span>
				<em>클릭하면 이동합니다</em>
			</button>
		</div>
	<? } ?>
</div>
