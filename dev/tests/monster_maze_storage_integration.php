<?php
/** Inline maze monsters become shared definitions; config keeps only IDs. */
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable DB required');
$name='codex_maze_storage_'.bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','mz_');$g5=array('connect_db'=>$db);
require dirname(__DIR__,2).'/lib/system_installer.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
    $contract=system_install_contract();
    system_install_query(system_install_create('monster_master',$contract['monsters']['monster_master']));
    $historical=json_decode(file_get_contents(dirname(__DIR__,2).'/install/system/labyrinth/schema-1.5.0.json'),true);
    system_install_query(system_install_create('dungeon_maze_config',$historical['dungeon_maze_config']));
    system_install_query("INSERT INTO mz_monster_master (monster_id,name,enabled,profile) VALUES (7,'연결됨',1,'{\"dg_mon_hp\":30}')");
    $settings=array('title'=>'중복 던전명','description'=>'중복 설명','max_players'=>4,'points'=>10,'monsters'=>array(
        array('enabled'=>true,'weight'=>2,'data'=>array('dg_mon_name'=>'인라인','dg_mon_hp'=>80,'dg_title'=>'던전 중복','custom'=>'보존')),
        array('monster_id'=>7,'enabled'=>true,'weight'=>1,'data'=>array('dg_mon_hp'=>30))
    ),'events'=>array(
        array('type'=>'MONSTER','monster'=>array('dg_mon_name'=>'이벤트','dg_mon_hp'=>40)),
        array('type'=>'TEXT','choices'=>array(array('type'=>'MONSTER','monster'=>array('dg_mon_name'=>'선택지','dg_mon_hp'=>50))))
    ));
    system_install_query('INSERT INTO mz_dungeon_maze_config (dg_id,settings) VALUES (1,'.system_install_quote(json_encode($settings,JSON_UNESCAPED_UNICODE)).')');
    $log=array();system_install_maze_monster_references($log);
    $after=json_decode(system_install_one('SELECT settings FROM mz_dungeon_maze_config WHERE dg_id=1')['settings'],true);
    check((int)$after['monsters'][0]['monster_id']>0 && !isset($after['monsters'][0]['data']) && !isset($after['monsters'][1]['data']),'maze monster pool stores only master IDs');
    check(!array_intersect_key($after,array_flip(array('title','description','max_players','points'))),'maze config drops duplicated dungeon base fields');
    check((int)$after['events'][0]['monster_id']>0 && !isset($after['events'][0]['monster']) && (int)$after['events'][1]['choices'][0]['monster_id']>0,'event and choice monsters store only master IDs');
    $m=system_install_one('SELECT name,profile FROM mz_monster_master WHERE monster_id='.(int)$after['monsters'][0]['monster_id']);
    $p=json_decode($m['profile'],true);
    check($m['name']==='인라인' && $p['custom']==='보존' && !isset($p['dg_title']) && (int)$p['dg_mon_hp']===80,'inline combat fields and custom data preserved without dungeon metadata');
    $count=(int)system_install_one('SELECT COUNT(*) n FROM mz_monster_master')['n'];
    $log=array();system_install_maze_monster_references($log);
    check(!$log && (int)system_install_one('SELECT COUNT(*) n FROM mz_monster_master')['n']===$count,'maze migration rerun does not duplicate definitions');
    system_install_query('INSERT INTO mz_dungeon_maze_config (dg_id,settings) VALUES (2,'.system_install_quote(json_encode(array('monsters'=>array(array('data'=>array('dg_mon_name'=>'먼저 이전','dg_mon_hp'=>10)),array('data'=>array('dg_mon_name'=>'잘못됨','dg_mon_hp'=>0)))) ,JSON_UNESCAPED_UNICODE)).')');
    try{$log=array();system_install_maze_monster_references($log);throw new LogicException('invalid inline monster accepted');}
    catch(RuntimeException $e){check(strpos($e->getMessage(),'인라인 몬스터 HP')!==false,'invalid inline monster stops migration');}
    check((int)system_install_one('SELECT COUNT(*) n FROM mz_monster_master')['n']===$count,'failed maze migration leaves catalog unchanged');
} finally {$db->query('DROP DATABASE `'.$name.'`');}
