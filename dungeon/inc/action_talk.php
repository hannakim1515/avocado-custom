<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<form name="frmTalkActive" method="post" action="<?=G5_URL?>/dungeon/proc/talk_update.php">
	<input type="hidden" name="list_type" value="<?=$list_type?>" />
	<input type="hidden" name="ds_id" value="<?=$ds_id?>" />

	<div class="input">
		<textarea name="talk_comment" placeholder="대화 내용을 입력하세요."></textarea>
		<button type="submit"><span>입력</span></button>
	</div>

</form>