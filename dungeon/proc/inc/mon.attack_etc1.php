<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

// 일반 공격 처리 부분
$m_log_comment = "{$ds['dg_s1_attack_comment']}&&&&";

// 공격력 버프 확인
$buff_attack = get_status_buffer_enermy($ds_id, "공격");

// 어글자 확인
//$aggro_dm_list = array();
//for($i=0; $i < count($dm_list); $i++) { if($dm_list[$i]['dm_aggro'] == 'Y') {$aggro_dm_list[] = $dm_list[$i];}}
//$is_aggro = count($aggro_dm_list) > 0 ? true : false;
//$total_attack = 0; // 전체 대미지, 어글자가 있을 경우 이 대미지를 나누게 됩니다.

for($i=0; $i < count($dm_list); $i++) {
	$re_dm = $dm_list[$i];

	$target_field = "st_id_{$ds['dg_s1_attack_st_id']}";
	$target_field_mod = "st_id_{$ds['dg_s1_attack_st_id']}_mod";
	$target_field_use = "st_id_{$ds['dg_s1_attack_st_id']}_use";

	$is_etc_attack_able = false;
	$target_status = ($re_dm[$target_field] * 1) + ($re_dm[$target_field_mod]*1) - ($re_dm[$target_field_use]*1);
	$check_status = $ds['dg_s1_attack_st_value'] * 1;

	if($ds['dg_s1_attack_type'] == '이상' && $target_status >= $check_status) {
		$is_etc_attack_able = true;
	}
	if($ds['dg_s1_attack_type'] == '이하' && $target_status <= $check_status) {
		$is_etc_attack_able = true;
	}

	if($is_etc_attack_able) {
		$damage = rand($ds['dg_s1_attack_min'], $ds['dg_s1_attack_max']);
		$damage = $damage + $buff_attack;

	//	if($is_aggro) {
	//		$total_attack += $damage;
	//	} else {
			$m_log_comment .= set_dungeon_character_damage($ds_id, $re_dm['ch_id'], $re_dm, $damage);
	//	}
	}
}
/*
if($is_aggro && $total_attack > 0) {
	$m_log_comment .= "<p>{$ds['dg_mon_name']}의 주의가 끌립니다<i>!</i></p>";
	$agrro_damage = $total_attack/count($aggro_dm_list);
	$agrro_damage = intval($agrro_damage);

	for($i=0; $i < count($aggro_dm_list); $i++) {
		$aggro_dm = $aggro_dm_list[$i];
		// 어글자 대미지 처리
		$now_agrro_damage = intval($agrro_damage * ((100-$aggro_dm['dm_aggro_per'])/100));
		$m_log_comment .= set_dungeon_character_damage($ds_id, $aggro_dm['ch_id'], $aggro_dm, $now_agrro_damage);

		// 어글자 도발 리셋
		//sql_query("update {$g5['dungeon_member_table']} set dm_aggro = '' where dm_id = '{$aggro_dm['dm_id']}'");
		//sql_query("update {$g5['dungeon_log_table']} set dl_keep_limit = 0 where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function = '도발' and ch_id = '{$aggro_dm['ch_id']}' and dl_keep_limit > 0");
	}
}
*/
insert_dungeon_log("몬스터", $ds, null, null, null, null, $m_log_comment);


// 턴수 처리
// -- 도트 대미지 처리가 필요하다.
$dot_damage = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function='공격' and dl_keep_limit > 0");
$dot_damage = $dot_damage['total'];
if($dot_damage > 0) {
	insert_dungeon_log("시스템", $ds, null, null, 0, 0, "추가 대미지 <strong>{$dot_damage}</strong><i>!</i>");
	$result_state = set_dungeon_mon_damage($ds_id, $ds, $dot_damage);
}
sql_query("update {$g5['dungeon_log_table']} set dl_keep_limit = dl_keep_limit-1 where ds_id = '{$ds_id}' and dl_cate = '효과' and ch_id = -1 and dl_keep_limit > 0");

?>