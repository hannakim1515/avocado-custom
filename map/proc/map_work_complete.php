<?php
include_once('./_common.php');

function map_work_popup($html, $class='') {
	echo '<div class="descript '.$class.'"><div class="tbl"><div class="cell">'.$html.'</div></div></div>';
}

if(!$is_member || !$character['ch_id']) {
	map_work_popup('<div class="txt error">로그인 후 이용할 수 있습니다.</div>');
	exit;
}

$work_id = isset($_POST['work_id']) ? (int)$_POST['work_id'] : 0;
$work = $work_id ? map_get_work($work_id) : map_get_active_work($character['ch_id']);

if(!$work['work_id'] || $work['ch_id'] != $character['ch_id']) {
	map_work_popup('<div class="txt error">확인할 작업이 없습니다.</div>');
	exit;
}

$work = map_refresh_work_status($work);
$action = get_map_action($work['action_id']);
if(!$action['action_id']) {
	$action = array(
		'action_id' => $work['action_id'],
		'ma_id' => $work['ma_id'],
		'action_type' => '',
		'action_name' => '작업'
	);
}

if($work['reward_claimed'] != '0') {
	map_work_popup('<div class="txt error">이미 보상을 수령한 작업입니다.</div>');
	exit;
}

if($work['work_status'] != 'COMPLETE') {
	map_work_popup('<div class="txt error">아직 작업이 끝나지 않았습니다.<br>남은 시간 '.map_format_seconds(map_remaining_seconds($work)).'</div>');
	exit;
}

if(!$work['result_event_id']) {
	$me = map_select_action_event($action);
	$result_event_id = (isset($me['me_id']) && $me['me_id']) ? (int)$me['me_id'] : -1;
	sql_query(" update {$g5['map_work_table']} set result_event_id = '{$result_event_id}' where work_id = '{$work['work_id']}' and result_event_id = '0' ");

	if(map_sql_affected_rows() != 1) {
		$work = map_get_work($work['work_id']);
		$me = $work['result_event_id'] > 0 ? map_get_event($work['result_event_id']) : array();
	}
} else {
	$me = $work['result_event_id'] > 0 ? map_get_event($work['result_event_id']) : array();
}

sql_query(" update {$g5['map_work_table']} set reward_claimed = '2' where work_id = '{$work['work_id']}' and ch_id = '{$character['ch_id']}' and reward_claimed = '0' ");
if(map_sql_affected_rows() != 1) {
	map_work_popup('<div class="txt error">이미 처리 중이거나 수령이 끝난 작업입니다.</div>');
	exit;
}

$item = array();
if(isset($me['me_id']) && $me['me_id']) {
	$log = "[reward_claim] {$action['action_name']} 완료 / 이벤트『{$me['me_title']}』 ({$me['me_content']})";
	$item = map_apply_event_reward($character['ch_id'], $member['mb_id'], $me, 'map_work', $work['work_id'], 'reward', $log);
	sql_query(" update {$g5['map_event_table']} set me_now_cnt = me_now_cnt + 1 where me_id = '{$me['me_id']}' ");
	set_map_log($character['ch_id'], $character['ch_name'], $log, $me['me_id']);
} else {
	$log = "[reward_claim] {$action['action_name']} 완료 / 이벤트 없음";
	set_map_log($character['ch_id'], $character['ch_name'], $log, 0);
}

sql_query("
	update {$g5['map_work_table']}
		set reward_claimed = '1',
			claimed_at = '".G5_TIME_YMDHIS."'
		where work_id = '{$work['work_id']}'
");

if(isset($me['me_id']) && $me['me_id']) {
	ob_start();
	include(G5_PATH.'/map/inc/search_event.php');
	$html = ob_get_contents();
	ob_end_clean();
	echo $html;
} else {
	map_work_popup('<div class="txt"><strong>'.get_text($action['action_name']).'를 마쳤습니다.</strong><br>특별한 일은 없었습니다.</div>');
}
?>
