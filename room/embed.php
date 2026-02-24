<?php
include_once('./_common.php');
$g5['title'] = "마이룸";
include_once('./_head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/room/css/index.css">', 0);

// 해당 페이지는 룸이 iframe 형태로 삽입될 경우 사용됩니다.
include_once('./room.inc.php');

include_once('./_tail.sub.php');
?>