<?php
include_once('./_common.php');

$return_url = G5_URL."/dungeon/ground.php?ds_id={$ds_id}&list_type={$list_type}";

$in = get_inventory_item($use_item);

// ------------------------------------------------------- 사용 가능 여부 체크 처리
if(!$in['in_id']) {	// 유효성 체크
	alert("아이템 정보를 확인할 수 없습니다.");
}
if($in['it_type'] != "스탯회복") {
	alert("사용할 수 없는 아이템입니다.");
}
// 현재 던전에 있는 캐릭터 정보 가져오기
$dm = get_dungeon_character($ds_id, $character['ch_id']);
$ds = get_dungeon_state($ds_id);

if(!$dm['dm_id']) {	// 참여 정보 체크
	alert("토벌 참여 정보를 확인할 수 없습니다.");
}
if($dm['dm_state'] == 'E') {
	alert("아이템을 사용할 수 없는 상태입니다.");
}
if($ds['ds_state'] == 'E') {	// 참여 정보 체크
	alert("토벌이 종료되었습니다.");
}

switch($in['it_type']) {
	case "스탯회복" :
		$value = $in['it_value'];

		// use 값을 변동시킨다
		$st_id_use = $dm['st_id_'.$in['st_id'].'_use'] - $value;
		if($st_id_use < 0) $st_id_use = 0;

		sql_query("update {$g5['dungeon_member_table']} set st_id_{$in['st_id']}_use = '{$st_id_use}' where dm_id = '{$dm['dm_id']}'");
		$log = "<p class=\'txt-skill-info\'><strong>{$in['it_name']}</strong> 아이템을 사용했습니다.</p>";
		$log .= "<p class=\'txt-skill-result\'>{$in['it_content2']}</p>";
	break;
}


insert_dungeon_log("아이템", $ds, $dm, null, $value, 0, $log);
delete_inventory($in['in_id'], $in['it_use_ever']);

//include(G5_PATH.'/dungeon/proc/inc/_active.cmm.php');

goto_url($return_url);
?>