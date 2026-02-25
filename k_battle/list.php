<?php
include_once('./_common.php');
include G5_PATH . '/k_battle/_raid_common.php';
if($raid_type=='mmbraid'){$skin_type='default';}else{$skin_type=$raid_type;}
include_once('./_head.sub.php');
include_once('./skin_'.$skin_type.'/raid_list.inc.php');
include_once('./_tail.sub.php');
?>