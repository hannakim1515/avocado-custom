<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if($inven_function =='알영양제'){
	sql_query (" UPDATE {$g5['character_table']} set egg_item = '{$in['it_value']}' where ch_id = '{$ch['ch_id']}' ");
	delete_inventory($in['in_id']);
	$msg="알 영양제를 사용했습니다.";
	alert($msg,$return_url);
}

?>