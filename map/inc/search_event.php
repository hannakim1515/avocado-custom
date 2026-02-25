<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<div class="descript">
	<div class="tbl">
		<div class="cell">
			<? if($me['me_get_item']) { ?>
				<div class="itembox">
					<div class="thumb"><img src="<?=$item['it_img']?>" onerror="this.remove();" alt="" /></div>
					<div class="desc">
						<strong><?=$item['it_name']?></strong>
						<span><?=$item['it_content']?></span>
					</div>
				</div>
			<? } ?>
			<div class="txt">
				<?=nl2br($me['me_content'])?>
			</div>
		</div>
	</div>
</div>