<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if($inven_function == "체력회복") {
	// 사망 여부 확인
	$now = is_extra_hp($character['ch_id']);
	if($now == '사망') {
		alert("사망 상태에서는 사용할 수 없습니다.");
	}
	
	// 체력 스탯을 회복시킵니다.
	set_extra_hp($character['ch_id'], $in['it_value']);
	delete_inventory($in['in_id'], $in['it_use_ever']);

	echo location_url($return_url);
}

if($inven_function == "사망해제") {
	// 사망상태를 해제합니다.
	$now = is_extra_hp($character['ch_id']);
	if($now != '사망') {
		alert("사망 상태인 경우에만 사용 가능합니다.");
	}

	set_extra_revive($character['ch_id']);

	delete_inventory($in['in_id'], $in['it_use_ever']);
	echo location_url($return_url);
}


?>