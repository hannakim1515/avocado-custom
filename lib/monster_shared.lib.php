<?php
if (!defined('_GNUBOARD_')) exit;
require_once __DIR__.'/monster.lib.php';

/** One definition and scheduler for Dungeon, Maze and Raid. No schema writes here. */
function monster_shared_ready() {
    return monster_ready() && (bool)sql_fetch('SHOW TABLES LIKE '.monster_quote(monster_table('monster_runtime')), false);
}
function monster_shared_normalize_skill($skill) {
    $skill=array_replace(array('count'=>1,'duration'=>1,'stat_id'=>0,'factor'=>0),$skill);
    unset($skill['mp'],$skill['cooldown']);
    $skill['type']=strtoupper((string)($skill['type']??''));
    if ($skill['type']==='HEAL' && strtoupper((string)($skill['effect']??'HP'))==='MP') $skill['type']='MP_HEAL';
    $skill['mode']=strtoupper((string)($skill['mode']??$skill['target_count']??'SINGLE'));
    $skill['cue']=$skill['cue']??$skill['ready_text']??'';
    return $skill;
}
function monster_shared_skill($row) {
    $info=json_decode($row['sk_info']??'',true);
    if (!is_array($info) || empty($info['monster_shared'])) return null;
    return monster_shared_normalize_skill(array_replace($info['monster_shared'],array('id'=>(int)$row['sk_id'],'name'=>$row['sk_name'])));
}
function monster_shared_skills() {
    $out=array();
    foreach(monster_rows('SELECT * FROM `'.monster_table('k_battle_skill')."` WHERE unit_type='mo' ORDER BY sk_id") as $row) {
        $skill=monster_shared_skill($row); if($skill)$out[$skill['id']]=$skill;
    }
    return $out;
}
/** Adjacent HP ranges share a boundary once: lower exclusive, upper inclusive. */
function monster_shared_validate_hp_range($entry) {
    $upper=filter_var($entry['hp_below']??100,FILTER_VALIDATE_INT);
    $lower=filter_var($entry['hp_above']??0,FILTER_VALIDATE_INT);
    if($upper===false || $lower===false || $upper<1 || $upper>100 || $lower<0 || $lower>99 || $lower>=$upper)throw new RuntimeException('HP 상한은 1~100%, 하한은 0~99%의 정수이며 상한보다 작아야 합니다.');
    return array('hp_below'=>$upper,'hp_above'=>$lower);
}
function monster_shared_hp_matches($skill,$self) {
    $maximum=max(1,(int)$self['max_hp']);$hp=max(0,(int)$self['hp']);
    return $hp*100<=$maximum*(int)($skill['hp_below']??100) && $hp*100>$maximum*(int)($skill['hp_above']??0);
}
function monster_shared_validate_phases($phases,$catalog) {
    // Integer HP percentages provide at most 100 distinct phase thresholds.
    if(!is_array($phases) || !count($phases) || count($phases)>100)throw new RuntimeException('행동 단계는 1~100개로 설정하세요.');
    $normalized=array();$previous=101;
    foreach($phases as $index=>$phase) {
        if(!is_array($phase) || !isset($phase['below']) || !ctype_digit((string)$phase['below']))throw new RuntimeException('행동 단계의 HP 기준을 확인하세요.');
        $below=(int)$phase['below'];
        if($below<1 || $below>100 || ($index===0 && $below!==100) || $below>=$previous)throw new RuntimeException('첫 단계는 100%, 다음 단계는 이전보다 낮은 HP 기준이어야 합니다.');
        $previous=$below;
        if(!isset($phase['actions']) || !is_array($phase['actions']) || !count($phase['actions']) || count($phase['actions'])>16)throw new RuntimeException('각 단계에 1~16개의 행동을 설정하세요.');
        $actions=array();foreach($phase['actions'] as $id) {
            if(!ctype_digit((string)$id) || ((int)$id>0 && !isset($catalog[(int)$id])))throw new RuntimeException('단계 행동에서 등록된 몬스터 스킬 또는 일반 공격을 선택하세요.');
            $actions[]=(int)$id;
        }
        $normalized[]=array('below'=>$below,'actions'=>$actions);
    }
    return $normalized;
}
function monster_shared_compile($profile) {
    $d=$profile['shared'];unset($d['mp']);$catalog=monster_shared_skills();$skills=array();
    if(!empty($d['phases']))$d['phases']=monster_shared_validate_phases($d['phases'],$catalog);
    $entries=$d['skills']??array();
    if(!empty($d['phases'])) {
        $entries=array();$seen=array();
        foreach($d['phases'] as $phase)foreach($phase['actions'] as $id)if((int)$id>0 && !isset($seen[(int)$id])){$seen[(int)$id]=true;$entries[]=array('skill_id'=>(int)$id,'turn'=>1,'hp_below'=>100);}
    }
    foreach($entries as $entry) {
        $id=(int)$entry['skill_id'];
        if(!isset($catalog[$id]))throw new RuntimeException('몬스터에 등록한 스킬이 없습니다. 몬스터 관리를 확인하세요.');
        $conditions=array('turn'=>(int)$entry['turn'],'hp_below'=>(int)$entry['hp_below']);
        if(array_key_exists('hp_above',$entry))$conditions=array_replace($conditions,monster_shared_validate_hp_range($entry));
        $skills[]=array_replace($catalog[$id],$conditions);
    }
    $d['enmity_enabled']=true;$d['skills']=$skills;$d['name']=$profile['dg_mon_name'];$d['max_hp']=(int)$profile['dg_mon_hp'];$d['defense']=(int)$profile['dg_defence'];
    return $d;
}
/** Choose the deepest reached HP phase; healing never rolls an active phase back. */
function monster_shared_phase_plan($runtime,$self) {
    $phases=$runtime['definition']['phases']??array();
    if(!$phases)return null;
    $maximum=max(1,(int)$self['max_hp']);$hp=max(0,(int)$self['hp']);$index=0;
    foreach($phases as $i=>$phase)if($hp*100<=$maximum*(int)$phase['below'])$index=$i;
    $index=max($index,(int)($runtime['phase_index']??0));
    $index=min($index,count($phases)-1);
    $actions=$phases[$index]['actions'];
    $step=(int)($runtime['phase_index']??-1)===$index?(int)($runtime['phase_step']??0):0;
    $step=$step%count($actions);
    return array('index'=>$index,'step'=>$step,'skill_id'=>(int)$actions[$step]);
}
function monster_shared_from_raid($id) {
    if(!monster_shared_ready())return null;
    $row=sql_fetch('SELECT monster_id FROM `'.monster_table('monster_master').'` WHERE raid_id='.(int)$id.' AND profile IS NOT NULL');
    if(!$row)return null;
    $profile=monster_profile($row['monster_id'],false);return $profile['shared']??null;
}
function monster_shared_runtime($key,$unit,$definition) {
    $row=sql_fetch('SELECT state FROM `'.monster_table('monster_runtime').'` WHERE battle_key='.monster_quote($key).' AND unit_key='.monster_quote($unit));
    if($row) {
        $runtime=json_decode($row['state'],true);
        return monster_shared_normalize_runtime($runtime);
    }
    $definition['skills']=array_map('monster_shared_normalize_skill',$definition['skills']);
    $runtime=monster_shared_normalize_runtime(array('definition'=>$definition,'guard'=>0,'guard_until'=>0));
    if(!empty($definition['enmity_enabled']))monster_shared_prepare($runtime,1,array('hp'=>$definition['max_hp'],'max_hp'=>$definition['max_hp']));
    return $runtime;
}
/** Old battle snapshots cannot impose MP or cooldown limits on monster patterns. */
function monster_shared_normalize_runtime($runtime) {
    unset($runtime['mp'],$runtime['cooldowns'],$runtime['definition']['mp']);
    $runtime['definition']['skills']=array_map('monster_shared_normalize_skill',$runtime['definition']['skills']);
    return $runtime;
}
function monster_shared_save($key,$unit,$state) {
    sql_query('INSERT INTO `'.monster_table('monster_runtime').'` (battle_key,unit_key,state) VALUES ('.monster_quote($key).','.monster_quote($unit).','.monster_quote(json_encode($state,JSON_UNESCAPED_UNICODE)).') ON DUPLICATE KEY UPDATE state=VALUES(state)');
}
function monster_shared_targets($skill,$enemies,$allies,$self,$runtime=null) {
    $pool=$skill['target']==='self'?array($self):($skill['target']==='ally'?$allies:$enemies);
    $pool=array_values(array_filter($pool,function($p){return $p['hp']>0 && in_array($p['state']??'ACTIVE',array('ACTIVE','S'),true);}));
    if(($skill['mode']??'SINGLE')==='ALL' || $skill['target']==='self')return $pool;
    if($runtime && !empty($runtime['definition']['enmity_enabled']) && $skill['target']==='enemy' && ($skill['mode']??'SINGLE')==='SINGLE') {
        $rank=monster_shared_ranking($runtime,$pool);return $rank?array($rank[0]):array();
    }
    shuffle($pool);return array_slice($pool,0,($skill['mode']??'SINGLE')==='RANDOM'?max(1,(int)$skill['count']):1);
}
function monster_shared_amount($skill,$definition) {
    $stat=(float)($definition['stats'][(int)($skill['stat_id']??0)]??0);
    return max(0,(int)round(random_int((int)$skill['min'],(int)$skill['max'])+$stat*(float)($skill['factor']??0)));
}
/** One eligible skill: longer intervals first, registration order for ties. */
function monster_shared_schedule($runtime,$turn,$self) {
    $d=$runtime['definition'];$out=array();
    $cycle=1;foreach($d['skills'] as $skill)$cycle=max($cycle,(int)$skill['turn']);
    foreach($d['skills'] as $skill) {
        $at=(int)$skill['turn'];
        if($at<1 || (($d['pattern']??'INTERVAL')==='CYCLE'?((($turn-1)%$cycle)+1)!==$at:$turn%$at!==0))continue;
        if(!monster_shared_hp_matches($skill,$self))continue;
        if(!$out || $at>(int)$out[0]['turn'])$out=array($skill);
    }
    return $out;
}
function monster_shared_basic($d) {
    return array('id'=>0,'name'=>'일반 공격','type'=>'DAMAGE','target'=>'enemy','mode'=>'SINGLE','count'=>1,'min'=>$d['attack_min'],'max'=>$d['attack_max'],'stat_id'=>0,'factor'=>0,'cue'=>'공격을 준비하고 있다.');
}
/** Prepare one action in existing per-unit JSON. Targets follow current threat. */
function monster_shared_prepare(&$runtime,$turn,$self) {
    if(empty($runtime['definition']['enmity_enabled']))return;
    $phase=monster_shared_phase_plan($runtime,$self);
    if($phase!==null) {
        // Persist a reached phase when the next intent is prepared, so healing
        // before execution cannot restore the earlier action sequence.
        $runtime['phase_index']=$phase['index'];
        $runtime['phase_step']=$phase['step'];
        $slot=-1;
        if($phase['skill_id']>0) {
            $slot=null;
            foreach($runtime['definition']['skills'] as $i=>$skill)if((int)$skill['id']===$phase['skill_id']){$slot=$i;break;}
            if($slot===null)throw new RuntimeException('단계 행동에 등록된 몬스터 스킬이 없습니다.');
        }
        $runtime['intent']=array('turn'=>(int)$turn,'slots'=>array($slot),'phase'=>$phase['index'],'step'=>$phase['step']);
        return;
    }
    $slots=array();foreach(monster_shared_schedule($runtime,$turn,$self) as $sk)$slots[]=array_search($sk,$runtime['definition']['skills'],true);
    if(!$slots && !empty($runtime['definition']['default_attack']))$slots[]=-1;
    $runtime['intent']=array('turn'=>(int)$turn,'slots'=>$slots);
}
function monster_shared_planned(&$runtime,$turn,$self) {
    $phase=monster_shared_phase_plan($runtime,$self);
    $ranged=false;
    if($phase===null)foreach($runtime['definition']['skills'] as $skill)if(array_key_exists('hp_above',$skill)){$ranged=true;break;}
    // Explicit ranges recheck current HP; old multi-action intents use priority.
    $multiple=$phase===null && count($runtime['intent']['slots']??array())>1;
    if($ranged || $multiple || (int)($runtime['intent']['turn']??0)!==(int)$turn || ($phase!==null && ((int)($runtime['intent']['phase']??-1)!==$phase['index'] || (int)($runtime['intent']['step']??-1)!==$phase['step'])))monster_shared_prepare($runtime,$turn,$self);
    $skills=array();foreach($runtime['intent']['slots']??array() as $slot){if($slot===-1)$skills[]=monster_shared_basic($runtime['definition']);elseif(isset($runtime['definition']['skills'][$slot]))$skills[]=$runtime['definition']['skills'][$slot];}
    return $skills;
}
function monster_shared_ranking($runtime,$participants) {
    $pool=array_values(array_filter($participants,function($p){return $p['hp']>0 && in_array($p['state']??'ACTIVE',array('ACTIVE','S'),true);}));
    foreach($pool as &$p)$p['enmity']=(float)($runtime['enmity'][$p['id']]??0);unset($p);
    $current=$runtime['enmity_target']??null;
    usort($pool,function($a,$b)use($current){if($a['enmity']!==$b['enmity'])return $a['enmity']<$b['enmity']?1:-1;if($a['id']==$b['id'])return 0;if($a['id']==$current)return -1;if($b['id']==$current)return 1;return $a['id']<=>$b['id'];});
    return $pool;
}
function monster_shared_enmity(&$runtime,$actor,$amount,$provoke=false) {
    if(empty($runtime['definition']['enmity_enabled']) || !$actor)return;
    if(!isset($runtime['enmity']))$runtime['enmity']=array();
    if($provoke){$top=max(array_merge(array(0),array_values($runtime['enmity'])));$runtime['enmity'][$actor]=$top+max(10,ceil($top*.1));}
    elseif($amount>0)$runtime['enmity'][$actor]=($runtime['enmity'][$actor]??0)+$amount;
}
function monster_shared_prune(&$runtime,$participants) {
    $living=array();foreach($participants as $p)if($p['hp']>0 && in_array($p['state']??'ACTIVE',array('ACTIVE','S'),true))$living[(string)$p['id']]=true;
    foreach($runtime['enmity']??array() as $id=>$score)if(!isset($living[(string)$id]))unset($runtime['enmity'][$id]);
    if(isset($runtime['enmity_target']) && !isset($living[(string)$runtime['enmity_target']]))unset($runtime['enmity_target']);
}
/** Public projection only. No RNG, writes, or hidden future encounter data. */
function monster_shared_forecast($runtime,$turn,$self,$participants,$disabled=false) {
    if(empty($runtime['definition']['enmity_enabled']) || $self['hp']<=0)return array();
    $rank=monster_shared_ranking($runtime,$participants);$actions=array();
    $skills=$disabled?array():monster_shared_planned($runtime,$turn,$self);

    foreach($skills as $sk){
        $lower=(int)($sk['hp_above']??0);$upper=(int)($sk['hp_below']??100);
        $condition=$lower>0?'HP '.$lower.'% 초과 · '.$upper.'% 이하':($upper<100?'HP '.$upper.'% 이하':'');
        $actions[]=array('name'=>$sk['name'],'cue'=>trim($sk['cue']??'')?:'힘을 모으며 다음 행동을 준비하고 있다.','type'=>$sk['type'],'scope'=>$sk['target']==='self'?'자신':($sk['target']==='ally'?'몬스터 아군':(($sk['mode']??'SINGLE')==='ALL'?'파티 전체':(($sk['mode']??'SINGLE')==='RANDOM'?'무작위 대상':'위협 1위'))),'condition'=>$condition);
    }
    return array('turn'=>(int)$turn,'actions'=>$actions,'target'=>$rank?$rank[0]['name']:'','ranking'=>array_map(function($p){return array('id'=>$p['id'],'name'=>$p['name'],'value'=>$p['enmity']);},$rank),'idle'=>$disabled?'행동 불가':'대기','conditional'=>false);
}
/** Apply callback receives the chosen skill, living targets and the common scalar. */
function monster_shared_turn(&$runtime,$turn,$self,$enemies,$allies,$apply) {
    $runtime=monster_shared_normalize_runtime($runtime);
    $d=$runtime['definition'];$acted=false;
    monster_shared_prune($runtime,$enemies);
    if($runtime['guard_until']<$turn)$runtime['guard']=0;
    $actions=!empty($d['enmity_enabled'])?monster_shared_planned($runtime,$turn,$self):monster_shared_schedule($runtime,$turn,$self);
    $phase_intent=!empty($d['phases'])?($runtime['intent']??null):null;
    foreach($actions as $skill) {
        $targets=monster_shared_targets($skill,$enemies,$allies,$self,$runtime);if(!$targets)continue;
        if($skill['target']==='enemy' && ($skill['mode']??'SINGLE')==='SINGLE')$runtime['enmity_target']=$targets[0]['id'];
        $living_after=$apply($skill,$targets,monster_shared_amount($skill,$d));
        $acted=true;
        if($living_after===false)break;
    }
    if(!$acted && !empty($d['default_attack']) && empty($d['enmity_enabled'])) {
        $skill=monster_shared_basic($d);$targets=monster_shared_targets($skill,$enemies,$allies,$self,$runtime);
        if($targets){$runtime['enmity_target']=$targets[0]['id'];$apply($skill,$targets,monster_shared_amount($skill,$d));$acted=true;}
    }
    if($phase_intent) {
        $runtime['phase_index']=(int)$phase_intent['phase'];
        $runtime['phase_step']=((int)$phase_intent['step']+1)%count($d['phases'][$runtime['phase_index']]['actions']);
    }
    unset($runtime['intent']);
    return $acted;
}
function monster_shared_raid_credit($ra_id,$actor,$amount=0,$unit=null,$provoke=false) {
    global $battle_table;
    if(!monster_shared_ready())return;
    $key='raid:'.$battle_table.':'.$ra_id;
    $rows=monster_rows('SELECT r.unit_key,r.state FROM `'.monster_table('monster_runtime').'` r JOIN `'.$battle_table.'_unit` u ON u.rm_id=r.unit_key WHERE r.battle_key='.monster_quote($key).' AND u.ra_id='.monster_quote($ra_id)." AND u.unit_type='mo' AND u.hp_now>0".($unit===null?'':' AND u.rm_id='.(int)$unit));
    $participants=monster_rows('SELECT rm_id id,hp_now hp FROM `'.$battle_table.'_unit` WHERE ra_id='.monster_quote($ra_id)." AND unit_type='ch'");
    foreach($rows as $row){$r=json_decode($row['state'],true);monster_shared_prune($r,$participants);monster_shared_enmity($r,$actor,$unit===null?$amount/max(1,count($rows)):$amount,$provoke);monster_shared_save($key,$row['unit_key'],$r);}
}

