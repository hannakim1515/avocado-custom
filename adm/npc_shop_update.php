<?php
$sub_menu = "400900";
include_once("./_common.php");
if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

// 캐릭터 이름 검색

$npcurl='./npc_list.php';

if($shop_npc != "") { 
	$npc = sql_fetch("select ch_id from {$g5['character_table']} where ch_name = '{$shop_npc}'");
	if(!$npc['ch_id']) {alert("캐릭터 정보를 확인할 수 없습니다.");}
} else {
	$npc['ch_id'] = 0;
}

$sql = " update {$g5['config_table']}
			set cf_shop_npc = '{$npc['ch_id']}'";
sql_query($sql);



goto_url($npcurl, false);
?>
