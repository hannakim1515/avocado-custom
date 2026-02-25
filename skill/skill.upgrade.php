<?php
include_once('./_common.php');

$ch = get_character($ch_id);

if(!$ch['ch_id']) {
	echo "F";
	exit;
}

// Has Item
$item = get_hasItem_func($ch_id, '스킬레벨업');
if(!$item['in_id']) {
	echo "F";
	exit;
}

$sh = sql_fetch("select * from {$g5['skill_has_table']} where sh_id = {$sh_id}");
if($sh['sh_id'] && $ch['ch_id'] && $ch['ch_id'] == $sh['ch_id'] && $ch['mb_id'] == $member['mb_id']) {
	
	$max_level = sql_fetch("select MAX(sl_level) as sl_level from {$g5['skill_level_table']} where sk_id = '{$sh['sk_id']}'");
	$max_level = $max_level['sl_level'];

	$next_level = $sh['sh_level'] +1;

	if($max_level >= $next_level) {
		sql_query("update {$g5['skill_has_table']} set sh_level = '{$next_level}' where sh_id = '{$sh['sh_id']}'"); 
		delete_inventory($item['in_id'], $item['it_use_ever']);
	} else {
		echo "F";
		exit;
	}
} else {
	echo "F";
	exit;
}

echo skill_setting($ch['ch_id'], $ch);
?>