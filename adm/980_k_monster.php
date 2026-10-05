<?php
// K raid and Dungeon monsters now share the same catalog editor.
$sub_menu='980700';
include_once './_common.php';
require_once __DIR__.'/dungeon_admin.lib.php';
da_monster_auth('r');
$id=(int)($_GET['mo_id']??0);
if($id>0) {
    require_once G5_PATH.'/lib/monster.lib.php';
    $row=sql_fetch('SELECT monster_id FROM `'.monster_table('monster_master').'` WHERE raid_id='.$id);
    if($row)goto_url('./monster_form.php?monster_id='.(int)$row['monster_id']);
}
goto_url('./monster.php');
