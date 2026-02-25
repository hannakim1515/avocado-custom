<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ($is_comment_write) {
	if($w == '') $w = 'c';
?>
<!-- 댓글 쓰기 시작 { -->
<aside class="bo_vc_w" id="bo_vc_w_<?php echo $list_item['wr_id']?>">
	<form name="fviewcomment" action="./write_comment_update.php" onsubmit="return fviewcomment_submit(this);" method="post" autocomplete="off">
	<input type="hidden" name="w" value="<?php echo h($w) ?>" id="w">
	<input type="hidden" name="bo_table" value="<?php echo h($bo_table) ?>">
	<input type="hidden" name="wr_id" value="<?php echo (int)$list_item['wr_id'] ?>">
	<input type="hidden" name="sca" value="<?php echo h($sca) ?>">
	<input type="hidden" name="sfl" value="<?php echo h($sfl) ?>">
	<input type="hidden" name="stx" value="<?php echo h($stx) ?>">
	<input type="hidden" name="spt" value="<?php echo h($spt) ?>">
	<input type="hidden" name="page" value="<?php echo (int)$page ?>">

	<input type="hidden" name="ch_id" value="<?php echo (int)ses($character, 'ch_id', 0)?>" />
	<input type="hidden" name="ti_id" value="<?php echo (int)ses($character, 'ch_title', 0)?>" />
	<input type="hidden" name="ma_id" value="<?php echo (int)ses($character, 'ma_id', 0)?>" />
	<input type="hidden" name="wr_subject" value="<?php echo h(ses($character, 'ch_name', '') ? $character['ch_name'] : 'GUEST')?>" />

	<div class="input-comment">
	<?php if(count($mmb_item) > 0) { ?>
		<select name="use_item" class="full">
			<option value="">사용할 아이템 선택</option>
		<?php 	for($h=0; $h < count($mmb_item); $h++) { ?>
			<option value="<?php echo (int)$mmb_item[$h]['in_id']?>">
				<?php echo h($mmb_item[$h]['it_name'])?>
			</option>
		<?php } ?>
		</select>
	<?php } ?>

		<textarea name="wr_content" required class="required" title="내용"></textarea>

		<div class="action-check form-input">
		<?php if($character['ch_state']=='승인') { ?>
			<input type="radio" name="action" id="action_<?php echo $list_item['wr_id']?>_" value="" checked/>
			<label for="action_<?php echo $list_item['wr_id']?>_">일반행동&nbsp;&nbsp;&nbsp;&nbsp;</label>
			<?php if($is_able_search) { ?>
			<input type="radio" name="action" id="action_<?php echo $list_item['wr_id']?>_S" value="S" />
			<label for="action_<?php echo $list_item['wr_id']?>_S">탐색&nbsp;&nbsp;&nbsp;&nbsp;</label>
			<?php } ?>
		<?php } ?>

			<input type="checkbox" name="game" id="game_<?php echo $list_item['wr_id']?>" value="dice" />
			<label for="game_<?php echo $list_item['wr_id']?>">주사위</label>

		<?php if($board['bo_use_noname'] && $is_member) { ?>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="checkbox" name="wr_noname" id="wr_noname_<?php echo $list_item['wr_id']?>" value="1" />
			<label for="wr_noname_<?php echo $list_item['wr_id']?>">익명</label>
		<?php } ?>
		</div>

	</div>
	<div class="btn_confirm">
		<button type="submit" class="ui-comment-submit ui-btn">입력</button>
	</div>

	</form>
</aside>
<?php 
}
?>

