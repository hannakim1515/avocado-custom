<?php
$sub_menu = "710100";
include_once('./_common.php');
$ma_id = $_REQUEST['ma_id'];

if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

if($it_name) {
	$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$it_name}'");
	$me_get_item = $it['it_id'];
} else {
	$me_get_item = "";
}

if (isset($_POST['me_content'])) {
	$me_content = substr(trim($_POST['me_content']),0,65536);
	$me_content = preg_replace("#[\\\]+$#", "", $me_content);
}


$sql_common = "
	ma_id			= '{$ma_id}',
	me_title		= '{$me_title}',
	me_content		= '{$me_content}',
	me_get_item		= '{$me_get_item}',
	me_get_money	= '{$me_get_money}',
	me_move_map		= '{$me_move_map}',
	me_per_s		= '{$me_per_s}',
	me_per_e		= '{$me_per_e}',
	me_replay_cnt	= '{$me_replay_cnt}',
	me_now_cnt		= '{$me_now_cnt}',
	me_use			= '{$me_use}'
";


if($w == '') { 
	$sql = " insert into {$g5['map_event_table']} set {$sql_common}";
	sql_query($sql);
} else {
	$me = sql_fetch("select me_id from {$g5['map_event_table']} where me_id = '{$me_id}'");
	if(!$me['me_id']) { alert("이벤트 정보가 존재하지 않습니다."); }

	$sql = " update {$g5['map_event_table']}
				set {$sql_common}
				where me_id = '{$me_id}'";
	sql_query($sql);
}

goto_url('./map_event_list.php?ma_id='.$ma_id.'&'.$qstr, false);
?>
