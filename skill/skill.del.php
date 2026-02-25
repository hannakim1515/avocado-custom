<?php
include_once('./_common.php');

$ch = get_character($ch_id);

if(!$ch['ch_id']) {
	echo "F";
	exit;
}

// Has Item
/*$item = get_hasItem_func($ch_id, '스킬해제');
if(!$item['in_id']) {
	echo "F";
	exit;
}*/

$now_dungeon = is_has_dungeon($ch['ch_id']);
$sh = sql_fetch("select * from {$g5['skill_has_table']} where sh_id = {$sh_id}");
if(!$now_dungeon && $sh['sh_id'] && $ch['ch_id'] && $ch['ch_id'] == $sh['ch_id'] && $ch['mb_id'] == $member['mb_id']) {
	sql_query("update {$g5['skill_has_table']} set sh_use = '0', sh_datetime = '' where sh_id = '{$sh['sh_id']}'"); 
	//delete_inventory($item['in_id'], $item['it_use_ever']);
} else {
	echo "F";
	exit;
}

echo skill_setting($ch['ch_id'], $ch);
?>