<?php
require_once './_common.php';

$action        = ses($_REQUEST, 'action', '', 'raw');
$return_url    = ses($_REQUEST, 'return_url', '', 'raw');

$ch_id = ses($character, 'ch_id', 0, 'int');
$tbl_ok = $battle_table !== '' && preg_match('/^\w+$/', $battle_table);

// 메시지 기본
$msg = '정상적인 방법으로 접근해 주세요.';

if ($ch_id > 0 && $tbl_ok) {

    $rid = sql_escape_string($ra_id);

    // 현재 등록 여부 확인
    $check = sql_fetch("
        SELECT rm_id
        FROM {$battle_table}_unit
        WHERE unit_id = '{$ch_id}'
          AND unit_type = 'ch'
          AND ra_id = '{$rid}'
        LIMIT 1
    ");

    if ($action === 'in') {
        if (empty($check['rm_id'])) {
            insert_k_battle_unit($ch_id, 'ch', $raid_type, $ra_id);
            $msg = '참가등록이 완료되었습니다.';
        } else {
            $msg = '이미 참가등록이 되어 있습니다.';
        }
    } elseif ($action === 'out') {
        if (empty($check['rm_id'])) {
            $msg = '참가등록이 되어 있지 않습니다.';
        } else {
            delete_k_battle_unit((int)$check['rm_id']);
            $msg = '참가등록을 취소했습니다.';
        }
    }
}

alert($msg, $return_url);
