<?php
$sub_menu = '500300';
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'd');
check_token();

$count = count($_POST['chk']);
if(!$count)
	alert($_POST['act_button'].' 하실 항목을 하나 이상 체크하세요.');

$delete_ids = array();
foreach ($_POST['chk'] as $key) $delete_ids[] = (int)$_POST['in_id'][$key];
$delete_claim = inventory_boundary_delete_or_alert('in_id', $delete_ids);
if ($delete_claim) inventory_boundary_done($delete_claim);

goto_url('./inventory_list.php?'.$qstr);
?>