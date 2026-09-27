<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$open_dungeons = function_exists('get_map_open_dungeons') ? get_map_open_dungeons($ma['ma_id'], true) : array();
?>

<div class="map-pannel">
	<div class="map-simple-info">
		<div class="in">
			<div class="area">
				<strong><?=$ma['ma_name']?></strong>
			</div>
			<div class="control">
				<button type="button" onclick="location.href='?now_idx=<?=$ma['ma_id']?>';" class="ui-btn"><span>자세히 보기</span></button>
			</div>
		</div>
	</div>
	<div class="map-detail">
		<div class="detail-txt">
			<div><?=nl2br($ma['ma_content'])?></div>
		</div>

		<? if(count($open_dungeons)) { ?>
		<div class="map-open-dungeon-list">
			<strong>던전 발생</strong>
			<ul>
			<? for($i = 0; $i < count($open_dungeons); $i++) { $open_dungeon = $open_dungeons[$i]; ?>
				<li>
					<span><?=get_text(get_map_name($open_dungeon['ds_ma_id']))?></span>
					<em><?=get_text($open_dungeon['dg_mon_name'])?></em>
				</li>
			<? } ?>
			</ul>
		</div>
		<? } ?>
	</div>

	<div class="searchCounter">
		<div class="counter">
			<p>탐색횟수</p>
			<div class="num"><div><em data-search-counter><?=($config['cf_search_count']-$character['ch_search'])?></em></div></div>
		</div>
	</div>
</div>
