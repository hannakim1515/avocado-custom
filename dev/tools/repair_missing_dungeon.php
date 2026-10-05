<?php
/** Explicitly retry a missing opening without shifting the configured cycle. */
if(PHP_SAPI!=='cli')exit(1);
date_default_timezone_set('Asia/Seoul');
$root=dirname(__DIR__,2);
define('_GNUBOARD_',true);define('G5_PATH',$root);define('G5_URL','http://localhost');
require $root.'/data/dbconfig.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);$db->set_charset('utf8mb4');$g5['connect_db']=$db;
function sql_query($s,$e=true){global $db;try{return $db->query($s);}catch(Throwable $x){if($e)throw $x;return false;}}
function sql_fetch($s,$e=true){$r=sql_query($s,$e);return $r?$r->fetch_assoc():null;}
function sql_fetch_array($r){return $r->fetch_assoc();}
function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}
function sql_insert_id(){return $GLOBALS['db']->insert_id;}
$g5['map_table']=G5_TABLE_PREFIX.'newmap_config';
require $root.'/extend/inventory_boundary.lib.php';
require $root.'/extend/dungeon_maze.lib.php';
require $root.'/extend/dungeon_maze_session.lib.php';
require $root.'/extend/dungeon.lib.php';
$config=sql_fetch('SELECT * FROM `'.$g5['config_table'].'`');
if(!maze_installed() || empty($config['cf_dungeon_open']) || (int)$config['cf_dungeon_count']<1)throw new RuntimeException('Dungeon opening is not enabled and ready.');
$when=$config['cf_dungeon_reset'];
if(!$when || $when==='0000-00-00 00:00:00')throw new RuntimeException('No current cycle to repair.');
$before=monster_rows('SELECT ds_id,ds_state,phase FROM `'.$g5['dungeon_state_table'].'` WHERE created_at>='.monster_quote($when).' OR ds_datetime>='.monster_quote($when));
if($before){echo json_encode(array('status'=>'already_generated','reset'=>$when,'instances'=>$before)),PHP_EOL;exit;}
if(($argv[1]??'')!=='--apply'){echo json_encode(array('status'=>'missing','reset'=>$when,'apply'=>'--apply')),PHP_EOL;exit;}
// Back up every table and view before changing the live database. Credentials
// are passed through the child environment, never the command or tool output.
$directory=$root.'/artifacts/db-backups';if(!is_dir($directory))mkdir($directory,0700,true);
$backup=$directory.'/'.G5_MYSQL_DB.'-before-dungeon-opening-repair-'.date('Ymd-His').'.sql';
$dump='C:/xampp/mysql/bin/mysqldump.exe';if(!is_file($dump))throw new RuntimeException('Backup executable is unavailable.');
$command=array($dump,'--single-transaction','--routines','--events','--triggers','--hex-blob','--host='.G5_MYSQL_HOST,'--user='.G5_MYSQL_USER,'--result-file='.$backup,G5_MYSQL_DB);
$environment=getenv();$environment['MYSQL_PWD']=G5_MYSQL_PASSWORD;
$process=proc_open($command,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root,$environment);
if(!is_resource($process))throw new RuntimeException('Cannot start backup.');
fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);
if($code!==0 || !is_file($backup) || filesize($backup)<100)throw new RuntimeException('Backup failed; opening was not changed. '.$error);
set_reset_dungeon(true);
$after=monster_rows('SELECT ds_id,dg_id,dg_title,ds_ma_id,ds_state,phase FROM `'.$g5['dungeon_state_table'].'` WHERE created_at>='.monster_quote($when).' OR ds_datetime>='.monster_quote($when));
echo json_encode(array('status'=>$after?'generated':'no_eligible_dungeon','backup'=>$backup,'reset_before'=>$when,'reset_after'=>$config['cf_dungeon_reset'],'instances'=>$after),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
