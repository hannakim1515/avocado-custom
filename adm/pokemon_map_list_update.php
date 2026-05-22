<?php
$sub_menu = "091003";
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
		$sql = " update {$g5['pokemon_map_table']}
					set ma_name        = '{$_POST['ma_name'][$k]}',
						ma_img         = '{$_POST['ma_img'][$k]}',
						ma_img_action  = '{$_POST['ma_img_action'][$k]}',
						ma_content     = '{$_POST['ma_content'][$k]}',
						ma_egg     	   = '{$_POST['ma_egg'][$k]}',
						ma_use         = '{$_POST['ma_use'][$k]}'
				  where ma_id          = '{$_POST['ma_id'][$k]}' ";
		sql_query($sql);
	}

}elseif ($_POST['act_button'] == "선택삭제") {
	$ids = array();
	if (isset($_POST['chk']) && is_array($_POST['chk'])) {
		foreach ($_POST['chk'] as $idx) {
			if ($_POST['ma_type'][$idx]=='command') continue;

			$this_ma_id = (int)$_POST['ma_id'][$idx];
			if ($this_ma_id > 0) {
				$ids[] = $this_ma_id;
			}
		}
	}

	if (!empty($ids)) {
		$ids = array_unique($ids);
		$in_list = implode(',', $ids);

		// 관련 레코드 일괄 삭제
		sql_query("DELETE FROM {$g5['pokemon_map_table']} WHERE ma_id IN ({$in_list})");
	}

}elseif ($_POST['act_button'] == "등록") {

	$sql = " insert into {$g5['pokemon_map_table']}
					set ma_name        = '{$_POST['ma_name']}',
						ma_img         = '{$_POST['ma_img']}',
						ma_img_action  = '{$_POST['ma_img_action']}',
						ma_content     = '{$_POST['ma_content']}',
						ma_use         = '{$_POST['ma_use']}',
						ma_egg         = '{$_POST['ma_egg']}',
						ma_type		   = 'default'
			";
	sql_query($sql);
}

goto_url('./pokemon_map_list.php?'.$qstr."&cate=".$cate."&map_id=".$map_id);
?>
