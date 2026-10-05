<?php
/** Inspect by default; --apply backs up the configured database then upgrades. */
if(PHP_SAPI!=='cli')exit(1);
date_default_timezone_set('Asia/Seoul');
$root=dirname(__DIR__,2);define('_GNUBOARD_',true);define('G5_PATH',$root);
require $root.'/data/dbconfig.php';
mysqli_report(MYSQLI_REPORT_OFF);
$db=@new mysqli(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);
if($db->connect_errno)throw new RuntimeException('Database connection failed.');
$db->set_charset('utf8mb4');$g5['connect_db']=$db;
require $root.'/lib/system_installer.lib.php';
$targets=array('monsters','labyrinth');$status=system_install_all_status();$plan=array();
foreach($targets as $module)$plan[$module]=array('version'=>$status[$module]['version'],'target'=>system_install_registry()[$module]['version'],'installed'=>$status[$module]['installed'],'missing'=>$status[$module]['missing'],'manual'=>$status[$module]['manual'],'blocked'=>$status[$module]['blocked']);
if(($argv[1]??'')!=='--apply'){echo json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;exit;}
if($status['monsters']['installed'] && $status['labyrinth']['installed']){echo json_encode(array('status'=>'already_current','modules'=>$plan),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;exit;}
foreach($targets as $module)if($status[$module]['manual'])throw new RuntimeException('Manual schema review required: '.$module);
if($status['monsters']['blocked'])throw new RuntimeException('Monster dependencies are not ready.');
$directory=$root.'/artifacts/db-backups';if(!is_dir($directory))mkdir($directory,0700,true);
$backup=$directory.'/'.G5_MYSQL_DB.'-before-dungeon-encounter-update-'.date('Ymd-His').'.sql';
$dump='C:/xampp/mysql/bin/mysqldump.exe';if(!is_file($dump))throw new RuntimeException('Backup executable is unavailable.');
$command=array($dump,'--single-transaction','--routines','--events','--triggers','--hex-blob','--host='.G5_MYSQL_HOST,'--user='.G5_MYSQL_USER,'--result-file='.$backup,G5_MYSQL_DB);
$environment=getenv();$environment['MYSQL_PWD']=G5_MYSQL_PASSWORD;
$process=proc_open($command,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$root,$environment);
if(!is_resource($process))throw new RuntimeException('Cannot start backup.');
fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);
if($code!==0 || !is_file($backup) || filesize($backup)<100)throw new RuntimeException('Backup failed; schema was not changed.');
// Compare the original columns, excluding only newly added default fields.
$preserved=array();
foreach(array('dungeon','dungeon_monster_pool','dungeon_item','monster_master','monster_runtime','dungeon_state','dungeon_maze_member','dungeon_maze_battle','dungeon_maze_action','dungeon_maze_log','dungeon_combat_event','dungeon_maze_inventory','dungeon_maze_reward') as $suffix) {
    $schema=system_install_schema(system_install_table($suffix));if(!$schema)throw new RuntimeException('Expected table missing: '.$suffix);
    $fields=implode(',',array_map('system_install_ident',array_keys($schema['columns'])));
    $order=implode(',',array_map('system_install_ident',$schema['indexes']['PRIMARY']['columns']));
    $query='SELECT '.$fields.' FROM '.system_install_ident(system_install_table($suffix)).' ORDER BY '.$order;
    $preserved[$suffix]=array('query'=>$query,'hash'=>hash('sha256',json_encode(system_install_rows($query))));
}
$is_admin='super';$_SESSION=array();$_SERVER['REQUEST_METHOD']='POST';$_POST=array('token'=>system_install_token());$updates=array();
foreach($targets as $module) {
    $result=system_install_run($module);if($result['errors'])throw new RuntimeException(implode('; ',$result['errors']));
    $verified=system_install_all_status()[$module];
    if(!$verified['installed'] || $verified['version']!==system_install_registry()[$module]['version'])throw new RuntimeException('Upgrade incomplete: '.$module);
    $updates[$module]=array('version'=>$verified['version'],'changes'=>$result['success']);
}
foreach($preserved as $suffix=>$original)if(hash('sha256',json_encode(system_install_rows($original['query'])))!==$original['hash'])throw new RuntimeException('Existing rows changed during upgrade: '.$suffix.'; backup: '.$backup);
echo json_encode(array('backup'=>$backup,'updates'=>$updates,'existing_rows'=>'preserved'),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
