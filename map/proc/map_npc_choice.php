<?php
include_once('./_common.php');

function map_npc_choice_error($message)
{
	global $character;
	map_vn_payload_marker(array(
		'type' => 'error',
		'ma_id' => isset($character['ma_id']) ? (int)$character['ma_id'] : 0,
		'title' => '선택지',
		'speaker' => '선택지',
		'content' => $message
	));
	exit;
}

if(!$is_member || !$character['ch_id']) {
	map_npc_choice_error('로그인 후 이용할 수 있습니다.');
}

$encounter_id = isset($_POST['encounter_id']) ? (int)$_POST['encounter_id'] : 0;
$choice_id = isset($_POST['choice_id']) ? (int)$_POST['choice_id'] : 0;

$encounter = map_get_npc_encounter($encounter_id);
if(!$encounter['enc_id'] || $encounter['ch_id'] != $character['ch_id']) {
	map_npc_choice_error('확인할 수 없는 이벤트입니다.');
}

if($encounter['ma_id'] != $character['ma_id']) {
	map_npc_choice_error('현재 위치에서 처리할 수 없는 이벤트입니다.');
}

if($encounter['script_type'] != 'event') {
	map_npc_choice_error('선택지가 없는 이벤트입니다.');
}

$choice = sql_fetch("
	select *
		from {$g5['map_npc_choice_table']}
		where choice_id = '{$choice_id}'
			and script_id = '{$encounter['script_id']}'
			and choice_use = '1'
");

if(!$choice['choice_id']) {
	map_npc_choice_error('사용할 수 없는 선택지입니다.');
}

$is_success = map_evaluate_npc_choice_condition($choice, $character['ch_id'], $member['mb_id']);

sql_query("
	update {$g5['map_npc_encounter_table']}
		set reward_claimed = '2'
		where enc_id = '{$encounter['enc_id']}'
			and ch_id = '{$character['ch_id']}'
			and reward_claimed = '0'
");

if(map_sql_affected_rows() != 1) {
	map_npc_choice_error('이미 처리된 이벤트입니다.');
}

$npc = map_get_npc($encounter['npc_id']);
$map_name = get_map_name($encounter['ma_id']);
$rewards = array();
$log = "[npc_choice] {$npc['ch_name']} / {$encounter['script_title']} / {$choice['choice_text']}";
$choice_get_item = $is_success ? $choice['choice_get_item'] : $choice['choice_fail_get_item'];
$choice_get_money = $is_success ? $choice['choice_get_money'] : $choice['choice_fail_get_money'];
$choice_favor_value = $is_success ? (int)$choice['choice_favor_value'] : (int)$choice['choice_fail_favor_value'];
$script_favor_value = isset($encounter['script_favor_value']) ? (int)$encounter['script_favor_value'] : 0;
$total_favor_value = $script_favor_value + $choice_favor_value;
$choice_result_text = $is_success ? $choice['choice_result'] : ($choice['choice_fail_result'] ?: $choice['choice_result']);

if($choice_get_item) {
	$item = get_item((int)$choice_get_item);
	if($item['it_id']) {
		insert_inventory($character['ch_id'], $item['it_id'], $item);
		$rewards[] = array(
			'type' => 'item',
			'title' => "아이템 《{$item['it_name']}》 획득",
			'desc' => isset($item['it_content']) ? $item['it_content'] : '',
			'img' => isset($item['it_img']) ? $item['it_img'] : ''
		);
		$log .= " / 아이템 《{$item['it_name']}》 획득";
	}
}

if($choice_get_money) {
	$money = (int)$choice_get_money;
	insert_point($member['mb_id'], $money, "[MAP NPC]{$choice['choice_text']}", 'map_npc', $encounter['enc_id'], 'choice');
	$rewards[] = array(
		'type' => 'money',
		'title' => "{$config['cf_money']} ".number_format($money)." {$config['cf_money_pice']} ".($money > 0 ? '획득' : '지불'),
		'desc' => '',
		'img' => ''
	);
	$log .= " / {$config['cf_money']} {$money} {$config['cf_money_pice']} ".($money > 0 ? '획득' : '지불');
}

if($total_favor_value != 0) {
	$favor_value = map_apply_npc_favor($encounter['npc_id'], $character['ch_id'], $total_favor_value, "[MAP] {$encounter['script_title']} / {$choice['choice_text']}");
	if($favor_value) {
		$rewards[] = array(
			'type' => 'favor',
			'title' => '호감도 '.($favor_value > 0 ? '+' : '').$favor_value,
			'desc' => $npc['ch_name'],
			'img' => ''
		);
		$log .= " / 호감도 ".($favor_value > 0 ? '+' : '').$favor_value;
	}
}

sql_query("
	update {$g5['map_npc_encounter_table']}
		set choice_id = '{$choice['choice_id']}',
			reward_claimed = '1',
			enc_status = 'COMPLETE',
			claimed_at = '".G5_TIME_YMDHIS."'
		where enc_id = '{$encounter['enc_id']}'
");

set_map_log($character['ch_id'], $character['ch_name'], $log, 0);

$content = map_replace_script_vars($choice_result_text, array(
	'npc_name' => $npc['ch_name'],
	'map_name' => $map_name
));

map_vn_payload_marker(array(
	'type' => 'npc_choice_result',
	'ma_id' => (int)$encounter['ma_id'],
	'title' => $choice['choice_text'],
	'speaker' => $npc['ch_name'] ? $npc['ch_name'] : '결과',
	'npc_id' => (int)$encounter['npc_id'],
	'npc_name' => $npc['ch_name'],
	'npc_img' => $npc['ch_body'],
	'npc_cast' => map_extract_script_cast($content, $npc),
	'content' => $content,
	'rewards' => $rewards,
	'success' => $is_success
));
?>
