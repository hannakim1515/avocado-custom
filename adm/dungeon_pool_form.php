<?php
// Keep old bookmarks and already-open forms working after the editor merge.
$sub_menu='730100';
include_once './_common.php';
$dg_id=(int)($_REQUEST['dg_id']??0);
if(!$dg_id) goto_url('./dungeon_list.php');
if($_SERVER['REQUEST_METHOD']!=='POST') goto_url('./dungeon_form.php?w=u&dg_id='.$dg_id.'#anc_002');
auth_check($auth[$sub_menu],'w');
check_token();
require_once G5_PATH.'/lib/monster.lib.php';
require_once __DIR__.'/dungeon_admin.lib.php';
if(!sql_fetch('SELECT dg_id FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$dg_id))alert('던전을 선택하세요.');
$input=$_POST;
foreach(($input['pool']??array()) as $row)if(!empty($row['enabled'])){$input['pool_enabled']=1;break;}
try { da_pool_save($dg_id,da_pool_validate($input)); }
catch(Throwable $error) { alert($error->getMessage()); }
goto_url('./dungeon_form.php?w=u&dg_id='.$dg_id.'&saved=1#anc_002');
