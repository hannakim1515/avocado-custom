<?php
include_once('./_common.php');

function map_action_popup($html, $class='') {
	echo '<div class="descript '.$class.'"><div class="tbl"><div class="cell">'.$html.'</div></div></div>';
}

$action_id = isset($_POST['action_id']) ? (int)$_POST['action_id'] : 0;
$action = get_map_action($action_id);

if(!$is_member || !$character['ch_id']) {
	map_action_popup('<div class="txt error">로그인 후 이용할 수 있습니다.</div>');
	exit;
}

if($character['ch_state'] != '승인') {
	map_action_popup('<div class="txt error">사용 가능한 캐릭터가 아닙니다.</div>');
	exit;
}

if(!$action['action_id'] || !$action['action_use']) {
	map_action_popup('<div class="txt error">사용할 수 없는 행동입니다.</div>');
	exit;
}

if($action['ma_id'] != $character['ma_id']) {
	map_action_popup('<div class="txt error">현재 위치에서 사용할 수 없는 행동입니다.</div>');
	exit;
}

if(!map_is_action_available_time($action)) {
	map_action_popup('<div class="txt error">현재 시간에는 이용할 수 없습니다.<br>가능 시간 '.map_action_time_label($action).'</div>');
	exit;
}

if($action['is_timed']) {
	$active_work = map_get_active_work($character['ch_id']);
	if(isset($active_work['work_id']) && $active_work['work_id']) {
		$active_action = get_map_action($active_work['action_id']);
		if($active_work['work_status'] == 'COMPLETE') {
			map_action_popup('<div class="txt error">완료된 작업의 결과를 먼저 확인해 주세요.<br><button type="button" onclick="map_work_complete('.$active_work['work_id'].');" class="ui-btn app"><span>결과 확인</span></button></div>');
		} else {
			map_action_popup('<div class="txt error">현재 '.$active_action['action_name'].' 진행 중입니다.<br>남은 시간 '.map_format_seconds(map_remaining_seconds($active_work)).'</div>');
		}
		exit;
	}

	$duration = (int)$action['duration_minutes'];
	if($duration <= 0) $duration = 1;
	$complete_at = date('Y-m-d H:i:s', G5_SERVER_TIME + ($duration * 60));

	sql_query("
		insert into {$g5['map_work_table']}
			set ch_id = '{$character['ch_id']}',
				mb_id = '{$member['mb_id']}',
				action_id = '{$action['action_id']}',
				ma_id = '{$action['ma_id']}',
				started_at = '".G5_TIME_YMDHIS."',
				complete_at = '{$complete_at}',
				work_status = 'WORKING',
				result_event_id = '0',
				reward_claimed = '0'
	");
	$work_id = sql_insert_id();

	set_map_log($character['ch_id'], $character['ch_name'], "[work_start] {$action['action_name']} 시작 / 완료 예정 {$complete_at}", 0);

	map_action_popup('
		<div class="txt">
			<strong>'.get_text($action['action_name']).'를 시작했습니다.</strong><br>
			소요시간 '.map_format_minutes($duration).'<br>
			완료 예정 '.$complete_at.'
		</div>
	');
	exit;
}

$me = map_select_action_event($action);
if(isset($me['me_id']) && $me['me_id']) {
	$log = "[action] {$action['action_name']} / 이벤트『{$me['me_title']}』 ({$me['me_content']})";
	$item = map_apply_event_reward($character['ch_id'], $member['mb_id'], $me, 'map_action', $action['action_id'], G5_TIME_YMDHIS, $log);
	sql_query(" update {$g5['map_event_table']} set me_now_cnt = me_now_cnt + 1 where me_id = '{$me['me_id']}' ");
	set_map_log($character['ch_id'], $character['ch_name'], $log, $me['me_id']);

	ob_start();
	include(G5_PATH.'/map/inc/search_event.php');
	$html = ob_get_contents();
	ob_end_clean();
	echo $html;
} else {
	map_action_popup('<div class="txt">'.get_text($action['action_name']).'를 마쳤습니다.<br>특별한 일은 없었습니다.</div>');
}
?>
