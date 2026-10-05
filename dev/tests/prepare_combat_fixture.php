<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);$fixture=json_decode(file_get_contents($root.'/artifacts/system-installer-web.json'),true);
if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',$fixture['database']))throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli('127.0.0.1','root','',$fixture['database'],33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Fixture server only');
$db->set_charset('utf8mb4');$db->query("SET SESSION sql_mode=''");
define('_GNUBOARD_',true);define('G5_PATH',$root);require $fixture['site'].'/data/dbconfig.php';$g5['connect_db']=$db;
require $root.'/lib/system_installer.lib.php';
require $root.'/lib/monster.lib.php';
function put($table,$values){system_install_query(system_install_default_row($table,$values));return (int)$GLOBALS['db']->insert_id;}
$db->query("UPDATE web_config SET cf_theme='',cf_dungeon_open=1,cf_dungeon_count=0,cf_dungeon_time=99999,cf_dungeon_enter=100,cf_dungeon_reset=NOW(),cf_open=1");
// Remove only named disposable probes; their DDL was for installer isolation tests.
foreach(array('/extend/zz_installer_fixture_probe.php','/theme/installer_fixture/theme.config.php') as $path)if(is_file($fixture['site'].$path))unlink($fixture['site'].$path);
$hp=put('status',array('st_name'=>'HP','st_use_hp'=>1,'st_max'=>100,'st_min'=>0));$mp=put('status',array('st_name'=>'정신','st_max'=>20,'st_min'=>0));
$db->query('UPDATE web_k_battle_plugin_config SET mp='.$mp.' WHERE cf_id=1');
foreach(array('fixture_admin'=>'아린','fixture_member'=>'세라') as $mb=>$name){$ch=put('character',array('ch_name'=>$name,'mb_id'=>$mb,'ch_state'=>'승인','ch_skill_slot'=>2));$db->query("UPDATE web_member SET ch_id=$ch WHERE mb_id='$mb'");put('status_character',array('ch_id'=>$ch,'st_id'=>$hp,'sc_max'=>100,'sc_value'=>100));put('status_character',array('ch_id'=>$ch,'st_id'=>$mp,'sc_max'=>20,'sc_value'=>20));$characters[$mb]=$ch;}
$profile=array();foreach(monster_dungeon_legacy_fields() as $key=>$ddl)$profile[$key]=strpos($ddl,'int(')===0?0:'';
$profile=array_replace($profile,array('dg_mon_name'=>'슬라임','dg_mon_hp'=>80,'dg_d_attack_turn'=>1,'dg_d_attack_count'=>1,'dg_d_attack_min'=>8,'dg_d_attack_max'=>8,'dg_d_attack_comment'=>'몸통박치기'));
$monster=put('monster_master',array('name'=>'슬라임','enabled'=>1,'profile'=>json_encode($profile,JSON_UNESCAPED_UNICODE)));
$exploration=array('background'=>'http://127.0.0.1:38082/fixture-background.svg','min_players'=>1,'rooms_min'=>5,'rooms_max'=>5,'branches_min'=>1,'branches_max'=>1,'weights'=>array('EMPTY'=>3,'SEARCH'=>3,'TREASURE'=>2,'TRAP'=>1),'dead_end_percent'=>20,'encounter_percent'=>100,'escape_percent'=>0,'trap_damage'=>10,'boss'=>false,'events'=>array());
$dg=put('dungeon',array('dg_title'=>'하늘의 잔해','dg_count'=>4,'dg_use'=>1,'dg_per_e'=>100,'dg_description'=>'흩어진 하늘 조각 사이, 파티와 함께 길을 찾으세요.','dg_settings'=>json_encode($exploration,JSON_UNESCAPED_UNICODE)));
put('dungeon_monster_pool',array('dg_id'=>$dg,'monster_id'=>$monster,'weight'=>1,'enabled'=>1,'is_boss'=>0));
$definitions=system_install_rows('SELECT * FROM web_status');$codes=array();foreach(array('공격','방어','치유','방어력') as $name)$codes[]=array('ex_name'=>$name,'ex_value'=>$name==='공격'?26:($name==='치유'?18:0));
$settings=array_replace($exploration,array('title'=>'하늘의 잔해','description'=>'흩어진 하늘 조각 사이, 파티와 함께 길을 찾으세요.','max_players'=>4,'hp_id'=>$hp,'mp_id'=>$mp,'stat_definitions'=>$definitions,'status_registry'=>array(),'attack_code'=>'공격','defense_code'=>'방어력','guard_code'=>'방어','heal_code'=>'치유','codes'=>$codes,'points'=>0,'rewards'=>array(),'reward_items'=>array()));
$skill=array('sh_id'=>1,'sh_name'=>'','sk_name'=>'강격','sk_type'=>'액티브','sh_limit'=>0,'sk_use_st_id'=>$mp,'sl_use_value'=>4,'sk_target'=>'적','sk_function'=>'공격','sk_status_code'=>'공격','sl_set_value'=>0,'sk_def_enermy'=>'','sk_def_type'=>'','sk_keep_limit'=>0,'sk_limit'=>1);
$ds=put('dungeon_state',array('dg_id'=>$dg,'dg_title'=>'하늘의 잔해','ds_state'=>'S','phase'=>'WAITING','snapshot'=>json_encode($settings,JSON_UNESCAPED_UNICODE),'created_at'=>date('Y-m-d H:i:s')));
foreach($characters as $mb=>$ch){$stats=array($hp=>100,$mp=>20,'_maximum'=>array($hp=>100,$mp=>20));put('dungeon_maze_member',array('ds_id'=>$ds,'ch_id'=>$ch,'mb_id'=>$mb,'name'=>$mb==='fixture_admin'?'아린':'세라','state'=>'ACTIVE','ready'=>0,'hp'=>100,'max_hp'=>100,'stats'=>json_encode($stats),'skills'=>json_encode(array($skill),JSON_UNESCAPED_UNICODE),'effects'=>'{}','joined_at'=>date('Y-m-d H:i:s')));}
$fixture['ds_id']=$ds;$fixture['dg_id']=$dg;$fixture['hp_id']=$hp;$fixture['mp_id']=$mp;$fixture['characters']=$characters;
file_put_contents($root.'/artifacts/combat-fixture.json',json_encode($fixture,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
file_put_contents($fixture['site'].'/fixture-background.svg','<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900"><defs><linearGradient id="sky" x2="0" y2="1"><stop stop-color="#a6c4ef"/><stop offset="1" stop-color="#eaf3ff"/></linearGradient></defs><rect width="1200" height="900" fill="url(#sky)"/><g fill="#c8d7ee" stroke="#8facd5" stroke-width="4"><path d="M90 900V210h90v690M260 900V80h70v820M980 900V110h110v790M840 900V310h70v590"/><path d="M180 370Q600 -130 980 370v50Q600 -70 180 420z"/></g><path d="M0 740H1200M0 810H1200M450 740L350 900M750 740L850 900" stroke="#fff" stroke-width="3"/></svg>');
echo json_encode(array('ds_id'=>$ds,'characters'=>$characters));
