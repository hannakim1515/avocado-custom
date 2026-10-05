<?php
if (PHP_SAPI !== 'cli') exit(1);
$root=dirname(__DIR__,2);$f=json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'),true);
if(!preg_match('/^codex_install_web_[a-f0-9]+$/D',$f['database']))throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli('127.0.0.1','root','',$f['database'],33381);$db->set_charset('utf8mb4');
if(strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'],'codex-system-installer-')===false)throw new RuntimeException('Wrong server');
define('_GNUBOARD_',true);define('G5_PATH',$root);require $f['site'].'/data/dbconfig.php';$g5['connect_db']=$db;$g5['k_ch_equip_table']=G5_TABLE_PREFIX.'k_battle_equip_ch';
function sql_query($s,$e=true){return $GLOBALS['db']->query($s);}
function sql_fetch($s,$e=true){return sql_query($s)->fetch_assoc();}
function sql_fetch_array($r){return $r->fetch_assoc();}
function sql_escape_string($s){return $GLOBALS['db']->real_escape_string((string)$s);}
function sql_insert_id(){return $GLOBALS['db']->insert_id;}
require $root.'/lib/system_installer.lib.php';require $root.'/extend/inventory_boundary.lib.php';
require $root.'/extend/dungeon_maze.lib.php';require $root.'/extend/dungeon_maze_inventory.lib.php';require $root.'/extend/dungeon_maze_combat.lib.php';
function put($table,$values){system_install_query(system_install_default_row($table,$values));return (int)$GLOBALS['db']->insert_id;}
$op=$argv[1]??'';
if($op==='seed') {
 $status=put('dungeon_maze_status',array('name'=>'치료 폼 검증','duration'=>3,'components'=>'[{"type":"DOT_HP","value":2}]','enabled'=>1));
 $id=(int)sql_fetch('SELECT COALESCE(MAX(it_id),0)+1 n FROM web_item')['n'];
 put('item',array('it_id'=>$id,'it_name'=>'기존 부활 설정','it_content'=>'기존 아이템 설명','it_type'=>'일반','it_value'=>'','it_use_battle_able'=>1,'it_2'=>"기존 '추가' 값",'it_3'=>'스탯 보존','it_4'=>'2|3','it_5'=>'4|5'));
 $old_effect=system_install_table('dungeon_maze_item_effect');
 if(!system_install_schema($old_effect)) {
  $snapshot=json_decode(file_get_contents($root.'/install/system/labyrinth/schema-1.0.0.json'),true);
  system_install_query(system_install_create('dungeon_maze_item_effect',$snapshot['dungeon_maze_item_effect']));
  unset($GLOBALS['system_install_schema_cache'][$old_effect]);
 }
 put('dungeon_maze_item_effect',array('it_id'=>$id,'kind'=>'REVIVE','value'=>25));
 $log=array();system_install_record('labyrinth','1.1.0',$log);
 echo json_encode(array('status_id'=>$status,'legacy_id'=>$id),JSON_UNESCAPED_UNICODE);exit;
}
if($op==='inspect') {
 $item=sql_fetch('SELECT * FROM web_item WHERE it_id='.(int)$argv[2]);
 echo json_encode(array('item'=>$item,'effect'=>item_recovery_effect($item),'legacy_table'=>!!system_install_schema(system_install_table('dungeon_maze_item_effect'))),JSON_UNESCAPED_UNICODE);exit;
}
if($op==='find') {echo json_encode(sql_fetch('SELECT it_id FROM web_item WHERE it_name='.system_install_quote($argv[2]).' ORDER BY it_id DESC LIMIT 1'));exit;}
if($op==='status') {echo json_encode(array('module'=>system_install_all_status()['labyrinth'],'meta'=>sql_fetch("SELECT version FROM web_system_migration_meta WHERE module='labyrinth'"),'old_table'=>!!system_install_schema(system_install_table('dungeon_maze_item_effect'))),JSON_UNESCAPED_UNICODE);exit;}
if($op==='runtime') {
 $ids=array_map('intval',array_slice($argv,2));$party=sql_fetch('SELECT * FROM web_dungeon_maze_member WHERE ds_id='.(int)$f['ds_id'].' AND ch_id='.(int)$f['characters']['fixture_admin']);
 $session=array('ds_id'=>(int)$party['ds_id']);$actor=$party;$db->begin_transaction();$results=array();
 try {
  foreach($ids as $id) {
   $item=sql_fetch('SELECT * FROM web_item WHERE it_id='.$id);
   maze_inventory_gain($session,$actor,$item);
   $stored=sql_fetch('SELECT * FROM web_dungeon_maze_inventory WHERE it_id='.$id.' AND ds_id='.$session['ds_id'].' AND dm_id='.(int)$actor['dm_id'].' ORDER BY inventory_id DESC LIMIT 1');
   $snapshot=inventory_boundary_decode($stored['item_snapshot']);
   $target=$actor;$target['hp']=$item['it_type']==='부활'?0:50;$target['max_hp']=100;
   $target['effects']=maze_json(array('status:'.$item['it_value']=>array('remaining'=>2),'status:999999'=>array('remaining'=>1),'guard'=>array('value'=>10)));
   maze_item_apply($session,$actor,$target,(int)$stored['inventory_id']);
   $consumed=sql_fetch('SELECT consumed FROM web_dungeon_maze_inventory WHERE inventory_id='.(int)$stored['inventory_id'])['consumed'];
   $results[]=array('kind'=>$snapshot['maze_effect']['kind'],'hp'=>$target['hp'],'effects'=>maze_data($target['effects']),'consumed'=>(int)$consumed);
   put('inventory',array('ch_id'=>$actor['ch_id'],'it_id'=>$id,'it_name'=>$item['it_name'],'ch_name'=>$actor['name']));
  }
  maze_inventory_isolate($session,array($actor));
  foreach($ids as $id){$escrow=sql_fetch("SELECT item_snapshot FROM web_dungeon_maze_inventory WHERE origin='ORIGINAL' AND ds_id=".$session['ds_id'].' AND it_id='.$id.' ORDER BY inventory_id DESC LIMIT 1');$results[]=array('original_effect'=>inventory_boundary_decode($escrow['item_snapshot'])['maze_effect']);}
  echo json_encode($results,JSON_UNESCAPED_UNICODE);
 } finally { $db->rollback(); }
}
