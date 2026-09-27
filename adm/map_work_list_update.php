<?php
$sub_menu = "710500";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

if (!isset($_POST['chk']) || !count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 선택하세요.");
}

$count = count($_POST['chk']);

if ($_POST['act_button'] == "선택초기화") {
	for ($i=0; $i<$count; $i++) {
		$k = $_POST['chk'][$i];
		$work_id = (int)$_POST['work_id'][$k];
		$work = map_get_work($work_id);
		if(!$work['work_id'] || $work['reward_claimed'] != '0') continue;

		$ch = get_character($work['ch_id']);
		$action = get_map_action($work['action_id']);

		sql_query("
			update {$g5['map_work_table']}
				set work_status = 'CANCELLED',
					reward_claimed = '1',
					claimed_at = '".G5_TIME_YMDHIS."'
				where work_id = '{$work_id}'
					and reward_claimed = '0'
		");

		set_map_log($work['ch_id'], $ch['ch_name'], "[admin_cancel] {$action['action_name']} 작업 보상 없이 초기화", 0);
	}
}

$s_claimed = isset($_POST['s_claimed']) ? $_POST['s_claimed'] : 'active';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

goto_url('./map_work_list.php?s_claimed='.$s_claimed.'&page='.$page);
?>
