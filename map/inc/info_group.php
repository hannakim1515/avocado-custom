<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$open_dungeons = $show_dungeon_map && function_exists('get_map_open_dungeons') ? get_map_open_dungeons($ma['ma_id'], true) : array();
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
			<? for($i = 0; $i < count($open_dungeons); $i++) {
				$open_dungeon = $open_dungeons[$i];
				$entry_label = maze_entry_label((int)$open_dungeon['ds_id'], (int)$character['ch_id']);
			?>
				<li>
					<span><?=get_text($open_dungeon['dg_title'])?> · <?=get_text($open_dungeon['ds_ma_id'] ? get_map_name($open_dungeon['ds_ma_id']) : '지역 제한 없음')?></span>
					<? if($entry_label !== '' && $character['ch_state'] === '승인' && (!$use_dungeon_map || (int)$open_dungeon['ds_ma_id'] === (int)$character['ma_id'])) { ?>
						<?php if($entry_label==='입장하기') { ?><form method="post" action="<?=G5_URL?>/dungeon/maze.php" onsubmit="return confirm('던전에 입장하시겠습니까?');"><input type="hidden" name="token" value="<?=get_text(inventory_boundary_token())?>"><input type="hidden" name="ds_id" value="<?=(int)$open_dungeon['ds_id']?>"><input type="hidden" name="operation" value="join"><button type="submit" class="ui-btn">입장하기</button></form><?php } else { ?><a class="ui-btn" href="<?=G5_URL?>/dungeon/applicate.php?ds_id=<?=(int)$open_dungeon['ds_id']?>"><?=get_text($entry_label)?></a><?php } ?>
					<? } else { ?><em><?=$entry_label === '' ? '탐험 진행 중' : '해당 지역에서 입장'?></em><? } ?>
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
