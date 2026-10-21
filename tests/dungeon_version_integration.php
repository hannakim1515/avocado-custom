<?php
/** Included by dungeon_inventory_integration.php inside its disposable DB fixture. */
if (!defined('_GNUBOARD_') || !isset($database)) exit('Run tests/dungeon_inventory_integration.php');

$test_dungeon['dg_count']=2;$test_dungeon['dg_point']=0;$config['cf_dungeon_enter']=1000;
$settings=maze_defaults($test_dungeon);
$settings['rooms_min']=3;$settings['rooms_max']=3;$settings['branches_min']=0;$settings['branches_max']=0;
$settings['encounter_percent']=0;$settings['boss']=false;$settings['events']=array();
$settings['weights']=array('EMPTY'=>1,'SEARCH'=>0,'TREASURE'=>0,'TRAP'=>0);
$test_dungeon['dg_settings']=maze_json($settings);
act_as(1);maze_join(50);act_as(2);maze_join(50);
act_as(1);$member['ch_id']=1;$first=maze_client_state(50);
act_as(2);maze_action(50,'ready',array('ready'=>1));
act_as(1);$member['ch_id']=1;$second=maze_client_state(50);
check($second['version']===$first['version']+1 && $second['party'][1]['ready'],'version: peer sees READY with a newer version');

// Execute the production polling query/branch/output, using this fixture's auth/DB.
$poll_source=file_get_contents(G5_PATH.'/dungeon/maze_state.php');
$poll_body=substr($poll_source,strpos($poll_source,'try {'));
$saved_get=$_GET;$version=$second['version'];
$last_log=(int)maze_one('SELECT MAX(log_id) n FROM avo_dungeon_maze_log WHERE ds_id=50')['n'];
act_as(2);$GLOBALS['query_capture']=array();maze_action(50,'chat',array('message'=>'peer chat without a version bump'));
$chat_queries=$GLOBALS['query_capture'];unset($GLOBALS['query_capture']);
act_as(1);$member['ch_id']=1;$chat_state=maze_client_state(50);
check($chat_state['version']===$version && array_column($chat_state['chat'],'message')===array('peer chat without a version bump'),'version: peer client state receives chat without version change');
check(!array_filter($chat_queries,function($query){return strpos($query,'UPDATE `avo_dungeon_state`')===0;}),'version: chat performs no session UPDATE');
$_GET=array('ds_id'=>50,'known_version'=>$version,'last_chat_id'=>0,'last_log_id'=>$last_log,'last_event_id'=>0);
ob_start();eval($poll_body);$poll=json_decode(ob_get_clean(),true);
check(!isset($poll['error']) && $poll['known_version']===$version && array_column($poll['chat'],'message')===array('peer chat without a version bump'),'version: real polling delivers chat at the same known_version');
$_GET['last_chat_id']=(int)$poll['chat'][0]['log_id'];
$GLOBALS['query_capture']=array();ob_start();eval($poll_body);$idle=json_decode(ob_get_clean(),true);$idle_queries=$GLOBALS['query_capture'];unset($GLOBALS['query_capture']);
check($idle===array('chat'=>array(),'logs'=>array(),'events'=>array(),'known_version'=>$version) && count($idle_queries)===1,'version: caught-up polling returns empty deltas after one query');

maze_action(50,'ready',array('ready'=>1));maze_action(50,'start',array());
$session=maze_session(50);$actor=maze_party(50)[0];
$item=get_item(1);$item['st_id']=1;$item['it_value']=5;
maze_inventory_gain($session,$actor,$item);
maze_update('member',array('hp'=>50),'dm_id='.(int)$actor['dm_id']);
$inventory=maze_one('SELECT inventory_id FROM avo_dungeon_maze_inventory WHERE ds_id=50 AND origin=\'FOUND\' ORDER BY inventory_id DESC LIMIT 1');
$before_item=(int)$session['version'];
maze_action(50,'item',array('version'=>$session['version'],'room_id'=>1,'target_id'=>$actor['dm_id'],'inventory_id'=>$inventory['inventory_id']));
check((int)maze_session(50)['version']===$before_item+1 && (int)maze_party(50)[0]['hp']===55,'version: exploration item use increments version and applies its effect');

act_as(2);$member['ch_id']=2;$before_move=maze_client_state(50);
act_as(1);$session=maze_session(50);maze_action(50,'move',array('version'=>$session['version'],'room_id'=>1,'direction'=>'forward'));
act_as(2);$member['ch_id']=2;$after_move=maze_client_state(50);
check($after_move['version']===$before_move['version']+1 && $after_move['room']===2,'version: peer sees movement with a newer version');
$_GET=array('ds_id'=>50,'known_version'=>$before_move['version'],'last_chat_id'=>$_GET['last_chat_id'],'last_log_id'=>$last_log,'last_event_id'=>0);
ob_start();eval($poll_body);$moved_poll=json_decode(ob_get_clean(),true);
check(!isset($moved_poll['error']) && $moved_poll['room']===2 && $moved_poll['known_version']===$after_move['version'],'version: polling returns changed movement state');

