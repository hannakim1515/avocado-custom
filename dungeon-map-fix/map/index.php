<?php
include_once('./_common.php');
$g5['title'] = "지역";
include_once('./_head.sub.php');

add_stylesheet('<link rel="stylesheet" href="'.G5_MAP_URL.'/css/style.css?v=26092301">', 0);

$ma_id = 0;
$pma_id = 0;
$start_map = null;
$map = array();
$map_config = array();

$map_top_list = array();
$map_sub_list = array();

$map_config_result = sql_query("select * from {$g5['map_table']} where ma_use = 1");
for($i=0; $m_rows = sql_fetch_array($map_config_result); $i++) {
	$map_config[$m_rows['ma_id']] = $m_rows;
	if($m_rows['ma_start'] == 1) {
		$start_map = $m_rows;
	}

	if($m_rows['ma_parent'] != $m_rows['ma_id']) {
		// 하위 지역일 경우, 부모 지역의 id 값을 index 로 하는 배열에 값을 추가한다.
		$map_sub_list[$m_rows['ma_parent']][] = $m_rows;
	} else {
		// 상위 지역일 경우, 전체 목록의 배열에 값을 추가한다.
		$map_top_list[] = $m_rows;
	}
}


// 현재 위치하고 있는 지역 정보
if($character['ma_id']) {
	$ma_id = $character['ma_id'];
	$map = $map_config[$ma_id];
	$pma_id = $map['ma_parent'];
} else {
	// 시작 지역 가져오기
	$map = $start_map;
	$ma_id = $map['ma_id'];
	$pma_id = $map['ma_parent'];

	sql_query(" update {$g5['character_table']} set ma_id = '{$ma_id}' where ch_id = '{$character['ch_id']}'"); 
	$character['ma_id'] = $ma_id;
}

if(!$pma_id) {alert("지역정보를 확인할 수 없습니다.");}

