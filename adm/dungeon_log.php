<?php
$sub_menu='730200';include_once './_common.php';auth_check($auth[$sub_menu],'r');
require_once G5_PATH.'/lib/monster.lib.php';
require __DIR__.'/dungeon_combat_log.view.php';
