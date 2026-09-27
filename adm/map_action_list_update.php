<?php
$sub_menu = "710100";
include_once('./_common.php');

check_demo();
check_token();

$act_button = isset($_POST['act_button']) ? $_POST['act_button'] : '';
$return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '';

if($act_button == "선택삭제") {
	auth_check($auth[$sub_menu], 'd');
} else {
	auth_check($auth[$sub_menu], 'w');
}

function map_action_post_time($value, $default='00:00')
{
	return map_normalize_time($value, $default);
}

if(!$act_button) {
	$ma_id = (int)$_POST['ma_id'];
	$action_type = map_normalize_action_type(isset($_POST['action_type']) ? $_POST['action_type'] : '');
	$action_name = trim($_POST['action_name']);
	$action_desc = isset($_POST['action_desc']) ? $_POST['action_desc'] : '';
	$is_timed = isset($_POST['is_timed']) ? 1 : 0;
	$duration_minutes = (int)$_POST['duration_minutes'];
	$available_time_use = isset($_POST['available_time_use']) ? 1 : 0;
	$available_start = map_action_post_time($_POST['available_start']);
	$available_end = map_action_post_time($_POST['available_end']);
	$action_order = isset($_POST['action_order']) ? (int)$_POST['action_order'] : 0;
	$action_use = isset($_POST['action_use']) ? 1 : 0;

	if(!$ma_id) alert("장소를 선택하세요.");
	if(!$action_name) alert("행동명을 입력하세요.");
	if($duration_minutes < 0) $duration_minutes = 0;

	sql_query("
		insert into {$g5['map_action_table']}
			set ma_id = '{$ma_id}',
				action_type = '{$action_type}',
				action_name = '{$action_name}',
				action_desc = '{$action_desc}',
				action_order = '{$action_order}',
				action_use = '{$action_use}',
				is_timed = '{$is_timed}',
				duration_minutes = '{$duration_minutes}',
				available_time_use = '{$available_time_use}',
				available_start = '{$available_start}',
				available_end = '{$available_end}'
	");

	if($return_url) goto_url(str_replace('&amp;', '&', $return_url));
	goto_url('./map_action_list.php?ma_id='.$ma_id);
}

if (!isset($_POST['chk']) || !count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 선택하세요.");
}

$count = count($_POST['chk']);
$ma_id_filter = (int)$_POST['ma_id_filter'];
$s_action_type = $_POST['s_action_type'];

if ($act_button == "선택수정") {
	for ($i=0; $i<$count; $i++) {
		$k = $_POST['chk'][$i];
		$action_id = (int)$_POST['action_id'][$k];
		$action_type = map_normalize_action_type(isset($_POST['action_type'][$k]) ? $_POST['action_type'][$k] : '');
		$action_name = trim($_POST['action_name'][$k]);
		$action_desc = $_POST['action_desc'][$k];
		$is_timed = isset($_POST['is_timed'][$k]) ? 1 : 0;
		$duration_minutes = (int)$_POST['duration_minutes'][$k];
		$available_time_use = isset($_POST['available_time_use'][$k]) ? 1 : 0;
		$available_start = map_action_post_time($_POST['available_start'][$k]);
		$available_end = map_action_post_time($_POST['available_end'][$k]);
		$action_order = isset($_POST['action_order'][$k]) ? (int)$_POST['action_order'][$k] : 0;
		$action_use = isset($_POST['action_use'][$k]) ? 1 : 0;

		if($duration_minutes < 0) $duration_minutes = 0;

		sql_query("
			update {$g5['map_action_table']}
				set action_type = '{$action_type}',
					action_name = '{$action_name}',
					action_desc = '{$action_desc}',
					action_order = '{$action_order}',
					action_use = '{$action_use}',
					is_timed = '{$is_timed}',
					duration_minutes = '{$duration_minutes}',
					available_time_use = '{$available_time_use}',
					available_start = '{$available_start}',
					available_end = '{$available_end}'
				where action_id = '{$action_id}'
		");
	}
} else if ($act_button == "선택삭제") {
	for ($i=0; $i<$count; $i++) {
		$k = $_POST['chk'][$i];
		$action_id = (int)$_POST['action_id'][$k];
		$active_work = sql_fetch(" select count(*) as cnt from {$g5['map_work_table']} where action_id = '{$action_id}' and reward_claimed = '0' ");
		if($active_work['cnt']) {
			sql_query(" update {$g5['map_action_table']} set action_use = '0' where action_id = '{$action_id}' ");
		} else {
			sql_query(" delete from {$g5['map_action_table']} where action_id = '{$action_id}' ");
			sql_query(" update {$g5['map_event_table']} set action_id = '0' where action_id = '{$action_id}' ");
		}
	}
}

if($return_url) goto_url(str_replace('&amp;', '&', $return_url));
goto_url('./map_action_list.php?ma_id='.$ma_id_filter.'&s_action_type='.$s_action_type);
?>
