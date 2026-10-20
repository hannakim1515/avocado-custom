<?php
if(!defined('_GNUBOARD_'))exit;
require_once __DIR__.'/monster_shared.lib.php';

function monster_shared_maze($session,&$battle,&$party,&$companions,&$runtimes) {
    $monster_definition=maze_data($battle['monster']);
    if(empty($monster_definition['shared']))return false;
    if($battle['weak_turn']>0) {
        $battle['weak_turn']--;
        $runtime=&$runtimes[(int)$battle['battle_id']];
        monster_shared_prepare($runtime,(int)$battle['turn_no']+1,array('hp'=>$battle['hp'],'max_hp'=>$monster_definition['dg_mon_hp']));
        return true;
    }
    $settings=maze_data($session['snapshot']);
    $runtime=&$runtimes[(int)$battle['battle_id']];
    $self=array('id'=>$companions===null?0:(int)$battle['battle_id'],'hp'=>(int)$battle['hp'],'max_hp'=>(int)$monster_definition['dg_mon_hp'],'name'=>$monster_definition['dg_mon_name']);
    $allies=array($self);
    if($companions!==null) {
        $allies=array();
        foreach($companions as $unit) {
            $definition=maze_data($unit['monster']);
            $allies[]=array('id'=>(int)$unit['battle_id'],'hp'=>(int)$unit['hp'],'max_hp'=>(int)$definition['dg_mon_hp'],'name'=>$definition['dg_mon_name']);
        }
    }
    $enemies=array();
    foreach($party as $id=>$participant) {
        $enemies[]=array('id'=>$id,'hp'=>(int)$participant['hp'],'max_hp'=>(int)$participant['max_hp'],'name'=>$participant['name']);
    }
    $acted=monster_shared_turn($runtime,(int)$battle['turn_no'],$self,$enemies,$allies,function($sk,$targets,$amount)use(&$runtime,&$runtimes,&$battle,&$party,&$companions,$session,$settings,$monster_definition){
        foreach($targets as $target) {
            $id=$target['id'];
            $actual=$amount;
            $result=$sk['type'];
            if($sk['target']==='enemy') {
                if($party[$id]['hp']<=0)continue;
                $participant=&$party[$id];
                $before=(int)$participant['hp'];
                if($sk['type']==='DAMAGE') {
                    $evaded=maze_evasion($participant);
                    foreach($settings['codes'] as $code)if(!$evaded && $code['ex_name']==='자동회피' && random_int(0,1000)<=maze_code($session,$participant,'자동회피'))$evaded=true;
                    $actual=$evaded?0:max(0,$amount-maze_code($session,$participant,$settings['defense_code']));
                    $actual=(int)round($actual*(1-min(90,max(0,maze_effect_value($participant,'GUARD')))/100));
                    $participant['hp']=max(0,$participant['hp']-$actual);
                    $actual=$before-$participant['hp'];
                    if($evaded)$result='EVADE';
                }elseif($sk['type']==='HEAL'){
                    maze_heal($participant,$amount);
                    $actual=$participant['hp']-$before;
                }
                elseif($sk['type']==='MP_HEAL'){
                    $stats=maze_data($participant['stats']);
                    $mp=(int)$settings['mp_id'];
                    $before=(int)($stats[$mp]??0);
                    $stats[$mp]=min((int)($stats['_maximum'][$mp]??$before),$before+$amount);
                    $actual=$stats[$mp]-$before;
                    $participant['stats']=maze_json($stats);
                }
                elseif($sk['type']==='GUARD'){
                    $effects=maze_data($participant['effects']);
                    $effects['monster:'.$sk['id']]=array('remaining'=>$sk['duration']+1,'components'=>array(array('type'=>'GUARD','value'=>$amount)));
                    $participant['effects']=maze_json($effects);
                }
                unset($participant);
            }else {
                if($companions===null)$unit=&$battle;else $unit=&$companions[$id];
                $definition=maze_data($unit['monster']);
                if($sk['type']==='DAMAGE'){$before=$unit['hp'];$unit['hp']=max(0,$unit['hp']-$amount);$actual=$before-$unit['hp'];}
                elseif($sk['type']==='HEAL'){$before=$unit['hp'];$unit['hp']=min($definition['dg_mon_hp'],$unit['hp']+$amount);$actual=$unit['hp']-$before;}
                elseif($sk['type']==='MP_HEAL'){$actual=0;}
                elseif($sk['type']==='GUARD'){
                    if((int)$unit['battle_id']===(int)$battle['battle_id']){$runtime['guard']=min(90,$amount);$runtime['guard_until']=$battle['turn_no']+$sk['duration'];}
                    elseif(!empty($definition['shared'])){$runtimes[(int)$unit['battle_id']]['guard']=min(90,$amount);$runtimes[(int)$unit['battle_id']]['guard_until']=$battle['turn_no']+$sk['duration'];}
                }
                unset($unit);
            }
            maze_combat_event($session,$battle['turn_no'],$monster_definition['dg_mon_name'],$target['name'],$sk['name'],$actual,$result);
        }
        return $battle['hp']>0;
    });
    $participants=array();
    foreach($party as $participant_id=>$participant) {
        $participants[]=array('id'=>$participant_id,'hp'=>$participant['hp']);
    }
    monster_shared_prune($runtime,$participants);
    monster_shared_prepare($runtime,(int)$battle['turn_no']+1,array('hp'=>$battle['hp'],'max_hp'=>$monster_definition['dg_mon_hp']));
    if(!$acted)maze_combat_event($session,$battle['turn_no'],$monster_definition['dg_mon_name'],$monster_definition['dg_mon_name'],'대기',0,'SKIP');
    return true;
}

