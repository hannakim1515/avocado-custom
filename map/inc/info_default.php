<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>

<div class="map-pannel">
	<div class="map-simple-info">
		<div class="in">
			<div class="area">
				<strong><?=$ma['ma_name']?></strong>
			</div>
			<div class="control">
				<?
					if($character['ch_state'] == '승인') { 
						if($character['ma_id'] == $ma_id) {
							include(G5_PATH."/map/inc/btn_search.php");
						} else if(!$use_dungeon_map || (function_exists('is_has_dungeon') && !is_has_dungeon($character['ch_id']))) {
							include(G5_PATH."/map/inc/btn_move.php");
						}
					}
					if($config['cf_use_map_all']) {
				?>
					<button type="button" onclick="location.href='<?=G5_URL?>/map';" class="ui-btn"><span>전체지역</span></button>
				<?
					}
				?>
			</div>
		</div>
	</div>
	<div class="map-detail">
		<div class="detail-txt">
			<div><?=nl2br($ma['ma_content'])?></div>
		</div>
	</div>

	<div class="searchCounter">
		<div class="counter">
			<p>탐색횟수</p>
			<div class="num"><div><em data-search-counter><?=($config['cf_search_count']-$character['ch_search'])?></em></div></div>
		</div>
	</div>
</div>
