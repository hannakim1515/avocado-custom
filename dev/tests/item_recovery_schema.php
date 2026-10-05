<?php
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);$db->set_charset('utf8mb4');
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable server required');
$name='codex_item_schema_'.bin2hex(random_bytes(5));$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','test_');$g5=array('connect_db'=>$db);
require dirname(__DIR__,2).'/lib/system_installer.lib.php';require dirname(__DIR__,2).'/lib/item_effect.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
 $db->query(system_install_create('system_migration_meta',system_install_meta_spec()));
 $log=array();$step='';$contract=system_install_contract()['labyrinth'];
 system_install_apply_contract('labyrinth','1.2.0',$contract,$log,$step);
 system_install_seed_defaults('labyrinth',$log);system_install_record('labyrinth','1.2.0',$log);
 check(!system_install_schema('test_dungeon_maze_item_effect'),'fresh Maze schema creates no separate item effect table');
 check(system_install_inspect('labyrinth')['installed'],'fresh minor version matches native integer version');
 $before=count(system_install_rows('SHOW TABLES'));system_install_apply_contract('labyrinth','1.2.0',$contract,$log,$step);
 system_install_record('labyrinth','1.2.0',$log);check(count(system_install_rows('SHOW TABLES'))===$before && system_install_inspect('labyrinth')['installed'],'schema and version rerun are idempotent');
 check(item_recovery_effect(array('it_type'=>'부활','it_value'=>20))['value']===20,'item effects work without the obsolete table');
 $old=json_decode(file_get_contents(dirname(__DIR__,2).'/install/system/labyrinth/schema-1.0.0.json'),true);
 $db->query('CREATE TABLE test_item (it_id int NOT NULL PRIMARY KEY,it_type varchar(40) NOT NULL DEFAULT \'\',it_value varchar(100) NOT NULL DEFAULT \'\') ENGINE=InnoDB');
 $db->query("INSERT INTO test_item (it_id,it_type) VALUES (1,'일반'),(2,'일반')");
 $db->query(system_install_create('dungeon_maze_item_effect',$old['dungeon_maze_item_effect']));
 $db->query("INSERT INTO test_dungeon_maze_item_effect (it_id,kind,status_id,value) VALUES (1,'REVIVE',0,25),(2,'CLEANSE',7,1)");
 unset($GLOBALS['system_install_schema_cache']['test_dungeon_maze_item_effect']);
 system_install_record('labyrinth','1.1.0',$log);system_install_apply_contract('labyrinth','1.2.0',$contract,$log,$step);
 system_install_seed_defaults('labyrinth',$log);system_install_record('labyrinth','1.2.0',$log);
 $items=system_install_rows('SELECT it_id,it_type,it_value FROM test_item ORDER BY it_id');
 check(!system_install_schema('test_dungeon_maze_item_effect') && $items[0]['it_type']==='부활' && $items[0]['it_value']==='25' && $items[1]['it_type']==='상태이상치료' && $items[1]['it_value']==='7','old Maze effects migrate into item definitions before table removal');
}finally{$db->query('DROP DATABASE `'.$name.'`');}
