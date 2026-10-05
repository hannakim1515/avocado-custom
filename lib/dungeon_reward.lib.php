<?php
if (!defined('_GNUBOARD_')) exit;

/** One definition list for every dungeon; snapshots freeze it at departure. */
function dungeon_rewards($dg_id) {
    global $g5;
    $result = sql_query('SELECT it_id,di_count,di_percent FROM `'.$g5['dungeon_item_table'].'` WHERE dg_id='.(int)$dg_id.' ORDER BY di_id');
    $rewards = array();
    while ($row = sql_fetch_array($result)) $rewards[] = array('it_id'=>(int)$row['it_id'], 'count'=>(int)$row['di_count'], 'percent'=>(float)$row['di_percent']);
    return $rewards;
}
function dungeon_reward_won($percent, $roll = null) {
    $threshold = (int)round((float)$percent * 100);
    if ($threshold < 0 || $threshold > 10000) throw new RuntimeException('보상 확률은 0~100%입니다.');
    return ($roll ?? random_int(1, 10000)) <= $threshold;
}
