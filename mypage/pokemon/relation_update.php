<?php
include_once('./_common.php');

$po = get_pokemon($ph_id);
if($po['ch_id'] != $character['ch_id']) { 
	alert("등록권한이 없습니다.");
}

if($w == 'u') { 
	// 수정사항
	$sql = " update {$g5['pokemon_has_table']}
				set po_content = '{$_POST['po_content']}'
				where ph_id = '{$_POST['ph_id']}'
				";
	sql_query($sql);

}

goto_url('./relation_list.php');
?>
