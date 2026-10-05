<?php
include_once('./_common.php');

$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;
$ma = get_map($ma_id);

if(!$ma['ma_id']) {
	exit;
}

$open_dungeons = array();
if($show_dungeon_map && function_exists('get_map_open_dungeons')) {
	$open_dungeons = get_map_open_dungeons($ma['ma_id']);
}
$is_current_map = ((int)$character['ma_id'] === (int)$ma['ma_id']);
?>

<div class="map-pannel map-vn-pannel">
	<? foreach($open_dungeons as $open_dungeon) {
		$maze_entry_label = maze_entry_label((int)$open_dungeon['ds_id'], (int)$character['ch_id']);
	?>
		<div class="map-vn-dungeon-notice" data-dungeon-id="<?=(int)$open_dungeon['ds_id']?>">
			<strong>던전 발생</strong>
			<span><?=get_text($open_dungeon['dg_title'])?> · <?=get_text($open_dungeon['dg_mon_name'])?></span>
			<? if(($is_current_map || !$use_dungeon_map) && $character['ch_state'] == '승인' && $maze_entry_label !== '') { ?>
				<form method="post" action="<?=G5_URL?>/dungeon/maze.php" onsubmit="return confirm('열린 던전에 입장하시겠습니까?');">
					<input type="hidden" name="token" value="<?=get_text(inventory_boundary_token())?>"><input type="hidden" name="ds_id" value="<?=(int)$open_dungeon['ds_id']?>"><input type="hidden" name="operation" value="join">
				<button type="<?=$maze_entry_label==='입장하기'?'submit':'button'?>" class="map-vn-action-choice dungeon-enter" <?=$maze_entry_label==='입장하기'?'':'onclick="location.href=\''.G5_URL.'/dungeon/maze.php?ds_id='.(int)$open_dungeon['ds_id'].'\';"'?>>
					<span><?=get_text($maze_entry_label)?></span>
					<em><?=$use_dungeon_map?'현재 지역에서 입장할 수 있습니다.':'지역 제한 없이 입장할 수 있습니다.'?></em>
				</button>
				</form>
			<? } elseif($maze_entry_label === '') { ?>
				<p>탐험 진행 중 · 출발 후에는 새로 참가할 수 없습니다.</p>
			<? } elseif($character['ch_state'] === '승인') { ?>
				<p>이 지역으로 이동하면 입장할 수 있습니다.</p>
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
