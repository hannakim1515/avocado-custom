<?php
include_once('./_common.php');
$g5['title'] = "마이룸";
include_once('./_head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/room/css/index.css">', 0);

// 해당 페이지는 룸이 전체 페이지로 작성 될 때 사용되는 파일입니다. (헤더  / 푸터 포함)
include_once('./room.inc.php');



include_once('./_tail.php');
?>