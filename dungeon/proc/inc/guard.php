<?
if (!defined('_GNUBOARD_')) exit;

/*
 * A 통합 방어 스킬. 기본수식 + 레벨 수치로 피해감소율을 만들고, 던전 로그의
 * 유지 턴을 사용한다. 실시간 레이드도 같은 스킬 스냅샷 값으로 unified_guard를
 * 적용하므로 스킬 정의는 한 번만 관리한다.
 */
$skill_name = $sh['sh_name'] ? $sh['sh_name'] : $sh['sk_name'];
$log = "<p class='txt-skill-info'><strong>{$skill_name}</strong> 스킬을 사용했습니다.</p>";
$log .= "<p class='txt-skill-info ty2'>{$sh['sk_descript']}</p>";

$base_rate = 0;
if($sh['sk_status_code']) {
	$code = get_status_dungeon($sh['sk_status_code'], $ds_id, $character['ch_id'], $dm);
	$base_rate = intval($code['value']);
}
if($sh['sl_set_value']) {
	if($sh['sk_value_type'] == 'x' && $base_rate > 0) $base_rate = intval($base_rate * $sh['sl_set_value']);
	else $base_rate += intval($sh['sl_set_value']);
}
$base_rate = min(90, max(0, $base_rate));

$targets = array();
switch($sh['sk_target']) {
	case "자신":
		$targets[] = $dm;
	break;
	case "아군":
		$targets[] = ($re_ch == $character['ch_id']) ? $dm : get_dungeon_character($ds_id, $re_ch);
	break;
	case "아군전체":
		$targets = get_dungeon_member($ds_id);
	break;
	case "타인":
		$targets[] = get_dungeon_character($ds_id, $re_ch);
	break;
	case "타인전체":
		$members = get_dungeon_member($ds_id);
		for($i=0; $i<count($members); $i++) if($members[$i]['ch_id'] != $character['ch_id']) $targets[] = $members[$i];
	break;
}

$guard_targets = array();
$log .= "<p class='txt-skill-result'>받는 피해 감소 효과를 적용합니다.</p>";
$guard_skill = $sh;
$guard_skill['sk_keep_limit'] = max(1, intval($sh['sk_keep_limit']));
for($i=0; $i<count($targets); $i++) {
	if(empty($targets[$i]['dm_id'])) continue;
	$rate = $base_rate;
	if($sh['sk_def_code']) {
		$mod_code = get_status_dungeon($sh['sk_def_code'], $ds_id, $targets[$i]['ch_id'], $targets[$i]);
		if($sh['sk_def_type'] == '-') $rate -= intval($mod_code['value']);
		else if($sh['sk_def_type'] == '+') $rate += intval($mod_code['value']);
	}
	$rate = min(90, max(0, $rate));
	$guard_targets[] = array('target' => $targets[$i], 'rate' => $rate);
	$log .= "<p class='txt-skill-result'><strong>{$targets[$i]['ch_name']}</strong>의 받는 피해 <em>{$rate}% 감소</em></p>";
}

/* 행동 본문은 한 번만 남기고, 지속 효과 행은 기존처럼 화면 로그에서 제외한다. */
insert_dungeon_log("스킬", $ds, $dm, $sh, $base_rate, 0, $log);
for($i=0; $i<count($guard_targets); $i++) {
	$target = $guard_targets[$i]['target'];
	$rate = $guard_targets[$i]['rate'];
	insert_dungeon_log("효과", $ds, $target, $guard_skill, $rate, 0, "");
	sql_query("update {$g5['dungeon_member_table']} set dm_comment = '방어: {$rate}% 피해 감소' where dm_id = '{$target['dm_id']}'");
}
?>
