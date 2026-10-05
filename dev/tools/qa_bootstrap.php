<?php
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__,2);
$g5_path=array('path'=>$root,'url'=>'');
require $root.'/config.php';require $root.'/data/dbconfig.php';
$options=getopt('',array('database:','inspect','apply','backup:','check','exercise'));
$qaFixture=isset($options['database']);
$qaDatabase=$qaFixture?$options['database']:G5_MYSQL_DB;
if($qaFixture && !preg_match('/^codex_qa_[a-z0-9_]+$/D',$qaDatabase))throw new RuntimeException('Fixture database only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($qaFixture?'127.0.0.1':G5_MYSQL_HOST,$qaFixture?'root':G5_MYSQL_USER,$qaFixture?'':G5_MYSQL_PASSWORD,$qaDatabase,$qaFixture?33381:3306);
$db->set_charset('utf8mb4');$g5['connect_db']=$db;
if($qaFixture && strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Isolated server only');
$db->query("SET SESSION sql_mode=''");
require $root.'/lib/common.lib.php';require $root.'/lib/system_installer.lib.php';
function sql_escape_string($value){return $GLOBALS['db']->real_escape_string((string)$value);}
// K aliases normally declared by its extension; loading that extension also runs bootstrap.
foreach(array('skill_table'=>'skill','skill_has_table'=>'skill_has','skill_level_table'=>'skill_level','k_skill_table'=>'k_battle_skill','k_skill_info_table'=>'k_battle_skill_info','k_ch_skill_table'=>'k_battle_skill_ch','k_monster_table'=>'k_battle_monster','k_stat_table'=>'k_battle_stat_func','k_realtime_table'=>'k_battle_realtime','k_ch_equip_table'=>'k_battle_equip_ch','dungeon_read_table'=>'dungeon_monster_read','dungeon_state_table'=>'dungeon_state','dungeon_table'=>'dungeon') as $alias=>$suffix)$g5[$alias]=G5_TABLE_PREFIX.$suffix;
$config=sql_fetch('SELECT * FROM '.$g5['config_table']);
$g5['status_extra_table']=G5_TABLE_PREFIX.'status_extra';
$kb_cf=sql_fetch('SELECT * FROM '.G5_TABLE_PREFIX.'k_battle_plugin_config LIMIT 1');
$member=sql_fetch('SELECT * FROM '.$g5['member_table'].' WHERE mb_id='.qa_quote($config['cf_admin']));$is_admin='super';
function qa_quote($value){return $value===null?'NULL':"'".$GLOBALS['db']->real_escape_string((string)$value)."'";}
function qa_table($suffix){return '`'.G5_TABLE_PREFIX.$suffix.'`';}
function qa_rows($sql){$r=$GLOBALS['db']->query($sql);return $r->fetch_all(MYSQLI_ASSOC);}
function qa_insert($suffix,$values){
    $schema=system_install_schema(system_install_table($suffix));
    foreach($schema['columns'] as $key=>$c)if(!array_key_exists($key,$values)&&$c['Default']===null&&$c['Null']!=='YES'&&stripos($c['Extra'],'auto_increment')===false)$values[$key]=preg_match('/^(tinyint|smallint|mediumint|int|bigint|float|double|decimal)/i',$c['Type'])?0:'';
    $keys=array_map('system_install_ident',array_keys($values));
    $GLOBALS['db']->query('INSERT INTO '.qa_table($suffix).' ('.implode(',',$keys).') VALUES ('.implode(',',array_map('qa_quote',array_values($values))).')');
    return (int)$GLOBALS['db']->insert_id;
}
function qa_update($suffix,$values,$where){$sets=array();foreach($values as $k=>$v)$sets[]='`'.$k.'`='.qa_quote($v);$GLOBALS['db']->query('UPDATE '.qa_table($suffix).' SET '.implode(',',$sets).' WHERE '.$where);}
function qa_upsert($suffix,$pk,$where,$values){$rows=qa_rows('SELECT * FROM '.qa_table($suffix).' WHERE '.$where);if(count($rows)>1)throw new RuntimeException('Ambiguous QA reference '.$suffix);if($rows){qa_update($suffix,$values,'`'.$pk.'`='.qa_quote($rows[0][$pk]));return $rows[0][$pk];}if($pk==='it_id')$values[$pk]=1+(int)sql_fetch('SELECT COALESCE(MAX(it_id),0) n FROM '.qa_table($suffix))['n'];qa_insert($suffix,$values);return $values[$pk]??(int)sql_insert_id();}
function qa_assert($condition,$label){if(!$condition)throw new RuntimeException($label);}
function qa_schema(){return qa_rows('SELECT TABLE_NAME,TABLE_TYPE,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME');}
if(isset($options['inspect'])){
    $out=array('objects'=>qa_schema());
    foreach(array('dungeon_state','dungeon_item','k_battle_equip_ch','inventory_journal','dungeon_maze_status','board','group','shop','item_recepi','battle_config','k_battle_realtime','skill','skill_level') as $suffix){$out[$suffix]=qa_rows('SELECT * FROM '.qa_table($suffix));}
    echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;
}
require $root.'/lib/monster_shared.lib.php';require $root.'/extend/inventory_boundary.lib.php';
require $root.'/extend/dungeon_maze.lib.php';require $root.'/extend/dungeon_maze_session.lib.php';
require $root.'/extend/dungeon_maze_inventory.lib.php';require $root.'/extend/dungeon_maze_combat.lib.php';require $root.'/extend/dungeon_maze_actions.lib.php';
require $root.'/extend/dungeon.lib.php';
require $root.'/extend/unified_stat.lib.php';require $root.'/extend/unified_skill.lib.php';
