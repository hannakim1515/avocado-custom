<?php
$sub_menu = "400900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

if(!$it_id && $it_name) {
	$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$it_name}'");
	if($it['it_id'] == 0) {
		alert("해당 아이템의 정보가 없습니다.");
	}
	$it_id = $it['it_id'];
}

$multi_check = sql_fetch("select count(*) as cnt from {$g5['npc_item_table']} where ch_id = '{$ch_id}' and it_id = '{$it_id}'");
if($multi_check['cnt'] > 0) {
	alert("이미 등록된 아이템 정보입니다.");
}

$sql = " insert into {$g5['npc_item_table']}
			set ch_id = '{$ch_id}',
				it_id = '{$it_id}',
				it_name = '{$it_name}',

				ni_value = '{$ni_value}',
				ni_max_count = '{$ni_max_count}',
				ni_comment = '{$ni_comment}',
				ni_max_is_total = '{$ni_max_is_total}',
				ni_max_comment = '{$ni_max_comment}'";
sql_query($sql);

goto_url('./npc_item_list.php?'.$qstr.'&amp;ns_id='.$ch_id);
?>
