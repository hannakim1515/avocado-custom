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

$rate = 0;
if($sh['sk_status_code']) {
	$code = get_status_dungeon($sh['sk_status_code'], $ds_id, $character['ch_id'], $dm);
	$rate = intval($code['value']);
}
if($sh['sl_set_value']) {
	if($sh['sk_value_type'] == 'x' && $rate > 0) $rate = intval($rate * $sh['sl_set_value']);
	else $rate += intval($sh['sl_set_value']);
}
$rate = min(90, max(0, $rate));

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

insert_dungeon_log("스킬", $ds, $dm, $sh, 0, 0, $log);
$guard_skill = $sh;
$guard_skill['sk_keep_limit'] = max(1, intval($sh['sk_keep_limit']));
for($i=0; $i<count($targets); $i++) {
	if(empty($targets[$i]['dm_id'])) continue;
	insert_dungeon_log("효과", $ds, $targets[$i], $guard_skill, $rate, 0, "");
	$log_target = $targets[$i]['ch_name'];
	sql_query("update {$g5['dungeon_member_table']} set dm_comment = '방어: {$rate}% 피해 감소' where dm_id = '{$targets[$i]['dm_id']}'");
}
?>
