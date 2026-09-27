<?php
include_once('./_common.php');
// 지역 이동 체크
// 통행체크
$msg = "";
if(!$ma_id) $ma_id = $idx;
$lock = false;
$ma = get_map($ma_id);
$move_money = sql_fetch("select * from {$g5['map_move_table']} where mf_start = '{$character['ma_id']}' and mf_end = '{$ma['ma_id']}' and mf_use = '1'");

if(!$ma['ma_use']) {
	$msg = "이동할 수 없는 지역입니다.";
	$lock = true;
}

if($use_dungeon_map && (function_exists('is_has_dungeon') && is_has_dungeon($character['ch_id']))) {
	$msg = "던전 진행 중에는 이동할 수 없습니다.";
	$lock = true;
}

if(!$lock) { 
	// 이동이 가능할 경우
	// 캐릭터 위치를 이동시킨다.
	set_move_map($character['ch_id'], $ma_id);
}
//$side_cost = get_gold($member['mb_id']);

$ma_name_js = json_encode($ma['ma_name']);
$ma_bg_img = trim(isset($ma['ma_img']) ? $ma['ma_img'] : '');
if($ma_bg_img === '' && isset($ma['ma_parent']) && (int)$ma['ma_parent'] > 0 && (int)$ma['ma_parent'] !== (int)$ma['ma_id']) {
	$parent_img = sql_fetch("select ma_img from {$g5['map_table']} where ma_id = '".(int)$ma['ma_parent']."'");
	$ma_bg_img = isset($parent_img['ma_img']) ? trim($parent_img['ma_img']) : '';
}
$ma_bg_img_js = json_encode($ma_bg_img);
$npc_img_js = json_encode('');
$npc_name_js = json_encode($ma['ma_name']);
$ma_content = map_replace_script_vars($ma['ma_content'], array(
	'map_name' => $ma['ma_name'],
	'npc_name' => $ma['ma_name']
));
$ma_content_js = json_encode($ma_content);
?>

<script>
<? if($msg) { ?>alert("<?=$msg?>");<? } ?>

<? if(!$lock) { ?>
	$('.map-img-viewer .anker a').removeClass('on');
	$('.map-img-viewer .anker a[data-idx="<?=$ma_id?>"]').addClass('on');
	if(typeof map_vn_after_move == 'function') {
		map_vn_after_move(<?=$ma_id?>, <?=$ma_name_js?>, <?=$npc_img_js?>, <?=$npc_name_js?>, <?=$ma_content_js?>, <?=$ma_bg_img_js?>);
	} else {
		open_map_pannel(<?=$ma_id?>);
		if(typeof map_set_scene_npc == 'function') {
			map_set_scene_npc(<?=$npc_img_js?>, <?=$npc_name_js?>);
		}
	}
<? } else { ?>
	if(typeof mapVnMovingTo != 'undefined') mapVnMovingTo = 0;
	open_map_pannel(<?=$ma_id?>);
<? } ?>
</script>
