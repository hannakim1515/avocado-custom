<?php
// Installed explicitly in the isolated web fixture by monster_shared_browser.cjs.
require __DIR__.'/common.php';
$dbinfo=sql_fetch('SELECT DATABASE() db,@@port port');
if($is_admin!=='super' || (int)$dbinfo['port']!==33381 || !preg_match('/^codex_install_web_[a-f0-9]+$/D',$dbinfo['db']))exit('isolated fixture only');
require_once G5_PATH.'/lib/monster_adapters.lib.php';require_once G5_PATH.'/lib/system_installer.lib.php';
header('Content-Type: application/json; charset=utf-8');
function resource_row($table,$data){sql_query(system_install_default_row($table,$data));return (int)sql_insert_id();}
function resource_old_snapshot($key,$unit,$definition){
 $r=monster_shared_runtime($key,$unit,$definition);$r['mp']=0;$r['definition']['mp']=0;
 foreach($r['definition']['skills'] as &$skill){$skill['mp']=999;$skill['cooldown']=999;$r['cooldowns'][$skill['id']]=999;}unset($skill);
 monster_shared_save($key,$unit,$r);return $r;
}
$op=$_GET['op']??'';$id=(int)($_GET['id']??0);$m=monster_profile($id);$master=sql_fetch('SELECT * FROM web_monster_master WHERE monster_id='.$id);
if(!$master)exit('fixture monster required');
if($op==='inspect'){echo maze_json(array('profile'=>$m,'master'=>$master,'raid'=>get_k_monster($master['raid_id'])));exit;}
if($op==='maze'){
 $session=maze_one('SELECT * FROM web_dungeon_state WHERE ds_id=1');
 $bid=1000000+random_int(1,1000000);$battle=array('battle_id'=>$bid,'turn_no'=>1,'hp'=>$m['dg_mon_hp'],'weak_turn'=>0,'monster'=>maze_json($m));
 $party=array(1=>array('dm_id'=>1,'name'=>'공용 전투 검증 대상','hp'=>100,'max_hp'=>100,'stats'=>'{}','effects'=>'{}'));
 resource_old_snapshot('maze:'.$bid,0,$m['shared']);$damage=array();
 $hp_steps=!empty($_GET['range'])?array(1000,501,500,301,300,1):array_fill(0,3,(int)$m['dg_mon_hp']);
 foreach($hp_steps as $index=>$hp){$battle['turn_no']=$index+1;$battle['hp']=$hp;$before=$party[1]['hp'];maze_monster_phase($session,$battle,$party);$damage[]=$before-$party[1]['hp'];}
 echo maze_json(array('damage'=>$damage,'runtime'=>monster_shared_runtime('maze:'.$bid,0,$m['shared'])));exit;
}
if($op==='effects'){
 $session=maze_one('SELECT * FROM web_dungeon_state WHERE ds_id=1');$out=array();
 foreach(array('HEAL','MP_HEAL','GUARD') as $type){
  $copy=$m;$copy['shared']['default_attack']=false;$skill=&$copy['shared']['skills'][0];$skill['type']=$type;$skill['target']='self';$skill['min']=$type==='GUARD'?50:18;$skill['max']=$skill['min'];unset($skill);
  $bid=3000000+random_int(1,1000000);$battle=array('battle_id'=>$bid,'turn_no'=>1,'hp'=>50,'weak_turn'=>0,'monster'=>maze_json($copy));$party=array();
  resource_old_snapshot('maze:'.$bid,0,$copy['shared']);maze_monster_phase($session,$battle,$party);
  $r=monster_shared_runtime('maze:'.$bid,0,$copy['shared']);$out[$type]=array('hp'=>$battle['hp'],'has_mp'=>isset($r['mp']),'guard'=>$r['guard']);
 }
 echo maze_json($out);exit;
}
if($op==='raid'){
 $mode=($_GET['mode']??'realtime')==='mmbraid'?'mmbraid':'realtime';
 $phase=!empty($_GET['phase']);$ra_id='res'.bin2hex(random_bytes(4));
 $battle_table=$mode==='mmbraid'?'web_board':'web_k_battle_realtime';$ar_title=$mode==='mmbraid'?'bo_table':'ra_id';
 if($mode==='mmbraid'){
  // MMB uses board_* unit/skill/buff/log tables, with the same combat columns.
  foreach(array('unit','skill','buff','log') as $suffix)sql_query('CREATE TABLE IF NOT EXISTS web_board_'.$suffix.' LIKE web_k_battle_realtime_'.$suffix);
  resource_row('board',array('bo_table'=>$ra_id,'bo_1_subj'=>'mmbraid','bo_1'=>'true','bo_2'=>$master['raid_id']));
 }else resource_row('k_battle_realtime',array('ra_turn'=>1,'ra_id'=>$ra_id));
 insert_k_battle_unit($master['raid_id'],'mo',$mode,$ra_id);insert_k_battle_unit(1,'ch',$mode,$ra_id);
 $mo=sql_fetch('SELECT * FROM '.$battle_table.'_unit WHERE ra_id='.monster_quote($ra_id)." AND unit_type='mo'");
 $ch=sql_fetch('SELECT * FROM '.$battle_table.'_unit WHERE ra_id='.monster_quote($ra_id)." AND unit_type='ch'");
 $key='raid:'.$battle_table.':'.$ra_id;$runtime=resource_old_snapshot($key,$mo['rm_id'],$m['shared']);
 if($phase){
  $heal=$runtime['definition']['skills'][0];$heal['id']+=1000000;$heal['type']='HEAL';$heal['target']='self';$heal['min']=18;$heal['max']=18;$heal['name']='회복 단계';
  $runtime['definition']['skills'][]=$heal;$runtime['definition']['phases']=array(array('below'=>100,'actions'=>array($runtime['definition']['skills'][0]['id'])),array('below'=>35,'actions'=>array($heal['id'])));
  unset($runtime['intent'],$runtime['phase_index'],$runtime['phase_step']);monster_shared_save($key,$mo['rm_id'],$runtime);
 }
 $damage=array();$hp=array();$hp_steps=!empty($_GET['range'])?array(1000,501,500,301,300,1):array_fill(0,3,null);
 foreach($hp_steps as $index=>$step_hp){
  $turn=$index+1;
  if($step_hp!==null)sql_query('UPDATE '.$battle_table.'_unit SET hp_now='.(int)$step_hp.' WHERE rm_id='.(int)$mo['rm_id']);
  if($phase && $turn===2)sql_query('UPDATE '.$battle_table.'_unit SET hp_now=43 WHERE rm_id='.(int)$mo['rm_id']);
  $before=(int)sql_fetch('SELECT hp_now FROM '.$battle_table.'_unit WHERE rm_id='.(int)$ch['rm_id'])['hp_now'];
  if($mode==='mmbraid'){
   // Execute the actual MMB post handler, including its permission and routing.
   $board=sql_fetch('SELECT * FROM web_board WHERE bo_table='.monster_quote($ra_id));$write_table='';$wr_id=$turn;$return_url='';
   $_REQUEST=array('admin_action'=>'pattern','mo_rm_id'=>$mo['rm_id']);require G5_PATH.'/k_battle/skin_raid/raid.mmbraid_write_update.inc.php';
   if(!empty($data['warning']))throw new RuntimeException($data['warning']);
  }else {sql_query('UPDATE '.$battle_table.' SET ra_turn='.$turn.' WHERE ra_id='.monster_quote($ra_id));exec_k_mo_act($mo['rm_id'],$ra_id);}
  $after=sql_fetch('SELECT hp_now FROM '.$battle_table.'_unit WHERE rm_id='.(int)$ch['rm_id']);$damage[]=$before-(int)$after['hp_now'];
  $hp[]=(int)sql_fetch('SELECT hp_now FROM '.$battle_table.'_unit WHERE rm_id='.(int)$mo['rm_id'])['hp_now'];
 }
 $runtime=monster_shared_runtime($key,$mo['rm_id'],$m['shared']);
 // Legacy K use_k_skill must also bypass monster cost/cooldown while players pay.
 $legacy=array('sk_id'=>1,'sk_name'=>'기존 K 회복','sk_mp'=>999,'sk_cool'=>999,'sk_cool_now'=>999,'si_category'=>980,'si_1'=>'default','si_code'=>'heal','sk_value'=>18,'bonus_calc'=>'p','sc_id'=>0,'sk_turn'=>0,'default_calc'=>'');
 sql_query('UPDATE '.$battle_table.'_unit SET hp_now=50,mp_now=0 WHERE rm_id='.(int)$mo['rm_id']);$results=array();
 for($i=0;$i<2;$i++)$results[]=use_k_skill($legacy,get_k_unit($mo['rm_id'],'mo'),$mo['rm_id'],'mo',$ra_id);
 $legacy_hp=(int)sql_fetch('SELECT hp_now FROM '.$battle_table.'_unit WHERE rm_id='.(int)$mo['rm_id'])['hp_now'];
 $player=get_k_unit($ch['rm_id'],'ch');$player['mp_now']=0;$player_result=use_k_skill($legacy,$player,$ch['rm_id'],'ch',$ra_id);
 echo maze_json(array('damage'=>$damage,'hp'=>$hp,'runtime'=>$runtime,'monster'=>sql_fetch('SELECT * FROM '.$battle_table.'_unit WHERE rm_id='.(int)$mo['rm_id']),'legacy'=>array('results'=>$results,'hp'=>$legacy_hp),'player_result'=>$player_result,'log'=>monster_rows('SELECT * FROM '.$battle_table.'_log WHERE ra_id='.monster_quote($ra_id))));exit;
}
