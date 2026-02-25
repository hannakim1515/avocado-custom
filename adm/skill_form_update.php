<?php
$sub_menu = "720100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

$skill_data_path = G5_DATA_PATH."/skill";
$skill_data_url = G5_DATA_URL."/skill";

@mkdir($skill_data_path, G5_DIR_PERMISSION);
@chmod($skill_data_path, G5_DIR_PERMISSION);

if($w == '') { 
	$tmp_row = sql_fetch(" select max(sk_id) as max_sk_id from {$g5['skill_table']}");
	$sk_id = $tmp_row['max_sk_id'] + 1;
} else { 
	$sk_id = trim($sk_id);
}

if ($img = $_FILES['sk_img_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['sk_img_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "skill_".$sk_id."_img.".$exp;
	upload_file($_FILES['sk_img_file']['tmp_name'], $image_name, $skill_data_path);
	$sk_img = $skill_data_url."/".$image_name;
}

$sql_common = " sk_cate			= '{$sk_cate}',
				sk_type			= '{$sk_type}',
				sk_name			= '{$sk_name}',
				sk_img			= '{$sk_img}',
				sk_status_code	= '{$sk_status_code}',
				sk_limit		= '{$sk_limit}',
				sk_keep_limit	= '{$sk_keep_limit}',
				sk_function		= '{$sk_function}',
				sk_value_type	= '{$sk_value_type}',
				sk_target		= '{$sk_target}',
				sk_descript		= '{$sk_descript}',
				sk_mod_st_id	= '{$sk_mod_st_id}',
				sk_mod_code		= '{$sk_mod_code}',
				sk_mod_enermy	= '{$sk_mod_enermy}',
				sk_mod_type		= '{$sk_mod_type}',
				sk_def_type		= '{$sk_def_type}',
				sk_def_code		= '{$sk_def_code}',
				sk_def_enermy	= '{$sk_def_enermy}',
				sk_use_single	= '{$sk_use_single}',
				sk_use_st_id	= '{$sk_use_st_id}'";

if($w == '') {
	$sql = " insert into {$g5['skill_table']} set {$sql_common}";
	sql_query($sql);
	$sk_id = sql_insert_id();
} else {
	// 기존 스킬 데이터 호출
	$skill = sql_fetch("select * from {$g5['skill_table']} where sk_id = '{$sk_id}'");
	if (!$skill['sk_id'])
		alert('존재하지 않는 스킬 정보 입니다.');

	if($skill['sk_img'] != $sk_img) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $skill['sk_img']);
		@unlink($prev_file_path);
	}

	$sql = " update {$g5['skill_table']}
				set {$sql_common}
				where sk_id = '{$sk_id}'";
	sql_query($sql);
}

sql_query(" delete from {$g5['skill_level_table']} where sk_id = '{$sk_id}' ");
if(is_array($sl_level)) { 
	for($i=0; $i < count($sl_level); $i++) {
		if($sl_name[$i] == '') {continue;}
		$sql = " insert into {$g5['skill_level_table']}
				set sk_id			= '{$sk_id}',
					sl_level		= '{$sl_level[$i]}',
					sl_name			= '{$sl_name[$i]}',
					sl_set_value	= '{$sl_set_value[$i]}',
					sl_use_value	= '{$sl_use_value[$i]}'";
		sql_query($sql);
	}

	$level = sql_query("select sl_id from {$g5['skill_level_table']} where sk_id = '{$sk_id}' order by sl_level asc");
	for($i=0; $sl = sql_fetch_array($level); $i++) {
		sql_query("update {$g5['skill_level_table']} set sl_level = '".($i+1)."' where sl_id = '{$sl['sl_id']}'");
	}
}



goto_url('./skill_form.php?w=u&sk_id='.$sk_id."&".$qstr);
?>
