<?php
// Previous list URL now leads to the single dungeon editor flow.
$sub_menu='730100';
include_once './_common.php';
auth_check($auth[$sub_menu],'r');
$dg_id=(int)($_REQUEST['dg_id']??0);
goto_url($dg_id?'./dungeon_form.php?w=u&dg_id='.$dg_id.'#anc_002':'./dungeon_list.php');
