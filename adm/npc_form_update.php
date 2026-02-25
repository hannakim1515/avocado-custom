<?php
$sub_menu = "400900";
include_once("./_common.php");
if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

$ns_lv0_item = 0;
$ns_lv1_item = 0;
$ns_lv2_item = 0;
$ns_lv3_item = 0;
$ns_lv4_item = 0;

for($i=0; $i < 5; $i++) {
	if(${"ns_lv".$i."_item_name"}) {
		$it = sql_fetch("select it_id from {$g5['item_table']} where it_name = '".${"ns_lv".$i."_item_name"}."'");
		${"ns_lv".$i."_item"} = $it['it_id'];
	}
}

$sql_common = "		ns_talk				='{$ns_talk}',
					ns_talk_item		='{$ns_talk_item}',
					ns_talk_point		='{$ns_talk_point}',
					ns_talk_max			='{$ns_talk_max}',
					ns_talk_max_txt		='{$ns_talk_max_txt}',
					ns_talk_hate_point	='{$ns_talk_hate_point}',
					ns_talk_hate		='{$ns_talk_hate}',
					ns_talk_hate_txt	='{$ns_talk_hate_txt}',
					
					ns_lv0_name			='{$ns_lv0_name}',
					ns_lv0_point		='{$ns_lv0_point}',
					ns_lv0_cost			='{$ns_lv0_cost}',
					ns_lv0_txt			='{$ns_lv0_txt}',
					ns_lv0_item			='{$ns_lv0_item}',
					ns_lv0_color		='{$ns_lv0_color}',
					
					ns_lv1_name			='{$ns_lv1_name}',
					ns_lv1_point		='{$ns_lv1_point}',
					ns_lv1_cost			='{$ns_lv1_cost}',
					ns_lv1_txt			='{$ns_lv1_txt}',
					ns_lv1_item			='{$ns_lv1_item}',
					ns_lv1_color		='{$ns_lv1_color}',
					
					ns_lv2_name			='{$ns_lv2_name}',
					ns_lv2_point		='{$ns_lv2_point}',
					ns_lv2_cost			='{$ns_lv2_cost}',
					ns_lv2_txt			='{$ns_lv2_txt}',
					ns_lv2_item			='{$ns_lv2_item}',
					ns_lv2_color		='{$ns_lv2_color}',
					
					ns_lv3_name			='{$ns_lv3_name}',
					ns_lv3_point		='{$ns_lv3_point}',
					ns_lv3_cost			='{$ns_lv3_cost}',
					ns_lv3_txt			='{$ns_lv3_txt}',
					ns_lv3_item			='{$ns_lv3_item}',
					ns_lv3_color		='{$ns_lv3_color}',
					
					ns_lv4_name			='{$ns_lv4_name}',
					ns_lv4_point		='{$ns_lv4_point}',
					ns_lv4_cost			='{$ns_lv4_cost}',
					ns_lv4_txt			='{$ns_lv4_txt}',
					ns_lv4_item			='{$ns_lv4_item}',
					ns_lv4_color		='{$ns_lv4_color}'";

if($w == '') {
	// 삽입
	$sql = " insert into {$g5['npc_table']}
				set ns_id			= '{$ns_id}',
					ns_name			= '{$ns_name}',
					{$sql_common}
					";
	sql_query($sql);
} else {
	// 수정
	$sql = " update {$g5['npc_table']}
				set {$sql_common}
				where ns_id = '{$ns_id}'";
	sql_query($sql);
}

goto_url('./npc_form.php?'.$qstr.'&amp;w=u&amp;ns_id='.$ns_id, false);
?>
