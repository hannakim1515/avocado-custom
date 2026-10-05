<?php
/** K catalog storage migration against the disposable installer database. */
if(PHP_SAPI!=='cli')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','','',33381);
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Disposable DB required');
$name='codex_monster_storage_'.bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4');$db->select_db($name);$db->set_charset('utf8mb4');
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','mv_');$g5=array('connect_db'=>$db);
require dirname(__DIR__,2).'/lib/system_installer.lib.php';
function check($ok,$label){if(!$ok)throw new LogicException('FAIL '.$label);echo 'PASS '.$label."\n";}
try {
    $root=dirname(__DIR__,2);
    $master=system_install_contract()['monsters']['monster_master'];
    $old=json_decode(file_get_contents($root.'/install/system/k_battle/schema-2.0.2.json'),true)['k_battle_monster'];
    system_install_query(system_install_create('monster_master',$master));
    system_install_query(system_install_create('k_battle_monster',$old));
    $profile=array('dg_mon_hp'=>80,'shared'=>array('mp'=>10,'stats'=>array(1=>12),'skills'=>array(),'attack_min'=>1,'attack_max'=>3,'pattern'=>'CYCLE','default_attack'=>true));
    system_install_query('INSERT INTO mv_monster_master (name,image,description,enabled,raid_id,profile) VALUES (\'슬라임\',\'\',\'던전 설명\',0,4,'.system_install_quote(json_encode($profile,JSON_UNESCAPED_UNICODE)).')');
    system_install_query("INSERT INTO mv_k_battle_monster (mo_id,mo_name,mo_thumb,mo_1,mo_hp,mo_mp,st_1,st_2,st_3,st_4,st_5,st_6,st_7,st_8,st_9,st_10,raid_type,mo_pattern,mo_default_act) VALUES (4,'슬라임','','K 설명',80,10,12,0,0,0,0,0,0,0,0,0,'realtime,mmbraid',2,2),(9,'K 전용','','K 전용 설명',120,5,0,0,0,0,0,0,0,0,0,0,'mmbraid',1,1)");
    $before=system_install_rows('SELECT * FROM mv_k_battle_monster ORDER BY mo_id');
    system_install_query("UPDATE mv_k_battle_monster SET mo_name='충돌하는 이름' WHERE mo_id=4");
    try { $failed_log=array();system_install_monster_raid_view($failed_log);throw new RuntimeException('conflicting name accepted'); }
    catch (RuntimeException $error) { check($error->getMessage()!=='conflicting name accepted','conflicting K and common names stop migration'); }
    check(system_install_schema('mv_k_battle_monster')['TABLE_TYPE']==='BASE TABLE' && !system_install_schema('mv_k_battle_monster_legacy_migration'),'failed migration restores physical K table');
    system_install_query("UPDATE mv_k_battle_monster SET mo_name='슬라임' WHERE mo_id=4");
    $log=array();system_install_monster_raid_view($log);
    check(system_install_schema('mv_k_battle_monster')['TABLE_TYPE']==='VIEW' && !system_install_schema('mv_k_battle_monster_legacy_migration'),'physical K table replaced by a view');
    $after=system_install_rows('SELECT * FROM mv_k_battle_monster ORDER BY mo_id');
    check($before==$after,'MMB and realtime K columns preserved byte for byte');
    check((int)system_install_one('SELECT COUNT(*) n FROM mv_monster_master')['n']===2,'unlinked legacy K monster joins common master');
    $joined=system_install_one("SELECT mo_name,mo_hp,st_1 FROM mv_k_battle_monster m JOIN (SELECT 4 unit_id) u ON m.mo_id=u.unit_id WHERE raid_type LIKE '%mmbraid%'");
    check($joined['mo_name']==='슬라임' && (int)$joined['mo_hp']===80 && (int)$joined['st_1']===12,'legacy raid JOIN works on the common catalog');
    system_install_query("UPDATE mv_monster_master SET name='공용 수정' WHERE raid_id=4");
    check(system_install_one('SELECT mo_name FROM mv_k_battle_monster WHERE mo_id=4')['mo_name']==='공용 수정','K readers see master edits without a second write');
} finally {$db->query('DROP DATABASE `'.$name.'`');}
