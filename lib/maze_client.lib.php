<?php
if (!defined('_GNUBOARD_')) exit;
function maze_skill_available($session,$actor,$skill,$settings=null,$stats=null) {
    if($settings===null)$settings=maze_data($session['snapshot']);
    if($stats===null)$stats=maze_data($actor['stats']);
    $id=(int)$skill['sk_use_st_id'];$cost=max(0,(int)$skill['sl_use_value']);$hp=(int)($settings['hp_id']??0);
    $available=$id===$hp?(int)$actor['hp']:(int)($stats[$id]??0);
    $reason=$skill['sk_type']==='패시브'?'패시브':((int)$skill['sh_limit']>0?'재사용 대기 '.$skill['sh_limit'].'턴':($id && ($available<$cost || ($id===$hp && $available===$cost))?'자원 부족':''));
    $name='';foreach($settings['stat_definitions']??array() as $definition) if((int)$definition['st_id']===$id)$name=$definition['st_name'];
    return array('id'=>(int)$skill['sh_id'],'name'=>$skill['sh_name']?:$skill['sk_name'],'resource'=>$name,'cost'=>$cost,'available'=>$available,'reason'=>$reason,'target'=>$skill['sk_target'],'function'=>$skill['sk_function']);
}
/** Only public, visible state. Never reveals unvisited rooms, RNG or future encounters. */
function maze_client_state($ds_id,$last_log=0,$last_chat=0,$last_event=0) {
    global $member;
    require_once __DIR__.'/monster_shared.lib.php';
    if (empty($member['mb_id'])) throw new RuntimeException('로그인 후 이용하세요.');
    $session=maze_one('SELECT * FROM `'.maze_table('session').'` WHERE ds_id='.(int)$ds_id);
    $people=$session?maze_party($ds_id,false):array();
    $actor=null;
    foreach($people as $person) {
        if((int)$person['ch_id']===(int)$member['ch_id'] && $person['mb_id']===$member['mb_id']) {
            $actor=$person;
            break;
        }
    }
    if (!$actor) throw new RuntimeException('미궁 참가자가 아닙니다.');
    $settings=maze_data($session['snapshot']);
    $battle=null;
    $room=null;
    $choices=array();
    $view_phase=$session['phase'];
    $prompt='';
    if ($session['room_id']) $room=maze_one('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.(int)$ds_id.' AND room_id='.(int)$session['room_id']);
    if ($session['phase']==='BATTLE' && $session['battle_id']) $battle=maze_one('SELECT * FROM `'.maze_table('battle').'` WHERE ds_id='.(int)$ds_id.' AND battle_id='.(int)$session['battle_id']." AND state='ACTIVE'");
    if ($room && $session['phase']==='EXPLORE') {
        foreach(monster_rows('SELECT direction FROM `'.maze_table('room').'` WHERE ds_id='.(int)$ds_id.' AND parent_id='.(int)$session['room_id']) as $next) $choices[]=array('op'=>'move','label'=>array('forward'=>'앞으로 간다','left'=>'왼쪽으로 간다','right'=>'오른쪽으로 간다')[$next['direction']],'data'=>array('direction'=>$next['direction']));
        if($room['parent_id']) $choices[]=array('op'=>'move','label'=>'이전 방으로 돌아간다','data'=>array('direction'=>'back'));
        if(!$room['resolved']) {
            $payload=maze_data($room['payload']);$event=maze_event_for_pool($payload['event']??null,$settings['monsters']??array());
            $view_phase='INVESTIGATE';$prompt=$event['prompt']??$event['text']??'주변에서 조사할 대상을 선택하세요.';
            if(!empty($event['choices'])) foreach($event['choices'] as $index=>$choice)$choices[]=array('op'=>'event','label'=>$choice['label'],'data'=>array('choice'=>$index));
            else $choices[]=array('op'=>'event','label'=>$room['kind']==='TREASURE'?'상자를 연다':'조사한다','data'=>array());
        }
    }
    $party=array();
    $mp_id=(int)($settings['mp_id']??0);
    $actor_stats=null;
    $actor_skills=null;
    foreach($people as $person) {
        $stats=maze_data($person['stats']);
        $person_skills=maze_data($person['skills']);
        $resources=array();
        if((int)$person['dm_id']===(int)$actor['dm_id']) {
            $actor_stats=$stats;
            $actor_skills=$person_skills;
        }
        $ids=$mp_id?array($mp_id):array();
        foreach($person_skills as $skill) if(!empty($skill['sk_use_st_id']) && (int)$skill['sk_use_st_id']!==(int)($settings['hp_id']??0))$ids[]=(int)$skill['sk_use_st_id'];
        foreach(array_unique($ids) as $id) if(isset($stats[$id])) {
            $name='자원';
            foreach($settings['stat_definitions']??array() as $stat_definition) {
                if((int)$stat_definition['st_id']===$id) $name=$stat_definition['st_name'];
            }
            $resources[]=array('id'=>$id,'name'=>$name,'value'=>(int)$stats[$id],'max'=>(int)($stats['_maximum'][$id]??$stats[$id]));
        }
        $party[]=array('id'=>(int)$person['dm_id'],'name'=>$person['name'],'state'=>$person['state'],'ready'=>(bool)$person['ready'],'hp'=>(int)$person['hp'],'max_hp'=>(int)$person['max_hp'],'resources'=>$resources,'effects'=>maze_data($person['effects']));
    }
    $skills=array();
    foreach($actor_skills as $skill) $skills[]=maze_skill_available($session,$actor,$skill,$settings,$actor_stats);
    $items=array();
    if($actor['state']==='ACTIVE') {
        foreach(monster_rows('SELECT inventory_id,item_snapshot FROM `'.maze_table('inventory').'` WHERE ds_id='.(int)$ds_id.' AND dm_id='.(int)$actor['dm_id'].' AND consumed=0 AND restored=0') as $stored_item) {
            $item=inventory_boundary_decode($stored_item['item_snapshot']);
            $items[]=array('id'=>(int)$stored_item['inventory_id'],'name'=>$item['it_name']);
        }
    }
    $monsters=array();
    $submitted=array();
    $mine=null;
    if($battle) {
        foreach(maze_battle_group($session,$battle) as $unit) {
            $monster_definition=maze_data($unit['monster']);
            $forecast=empty($monster_definition['shared'])?array():monster_shared_forecast(monster_shared_runtime('maze:'.$unit['battle_id'],0,$monster_definition['shared']),(int)$battle['turn_no'],array('hp'=>$unit['hp'],'max_hp'=>$monster_definition['dg_mon_hp']),$party,(int)$unit['weak_turn']>0);
            $monsters[]=array('id'=>(int)$unit['battle_id'],'name'=>$monster_definition['dg_mon_name'],'image'=>$monster_definition['dg_mon_img']??'','hp'=>(int)$unit['hp'],'max_hp'=>(int)$monster_definition['dg_mon_hp'],'boss'=>(bool)$room['is_boss'],'state'=>$unit['hp']>0?'ALIVE':'DEAD','forecast'=>$forecast);
        }
        $action_rows=monster_rows('SELECT dm_id,action,reference_id,monster_id,escape_vote FROM `'.maze_table('action').'` WHERE battle_id='.(int)$battle['battle_id'].' AND turn_no='.(int)$battle['turn_no']);
        $submitted=array_map('intval',array_column($action_rows,'dm_id'));
        foreach($action_rows as $action) {
            if((int)$action['dm_id']===(int)$actor['dm_id']) {
                $mine=array('action'=>$action['action'],'reference_id'=>(int)$action['reference_id'],'monster_id'=>(int)$action['monster_id'],'escape_vote'=>(int)$action['escape_vote']);
            }
        }
    }
    $chat=monster_rows('SELECT l.log_id,l.message,l.created_at,m.name FROM `'.maze_table('log').'` l LEFT JOIN `'.maze_table('member').'` m ON m.dm_id=l.dm_id WHERE l.ds_id='.(int)$ds_id." AND l.kind='CHAT' AND l.log_id>".(int)$last_chat.' ORDER BY l.log_id '.($last_chat>0?'ASC':'DESC').' LIMIT 60');
    $logs=monster_rows('SELECT log_id,kind,message,created_at FROM `'.maze_table('log').'` WHERE ds_id='.(int)$ds_id." AND kind <> 'CHAT' AND log_id>".(int)$last_log.' ORDER BY log_id '.($last_log>0?'ASC':'DESC').' LIMIT 80');
    $events=monster_rows('SELECT event_id,turn_no,actor,target,action,amount,result,created_at FROM `'.monster_table('dungeon_combat_event')."` WHERE source='maze' AND ds_id=".(int)$ds_id.' AND event_id>'.(int)$last_event.' ORDER BY event_id '.($last_event>0?'ASC':'DESC').' LIMIT 80');
    $map=monster_rows('SELECT room_id,parent_id,kind,is_boss FROM `'.maze_table('room').'` WHERE ds_id='.(int)$ds_id.' AND visited=1 ORDER BY room_id');
    return array(
        'view_phase'=>$view_phase,
        'prompt'=>$prompt,
        'version'=>(int)$session['version'],
        'phase'=>$session['phase'],
        'room'=>(int)$session['room_id'],
        'title'=>$settings['title']??'미궁',
        'background'=>$settings['background']??'',
        'description'=>$settings['description']??'',
        'min'=>(int)$settings['min_players'],
        'max'=>(int)$settings['max_players'],
        'actor'=>(int)$actor['dm_id'],
        'active'=>$actor['state']==='ACTIVE',
        'party'=>$party,
        'monsters'=>$monsters,
        'skills'=>$skills,
        'items'=>$items,
        'choices'=>$choices,
        'map'=>$map,
        'result'=>$room['result_text']??'',
        'battle'=>$battle?array(
            'id'=>(int)$battle['battle_id'],
            'turn'=>(int)$battle['turn_no'],
            'expired'=>$battle['deadline_at'] && strtotime($battle['deadline_at'])<=time(),
            'deadline'=>$battle['deadline_at']?strtotime($battle['deadline_at'])*1000:null,
            'submitted'=>$submitted,
            'mine'=>$mine
        ):null,
        'chat'=>$last_chat>0?$chat:array_reverse($chat),
        'logs'=>$last_log>0?$logs:array_reverse($logs),
        'events'=>$last_event>0?$events:array_reverse($events)
    );
}
