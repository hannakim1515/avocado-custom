<?php
/** Opening selection and atomic rotation, using a disposable MariaDB only. */
if(PHP_SAPI!=='cli')exit(1);
date_default_timezone_set('Asia/Seoul');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable DB required');
$name='codex_spawn_'.bin2hex(random_bytes(5));$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_PATH',dirname(__DIR__,2));define('G5_URL','http://fixture.invalid');define('G5_TABLE_PREFIX','sp_');
$g5=array('connect_db'=>$db,'config_table'=>'sp_config','map_table'=>'sp_map');
function sql_query($s,$e=true){global $db;try{return $db->query($s);}catch(Throwable $x){if($e)throw $x;return false;}}
function sql_fetch($s,$e=true){$r=sql_query($s,$e);return $r?$r->fetch_assoc():null;}
function sql_fetch_array($r){return $r->fetch_assoc();}
function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}
function maze_installed(){return true;}
function maze_expire_waiting(){} // No waiting participants in this selection fixture.
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
require G5_PATH.'/extend/inventory_boundary.lib.php';require G5_PATH.'/extend/dungeon.lib.php';
function reset_cycle($when){global $config;sql_query('UPDATE sp_config SET cf_dungeon_reset='.monster_quote($when));$config=sql_fetch('SELECT * FROM sp_config');}
try {
 sql_query('CREATE TABLE sp_config (cf_dungeon_map INT,cf_dungeon_count INT,cf_dungeon_time INT,cf_dungeon_reset DATETIME) ENGINE=InnoDB');
 sql_query('CREATE TABLE sp_map (ma_id INT PRIMARY KEY,ma_use INT,ma_use_dungeon INT) ENGINE=InnoDB');
 sql_query('CREATE TABLE sp_dungeon (dg_id INT PRIMARY KEY,dg_title VARCHAR(100),dg_use INT,ma_ids TEXT,dg_per_s INT,dg_per_e INT) ENGINE=InnoDB');
 sql_query("CREATE TABLE sp_dungeon_state (ds_id INT AUTO_INCREMENT PRIMARY KEY,dg_id INT,dg_title VARCHAR(100),ds_ma_id INT,ds_state CHAR(1),phase VARCHAR(20) DEFAULT 'WAITING',ds_datetime DATETIME,created_at DATETIME,finished_at DATETIME NULL,snapshot TEXT NULL) ENGINE=InnoDB");
 sql_query("INSERT INTO sp_config VALUES (1,1,1,'2026-01-01 00:00:00')");
 sql_query('INSERT INTO sp_map VALUES (1,1,1),(2,1,1),(3,0,1),(4,1,0),(5,1,1)');
 sql_query("INSERT INTO sp_dungeon VALUES (1,'eligible',1,'||2||',0,100),(2,'disabled',0,'||1||',0,100),(3,'inactive map',1,'||3||',0,100),(4,'disabled map mode',1,'||4||',0,100),(5,'outside draw range',1,'||5||',101,101)");
 $old=date('Y-m-d H:i:s',time()-7200);
 for($i=0;$i<100;$i++) {
  reset_cycle($old);set_reset_dungeon();$s=sql_fetch("SELECT * FROM sp_dungeon_state WHERE ds_state='S'");
  if(!$s || (int)$s['dg_id']!==1 || (int)$s['ds_ma_id']!==2 || (int)sql_fetch("SELECT COUNT(*) n FROM sp_dungeon_state WHERE ds_state='S'")['n']!==1)throw new RuntimeException('An empty or ineligible map consumed a slot');
 }
 check(true,'100 rotations open the matching dungeon despite empty, disabled and out-of-range regions');
 $n=(int)sql_fetch('SELECT COUNT(*) n FROM sp_dungeon_state')['n'];set_reset_dungeon();
 check((int)sql_fetch('SELECT COUNT(*) n FROM sp_dungeon_state')['n']===$n,'same cycle does not create duplicate openings');
 sql_query("UPDATE sp_dungeon_state SET ds_state='E'");set_reset_dungeon(true);
 check((int)sql_fetch('SELECT COUNT(*) n FROM sp_dungeon_state')['n']===$n,'explicit retry does not respawn already completed or closed openings');
 sql_query('DELETE FROM sp_dungeon_state');$current=date('Y-m-d H:i:s');reset_cycle($current);set_reset_dungeon(true);
 $s=sql_fetch("SELECT * FROM sp_dungeon_state WHERE ds_state='S'");
 check($s && (int)$s['ds_ma_id']===2 && sql_fetch('SELECT cf_dungeon_reset r FROM sp_config')['r']===$current,'missing current opening repaired with unchanged scheduled time');
 $before=$s;reset_cycle($old);sql_query("CREATE TRIGGER fail_open BEFORE INSERT ON sp_dungeon_state FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture write failure'");
 $failed=false;try{set_reset_dungeon();}catch(InventoryStorageException $e){$failed=true;}finally{sql_query('DROP TRIGGER fail_open');}
 check($failed && sql_fetch('SELECT * FROM sp_dungeon_state WHERE ds_id='.(int)$before['ds_id'])===$before && sql_fetch('SELECT cf_dungeon_reset r FROM sp_config')['r']===$old,'failed opening rolls back prior closure and timer together');
 check((int)sql_fetch("SELECT IS_FREE_LOCK('sp_dungeon_reset') free")['free']===1,'failure releases the rotation lock');
 sql_query('UPDATE sp_config SET cf_dungeon_map=0');$config=sql_fetch('SELECT * FROM sp_config');set_reset_dungeon();
 check((int)sql_fetch("SELECT ds_ma_id FROM sp_dungeon_state WHERE ds_state='S'")['ds_ma_id']===0,'non-region opening mode remains supported');
} finally {$db->query('DROP DATABASE `'.$name.'`');}
