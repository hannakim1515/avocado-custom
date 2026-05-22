<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 배틀 시작
$battle_log = "";
$battle_sql = "";

$ch_value = 0;
$ch_damage = 0;

$re_ch_value = 0;
$re_ch_damage = 0;

// K 전투 공식이 상대 스탯을 참조할 수 있도록 선공 계산에도 상대 캐릭터를 함께 넘긴다.
$ch_battle_info = get_battle_point($ch['ch_id'], 'before', 0, $add_status, $re_ch['ch_id']);
$ch_value = $ch_battle_info['value'];

// 대응 종류 체크
// 자동일 경우 지금 여기서 바로 상대편의 수치를 뽑아내어 체크한다.
// 공격 대응 상태일 경우 바로 정산 들어간다.
if($battle_config['bc_proc'] == '자동' || $action == 'BATTLE_ANSWER') {
	// 후공 계산도 같은 방식으로 target을 넘겨 K battlefunc.inc.php의 대상 참조를 살린다.
	$re_ch_battle_info = get_battle_point($re_ch['ch_id'], 'after', 0, $add_after_status, $ch['ch_id']);
	$re_ch_value = $re_ch_battle_info['value'];

	// 같은 이름의 전투 함수가 다른 플러그인에 있어도 K 브릿지 계산을 우선 사용한다.
	if(function_exists('k_status_bridge_get_battle_result')) {
		$battle_result = k_status_bridge_get_battle_result($ch['ch_id'], $ch_value, $re_ch['ch_id'], $re_ch_value);
	} else {
		$battle_result = get_battle_result($ch['ch_id'], $ch_value, $re_ch['ch_id'], $re_ch_value);
	}
	$battle_result['ch_reward'] = str_replace("||", "^^", $battle_result['ch_reward']);
	$battle_result['re_ch_reward'] = str_replace("||", "^^", $battle_result['re_ch_reward']);

	// 대미지가 나온 상태에서 hp에 적용하기
	$ch_hp = set_extra_hp($ch['ch_id'], ($battle_result['ch_damage'] * -1));
	$re_ch_hp = set_extra_hp($re_ch['ch_id'], ($battle_result['re_ch_damage'] * -1));

	// 전투 진행 사항 처리 : N : 진행중 / E : 엔드
	$battle_log = $action."||E||";
	$battle_log .= "{$ch['ch_id']}&&{$ch['ch_name']}&&{$ch_hp['has']}&&{$ch_hp['now']}&&{$ch_battle_info['default']}&&{$ch_battle_info['is_cri']}&&{$ch_battle_info['cri_value']}&&{$ch_battle_info['value']}&&{$battle_result['ch_damage']}&&{$battle_result['ch_reward']}";
	$battle_log .= "||";
	$battle_log .= "{$re_ch['ch_id']}&&{$re_ch['ch_name']}&&{$re_ch_hp['has']}&&{$re_ch_hp['now']}&&{$re_ch_battle_info['default']}&&{$re_ch_battle_info['is_cri']}&&{$re_ch_battle_info['cri_value']}&&{$re_ch_battle_info['value']}&&{$battle_result['re_ch_damage']}&&{$battle_result['re_ch_reward']}";
	$battle_log .= "||";
	$battle_log .= "{$battle_result['win']}||{$battle_result['lose']}||{$battle_result['both']}";

} else {
	$battle_log = $action."||N||";
	$battle_log .= "{$ch['ch_id']}&&{$ch['ch_name']}&&{$ch_hp['has']}&&{$ch_hp['now']}";
	$battle_log .= "||";
	$battle_log .= "{$re_ch['ch_id']}&&{$re_ch['ch_name']}&&{$re_ch_hp['has']}&&{$re_ch_hp['now']}";
}

?>
