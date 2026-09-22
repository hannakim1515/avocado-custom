<?php

if($inven_function == "스킬지급(K)") {
    $sk=sql_fetch (" SELECT * from {$g5['k_skill_table']} sk, {$g5['k_skill_info_table']} si where si.si_id=sk.si_id and sk.sk_id='{$in['it_value']}'");
	if($ch['ch_id']&&$sk['sk_id']){
        $check=sql_fetch (" SELECT * from {$g5['k_ch_skill_table']} where sk_id='{$in['it_value']}' and ch_id='{$ch['ch_id']}' ");
        if($check['cs_id']){
			alert('이미 보유한 스킬입니다.', $return_url);
        }else{
            sql_query (" INSERT into {$g5['k_ch_skill_table']} 
                        set cs_name='{$sk['sk_name']}',
                        cs_icon='{$sk['sk_icon']}',
                        cs_content='{$sk['sk_content']}',
                        ch_id = '{$ch['ch_id']}',
                        sk_id = '{$in['it_value']}'
            " );
			if(function_exists('unified_skill_sync_character')) unified_skill_sync_character($ch['ch_id']);
            delete_inventory($in['in_id'], $in['it_use_ever']);
            alert('등록되었습니다.', $return_url);
        }
    }
}

if($inven_function =="스탯증가(K)"){
    $sl = sql_fetch("select st_id, st_max from {$g5['status_config_table']} where st_id = '{$in['st_id']}'");
    $sc = sql_fetch("select sc_id, sc_max from {$g5['status_table']} where ch_id = '{$ch['ch_id']}' and st_id = '{$sl['st_id']}'");
    
    if($sc['sc_max']>=$sl['st_max']){
        alert('이 스테이터스를 더 올릴 수 없습니다!', $return_url);
    }else{
        $sc['sc_max'] = $sc['sc_max'] + $in['it_value']; 

        if($sc['sc_max'] >= $sl['st_max']) { 
            $sc['sc_max'] = $sl['st_max']; 
        } else if($sc['sc_max'] < 0) { 
            $sc['sc_max'] = 0; 
        } 

		$modify_point=$ch['ch_point']+$in['it_value'];
        sql_query(" update {$g5['status_table']} set sc_max = '{$sc['sc_max']}' where sc_id = '{$sc['sc_id']}'"); 
		sql_query(" update {$g5['character_table']} set ch_point='{$modify_point}' where ch_id='{$ch['ch_id']}'"); 
        delete_inventory($in['in_id'], $in['it_use_ever']);
    }
}

if($inven_function == "커스텀장비제작(K)") {
    include('./inc/add_item_form.php');
}
?>
