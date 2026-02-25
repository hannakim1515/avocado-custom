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

		if($_POST['me_get_item_name'][$k]) {
			$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$_POST['me_get_item_name'][$k]}'");
			$me_get_item = $it['it_id'];
		} else {
			$me_get_item = "";
		}
		
		$sql_common = "
			me_title		= '{$_POST['me_title'][$k]}',
			me_content		= '{$_POST['me_content'][$k]}',
			me_get_item		= '{$me_get_item}',
			me_get_money	= '{$_POST['me_get_money'][$k]}',
			me_move_map		= '{$_POST['me_move_map'][$k]}',
			me_per_s		= '{$_POST['me_per_s'][$k]}',
			me_per_e		= '{$_POST['me_per_e'][$k]}',
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

goto_url('./map_event_list.php?ma_id='.$ma_id.'&'.$qstr);

?>