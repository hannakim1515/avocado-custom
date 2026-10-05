<?php
/** Exercise migration from split definitions and active exploration sessions. */
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable DB required');
$name='codex_unify_'.bin2hex(random_bytes(5));$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','un_');$g5=array('connect_db'=>$db);
require dirname(__DIR__,2).'/lib/system_installer.lib.php';require dirname(__DIR__,2).'/lib/dungeon_unify_migration.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
    $contract=system_install_contract();
    foreach(array('dungeon','dungeon_item','dungeon_state') as $suffix)system_install_query(system_install_create($suffix,$contract['dungeon'][$suffix]));
    foreach(array('monster_master','dungeon_monster_pool') as $suffix)system_install_query(system_install_create($suffix,$contract['monsters'][$suffix]));
    $old=json_decode(file_get_contents(dirname(__DIR__,2).'/install/system/labyrinth/schema-1.5.0.json'),true);
    foreach(array('dungeon_maze_config','dungeon_maze_session','dungeon_maze_member','dungeon_maze_inventory') as $suffix)system_install_query(system_install_create($suffix,$old[$suffix]));
    foreach(array('dungeon_member','dungeon_log','dungeon_state_monster','dungeon_monster_config') as $suffix)system_install_query('CREATE TABLE `un_'.$suffix.'` (id INT PRIMARY KEY) ENGINE=InnoDB');
    system_install_query('ALTER TABLE un_dungeon_item ADD di_per_s INT DEFAULT 0,ADD di_per_e INT DEFAULT 0');
    system_install_query('ALTER TABLE un_dungeon_state ADD ds_hp INT DEFAULT 0,ADD ds_hurt INT DEFAULT 0,ADD ds_weak_count INT DEFAULT 0,ADD ds_weak_turn INT DEFAULT 0');
    system_install_query("INSERT INTO un_dungeon (dg_id,dg_title,dg_count) VALUES (1,'설정된 던전',4),(2,'일반 던전',1)");
    system_install_query("INSERT INTO un_monster_master (monster_id,name,profile) VALUES (8,'공용','{\"dg_mon_hp\":120}')");
    system_install_query('INSERT INTO un_dungeon_item (dg_id,it_id,di_count,di_per_s,di_per_e) VALUES (1,12,1,0,49),(2,13,1,0,49)');
    $settings=array('rooms_min'=>9,'rooms_max'=>15,'custom'=>'유지','monsters'=>array(array('monster_id'=>8,'weight'=>3,'enabled'=>true,'boss'=>false)),
        'rewards'=>array(array('it_id'=>12,'count'=>2,'percent'=>30.25)));
    system_install_query('INSERT INTO un_dungeon_maze_config VALUES (1,'.system_install_quote(json_encode($settings,JSON_UNESCAPED_UNICODE)).')');
    system_install_query("INSERT INTO un_dungeon_state (ds_id,dg_id,ds_state) VALUES (7,1,'S'),(9,2,'E')");
    $snapshot=json_encode(array('title'=>'출발 시 이름','points'=>10,'rewards'=>array(array('it_id'=>13,'count'=>1,'percent'=>100)),'custom'=>"원본 '스냅샷'"),JSON_UNESCAPED_UNICODE);
    system_install_query('INSERT INTO un_dungeon_maze_session (ds_id,dg_id,phase,version,room_id,battle_id,snapshot,created_at) VALUES (7,1,\'BATTLE\',11,3,18,'.system_install_quote($snapshot).',NOW())');
    system_install_query("INSERT INTO un_dungeon_maze_member (dm_id,ds_id,ch_id,mb_id,name,stats,skills,effects,joined_at) VALUES (4,7,2,'test','참가자','{}','{}','{}',NOW())");
    system_install_query("INSERT INTO un_dungeon_maze_inventory (inventory_id,ds_id,dm_id,ch_id,it_id,origin,original_row,item_snapshot,original_id,active_original_id) VALUES (5,7,4,2,12,'ORIGINAL','보관 원본','아이템 원본',34,34)");
    $member=system_install_one('SELECT * FROM un_dungeon_maze_member');$inventory=system_install_one('SELECT * FROM un_dungeon_maze_inventory');
    $session=system_install_one('SELECT * FROM un_dungeon_maze_session WHERE ds_id=7');
    $bad=$settings;$bad['rewards'][0]['percent']=101;
    system_install_query('UPDATE un_dungeon_maze_config SET settings='.system_install_quote(json_encode($bad)).' WHERE dg_id=1');
    $failed=false;$log=array();try{system_install_dungeon_unify($log);}catch(RuntimeException $e){$failed=true;}
    check($failed&&system_install_schema('un_dungeon_maze_config')&&system_install_one('SELECT di_count FROM un_dungeon_item WHERE dg_id=1')['di_count']==='1','invalid import rolls back data and preserves import tables');
    system_install_query('UPDATE un_dungeon_maze_config SET settings='.system_install_quote(json_encode($settings,JSON_UNESCAPED_UNICODE)).' WHERE dg_id=1');
    system_install_dungeon_unify($log);
    $reward=system_install_one('SELECT * FROM un_dungeon_item WHERE dg_id=1');
    check($reward['di_count']==='2'&&$reward['di_percent']==='30.25','active exploration reward replaces stale direct-battle reward');
    check(system_install_one('SELECT di_percent FROM un_dungeon_item WHERE dg_id=2')['di_percent']==='49.50','inclusive legacy range converted to its actual percentage');
    $definition=json_decode(system_install_one('SELECT dg_settings FROM un_dungeon WHERE dg_id=1')['dg_settings'],true);
    check($definition===array('rooms_min'=>9,'rooms_max'=>15,'custom'=>'유지'),'settings retain exploration fields without reward or pool copies');
    check(system_install_one('SELECT weight FROM un_dungeon_monster_pool WHERE dg_id=1 AND monster_id=8')['weight']==='3','common pool carries the original encounter weight');
    $pool=system_install_one('SELECT min_count,max_count FROM un_dungeon_monster_pool WHERE dg_id=1');
    check($pool['min_count']==='1'&&$pool['max_count']==='1','imported appearances preserve one-unit default');
    system_install_query('UPDATE un_dungeon_monster_pool SET min_count=2,max_count=3 WHERE dg_id=1');
    $state=system_install_one('SELECT * FROM un_dungeon_state WHERE ds_id=7');
    foreach($session as $key=>$value)check($state[$key]===$value,'active session '.$key.' preserved');
    check($member===system_install_one('SELECT * FROM un_dungeon_maze_member')&&$inventory===system_install_one('SELECT * FROM un_dungeon_maze_inventory'),'participant, escrow original ID and original rows preserved byte for byte');
    foreach(array('dungeon_maze_config','dungeon_maze_session','dungeon_member','dungeon_log','dungeon_state_monster','dungeon_monster_config') as $suffix)check(!system_install_schema('un_'.$suffix),'retired '.$suffix.' removed');
    check(!isset(system_install_schema('un_dungeon_item')['columns']['di_per_s'])&&!isset(system_install_schema('un_dungeon_state')['columns']['ds_hp']),'retired probability ranges and aggregate HP columns removed');
    system_install_dungeon_unify($log);
    check($reward===system_install_one('SELECT * FROM un_dungeon_item WHERE dg_id=1')&&$state===system_install_one('SELECT * FROM un_dungeon_state WHERE ds_id=7'),'migration retry preserves reward values and active session bytes');
    check(system_install_one('SELECT min_count,max_count FROM un_dungeon_monster_pool WHERE dg_id=1')===array('min_count'=>'2','max_count'=>'3'),'unification retry retains actual encounter counts');
    echo "COMPLETE dungeon storage unification\n";
}finally{$db->query('DROP DATABASE `'.$name.'`');}
