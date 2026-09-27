<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<div class="descript">
	<div class="tbl">
		<div class="cell">
			<div class="txt">
				<?=nl2br($me['me_content'])?>
			</div>
			<? if($me['me_get_item']) { ?>
				<div class="itembox">
					<div class="thumb"><img src="<?=$item['it_img']?>" onerror="this.remove();" alt="" /></div>
					<div class="desc">
						<strong><?=$item['it_name']?></strong>
						<span><?=$item['it_content']?></span>
					</div>
				</div>
			<? } ?>
			<? if($me['me_get_money']) { ?>
				<div class="itembox single">
					<div class="desc">
						<strong><?=$config['cf_money']?> <?=number_format($me['me_get_money'])?> <?=$config['cf_money_pice']?> 획득</strong>
					</div>
				</div>
			<? } ?>
		</div>
	</div>
</div>
