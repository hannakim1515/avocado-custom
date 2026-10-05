<?php
if(PHP_SAPI!=='cli')exit(1);mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);$db->set_charset('utf8mb4');if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable server required');
$name='codex_pool_schema_'.bin2hex(random_bytes(5));$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','test_');$g5=array('connect_db'=>$db);require dirname(__DIR__,2).'/lib/system_installer.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
 $old=json_decode(file_get_contents(dirname(__DIR__,2).'/install/system/monsters/schema-1.7.0.json'),true);$new=system_install_contract()['monsters'];$log=array();$step='';
 require dirname(__DIR__,2).'/lib/dungeon_unify_migration.lib.php';
 system_install_query(system_install_create('system_migration_meta',system_install_meta_spec()));
 system_install_query(system_install_create('dungeon_monster_pool',$old['dungeon_monster_pool']));
 system_install_query('INSERT INTO test_dungeon_monster_pool (dg_id,monster_id,weight,enabled,is_boss) VALUES (1,10,7,1,0)');
 system_install_record('monsters','1.7.0',$log);
 system_install_apply_contract('monsters','1.8.0',array('dungeon_monster_pool'=>$new['dungeon_monster_pool']),$log,$step);
 system_install_monster_encounter_counts($log);system_install_record('monsters','1.8.0',$log);
 $row=system_install_one('SELECT * FROM test_dungeon_monster_pool');
 check($row['weight']==7&&$row['enabled']==1&&$row['min_count']==1&&$row['max_count']==1,'1.7 upgrade preserves weights and defaults to one unit');
 system_install_query('UPDATE test_dungeon_monster_pool SET min_count=2,max_count=4');
 system_install_apply_contract('monsters','1.8.0',array('dungeon_monster_pool'=>$new['dungeon_monster_pool']),$log,$step);system_install_monster_encounter_counts($log);
 $row=system_install_one('SELECT * FROM test_dungeon_monster_pool');check($row['min_count']==2&&$row['max_count']==4,'upgrade retry preserves configured encounter range');
 system_install_query('ALTER TABLE test_dungeon_monster_pool MODIFY min_count INT NOT NULL DEFAULT 0,MODIFY max_count INT NOT NULL DEFAULT 12');
 unset($GLOBALS['system_install_schema_cache']['test_dungeon_monster_pool']);
 system_install_record('monsters','1.6.0',$log);system_install_monster_encounter_counts($log);
 $row=system_install_one('SELECT * FROM test_dungeon_monster_pool');$columns=system_install_schema('test_dungeon_monster_pool')['columns'];
 check($row['min_count']==1&&$row['max_count']==1&&$columns['min_count']['Default']==1&&$columns['max_count']['Default']==1,'obsolete quota values do not become simultaneous group sizes');
 system_install_query(system_install_create('fresh_pool',$new['dungeon_monster_pool']));
 $columns=system_install_schema('test_fresh_pool')['columns'];
 check($columns['min_count']['Default']==1&&$columns['max_count']['Default']==1,'fresh appearances default to one unit');
}finally{$db->query('DROP DATABASE `'.$name.'`');}
