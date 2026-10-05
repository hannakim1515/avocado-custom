<?php
/** Legacy dungeon monster columns migrate into monster_master without losing combat reads. */
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable DB required');
$name='codex_dungeon_storage_'.bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','dv_');$g5=array('connect_db'=>$db);
require dirname(__DIR__,2).'/lib/system_installer.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
    $root=dirname(__DIR__,2);
    $old=json_decode(file_get_contents($root.'/install/system/dungeon/schema-1.0.0.json'),true)['dungeon'];
    $common=system_install_contract()['monsters'];
    system_install_query(system_install_create('dungeon',$old));
    foreach(array('monster_master','dungeon_monster_pool') as $table)system_install_query(system_install_create($table,$common[$table]));
    system_install_query("INSERT INTO dv_dungeon (dg_id,dg_title,dg_mon_name,dg_mon_img,dg_mon_hp,dg_mon_descript,dg_d_attack_min,dg_d_attack_max) VALUES (2,'기존 던전','구형 몬스터','image.png',125,'던전 안내',7,12),(3,'풀 던전','','',0,'출현 풀 안내',0,0)");
    system_install_query("INSERT INTO dv_monster_master (name,image,description,enabled,profile) VALUES ('일반','', '',1,'{\"dg_mon_hp\":80}'),('보스','boss.png','',1,'{\"dg_mon_hp\":250}')");
    system_install_query('INSERT INTO dv_dungeon_monster_pool (dg_id,monster_id,weight,enabled,is_boss) VALUES (3,1,1,1,0),(3,2,1,1,1)');
    system_install_query('ALTER TABLE dv_dungeon ADD dg_description text NOT NULL');
    $log=array();system_install_monster_dungeon_view($log);
    $schema=system_install_schema('dv_dungeon');
    check(!isset($schema['columns']['dg_mon_name']) && !isset($schema['columns']['dg_mon_hp']) && isset($schema['columns']['dg_description']),'duplicate dungeon monster columns removed');
    check(system_install_schema('dv_dungeon_monster_read')['TABLE_TYPE']==='VIEW','legacy combat fields projected by a read view');
    $row=system_install_one('SELECT dg_mon_name,dg_mon_img,dg_mon_hp,dg_d_attack_min,dg_d_attack_max,dg_mon_descript FROM dv_dungeon_monster_read WHERE dg_id=2');
    check($row['dg_mon_name']==='구형 몬스터' && $row['dg_mon_img']==='image.png' && (int)$row['dg_mon_hp']===125 && (int)$row['dg_d_attack_min']===7 && (int)$row['dg_d_attack_max']===12 && $row['dg_mon_descript']==='던전 안내','unpooled legacy monster and dungeon description retained');
    check((int)system_install_one('SELECT COUNT(*) n FROM dv_dungeon_monster_pool WHERE dg_id=2')['n']===1,'legacy monster linked into common pool');
    $row=system_install_one('SELECT dg_mon_name,dg_mon_hp,dg_mon_descript FROM dv_dungeon_monster_read WHERE dg_id=3');
    check($row['dg_mon_name']==='보스' && (int)$row['dg_mon_hp']===250 && $row['dg_mon_descript']==='출현 풀 안내','boss takes priority as dungeon representative');
    system_install_query("UPDATE dv_monster_master SET name='공용 변경' WHERE name='구형 몬스터'");
    check(system_install_one('SELECT dg_mon_name FROM dv_dungeon_monster_read WHERE dg_id=2')['dg_mon_name']==='공용 변경','dungeon reader follows common master edit');
    $second=array();system_install_monster_dungeon_view($second);
    check(!$second,'migration rerun is idempotent');
} finally {$db->query('DROP DATABASE `'.$name.'`');}
