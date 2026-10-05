<?php
// The old GET endpoint changed data without CSRF protection and is no longer
// linked from the dungeon editor.
$sub_menu='730100';
include_once './_common.php';
auth_check($auth[$sub_menu],'r');
goto_url('./dungeon_list.php');
