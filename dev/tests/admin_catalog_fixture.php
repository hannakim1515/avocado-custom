<?php
if(PHP_SAPI!=='cli')exit(1);$root=dirname(__DIR__,2);$f=json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'),true);
if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',$f['database']))throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root','',$f['database'],33381);$db->set_charset('utf8mb4');
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Wrong server');
define('_GNUBOARD_',true);define('G5_PATH',$root);require $f['site'].'/data/dbconfig.php';$g5['connect_db']=$db;
function sql_query($s,$e=true){return $GLOBALS['db']->query($s);}function sql_fetch($s,$e=true){return sql_query($s)->fetch_assoc();}function sql_fetch_array($r){return $r->fetch_assoc();}function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}function sql_insert_id(){return $GLOBALS['db']->insert_id;}
require $root.'/lib/system_installer.lib.php';require $root.'/lib/monster.lib.php';
$op=$argv[1]??'';$id=(int)($argv[2]??0);
if($op==='admin_battle_state') {
    $state=$argv[3]??'';
    if(!in_array($state,array('ACTIVE','WON','LOST','ESCAPED'),true)) throw new RuntimeException('Invalid fixture state');
    $row=sql_fetch('SELECT b.battle_id,b.state FROM web_dungeon_state s JOIN web_dungeon_maze_battle b ON b.battle_id=s.battle_id AND b.ds_id=s.ds_id WHERE s.ds_id='.$id);
    if(!$row)throw new RuntimeException('No fixture battle');
    sql_query('UPDATE web_dungeon_maze_battle SET state='.monster_quote($state).' WHERE battle_id='.(int)$row['battle_id']);
    echo json_encode($row);exit;
}
if($op==='region_prepare') {
    sql_query(system_install_default_row('newmap_config',array('ma_name'=>'Region root-zero','ma_parent'=>0,'ma_use'=>1,'ma_use_dungeon'=>1)));
    $region=(int)sql_insert_id();
    sql_query('UPDATE web_dungeon SET ma_ids='.monster_quote('||'.$region.'||').' WHERE dg_id=1');
    echo json_encode(array('region'=>$region));exit;
}
if($op==='maze_count'){echo json_encode(sql_fetch('SELECT COUNT(*) total FROM web_dungeon'));exit;}
if($op==='maze_definition'){echo json_encode(array('definition'=>sql_fetch('SELECT * FROM web_dungeon WHERE dg_id='.$id),'settings'=>sql_fetch('SELECT dg_settings FROM web_dungeon WHERE dg_id='.$id),'rewards'=>monster_rows('SELECT * FROM web_dungeon_item WHERE dg_id='.$id),'pool'=>monster_rows('SELECT * FROM web_dungeon_monster_pool WHERE dg_id='.$id)));exit;}
if($op==='update'){$is_admin='super';$_SESSION=array();$_SERVER['REQUEST_METHOD']='POST';$_POST=array('token'=>system_install_token());$r=system_install_run('monsters');if($r['errors'])throw new RuntimeException(implode(';',$r['errors']));echo json_encode(system_install_inspect('monsters'));exit;}
if($op==='monster_mark'){$row=sql_fetch('SELECT profile FROM web_monster_master WHERE monster_id='.$id);$p=json_decode($row['profile'],true);$p['test_preserve']='original metadata';sql_query('UPDATE web_monster_master SET profile='.monster_quote(json_encode($p)).' WHERE monster_id='.$id);}
if($op==='monster'||$op==='monster_mark'){echo json_encode(sql_fetch('SELECT * FROM web_monster_master WHERE monster_id='.$id));exit;}
if($op==='status_mark'){$s=sql_fetch('SELECT components FROM web_dungeon_maze_status WHERE status_id='.$id);$c=json_decode($s['components'],true);$c[0]['custom']='original component';sql_query('UPDATE web_dungeon_maze_status SET components='.monster_quote(json_encode($c)).' WHERE status_id='.$id);}
if($op==='status'||$op==='status_mark'){echo json_encode(sql_fetch('SELECT * FROM web_dungeon_maze_status WHERE status_id='.$id));exit;}
if($op==='pool'){echo json_encode(monster_rows('SELECT * FROM web_dungeon_monster_pool WHERE dg_id='.(int)$f['dg_id'].' AND enabled=1 ORDER BY monster_id'));exit;}
if($op==='pool_for'){echo json_encode(monster_rows('SELECT * FROM web_dungeon_monster_pool WHERE dg_id='.$id.' AND enabled=1 ORDER BY monster_id'));exit;}
