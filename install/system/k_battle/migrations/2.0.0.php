<?php
if (!defined('_GNUBOARD_')) exit;
// The safe, additive part of the legacy plugin_2.0.0.php migration.
// limit_item MODIFY is deliberately absent: a type mismatch requires review.
$source = json_decode(file_get_contents(__DIR__.'/../schema-2.0.2.json'), true);
$migration = array();
foreach (array('k_battle_skill_mo', 'k_battle_pattern_mo', 'k_raid_list') as $table) {
    $migration[$table] = $source[$table];
}
foreach (array(
    'k_battle_plugin_config'=>array('equip_per_hide','ver_plugin'),
    'k_battle_monster'=>array('raid_type','mo_pattern','mo_default_act'),
    'k_battle_skill'=>array('unit_type','raid_type')
) as $table=>$columns) {
    if ($table==='k_battle_monster') {
        $catalog=system_install_schema(system_install_table($table));
        if ($catalog && $catalog['TABLE_TYPE']==='VIEW') continue;
    }
    $migration[$table] = $source[$table];
    $migration[$table]['create'] = false;
    $migration[$table]['columns'] = array_intersect_key($source[$table]['columns'], array_flip($columns));
    $migration[$table]['indexes'] = isset($source[$table]['indexes']['idx_raid_type'])
        ? array('idx_raid_type'=>$source[$table]['indexes']['idx_raid_type']) : array();
}
return $migration;
