<?php
// iframe 프레임이 아니면 raid.php로 리다이렉트
if (!isset($_GET['in_frame'])) {
    $query = $_SERVER['QUERY_STRING'];
    header('Location: ' . './raid.php?' . $query);
    exit;
}

include_once('./_common.php');
include G5_PATH . '/k_battle/_raid_common.php';

include_once('./_head.sub.php');
include_once('./skin_'.$raid_type.'/raid.inc.php');
include_once('./_tail.sub.php');
?>