if($use_dungeon_map && function_exists('set_reset_dungeon')) {
	// 게이트변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

// The global map displays parent areas, while dungeon_state stores the exact
// child-area id. Index open dungeons for each visible area and its children.
$open_dungeons_by_map = array();
if($use_dungeon_map && !empty($g5['dungeon_state_table'])) {
	$open_dungeon_sql = sql_query("select ds.* from {$g5['dungeon_state_table']} ds where ds.ds_state = 'S' and ds.ds_ma_id > 0", false);
	if($open_dungeon_sql) {
		for($i = 0; $open_dungeon = sql_fetch_array($open_dungeon_sql); $i++) {
			$current_area_id = (int)$open_dungeon['ds_ma_id'];
			$seen_area_ids = array();
			while($current_area_id && isset($map_config[$current_area_id]) && !isset($seen_area_ids[$current_area_id])) {
				$seen_area_ids[$current_area_id] = true;
				if(!isset($open_dungeons_by_map[$current_area_id])) $open_dungeons_by_map[$current_area_id] = array();
				$open_dungeons_by_map[$current_area_id][] = $open_dungeon;
				$parent_area_id = (int)$map_config[$current_area_id]['ma_parent'];
				if($parent_area_id === $current_area_id) break;
				$current_area_id = $parent_area_id;
			}
		}
	}
}

$now_map = null;
$now_map_list = array();
$now_map_img = "";
$scene_map_img = "";
$original_width = 0;
$original_height = 0;
$original_size = 20;
$original_raito = 0;

if($config['cf_use_map_all'] && !$now_idx) {
	// 전체 지도를 사용하고, 선택된 상위 지역 정보가 없을 경우
	// 현재 지도 이미지는 전체 지도 이미지를 불러온다.
	// 지역 목록은 상위 지역으로 설정
	$now_map_img = $config['cf_map_all_img'];
	$scene_map_img = $now_map_img;
	$original_width = $config['cf_map_all_w'];
	$original_height = $config['cf_map_all_h'];
	$now_map_list = $map_top_list;
	$is_global_map = true;
} else if(!$now_idx) {
	// 전체 지도를 사용하지 않으면서 현재 선택된 지역 정보가 없을 경우
	// 현재 위치한 장소의 지도를 가져온다.
	$_now_map = $map;
	$now_map = $map_config[$_now_map['ma_parent']];

	$now_map_img = $now_map['ma_img'];
	$scene_map_img = trim(isset($_now_map['ma_img']) ? $_now_map['ma_img'] : '') !== '' ? $_now_map['ma_img'] : $now_map_img;
	$original_width = $now_map['ma_width'];
	$original_height = $now_map['ma_height'];
	$now_map_list = isset($map_sub_list[$pma_id]) ? $map_sub_list[$pma_id] : array();
	$is_global_map = false;

} else {
	// 전체 지도를 사용하지 않으면서 현재 선택한 정보가 있을 경우
	// 현재 선택한 지역의 정보를 가지고 온다.
	$now_map = $map_config[$now_idx];

	$now_map_img = $now_map['ma_img'];
	$scene_map_img = trim(isset($now_map['ma_img']) ? $now_map['ma_img'] : '');
	if($scene_map_img === '' && isset($map_config[$now_map['ma_parent']])) {
		$scene_map_img = $map_config[$now_map['ma_parent']]['ma_img'];
	}
	$original_width = $now_map['ma_width'];
	$original_height = $now_map['ma_height'];
	$now_map_list = isset($map_sub_list[$now_map['ma_parent']]) ? $map_sub_list[$now_map['ma_parent']] : array();
	$is_global_map = false;
}

// 이미지 비율 처리
$original_raito = ($original_height == 0 || $original_width == 0) ? 0 : ($original_height/$original_width);
if(!$character['ma_id']) $character['ma_id'] = $start_map['ma_id'];

$scene_character = $map_config[$character['ma_id']];
$scene_npc_img = '';
$scene_npc_name = '';
$character_map = $map_config[$character['ma_id']];

?>
<script src="<?php echo G5_MAP_URL ?>/js/script.js"></script>

<? if($is_global_map) { ?>
<div class="map_wrap none-trans">
	<a href="<?=G5_URL?>/main.php" class="map-close"><img src="<?=G5_MAP_URL?>/img/btn_close_map.png" alt="메인으로" /></a>

	<div class="map-img-viewer">
		<div class="bak">
			<div class="img" style="padding-top:<?=($original_raito*100)?>%; background-image:url(<?=$now_map_img?>);"></div>
			<div class="anker">
				<?
					for($i=0; $i < count($now_map_list); $i++) {
						$ma = $now_map_list[$i];
						$x = ($ma['ma_left'] == 0 || $original_width == 0) ? 0 : ($ma['ma_left'] / $original_width) * 100;
						$y = ($ma['ma_top'] == 0 || $original_height == 0) ? 0 : ($ma['ma_top'] / $original_height) * 100;
						$class = "";
						if($ma_id == $ma['ma_id'] || $pma_id == $ma['ma_id']) $class .= " on";
						$open_dungeon_count = isset($open_dungeons_by_map[$ma['ma_id']]) ? count($open_dungeons_by_map[$ma['ma_id']]) : 0;
						if($open_dungeon_count) $class .= " open-gate";
				?>
				<a href="javascript:;" data-idx="<?=$ma['ma_id']?>" onclick="open_map_pannel(<?=$ma['ma_id']?>);" class="<?=$class?>" style="left:<?=$x?>%; top:<?=$y?>%;">
					<span class="mark"></span>
					<span class="area"></span>
					<span class="check-area"></span>
					<span class="name"><em><?=get_text($ma['ma_name'])?></em></span>
					<? if($open_dungeon_count) { ?><span class="dungeon-alert">던전 발생<?=($open_dungeon_count > 1 ? ' '.$open_dungeon_count : '')?></span><? } ?>
				</a>
				<? } ?>
			</div>
		</div>
	</div>
</div>
<? } else { ?>
<div class="map_wrap map-vn-wrap none-trans">
	<a href="<?=G5_URL?>/main.php" class="map-close"><img src="<?=G5_MAP_URL?>/img/btn_close_map.png" alt="메인으로" /></a>

	<div class="map-vn-scene" style="background-image:url(<?=$scene_map_img?>);">
		<div class="map-vn-shade"></div>

		<div class="map-img-viewer">
			<div class="bak">
				<div class="img" style="padding-top:<?=($original_raito*100)?>%; background-image:url(<?=$now_map_img?>);"></div>
				<div class="anker">
					<?
						for($i=0; $i < count($now_map_list); $i++) {
							$ma = $now_map_list[$i];
							$class = "";
							if($ma_id == $ma['ma_id'] || $pma_id == $ma['ma_id']) $class .= " on";
					?>
					<a href="javascript:;" data-idx="<?=$ma['ma_id']?>" class="<?=$class?>"></a>
					<? } ?>
				</div>
			</div>
		</div>

		<div class="map-vn-character <?=$scene_npc_img ? 'active' : ''?>">
			<? if($scene_npc_img) { ?>
				<img src="<?=$scene_npc_img?>" alt="<?=get_text($scene_npc_name ? $scene_npc_name : $scene_character['ma_name'])?>" />
			<? } ?>
		</div>

		<div class="map-vn-choice-box">
			<div class="map-vn-place">
				<strong><?=isset($now_map['ma_name']) && $now_map['ma_name'] ? get_text($now_map['ma_name']) : '전체 지역'?></strong>
				<span>현재 위치: <?=get_map_name($character['ma_id'])?></span>
			</div>
			<div class="map-vn-choice-scroll">
				<ul>
				<?
					for($i=0; $i < count($now_map_list); $i++) {
						$ma = $now_map_list[$i];
						$is_group = ($ma['ma_id'] == $ma['ma_parent']);
						$is_current = ($character['ma_id'] == $ma['ma_id']);
						$is_parent_current = ($pma_id == $ma['ma_id']);
						$btn_class = $is_current || $is_parent_current ? "current" : "";
						$onclick = "";
						$label = "";
						$choice_text = get_text($ma['ma_name']);
						$open_dungeon_count = isset($open_dungeons_by_map[$ma['ma_id']]) ? count($open_dungeons_by_map[$ma['ma_id']]) : 0;

						if($is_group) {
							$onclick = "location.href='?now_idx=".$ma['ma_id']."';";
							$label = $is_parent_current ? "현재 구역" : "구역 보기";
							$choice_text = get_text($ma['ma_name'])." 보기";
						} else if($is_current) {
							$onclick = "map_vn_choice(".$ma['ma_id'].");";
							$label = "현재 위치";
						} else {
							$onclick = "map_vn_choice(".$ma['ma_id'].");";
							$label = "이동";
							$choice_text = get_text($ma['ma_name'])."로 이동";
						}
						if($open_dungeon_count) {
							$btn_class .= " dungeon-open";
							$label = "던전 발생";
							$choice_text = get_text($ma['ma_name'])." · 던전 발생";
						}
				?>
					<li>
						<button type="button" class="<?=$btn_class?>" data-map-id="<?=$ma['ma_id']?>" data-map-name="<?=get_text($ma['ma_name'])?>" onclick="<?=$onclick?>" ondblclick="map_vn_move_choice(<?=$ma['ma_id']?>);">
							<strong><?=$choice_text?></strong>
							<span><?=$label?></span>
						</button>
					</li>
				<? } ?>
				</ul>
			</div>
		</div>

		<div class="map-vn-action-box" style="display:none;"></div>

		<div class="map-vn-dialog" style="display:none;">
			<div class="map-vn-dialog-name"></div>
			<div class="map-vn-dialog-text"></div>
			<div class="map-vn-dialog-next">NEXT</div>
		</div>

		<div class="map-vn-reward-layer" style="display:none;">
			<div class="map-vn-reward-box">
				<div class="map-vn-reward-title">보상 획득</div>
				<div class="map-vn-reward-list"></div>
				<button type="button" class="ui-btn point" onclick="map_vn_close_rewards();">확인</button>
			</div>
		</div>

		<a href="<?=G5_URL?>/map" class="map-vn-back-global">전체지역으로 돌아가기</a>
	</div>
</div>
<? } ?>

<div class="map-descript-box"></div>

<div class="map-script"></div>

<div class="map-searchPopup">
	<div class="pannel"></div>
	<div class="control"><a href="javascript:map_search_close();" class="ui-btn">닫기</a></div>
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
	var current_ma_id = <?=$character['ma_id']?>;
</script>
<script src="<?=G5_MAP_URL ?>/js/map.js?v=26081701"></script>
<?php
include_once('./_tail.sub.php');
?>


