<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if($inven_function == "스킬획득") {
	$item_sk = sql_fetch("select sk_id from {$g5['skill_table']} where sk_name = '{$in['it_value']}'");
	add_skill_inven($character['ch_id'], $item_sk['sk_id'], 1);
	delete_inventory($in['in_id'], $in['it_use_ever']);
	echo location_url($return_url);
}
if($inven_function == "스킬슬롯추가") {
	$max_slot = get_skill_maxium_count();
	$next_slot_count = $character['ch_skill_slot'] + 1;
	
	if($next_slot_count > $max_slot) { 
		echo error_message("최대 스킬 슬롯 오픈 갯수를 초과하였습니다.");
	} else {
		sql_query("update {$g5['character_table']} set ch_skill_slot = '{$next_slot_count}' where ch_id = '{$character['ch_id']}'");
		delete_inventory($in['in_id'], $in['it_use_ever']);
		echo location_url($return_url);
	}
}

?>