act_as(1);$before_begin=(int)maze_session(50)['version'];
$monster=maze_monster_definition(array('dg_mon_name'=>'Version probe','dg_mon_hp'=>8),$test_dungeon);
maze_transaction(50,function($session)use($monster){maze_battle_begin($session,maze_room($session),$monster);});
act_as(2);$member['ch_id']=2;$begun=maze_client_state(50);
check($begun['version']===$before_begin+1 && $begun['phase']==='BATTLE' && $begun['battle']['id']>0,'version: battle begin now increments version and is visible to peer');

$session=maze_session(50);$battle=maze_battle_current($session);$party=array_column(maze_party(50),null,'ch_id');
maze_action(50,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'SKIP'));
act_as(1);$member['ch_id']=1;$submitted=maze_client_state(50);
check($submitted['version']===$begun['version']+1 && in_array((int)$party[2]['dm_id'],$submitted['battle']['submitted'],true),'version: peer sees submitted action');
act_as(2);maze_action(50,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'SKIP'));
check((int)maze_session(50)['version']===$submitted['version'],'version: identical submission preserves ROW_COUNT no-op behavior');
act_as(1);maze_action(50,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>1,'action'=>'ATTACK'));
$before_turn=(int)maze_session(50)['version'];maze_action(50,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>1));
act_as(2);$member['ch_id']=2;$turn=maze_client_state(50);
check($turn['version']===$before_turn+1 && $turn['phase']==='BATTLE' && $turn['battle']['turn']===2,'version: ongoing turn resolution increments version and updates peer');
maze_action(50,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>2,'action'=>'SKIP'));
act_as(1);maze_action(50,'submit',array('battle_id'=>$battle['battle_id'],'turn_no'=>2,'action'=>'ATTACK'));
$before_end=(int)maze_session(50)['version'];maze_action(50,'resolve',array('battle_id'=>$battle['battle_id'],'turn_no'=>2));
act_as(2);$member['ch_id']=2;$ended=maze_client_state(50);
check($ended['version']===$before_end+1 && $ended['phase']==='EXPLORE' && !$ended['battle'],'version: battle end still increments version and updates peer');

$before_leave=(int)maze_session(50)['version'];maze_action(50,'leave',array('version'=>$before_leave));
act_as(1);$member['ch_id']=1;$left=maze_client_state(50);
check($left['version']===$before_leave+1 && $left['party'][1]['state']==='EXIT','version: leave increments version and updates remaining peer');
$session=maze_session(50);maze_action(50,'event',array('version'=>$session['version'],'room_id'=>2));
$session=maze_session(50);$before_clear=(int)$session['version'];
maze_action(50,'move',array('version'=>$session['version'],'room_id'=>2,'direction'=>'forward'));
check(maze_session(50)['phase']==='CLEAR' && (int)maze_session(50)['version']===$before_clear+2,'version: exit movement and settlement each increment version once');

maze_join(60);maze_update('session',array('expires_at'=>date('Y-m-d H:i:s',time()-5)),'ds_id=60');
$before_expire=(int)maze_session(60)['version'];maze_expire_waiting();
check(maze_session(60)['phase']==='CLOSED' && (int)maze_session(60)['version']===$before_expire+1,'version: waiting expiry increments version');
$before_empty=(int)maze_session(60)['version'];maze_update('session',array(),'ds_id=60');
check((int)maze_session(60)['version']===$before_empty+1,'version: empty session data still produces a valid version-only UPDATE');

// sql_query checks actual before/after row versions for every helper-driven UPDATE.
$expected_sites=0;
foreach(array('extend/dungeon_maze_actions.lib.php','extend/dungeon_maze_combat.lib.php','extend/dungeon_maze_session.lib.php') as $file){
    foreach(file(G5_PATH.'/'.$file) as $index=>$line)if(strpos($line,"maze_update('session'")!==false){
        $site=str_replace('\\','/',G5_PATH).'/'.$file.':'.($index+1);$expected_sites++;
        check(!empty($GLOBALS['maze_version_checks'][$site]),'version: actual +1 verified at '.$file.':'.($index+1));
    }
}
check($expected_sites===14,'version: all fourteen remaining product session UPDATE call sites were executed');
echo 'Verified helper session UPDATE executions: '.array_sum($GLOBALS['maze_version_checks'])."\n";
$_GET=$saved_get;
