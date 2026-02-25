<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<style>
:root {
--now-love-color:linear-gradient(90deg, <?=$now_color?> 0%, <?=$now_color?> 100%);
--next-love-color:linear-gradient(90deg, <?=$next_color?> 0%, <?=$next_color?> 100%);
}
</style>

<div class="npc-talk-area">
	<div class="messages">
		<div class="in">
			<?=$message?>
		</div>
	</div>
	<div class="control">
		<div class="state">
			<div class="graph" data-level="<?=$level?>" data-next-level="<?=$next_level?>">
				<div class="bar-frame">
					<p class="bar" style="background-size:<?=$per?>% 100%;">♥♥♥♥♥</p>
					<p>♥♥♥♥♥</p>
				</div>
				<span><?=$npc_now_state?></span>
			</div>
		</div>
		<div class="buttons">
			<button type="button" class="ui-btn" onclick="set_npc_event('talk', <?=$npc_id?>);">대화하기</button>
			<button type="button" class="ui-btn" onclick="set_npc_event('inven', <?=$npc_id?>);">선물주기</button>
		</div>
	</div>
</div>