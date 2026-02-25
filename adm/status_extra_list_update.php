<?php
$sub_menu = '910100';
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

auth_check($auth[$sub_menu], 'w');

echo "!!!";

if ($_POST['act_button'] == "선택수정") {
	
	for ($i=0; $i<count($_POST['chk']); $i++) {
		$sql_common = "";

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$sql = " update {$g5['status_extra_table']}
					set ex_name = '{$_POST['ex_name'][$k]}',
						ex_main_min = '{$_POST['ex_main_min'][$k]}',
						ex_main_max = '{$_POST['ex_main_max'][$k]}',
						ex_is_main_status = '{$_POST['ex_is_main_status'][$k]}',
						ex_main_status_type = '{$_POST['ex_main_status_type'][$k]}',
						ex_main_status_per = '{$_POST['ex_main_status_per'][$k]}',
						ex_cri = '{$_POST['ex_cri'][$k]}',
						ex_is_cri_status = '{$_POST['ex_is_cri_status'][$k]}',
						ex_cri_status_type = '{$_POST['ex_cri_status_type'][$k]}',
						ex_cri_status_per = '{$_POST['ex_cri_status_per'][$k]}',
						ex_cri_add_per = '{$_POST['ex_cri_add_per'][$k]}',
						ex_is_cri_add_status = '{$_POST['ex_is_cri_add_status'][$k]}',
						ex_cri_add_status_type = '{$_POST['ex_cri_add_status_type'][$k]}',
						ex_cri_add_status_per = '{$_POST['ex_cri_add_status_per'][$k]}',
						ex_all_per = '{$_POST['ex_all_per'][$k]}'
				";
		
		$sql .= "   where ex_id = '{$_POST['ex_id'][$k]}' ";


		sql_query($sql);
	}
} else if ($_POST['act_button'] == "선택삭제") {

	$count = count($_POST['chk']);
	for ($i=0; $i<$count; $i++)
	{
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		// 연동코드 삭제
		$sql = " delete from {$g5['status_extra_table']} where ex_id = '{$_POST['ex_id'][$k]}' ";
		sql_query($sql);
	}
}

if ($msg) alert($msg);
goto_url('./status_extra_list.php?'.$qstr);
?>