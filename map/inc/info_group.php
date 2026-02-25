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
				<button type="button" onclick="location.href='?now_idx=<?=$ma['ma_id']?>';" class="ui-btn"><span>자세히 보기</span></button>
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