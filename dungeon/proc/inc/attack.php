<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

// 공격 스킬 : 사용 대상은 적으로 고정됩니다.
// - 사용 대상 : 적 (고정)
// - 대상의 체력 감소 (고정)


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
if($sh['sl_set_value']) {
	switch($sh['sk_value_type']) {
		case "+" : 
			$default_vaule = $default_vaule + $sh['sl_set_value'];
		break;
		case "x" : 
			$default_vaule = $default_vaule * $sh['sl_set_value'];
		break;
	}
}

// 공통 대미지 버프 적용
$damage_buff = get_status_buffer_code($ds_id, $character['ch_id'], '공통대미지');
$default_vaule += ($damage_buff*1);

// 3. 결과 수정 처리 : 대상의 수치에 따른 결과 수정을 적용합니다.
$last_vaule = $default_vaule;
if($sh['sk_def_enermy']) {
	$target_value = 0;
	switch($sh['sk_def_enermy']) {
		case "체력" : 
			// 적 체력
			$temp_buff = get_status_buffer_enermy($ds_id, "체력");
			$temp = $ds['ds_hp'] - $ds['ds_hurt'] + ($temp_buff);
			$target_value = $temp;
		break;
		case "공격" : 
			// 적 공격
			$temp_buff = get_status_buffer_enermy($ds_id, "공격");
			$target_value = $ds['dg_d_attack_max'] + ($temp_buff);
		break;
		case "방어" : 
			// 적 방어
			$temp_buff = get_status_buffer_enermy($ds_id, "방어");
			$target_value = $ds['dg_defence'] + ($temp_buff);
		break;
	}

	switch($sh['sk_def_type']) {
		case "+" : 
			$last_vaule = $last_vaule + $target_value;
		break;
		case "-" : 
			$last_vaule = $last_vaule - $target_value;
		break;
	}
	if($last_vaule < 0) {$last_vaule = 0;}
}

$is_strong = false;
$is_weak = false;
$attack_other_comment = "";
if($sh['sk_status_code'] && $ds['dg_strong_code'] && $ds['dg_strong_code'] == $sh['sk_status_code']) {
	// 강점 처리 (멤버에게 불리함)
	$last_vaule = $last_vaule * $ds['dg_strong_value'];
	$is_strong = true;
	$attack_other_comment = "「{$ds['dg_mon_name']}」이(가) 대미지의 일부를 상쇄시켰습니다.";
}
if($sh['sk_status_code'] && $ds['dg_weak_code'] && $ds['dg_weak_code'] == $sh['sk_status_code']) {
	// 취약 처리 (멤버에게 유리함)
	// -- 취약점 카운트 처리
	$last_vaule = $last_vaule * $ds['dg_weak_value'];
	$is_weak = true;
	$attack_other_comment = "「{$ds['dg_mon_name']}」이(가) 경직됩니다.";
}

$last_vaule = intval($last_vaule);

$skill_name = $sh['sh_name'] ? $sh['sh_name'] : $sh['sk_name'];
$txt_critical = $is_critical ? "<i>크리티컬!</i>" : "";

$log = "<p class=\'txt-skill-info\'><strong>{$skill_name}</strong> 스킬을 사용했습니다.</p>";
$log .= "<p class=\'txt-skill-info ty2\'>{$sh['sk_descript']}</p>";
if($attack_other_comment) {
	$log .= "<p class=\'txt-skill-info\'>{$attack_other_comment}</p>";
}
$log .= "<p class=\'txt-skill-result\'>대미지 <em>{$last_vaule}!</em> {$txt_critical}</p>";

// 4. 최종 몹 hp 결과
// -- 몹 대미지 수행
insert_dungeon_log("스킬", $ds, $dm, $sh, $last_vaule, $is_critical, $log);
$result_state = set_dungeon_mon_damage($ds_id, $ds, $last_vaule, $is_weak);

if($result_state == 'G') {
	$ds['ds_weak_turn'] = $ds['dg_weak_effect_turn'];
	insert_dungeon_log("몬스터", $ds, null, null, null, null,"「{$ds['dg_mon_name']}」이(가) 그로기 상태에 빠집니다!&&&&{$ds['dg_weak_effect_turn']}회 공격 무효");
}

if($sh['sk_keep_limit'] > 0) {
	$re_dm = array();
	$re_dm['ch_id'] = -1;
	$re_dm['ch_name'] = $ds['dg_mon_name'];
	insert_dungeon_log("효과", $ds, $re_dm, $sh, $last_vaule, 0, "");
}


?>