<?php
$sub_menu = "091100";
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])&&$_POST['act_button'] != "등록") {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {

	auth_check($auth[$sub_menu], 'w');

	for ($i=0; $i<count($_POST['chk']); $i++) {

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];
		$this_ma_id=implode(",",$_POST['ma_id'][$k]);
		$sql = " update {$g5['pokemon_table']}
					set ma_id         = '{$this_ma_id}'
					,	po_num        = '{$_POST['po_num'][$k]}'
					,	po_species    = '{$_POST['po_species'][$k]}'
					,	po_form       = '{$_POST['po_form'][$k]}'
					,	po_type1      = '{$_POST['po_type1'][$k]}'
					,	po_type2      = '{$_POST['po_type2'][$k]}'
					,	po_sex        = '{$_POST['po_sex'][$k]}'
					,	po_ex         = '{$_POST['po_ex'][$k]}'
					,	po_egg         = '{$_POST['po_egg'][$k]}'
				  where po_id         = '{$_POST['po_id'][$k]}' ";
		sql_query($sql);
	}

}elseif ($_POST['act_button'] == "선택삭제") {
	$ids = array();
	if (isset($_POST['chk']) && is_array($_POST['chk'])) {
		foreach ($_POST['chk'] as $idx) {
			if (!isset($_POST['po_id'][$idx])) continue;

			$this_po_id = (int)$_POST['po_id'][$idx];
			if ($this_po_id > 0) {
				$ids[] = $this_po_id;
			}
		}
	}

	if (!empty($ids)) {
		$ids = array_unique($ids);

		$in_list = implode(',', $ids);

		// 관련 레코드 일괄 삭제
		sql_query("DELETE FROM {$g5['pokemon_table']} WHERE po_id IN ({$in_list})");
	}

}elseif ($_POST['act_button'] == "등록") {
	$this_ma_id=implode(",",$_POST['ma_id']);
	$sql = " insert into {$g5['pokemon_table']}
					set ma_id         = '{$this_ma_id}'
					,	po_num        = '{$_POST['po_num']}'
					,	po_species    = '{$_POST['po_species']}'
					,	po_form       = '{$_POST['po_form']}'
					,	po_type1      = '{$_POST['po_type1']}'
					,	po_type2      = '{$_POST['po_type2']}'
					,	po_sex        = '{$_POST['po_sex']}'
					,	po_egg        = '{$_POST['po_egg']}'
					,	po_ex         = '{$_POST['po_ex']}'";
	sql_query($sql);
}

goto_url('./pokemon_list.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
