<?php
if (!defined('_GNUBOARD_')) exit;

/** Item effects use the existing type/value fields, just like stat recovery. */
function item_recovery_effect($item) {
    $type = $item['it_type'] ?? '';
    $value = $item['it_value'] ?? '';
    if ($type === '부활' && filter_var($value, FILTER_VALIDATE_INT) !== false && $value >= 1 && $value <= 100)
        return array('kind'=>'REVIVE', 'value'=>(int)$value, 'status_id'=>0);
    if ($type === '상태이상치료' && filter_var($value, FILTER_VALIDATE_INT) !== false && $value > 0)
        return array('kind'=>'CLEANSE', 'value'=>0, 'status_id'=>(int)$value);
    return null;
}

/** Optional legacy catalog: opening the item form never installs Maze tables. */
function item_recovery_table_exists($suffix) {
    global $g5;
    $name = mysqli_real_escape_string($g5['connect_db'], G5_TABLE_PREFIX.'dungeon_maze_'.$suffix);
    $result = mysqli_query($g5['connect_db'], "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$name'");
    return $result && mysqli_num_rows($result) > 0;
}
function item_recovery_statuses() {
    global $g5;
    if (!item_recovery_table_exists('status')) return array();
    $result = mysqli_query($g5['connect_db'], 'SELECT status_id,name,enabled FROM `'.G5_TABLE_PREFIX.'dungeon_maze_status` ORDER BY name,status_id');
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) $rows[(int)$row['status_id']] = $row;
    return $rows;
}
