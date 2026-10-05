<?php
include_once './_common.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (($_POST['operation']??'')==='settings') { require __DIR__.'/dungeon_maze_form.php'; return; }
    require __DIR__.'/dungeon_state_list.php'; return;
}
$ds_id=(int)($_GET['ds_id']??0);
$dg_id=(int)($_GET['dg_id']??0);
if($ds_id) goto_url('./dungeon_state_list.php?ds_id='.$ds_id);
if($dg_id) goto_url('./dungeon_form.php?w=u&dg_id='.$dg_id);
goto_url('./dungeon_list.php');
