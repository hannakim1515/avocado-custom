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
$op=$argv[1]??'inspect';$dg=(int)$f['dg_id'];$id=(int)($argv[2]??0);
if($op==='seed') {
    $row=sql_fetch('SELECT dg_settings AS settings FROM web_dungeon WHERE dg_id='.$dg);$settings=maze_data($row['settings']);
    $settings['admin_roundtrip_marker']=array('text'=>"원본 '설정' \\ 보존",'value'=>7);
    $settings['events']=array(array('enabled'=>true,'room_kind'=>'SEARCH','weight'=>3,'type'=>'TEXT','text'=>'기존 조사 문구','custom'=>array('keep'=>true),'choices'=>array(array('label'=>'기존 선택지','type'=>'TEXT','text'=>'기존 결과','custom'=>'보존'))));
    sql_query('UPDATE web_dungeon SET dg_settings='.monster_quote(maze_json($settings)).' WHERE dg_id='.$dg);
    $status=sql_fetch("SELECT status_id FROM web_dungeon_maze_status WHERE name='폼 유지 검증'");
    $values=array('name'=>'폼 유지 검증','description'=>'기존 상태 설명','duration'=>3,'enabled'=>1,'components'=>maze_json(array(array('type'=>'STAT_MODIFIER','stat_id'=>(int)$f['hp_id'],'mode'=>'percent','value'=>15,'custom'=>'효과 원본'))));
    if($status)maze_update('status',$values,'status_id='.(int)$status['status_id']);else maze_insert('status',$values);
    if($id){$p=sql_fetch('SELECT profile FROM web_monster_master WHERE monster_id='.$id);$profile=json_decode($p['profile'],true);$profile['admin_roundtrip_marker']='능력치 보존';sql_query('UPDATE web_monster_master SET profile='.monster_quote(json_encode($profile,JSON_UNESCAPED_UNICODE)).' WHERE monster_id='.$id);}
}
$row=sql_fetch('SELECT dg_settings AS settings FROM web_dungeon WHERE dg_id='.$dg);$p=sql_fetch('SELECT profile FROM web_monster_master WHERE monster_id='.$id);
echo json_encode(array('definition'=>sql_fetch('SELECT * FROM web_dungeon WHERE dg_id='.$dg),'settings'=>maze_data($row['settings']),'statuses'=>monster_rows("SELECT * FROM web_dungeon_maze_status WHERE name='폼 유지 검증'"),'profile'=>$p?json_decode($p['profile'],true):null),JSON_UNESCAPED_UNICODE);
