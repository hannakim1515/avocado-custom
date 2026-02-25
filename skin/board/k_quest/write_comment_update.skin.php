<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
// $comment_id는 상위 스코프(write_comment_update.php)에서 정의됨
$temp_wr_id = ses($GLOBALS, 'comment_id', 0, 'int');
// $wr 배열 안전 접근
$wr_num = ses($wr, 'wr_num', 0, 'int');
if(!$wr_num && isset($comment['wr_num'])) $wr_num = (int)$comment['wr_num'];

include_once($board_skin_path.'/write_update.inc.php');

if($w != 'cu') { 
	// SQL Injection 방지
	$wr_subject_safe = sql_escape_string($wr_subject);
	$comment_id_safe = (int)$comment_id;
	$sql = " update {$write_table}
				set wr_subject = '{$wr_subject_safe}'
				{$customer_sql}
			  where wr_id = '{$comment_id_safe}' ";
	sql_query($sql);
} else {
	$comment_id_safe = (int)$comment_id;
	$memo_custom_sql_safe = ses($GLOBALS, 'memo_custom_sql', '');
	$sql = " update {$write_table}
				set wr_id = '{$comment_id_safe}'
				{$memo_custom_sql_safe}
			  where wr_id = '{$comment_id_safe}' ";
	sql_query($sql);

}

$wr_id_safe = (int)$wr_id;
$original_write = sql_fetch("select mb_id, wr_subject from {$write_table} where wr_id = '{$wr_id_safe}' ");

if($original_write['mb_id'] == $member['mb_id'] && ($original_write['wr_subject'] == '--|UPLOADING|--' || $original_write['wr_subject'] == '')) { 
	// 콩 상태가 해제가 안되었을 때
	$ch_name_safe = sql_escape_string($character['ch_name']);
	$sql = " update {$write_table}
				set wr_subject = '{$ch_name_safe}', wr_ing = '0', wr_datetime = '".G5_TIME_YMDHIS."'
			  where wr_id = '{$wr_id_safe}' ";
	sql_query($sql);
}


goto_url('./board.php?bo_table='.$bo_table.'&amp;'.$qstr.'&amp;#c_'.$comment_id);
?>
