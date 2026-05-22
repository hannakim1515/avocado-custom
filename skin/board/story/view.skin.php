<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
// get_pretty_url 대신 구버전 방식의 경로를 사용합니다.
goto_url("./board.php?bo_table=".$bo_table);
?>