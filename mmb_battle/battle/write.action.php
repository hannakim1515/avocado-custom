<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 배틀 관련 UI : WRITE 페이지 ACTION 항목 추가

if(is_extra_hp($character['ch_id']) == "생존") { 
?>
<script>
$(function() {
	$('*[name="action"]').append("<option value='BATTLE'>1:1 전투</option>");
	
	var action_pannel = $("<div>", {class:"comment-data", id:"action_BATTLE"}).html("<dl><dt>전투대상</dt><dd></dd></dl>");
	var action_re_character = $("<select>", {name:"battle_re_character"});
	
	action_pannel.find('dd').append(action_re_character);
	action_re_character.append($("<option value=''></option>"));

<?
	$battle_re_character = sql_query("select ch_id, ch_name, ch_thumb from {$g5['character_table']} where ch_type = 'main' and ch_id != {$character['ch_id']}");
	for($i=0; $battle_re_ch = sql_fetch_array($battle_re_character); $i++) {
		$check = get_extra_hp($battle_re_ch['ch_id']);
		if($check['sc_value'] >= $check['sc_max']) continue;
?>
		action_re_character.append($("<option value='<?=$battle_re_ch['ch_id']?>'><?=$battle_re_ch['ch_name']?></option>"));

<?	} ?>

	$('#board_action').append(action_pannel);

});

</script>
<? } ?>