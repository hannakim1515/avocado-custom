<?php
$sub_menu = '920200';
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

$skill_data_path = G5_DATA_PATH."/skill";
$skill_data_url = G5_DATA_URL."/skill";

@mkdir($skill_data_path, G5_DIR_PERMISSION);
@chmod($skill_data_path, G5_DIR_PERMISSION);


auth_check($auth[$sub_menu], 'w');

if ($_POST['act_button'] == "선택수정") {
	
	for ($i=0; $i<count($_POST['chk']); $i++) {
		$sql_common = "";

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$sql_common = "	sk_name			= '{$_POST['sk_name'][$k]}',
						sk_limit		= '{$_POST['sk_limit'][$k]}',
						sk_keep_limit	= '{$_POST['sk_keep_limit'][$k]}'";
		$sql = " update {$g5['skill_table']} set {$sql_common} where sk_id = '{$_POST['sk_id'][$k]}' ";
		sql_query($sql);
	}
} else if ($_POST['act_button'] == "선택삭제") {

	$count = count($_POST['chk']);
	for ($i=0; $i<$count; $i++)
	{
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		// 스킬 정보 삭제
		sql_query("delete from {$g5['skill_table']} where sk_id = '{$_POST['sk_id'][$k]}'");

		// 스킬 레벨 정보 삭제
		sql_query("delete from {$g5['skill_level_table']} where sk_id = '{$_POST['sk_id'][$k]}'");

		// 스킬 보유 정보 삭제
		sql_query("delete from {$g5['skill_has_table']} where sk_id = '{$_POST['sk_id'][$k]}'");

		// 아이템 테이블 스킬 설정값 제거
		sql_query("update {$g5['skill_has_table']} set sk_id = '0' where sk_id = '{$_POST['sk_id'][$k]}'");
	}
}

if ($msg) alert($msg);
goto_url('./skill_list.php?'.$qstr);
?>