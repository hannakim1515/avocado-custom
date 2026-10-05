<?php
include_once './_common.php';
$sub_menu='730100';
auth_check($auth[$sub_menu],$_SERVER['REQUEST_METHOD']==='POST'?'w':'r');
$id=(int)($_REQUEST['dg_id']??0);
if($_SERVER['REQUEST_METHOD']==='POST')alert('미궁 설정 저장 화면이 통합되었습니다. 던전 편집 화면에서 다시 저장해 주세요.','./dungeon_form.php'.($id?'?w=u&dg_id='.$id.'&mode=maze':'?mode=maze'));
goto_url('./dungeon_form.php?mode=maze'.($id?'&w=u&dg_id='.$id:''));