/** MMB's manual pattern action advances this monster's shared turn cursor. */
function monster_shared_mmb_step($unit,$ra_id,$option='') {
    global $battle_table;
    if(!monster_shared_ready() || empty($unit['rm_id']) || ($unit['unit_type']??'')!=='mo' || (string)$unit['ra_id']!==(string)$ra_id)return false;
    $key='raid:'.$battle_table.':'.$ra_id;$id=(int)$unit['rm_id'];
    $lock='mmb_pattern_'.sha1($key.':'.$id);
    if(empty(sql_fetch('SELECT GET_LOCK('.monster_quote($lock).',0) acquired')['acquired']))throw new RuntimeException('몬스터 행동 처리 중입니다. 잠시 뒤 다시 시도하세요.');
    try {
        $current=get_k_unit($id,'mo');
        if(!$current || (int)$current['hp_now']<=0)return false;
        $saved=sql_fetch('SELECT state FROM `'.monster_table('monster_runtime').'` WHERE battle_key='.monster_quote($key).' AND unit_key='.monster_quote($id));
        if(!$saved)return false;
        $runtime=monster_shared_normalize_runtime(json_decode($saved['state'],true));
        $turn=max(1,(int)($runtime['mmb_turn']??1));
        if(!monster_shared_raid($current,$ra_id,$turn,$option))return false;
        $runtime=monster_shared_runtime($key,$id,$runtime['definition']);$runtime['mmb_turn']=$turn+1;
        monster_shared_save($key,$id,$runtime);
        return true;
    } finally {sql_query('SELECT RELEASE_LOCK('.monster_quote($lock).')',false);}
}

