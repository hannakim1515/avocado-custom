<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 사용 가능한 아이템 목록 가져오기

$battle_items = sql_query("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_use = '' and item.it_use_battle_able = '1' and inven.it_id = item.it_id and inven.ch_id = '{$character['ch_id']}' order by item.it_name asc");

?>
<form name="frmItemActive" method="post" action="<?=G5_URL?>/dungeon/proc/item_use.php">
	<input type="hidden" name="list_type" value="<?=$list_type?>" />
	<input type="hidden" name="ds_id" value="<?=$ds_id?>" />

	<div class="input">
		<select name="use_item" size="3" class="none-trans">
			<? for($i=0; $in = sql_fetch_array($battle_items); $i++) { ?>
				<option value="<?=$in['in_id']?>"><?=$in['it_name']?> <? if($in['it_content2']) { ?>(<?=$in['it_content2']?>)<? } ?></option>
			<? } ?>
			</select>
		<button type="button" onclick="fn_use_item(this, this.form);"><span>아이템 사용하기</span></button>
	</div>
	<script>
		function fn_use_item(obj, f) {
			$(obj).attr('disabled', 'disabled');
			f.submit();
		}
	</script>

</form>