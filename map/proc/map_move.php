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
?>

<script>
<? if($msg) { ?>alert("<?=$msg?>");<? } ?>

open_map_pannel(<?=$ma_id?>);

<? if(!$lock) { ?>
	$('.map-img-viewer .anker a').removeClass('on');
	$('.map-img-viewer .anker a[data-idx="<?=$ma_id?>"]').addClass('on');
<? } ?>
</script>