function monster_shared_raid($unit,$ra_id,$turn,$option='',$system='') {
    global $battle_table;
    $key='raid:'.$battle_table.':'.$ra_id;$id=(int)$unit['rm_id'];
    $saved=sql_fetch('SELECT state FROM `'.monster_table('monster_runtime').'` WHERE battle_key='.monster_quote($key).' AND unit_key='.monster_quote($id));
    if(!$saved)return false; // Only units registered with a shared snapshot use this path.
    $runtime=monster_shared_normalize_runtime(json_decode($saved['state'],true));$d=$runtime['definition'];
    $self=array('id'=>$id,'hp'=>(int)$unit['hp_now'],'max_hp'=>(int)$unit['hp_max'],'name'=>$d['name'],'unit_type'=>'mo');$enemies=array();$allies=array();
    foreach(monster_rows('SELECT u.*,COALESCE(c.ch_name,m.mo_name) name FROM `'.$battle_table.'_unit` u LEFT JOIN `'.$GLOBALS['g5']['character_table']."` c ON u.unit_type='ch' AND c.ch_id=u.unit_id LEFT JOIN `".$GLOBALS['g5']['k_monster_table']."` m ON u.unit_type='mo' AND m.mo_id=u.unit_id WHERE u.ra_id=".monster_quote($ra_id).' AND u.hp_now>0') as $unit_row) {
        $participant=array('id'=>(int)$unit_row['rm_id'],'hp'=>(int)$unit_row['hp_now'],'max_hp'=>(int)$unit_row['hp_max'],'name'=>$unit_row['name'],'unit_type'=>$unit_row['unit_type']);
        if($unit_row['unit_type']==='ch') $enemies[]=$participant;
        else $allies[]=$participant;
    }
    $acted=monster_shared_turn($runtime,$turn,$self,$enemies,$allies,function($sk,$targets,$amount)use(&$runtime,$unit,$id,$key,$ra_id,$turn,$option,$system,$battle_table){
        foreach($targets as $target) {
            $tid=(int)$target['id'];$p=get_k_unit($tid,$target['unit_type']);if(!$p || $p['hp_now']<=0)continue;$actual=$amount;$result=$sk['type'];
            if($sk['type']==='DAMAGE') {
                $evasion=sql_fetch('SELECT bf_id FROM `'.$battle_table.'_buff` WHERE rm_id='.$tid." AND si_code='unified_evade' AND turn_left>0 LIMIT 1");
                $miss=get_k_battle_func('miss',$p,$unit,true);$evaded=$evasion || random_int(1,100)<=(int)($miss['value']??0);
                $defense=($target['unit_type']==='ch' && function_exists('unified_status_extra_value_from_types'))?(int)(unified_status_extra_value_from_types('방어력',function($type)use($p){return unified_combat_unit_type_value($p,$type);})['value']??0):0;
                $value=$evaded?0:k_guard_damage_value($p,-max(0,$amount-$defense));$actual=min((int)$p['hp_now'],abs($value));set_k_dmg($p,'hp',$value,false,true);if($evaded)$result='EVADE';
            }elseif($sk['type']==='HEAL'){$actual=min($amount,$p['hp_max']-$p['hp_now']);set_k_dmg($p,'hp',$amount);}
            elseif($sk['type']==='MP_HEAL'){$actual=$target['unit_type']==='ch'?min($amount,$p['mp_max']-$p['mp_now']):0;if($actual>0)set_k_dmg($p,'mp',$actual);}
            elseif($sk['type']==='GUARD'){insert_k_buff($tid,array('si_code'=>'unified_guard','target_sc'=>0,'cs_id'=>$sk['id'],'sk_turn'=>$sk['duration']),min(90,$amount),$ra_id);}
            $msg='<p>'.htmlspecialchars($runtime['definition']['name'],ENT_QUOTES,'UTF-8').' → '.htmlspecialchars($target['name'],ENT_QUOTES,'UTF-8').'</p><p>'.htmlspecialchars($sk['name'],ENT_QUOTES,'UTF-8').' · '.$actual.' '.$result.' · 턴 '.$turn.'</p>';
            insert_k_log($msg,$ra_id,'mo',$option,$system);
        }
        return (int)sql_fetch('SELECT hp_now FROM `'.$battle_table.'_unit` WHERE rm_id='.$id)['hp_now']>0;
    });
    monster_shared_prune($runtime,monster_rows('SELECT rm_id id,hp_now hp FROM `'.$battle_table.'_unit` WHERE ra_id='.monster_quote($ra_id)." AND unit_type='ch'"));
    $hp_next=sql_fetch('SELECT hp_now,hp_max FROM `'.$battle_table.'_unit` WHERE rm_id='.$id);
    monster_shared_prepare($runtime,$turn+1,array('hp'=>$hp_next['hp_now'],'max_hp'=>$hp_next['hp_max']));
    sql_query('UPDATE `'.$battle_table.'_unit` SET mp_now=0,mp_max=0 WHERE rm_id='.$id);monster_shared_save($key,$id,$runtime);
    monster_shared_raid_announce($runtime,$turn+1,(int)sql_fetch('SELECT hp_now FROM `'.$battle_table.'_unit` WHERE rm_id='.$id)['hp_now'],$ra_id,$option);
    if(!$acted)insert_k_log('<p>'.htmlspecialchars($d['name'],ENT_QUOTES,'UTF-8').' · 대기 · 턴 '.$turn.'</p>',$ra_id,'mo',$option,$system);
    return true;
}
