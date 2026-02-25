<?php

if($type=='stat'){
	$sub_menu = "980110";
}elseif($type=='battle'){
	$sub_menu = "980120";
}

include_once('./_common.php');

check_token();


if($act_button=='업데이트'){
	sql_query (" UPDATE {$g5['k_battle_config']} 
				set hp='{$hp}', 
					hp_name='{$hp_name}', 
					mp='{$mp}', 
					mp_name='{$mp_name}', 
					speed='{$speed}'
				");
}elseif($act_button=='등록') {

	$sc_value=implode('|',$value_normal);
	$sc_type=implode('|',$type_normal);
	
	sql_query (" INSERT into {$g5['k_stat_table']} 
				set sc_name='{$sc_name}',
				sc_value='{$sc_value}',
				sc_type='{$sc_type}',
				sc_1='{$round}',
				sc_2='{$min_value}',
				sc_3='{$max_value}',
				sc_4='{$use_cri}',
				sc_5='{$rand}',
				sc_category='{$category}'" );
				
}elseif($act_button=='선택삭제'){
	for ($i=0; $i<count($_POST['chk']); $i++)
	{
		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

			$sql = " DELETE from {$g5['k_stat_table']} 
						where sc_id = '{$_POST['sc_id'][$k]}' ";

		sql_query($sql);

	}
}elseif($act_button=='샘플등록'){
	$unit=implode("|",$sample_unit);
	if($sample_target[0]){
		$target=implode("|",$sample_target);
		$target_sql=", sample_target = '{$target}'";
	}
	$sql=" UPDATE {$g5['k_battle_config']} 
		set sample_unit='{$unit}'
		{$target_sql}";
	sql_query($sql);
}

if($category=='stat'){
	$stat_list="";
	$st_list = sql_query("select sc_id from {$g5['k_stat_table']} where sc_category='stat' ");
	for($i = 0; $row = sql_fetch_array($st_list); $i++) {
		$stat_list .= "|{$row['sc_id']}";
	};
	sql_query (" UPDATE {$g5['k_battle_config']} set stat_list='{$stat_list}'");
}

goto_url('./980_k_stat_func.php?category='.$category);
?>
