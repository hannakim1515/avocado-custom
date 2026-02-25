<?php
include_once('./_common.php');

$ch = get_character($ch_id);

if(!$ch['ch_id']) {
	echo "F";
	exit;
}

$sh = sql_fetch("select * from {$g5['skill_has_table']} where sh_id = {$sh_id}");
if($sh['sh_id'] && $ch['ch_id'] && $ch['ch_id'] == $sh['ch_id'] && $ch['mb_id'] == $member['mb_id']) {
	sql_query("update {$g5['skill_has_table']} set sh_name = '{$sh_name}', sh_descript = '{$sh_descript}' where sh_id = '{$sh['sh_id']}'"); 
} else {
	echo "F";
	exit;
}

echo skill_setting($ch['ch_id'], $ch);
?>