function monster_shared_raid_announce($runtime,$turn,$hp,$ra_id,$option='',$system='') {
    global $battle_table,$g5;
    if(empty($runtime['definition']['enmity_enabled']) || $hp<=0)return;
    $forecast=monster_shared_forecast($runtime,$turn,array('hp'=>$hp,'max_hp'=>$runtime['definition']['max_hp']),monster_rows('SELECT u.rm_id id,u.hp_now hp,c.ch_name name FROM `'.$battle_table.'_unit` u JOIN `'.$g5['character_table']."` c ON c.ch_id=u.unit_id WHERE u.unit_type='ch' AND u.ra_id=".monster_quote($ra_id)));
    $parts=array();foreach($forecast['actions'] as $a)$parts[]=htmlspecialchars($a['cue'].' · '.$a['name'].' ('.$a['scope'].')'.($a['condition']?' · '.$a['condition']:''),ENT_QUOTES,'UTF-8');
    insert_k_log('<p>'.htmlspecialchars($runtime['definition']['name'],ENT_QUOTES,'UTF-8').' · 다음 행동 '.$turn.'턴</p><p>'.($parts?implode('<br>',$parts):'대기').'</p>'.($forecast['target']?'<p>현재 위협 1위: '.htmlspecialchars($forecast['target'],ENT_QUOTES,'UTF-8').'</p>':'').'<p>방어·회피 스킬로 대비하세요. 도발로 단일 공격 대상을 바꿀 수 있습니다.</p>',$ra_id,'mo',$option,$system);
}

function monster_shared_forget($key,$actor) {
    if(!monster_shared_ready())return;
    foreach(monster_rows('SELECT unit_key,state FROM `'.monster_table('monster_runtime').'` WHERE battle_key='.monster_quote($key)) as $row){$r=json_decode($row['state'],true);unset($r['enmity'][$actor]);if(($r['enmity_target']??null)==$actor)unset($r['enmity_target']);monster_shared_save($key,$row['unit_key'],$r);}
}
