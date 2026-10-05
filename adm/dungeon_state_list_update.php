<?php
$sub_menu='730200';
include_once './_common.php';
auth_check($auth[$sub_menu],'w');
check_token();
alert('던전 발생 현황의 세션 관리에서 종료·정산해 주세요.','./dungeon_state_list.php');
