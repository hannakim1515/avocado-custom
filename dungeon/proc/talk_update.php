<?php
include_once('./_common.php');

$return_url = G5_URL."/dungeon/ground.php?ds_id={$ds_id}&list_type={$list_type}";

// 현재 던전에 있는 캐릭터 정보 가져오기
$dm = get_dungeon_character($ds_id, $character['ch_id']);
// 던전 정보
$ds = get_dungeon_state($ds_id);

if(!$dm['dm_id']) {	// 참여 정보 체크
	alert("던전 참여 정보를 확인할 수 없습니다.");
}

insert_dungeon_log("대화", $ds, $dm, null, 0, 0, $talk_comment);

goto_url($return_url);
?>