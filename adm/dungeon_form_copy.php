<?php
$sub_menu = '730100';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');

$dg = sql_fetch("select * from {$g5['dungeon_table']} where dg_id = '{$dg_id}'");
if(!$dg['dg_id']) {
	alert("던전 정보가 존재하지 않습니다.");
}


$tmp_row = sql_fetch("select max(dg_id) as max_dg_id from {$g5['dungeon_table']}");
$new_dg_id = $tmp_row['max_dg_id'] + 1;

$sql_common = " dg_use			= '{$dg['dg_use']}',
				dg_title		= '{$dg['dg_title']}',
				dg_count		= '{$dg['dg_count']}',
				ma_id			= '{$dg['ma_id']}',
				dg_rank			= '{$dg['dg_rank']}',
				dg_per_s		= '{$dg['dg_per_s']}',
				dg_per_e		= '{$dg['dg_per_e']}',
				dg_point		= '{$dg['dg_point']}',

				dg_status		= '{$dg['dg_status']}',
				dg_status_type	= '{$dg['dg_status_type']}',
				dg_status_value	= '{$dg['dg_status_value']}',

				dg_mon_name		= '{$dg['dg_mon_name']}',
				dg_mon_img		= '{$dg['dg_mon_img']}',
				dg_mon_hp		= '{$dg['dg_mon_hp']}',
				dg_mon_descript	= '{$dg['dg_mon_descript']}',
				dg_defence		= '{$dg['dg_defence']}',

				dg_strong_code		= '{$dg['dg_strong_code']}',
				dg_strong_value		= '{$dg['dg_strong_value']}',
				dg_weak_code		= '{$dg['dg_weak_code']}',
				dg_weak_value		= '{$dg['dg_weak_value']}',

				dg_weak_effect_count		= '{$dg['dg_weak_effect_count']}',
				dg_weak_effect_turn		= '{$dg['dg_weak_effect_turn']}',

				dg_d_attack_min		= '{$dg['dg_d_attack_min']}',
				dg_d_attack_max		= '{$dg['dg_d_attack_max']}',
				dg_d_attack_turn	= '{$dg['dg_d_attack_turn']}',
				dg_d_attack_count	= '{$dg['dg_d_attack_count']}',
				dg_d_attack_comment	= '{$dg['dg_d_attack_comment']}',

				dg_w_attack_min		= '{$dg['dg_w_attack_min']}',
				dg_w_attack_max		= '{$dg['dg_w_attack_max']}',
				dg_w_attack_turn	= '{$dg['dg_w_attack_turn']}',
				dg_w_attack_comment	= '{$dg['dg_w_attack_comment']}',

				dg_s1_attack_turn		= '{$dg['dg_s1_attack_turn']}',
				dg_s1_attack_st_id		= '{$dg['dg_s1_attack_st_id']}',
				dg_s1_attack_st_value	= '{$dg['dg_s1_attack_st_value']}',
				dg_s1_attack_type		= '{$dg['dg_s1_attack_type']}',
				dg_s1_attack_min		= '{$dg['dg_s1_attack_min']}',
				dg_s1_attack_max		= '{$dg['dg_s1_attack_max']}',
				dg_s1_attack_comment	= '{$dg['dg_s1_attack_comment']}',
				
				dg_s2_attack_turn		= '{$dg['dg_s2_attack_turn']}',
				dg_s2_attack_st_id		= '{$dg['dg_s2_attack_st_id']}',
				dg_s2_attack_st_value	= '{$dg['dg_s2_attack_st_value']}',
				dg_s2_attack_type		= '{$dg['dg_s2_attack_type']}',
				dg_s2_attack_min		= '{$dg['dg_s2_attack_min']}',
				dg_s2_attack_max		= '{$dg['dg_s2_attack_max']}',
				dg_s2_attack_comment	= '{$dg['dg_s2_attack_comment']}'";


$sql = " insert into {$g5['dungeon_table']}
			set dg_id = '{$new_dg_id}', {$sql_common}";
sql_query($sql);


$item_result = sql_query("select * from {$g5['dungeon_item_table']} di, {$g5['item_table']} item where di.dg_id = '{$dg_id}' and di.it_id = item.it_id");

for($row=0; $row = sql_fetch_array($item_result); $i++) {
	$sql = " insert into {$g5['dungeon_item_table']}
				set dg_id = '{$new_dg_id}',
					it_id = '{$row['it_id']}',
					di_count = '{$row['di_count']}',
					di_per_s = '{$row['di_per_s']}',
					di_per_e = '{$row['di_per_e']}'
				";
	sql_query($sql);
}
alert("복사에 성공 했습니다.", './dungeon_list.php?chk=1&amp;'.$qstr);
?>