<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$it = array();
$customer_sql = "";
// $wr_id는 write_update.php에서 이미 정의됨 (insert_id 또는 $_POST)
// SQL 인젝션 방어를 위해 int 캐스팅 (write_update.php에서 이미 안전하지만 이중 방어)
$wr_id_safe = (int)$wr_id;
// $wr_num은 write_update.php에서 이미 정의됨
if(empty($wr_num) && !empty($write['wr_num'])) $wr_num = $write['wr_num'];

if($w == '') { 
	if($board['bo_use_chick']) { 
		// 신규 글 작성이고, 췩이 가능한 게시판일 경우
		// -- wr_ing 값에 1을 입력한다.
		// -- 글 등록이 완료 된 이후에는 wr_ing 값을 2로 돌린다.

		// -- wr_ing : 0 - 췩 불가
		// -- wr_ing : 1 - 췩 가능, 글 작성 중

		$write['wr_ing'] = 1;

		$sql = " update {$write_table}
					set wr_id = '{$wr_id_safe}',
						wr_ing = '1'
				  where wr_id = '{$wr_id_safe}' ";
		sql_query($sql);
		setcookie('MMB_PW', $_POST['wr_password']);
?>
	<!-- 바로 글 수정페이지로 이동 -->
	<html>
		<body>
			<form name="fboardpassword" id="fboardpassword" action="./write.php" method="post">
				<input type="hidden" name="w" value="u">
				<input type="hidden" name="bo_table" value="<?php echo h($bo_table)?>">
				<input type="hidden" name="wr_id" value="<?php echo (int)$wr_id?>">
				<input type="hidden" name="wr_password" value="<?php echo h(ses($_POST, 'wr_password', '', 'raw'))?>">
				<button type="submit"></button>
			</form>
			<script>document.getElementById('fboardpassword').submit();</script>
		</body>
	</html>
<?php 
	exit;
	}
}


if($write['wr_ing'] != '1' || $w == 'u') { 

	// 췩이 아니거나 글이 수정하고 난 뒤일때
	setcookie('MMB_PW', '');
	include_once($board_skin_path.'/write_update.inc.php');

	if($write['wr_ing'] == '1') { 
		// 췩 당하는 중인 글일 경우
		$customer_sql .= " , wr_ing = 0, wr_datetime = '".date('Y-m-d H:i:s')."' ";
		$write['wr_ing'] = 0;
	}

	//$customer_sql .= " , wr_ing = 0 ";
	$sql = " update {$write_table}
				set wr_id = '{$wr_id_safe}'
				{$customer_sql}
			  where wr_id = '{$wr_id_safe}' ";
	sql_query($sql);
}

if($write['wr_ing'] == '1') { 
	$sql = " update {$write_table}
				set wr_ing = '0'
			  where wr_id = '{$wr_id_safe}' ";
	sql_query($sql);
}

if ($file_upload_msg) {
	alert($file_upload_msg, G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table);
} else {
	goto_url(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table.$qstr."#log_".$wr_id_safe);
}
?>
