<?php
$sub_menu = "091002";
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])&&$_POST['act_button'] != "등록") {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {

	auth_check($auth[$sub_menu], 'w');

	for ($i=0; $i<count($_POST['chk']); $i++) {

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];
		$sql = " update {$g5['pokemon_map_table']}
					set ma_img         = '{$_POST['ma_img'][$k]}',
						ma_img_action  = '{$_POST['ma_img_action'][$k]}',
						ma_content     = '{$_POST['ma_content'][$k]}',
						ma_use         = '{$_POST['ma_use'][$k]}'
				  where ma_id          = '{$_POST['ma_id'][$k]}' ";
		sql_query($sql);
	}

}
goto_url('./pokemon_command.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
