<?php
include_once('./_common.php');

$ch = get_character($ch_id);

if(!$ch['ch_id']) {
	echo "F";
	exit;
}

// Max Count & Has Skill Count
$max_count = $ch['ch_skill_slot'];

$count = sql_fetch("select count(*) as cnt from {$g5['skill_has_table']} where ch_id = {$ch_id} and sh_use = 1");
$count = $count['cnt'];

// 소지 가능한 갯수 이상을 가지고 있을 경우 리턴
if($max_count <= $count) {
	echo "F";
	exit;
}

$sh = sql_fetch("select * from {$g5['skill_has_table']} where sh_id = {$sh_id}");
if($sh['sh_id'] && $ch['ch_id'] && $ch['ch_id'] == $sh['ch_id'] && $ch['mb_id'] == $member['mb_id']) {
	sql_query("update {$g5['skill_has_table']} set sh_use = '1', sh_datetime = '".date('Y-m-s H:i:s')."' where sh_id = '{$sh['sh_id']}'"); 
	if(function_exists('unified_skill_sync_character')) unified_skill_sync_character($ch['ch_id'], true, 'a');
} else {
	echo "F";
	exit;
}

echo skill_setting($ch['ch_id'], $ch);
?>
