<?php
error_reporting( E_ALL );
ini_set( "display_errors", 1 );

include_once('./_common.php');

$return_url = G5_URL."/dungeon/ground.php?ds_id={$ds_id}&list_type={$list_type}";

$sh = get_skill_has_by_has_id($character['ch_id'], $sh_id);

// ------------------------------------------------------- 사용 가능 여부 체크 처리
if(!$sh['sh_id']) {	// 유효성 체크
	alert("올바른 방법으로 사용해 주시길 바랍니다.");
}
if($sh['sh_use'] != 1) {	// 장착 여부 체크
	alert("장착중인 스킬이 아닙니다.");
}
if($sh['sh_limit'] > 0) {	// 사용 가능 턴수 체크
	alert(" 사용가능까지 {$sh['sh_limit']}턴이 남았습니다.");
}

// 현재 던전에 있는 캐릭터 정보 가져오기
$dm = get_dungeon_character($ds_id, $character['ch_id']);
// 던전 정보
$ds = get_dungeon_state($ds_id);

if(!$dm['dm_id']) {	// 참여 정보 체크
	alert("토벌 참여 정보를 확인할 수 없습니다.");
}
if($ds['ds_state'] == 'E') {	// 참여 정보 체크
	alert("토벌이 종료되었습니다.");
}
if($sh['sk_use_st_id']) {	// 소모자원 체크
	$skill_use_state = $sh['sl_use_value'];
	$ori_use_sk = sql_fetch("select * from {$g5['status_config_table']} where st_id = '{$sh['sk_use_st_id']}'");
	if($ori_use_sk['st_use_max']) {
		// 전체 스탯 소모시키기 : 캐릭터 오리지널 스탯 수치도 소모시킨다.
		$now_use_state = $dm['st_id_'.$sh['sk_use_st_id']] + $dm['st_id_'.$sh['sk_use_st_id'].'_mod'];
	} else {
		// use 값을 변동시킨다
		$max_use_state = $dm['st_id_'.$sh['sk_use_st_id']] + $dm['st_id_'.$sh['sk_use_st_id'].'_mod'];
		$now_use_state = $max_use_state - $dm['st_id_'.$sh['sk_use_st_id'].'_use'];
	}

	if($now_use_state < $skill_use_state) {
		alert("스킬 사용에 필요한 자원이 부족합니다.");
	} else {
		// 스킬 자원 소모 처리
		if($ori_use_sk['st_use_max']) {
			// 전체 스탯 소모시키기
			$st_id_mod = $dm['st_id_'.$sh['sk_use_st_id']] - $skill_use_state;
			sql_query("update {$g5['dungeon_member_table']} set st_id_{$sh['sk_use_st_id']} = '{$st_id_mod}' where dm_id = '{$dm['dm_id']}'");

			// 캐릭터 오리지널 스탯 수치를 소모시킬 경우 아래의 코드를 사용
			//set_status_max($dm['ch_id'], $sh['sk_use_st_id'], $skill_use_state);
		} else {
			// use 값을 변동시킨다
			$st_id_mod = $dm['st_id_'.$sh['sk_use_st_id'].'_use'] + $skill_use_state;
			sql_query("update {$g5['dungeon_member_table']} set st_id_{$sh['sk_use_st_id']}_use = '{$st_id_mod}' where dm_id = '{$dm['dm_id']}'");
		}
	}
}
// ------------------------------------------------------- 사용 가능 여부 체크 처리 종료


// 스킬 기능 별 효과 처리
switch($sh['sk_function']) {
	case "공격" : 
		include(G5_PATH.'/dungeon/proc/inc/attack.php');
	break;
	case "도발" : 
		include(G5_PATH.'/dungeon/proc/inc/aggro.php');
	break;
	case "회피" : 
		include(G5_PATH.'/dungeon/proc/inc/evasion.php');
	break;
	case "스탯강화" : 
		include(G5_PATH.'/dungeon/proc/inc/enforce_state.php');
	break;
	case "연동코드강화" : 
		include(G5_PATH.'/dungeon/proc/inc/enforce_code.php');
	break;
	case "스탯회복" : 
		include(G5_PATH.'/dungeon/proc/inc/recovery_state.php');
	break;
}

// 스킬 사용 처리 (턴수 처리)
sql_query("update {$g5['skill_has_table']} set sh_limit = '{$sh['sk_limit']}' where sh_id = '{$sh['sh_id']}'");

include(G5_PATH.'/dungeon/proc/inc/_active.cmm.php');

goto_url($return_url);
?>