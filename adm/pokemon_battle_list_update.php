<?php
$sub_menu = "091200";
include_once('./_common.php');

check_demo();

if (!count($_POST['chk'])&&$_POST['act_button'] != "지급") {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택삭제") {
	$ids = array();
	if (isset($_POST['chk']) && is_array($_POST['chk'])) {
		foreach ($_POST['chk'] as $idx) {
			if (!isset($_POST['ph_id'][$idx])) continue;

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
		sql_query("DELETE FROM {$g5['pokemon_battle_table']} WHERE ph_id IN ({$in_list})");
	}

}

goto_url('./pokemon_battle_list.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
