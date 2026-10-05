<?php
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
$dir=str_replace('\\','/',$db->query('SELECT @@datadir d')->fetch_assoc()['d']);
if(strpos($dir,'/codex-system-installer-')===false)throw new RuntimeException('Not an isolated database');
$database='codex_combat_'.bin2hex(random_bytes(5));$db->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');$db->select_db($database);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_PATH',dirname(__DIR__,2));define('G5_TABLE_PREFIX','ct_');
$g5=array('connect_db'=>$db);$is_admin='super';$_SESSION=array();$_SERVER['REQUEST_METHOD']='POST';
function sql_query($sql,$error=true){global $db;try{return $db->query($sql);}catch(Throwable $e){if($error)throw $e;return false;}}
function sql_fetch_array($r){return $r->fetch_assoc();}function sql_fetch($sql,$error=true){$r=sql_query($sql,$error);return $r?$r->fetch_assoc():null;}
function sql_escape_string($v){return $GLOBALS['db']->real_escape_string((string)$v);}function sql_insert_id(){return $GLOBALS['db']->insert_id;}
function check($value,$message){if(!$value)throw new RuntimeException('FAIL '.$message);echo 'PASS '.$message."\n";}
function rejects($fn,$message){try{$fn();}catch(RuntimeException $e){check(true,$message);return;}check(false,$message);}
require G5_PATH.'/lib/system_installer.lib.php';require G5_PATH.'/lib/monster.lib.php';require G5_PATH.'/extend/inventory_boundary.lib.php';require G5_PATH.'/extend/dungeon_maze.lib.php';require G5_PATH.'/extend/dungeon_maze_combat.lib.php';
function addrow($table,$data){sql_query(system_install_default_row($table,$data));return sql_insert_id();}
try {
    $db->query("SET SESSION sql_mode=''");$db->multi_query(str_replace('`avo_','`ct_',file_get_contents(G5_PATH.'/install/gnuboard5.sql')));do{if($r=$db->store_result())$r->free();}while($db->more_results()&&$db->next_result());
    $_POST=array('token'=>system_install_token(),'backup_confirm'=>'1','writers_confirm'=>'1');
    $result=system_install_run('all');check(!$result['errors'],'fresh additive migrations');
    $result=system_install_run('inventory_engine','inventory_engine');check(!$result['errors'],'isolated inventory engine preparation');
    $result=system_install_run('all');check(!$result['errors'],'maze dependencies installed');
    $result=system_install_run('all');check(!$result['errors'] && !$result['success'],'migration rerun unchanged');
    check(!system_install_schema('ct_dungeon_member')&&!system_install_schema('ct_dungeon_maze_config')&&!system_install_schema('ct_dungeon_state_monster'),'no retired direct-battle tables on new installs');
    $raid=42;addrow('monster_master',array('name'=>'기존 레이드','image'=>'https://example.test/'.str_repeat('a',300).'.png','description'=>'레이드 안내','enabled'=>1,'raid_id'=>$raid,'profile'=>json_encode(array('dg_mon_hp'=>500,'k_mp'=>0,'k_raid_type'=>'realtime','k_stats'=>array_fill(0,10,0),'k_pattern'=>1,'k_default_act'=>1),JSON_UNESCAPED_UNICODE)));
    $master=sql_fetch('SELECT * FROM ct_monster_master WHERE raid_id='.$raid);check($master && (int)$master['raid_id']===$raid && (int)sql_fetch('SELECT COUNT(*) n FROM ct_monster_master')['n']===1,'one common record exposes the K raid ID');
    check(strlen($master['image'])>255,'existing Raid image URL retained without truncation');
    require G5_PATH.'/k_battle/extend/raid/0_default.php';
    function ses($a,$k,$default='',$type=''){return $a[$k]??$default;}
    $g5['k_monster_table']='ct_k_battle_monster';$battle_table='ct_k_battle_realtime';$k_stat=array();$kb_cf=array('mp'=>0);
    check(count(get_k_monster_list('*','realtime'))===1,'realtime picker uses linked enabled master');
    check(insert_k_battle_unit($raid,'mo','realtime','fixture'),'realtime registration preserves K monster ID');
    $unit=sql_fetch("SELECT * FROM ct_k_battle_realtime_unit WHERE ra_id='fixture'");check((int)$unit['unit_id']===$raid && (int)$unit['hp_now']===500,'realtime monster HP snapshot unchanged');
    sql_query('UPDATE ct_monster_master SET enabled=0 WHERE raid_id='.$raid);
    check(!get_k_monster_list('*','realtime') && !insert_k_battle_unit($raid,'mo','realtime','second'),'disabled master excluded from picker and direct registration');
    check((int)get_k_monster($raid)['mo_id']===$raid && sql_fetch('SELECT rm_id FROM ct_k_battle_realtime_unit WHERE rm_id='.(int)$unit['rm_id']),'existing realtime unit and K profile retained after disable');
    delete_k_battle_unit($unit['rm_id']);check(!sql_fetch('SELECT rm_id FROM ct_k_battle_realtime_unit WHERE rm_id='.(int)$unit['rm_id']) && get_k_monster($raid),'Raid unit removal retains Monster Master and K profile');
    sql_query('UPDATE ct_monster_master SET enabled=1 WHERE raid_id='.$raid);
    $spec=json_decode(file_get_contents(G5_PATH.'/install/system/dungeon/schema-1.0.0.json'),true)['dungeon']['columns'];$profile=array();foreach($spec as $key=>$ddl)$profile[$key]=strpos($ddl,'int(')===0?0:'';
    $profile['dg_mon_hp']=100;$profile['dg_d_attack_min']=2;$profile['dg_d_attack_max']=5;
    sql_query('UPDATE ct_monster_master SET profile='.monster_quote(json_encode($profile)).' WHERE monster_id='.(int)$master['monster_id']);
    $boss=addrow('monster_master',array('name'=>'문지기','enabled'=>1,'profile'=>json_encode($profile)));
    $dg=addrow('dungeon',array('dg_title'=>'검증 던전','dg_description'=>'공용 출현 검증'));$ds=addrow('dungeon_state',array('dg_id'=>$dg,'ds_state'=>'S'));
    sql_query('INSERT INTO ct_dungeon_monster_pool (dg_id,monster_id,weight,enabled,is_boss) VALUES ('.$dg.','.$master['monster_id'].',1,1,0),('.$dg.','.$boss.',100,1,1)');
    $read=sql_fetch('SELECT * FROM ct_dungeon_monster_read WHERE dg_id='.$dg);
    check($read['dg_mon_name']==='문지기','representative monster comes from the common pool, boss first');
    $g5['dungeon_item_table']='ct_dungeon_item';
    addrow('dungeon_item',array('dg_id'=>$dg,'it_id'=>12,'di_count'=>2,'di_percent'=>30.25));
    check(dungeon_rewards($dg)===array(array('it_id'=>12,'count'=>2,'percent'=>30.25)),'one reward definition preserves fractional percentages');
    check(!dungeon_reward_won(0,1)&&dungeon_reward_won(100,10000)&&dungeon_reward_won(30.25,3025)&&!dungeon_reward_won(30.25,3026),'reward thresholds include 0, 100 and fractional boundaries');
    $skill=array('sh_id'=>1,'sh_name'=>'','sk_name'=>'강격','sk_type'=>'액티브','sh_limit'=>0,'sk_use_st_id'=>9,'sl_use_value'=>4,'sk_target'=>'적','sk_function'=>'공격');
    $person=array('hp'=>20,'stats'=>'{"9":3}','effects'=>'{}','skills'=>json_encode(array($skill)));
    $session=array('snapshot'=>json_encode(array('hp_id'=>1,'stat_definitions'=>array(array('st_id'=>9,'st_name'=>'정신')))));
    check(maze_skill_available($session,$person,$skill)['reason']==='자원 부족','actual resource ID insufficient');$person['stats']='{"9":4}';check(maze_skill_available($session,$person,$skill)['reason']==='','actual resource sufficient');$skill['sh_limit']=2;check(strpos(maze_skill_available($session,$person,$skill)['reason'],'재사용')===0,'cooldown disabled');
    combat_event('maze',$ds,3,'아린','슬라임 A','강격',26,'DAMAGE');$event=sql_fetch('SELECT * FROM ct_dungeon_combat_event ORDER BY event_id DESC LIMIT 1');check($event['actor']==='아린' && $event['target']==='슬라임 A' && (int)$event['amount']===26 && (int)$event['turn_no']===3 && $event['created_at'],'structured combat log fields');
    echo "COMPLETE combat integration\n";
} finally {$db->query('DROP DATABASE `'.$database.'`');}
