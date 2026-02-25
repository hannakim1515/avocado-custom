<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 


function set_user_aggro_evasion($ds_id, $dm) {
	global $g5, $character;

	// 도발 유지 여부 확인
	$chk_aggro_data = sql_fetch("select count(*) as cnt, max(dl_value) as per from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and ch_id = '{$character['ch_id']}' and dl_function = '도발' and dl_keep_limit > 0");
	$chk_aggro = $chk_aggro_data['cnt'] > 0 ? "Y" : "";
	$chk_aggro_per = $chk_aggro == 'Y' ? $chk_aggro_data['per'] : 0;

	// 회피 유지 여부 확인
	$chk_evasion = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and ch_id = '{$character['ch_id']}' and dl_function = '회피' and dl_value > 0 and dl_keep_limit > 0");
	$chk_evasion = $chk_evasion['cnt'] > 0 ? "Y" : "";

	sql_query("update {$g5['dungeon_member_table']} set dm_evasion = '{$chk_evasion}', dm_aggro = '{$chk_aggro}', dm_aggro_per = '{$chk_aggro_per}' where dm_id = '{$dm['dm_id']}'");
	$dm_list = get_dungeon_member($ds_id, 'S');
	
	return $dm_list;
}

$dm_list = set_user_aggro_evasion($ds_id, $dm);


/*------------------------------------------
	턴수 별 몹 이펙트 체크
------------------------------------------ */
$total_turn_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where dl_is_turn = 1 and ds_id = '{$ds_id}'");

// -- 일반 공격
$d_turn = $ds['dg_d_attack_turn'];
$d_counter = $ds['dg_d_attack_count'];
$is_d_attack = false;
if($d_turn > 0 && $total_turn_count['cnt'] % $d_turn == 0 && $d_counter > 0) {$is_d_attack = true;}

// -- 광역 공격
$w_turn = $ds['dg_w_attack_turn'];
$is_w_attack = false;
if($w_turn > 0 && $total_turn_count['cnt'] % $w_turn == 0) {$is_w_attack = true;}

// -- 특수 공격1
$s1_turn = $ds['dg_s1_attack_turn'];
$is_s1_attack = false;
if($s1_turn > 0 && $ds['dg_s1_attack_st_id'] && $total_turn_count['cnt'] % $s1_turn == 0) {$is_s1_attack = true;}

// -- 특수 공격2
$s2_turn = $ds['dg_s2_attack_turn'];
$is_s2_attack = false;
if($s2_turn > 0 && $ds['dg_s2_attack_st_id'] && $total_turn_count['cnt'] % $s2_turn == 0) {$is_s2_attack = true;}


if($result_state != 'E') {
	if($is_d_attack) {
		if($ds['ds_weak_turn']) {
			insert_dungeon_log("몬스터", $ds, null, null, null, null,"「{$ds['dg_mon_name']}」이(가) 움직이지 못합니다!");
			$ds['ds_weak_turn'] = $ds['ds_weak_turn'] - 1 < 0 ? 0 : $ds['ds_weak_turn']-1;
			sql_query("update {$g5['dungeon_state_table']} set ds_weak_turn = '{$ds['ds_weak_turn']}' where ds_id = '{$ds['ds_id']}'");
		} else {
			include(G5_PATH.'/dungeon/proc/inc/mon.attack_default.php');
		}
	}
	if($is_w_attack) {
		if($ds['ds_weak_turn']) {
			insert_dungeon_log("몬스터", $ds, null, null, null, null,"「{$ds['dg_mon_name']}」이(가) 움직이지 못합니다!");
			$ds['ds_weak_turn'] = $ds['ds_weak_turn'] - 1 < 0 ? 0 : $ds['ds_weak_turn']-1;
			sql_query("update {$g5['dungeon_state_table']} set ds_weak_turn = '{$ds['ds_weak_turn']}' where ds_id = '{$ds['ds_id']}'");
		} else {
			include(G5_PATH.'/dungeon/proc/inc/mon.attack_aoe.php');
		}
	}
	if($is_s1_attack) {
		if($ds['ds_weak_turn']) {
			insert_dungeon_log("몬스터", $ds, null, null, null, null,"「{$ds['dg_mon_name']}」이(가) 움직이지 못합니다!");
			$ds['ds_weak_turn'] = $ds['ds_weak_turn'] - 1 < 0 ? 0 : $ds['ds_weak_turn']-1;
			sql_query("update {$g5['dungeon_state_table']} set ds_weak_turn = '{$ds['ds_weak_turn']}' where ds_id = '{$ds['ds_id']}'");
		} else {
			include(G5_PATH.'/dungeon/proc/inc/mon.attack_etc1.php');
		}
	}
	if($is_s2_attack) {
		if($ds['ds_weak_turn']) {
			insert_dungeon_log("몬스터", $ds, null, null, null, null,"「{$ds['dg_mon_name']}」이(가) 움직이지 못합니다!");
			$ds['ds_weak_turn'] = $ds['ds_weak_turn'] - 1 < 0 ? 0 : $ds['ds_weak_turn']-1;
			sql_query("update {$g5['dungeon_state_table']} set ds_weak_turn = '{$ds['ds_weak_turn']}' where ds_id = '{$ds['ds_id']}'");
		} else {
			include(G5_PATH.'/dungeon/proc/inc/mon.attack_etc2.php');
		}
	}
}



?>