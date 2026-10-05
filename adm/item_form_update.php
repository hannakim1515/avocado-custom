<?php
$sub_menu = "500200";
include_once('./_common.php');

if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();
require_once dirname(__DIR__).'/lib/item_effect.lib.php';
$existing_item = $w === 'u' ? sql_fetch("SELECT * FROM {$g5['item_table']} WHERE it_id=".(int)$it_id) : array();
if ($w === 'u' && !$existing_item) alert('아이템 정보가 존재하지 않습니다.');
// Auxiliary equipment settings must survive edits in the ordinary item form.
foreach (array('it_2','it_3','it_4','it_5') as $extra) {
    $$extra = isset($_POST[$extra]) ? $_POST[$extra] : sql_escape_string($existing_item[$extra] ?? '');
}
if ($it_type === '부활') {
    $rate = filter_var($_POST['it_value'] ?? '', FILTER_VALIDATE_INT);
    if ($rate === false || $rate < 1 || $rate > 100) alert('부활 HP 회복률은 1~100%로 입력해 주세요.');
    $it_value = (string)$rate;
} elseif ($it_type === '상태이상치료') {
    $status_id = filter_var($_POST['recovery_status_id'] ?? '', FILTER_VALIDATE_INT);
    $statuses = item_recovery_statuses();
    if ($status_id === false || $status_id < 1 || !isset($statuses[$status_id])) alert('치료할 상태이상을 선택해 주세요.');
    $it_value = (string)$status_id;
}

$item_data_path = G5_DATA_PATH."/item";
$item_data_url = G5_DATA_URL."/item";

@mkdir($item_data_path, G5_DIR_PERMISSION);
@chmod($item_data_path, G5_DIR_PERMISSION);


$sql_common = "";

if($w == '') { 
	$tmp_row = sql_fetch(" select max(it_id) as max_it_id from {$g5['item_table']}");
	$it_id = $tmp_row['max_it_id'] + 1;
} else { 
	$it_id = trim($it_id);
}

if ($img = $_FILES['it_img_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['it_img_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "item_".$it_id."_img.".$exp;
	upload_file($_FILES['it_img_file']['tmp_name'], $image_name, $item_data_path);
	$it_img = $item_data_url."/".$image_name;
}

if ($img = $_FILES['it_1_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['it_1_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "item_".$it_id."_detail_img.".$exp;
	upload_file($_FILES['it_1_file']['tmp_name'], $image_name, $item_data_path);
	$it_1 = $item_data_url."/".$image_name;
}


$sql_common = " it_use			= '{$it_use}',
				it_category		= '{$it_category}',
				it_type			= '{$it_type}',
				it_value		= '{$it_value}',
				it_name			= '{$it_name}',
				it_img			= '{$it_img}',
				it_content		= '{$it_content}',
				it_content2		= '{$it_content2}',

				it_use_sell		= '{$it_use_sell}',
				it_sell			= '{$it_sell}',
				it_use_ever		= '{$it_use_ever}',

				it_has			= '{$it_has}',
				it_use_able		= '{$it_use_able}',
				it_use_mmb_able	= '{$it_use_mmb_able}',
				it_use_recepi	= '{$it_use_recepi}',

				it_seeker		= '{$it_seeker}',
				it_seeker_per_s =  '{$it_seeker_per_s}',
				it_seeker_per_e =  '{$it_seeker_per_e}',

				st_id	= '{$st_id}',

				it_1	= '{$it_1}',
				it_2	= '{$it_2}',
				it_3	= '{$it_3}',
				it_4	= '{$it_4}',
				it_5	= '{$it_5}'";

/**************************************************************
	S : 던전 기능 추가에 따른 아이템 관련 로직 변경
***************************************************************/
$sql_common .= ", it_use_battle_able = '{$it_use_battle_able}'";
/**************************************************************
	E : 던전 기능 추가에 따른 아이템 관련 로직 변경
***************************************************************/


if($w == '') { 
	$sql = " insert into {$g5['item_table']}
				set it_id = '{$it_id}', {$sql_common}";
	sql_query($sql);
} else {
	$it = sql_fetch("select it_id, it_img from {$g5['item_table']} where it_id = '{$it_id}'");

	if(!$it['it_id']) {
		alert("아이템 정보가 존재하지 않습니다.");
	}
	$sql = " update {$g5['item_table']}
				set {$sql_common}
				where it_id = '{$it['it_id']}'";
	sql_query($sql);

	if($it['it_img'] != $it_img) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $it['it_img']);
		@unlink($prev_file_path);
	}
	if($it['it_1'] != $it_img) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $it['it_1']);
		@unlink($prev_file_path);
	}
}
goto_url('./item_list.php?'.$qstr, false);
?>
