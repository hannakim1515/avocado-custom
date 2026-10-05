<?php
/** CLI only. Requires a disposable server whose datadir contains codex-dungeon-. */
if (PHP_SAPI !== 'cli') exit(1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', '', (int)(getenv('CODEX_DUNGEON_TEST_PORT')?:33379));
$data_dir = $db->query('SELECT @@datadir AS d')->fetch_assoc()['d'];
if (strpos($data_dir, 'codex-dungeon-') === false && strpos($data_dir,'codex-system-installer-')===false) throw new RuntimeException('Refusing a non-disposable database server.');
$worker = isset($argv[1]) && in_array($argv[1],array('worker','readiness'),true);
$database = $worker ? $argv[2] : 'codex_maze_test_'.bin2hex(random_bytes(5));
if (!preg_match('/^codex_maze_test_[a-f0-9]+$/D', $database)) throw new RuntimeException('Invalid test database');
if (!$worker) $db->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');
$db->select_db($database);
$db->set_charset('utf8mb4');
define('_GNUBOARD_', true);
define('G5_PATH',dirname(__DIR__));
define('G5_TABLE_PREFIX', 'avo_');
$g5 = array('inventory_table' => 'avo_inventory', 'item_table' => 'avo_item', 'k_ch_equip_table' => 'avo_equip');
$member = array('mb_id' => 'tester');
$character = array('ch_id' => 1);
function sql_query($sql, $error = true) { global $db; try { return $db->query($sql); } catch (Throwable $e) { if ($error) throw $e; return false; } }
function sql_fetch_array($result) { return $result->fetch_assoc(); }
function sql_fetch($sql, $error = true) { $result = sql_query($sql, $error); return $result ? $result->fetch_assoc() : false; }
function sql_escape_string($str) { global $db; return $db->real_escape_string($str); }
function sql_insert_id() { global $db; return $db->insert_id; }
function get_character($id) { return array('ch_id' => $id, 'mb_id' => $id === 1 ? 'tester' : 'other', 'ch_name' => 'Test', 'ch_state' => '승인'); }
function get_item($id) { return sql_fetch('SELECT * FROM avo_item WHERE it_id='.(int)$id); }
require dirname(__DIR__).'/extend/inventory_boundary.lib.php';
require dirname(__DIR__).'/lib/monster.lib.php';
require dirname(__DIR__).'/extend/dungeon_maze.lib.php';
require dirname(__DIR__).'/extend/dungeon_maze_inventory.lib.php';
require dirname(__DIR__).'/extend/dungeon_maze_session.lib.php';
require dirname(__DIR__).'/extend/dungeon_maze_combat.lib.php';
require dirname(__DIR__).'/extend/dungeon_maze_actions.lib.php';
require dirname(__DIR__).'/extend/dungeon_formula.lib.php';
function get_dungeon_state($id) {
    global $test_dungeon;
    if(!sql_fetch('SELECT ds_id FROM avo_dungeon_state WHERE ds_id='.(int)$id))sql_query('INSERT INTO avo_dungeon_state (ds_id,dg_id,created_at) VALUES ('.(int)$id.','.(int)$test_dungeon['dg_id'].',NOW())');
    return array_merge($test_dungeon,array('ds_id'=>$id));
}
function is_has_dungeon($ch_id,$except=0) { return false; }
function unified_stat_value($ch_id,$st_id) { return 100; }
function get_skill_set_list($ch_id) { return array(); }
function unified_combat_action_code($action) { return $action; }
function get_status_type_filed($type) { return 'st_type1'; }
function unified_active_effect_value($base,$flat,$percent,$final) { $result=($base+$flat)*(1+$percent/100); foreach($final as $value)$result*=$value;return (int)$result; }
function insert_point($mb,$amount,$description,$table,$id,$action) {
    $key=inventory_boundary_quote($mb.'|'.$table.'|'.$id.'|'.$action);
    if(sql_fetch('SELECT id FROM avo_test_point WHERE relation_key='.$key))return -1;
    sql_query('INSERT INTO avo_test_point (relation_key,amount) VALUES ('.$key.','.(int)$amount.')');return 1;
}

if ($worker && $argv[1]==='readiness') { echo maze_readiness()['ready']?'READY':'BLOCKED'; exit; }
if ($worker) {
    echo "READY\n"; flush();
    try {
        $ids = array_map('intval', explode(',', $argv[3]));
        $claim = inventory_boundary_begin(1, $ids, 'test.consume', array(), 'remove');
        inventory_boundary_done($claim);
        echo "CONSUMED\n";
    } catch (Throwable $error) { echo "REJECTED\n"; }
    exit;
}

function check($condition, $message) { if (!$condition) throw new LogicException('FAIL: '.$message); echo 'PASS: '.$message."\n"; }
function migration($name) {
    global $db;
    $db->multi_query(file_get_contents(dirname(__DIR__).'/install/dungeon/'.$name));
    do { if ($result = $db->store_result()) $result->free(); } while ($db->more_results() && $db->next_result());
}
function child_claim($ids) {
    global $database;
    $pipes = array();
    $process = proc_open(array(PHP_BINARY, __FILE__, 'worker', $database, implode(',', $ids)), array(0 => array('pipe','r'), 1 => array('pipe','w'), 2 => array('pipe','w')), $pipes);
    if (!is_resource($process) || trim(fgets($pipes[1])) !== 'READY') throw new RuntimeException('Worker failed to start');
    fclose($pipes[0]);
    return array($process, $pipes);
}
function child_result($child) {
    list($process, $pipes) = $child;
    $output = trim(stream_get_contents($pipes[1])); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $exit = proc_close($process);
    if ($exit !== 0 || $errors !== '') throw new RuntimeException('Worker error: '.$errors);
    return $output;
}
function add_inventory($id, $it_id = 1, $owner = 1) {
    sql_query("INSERT INTO avo_inventory (in_id,it_id,ch_id,it_name,ch_name,in_use,in_memo,custom_state) VALUES ({$id},{$it_id},'{$owner}','Potion','Test','','original',X'00FF41')");
}
function act_as($id) {global $character,$member;$character=get_character($id);$member=array('mb_id'=>$character['mb_id']);}

try {
    sql_query("CREATE TABLE avo_inventory (in_id INT AUTO_INCREMENT PRIMARY KEY,it_id INT NOT NULL,ch_id VARCHAR(20) NOT NULL,it_name VARCHAR(255) NOT NULL,ch_name VARCHAR(255) NOT NULL,in_use VARCHAR(20) NOT NULL DEFAULT '',in_memo TEXT,custom_state VARBINARY(30),KEY owner_rows(ch_id,in_id)) ENGINE=InnoDB");
    sql_query("CREATE TABLE avo_item (it_id INT PRIMARY KEY,it_name VARCHAR(100),it_type VARCHAR(30),it_value VARCHAR(100) NOT NULL DEFAULT '',it_use_battle_able INT,it_use_ever INT) ENGINE=MyISAM");
    sql_query("CREATE TABLE avo_equip (in_id INT PRIMARY KEY,eq_use VARCHAR(20)) ENGINE=MyISAM");
    sql_query("INSERT INTO avo_item (it_id,it_name,it_type,it_use_battle_able,it_use_ever) VALUES (1,'Potion','스탯회복',1,0),(2,'Material','material',0,0),(3,'Gear','gear',1,0)");
    migration('002_inventory_boundary.manual.sql');
    $g5['connect_db']=$db;
    require G5_PATH.'/lib/system_installer.lib.php';
    $contract=system_install_contract();
    foreach($contract['labyrinth'] as $suffix=>$spec)if(!empty($spec['create']))sql_query(system_install_create($suffix,$spec));
    sql_query("INSERT INTO avo_dungeon_maze_meta (singleton,schema_version) VALUES (1,1)");
    foreach(array('dungeon_state','dungeon_item') as $suffix)sql_query(system_install_create($suffix,$contract['dungeon'][$suffix]));
    foreach(array('monster_master','dungeon_monster_pool','monster_runtime','dungeon_combat_event') as $suffix)sql_query(system_install_create($suffix,$contract['monsters'][$suffix]));
    $g5['dungeon_state_table']='avo_dungeon_state';$g5['dungeon_item_table']='avo_dungeon_item';
    check(maze_readiness()['ready'], 'complete migration is ready');
    add_inventory(10); add_inventory(11, 2); add_inventory(12, 3); add_inventory(13, 1, 2);
    sql_query("INSERT INTO avo_equip VALUES (12,'weapon')");
    $snapshot = maze_json(array('points' => 0, 'rewards' => array()));
    maze_insert('session', array('ds_id' => 1, 'dg_id' => 1, 'phase' => 'EXPLORE', 'snapshot' => $snapshot, 'created_at' => date('Y-m-d H:i:s')));
    $id = maze_insert('member', array('ds_id' => 1, 'ch_id' => 1, 'mb_id' => 'tester', 'name' => 'Test', 'stats' => '{}', 'skills' => '{}', 'effects' => '{}', 'joined_at' => date('Y-m-d H:i:s')));
    $session = maze_session(1); $party = maze_party(1);
    $original = sql_fetch('SELECT * FROM avo_inventory WHERE in_id=10');
    sql_query('START TRANSACTION'); inventory_boundary_lock_owners(array(1));
    maze_inventory_isolate($session, $party);
    $child = child_claim(array(10));
    sql_query('COMMIT');
    check(child_result($child) === 'REJECTED', 'dungeon first: stale consumer rejected');
    check((int)sql_fetch('SELECT COUNT(*) AS n FROM avo_inventory')['n'] === 3, 'equipment, non-battle and foreign rows remain');
    add_inventory(20); // Acquired externally during the session.
    sql_query('START TRANSACTION'); inventory_boundary_lock_owners(array(1));
    maze_settle_member($session, $party[0], 'EXIT'); sql_query('COMMIT');
    check(sql_fetch('SELECT * FROM avo_inventory WHERE in_id=10') === $original, 'full original row restored including binary/custom columns and ID');
    check((bool)sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=20'), 'external acquisition preserved');
    sql_query('START TRANSACTION'); inventory_boundary_lock_owners(array(1));
    check(maze_settle_member($session, $party[0], 'EXIT') === false, 'second settlement is a no-op'); sql_query('COMMIT');

    $claim = inventory_boundary_begin(1, array(10), 'test.sale', array(), 'remove');
    sql_query('START TRANSACTION');
    try { inventory_boundary_lock_owners(array(1)); check(false, 'pending effect must block start'); }
    catch (RuntimeException $expected) { sql_query('ROLLBACK'); }
    inventory_boundary_done($claim);
    check(child_result(child_claim(array(10))) === 'REJECTED', 'sale first: duplicate sale/use rejected');
    $before = sql_fetch('SELECT COUNT(*) AS n FROM avo_inventory')['n'];
    try { inventory_boundary_begin(1, array(11,999), 'test.batch', array(), 'remove'); check(false, 'missing material must reject'); }
    catch (RuntimeException $expected) {}
    check(sql_fetch('SELECT COUNT(*) AS n FROM avo_inventory')['n'] === $before, 'partial multi-item consumption rolls back');
    try { inventory_boundary_begin(1, array(13), 'test.foreign', array(), 'remove'); check(false, 'foreign item must reject'); }
    catch (RuntimeException $expected) {}
    check((bool)sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=13'), 'foreign owner preserved');
    sql_query('START TRANSACTION'); inventory_boundary_lock_owners(array(1));
    inventory_boundary_lock_rows(1, array(20));
    $child = child_claim(array(20));
    sql_query('DELETE FROM avo_inventory WHERE in_id=20'); sql_query('COMMIT');
    check(child_result($child) === 'REJECTED', 'same item concurrent consumer loses after row lock');
    $g5['status_config_table']='avo_status_config'; $g5['status_extra_table']='avo_status_extra';
    sql_query('CREATE TABLE avo_status_config (st_id INT PRIMARY KEY,st_use_hp INT,st_type1 INT) ENGINE=MyISAM');
    sql_query('INSERT INTO avo_status_config VALUES (1,1,1)');
    $formula_fields=array('ex_main_min','ex_main_max','ex_is_main_status','ex_main_status_type','ex_main_status_per','ex_cri','ex_is_cri_status','ex_cri_status_type','ex_cri_status_per','ex_cri_add_per','ex_is_cri_add_status','ex_cri_add_status_type','ex_cri_add_status_per','ex_all_per');
    sql_query('CREATE TABLE avo_status_extra (ex_name VARCHAR(100) PRIMARY KEY,'.implode(',',array_map(function($field){return $field.' INT DEFAULT 0';},$formula_fields)).') ENGINE=MyISAM');
    foreach(array('atk'=>5,'heal'=>3,'guard'=>20,'방어력'=>1) as $code=>$value) sql_query('INSERT INTO avo_status_extra (ex_name,ex_main_min,ex_main_max) VALUES ('.inventory_boundary_quote($code).','.$value.','.$value.')');
    $test_dungeon=array('dg_id'=>2,'dg_title'=>'탐험 검증','ds_state'=>'S','ds_ma_id'=>0,'dg_use'=>1,'dg_count'=>1,'dg_point'=>0,'dg_status'=>0,'dg_mon_name'=>'Fixture monster','dg_mon_hp'=>10,'dg_defence'=>0);
    foreach(array('d','w','s1','s2') as $pattern) foreach(array('turn','count','min','max','st_id','st_value') as $suffix) $test_dungeon['dg_'.$pattern.'_attack_'.$suffix]=0;
    $config=array('cf_dungeon_open'=>1,'cf_dungeon_map'=>0,'cf_dungeon_reset'=>date('Y-m-d H:i:s'),'cf_dungeon_time'=>24,'cf_dungeon_enter'=>100);
    $settings=maze_defaults($test_dungeon);
    $settings['rooms_min']=3;$settings['rooms_max']=3;$settings['branches_min']=0;$settings['branches_max']=0;$settings['encounter_percent']=0;
    $settings['weights']=array('EMPTY'=>0,'SEARCH'=>1,'TREASURE'=>0,'TRAP'=>0);
    $settings['events']=array(array('enabled'=>true,'room_kind'=>'SEARCH','weight'=>1,'type'=>'ITEM','it_id'=>1,'count'=>1,'text'=>'Found potion'));
    $test_dungeon['dg_settings']=maze_json($settings);
    maze_join(2); $waiting=maze_session(2);
    check($waiting['phase']==='WAITING','join creates WAITING without maze');
    check(!(int)sql_fetch('SELECT COUNT(*) AS n FROM avo_dungeon_maze_room WHERE ds_id=2')['n'],'WAITING has no generated rooms');
    maze_action(2,'ready',array('ready'=>1)); maze_action(2,'start',array());
    $started=maze_session(2);
    check($started['phase']==='EXPLORE','validated start transitions to EXPLORE');
    check((int)sql_fetch('SELECT COUNT(*) AS n FROM avo_dungeon_maze_room WHERE ds_id=2')['n']===3,'maze generated exactly once');
    try { maze_action(2,'start',array('version'=>$started['version'],'room_id'=>1)); check(false,'second start must reject'); } catch(RuntimeException $expected) {}
    maze_action(2,'move',array('version'=>$started['version'],'room_id'=>1,'direction'=>'forward'));
    $exploring=maze_session(2);
    maze_action(2,'event',array('version'=>$exploring['version'],'room_id'=>2));
    check((int)sql_fetch("SELECT COUNT(*) AS n FROM avo_dungeon_maze_inventory WHERE ds_id=2 AND origin='FOUND'")['n']===1,'room item enters dungeon escrow immediately');
    try { maze_action(2,'event',array('version'=>$exploring['version'],'room_id'=>2)); check(false,'stale event must reject'); } catch(RuntimeException $expected) {}
    check((int)sql_fetch("SELECT COUNT(*) AS n FROM avo_dungeon_maze_inventory WHERE ds_id=2 AND origin='FOUND'")['n']===1,'stale event cannot duplicate reward');
    maze_transaction(2,function($session) use($test_dungeon){maze_battle_begin($session,maze_room($session),$test_dungeon);});
    for($turn=1;$turn<=2;$turn++) {
        $battle_session=maze_session(2);$battle=maze_battle_current($battle_session);
        maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>$battle['turn_no'],'action'=>'ATTACK','escape_vote'=>0));
        maze_action(2,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>$battle['turn_no']));
    }
    $won=maze_session(2);check($won['phase']==='EXPLORE','two synchronized turns defeat monster and return to exploration');
    maze_update('meta',array('action_timeout'=>30),'singleton=1');
    maze_transaction(2,function($session) use($test_dungeon){maze_battle_begin($session,maze_room($session),$test_dungeon);});
    $timed_session=maze_session(2);$timed=maze_battle_current($timed_session);
    maze_action(2,'submit',array('battle_id'=>$timed['battle_id'],'turn_no'=>1,'action'=>'ATTACK','escape_vote'=>0));
    check((int)maze_battle_current(maze_session(2))['hp']===10,'timed turn does not resolve just because everyone submitted');
    maze_action(2,'submit',array('battle_id'=>$timed['battle_id'],'turn_no'=>1,'action'=>'SKIP','escape_vote'=>0));
    maze_action(2,'resolve',array('battle_id'=>$timed['battle_id'],'turn_no'=>1));
    $timed=maze_battle_current(maze_session(2));
    check((int)$timed['turn_no']===2 && (int)$timed['hp']===10,'revised action wins when remaining time is skipped');
    try{maze_action(2,'resolve',array('battle_id'=>$timed['battle_id'],'turn_no'=>1));check(false,'stale resolve must reject');}catch(RuntimeException $expected){}
    check((int)maze_battle_current(maze_session(2))['turn_no']===2,'duplicate resolve cannot advance another turn');
    maze_update('battle',array('deadline_at'=>date('Y-m-d H:i:s',time()-1)),'battle_id='.(int)$timed['battle_id']);
    maze_action(2,'resolve',array('battle_id'=>$timed['battle_id'],'turn_no'=>2));
    check((int)maze_battle_current(maze_session(2))['turn_no']===3,'server deadline resolves unsubmitted actions as skips');
    $escape_settings=maze_data(maze_session(2)['snapshot']);$escape_settings['escape_percent']=100;
    maze_update('session',array('snapshot'=>maze_json($escape_settings)),'ds_id=2');
    $timed=maze_battle_current(maze_session(2));
    maze_action(2,'submit',array('battle_id'=>$timed['battle_id'],'turn_no'=>3,'action'=>'ATTACK','escape_vote'=>1));
    maze_action(2,'resolve',array('battle_id'=>$timed['battle_id'],'turn_no'=>3));
    check(maze_session(2)['phase']==='EXPLORE','unanimous successful escape returns to exploration');
    $escaped=maze_one('SELECT * FROM avo_dungeon_maze_battle WHERE battle_id='.(int)$timed['battle_id']);
    check((int)$escaped['hp']===10 && $escaped['state']==='ESCAPED','escape takes priority over submitted attack');
    maze_update('meta',array('action_timeout'=>null),'singleton=1');
    $escape_settings['escape_percent']=0;
    maze_update('session',array('snapshot'=>maze_json($escape_settings)),'ds_id=2');
    maze_transaction(2,function($session) use($test_dungeon){maze_battle_begin($session,maze_room($session),$test_dungeon);});
    $battle=maze_battle_current(maze_session(2));
    maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'ATTACK','escape_vote'=>1));
    maze_action(2,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>1));
    $failed_escape=maze_battle_current(maze_session(2));
    check((int)$failed_escape['turn_no']===2 && (int)$failed_escape['hp']===10 && $failed_escape['state']==='ACTIVE','ordinary failed escape keeps battle active and skips submitted attack');
    $escape_settings['escape_percent']=100;
    maze_update('session',array('snapshot'=>maze_json($escape_settings)),'ds_id=2');
    maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>2,'action'=>'SKIP','escape_vote'=>1));
    maze_action(2,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>2));
    $boss=$test_dungeon;$boss['dg_mon_hp']=5;
    maze_update('room',array('is_boss'=>1),'ds_id=2 AND room_id=2');
    sql_query('CREATE TABLE avo_test_point (id INT AUTO_INCREMENT PRIMARY KEY,relation_key VARCHAR(255),amount INT) ENGINE=MyISAM');
    $escape_settings['boss']=true;$escape_settings['points']=10;
    $escape_settings['rewards']=array(array('it_id'=>2,'count'=>1,'percent'=>100));
    maze_update('session',array('snapshot'=>maze_json($escape_settings)),'ds_id=2');
    $before_exit=maze_session(2);
    try{maze_action(2,'move',array('version'=>$before_exit['version'],'room_id'=>2,'direction'=>'forward'));check(false,'undefeated boss must block exit movement');}catch(RuntimeException $expected){}
    check(maze_session(2)===$before_exit && !(int)maze_one('SELECT visited FROM avo_dungeon_maze_room WHERE ds_id=2 AND room_id=3')['visited'],'undefeated boss exit rejection preserves position, phase and exit visibility');
    try{maze_transaction(2,function($session){maze_finish($session,true);});check(false,'direct clear must reject undefeated boss');}catch(RuntimeException $expected){}
    try{maze_transaction(2,function($session){$session['phase']='BOSS_RESULT';maze_finish($session,true);});check(false,'boss result without a won boss battle must reject');}catch(RuntimeException $expected){}
    check(!(int)maze_party(2)[0]['settled'] && !(int)maze_one('SELECT COUNT(*) AS n FROM avo_dungeon_maze_reward WHERE ds_id=2')['n'],'blocked boss clear never settles or creates rewards');
    $person=maze_party(2)[0];
    maze_transaction(2,function($session) use($boss){maze_battle_begin($session,maze_room($session),$boss);});
    $battle=maze_battle_current(maze_session(2));
    try{maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'SKIP','escape_vote'=>1));check(false,'boss escape vote direct request must reject');}catch(RuntimeException $expected){}
    check(!maze_actions($battle) && maze_session(2)['phase']==='BATTLE','boss escape vote is rejected before action storage');
    maze_update('battle',array('deadline_at'=>date('Y-m-d H:i:s',time()-1)),'battle_id='.(int)$battle['battle_id']);
    try{maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'SKIP','escape_vote'=>1));check(false,'expired boss escape vote must reject');}catch(RuntimeException $expected){}
    check((int)maze_battle_current(maze_session(2))['turn_no']===1 && !maze_actions($battle),'expired deadline does not bypass boss escape submission rejection');
    maze_update('battle',array('deadline_at'=>null),'battle_id='.(int)$battle['battle_id']);
    // Simulate a vote persisted before the rule change: resolve must still never flee.
    maze_insert('action',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'dm_id'=>$person['dm_id'],'action'=>'SKIP','target_id'=>$person['dm_id'],'reference_id'=>0,'escape_vote'=>1));
    maze_action(2,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>1));
    $boss_active=maze_battle_current(maze_session(2));
    check($boss_active['state']==='ACTIVE' && (int)$boss_active['hp']===5 && (int)$boss_active['turn_no']===2,'boss resolve ignores stored escape vote even at 100 percent');
    maze_update('member',array('hp'=>1,'effects'=>maze_json(array('poison'=>array('remaining'=>3,'components'=>array(array('type'=>'DOT_HP','value'=>10)))))),'dm_id='.(int)$person['dm_id']);
    maze_action(2,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>2,'action'=>'ATTACK','escape_vote'=>0));
    maze_action(2,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>2));
    check(maze_session(2)['phase']==='BOSS_RESULT' && (int)maze_party(2)[0]['hp']===1,'boss death freezes HP before end-of-turn DOT');
    $defeated=maze_one('SELECT state,hp FROM avo_dungeon_maze_battle WHERE battle_id='.(int)$battle['battle_id']);
    check($defeated['state']==='WON' && (int)$defeated['hp']===0,'boss result requires a defeated boss battle');
    foreach(array('item','move','submit','resolve','event','leave','vote') as $blocked) {
        try{maze_action(2,$blocked,array());check(false,'boss result must block '.$blocked);}catch(RuntimeException $expected){}
    }
    maze_action(2,'chat',array('message'=>'Boss defeated'));
    maze_insert('member',array('ds_id'=>2,'ch_id'=>2,'mb_id'=>'other','name'=>'Dead participant','hp'=>0,'stats'=>'{}','skills'=>'{}','effects'=>'{}','joined_at'=>date('Y-m-d H:i:s')));
    act_as(2);try{maze_action(2,'escape_final',array());check(false,'dead participant cannot trigger final exit');}catch(RuntimeException $expected){}act_as(1);
    $reward_before=(int)maze_one('SELECT COUNT(*) AS n FROM avo_inventory WHERE ch_id=1 AND it_id=2')['n'];
    $dead_reward_before=(int)maze_one('SELECT COUNT(*) AS n FROM avo_inventory WHERE ch_id=2 AND it_id=2')['n'];
    maze_action(2,'escape_final',array());
    check(maze_session(2)['phase']==='CLEAR','boss final exit uses common clear settlement');
    $settled=array_column(maze_party(2,false),null,'ch_id');
    check((int)$settled[1]['settled']===1 && (int)$settled[2]['settled']===1 && (int)$settled[1]['reward_eligible']===1 && (int)$settled[2]['reward_eligible']===0,'boss final exit settles everyone but rewards only survivors');
    try{maze_action(2,'escape_final',array());check(false,'duplicate final exit must reject');}catch(RuntimeException $expected){}
    maze_transaction(2,function($session){maze_finish($session,true);});maze_pay_rewards(2);
    check((int)maze_one('SELECT COUNT(*) AS n FROM avo_inventory WHERE ch_id=1 AND it_id=2')['n']===$reward_before+1 && (int)maze_one('SELECT COUNT(*) AS n FROM avo_inventory WHERE ch_id=2 AND it_id=2')['n']===$dead_reward_before,'boss clear item reward is granted to survivor exactly once');
    $boss_reward=maze_one('SELECT COUNT(*) AS n,SUM(amount) AS amount FROM avo_test_point WHERE relation_key LIKE \'%|clear:2\'');
    check((int)$boss_reward['n']===1 && (int)$boss_reward['amount']===10 && (int)maze_one("SELECT COUNT(*) AS n FROM avo_dungeon_maze_reward WHERE ds_id=2 AND state='DONE'")['n']===1,'boss clear point journal pays survivor exactly once');
    check((int)maze_one("SELECT COUNT(*) AS n FROM avo_dungeon_maze_log WHERE ds_id=2 AND kind='SETTLEMENT'")['n']===2,'boss final exit records one common settlement per participant');
    // Separate no-boss session verifies the ordinary exit path as well.
    maze_join(3);maze_action(3,'ready',array('ready'=>1));maze_action(3,'start',array());
    $step=maze_session(3);maze_action(3,'move',array('version'=>$step['version'],'room_id'=>1,'direction'=>'forward'));
    $won=maze_session(3);
    maze_action(3,'move',array('version'=>$won['version'],'room_id'=>2,'direction'=>'forward'));
    check(maze_session(3)['phase']==='CLEAR','exit transition settles participant');
    check((int)sql_fetch('SELECT settled FROM avo_dungeon_maze_member WHERE ds_id=3')['settled']===1,'clear settlement recorded once');
    $test_dungeon['dg_point']=10;
    add_inventory(1000);maze_join(4);maze_action(4,'ready',array('ready'=>1));maze_action(4,'start',array());
    add_inventory(1000);sql_query("UPDATE avo_inventory SET in_memo='conflicting new row' WHERE in_id=1000");
    try{maze_action(4,'leave',array('version'=>maze_session(4)['version']));check(false,'conflicting restoration must fail');}catch(InventoryStorageException $expected){}
    check(sql_fetch('SELECT in_memo FROM avo_inventory WHERE in_id=1000')['in_memo']==='conflicting new row','restoration never overwrites a conflicting ID');
    check((int)sql_fetch('SELECT settled FROM avo_dungeon_maze_member WHERE ds_id=4')['settled']===0,'failed restoration leaves participant unsettled');
    sql_query('DELETE FROM avo_inventory WHERE in_id=1000');
    maze_transaction(4,function($session){maze_finish($session,true);});
    maze_pay_rewards(4);maze_pay_rewards(4);
    check((int)sql_fetch("SELECT COUNT(*) AS n FROM avo_test_point WHERE relation_key LIKE '%|clear:4'")['n']===1,'point journal pays once across repeated requests');
    maze_update('reward',array('state'=>'PENDING'),'ds_id=4');maze_pay_rewards(4);
    check((int)sql_fetch("SELECT COUNT(*) AS n FROM avo_test_point WHERE relation_key LIKE '%|clear:4'")['n']===1,'deterministic point relation survives missing completion flag');
    $test_dungeon['dg_count']=2;act_as(1);maze_join(5);act_as(2);maze_join(5);
    act_as(3);try{maze_join(5);check(false,'third participant must reject');}catch(RuntimeException $expected){}
    check(count(maze_party(5))===2,'maximum party size enforced on server');
    $party=array_column(maze_party(5),null,'ch_id');act_as(2);
    maze_action(5,'vote',array('version'=>0,'target_id'=>$party[1]['dm_id']));
    check(count(maze_party(5))===1,'two-person party needs one vote to kick the other member');
    act_as(1);maze_join(5);check(count(maze_party(5))===2,'WAITING kick permits re-entry');
    sql_query("INSERT INTO avo_item (it_id,it_name,it_type,it_value,it_use_battle_able,it_use_ever) VALUES (4,'Revive','부활','25',1,0)");add_inventory(2000,4);
    maze_action(5,'ready',array('ready'=>1));act_as(2);maze_action(5,'ready',array('ready'=>1));act_as(1);maze_action(5,'start',array());
    $party=array_column(maze_party(5),null,'ch_id');
    maze_update('member',array('hp'=>0),'dm_id='.(int)$party[2]['dm_id']);
    maze_update('meta',array('action_timeout'=>30),'singleton=1');
    $durable_monster=$test_dungeon;$durable_monster['dg_mon_hp']=100;
    maze_transaction(5,function($session)use($durable_monster){maze_battle_begin($session,maze_room($session),$durable_monster);});
    $battle=maze_battle_current(maze_session(5));
    act_as(2);try{maze_action(5,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'ATTACK'));check(false,'dead action must reject');}catch(RuntimeException $expected){}
    $revive=maze_one('SELECT inventory_id FROM avo_dungeon_maze_inventory WHERE ds_id=5 AND it_id=4');
    act_as(1);maze_action(5,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'ITEM','target_id'=>$party[2]['dm_id'],'reference_id'=>$revive['inventory_id']));
    maze_action(5,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>1));
    $revived=array_column(maze_party(5),null,'ch_id');
    check((int)$revived[2]['hp']===25,'revive consumes owner item and restores configured HP percentage');
    check((int)maze_battle_current(maze_session(5))['hp']===100,'revived member has no action in the revival turn');
    $foreign_item=maze_one('SELECT inventory_id FROM avo_dungeon_maze_inventory WHERE ds_id=5 AND ch_id=1 AND consumed=0 LIMIT 1');
    act_as(2);try{maze_action(5,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>2,'action'=>'ITEM','target_id'=>$party[2]['dm_id'],'reference_id'=>$foreign_item['inventory_id']));check(false,'foreign dungeon item must reject');}catch(RuntimeException $expected){}
    maze_action(5,'leave',array('version'=>maze_session(5)['version']));
    try{maze_join(5);check(false,'started exit cannot rejoin');}catch(RuntimeException $expected){}
    check((int)sql_fetch('SELECT settled FROM avo_dungeon_maze_member WHERE ds_id=5 AND ch_id=2')['settled']===1,'started exit settles once and blocks re-entry');
    act_as(1);maze_action(5,'leave',array('version'=>maze_session(5)['version']));
    check(maze_session(5)['phase']==='FAILED','last participant exit terminates session');
    maze_join(6);maze_action(6,'ready',array('ready'=>1));act_as(2);maze_join(6);maze_action(6,'ready',array('ready'=>1));act_as(1);maze_action(6,'start',array());
    $party=array_column(maze_party(6),null,'ch_id');maze_update('member',array('hp'=>0),'dm_id='.(int)$party[2]['dm_id']);
    act_as(2);maze_action(6,'vote',array('version'=>maze_session(6)['version'],'target_id'=>$party[1]['dm_id']));
    check(maze_session(6)['phase']==='FAILED','dead member may vote; removing last living member fails and settles party');
    act_as(1);
    $geometry=maze_defaults($test_dungeon);$geometry['boss']=true;$geometry['monsters'][0]['boss']=true;
    for($iteration=0;$iteration<250;$iteration++) {
        $geometry['dead_end_percent']=$iteration%2?0:100;
        $rooms=maze_generate($geometry);$by_id=array_column($rooms,null,'room_id');$children=array();$exits=0;$bosses=0;
        foreach($rooms as $room) {
            if($room['parent_id'])$children[$room['parent_id']][]=$room;
            $exits+=$room['is_exit'];$bosses+=$room['is_boss'];$cursor=$room['room_id'];$steps=0;
            while($cursor){if(!isset($by_id[$cursor]) || ++$steps>count($rooms))throw new LogicException('Invalid rooted tree');$cursor=$by_id[$cursor]['parent_id'];}
        }
        $branches=count(array_filter($children,function($list){return count($list)>1;}));
        if(count($rooms)<$geometry['rooms_min'] || count($rooms)>$geometry['rooms_max'] || $branches<$geometry['branches_min'] || $branches>$geometry['branches_max'] || $exits!==1 || $bosses!==1)throw new LogicException('Invalid generation bounds');
        foreach($children as $list)if(count($list)>3 || count(array_unique(array_column($list,'direction')))!==count($list))throw new LogicException('Duplicate room direction');
    }
    check(true,'250 generated mazes satisfy tree, room, branch, direction, boss and exit invariants');
    sql_query('CREATE TABLE atomic_effect (id INT PRIMARY KEY,value INT) ENGINE=InnoDB');
    sql_query('INSERT INTO atomic_effect VALUES (1,0)');
    add_inventory(9001);
    $atomic=inventory_boundary_begin(1,array(9001),'atomic.test',array('transactional_effect_tables'=>array('atomic_effect')),'remove');
    check($GLOBALS['inventory_boundary_pending'][$atomic['journal_id']]==='ATOMIC','InnoDB effect stays in the inventory transaction');
    sql_query('UPDATE atomic_effect SET value=10 WHERE id=1');
    inventory_boundary_shutdown();
    check((bool)sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=9001') && (int)sql_fetch('SELECT value FROM atomic_effect WHERE id=1')['value']===0,'effect failure rolls back inventory and InnoDB effect together');
    $atomic=inventory_boundary_begin(1,array(9001),'atomic.test',array('transactional_effect_tables'=>array('atomic_effect')),'remove');
    sql_query('UPDATE atomic_effect SET value=20 WHERE id=1');inventory_boundary_done($atomic);
    check(!sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=9001') && (int)sql_fetch('SELECT value FROM atomic_effect WHERE id=1')['value']===20,'atomic completion commits both consumption and effect');
    add_inventory(9002);
    $review=inventory_boundary_begin(1,array(9002),'review.test',array('transactional_effect_tables'=>array('avo_item')),'remove');
    check($GLOBALS['inventory_boundary_pending'][$review['journal_id']]==='JOURNAL','MyISAM effect uses durable journal');
    inventory_boundary_shutdown();
    check(sql_fetch('SELECT state FROM avo_inventory_journal WHERE journal_id='.(int)$review['journal_id'])['state']==='REVIEW','unfinished mixed-engine effect requires explicit reconciliation');
    $is_admin=true;inventory_boundary_reconcile($review['journal_id'],true,'test verified no effect');
    check((bool)sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=9002'),'reconciliation restores the original row once');
    try{inventory_boundary_reconcile($review['journal_id'],true,'duplicate');check(false,'duplicate recovery must reject');}catch(RuntimeException $expected){}
    $is_admin=false;
    add_inventory(9003);add_inventory(9004);
    sql_query('START TRANSACTION');inventory_boundary_lock_owners(array(1));inventory_boundary_lock_rows(1,array(9003));
    sql_query('DELETE FROM avo_inventory WHERE in_id=9003');$child=child_claim(array(9003,9004));sql_query('COMMIT');
    check(child_result($child)==='REJECTED' && (bool)sql_fetch('SELECT in_id FROM avo_inventory WHERE in_id=9004'),'concurrent multi-item claim fails without consuming remaining materials');
    add_inventory(9005);
    $transfer=inventory_boundary_begin(1,array(9005),'transfer.test',array('receiver_ch_id'=>2),'hold');
    try{sql_query('START TRANSACTION');inventory_boundary_lock_owners(array(1));check(false,'pending transfer must block start');}catch(RuntimeException $expected){sql_query('ROLLBACK');}
    sql_query('UPDATE avo_inventory SET ch_id=2 WHERE in_id=9005');inventory_boundary_done($transfer);
    check((int)sql_fetch('SELECT ch_id FROM avo_inventory WHERE in_id=9005')['ch_id']===2,'transfer holds sender and recipient boundaries until ownership change');
    $test_dungeon['dg_count']=1;act_as(1);maze_join(7);maze_action(7,'ready',array('ready'=>1));
    $before_rows=inventory_boundary_rows('SELECT * FROM avo_inventory WHERE ch_id=1 ORDER BY in_id');
    sql_query("CREATE TRIGGER fail_room BEFORE INSERT ON avo_dungeon_maze_room FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='injected failure'");
    try{maze_action(7,'start',array());check(false,'injected start failure must rollback');}catch(RuntimeException $expected){}
    sql_query('DROP TRIGGER fail_room');
    check(maze_session(7)['phase']==='WAITING' && $before_rows===inventory_boundary_rows('SELECT * FROM avo_inventory WHERE ch_id=1 ORDER BY in_id') && !(int)sql_fetch('SELECT COUNT(*) AS n FROM avo_dungeon_maze_inventory WHERE ds_id=7')['n'],'failed room creation rolls back inventory escrow and entire departure');
    maze_action(7,'start',array());$is_admin=true;maze_admin_end(7,'fail');maze_admin_end(7,'fail');$is_admin=false;
    check(maze_session(7)['phase']==='FAILED' && (int)sql_fetch('SELECT settled FROM avo_dungeon_maze_member WHERE ds_id=7')['settled']===1 && sql_fetch('SELECT ds_state FROM avo_dungeon_state WHERE ds_id=7')['ds_state']==='E','repeated administrator termination settles once and closes the common state row');
    // Several units share one encounter and one set of player submissions.
    maze_update('meta',array('action_timeout'=>null),'singleton=1');
    maze_join(8);maze_action(8,'ready',array('ready'=>1));maze_action(8,'start',array());
    $pack=$test_dungeon;$pack['dg_mon_hp']=20;
    $pack['shared']=array('name'=>'Pack','max_hp'=>20,'stats'=>array(),'attack_min'=>3,'attack_max'=>3,'default_attack'=>true,'enmity_enabled'=>true,'skills'=>array(),'phases'=>array(array('below'=>100,'actions'=>array(0)),array('below'=>50,'actions'=>array(0))));
    maze_transaction(8,function($session)use($pack){maze_battle_begin($session,maze_room($session),$pack,3);});
    $group=maze_battle_group(maze_session(8),maze_battle_current(maze_session(8)));$unit_ids=array_keys($group);
    check(count($group)===3 && count(array_unique(array_map(function($u){return maze_data($u['monster'])['dg_mon_name'];},$group)))===3,'three independently named units share one encounter');
    function pack_round($action,$monster=0,$reference=0) {
        $s=maze_session(8);$b=maze_battle_current($s);
        maze_action(8,'submit',array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no'],'action'=>$action,'monster_id'=>$monster,'reference_id'=>$reference,'target_id'=>maze_party(8)[0]['dm_id']));
        maze_action(8,'resolve',array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no']));
    }
    pack_round('ATTACK',$unit_ids[1]);
    $group=maze_battle_group(maze_session(8),maze_battle_current(maze_session(8)));
    check(array_column(array_values($group),'hp')===array('20','15','20') && (int)maze_party(8)[0]['hp']===94,'selected second unit alone takes damage and all three living units act');
    $skill=array('sh_id'=>501,'sk_name'=>'Healing with cost','sh_name'=>'','sk_type'=>'액티브','sh_limit'=>0,'sk_use_st_id'=>2,'sl_use_value'=>4,'sk_status_code'=>'heal','sl_set_value'=>20,'sk_value_type'=>'+','sk_function'=>'스탯회복','sk_target'=>'자신','sk_def_code'=>'','sk_def_type'=>'','sk_mod_type'=>'+','sk_mod_st_id'=>1,'sk_keep_limit'=>0,'sk_limit'=>0,'sk_def_enermy'=>'','sk_dungeon_target_mode'=>'SINGLE');
    $buff=array_replace($skill,array('sh_id'=>502,'sk_name'=>'Real guard','sk_function'=>'방어','sk_status_code'=>'guard','sl_set_value'=>0));
    $all=array_replace($skill,array('sh_id'=>503,'sk_name'=>'All enemies','sk_function'=>'공격','sk_target'=>'적','sk_status_code'=>'atk','sl_set_value'=>0,'sk_dungeon_target_mode'=>'ALL'));
    $random=array_replace($all,array('sh_id'=>504,'sk_name'=>'Random enemy','sk_dungeon_target_mode'=>'RANDOM'));
    $actor=maze_party(8)[0];$stats=maze_data($actor['stats']);$stats[2]=50;
    maze_update('member',array('hp'=>50,'stats'=>maze_json($stats),'skills'=>maze_json(array($skill,$buff,$all,$random))),'dm_id='.(int)$actor['dm_id']);
    pack_round('SKILL',0,501);
    check((int)maze_party(8)[0]['hp']===67 && (int)maze_data(maze_party(8)[0]['stats'])[2]===46,'healing pays cost once and restores HP before each unit acts');
    $heal_logs=maze_one("SELECT COUNT(*) n FROM avo_dungeon_maze_log WHERE ds_id=8 AND kind='COMBAT' AND message LIKE '%Healing with cost%'");
    check((int)$heal_logs['n']===1 && (int)maze_one("SELECT amount FROM avo_dungeon_combat_event WHERE ds_id=8 AND action='Healing with cost' AND result='HEAL'")['amount']===23,'healing produces one actual heal event without a cost-only STATUS duplicate');
    pack_round('SKILL',0,502);
    check((int)maze_one("SELECT COUNT(*) n FROM avo_dungeon_combat_event WHERE ds_id=8 AND action='Real guard' AND result='STATUS'")['n']===1,'real status application still produces its own event');
    pack_round('SKILL',$unit_ids[0],503);
    $group=maze_battle_group(maze_session(8),maze_battle_current(maze_session(8)));
    check(array_column(array_values($group),'hp')===array('15','10','15') && (int)maze_data(maze_party(8)[0]['stats'])[2]===38,'ALL skill hits every living unit while paying one cost');
    pack_round('SKILL',$unit_ids[0],504);
    $next=maze_battle_group(maze_session(8),maze_battle_current(maze_session(8)));$changed=0;$delta=0;
    foreach($next as $uid=>$unit){$changed+=(int)$unit['hp']!==(int)$group[$uid]['hp'];$delta+=(int)$group[$uid]['hp']-(int)$unit['hp'];}
    check($changed===1 && $delta===5,'RANDOM skill affects one living unit');
    maze_update('battle',array('hp'=>1),'battle_id='.(int)$unit_ids[0]);pack_round('ATTACK',$unit_ids[0]);
    check(maze_session(8)['phase']==='BATTLE' && (int)maze_battle_current(maze_session(8))['hp']===0,'dead first unit does not end the encounter or stop other units');
    $s=maze_session(8);$b=maze_battle_current($s);
    try{maze_action(8,'submit',array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no'],'action'=>'ATTACK','monster_id'=>$unit_ids[0]));check(false,'dead target must reject');}catch(RuntimeException $expected){}
    try{maze_action(8,'submit',array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no'],'action'=>'ATTACK','monster_id'=>999999));check(false,'foreign target must reject');}catch(RuntimeException $expected){}
    check(!maze_actions($b),'dead or foreign monster target rejected before action storage');
    foreach(array_slice($unit_ids,1) as $uid)maze_update('battle',array('hp'=>1),'battle_id='.(int)$uid);
    pack_round('SKILL',$unit_ids[1],503);
    check(maze_session(8)['phase']==='EXPLORE' && (int)maze_one("SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8 AND state='WON'")['n']===3,'encounter ends only when every unit is defeated');
    $s=maze_session(8);maze_transaction(8,function($session)use($pack){maze_battle_begin($session,maze_room($session),$pack,2);});
    $b=maze_battle_current(maze_session(8));$snapshot=maze_data(maze_session(8)['snapshot']);$snapshot['escape_percent']=100;maze_update('session',array('snapshot'=>maze_json($snapshot)),'ds_id=8');
    maze_action(8,'submit',array('battle_id'=>$b['battle_id'],'turn_no'=>1,'action'=>'SKIP','escape_vote'=>1));maze_action(8,'resolve',array('battle_id'=>$b['battle_id'],'turn_no'=>1));
    check((int)maze_one("SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8 AND state='ESCAPED'")['n']===2,'escaping closes every unit in the encounter');
    $s=maze_session(8);$snapshot=maze_data($s['snapshot']);$snapshot['monsters']=array(array('monster_id'=>17,'enabled'=>true,'min_count'=>2,'max_count'=>2));
    maze_update('session',array('snapshot'=>maze_json($snapshot)),'ds_id=8');
    $before_units=(int)maze_one('SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8')['n'];
    maze_update('room',array('resolved'=>0,'payload'=>maze_json(array('event'=>array('type'=>'MONSTER','monster_id'=>19,'monster'=>$pack)))),'ds_id=8 AND room_id='.(int)$s['room_id']);
    maze_action(8,'event',array('room_id'=>$s['room_id'],'version'=>$s['version']));
    check(maze_session(8)['phase']==='EXPLORE' && (int)maze_one('SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8')['n']===$before_units,'legacy room event outside the departure pool cannot spawn a monster');
    $choices=array('enabled'=>true,'choices'=>array(array('label'=>'Excluded','type'=>'MONSTER','monster_id'=>19),array('label'=>'Text','type'=>'TEXT','text'=>'Safe'),array('label'=>'Allowed','type'=>'MONSTER','monster_id'=>17)));
    check(array_keys(maze_event_for_pool($choices,$snapshot['monsters'])['choices'])===array(1,2),'removed event choice keeps original indexes for visible allowed choices');
    check(!maze_event_for_pool(array('enabled'=>false,'type'=>'MONSTER','monster_id'=>17),$snapshot['monsters']),'disabled monster event cannot execute');
    $s=maze_session(8);
    maze_update('room',array('resolved'=>0,'payload'=>maze_json(array('event'=>array('type'=>'MONSTER','monster_id'=>17,'monster'=>$pack)))),'ds_id=8 AND room_id='.(int)$s['room_id']);
    maze_action(8,'event',array('room_id'=>$s['room_id'],'version'=>$s['version']));
    check(count(maze_battle_group(maze_session(8),maze_battle_current(maze_session(8))))===2,'event encounter uses the same configured species count');
    $b=maze_battle_current(maze_session(8));maze_action(8,'submit',array('battle_id'=>$b['battle_id'],'turn_no'=>1,'action'=>'SKIP','escape_vote'=>1));maze_action(8,'resolve',array('battle_id'=>$b['battle_id'],'turn_no'=>1));
    $before=maze_session(8);$before_count=(int)maze_one('SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8')['n'];
    sql_query("CREATE TRIGGER fail_pack BEFORE INSERT ON avo_dungeon_maze_battle FOR EACH ROW BEGIN IF NEW.encounter_id>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='pack fixture failure'; END IF; END");
    try{maze_transaction(8,function($session)use($pack){maze_battle_begin($session,maze_room($session),$pack,3);});check(false,'partial group insert must fail');}catch(InventoryStorageException $expected){}
    sql_query('DROP TRIGGER fail_pack');
    check(maze_session(8)===$before && (int)maze_one('SELECT COUNT(*) n FROM avo_dungeon_maze_battle WHERE ds_id=8')['n']===$before_count,'failure creating second unit rolls back the whole encounter');
    maze_update('room',array('is_boss'=>1),'ds_id=8 AND room_id='.(int)$before['room_id']);$pack['dg_mon_hp']=1;
    maze_transaction(8,function($session)use($pack){maze_battle_begin($session,maze_room($session),$pack,2);});$units=array_keys(maze_battle_group(maze_session(8),maze_battle_current(maze_session(8))));
    pack_round('ATTACK',$units[0]);check(maze_session(8)['phase']==='BATTLE','one defeated boss leaves the other boss active');
    pack_round('ATTACK',$units[1]);check(maze_session(8)['phase']==='BOSS_RESULT','all bosses must be defeated before the final exit');
    maze_action(8,'escape_final',array());
    function readiness_probe() {
        global $database;
        $pipes=array();$process=proc_open(array(PHP_BINARY,__FILE__,'readiness',$database),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0 || $error!=='')throw new LogicException('Readiness process failed: '.$error);
        return trim($out);
    }
    sql_query('ALTER TABLE avo_inventory ENGINE=MyISAM');
    check(readiness_probe()==='BLOCKED','unconverted inventory blocks labyrinth readiness');
    sql_query('ALTER TABLE avo_inventory ENGINE=InnoDB');
    sql_query('ALTER TABLE avo_dungeon_maze_action DROP PRIMARY KEY');
    check(readiness_probe()==='BLOCKED','partial migration with missing action uniqueness is blocked');
    sql_query('ALTER TABLE avo_dungeon_maze_action ADD PRIMARY KEY(battle_id,turn_no,dm_id)');
    sql_query('ALTER TABLE avo_dungeon_maze_member DROP KEY daily_settled');
    check(readiness_probe()==='BLOCKED','missing required lookup index is blocked');
    sql_query('ALTER TABLE avo_dungeon_maze_member ADD KEY daily_settled(ch_id,settled,exited_at)');
    check(readiness_probe()==='READY','complete engine/schema/index contract enables readiness again');
    echo "All inventory integration checks passed.\n";
} finally {
    sql_query('ROLLBACK', false);
    $GLOBALS['inventory_boundary_pending'] = array();
    $db->query('DROP DATABASE `'.$database.'`');
}
