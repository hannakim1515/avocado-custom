<?php
/** Read-only diagnostics for scheduled dungeon openings. */
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);require dirname(__DIR__,2).'/data/dbconfig.php';
mysqli_report(MYSQLI_REPORT_OFF);
$db=mysqli_connect(G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB);
if(!$db)throw new RuntimeException('Database connection failed.');
mysqli_set_charset($db,'utf8mb4');
function inspect_spawn_rows($suffix,$columns,$where='') {
    global $db;
    $result=mysqli_query($db,'SELECT '.$columns.' FROM `'.G5_TABLE_PREFIX.$suffix.'` '.$where);
    if(!$result)throw new RuntimeException('Cannot read '.$suffix.': '.mysqli_error($db));
    return mysqli_fetch_all($result,MYSQLI_ASSOC);
}
$out=array(
 'database_time'=>mysqli_fetch_assoc(mysqli_query($db,'SELECT NOW() AS db_now,@@session.time_zone AS zone')),
 'config'=>inspect_spawn_rows('config','cf_dungeon_open,cf_dungeon_map,cf_dungeon_count,cf_dungeon_time,cf_dungeon_reset'),
 'definitions'=>inspect_spawn_rows('dungeon','dg_id,dg_title,dg_use,dg_count,ma_id,ma_ids,dg_per_s,dg_per_e','ORDER BY dg_id'),
 'regions'=>inspect_spawn_rows('newmap_config','ma_id,ma_parent,ma_name,ma_use,ma_use_dungeon','ORDER BY ma_id'),
 'recent_instances'=>inspect_spawn_rows('dungeon_state','ds_id,dg_id,dg_title,ds_ma_id,ds_state,phase,ds_datetime,created_at,finished_at,snapshot IS NULL AS unstarted','ORDER BY ds_id DESC LIMIT 20'),
 'visible_openings'=>mysqli_fetch_all(mysqli_query($db,'SELECT s.ds_id,d.dg_id,d.dg_title,s.ds_ma_id,m.ma_parent,m.ma_name,d.dg_mon_name FROM `'.G5_TABLE_PREFIX.'dungeon_state` s JOIN `'.G5_TABLE_PREFIX.'dungeon_monster_read` d ON d.dg_id=s.dg_id LEFT JOIN `'.G5_TABLE_PREFIX.'newmap_config` m ON m.ma_id=s.ds_ma_id WHERE s.ds_state=\'S\' AND d.dg_use=1'),MYSQLI_ASSOC)
);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),PHP_EOL;
