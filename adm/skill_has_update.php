<?php
$sub_menu = "720200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

if(!$sk_id && $sk_name) {
	$sk = sql_fetch("select sk_id from {$g5['skill_table']} where sk_name = '{$sk_name}'");
	if(!$sk['sk_id']) {
		alert("해당 스킬의 정보가 없습니다.");
	}
	$sk_id = $sk['sk_id'];
} else {
	$sk = sql_fetch("select sk_id from {$g5['skill_table']} where sk_id = '{$sk_id}'");
}

if(!$sk['sk_id']) { 
	alert("등록된 자료가 없습니다.");
}

$sl = array();
$skill_level_result = sql_query("select sl_name from {$g5['skill_level_table']} where sk_id = '{$sk_id}' order by sl_level asc");
for($k=0; $skill_level = sql_fetch_array($skill_level_result); $k++) {
	$sl[] = $skill_level['sl_name'];
}
if(array_search($sh_level, $sl) == false) {
	// 목록 내에 레벨 정보가 없다면
	$sh_level = $sl[0];
}

// 레벨 idx 값 처리
$chk_id = sql_fetch("select sl_level from {$g5['skill_level_table']} where sk_id = '{$sk_id}' and sl_name = '{$sh_level}'");
$sh_level = $chk_id['sl_level'];


if($take_type == 'A') {
	// 전체지급
	$sql_common = " from {$g5['character_table']} ";
	$sql_search = " where ch_state = '승인' ";
	$sql = " select ch_id {$sql_common} {$sql_search} ";
	$result = sql_query($sql);

	for($i=0; $ch = sql_fetch_array($result); $i++) { 
		add_skill_inven($ch['ch_id'], $sk['sk_id'], $sh_level);
	}

} else {
	// 개별지급
	if(!$ch_id && $ch_name) {
		$ch = sql_fetch("select ch_id, ch_name from {$g5['character_table']} where ch_name = '{$ch_name}'");
		$ch_id = $ch['ch_id'];
	} else {
		$ch = sql_fetch("select ch_id, ch_name from {$g5['character_table']} where ch_id = '{$ch_id}'");
	}

	if (!$ch['ch_id'])
		alert('존재하는 캐릭터가 아닙니다.');

	add_skill_inven($ch['ch_id'], $sk['sk_id'], $sh_level);
}


goto_url('./skill_has_list.php?'.$qstr);
?>
