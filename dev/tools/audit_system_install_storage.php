<?php
/** Read-only audit of current installer contracts and the configured database. */
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__,2);define('_GNUBOARD_',true);define('G5_PATH',$root);
require $root.'/data/dbconfig.php';
mysqli_report(MYSQLI_REPORT_OFF);
$db=@new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);
if($db->connect_errno)throw new RuntimeException('Database connection failed.');
$db->set_charset('utf8mb4');$g5['connect_db']=$db;
require $root.'/lib/system_installer.lib.php';require $root.'/lib/monster.lib.php';
$retired=array('dungeon_maze_config','dungeon_maze_session','dungeon_monster_config',
    'dungeon_state_monster','dungeon_member','dungeon_log','monster_dungeon_profile',
    'dungeon_skill_target','dungeon_maze_item_effect','k_battle_monster_legacy_migration');
$problems=array();$create=array();$contracts=system_install_contract();
foreach($contracts as $module=>$specs){
    $create[$module]=array();
    foreach($specs as $suffix=>$spec){
        if(in_array($suffix,$retired,true)||$suffix==='k_battle_monster')$problems[]='Current contract references retired storage: '.$module.'/'.$suffix;
        if(!empty($spec['create']))$create[$module][]=$suffix;
    }
}
$absence=array();foreach($retired as $suffix){
    $absence[$suffix]=system_install_schema(system_install_table($suffix))===null;
    if(!$absence[$suffix])$problems[]='Retired object still exists: '.$suffix;
}
$views=array();foreach(array('k_battle_monster','dungeon_monster_read') as $suffix){
    $schema=system_install_schema(system_install_table($suffix));$views[$suffix]=$schema['TABLE_TYPE']??'MISSING';
    if($views[$suffix]!=='VIEW')$problems[]='Shared monster view required: '.$suffix;
}
$old_columns=array('dungeon'=>array_keys(monster_dungeon_legacy_fields()),
    'dungeon_item'=>array('di_per_s','di_per_e'),
    'dungeon_state'=>array('ds_hp','ds_hurt','ds_weak_count','ds_weak_turn'));
foreach($old_columns as $suffix=>$columns){
    $actual=system_install_schema(system_install_table($suffix));
    foreach($columns as $column){
        if(isset($contracts['dungeon'][$suffix]['columns'][$column]))$problems[]='Current contract restores retired column: '.$suffix.'.'.$column;
        if(isset($actual['columns'][$column]))$problems[]='Retired column still exists: '.$suffix.'.'.$column;
    }
}
$states=array();foreach(system_install_all_status() as $module=>$state){
    $states[$module]=array('version'=>$state['version'],'latest'=>system_install_registry()[$module]['version'],
        'installed'=>$state['installed'],'status'=>$state['status'],'blocked'=>$state['blocked']);
    if(in_array($module,array('dungeon','k_battle','monsters','labyrinth'),true)&&(!$state['installed']||$state['blocked']||$state['version']!==system_install_registry()[$module]['version']))$problems[]='Required shared-storage module is not current: '.$module;
}
echo json_encode(array('ok'=>!$problems,'problems'=>$problems,'current_create_tables'=>$create,
    'retired_objects_absent'=>$absence,'shared_monster_objects'=>$views,'modules'=>$states),
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),PHP_EOL;
exit($problems?1:0);
