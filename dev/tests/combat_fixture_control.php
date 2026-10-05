<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);$f=json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'),true);
if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',$f['database']))throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root','',$f['database'],33381);$db->set_charset('utf8mb4');
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Wrong server');
define('_GNUBOARD_',true);define('G5_PATH',$root);require $f['site'].'/data/dbconfig.php';$g5['connect_db']=$db;
function sql_query($s,$e=true){global $db;try{return $db->query($s);}catch(Throwable $x){if($e)throw $x;return false;}}
function sql_fetch($s,$e=true){$r=sql_query($s,$e);return $r?$r->fetch_assoc():null;}function sql_fetch_array($r){return $r->fetch_assoc();}function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}function sql_insert_id(){return $GLOBALS['db']->insert_id;}
require $root.'/lib/system_installer.lib.php';require $root.'/lib/monster.lib.php';require $root.'/extend/inventory_boundary.lib.php';require $root.'/extend/dungeon_maze.lib.php';
$ds=(int)$f['ds_id'];$op=$argv[1]??'bootstrap';
if($op==='update_encounters') {
    $is_admin='super';$_SESSION=array();$_SERVER['REQUEST_METHOD']='POST';$_POST=array('token'=>system_install_token());
    foreach(array('monsters','labyrinth') as $module){$r=system_install_run($module);if($r['errors'] || $r['skipped'])throw new RuntimeException(implode('; ',array_merge($r['errors'],$r['skipped'])));}
    echo '{}';exit;
} elseif($op==='bootstrap') {
    if(!sql_fetch('SELECT sk_id FROM web_skill LIMIT 1')) {
        sql_query(system_install_default_row('skill',array('sk_name'=>'강격','sk_type'=>'액티브','sk_function'=>'공격','sk_target'=>'적','sk_use_st_id'=>$f['mp_id'],'sk_status_code'=>'공격','sk_limit'=>2)));
        $skill=sql_insert_id();sql_query(system_install_default_row('skill_level',array('sk_id'=>$skill,'sl_level'=>1,'sl_use_value'=>4)));
        foreach($f['characters'] as $ch)sql_query(system_install_default_row('skill_has',array('sk_id'=>$skill,'ch_id'=>$ch,'sh_use'=>1,'sh_level'=>1)));
    }
    if(!sql_fetch('SELECT 1 FROM web_css_config LIMIT 1'))sql_query(system_install_default_row('css_config',array()));
    foreach(array('공격'=>26,'치유'=>18,'방어'=>20,'방어력'=>0,'자동회피'=>-1) as $name=>$value)if(!sql_fetch('SELECT ex_id FROM web_status_extra WHERE ex_name='.monster_quote($name)))sql_query(system_install_default_row('status_extra',array('ex_name'=>$name,'ex_main_min'=>$value,'ex_main_max'=>$value)));
    $dg=sql_fetch('SELECT * FROM web_dungeon WHERE dg_id='.(int)$f['dg_id']);$settings=maze_defaults($dg);$settings['background']='http://127.0.0.1:38082/fixture-background.svg';$settings['encounter_percent']=100;$settings['rooms_min']=5;$settings['rooms_max']=5;$settings['branches_min']=1;$settings['branches_max']=1;
    foreach(array('title','description','max_players','points','rewards','monsters') as $key)unset($settings[$key]);
    sql_query('UPDATE web_dungeon SET dg_settings='.monster_quote(maze_json($settings)).' WHERE dg_id='.(int)$f['dg_id']);
} elseif($op==='target_mode') {
    $mode=$argv[2]??'SINGLE';if(!in_array($mode,array('SINGLE','ALL','RANDOM'),true))throw new RuntimeException('Invalid mode');
    $skill=sql_fetch('SELECT sk_id FROM web_skill LIMIT 1');sql_query('UPDATE web_skill SET sk_dungeon_target_mode='.monster_quote($mode).' WHERE sk_id='.(int)$skill['sk_id']);sql_query('UPDATE web_skill_has SET sh_limit=0');
} elseif($op==='resource') {
    $row=sql_fetch('SELECT * FROM web_dungeon_maze_member WHERE ds_id='.$ds.' AND ch_id='.(int)$f['characters']['fixture_admin']);$stats=maze_data($row['stats']);$stats[$f['mp_id']]=(int)($argv[2]??0);maze_update('member',array('stats'=>maze_json($stats)),'dm_id='.(int)$row['dm_id']);
} elseif($op==='inspect') {
    echo json_encode(array('session'=>sql_fetch('SELECT phase,version,room_id,battle_id FROM web_dungeon_state WHERE ds_id='.$ds),'party'=>monster_rows('SELECT dm_id,hp,stats,ready,state FROM web_dungeon_maze_member WHERE ds_id='.$ds),'combat'=>monster_rows('SELECT * FROM web_dungeon_combat_event WHERE ds_id='.$ds)) ,JSON_UNESCAPED_UNICODE);
} elseif($op==='digest') {
    $values=array();foreach(array('dungeon_state','dungeon_maze_member','dungeon_maze_log','dungeon_maze_action','dungeon_maze_battle','dungeon_combat_event','inventory','member','point') as $table)$values[$table]=monster_rows('SELECT * FROM web_'.$table);echo hash('sha256',json_encode($values));
} else {throw new RuntimeException('Unknown fixture operation: '.$op);}
