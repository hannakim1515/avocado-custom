<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

// 회피 스킬 : 사용 대상은 적을 제외한 다른 대상들을 선택 가능하다.
// 따라서, 사용 다생아 따라 적절히 구분을 진행한다.

$skill_name = $sh['sh_name'] ? $sh['sh_name'] : $sh['sk_name'];
$log = "<p class=\'txt-skill-info\'><strong>{$skill_name}</strong> 스킬을 사용했습니다.</p>";
$log .= "<p class=\'txt-skill-info ty2\'>{$sh['sk_descript']}</p>";

$default_vaule = 0;
$last_vaule = 0;
$is_critical = 0;

// 1. 기본 수식을 가져옵니다
if($sh['sk_status_code']) {
	// default : 기본값, is_cri : 크리티컬 유무, cri_value : 크리티컬 값, value : 총합계
	$default_code = get_status_dungeon($sh['sk_status_code'], $ds_id, $character['ch_id'], $dm);
	$is_critical = $default_code['is_cri'] ? 1 : 0;
	$default_vaule = $default_code['value'];
}
// 2. 레벨 별 변동수치를 가져옵니다.
$effect_type = isset($sh['sk_effect_type']) ? $sh['sk_effect_type'] : 'flat';
if(!in_array($effect_type, array('flat', 'percent', 'final'))) $effect_type = 'flat';
if($sh['sk_function'] == '스탯강화' && $effect_type == 'final') {
	/* 최종 배율은 기준 스탯을 다시 곱한 절대값이 아니라 레벨별 수치 자체다.
	 * 예: 레벨 설정값 1.5 = 해당 대상 스탯 ×1.5 */
	$default_vaule = is_numeric($sh['sl_set_value']) ? (float)$sh['sl_set_value'] : 0;
} else if($sh['sl_set_value']) {
	switch($sh['sk_value_type']) {
		case "+" : 
			$default_vaule = $default_vaule + $sh['sl_set_value'];
		break;
		case "x" : 
			$default_vaule = $default_vaule * $sh['sl_set_value'];
		break;
	}
}
if($effect_type != 'final') $default_vaule = intval($default_vaule);

$last_value = $default_vaule;

$st_name = "";
if($sk_mod_st_id) {
	$st_name = sql_fetch("select st_name from {$g5['status_config_table']} where st_id = '{$sk_mod_st_id}'");
	$st_name = $st_name['st_name'];
}

$skill_target = null;
$skill_target_list = array();
$s_index = 0;

