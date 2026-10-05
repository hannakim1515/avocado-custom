<?php
/** Only creates/removes named cases in the disposable browser database. */
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$fixture = json_decode(file_get_contents($root.'/artifacts/combat-fixture.json'), true);
if (!preg_match('/^codex_install_web_[a-f0-9]+$/D', $fixture['database'])) throw new RuntimeException('Fixture only');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', $fixture['database'], 33381);
if (strpos($db->query('SELECT @@datadir d')->fetch_assoc()['d'], 'codex-system-installer-') === false) throw new RuntimeException('Isolated server required');
$db->set_charset('utf8mb4');
define('_GNUBOARD_', true); define('G5_PATH', $root);
require $fixture['site'].'/data/dbconfig.php'; $g5['connect_db'] = $db;
require $root.'/lib/system_installer.lib.php';
function sql_query($sql, $error = true) { global $db; try { return $db->query($sql); } catch (Throwable $e) { if ($error) throw $e; return false; } }
function sql_fetch($sql, $error = true) { $r = sql_query($sql, $error); return $r ? $r->fetch_assoc() : null; }
function sql_fetch_array($result) { return $result->fetch_assoc(); }
function sql_escape_string($value) { return $GLOBALS['db']->real_escape_string((string)$value); }
function sql_insert_id(){return $GLOBALS['db']->insert_id;}
function map_fixture_put($table, $values) { sql_query(system_install_default_row($table, $values)); return (int)$GLOBALS['db']->insert_id; }
function map_fixture_quote($value) { return $value === null ? 'NULL' : "'".sql_escape_string($value)."'"; }
$record = $root.'/artifacts/map-dungeon-fixture.json';
$op = $argv[1] ?? 'inspect';
if ($op === 'prepare') {
    if (is_file($record)) throw new RuntimeException('Restore previous map fixture before preparing');
    $fields = 'cf_dungeon_open,cf_dungeon_map,cf_dungeon_count,cf_dungeon_reset,cf_dungeon_time,cf_use_map_all,cf_map_all_img,cf_map_all_w,cf_map_all_h';
    $data = array('database' => $fixture['database'], 'config' => sql_fetch('SELECT '.$fields.' FROM web_config'), 'characters' => system_install_rows('SELECT ch_id,ma_id FROM web_character WHERE ch_id IN ('.implode(',', array_map('intval', $fixture['characters'])).')'), 'member_character' => sql_fetch("SELECT ch_id FROM web_member WHERE mb_id='fixture_member'")['ch_id'], 'maps' => array(), 'dungeons' => array(), 'instances' => array());
    file_put_contents($record, json_encode($data));
    // Use a fresh owned character so other browser fixtures remain playable.
    $clone=sql_fetch('SELECT * FROM web_character WHERE ch_id='.(int)$data['member_character']);unset($clone['ch_id']);
    $clone['ch_name']='동시 출현 검증';$data['test_character']=map_fixture_put('character',$clone);file_put_contents($record,json_encode($data));
    foreach(system_install_rows('SELECT * FROM web_status_character WHERE ch_id='.(int)$data['member_character']) as $stat){unset($stat['sc_id']);$stat['ch_id']=$data['test_character'];map_fixture_put('status_character',$stat);}
    sql_query("UPDATE web_member SET ch_id=".(int)$data['test_character']." WHERE mb_id='fixture_member'");
    foreach (array('root_zero','child_zero','root_self','child_self') as $key) {
        $parent = $key === 'child_zero' ? $data['maps']['root_zero'] : ($key === 'child_self' ? $data['maps']['root_self'] : 0);
        $id = map_fixture_put('newmap_config', array('ma_name' => '맵 입장 검증 '.$key, 'ma_parent' => $parent, 'ma_use' => 1, 'ma_use_dungeon' => 1, 'ma_img' => '/fixture-background.svg', 'ma_width' => 1200, 'ma_height' => 900, 'ma_left' => $key==='root_self'?850:300, 'ma_top' => 300));
        $data['maps'][$key] = $id;
        if ($key === 'root_self') sql_query('UPDATE web_newmap_config SET ma_parent='.$id.' WHERE ma_id='.$id);
        file_put_contents($record, json_encode($data));
    }
    $template = sql_fetch('SELECT * FROM web_dungeon WHERE dg_id='.(int)$fixture['dg_id']); unset($template['dg_id']);
    foreach (array('enabled' => 1, 'disabled' => 0) as $key => $enabled) {
        $values = array_replace($template, array('dg_title' => '맵 입장 검증 '.$key, 'dg_use' => $enabled, 'dg_count'=>1));
        $data['dungeons'][$key] = map_fixture_put('dungeon', $values); file_put_contents($record, json_encode($data));
    }
    $profile=array('dg_mon_name'=>'동시 출현 슬라임','dg_mon_hp'=>80,'dg_defence'=>0,'dg_mon_img'=>'','shared'=>array('name'=>'동시 출현 슬라임','max_hp'=>80,'stats'=>array(),'attack_min'=>1,'attack_max'=>1,'default_attack'=>true,'enmity_enabled'=>true,'skills'=>array(),'phases'=>array(array('below'=>100,'actions'=>array(0)))));
    $data['monster']=map_fixture_put('monster_master',array('name'=>$profile['dg_mon_name'],'enabled'=>1,'profile'=>json_encode($profile,JSON_UNESCAPED_UNICODE)));file_put_contents($record,json_encode($data));
    $profile['dg_mon_name']='출현 목록 밖 파수꾼';$profile['shared']['name']=$profile['dg_mon_name'];
    $data['rogue_monster']=map_fixture_put('monster_master',array('name'=>$profile['dg_mon_name'],'enabled'=>1,'profile'=>json_encode($profile,JSON_UNESCAPED_UNICODE)));file_put_contents($record,json_encode($data));
    map_fixture_put('dungeon_monster_pool',array('dg_id'=>$data['dungeons']['enabled'],'monster_id'=>$data['monster'],'weight'=>1,'enabled'=>1));
    $settings=json_decode($template['dg_settings'],true);$settings['min_players']=1;$settings['encounter_percent']=100;$settings['rooms_min']=3;$settings['rooms_max']=3;$settings['branches_min']=0;$settings['branches_max']=0;$settings['boss']=false;$settings['weights']=array('EMPTY'=>1,'SEARCH'=>0,'TREASURE'=>0,'TRAP'=>0);$settings['events']=array();
    sql_query('UPDATE web_dungeon SET dg_settings='.map_fixture_quote(json_encode($settings)).' WHERE dg_id='.(int)$data['dungeons']['enabled']);
    foreach (array('running','waiting','other_root','global','closed','disabled') as $key) {
        $values = array('dg_id' => $data['dungeons'][$key === 'disabled' ? 'disabled' : 'enabled'], 'dg_title' => '맵 입장 검증 '.$key, 'ds_ma_id' => $key === 'global' ? 0 : $data['maps'][$key === 'other_root' ? 'child_self' : 'child_zero'], 'ds_state' => $key === 'closed' ? 'E' : 'S', 'phase' => $key === 'running' ? 'BATTLE' : ($key === 'closed' ? 'CLOSED' : 'WAITING'), 'created_at' => date('Y-m-d H:i:s'), 'ds_datetime' => date('Y-m-d H:i:s'));
        if ($key === 'running') $values['snapshot'] = '{}';
        $data['instances'][$key] = map_fixture_put('dungeon_state', $values); file_put_contents($record, json_encode($data));
    }
    map_fixture_put('dungeon_maze_member', array('ds_id' => $data['instances']['running'], 'ch_id' => $fixture['characters']['fixture_admin'], 'mb_id' => 'fixture_admin', 'name' => '맵 입장 검증 참가자', 'state' => 'ACTIVE', 'hp' => 100, 'max_hp' => 100, 'joined_at' => date('Y-m-d H:i:s')));
    sql_query('UPDATE web_config SET cf_dungeon_open=1,cf_dungeon_map=1,cf_dungeon_count=0,cf_dungeon_reset=NOW(),cf_dungeon_time=24,cf_use_map_all=1,cf_map_all_img=\'/fixture-background.svg\',cf_map_all_w=1200,cf_map_all_h=900');
    sql_query('UPDATE web_character SET ma_id='.$data['maps']['child_zero'].' WHERE ch_id IN ('.implode(',', array_map('intval', $fixture['characters'])).')');
    sql_query('UPDATE web_character SET ma_id='.$data['maps']['child_zero'].' WHERE ch_id='.(int)$data['test_character']);
    echo json_encode($data); exit;
}
if (!is_file($record)) throw new RuntimeException('No prepared fixture');
$data = json_decode(file_get_contents($record), true);
if ($data['database'] !== $fixture['database']) throw new RuntimeException('Fixture database changed');
if ($op === 'mode') sql_query('UPDATE web_config SET cf_dungeon_map='.(int)($argv[2] ?? 1));
elseif ($op === 'feature') sql_query('UPDATE web_config SET cf_dungeon_open='.(int)($argv[2] ?? 1));
elseif ($op === 'position') sql_query('UPDATE web_character SET ma_id='.(int)($argv[2] ?? 0).' WHERE ch_id='.(int)$data['test_character']);
elseif(in_array($op,array('legacy_event','legacy_room','rogue_battle'),true)) {
    $dg=(int)$data['dungeons']['enabled'];$ds=(int)$data['instances']['waiting'];
    $settings=json_decode(sql_fetch('SELECT dg_settings FROM web_dungeon WHERE dg_id='.$dg)['dg_settings'],true);
    $event=array('enabled'=>true,'room_kind'=>'EMPTY','weight'=>1,'type'=>'MONSTER','monster_id'=>$data['rogue_monster'],'text'=>'출현 목록 밖 파수꾼이 나타났다.');
    $settings['events']=array($event);$settings['encounter_percent']=0;sql_query('UPDATE web_dungeon SET dg_settings='.map_fixture_quote(json_encode($settings,JSON_UNESCAPED_UNICODE)).' WHERE dg_id='.$dg);
    if($op!=='legacy_event') {
        require $root.'/lib/monster.lib.php';require $root.'/extend/inventory_boundary.lib.php';require $root.'/extend/dungeon_maze.lib.php';require $root.'/extend/dungeon_maze_combat.lib.php';
        $s=sql_fetch('SELECT * FROM web_dungeon_state WHERE ds_id='.$ds);$snapshot=maze_data($s['snapshot']);$event['monster']=monster_profile($data['rogue_monster']);$snapshot['events']=array($event);
        maze_update('session',array('snapshot'=>maze_json($snapshot),'version'=>(int)$s['version']+1),'ds_id='.$ds);
        $room=sql_fetch('SELECT * FROM web_dungeon_maze_room WHERE ds_id='.$ds.' AND room_id='.(int)$s['room_id']);$payload=maze_data($room['payload']);$payload['event']=$event;
        maze_update('room',array('payload'=>maze_json($payload),'resolved'=>$op==='rogue_battle'?1:0,'result_text'=>$op==='rogue_battle'?$event['text']:''),'ds_id='.$ds.' AND room_id='.(int)$s['room_id']);
        if($op==='rogue_battle') {sql_query('START TRANSACTION');try{maze_battle_begin($s,$room,$event['monster']);sql_query('COMMIT');}catch(Throwable $e){sql_query('ROLLBACK');throw $e;}}
    }
}
elseif ($op === 'inspect') {
    $ds=(int)$data['instances']['waiting'];
    $data['pool']=system_install_rows('SELECT * FROM web_dungeon_monster_pool WHERE dg_id='.(int)$data['dungeons']['enabled']);
    $data['party']=system_install_rows('SELECT * FROM web_dungeon_maze_member WHERE ds_id='.$ds);
    $data['battles']=system_install_rows('SELECT * FROM web_dungeon_maze_battle WHERE ds_id='.$ds.' ORDER BY battle_id');
    $data['session']=sql_fetch('SELECT phase,version,room_id,battle_id,snapshot FROM web_dungeon_state WHERE ds_id='.$ds);
}
elseif ($op === 'cleanup') {
    foreach ($data['instances'] as $id) {
        foreach(system_install_rows('SELECT battle_id FROM web_dungeon_maze_battle WHERE ds_id='.(int)$id) as $battle){sql_query('DELETE FROM web_dungeon_maze_action WHERE battle_id='.(int)$battle['battle_id']);sql_query('DELETE FROM web_monster_runtime WHERE battle_key='.map_fixture_quote('maze:'.$battle['battle_id']));}
        foreach(array('dungeon_maze_room','dungeon_maze_battle','dungeon_maze_log','dungeon_combat_event','dungeon_maze_vote','dungeon_maze_member','dungeon_maze_reward','dungeon_maze_inventory','dungeon_state') as $table)sql_query('DELETE FROM web_'.$table.' WHERE ds_id='.(int)$id);
    }
    foreach ($data['dungeons'] as $id) { sql_query('DELETE FROM web_dungeon_monster_pool WHERE dg_id='.(int)$id); sql_query('DELETE FROM web_dungeon WHERE dg_id='.(int)$id); }
    foreach ($data['maps'] as $id) sql_query('DELETE FROM web_newmap_config WHERE ma_id='.(int)$id);
    $sets = array(); foreach ($data['config'] as $key => $value) $sets[] = '`'.$key.'`='.map_fixture_quote($value);
    sql_query('UPDATE web_config SET '.implode(',', $sets));
    foreach ($data['characters'] as $row) sql_query('UPDATE web_character SET ma_id='.(int)$row['ma_id'].' WHERE ch_id='.(int)$row['ch_id']);
    if(!empty($data['test_character'])){sql_query("UPDATE web_member SET ch_id=".(int)$data['member_character']." WHERE mb_id='fixture_member'");sql_query('DELETE FROM web_status_character WHERE ch_id='.(int)$data['test_character']);sql_query('DELETE FROM web_character WHERE ch_id='.(int)$data['test_character']);}
    if(!empty($data['monster']))sql_query('DELETE FROM web_monster_master WHERE monster_id='.(int)$data['monster']);
    if(!empty($data['rogue_monster']))sql_query('DELETE FROM web_monster_master WHERE monster_id='.(int)$data['rogue_monster']);
    unlink($record);
}
echo json_encode($data);
