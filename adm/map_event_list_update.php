<?php
$sub_menu = '710100';
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_token();

$ma_id = $_REQUEST['ma_id'];

if (!count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}


$count = count($_POST['chk']);
if ($_POST['act_button'] == "선택수정") {
	for ($i=0; $i<$count; $i++) {
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];
		$action_id = isset($_POST['action_id'][$k]) ? (int)$_POST['action_id'][$k] : 0;
		$me_type = (isset($_POST['me_type'][$k]) && $_POST['me_type'][$k] == 'parttime') ? 'parttime' : 'search';
		if($me_type == 'search') {
			$action_id = 0;
		} else {
			$action = get_map_action($action_id);
			if(!$action['action_id'] || $action['ma_id'] != $ma_id) {
				alert("아르바이트/외주 이벤트는 연결할 MAP 행동을 선택하세요.");
			} else {
				$me_type = map_normalize_action_type($action['action_type']);
			}
		}

		if($_POST['me_get_item_name'][$k]) {
			$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$_POST['me_get_item_name'][$k]}'");
			$me_get_item = $it['it_id'];
		} else {
			$me_get_item = "";
		}
		$me_per_s = 1;
		$me_per_e = isset($_POST['me_per_e'][$k]) ? (int)$_POST['me_per_e'][$k] : 100;
		if($me_per_e < 0) $me_per_e = 0;
		if($me_per_e > 100) $me_per_e = 100;
		
		$sql_common = "
			action_id		= '{$action_id}',
			me_type			= '{$me_type}',
			me_title		= '{$_POST['me_title'][$k]}',
			me_content		= '{$_POST['me_content'][$k]}',
			me_get_item		= '{$me_get_item}',
			me_get_money	= '{$_POST['me_get_money'][$k]}',
			me_move_map		= '{$_POST['me_move_map'][$k]}',
			me_per_s		= '{$me_per_s}',
			me_per_e		= '{$me_per_e}',
			me_replay_cnt	= '{$_POST['me_replay_cnt'][$k]}',
			me_now_cnt		= '{$_POST['me_now_cnt'][$k]}',
			me_use			= '{$_POST['me_use'][$k]}'
		";
		$sql = " update {$g5['map_event_table']} set {$sql_common} where me_id = '{$_POST['me_id'][$k]}'";
		sql_query($sql);
	}
} else if ($_POST['act_button'] == "선택삭제") {
	for ($i=0; $i<$count; $i++) {
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];
		$sql = " delete from {$g5['map_event_table']} where me_id = '{$_POST['me_id'][$k]}'";
		sql_query($sql);
	}
}

$s_me_type = isset($_POST['s_me_type']) ? $_POST['s_me_type'] : '';
$s_action_id = isset($_POST['s_action_id']) ? (int)$_POST['s_action_id'] : 0;
$s_me_use = isset($_POST['s_me_use']) ? $_POST['s_me_use'] : '';

goto_url('./map_event_list.php?ma_id='.$ma_id.'&s_me_type='.$s_me_type.'&s_action_id='.$s_action_id.'&s_me_use='.$s_me_use.'&'.$qstr);

?>
