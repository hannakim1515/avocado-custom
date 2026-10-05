<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__,2);$f=json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'),true);
if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',$f['database']))throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','',$f['database'],33381);$db->set_charset('utf8mb4');
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Wrong server');
define('_GNUBOARD_',true);define('G5_PATH',$root);require $f['site'].'/data/dbconfig.php';$g5['connect_db']=$db;
function sql_query($s,$e=true){global $db;try{return $db->query($s);}catch(Throwable $x){if($e)throw $x;return false;}}
function sql_fetch($s,$e=true){$r=sql_query($s,$e);return $r?$r->fetch_assoc():null;}
function sql_fetch_array($r){return $r->fetch_assoc();}
function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}
function sql_insert_id(){return $GLOBALS['db']->insert_id;}
require $root.'/lib/system_installer.lib.php';require $root.'/lib/monster.lib.php';
$op=$argv[1]??'inspect';$id=(int)($argv[2]??0);$ch=(int)$f['characters']['fixture_admin'];
if($op==='active') {echo json_encode(monster_rows('SELECT m.ds_id,m.dm_id,s.phase FROM web_dungeon_maze_member m JOIN web_dungeon_state s ON s.ds_id=m.ds_id WHERE m.ch_id='.$ch." AND m.state='ACTIVE'"));exit;}
if($op==='catalog') {
    foreach(array('통합 보상 100%','통합 보상 0%') as $name) {
        $row=sql_fetch('SELECT it_id FROM web_item WHERE it_name='.monster_quote($name));
        if($row && !(int)$row['it_id']){sql_query('DELETE FROM web_item WHERE it_id=0 AND it_name='.monster_quote($name));$row=null;}
        $next=(int)sql_fetch('SELECT COALESCE(MAX(it_id),0)+1 n FROM web_item')['n'];
        if(!$row)sql_query(system_install_default_row('item',array('it_id'=>$next,'it_name'=>$name,'it_use'=>'Y')));
        else sql_query('UPDATE web_item SET it_use=\'Y\' WHERE it_id='.(int)$row['it_id']);
    }
    echo '{}';exit;
}
if($op==='prepare') {
    if(sql_fetch("SELECT dm_id FROM web_dungeon_maze_member WHERE ch_id={$ch} AND state='ACTIVE'"))throw new RuntimeException('Settle previous fixture sessions before testing');
    sql_query('UPDATE web_config SET cf_dungeon_open=1,cf_dungeon_map=0,cf_dungeon_reset=NOW(),cf_dungeon_time=24,cf_dungeon_enter=100,cf_use_point=1');
    sql_query('INSERT INTO web_dungeon_state (dg_id,dg_title,ds_state,ds_datetime,created_at) SELECT dg_id,dg_title,\'S\',NOW(),NOW() FROM web_dungeon WHERE dg_id='.$id);
    $ds=sql_insert_id();
    echo json_encode(array('ds_id'=>$ds));exit;
}
if($op==='fail_save') {sql_query("CREATE TRIGGER web_fixture_fail_pool BEFORE INSERT ON web_dungeon_monster_pool FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture reject pool'");echo '{}';exit;}
if($op==='allow_save') {sql_query('DROP TRIGGER IF EXISTS web_fixture_fail_pool');echo '{}';exit;}
$definition=sql_fetch('SELECT * FROM web_dungeon WHERE dg_id='.$id);
$rewards=monster_rows('SELECT * FROM web_dungeon_item WHERE dg_id='.$id.' ORDER BY di_id');
echo json_encode(array('definition'=>$definition,'rewards'=>$rewards,'pool'=>monster_rows('SELECT * FROM web_dungeon_monster_pool WHERE dg_id='.$id),
    'items'=>monster_rows('SELECT it_id,it_name,it_use FROM web_item ORDER BY it_id'),
    'inventory'=>monster_rows('SELECT * FROM web_inventory WHERE ch_id='.$ch.' ORDER BY in_id'),
    'points'=>(int)(sql_fetch("SELECT COALESCE(SUM(po_point),0) n FROM web_point WHERE mb_id='fixture_admin'")['n']),
    'sessions'=>monster_rows('SELECT ds_id,phase,snapshot FROM web_dungeon_state WHERE dg_id='.$id),
    'retired'=>monster_rows("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('web_dungeon_maze_config','web_dungeon_maze_session','web_dungeon_monster_config','web_dungeon_state_monster','web_dungeon_member','web_dungeon_log')")),JSON_UNESCAPED_UNICODE);
