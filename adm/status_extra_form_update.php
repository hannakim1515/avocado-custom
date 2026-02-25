<?php
$sub_menu = "910100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

if($add_type == 'hp') { 
	$sql = " update {$g5['status_config_table']}
				set st_use_hp = '0'";
	sql_query($sql);
	$sql = " update {$g5['status_config_table']}
				set st_use_hp = '1'
				where st_id = '{$_POST['st_id']}'";
	sql_query($sql);
} else {

	$sql = " insert into {$g5['status_extra_table']}
				set ex_name = '{$_POST['ex_name']}',
					ex_main_min = '{$_POST['ex_main_min']}',
					ex_main_max = '{$_POST['ex_main_max']}',
					ex_is_main_status = '{$_POST['ex_is_main_status']}',
					ex_main_status_type = '{$_POST['ex_main_status_type']}',
					ex_main_status_per = '{$_POST['ex_main_status_per']}',
					ex_cri = '{$_POST['ex_cri']}',
					ex_is_cri_status = '{$_POST['ex_is_cri_status']}',
					ex_cri_status_type = '{$_POST['ex_cri_status_type']}',
					ex_cri_status_per = '{$_POST['ex_cri_status_per']}',
					ex_cri_add_per = '{$_POST['ex_cri_add_per']}',
					ex_is_cri_add_status = '{$_POST['ex_is_cri_add_status']}',
					ex_cri_add_status_type = '{$_POST['ex_cri_add_status_type']}',
					ex_cri_add_status_per = '{$_POST['ex_cri_add_status_per']}',
					ex_all_per = '{$_POST['ex_all_per']}'
			";
	sql_query($sql);
}

goto_url('./status_extra_list.php?'.$qstr);
?>
