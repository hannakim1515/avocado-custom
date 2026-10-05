<?php
$sub_menu='730200';
include_once './_common.php';
auth_check($auth[$sub_menu],'w');
goto_url('./dungeon_state_list.php');
