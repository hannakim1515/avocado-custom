<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>

<div class="npc-inventory">
	<div class="filter">
		<input type="text" onInput="set_filter_inven(this);" value="" placeholder="Search Item..." />
	</div>
	<div class="in">
		<form name="frm_present" id="frm_npc_present">
			<input type="hidden" name="npc_id" value="<?=$npc_id?>" />
			<ul id="npc_my_inven_list">
			<? 
			for($i=0; $i < count($pin); $i++) {
				if($pin[$i]['in_id']){ ?>
				<li data-name="<?=$pin[$i]['it_name']?>">
					<label>
						<input type="radio" name="in_id" value="<?=$pin[$i]['in_id']?>">
						<div>
							<em>
								<img src="<?=$pin[$i]['it_img']?>" alt="">
							</em>
							<span><?=$pin[$i]['it_name']?></span>
						</div>
					</label>
				</li>
				<? }
			} 
			?>
			</ul>
		</form>
	</div>
	<div class="control">
		<button type="button" class="ui-btn" onclick="vew_npc_content('talk');">이전</button>
		<button type="button" class="ui-btn" onclick="send_npc_item();">선택한 선물 주기</button>
	</div>
</div>