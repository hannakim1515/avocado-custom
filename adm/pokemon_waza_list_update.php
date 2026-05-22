<?php
$sub_menu = "091210";
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
		$sql = " update {$g5['pokemon_waza_table']}
					set wa_name         = '{$_POST['wa_name'][$k]}',
						wa_type         = '{$_POST['wa_type'][$k]}',
						wa_type2  		= '{$_POST['wa_type2'][$k]}',
						wa_power      	= '{$_POST['wa_power'][$k]}',
						wa_hit      	= '{$_POST['wa_hit'][$k]}',
						wa_pp_max     	= '{$_POST['wa_pp_max'][$k]}',
						wa_content      = '{$_POST['wa_content'][$k]}'
				  where wa_id           = '{$_POST['wa_id'][$k]}' ";
		sql_query($sql);
	}

}elseif ($_POST['act_button'] == "선택삭제") {
	$ids = array();
	if (isset($_POST['chk']) && is_array($_POST['chk'])) {
		foreach ($_POST['chk'] as $idx) {
			if ($_POST['wa_category'][$idx]!='공격') continue;

			$this_wa_id = (int)$_POST['wa_id'][$idx];
			if ($this_wa_id > 0) {
				$ids[] = $this_wa_id;
			}
		}
	}

	if (!empty($ids)) {
		$ids = array_unique($ids);
		$in_list = implode(',', $ids);

		// 관련 레코드 일괄 삭제
		sql_query("DELETE FROM {$g5['pokemon_waza_table']} WHERE wa_id IN ({$in_list})");
	}

}elseif ($_POST['act_button'] == "등록") {

	$sql = " insert into {$g5['pokemon_waza_table']}
					set wa_name         = '{$_POST['wa_name']}',
						wa_type         = '{$_POST['wa_type']}',
						wa_type2  		= '{$_POST['wa_type2']}',
						wa_power      	= '{$_POST['wa_power']}',
						wa_hit      	= '{$_POST['wa_hit']}',
						wa_pp_max     	= '{$_POST['wa_pp_max']}',
						wa_content      = '{$_POST['wa_content']}',
						wa_category		= '공격'
			";
	sql_query($sql);
}

goto_url('./pokemon_waza_list.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
