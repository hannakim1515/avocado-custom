<?php
include_once('../common.php');
// Location restrictions control joining and movement; dungeon visibility is
// available whenever the feature is enabled, including unrestricted openings.
$show_dungeon_map = !empty($config['cf_dungeon_open']);
?>