switch($sh['sk_target']) {
	case "자신" :
		if($sh['sk_def_code']) {
			$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $character['ch_id'], $dm);
			switch($sh['sk_def_type']) {
				case "+" : 
					$last_value = $last_value + $mod_code['value'];
				break;
				case "-" : 
					$last_value = $last_value - $mod_code['value'];
				break;
			}
		}
		if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }
		
		$last_value_type = "";
		if($last_value > 0) {$last_value_type = "+";}

		$log .= "<p class=\'txt-skill-result\'>{$st_name}<em>{$last_value_type}{$last_value}</em></p>";
		
		$skill_target['target'] = $dm;
		$skill_target['value'] = $last_value;
		$skill_target['is_critical'] = $is_critical;
		
	break;
	case "아군" :
		if($re_ch == $character['ch_id']) {
			$re_dm = $dm;
		} else {
			$re_dm = get_dungeon_character($ds_id, $re_ch);
		}

		if($sh['sk_def_code']) {
			$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $re_dm['ch_id'], $re_dm);
			switch($sh['sk_def_type']) {
				case "+" : 
					$last_value = $last_value + $mod_code['value'];
				break;
				case "-" : 
					$last_value = $last_value - $mod_code['value'];
				break;
			}
		}
		if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }

		$last_value_type = "";
		if($last_value > 0) {$last_value_type = "+";}
		$log .= "<p class=\'txt-skill-result\'><strong>{$re_dm['ch_name']}</strong>에게 {$st_name}<em>{$last_value_type}{$last_value}</em></p>";
		
		$skill_target['target'] = $re_dm;
		$skill_target['value'] = $last_value;
		$skill_target['is_critical'] = $is_critical;

	break;
	case "아군전체" :
		$dm_list = get_dungeon_member($ds_id);
		for($i=0; $i < count($dm_list); $i++) { 
			$re_dm = $dm_list[$i];
			$last_value = $default_vaule;

			if($sh['sk_def_code']) {
				$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $re_dm['ch_id'], $re_dm);
				switch($sh['sk_def_type']) {
					case "+" : 
						$last_value = $last_value + $mod_code['value'];
					break;
					case "-" : 
						$last_value = $last_value - $mod_code['value'];
					break;
				}
			}
			if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }

			$last_value_type = "";
			if($last_value > 0) {$last_value_type = "+";}
			$log .= "<p class=\'txt-skill-result\'><strong>{$re_dm['ch_name']}</strong>에게 {$st_name}<em>{$last_value_type}{$last_value}</em></p>";
			
			$skill_target_list[$s_index]['target'] = $re_dm;
			$skill_target_list[$s_index]['value'] = $last_value;
			$skill_target_list[$s_index]['is_critical'] = $is_critical;
			$s_index++;
		}
	break;
	case "타인" :
		$re_dm = get_dungeon_character($ds_id, $re_ch);

		if($sh['sk_def_code']) {
			$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $re_dm['ch_id'], $re_dm);
			switch($sh['sk_def_type']) {
				case "+" : 
					$last_value = $last_value + $mod_code['value'];
				break;
				case "-" : 
					$last_value = $last_value - $mod_code['value'];
				break;
			}
		}
		if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }

		$last_value_type = "";
		if($last_value > 0) {$last_value_type = "+";}
		$log .= "<p class=\'txt-skill-result\'><strong>{$re_dm['ch_name']}</strong>에게 {$st_name}<em>{$last_value_type}{$last_value}</em></p>";
		
		$skill_target['target'] = $re_dm;
		$skill_target['value'] = $last_value;
		$skill_target['is_critical'] = $is_critical;

	break;
	case "타인전체" :
		$dm_list = get_dungeon_member($ds_id);
		for($i=0; $i < count($dm_list); $i++) { 
			$re_dm = $dm_list[$i];
			$last_value = $default_vaule;

			if($re_dm['ch_id'] == $character['ch_id']) continue;

			if($sh['sk_def_code']) {
				$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $re_dm['ch_id'], $re_dm);
				switch($sh['sk_def_type']) {
					case "+" : 
						$last_value = $last_value + $mod_code['value'];
					break;
					case "-" : 
						$last_value = $last_value - $mod_code['value'];
					break;
				}
			}
			if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }

			$last_value_type = "";
			if($last_value > 0) {$last_value_type = "+";}
			$log .= "<p class=\'txt-skill-result\'><strong>{$re_dm['ch_name']}</strong>에게 {$st_name}<em>{$last_value_type}{$last_value}</em></p>";
			
			$skill_target_list[$s_index]['target'] = $re_dm;
			$skill_target_list[$s_index]['value'] = $last_value;
			$skill_target_list[$s_index]['is_critical'] = $is_critical;
			$s_index++;
		}
	break;
	case "적" :
		if($sh['sk_def_enermy']) {
			$mod_code = array();
			
			switch($sh['sk_def_enermy']) {
				case "체력" : 
					$mod_code['value'] = $ds['ds_hp'] - $ds['ds_hurt'];
				break;
				case "공격" : 
					$mod_code['value'] = $ds['dg_d_attack_max'];
				break;
				case "방어" : 
					$mod_code['value'] = $ds['dg_defence'];
				break;
			}

			switch($sh['sk_def_type']) {
				case "+" : 
					$last_value = $last_value + $mod_code['value'];
				break;
				case "-" : 
					$last_value = $last_value - $mod_code['value'];
				break;
			}
		}
		if($sh['sk_mod_type'] == '-') { $last_value = $last_value * -1; }
		
		$last_value_type = "";
		if($last_value > 0) {$last_value_type = "+";}

		$log .= "<p class=\'txt-skill-result\'><strong>{$ds['dg_mon_name']}</strong>에게 {$sh['sk_def_enermy']}<em>{$last_value_type}{$last_value}</em></p>";
		
		$temp_dm = array();
		$temp_dm['ch_id'] = -1;
		$temp_dm['ch_name'] = $ds['dg_mon_name'];

		$skill_target['target'] = $temp_dm;
		$skill_target['value'] = $last_value;
		$skill_target['is_critical'] = $is_critical;
		
	break;
}

insert_dungeon_log("스킬", $ds, $dm, $sh, $default_vaule, $is_critical, $log);

if(count($skill_target_list) > 0) {
	for($i=0; $i < count($skill_target_list); $i++) {
		$skill_target = $skill_target_list[$i];
		if($sh['sk_keep_limit'] > 0) {
			insert_dungeon_log("효과", $ds, $skill_target['target'], $sh, $skill_target['value'], $skill_target['is_critical'], "");
		}
	}
} else {
	if($sh['sk_keep_limit'] > 0) {
		insert_dungeon_log("효과", $ds, $skill_target['target'], $sh, $skill_target['value'], $skill_target['is_critical'], "");
	}
}



?>
