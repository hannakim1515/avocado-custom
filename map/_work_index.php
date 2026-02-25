<?php
include_once('./_common.php');
$g5['title'] = "지역";
include_once('./_head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_MAP_URL.'/css/style.css">', 0);

$ma_id = 0;
$pma_id = 0;
$map = array();

if($character['ma_id']) {
	$ma_id = $character['ma_id'];
	$map = sql_fetch("select ma_id, ma_parent from {$g5['map_table']} where ma_id = {$ma_id}");
	$pma_id = $map['ma_parent'];
} else {
	$map = sql_fetch("select ma_id, ma_parent from {$g5['map_table']} where ma_use = 1 order by ma_start desc, ma_id asc limit 0, 1");
	$ma_id = $map['ma_id'];
	$pma_id = $map['ma_parent'];
}
if(!$pma_id) {alert("지역정보를 확인할 수 없습니다.");}

if($config['cf_dungeon_open']) {
	// 게이트변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

$pma = get_map($map['ma_parent']);
$pma_list = sql_query("select * from {$g5['map_table']} where ma_use = 1 and ma_parent = '{$pma['ma_id']}' and ma_id != ma_parent");

$original_width = $pma['ma_width'];
$original_height = $pma['ma_height'];
$original_raito = ($pma['ma_height']/$pma['ma_width']);

$original_size = 20;

if(!$character['ma_id']) $character['ma_id'] = 1;


?>
<script src="<?php echo G5_MAP_URL ?>/js/script.js"></script>

<div class="map_wrap none-trans">
	<a href="<?=G5_URL?>/main.php" class="map-close"><img src="<?=G5_MAP_URL?>/img/btn_close_map.png" alt="" class="only-pc" /><img src="<?=G5_MAP_URL?>/img/btn_close_map_mo.png" alt="" class="not-pc"/></a>
	<div class="totalTitleBox">
		<strong><?=$pma['ma_name']?></strong>
	</div>

	<div class="map-img-viewer">
		<div class="bak">
			<div class="img" style="padding-top:<?=($original_raito*100)?>%; background-image:url(<?=$pma['ma_img']?>);"></div>
			<div class="anker">
				<?
					for($i=0; $ma = sql_fetch_array($pma_list); $i++) {
						$x = ($ma['ma_left'] / $original_width) * 100;
						$y = ($ma['ma_top'] / $original_height) * 100;

						$state = get_map_dungeon($ma['ma_id']);

						$class = "";
						if($character['ma_id'] == $ma['ma_id']) $class .= " on";
						if($state['ds_state'] == 'S') $class .= " open-gate";
					
				?>
				<a href="javascript:;" data-idx="<?=$ma['ma_id']?>" onclick="open_map_pannel(<?=$ma['ma_id']?>);" class="<?=$class?>" style="left:<?=$x?>%; top:<?=$y?>%; ">
					<span class="mark"></span>
					<span class="area"></span>
					<span class="check-area"></span>
					<span class="name"><em><?=$ma['ma_name']?></em></span>
				</a>
				<? } ?>
			</div>
		</div>
	</div>
</div>

<div class="map-descript-box not-mo"></div>

<div class="map-script"></div>

<div class="mobile-guide only-mo">
	<strong>모바일에서는 <span class="txt-point">지도만 확인</span>이 가능합니다.</strong>
</div>

<script>
	var original_width = <?=$original_width?>;
	var original_height = <?=$original_height?>;
	var raito = <?=$original_raito?>;
	var zoom = 1.2;
	var dragFlag = false;
	var x, y, pre_x, pre_y;
	var pre_w = 0;
	var pre_h = 0;
	var c_x = 0;
	var c_y = 0;
</script>
<script src="<?=G5_MAP_URL ?>/js/map.js"></script>
<script>
open_map_pannel(<?=$ma_id?>);
</script>

<?php
include_once('./_tail.sub.php');
?>


