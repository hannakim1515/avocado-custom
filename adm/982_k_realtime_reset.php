<?php
$sub_menu = "981100";
include_once './_common.php';

$raid=get_k_raid_type('realtime', $ra_id);
$battle_table = $raid['battle_table'];
$ar_value = $raid['ar_value'];
$ar_title = $raid['ar_title'];
$raid_get = $raid['raid_get'];

if ($is_admin !== 'super') {
    alert('관리자만 접근 가능합니다.');
}

$ra_id = ses($_REQUEST, 'ra_id', '', 'string');
$type = ses($_REQUEST, 'type', 'all', 'string');

if ($ra_id === '') {
    alert('레이드 ID가 없습니다.', './982_k_realtime_list.php');
}

// 레이드 존재 확인
$raid = sql_fetch("SELECT ra_id, ra_title FROM {$g5['k_realtime_table']} WHERE ra_id = '{$ra_id}'");
if (empty($raid['ra_id'])) {
    alert('존재하지 않는 레이드입니다.', './982_k_realtime_list.php');
}


k_raid_reset($ra_id, $type);

$type_labels = array(
    'log' => '로그',
    'buff' => '버프',
    'all' => '전체'
);
$type_label = ses($type_labels, $type, $type);

alert("레이드 [{$raid['ra_title']}]의 {$type_label} 초기화가 완료되었습니다.", './982_k_realtime_list.php?' . $qstr);
