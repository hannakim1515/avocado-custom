<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// 파라미터 기본값 처리
$type = ses($_REQUEST, 'type', '');

// ra_id가 없으면 아무 것도 하지 않음
if ($ra_id !== '' && $type !== '') {
    k_raid_reset($ra_id, $type);
}

echo json_encode(true);
exit;
