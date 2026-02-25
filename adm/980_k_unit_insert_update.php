<?
include_once('./_common.php');

$raid=get_k_raid_type($raid_type, $ra_id);
$battle_table = $raid['battle_table'];
$ar_value = $raid['ar_value'];
$ar_title = $raid['ar_title'];
$raid_get = $raid['raid_get'];

if(!$is_admin||!$battle_table){
    alert('정상적인 방법으로 접근해 주세요.',$url);
}else{
	if($act_button=='참가등록'){
		$check=sql_fetch (" SELECT rm_id from {$battle_table}_unit 
				where unit_id='{$unit_id}' 
				and unit_type='{$type}' 
				and ra_id='{$ra_id}'");
		if($check['rm_id']){
			insert_k_battle_unit($unit_id,$type,$raid_type,$ra_id);
		}
	}elseif($act_button=='선택삭제'){
		for ($i=0; $i<count($_POST['chk']); $i++)
        {
            // 실제 번호를 넘김
            $k = $_POST['chk'][$i];
			delete_k_battle_unit($rm_id[$k]);
        }
	}
}

goto_url($url);
?>