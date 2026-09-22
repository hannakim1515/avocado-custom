<?php
include_once('./_common.php');
$g5['title'] = "지역";
include_once('./_head.sub.php');

add_stylesheet('<link rel="stylesheet" href="'.G5_MAP_URL.'/css/style.css?v=unified-20260922">', 0);

$ma_id = 0;
$pma_id = 0;
$start_map = null;
$map = array();
$map_config = array();

$map_top_list = array();
$map_sub_list = array();
$now_idx = isset($_REQUEST['now_idx']) ? (int)$_REQUEST['now_idx'] : 0;

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

// 시작 지역이 비어 있어도 사용 중인 최상위 지역으로 안전하게 복구한다.
if (!$start_map && count($map_top_list)) $start_map = $map_top_list[0];

// 현재 위치하고 있는 지역 정보
if (!empty($character['ma_id']) && isset($map_config[(int)$character['ma_id']])) {
	$ma_id = (int)$character['ma_id'];
	$map = $map_config[$ma_id];
} else {
	// 삭제·비활성화된 지역을 바라보는 캐릭터는 사용 중인 시작 지역으로 되돌린다.
	$map = $start_map;
	if (empty($map['ma_id'])) alert('사용 중인 시작 지역이 없습니다. 관리자 지역 관리에서 지역 사용과 시작 지역을 확인하세요.');
	$ma_id = (int)$map['ma_id'];
	sql_query(" update {$g5['character_table']} set ma_id = '{$ma_id}' where ch_id = '".(int)$character['ch_id']."'");
	$character['ma_id'] = $ma_id;
}
$pma_id = (int)$map['ma_parent'];
if ($pma_id <= 0 || !isset($map_config[$pma_id])) $pma_id = $ma_id;

if($use_dungeon_map && function_exists('set_reset_dungeon')) {
	// 게이트변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

$now_map = null;
$now_map_list = array();
$now_map_img = "";
$original_width = 0;
$original_height = 0;
$original_size = 20;
$original_raito = 0;

if($config['cf_use_map_all'] && !$now_idx) {
	// 전체 지도를 사용하고, 선택된 상위 지역 정보가 없을 경우
	// 현재 지도 이미지는 전체 지도 이미지를 불러온다.
	// 지역 목록은 상위 지역으로 설정
	$now_map_img = $config['cf_map_all_img'];
	$original_width = $config['cf_map_all_w'];
	$original_height = $config['cf_map_all_h'];
	$now_map_list = $map_top_list;
	/* 전체 지도에서는 원래 상위 지역만 표식으로 그려 하위 지역에 열린 던전이
	 * 붉은 게이트로 전혀 보이지 않았다. 하위 던전 지역만 추가해 바로 열람·입장할
	 * 수 있게 한다. 하위 지역의 X/Y는 전체지도 사용 시 관리자 화면에서 입력하는
	 * 전체지도 좌표이므로 그대로 사용한다. */
	if($use_dungeon_map && function_exists('get_map_dungeon')) {
		foreach($map_sub_list as $sub_rows) foreach($sub_rows as $sub_map) {
			$sub_dungeon = get_map_dungeon($sub_map['ma_id']);
			if(!empty($sub_dungeon['ds_state']) && $sub_dungeon['ds_state'] == 'S') $now_map_list[] = $sub_map;
		}
	}
} else if(!$now_idx) {
	// 전체 지도를 사용하지 않으면서 현재 선택된 지역 정보가 없을 경우
	// 현재 위치한 장소의 지도를 가져온다.
	$_now_map = $map;
	$now_map = isset($map_config[$_now_map['ma_parent']]) ? $map_config[$_now_map['ma_parent']] : $_now_map;

	$now_map_img = $now_map['ma_img'];
	$original_width = $now_map['ma_width'];
	$original_height = $now_map['ma_height'];
	$now_map_list = isset($map_sub_list[$pma_id]) ? $map_sub_list[$pma_id] : array();

} else {
	// 전체 지도를 사용하지 않으면서 현재 선택한 정보가 있을 경우
	// 현재 선택한 지역의 정보를 가지고 온다.
	$now_map = isset($map_config[$now_idx]) ? $map_config[$now_idx] : $map;

	$now_map_img = $now_map['ma_img'];
	$original_width = $now_map['ma_width'];
	$original_height = $now_map['ma_height'];
	$now_map_list = isset($map_sub_list[$now_map['ma_parent']]) ? $map_sub_list[$now_map['ma_parent']] : array();
}

// 이미지 비율 처리
$original_raito = ($original_height == 0 || $original_width == 0) ? 0 : ($original_height/$original_width);
if(!$character['ma_id']) $character['ma_id'] = $start_map['ma_id'];

?>
<script src="<?php echo G5_MAP_URL ?>/js/script.js"></script>

<div class="map_wrap none-trans">
	<a href="<?=G5_URL?>/main.php" class="map-close"><img src="<?=G5_MAP_URL?>/img/btn_close_map.png" alt="메인으로" /></a>

	<div class="map-img-viewer">
		<div class="bak">
			<div class="img" style="padding-top:<?=($original_raito*100)?>%; background-image:url(<?=$now_map_img?>);"></div>
			<div class="anker">
				<?
					for($i=0; $i < count($now_map_list); $i++) {
						$ma = $now_map_list[$i];

						$x = ($ma['ma_left']  == 0 || $original_width == 0) ? 0 : ($ma['ma_left'] / $original_width) * 100;
						$y = ($ma['ma_top']  == 0 || $original_height == 0) ? 0 : ($ma['ma_top'] / $original_height) * 100;

						$class = "";
						if($ma_id == $ma['ma_id'] || $pma_id == $ma['ma_id']) $class .= " on";

						if($use_dungeon_map && function_exists('get_map_dungeon')) {
							$state = get_map_dungeon($ma['ma_id']);
							if($state['ds_state'] == 'S') $class .= " open-gate";
						}
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
</script>
<script src="<?=G5_MAP_URL ?>/js/map.js"></script>
<?php
include_once('./_tail.sub.php');
?>


