<?php
$sub_menu = "910200";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'w');

if ($is_admin != 'super')
	alert('최고관리자만 접근 가능합니다.');

check_admin_token();

$bc = sql_fetch("select bc_id from {$g5['battle_config_table']}");

// 아이템 관련 검증
if(!$bc_reward_win_item && $bc_reward_win_item_name) {
	$temp = sql_fetch("select it_id {$g5['item_table']} where it_name = '{$bc_reward_win_item_name}'");
	if($temp['it_id']) {
		$bc_reward_win_item = $temp['it_id'];
	} else {
		$bc_reward_win_item = "0";
	}
} else if($bc_reward_win_item_name == '') {
	$bc_reward_win_item = "0";
}

if(!$bc_reward_lose_item && $bc_reward_lose_item_name) {
	$temp = sql_fetch("select it_id {$g5['item_table']} where it_name = '{$bc_reward_lose_item_name}'");
	if($temp['it_id']) {
		$bc_reward_lose_item = $temp['it_id'];
	} else {
		$bc_reward_lose_item = "0";
	}
} else if($bc_reward_lose_item_name == '') {
	$bc_reward_lose_item = "0";
}

if(!$bc_reward_both_item && $bc_reward_both_item_name) {
	$temp = sql_fetch("select it_id {$g5['item_table']} where it_name = '{$bc_reward_both_item_name}'");
	if($temp['it_id']) {
		$bc_reward_both_item = $temp['it_id'];
	} else {
		$bc_reward_both_item = "0";
	}
} else if($bc_reward_both_item_name == '') {
	$bc_reward_both_item = "0";
}

$sql_common = "
	bc_proc ='{$bc_proc}',
	bc_before ='{$bc_before}',
	bc_after ='{$bc_after}',
	bc_damage_proc ='{$bc_damage_proc}',
	bc_damage_type ='{$bc_damage_type}',
	bc_damage_min_point ='{$bc_damage_min_point}',
	bc_damage_max_point ='{$bc_damage_max_point}',
	bc_damage_before ='{$bc_damage_before}',
	bc_damage_after ='{$bc_damage_after}',
	bc_reward_proc ='{$bc_reward_proc}',
	bc_reward_win_point ='{$bc_reward_win_point}',
	bc_reward_win_exp ='{$bc_reward_win_exp}',
	bc_reward_win_item ='{$bc_reward_win_item}',
	bc_reward_lose_point ='{$bc_reward_lose_point}',
	bc_reward_lose_exp ='{$bc_reward_lose_exp}',
	bc_reward_lose_item ='{$bc_reward_lose_item}',
	bc_reward_both_point ='{$bc_reward_both_point}',
	bc_reward_both_exp ='{$bc_reward_both_exp}',
	bc_reward_both_item ='{$bc_reward_both_item}'
";

if($bc['bc_id']) {
	// 업데이트
	$sql = " update {$g5['battle_config_table']}
				set {$sql_common} ";
	sql_query($sql);
} else {
	// 추가
	$sql = " insert into {$g5['battle_config_table']}
				set {$sql_common} ";
	sql_query($sql);
}

$sql = " update {$g5['config_table']}
			set cf_use_status_battle = '{$cf_use_status_battle}' ";
sql_query($sql);




goto_url('./battle_config.php', false);
?>