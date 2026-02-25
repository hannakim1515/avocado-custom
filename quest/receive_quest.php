<?php
include_once("./_common.php");

$qu_id = ses($_GET, 'qu_id', 0, 'int');

if (!$qu_id) {
    alert("잘못된 접근입니다.", G5_URL . '/quest');
}

$result = insert_quest($qu_id, $character['ch_id']);

alert($result, G5_URL . '/quest');
?>