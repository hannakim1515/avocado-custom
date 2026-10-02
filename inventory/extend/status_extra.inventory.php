<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if($inven_function == "체력회복") {
	// 사망 여부 확인
	$now = is_extra_hp($character['ch_id']);
	if($now == '사망') {
		alert("사망 상태에서는 사용할 수 없습니다.");
	}
	
	// 체력 스탯을 회복시킵니다.
	$claim = inventory_boundary_begin_or_alert(array($in['in_id']), 'inventory.extra_hp');
	set_extra_hp($character['ch_id'], $in['it_value']);
	inventory_boundary_done($claim);

	echo location_url($return_url);
}

if($inven_function == "사망해제") {
	// 사망상태를 해제합니다.
	$now = is_extra_hp($character['ch_id']);
	if($now != '사망') {
		alert("사망 상태인 경우에만 사용 가능합니다.");
	}

	$claim = inventory_boundary_begin_or_alert(array($in['in_id']), 'inventory.extra_revive');
	set_extra_revive($character['ch_id']);

	inventory_boundary_done($claim);
	echo location_url($return_url);
}


?>
