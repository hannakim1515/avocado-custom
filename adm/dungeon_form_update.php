<?php
$sub_menu = "730100";
include_once('./_common.php');

if ($w == 'u') check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

$mon_data_path = G5_DATA_PATH."/dungeon";
$mon_data_url = G5_DATA_URL."/dungeon";

@mkdir($mon_data_path, G5_DIR_PERMISSION);
@chmod($mon_data_path, G5_DIR_PERMISSION);


$sql_common = "";

if($w == '') { 
	$tmp_row = sql_fetch("select max(dg_id) as max_dg_id from {$g5['dungeon_table']}");
	$dg_id = $tmp_row['max_dg_id'] + 1;
} else { 
	$dg_id = trim($dg_id);
}

if ($img = $_FILES['dg_mon_img_file']['name']) {
	// 확장자 따기
	$exp = explode(".", $_FILES['dg_mon_img_file']['name']);
	$exp = $exp[count($exp)-1];

	$image_name = "mon_".$dg_id."_img.".$exp;
	upload_file($_FILES['dg_mon_img_file']['tmp_name'], $image_name, $mon_data_path);
	$dg_mon_img = $mon_data_url."/".$image_name;
}

$ma_ids = "||".implode("||",$ma_ids)."||";
$sql_common = " dg_use			= '{$dg_use}',
				dg_title		= '{$dg_title}',
				dg_count		= '{$dg_count}',
				ma_id			= '{$ma_id}',
				ma_ids			= '{$ma_ids}',
				dg_rank			= '{$dg_rank}',
				dg_per_s		= '{$dg_per_s}',
				dg_per_e		= '{$dg_per_e}',
				dg_point		= '{$dg_point}',

				dg_status		= '{$dg_status}',
				dg_status_type	= '{$dg_status_type}',
				dg_status_value	= '{$dg_status_value}',

				dg_mon_name		= '{$dg_mon_name}',
				dg_mon_img		= '{$dg_mon_img}',
				dg_mon_hp		= '{$dg_mon_hp}',
				dg_mon_descript= '{$dg_mon_descript}',
				dg_defence		= '{$dg_defence}',

				dg_strong_code		= '{$dg_strong_code}',
				dg_strong_value		= '{$dg_strong_value}',
				dg_weak_code		= '{$dg_weak_code}',
				dg_weak_value		= '{$dg_weak_value}',

				dg_weak_effect_count	= '{$dg_weak_effect_count}',
				dg_weak_effect_turn		= '{$dg_weak_effect_turn}',

				dg_d_attack_min		= '{$dg_d_attack_min}',
				dg_d_attack_max		= '{$dg_d_attack_max}',
				dg_d_attack_turn	= '{$dg_d_attack_turn}',
				dg_d_attack_count	= '{$dg_d_attack_count}',
				dg_d_attack_comment	= '{$dg_d_attack_comment}',

				dg_w_attack_min		= '{$dg_w_attack_min}',
				dg_w_attack_max		= '{$dg_w_attack_max}',
				dg_w_attack_turn	= '{$dg_w_attack_turn}',
				dg_w_attack_comment	= '{$dg_w_attack_comment}',

				dg_s1_attack_turn		= '{$dg_s1_attack_turn}',
				dg_s1_attack_st_id		= '{$dg_s1_attack_st_id}',
				dg_s1_attack_st_value	= '{$dg_s1_attack_st_value}',
				dg_s1_attack_type		= '{$dg_s1_attack_type}',
				dg_s1_attack_min		= '{$dg_s1_attack_min}',
				dg_s1_attack_max		= '{$dg_s1_attack_max}',
				dg_s1_attack_comment	= '{$dg_s1_attack_comment}',
				
				dg_s2_attack_turn		= '{$dg_s2_attack_turn}',
				dg_s2_attack_st_id		= '{$dg_s2_attack_st_id}',
				dg_s2_attack_st_value	= '{$dg_s2_attack_st_value}',
				dg_s2_attack_type		= '{$dg_s2_attack_type}',
				dg_s2_attack_min		= '{$dg_s2_attack_min}',
				dg_s2_attack_max		= '{$dg_s2_attack_max}',
				dg_s2_attack_comment	= '{$dg_s2_attack_comment}'";


if($w == '') { 
	$sql = " insert into {$g5['dungeon_table']}
				set dg_id = '{$dg_id}', {$sql_common}";
	sql_query($sql);
} else {
	$dg = sql_fetch("select dg_id, dg_mon_img from {$g5['dungeon_table']} where dg_id = '{$dg_id}'");

	if(!$dg['dg_id']) {
		alert("던전 정보가 존재하지 않습니다.");
	}
	$sql = " update {$g5['dungeon_table']}
				set {$sql_common}
				where dg_id = '{$dg['dg_id']}'";
	sql_query($sql);

	if($dg['dg_mon_img'] != $dg_mon_img) { 
		// 해당 서버에 업로드 한 파일일 경우
		$prev_file_path = str_replace(G5_URL, G5_PATH, $dg['dg_mon_img']);
		@unlink($prev_file_path);
	}
}

// 보상 아이템 저장 부분
sql_query("DELETE from {$g5['dungeon_item_table']} where dg_id = '{$dg_id}'");

for($i=0; $i < count($it_name); $i++) {
	if($it_name[$i]) { 
		$it_id = sql_fetch("select it_id from {$g5['item_table']} where it_name = '{$it_name[$i]}'");
		$it_id = $it_id['it_id'];

		if($it_id) { 
			$sql = " insert into {$g5['dungeon_item_table']}
						set dg_id = '{$dg_id}',
							it_id = '{$it_id}',
							di_count = '{$di_count[$i]}',
							di_per_s = '{$di_per_s[$i]}',
							di_per_e = '{$di_per_e[$i]}'
						";
			sql_query($sql);
		}
	}
}





goto_url('./dungeon_form.php?w=u&dg_id='.$dg_id.'&'.$qstr, false);
?>
