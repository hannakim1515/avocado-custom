<?php
/** Inspect by default; repair untouched, unlisted event encounters after backup. */
if(PHP_SAPI!=='cli')exit(1);
date_default_timezone_set('Asia/Seoul');$root=dirname(__DIR__,2);
define('_GNUBOARD_',true);define('G5_PATH',$root);
$fixture_mode=in_array('--fixture',$argv,true);$port=$fixture_mode?33381:3306;
if($fixture_mode){$fixture=json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'),true);require $fixture['site'].'/data/dbconfig.php';if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',G5_MYSQL_DB))throw new RuntimeException('Fixture database required');}
else require $root.'/data/dbconfig.php';
$host=G5_MYSQL_HOST;if(preg_match('/^([^:]+):(\d+)$/D',$host,$address)){$host=$address[1];$port=(int)$address[2];}
if($fixture_mode&&$port!==33381)throw new RuntimeException('Disposable port required');
mysqli_report(MYSQLI_REPORT_OFF);$db=@new mysqli($host,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB,$port);
if($db->connect_errno)throw new RuntimeException('Database connection failed.');$db->set_charset('utf8mb4');$g5['connect_db']=$db;
require $root.'/lib/system_installer.lib.php';
if($fixture_mode&&strpos(system_install_one('SELECT @@datadir d')['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable server required');
function sql_query($query,$error=true){try{return system_install_query($query);}catch(Throwable $e){if($error)throw $e;return false;}}
function sql_fetch($query,$error=true){$r=sql_query($query,$error);return $r?$r->fetch_assoc():null;}
function sql_fetch_array($r){return $r->fetch_assoc();}
function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}
function sql_insert_id(){return $GLOBALS['db']->insert_id;}
require $root.'/extend/inventory_boundary.lib.php';require $root.'/extend/dungeon_maze.lib.php';
function repair_event_pool($dg){return system_install_rows('SELECT p.* FROM '.system_install_ident(system_install_table('dungeon_monster_pool')).' p JOIN '.system_install_ident(system_install_table('monster_master')).' m ON m.monster_id=p.monster_id WHERE p.dg_id='.(int)$dg.' AND p.enabled=1 AND m.enabled=1 ORDER BY p.monster_id');}
function repair_bad_event_indexes($settings,$pool){$bad=array();foreach($settings['events']??array() as $i=>$event)if(!empty($event['enabled'])&&empty($event['choices'])&&!maze_event_result_allowed($event,$pool))$bad[]=$i;return $bad;}
function repair_event_encounter($s,$lock=false){
    if($s['phase']!=='BATTLE'||!$s['battle_id'])return null;
    $room=system_install_one('SELECT * FROM '.system_install_ident(maze_table('room')).' WHERE ds_id='.(int)$s['ds_id'].' AND room_id='.(int)$s['room_id'].($lock?' FOR UPDATE':''));
    if(!$room||$room['is_boss'])return null;
    $payload=maze_data($room['payload']);$event=$payload['event']??null;if(!$event)return null;
    $settings=maze_data($s['snapshot']);$pool=$settings['monsters']??array();
    $units=system_install_rows('SELECT * FROM '.system_install_ident(maze_table('battle')).' WHERE ds_id='.(int)$s['ds_id'].' AND (battle_id='.(int)$s['battle_id'].' OR encounter_id='.(int)$s['battle_id'].') ORDER BY battle_id'.($lock?' FOR UPDATE':''));
    if(!$units)return null;$eligible=true;
    foreach($units as $unit){$monster=maze_data($unit['monster']);$id=(int)($monster['monster_id']??0);if(!$id||maze_pool_monster($id,$pool)||$unit['state']!=='ACTIVE')return null;
        $results=$event['choices']??array($event);$matched=false;foreach($results as $result)if(($result['type']??'')==='MONSTER'&&maze_event_monster_id($result)===$id)$matched=true;if(!$matched)return null;
        if((int)$unit['turn_no']!==1||(int)$unit['hp']!==(int)$monster['dg_mon_hp'])$eligible=false;
    }
    if((int)system_install_one('SELECT COUNT(*) n FROM '.system_install_ident(maze_table('action')).' WHERE battle_id='.(int)$s['battle_id'])['n'])$eligible=false;
    return array('room'=>$room,'units'=>$units,'eligible'=>$eligible);
}
function repair_party_digest($id){$values=array();foreach(array('member'=>'dm_id','inventory'=>'inventory_id','reward'=>'dm_id,reward_type') as $table=>$order)$values[$table]=system_install_rows('SELECT * FROM '.system_install_ident(maze_table($table)).' WHERE ds_id='.(int)$id.' ORDER BY '.$order);return hash('sha256',json_encode($values));}
$definition_changes=array();$battle_changes=array();$blocked=array();
foreach(system_install_rows('SELECT dg_id,dg_settings FROM '.system_install_ident(system_install_table('dungeon')).' ORDER BY dg_id') as $dg){$settings=maze_data($dg['dg_settings']);$bad=repair_bad_event_indexes($settings,repair_event_pool($dg['dg_id']));if($bad)$definition_changes[]=array('dg_id'=>(int)$dg['dg_id'],'event_numbers'=>array_map(function($i){return $i+1;},$bad));}
foreach(system_install_rows('SELECT * FROM '.system_install_ident(maze_table('session'))." WHERE phase='BATTLE' ORDER BY ds_id") as $s){$encounter=repair_event_encounter($s);if(!$encounter)continue;$entry=array('ds_id'=>(int)$s['ds_id'],'battle_id'=>(int)$s['battle_id']);if($encounter['eligible'])$battle_changes[]=$entry;else $blocked[]=$entry;}
$report=array('disable_events'=>$definition_changes,'cancel_untouched_battles'=>$battle_changes,'requires_review'=>$blocked);
if(!in_array('--apply',$argv,true)||(!$definition_changes&&!$battle_changes)){echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;exit;}
$directory=$root.'/artifacts/db-backups';if(!is_dir($directory))mkdir($directory,0700,true);
$backup=$directory.'/'.G5_MYSQL_DB.'-before-unlisted-events-repair-'.date('Ymd-His').'.sql';
$environment=getenv();$environment['MYSQL_PWD']=G5_MYSQL_PASSWORD;
$command=array('C:/xampp/mysql/bin/mysqldump.exe','--single-transaction','--routines','--events','--triggers','--hex-blob','--host='.$host,'--port='.$port,'--user='.G5_MYSQL_USER,'--result-file='.$backup,G5_MYSQL_DB);
$process=proc_open($command,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root,$environment);if(!is_resource($process))throw new RuntimeException('Cannot start backup.');
fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);
if($code!==0||!is_file($backup)||filesize($backup)<100)throw new RuntimeException('Backup failed; no repair performed. '.(G5_MYSQL_PASSWORD!==''?str_replace(G5_MYSQL_PASSWORD,'[redacted]',$error):$error));
system_install_query('START TRANSACTION');$applied=array('events'=>array(),'battles'=>array());
try{
    foreach($definition_changes as $change){$dg=system_install_one('SELECT dg_settings FROM '.system_install_ident(system_install_table('dungeon')).' WHERE dg_id='.$change['dg_id'].' FOR UPDATE');$settings=maze_data($dg['dg_settings']);$bad=repair_bad_event_indexes($settings,repair_event_pool($change['dg_id']));foreach($bad as $i)$settings['events'][$i]['enabled']=false;if($bad){system_install_query('UPDATE '.system_install_ident(system_install_table('dungeon')).' SET dg_settings='.system_install_quote(maze_json($settings)).' WHERE dg_id='.$change['dg_id']);$applied['events'][]=$change['dg_id'];}}
    foreach($battle_changes as $change){$id=$change['ds_id'];$s=system_install_one('SELECT * FROM '.system_install_ident(maze_table('session')).' WHERE ds_id='.$id.' FOR UPDATE');$encounter=repair_event_encounter($s,true);if(!$encounter||!$encounter['eligible']){continue;}
        $before=repair_party_digest($id);$room=$encounter['room'];$payload=maze_data($room['payload']);$payload['event']=null;
        foreach($encounter['units'] as $unit)maze_update('battle',array('state'=>'CLOSED'),'battle_id='.(int)$unit['battle_id']);
        maze_update('room',array('resolved'=>1,'encounter_done'=>1,'result_text'=>'','payload'=>maze_json($payload)),'ds_id='.$id.' AND room_id='.(int)$room['room_id']);
        maze_update('session',array('phase'=>'EXPLORE','battle_id'=>0,'version'=>(int)$s['version']+1),'ds_id='.$id);
        maze_log($id,0,'ADMIN','출현 목록에 없는 이벤트 몬스터 전투를 취소했습니다. 탐험을 계속할 수 있습니다.');
        if(repair_party_digest($id)!==$before)throw new RuntimeException('Participant or escrow changed during repair.');$applied['battles'][]=$id;
    }
    system_install_query('COMMIT');
}catch(Throwable $e){system_install_query('ROLLBACK');throw $e;}
echo json_encode(array('backup'=>$backup,'applied'=>$applied,'requires_review'=>$blocked),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
