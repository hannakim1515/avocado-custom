<?php
// Copied explicitly into the isolated web fixture, never the live document root.
require __DIR__.'/common.php';
$dbinfo=sql_fetch('SELECT DATABASE() db,@@port port');
if($is_admin!=='super' || (int)$dbinfo['port']!==33381 || !preg_match('/^codex_install_web_[a-f0-9]+$/D',$dbinfo['db']))exit('isolated fixture only');
require_once G5_PATH.'/lib/monster_adapters.lib.php';require_once G5_PATH.'/lib/system_installer.lib.php';
header('Content-Type: application/json; charset=utf-8');
function enrow($table,$data){sql_query(system_install_default_row($table,$data));return (int)sql_insert_id();}
function enclone($table,$row,$pk,$changes){unset($row[$pk]);return enrow($table,array_replace($row,$changes));}
$op=$_GET['op']??'';$id=(int)($_GET['id']??0);
if($op==='seed') {
    $m=monster_profile($id);$m['dg_mon_hp']=500;$m['shared']['max_hp']=500;
    $ds=enrow('dungeon_state',array('dg_id'=>1,'ds_state'=>'S'));
    $skill=enrow('skill',array('sk_name'=>'검증 도발 '.$ds,'sk_type'=>'액티브','sk_function'=>'도발','sk_target'=>'자신','sk_value_type'=>'+','sk_keep_limit'=>1));
    enrow('skill_level',array('sk_id'=>$skill,'sl_level'=>1,'sl_set_value'=>100));
    $sh=enrow('skill_has',array('sk_id'=>$skill,'ch_id'=>1,'sh_use'=>1,'sh_level'=>1));
    $mz=$ds;$base=maze_one('SELECT * FROM web_dungeon_state WHERE snapshot IS NOT NULL ORDER BY ds_id LIMIT 1');
    maze_update('session',array('snapshot'=>$base['snapshot'],'phase'=>'BATTLE','version'=>1,'battle_id'=>0,'room_id'=>0),'ds_id='.$mz);
    $room=enrow('dungeon_maze_room',array('ds_id'=>$mz,'kind'=>'MONSTER','visited'=>1,'resolved'=>1,'is_boss'=>0,'payload'=>'{}'));
    $members=array();foreach(maze_party(1,false) as $p){$pid=enclone('dungeon_maze_member',$p,'dm_id',array('ds_id'=>$mz,'state'=>'ACTIVE','hp'=>100,'max_hp'=>100,'effects'=>'{}','skills'=>maze_json(array(array('sh_id'=>$sh,'sk_name'=>'검증 도발 '.$ds,'sk_function'=>'도발','sk_target'=>'자신','sh_limit'=>0,'sk_use_st_id'=>0,'sk_status_code'=>'','sl_set_value'=>100,'sk_value_type'=>'+','sk_def_code'=>'','sk_mod_type'=>'+','sk_keep_limit'=>1,'sk_limit'=>0))),'stats'=>$p['stats']));$members[$p['ch_id']]=$pid;}
    $bid=enrow('dungeon_maze_battle',array('ds_id'=>$mz,'room_id'=>$room,'monster'=>maze_json($m),'hp'=>500,'turn_no'=>1,'state'=>'ACTIVE','deadline_at'=>date('Y-m-d H:i:s',time()+3600)));
    maze_update('session',array('battle_id'=>$bid,'room_id'=>$room),'ds_id='.$mz);
    $r=monster_shared_runtime('maze:'.$bid,0,$m['shared']);monster_shared_enmity($r,$members[2],50);monster_shared_save('maze:'.$bid,0,$r);
    maze_insert('action',array('battle_id'=>$bid,'turn_no'=>1,'dm_id'=>$members[2],'action'=>'SKIP','target_id'=>$members[2]));
    echo maze_json(array('skill_name'=>'검증 도발 '.$ds,'ds'=>$ds,'sh'=>$sh,'maze_ds'=>$mz,'maze_battle'=>$bid,'members'=>$members));exit;
}
if($op==='runtime') {
    echo maze_json(monster_rows('SELECT * FROM web_monster_runtime WHERE battle_key='.monster_quote('maze:'.(int)($_GET['battle']??0))));exit;
}
if($op==='maze_heal') {
    $ds=(int)$_GET['ds'];$p=maze_one('SELECT * FROM web_dungeon_maze_member WHERE ds_id='.$ds.' AND ch_id=1');
    $skills=maze_data($p['skills']);$skills[0]=array_replace($skills[0],array('sk_function'=>'스탯회복','sk_use_st_id'=>1,'sl_use_value'=>10,'sl_set_value'=>18,'sk_mod_st_id'=>1));
    maze_update('member',array('hp'=>50,'skills'=>maze_json($skills)),'dm_id='.(int)$p['dm_id']);echo maze_json(array('ok'=>true));exit;
}
if($op==='raid') {
    $m=monster_profile($id);$master=sql_fetch('SELECT * FROM web_monster_master WHERE monster_id='.$id);$battle_table='web_k_battle_realtime';$ar_title='ra_id';$ra='enmity-'.bin2hex(random_bytes(4));
    enrow('k_battle_realtime',array('ra_id'=>$ra,'ra_turn'=>1));insert_k_battle_unit($master['raid_id'],'mo','realtime',$ra);insert_k_battle_unit(1,'ch','realtime',$ra);insert_k_battle_unit(2,'ch','realtime',$ra);
    $mo=sql_fetch('SELECT * FROM web_k_battle_realtime_unit WHERE ra_id='.monster_quote($ra)." AND unit_type='mo'");$ch=monster_rows('SELECT * FROM web_k_battle_realtime_unit WHERE ra_id='.monster_quote($ra)." AND unit_type='ch' ORDER BY unit_id");
    set_k_dmg($mo,'hp',-12,false,true,$ch[1]);$key='raid:'.$battle_table.':'.$ra;$after_damage=monster_shared_runtime($key,$mo['rm_id'],$m['shared']);
    $sk=array('sk_id'=>1,'sk_mp'=>0,'si_category'=>980,'si_1'=>'percent','si_code'=>'aggr','sk_value'=>100,'bonus_calc'=>'p','sc_id'=>0,'sk_turn'=>1,'sk_cool'=>0);
    use_k_skill($sk,$ch[0],$ch[0]['rm_id'],'ch',$ra);$after_provoke=monster_shared_runtime($key,$mo['rm_id'],$m['shared']);
    set_k_dmg($ch[0],'hp',-10,false,true);set_k_dmg(get_k_unit($ch[0]['rm_id'],'ch'),'hp',100,false,false,$ch[1]);$after_heal=monster_shared_runtime($key,$mo['rm_id'],$m['shared']);
    exec_k_mo_act($mo['rm_id'],$ra);
    echo maze_json(array('damage'=>$after_damage,'provoke'=>$after_provoke,'heal'=>$after_heal,'actors'=>array_column($ch,'rm_id'),'targets'=>monster_rows('SELECT unit_id,hp_now FROM web_k_battle_realtime_unit WHERE ra_id='.monster_quote($ra)." AND unit_type='ch' ORDER BY unit_id"),'logs'=>monster_rows('SELECT lo_content FROM web_k_battle_realtime_log WHERE ra_id='.monster_quote($ra))));exit;
}
