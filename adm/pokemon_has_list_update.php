<?php
$sub_menu = "091100";
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])&&$_POST['act_button'] != "지급") {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {

	auth_check($auth[$sub_menu], 'w');

	for ($i=0; $i<count($_POST['chk']); $i++) {

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];
		$sql = " update {$g5['pokemon_has_table']}
					set ph_type      = '{$_POST['ph_type'][$k]}'
					,	ph_egg       = '{$_POST['ph_egg'][$k]}'
					,	ph_sex  	 = '{$_POST['ph_sex'][$k]}'
					,	po_pers1     = '{$_POST['po_pers1'][$k]}'
					,	po_pers2  	 = '{$_POST['po_pers2'][$k]}'
					,	po_date      = '{$_POST['po_date'][$k]}'
					,	ma_name  	 = '{$_POST['ma_name'][$k]}'
					,	po_st1         = '{$_POST['po_st1'][$k]}'
					,	po_st2         = '{$_POST['po_st2'][$k]}'
					,	po_st3         = '{$_POST['po_st3'][$k]}'
					,	po_st4         = '{$_POST['po_st4'][$k]}'
					,	po_st5         = '{$_POST['po_st5'][$k]}'
				  where ph_id        = '{$_POST['ph_id'][$k]}' ";
		sql_query($sql);
	}

}elseif ($_POST['act_button'] == "선택삭제") {
	$ids = array();
	if (isset($_POST['chk']) && is_array($_POST['chk'])) {
		foreach ($_POST['chk'] as $idx) {
			if (!isset($_POST['ph_id'][$idx])) continue;
			if ($_POST['ph_type'][$idx]=='partner_buddy') continue;

			$this_po_id = (int)$_POST['ph_id'][$idx];
			if ($this_po_id > 0) {
				$ids[] = $this_po_id;
			}
		}
	}

	if (!empty($ids)) {
		$ids = array_unique($ids);

		$in_list = implode(',', $ids);

		// 관련 레코드 일괄 삭제
		sql_query("DELETE FROM {$g5['pokemon_has_table']} WHERE ph_id IN ({$in_list})");
	}

}elseif ($_POST['act_button'] == "지급") {
	if(!$_POST['ch_id']||!$_POST['po_id']){
		alert("캐릭터와 포켓몬 종류를 확인해 주세요");
	}
	if(!get_character($_POST['ch_id'])['ch_id']||!get_wild_pokemon($po_id)['po_id']){
		alert("캐릭터와 포켓몬 종류를 확인해 주세요");
	}
	if(!$_POST['ph_egg']){$_POST['ph_egg']=0;}
	if(!$_POST['ma_name']){$_POST['ma_name']='어딘가';}
	if(!$_POST['po_date']){$_POST['po_date']='??월 ??일';}
	$sql = " insert into {$g5['pokemon_has_table']}
					set ph_type      = '{$_POST['ph_type']}'
					,	po_id   	 = '{$_POST['po_id']}'
					,	ph_egg       = '{$_POST['ph_egg']}'
					,	ph_sex  	 = '{$_POST['ph_sex']}'
					,	po_pers1     = '{$_POST['po_pers1']}'
					,	po_pers2  	 = '{$_POST['po_pers2']}'
					,	po_date      = '{$_POST['po_date']}'
					,	ma_name  	 = '{$_POST['ma_name']}'
					,	ch_id        = '{$_POST['ch_id']}'";
	sql_query($sql);
}

goto_url('./pokemon_has_list.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
