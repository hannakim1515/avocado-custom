<?php
include_once('./_common.php');
$expire = preg_replace('/[^0-9]/', '', $_POST['po_expire_term']);

// dm 정보를 가져온 뒤, 유효성을 체크합니다.
$dm = sql_fetch("select * from {$g5['dungeon_member_table']} dm, {$g5['dungeon_table']} dg, {$g5['dungeon_state_table']} ds where dm.dm_id = '{$dm_id}' and dg.dg_id = ds.dg_id and ds.ds_id = dm.ds_id");

/* --------------------------------------------------------
	유효성 체크
-------------------------------------------------------- */

if(!$dm['dm_id']) {
	alert("참여정보를 확인할 수 없습니다.");
}
if($dm['dm_result'] != "") {
	alert("이미 보상을 받았습니다.");
}
if($dm['dm_state'] == "E" && $dm['dm_result'] == "") {
	alert("던전 중 중도 탈진하였으므로 보상을 획득할 수 없습니다.");
}
if($dm['ch_id'] != $character['ch_id']) {
	alert("올바른 방법으로 이용해 주시길 바랍니다.");
}

$return_url = G5_URL."/dungeon/ground.php?ds_id={$dm['ds_id']}";

/* --------------------------------------------------------
	보상 정보 가져오기
-------------------------------------------------------- */

$present_result = "";

if($dm['dg_point'] > 0) {
	// 기본 보상 지급
	$po_content = "『{$dm['dg_title']}』던전 완료";
	insert_point($member['mb_id'], $dm['dg_point'], $po_content, '@passive', $member['mb_id'], $member['mb_id'].'-'.uniqid(''), $expire);
	$present_result .= "<p class=\'point\'><strong>{$dm['dg_point']}{$config['cf_money_pice']}</strong> 획득</p>";
}

$seed = rand(0,100);
$item_result = sql_query("select * from {$g5['dungeon_item_table']} di, {$g5['item_table']} item where di.dg_id = '{$dm['dg_id']}' and item.it_id = di.it_id and (di.di_per_s <= '{$seed}' and di.di_per_e >= '{$seed}')");

for($i = 0; $item = sql_fetch_array($item_result); $i++) {
	insert_inventory($character['ch_id'], $item['it_id'], $item, $item['di_count']);
	$present_result .= "<p class=\'item\'><strong>{$item['it_name']}</strong> {$item['di_count']}개 획득</p>";
}

if($present_result == "") {
	$present_result = "<p class=\'none\'>아무것도 획득하지 못했습니다.</p>";
}

$sql = " update {$g5['dungeon_member_table']}
			set dm_result = '{$present_result}', dm_state = 'E'
		  where dm_id = '{$dm['dm_id']}' ";
sql_query($sql);

goto_url($return_url);
?>