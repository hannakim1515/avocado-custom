<?php
include_once('./_common.php');

$raid=get_k_raid_type($raid_type, $ra_id);
$battle_table = $raid['battle_table'];
$ar_value = $raid['ar_value'];
$ar_title = $raid['ar_title'];
$raid_get = $raid['raid_get'];

$url = ses($_POST, 'url', './980_k_unit_insert.php', 'raw');
$unit_id = ses($_POST, 'unit_id', 0, 'int');
$type = ses($_POST, 'type', '', 'raw');
$act_button = ses($_POST, 'act_button', '', 'raw');
$rm_id = ses($_POST, 'rm_id', array(), 'array');

if (!$is_admin || !$battle_table || !$ra_id) {
    alert('정상적인 방법으로 접근해 주세요.', $url);
} else {
    if ($act_button === '참가등록') {
        $check = sql_fetch("SELECT rm_id FROM {$battle_table}_unit 
                WHERE unit_id = '".sql_escape_string($unit_id)."' 
                AND unit_type = '".sql_escape_string($type)."' 
                AND ra_id = '".sql_escape_string($ra_id)."'");
        if (empty($check['rm_id'])) {
            insert_k_battle_unit($unit_id, $type, $raid_type, $ra_id);
        }
    } elseif ($act_button === '선택삭제') {
        $chk = ses($_POST, 'chk', array(), 'array');
        for ($i = 0; $i < count($chk); $i++) {
            $k = (int)$chk[$i];
            if (isset($rm_id[$k])) {
                delete_k_battle_unit((int)$rm_id[$k]);
            }
        }
    }
}

goto_url($url);
?>