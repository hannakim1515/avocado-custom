<?php
if (!defined('_GNUBOARD_')) exit;
require_once G5_PATH.'/lib/maze_client.lib.php';

function maze_stat($person, $stat_id) {
    $stats = maze_data($person['stats']);
    $base = isset($stats[$stat_id]) ? (int)$stats[$stat_id] : 0;
    $flat = 0; $percent = 0; $final = array();
    foreach (maze_data($person['effects']) as $effect) foreach ($effect['components'] as $component) {
        if ($component['type'] !== 'STAT_MODIFIER' || (int)$component['stat_id'] !== (int)$stat_id) continue;
        $mode = isset($component['mode']) ? $component['mode'] : 'flat';
        if ($mode === 'final') $final[] = (float)$component['value'];
        elseif ($mode === 'percent') $percent += (float)$component['value'];
        else $flat += (float)$component['value'];
    }
    return unified_active_effect_value($base, $flat, $percent, $final);
}
function maze_effect_value($person, $type, $key = null) {
    $value = 0;
    foreach (maze_data($person['effects']) as $effect) foreach ($effect['components'] as $component) {
        if ($component['type'] === $type && ($key === null || (isset($component['key']) && $component['key'] === $key))) $value += (float)$component['value'];
    }
    return $value;
}
function maze_monster_bonus($monster, $key) {
    $sum=0;
    foreach (isset($monster['_effects']) ? $monster['_effects'] : array() as $effect) if ($effect['type']==='STAT' && $effect['key']===$key) $sum+=(float)$effect['value'];
    return $sum;
}
function maze_evasion(&$person) {
    $effects=maze_data($person['effects']);
    foreach($effects as &$effect) foreach($effect['components'] as &$component) {
        if($component['type']==='EVASION' && $component['value']>0) {
            $component['value']--;unset($component);unset($effect);
            $person['effects']=maze_json($effects);return true;
        }
    }
    unset($component);unset($effect);return false;
}
function maze_code($session, $person, $name) {
    $settings = maze_data($session['snapshot']);
    foreach ($settings['codes'] as $code) if ($code['ex_name'] === $name) {
        $result = dungeon_formula_result($code, function ($type) use ($settings, $person) {
            $field = get_status_type_filed($type); $sum = 0;
            foreach ($settings['stat_definitions'] as $definition) if (!empty($definition[$field])) $sum += maze_stat($person, (int)$definition['st_id']);
            return $sum;
        }, 0, 0, maze_effect_value($person, 'CODE', $name));
        return max(0, (int)$result['value']);
    }
    throw new RuntimeException('연동 코드 설정이 없습니다: '.$name);
}
function maze_person_save($person) {
    maze_update('member', array('hp' => max(0, (int)$person['hp']), 'stats' => $person['stats'], 'skills' => $person['skills'], 'effects' => $person['effects']), 'dm_id='.(int)$person['dm_id']);
}
function maze_status_apply($session, &$person, $status_id) {
    $settings = maze_data($session['snapshot']);
    foreach ($settings['status_registry'] as $status) if ((int)$status['status_id'] === (int)$status_id) {
        $effects = maze_data($person['effects']);
        $effects['status:'.$status_id] = array('remaining' => (int)$status['duration'], 'components' => maze_data($status['components']));
        $person['effects'] = maze_json($effects);
        return;
    }
    throw new RuntimeException('등록되지 않은 상태이상입니다.');
}
function maze_heal(&$person, $amount) {
    $amount = max(0, (int)round($amount * max(0, 1 + maze_effect_value($person, 'HEAL_MODIFIER') / 100)));
    $person['hp'] = min((int)$person['max_hp'], (int)$person['hp'] + $amount);
}
function maze_item_apply($session, &$actor, &$target, $inventory_id) {
    $stored = maze_inventory_owned($session, $actor, $inventory_id);
    $item = inventory_boundary_decode($stored['item_snapshot']);
    if (empty($item['it_use_battle_able'])) throw new RuntimeException('전투에서 사용할 수 없는 아이템입니다.');
    $effect = $item['maze_effect'];
    if ($effect && $effect['kind'] === 'REVIVE') {
        if ((int)$target['hp'] > 0 || $effect['value'] <= 0 || $effect['value'] > 100) throw new RuntimeException('부활 대상 또는 회복률이 올바르지 않습니다.');
        $target['hp'] = max(1, (int)ceil($target['max_hp'] * $effect['value'] / 100));
    } elseif ($effect && $effect['kind'] === 'CLEANSE') {
        if ((int)$target['hp'] <= 0) throw new RuntimeException('살아 있는 대상만 치료할 수 있습니다.');
        $effects = maze_data($target['effects']);
        unset($effects['status:'.(int)$effect['status_id']]); $target['effects'] = maze_json($effects);
    } elseif ($item['it_type'] === '스탯회복') {
        if ((int)$target['hp'] <= 0) throw new RuntimeException('일반 회복으로 부활할 수 없습니다.');
        $settings = maze_data($session['snapshot']);
        if ((int)$item['st_id'] === (int)$settings['hp_id']) maze_heal($target, (int)$item['it_value']);
        else {
            $stats = maze_data($target['stats']); $key = (int)$item['st_id'];
            if (!isset($stats[$key])) throw new RuntimeException('회복할 스탯이 없습니다.');
            $limit = isset($stats['_maximum'][$key]) ? (int)$stats['_maximum'][$key] : (int)$stats[$key];
            $stats[$key] = min($limit, $stats[$key] + (int)$item['it_value']); $target['stats'] = maze_json($stats);
        }
    } else throw new RuntimeException('미궁에서 지원하는 회복/부활/치료 아이템이 아닙니다.');
    if (empty($item['it_use_ever'])) maze_update('inventory', array('consumed' => 1), 'inventory_id='.(int)$stored['inventory_id']);
    maze_log($session['ds_id'], $actor['dm_id'], 'ITEM_USE', $item['it_name'].' → '.$target['name']);
}
function maze_monster_count_range($entry) {
    $min = filter_var($entry['min_count']??1, FILTER_VALIDATE_INT);
    $max = filter_var($entry['max_count']??1, FILTER_VALIDATE_INT);
    if($min===false || $max===false || $min<1 || $max>20 || $min>$max) throw new RuntimeException('몬스터 동시 출현 수는 1~20마리이며 최대값은 최소값 이상이어야 합니다.');
    return array($min,$max);
}
function maze_battle_begin($session, $room, $monster, $count = 1) {
    if (empty($monster['dg_mon_hp']) || $monster['dg_mon_hp'] < 1) throw new RuntimeException('몬스터 HP가 올바르지 않습니다.');
    if($count<1 || $count>20) throw new RuntimeException('몬스터 동시 출현 수가 올바르지 않습니다.');
    $id = 0; $deadline = maze_deadline();
    for($number=1;$number<=$count;$number++) {
        $copy=$monster;
        if($count>1)$copy['dg_mon_name'].=' '.$number;
        $unit=maze_insert('battle', array('ds_id' => $session['ds_id'], 'room_id' => $room['room_id'], 'encounter_id' => $id, 'monster' => maze_json($copy), 'hp' => (int)$monster['dg_mon_hp'], 'deadline_at' => $deadline));
        if(!$id)$id=$unit;
    }
    maze_update('session', array('phase' => 'BATTLE', 'battle_id' => $id), 'ds_id='.(int)$session['ds_id']);
    maze_log($session['ds_id'], 0, 'BATTLE', $monster['dg_mon_name'].($count>1?' '.$count.'마리':'').' 조우');
}
function maze_battle_living($battles) { return array_filter($battles,function($unit){return $unit['hp']>0;}); }
function maze_battle_unit_save($unit,$state=null) {
    $values=array('hp'=>$unit['hp'],'monster'=>$unit['monster'],'weak_count'=>$unit['weak_count'],'weak_turn'=>$unit['weak_turn'],'turn_no'=>$unit['turn_no'],'deadline_at'=>$unit['deadline_at']);
    if($state!==null)$values['state']=$state;
    maze_update('battle',$values,'battle_id='.(int)$unit['battle_id']);
}
function maze_battle_current($session) {
    $battle = maze_one('SELECT * FROM `'.maze_table('battle').'` WHERE battle_id='.(int)$session['battle_id'].' AND ds_id='.(int)$session['ds_id']);
    if ($session['phase'] !== 'BATTLE' || !$battle || $battle['state'] !== 'ACTIVE') throw new RuntimeException('진행 중인 전투가 아닙니다.');
    return $battle;
}
function maze_actions($battle) {
    $rows = inventory_boundary_rows('SELECT * FROM `'.maze_table('action').'` WHERE battle_id='.(int)$battle['battle_id'].' AND turn_no='.(int)$battle['turn_no'].' ORDER BY dm_id');
    $actions = array(); foreach ($rows as $row) $actions[(int)$row['dm_id']] = $row;
    return $actions;
}
function maze_all_submitted($party, $actions) {
    $living = 0;
    foreach ($party as $person) if ($person['hp'] > 0) { $living++; if (empty($actions[(int)$person['dm_id']]['action'])) return false; }
    return $living > 0;
}
function maze_skill($person, $id) {
    foreach (maze_data($person['skills']) as $skill) if ((int)$skill['sh_id'] === (int)$id && $skill['sk_type'] !== '패시브') return $skill;
    throw new RuntimeException('출발 시 장착한 스킬이 아닙니다.');
}
function maze_check_escape_vote($session, $input) {
    if (!empty($input['escape_vote']) && maze_room($session)['is_boss']) throw new RuntimeException('보스 전투에서는 도주할 수 없습니다.');
}
function maze_combat_event($session,$turn,$actor,$target,$action,$amount,$result) {
    combat_event('maze',$session['ds_id'],$turn,$actor,$target,$action,$amount,$result);
    maze_log($session['ds_id'],0,'COMBAT',$actor.' → '.$target.' / '.$action.' / '.($result==='HEAL'?'+':'').$amount.' '.$result.' · 턴 '.$turn);
}
function maze_submit($session, $actor, $input) {
    $battle = maze_battle_current($session);
    maze_check_escape_vote($session, $input);
    if ((int)$input['battle_id'] !== (int)$battle['battle_id'] || (int)$input['turn_no'] !== (int)$battle['turn_no']) throw new RuntimeException('이미 종료된 턴입니다.');
    if ($battle['deadline_at'] && strtotime($battle['deadline_at']) <= time()) throw new RuntimeException('행동 제출 시간이 지났습니다.');
    $action = $input['action'];
    if (!in_array($action, array('ATTACK','GUARD','HEAL','SKILL','ITEM','SKIP'), true)) throw new RuntimeException('지원하지 않는 행동입니다.');
    $target_id = isset($input['target_id']) ? (int)$input['target_id'] : (int)$actor['dm_id'];
    $reference = isset($input['reference_id']) ? (int)$input['reference_id'] : 0;
    $party = maze_party($session['ds_id']); $targets = array_column($party, null, 'dm_id');
    $enemies=maze_battle_group($session,$battle);$living=maze_battle_living($enemies);
    $enemy_id=isset($input['monster_id'])?(int)$input['monster_id']:(int)key($living);
    if ($action==='HEAL' && (!isset($targets[$target_id]) || $targets[$target_id]['hp']<=0)) throw new RuntimeException('살아 있는 회복 대상을 선택하세요.');
    if (in_array($action, array('ITEM','HEAL','SKILL'), true) && !isset($targets[$target_id])) throw new RuntimeException('현재 파티의 대상만 선택할 수 있습니다.');
    if ($action === 'SKILL') {
        $skill = maze_skill($actor, $reference);
        $availability=maze_skill_available($session,$actor,$skill);
        if ($availability['reason']) throw new RuntimeException($availability['reason']);
        if ($skill['sk_function']!=='공격' && $skill['sk_target']!=='적' && !maze_skill_targets($skill,(int)$actor['dm_id'],$target_id,$targets)) throw new RuntimeException('유효한 스킬 대상이 없습니다.');
    }
    $enemy_action=$action==='ATTACK' || ($action==='SKILL' && ($skill['sk_function']==='공격' || $skill['sk_target']==='적'));
    if($enemy_action && (!isset($enemies[$enemy_id]) || $enemies[$enemy_id]['hp']<=0))throw new RuntimeException('살아 있는 몬스터 대상을 선택하세요.');
    if(!$enemy_action)$enemy_id=0;
    if ($action === 'ITEM') maze_inventory_owned($session, $actor, $reference);
    $escape = !empty($input['escape_vote']) ? 1 : 0;
    inventory_boundary_query('INSERT INTO `'.maze_table('action').'` (battle_id,turn_no,dm_id,action,target_id,monster_id,reference_id,escape_vote) VALUES ('.(int)$battle['battle_id'].','.(int)$battle['turn_no'].','.(int)$actor['dm_id'].','.inventory_boundary_quote($action).','.$target_id.','.$enemy_id.','.$reference.','.$escape.') ON DUPLICATE KEY UPDATE action=VALUES(action),target_id=VALUES(target_id),monster_id=VALUES(monster_id),reference_id=VALUES(reference_id),escape_vote=VALUES(escape_vote)');
    // Submission only records the choice; resolving requires an explicit POST.
}
function maze_skill_targets($skill, $actor_id, $target_id, $party) {
    $targets = array();
    foreach ($party as $id => $person) {
        if ($person['hp'] <= 0) continue;
        $type = $skill['sk_target'];
        if (($type === '자신' && $id === $actor_id) || ($type === '아군' && $id === $target_id) || ($type === '타인' && $id === $target_id && $id !== $actor_id)
            || $type === '아군전체' || ($type === '타인전체' && $id !== $actor_id)) $targets[] = $id;
    }
    return $targets;
}
function maze_attack_monster(&$battle, $monster, $code, $amount) {
    if(!empty($monster['shared'])) {
        require_once G5_PATH.'/lib/monster_shared.lib.php';
        $runtime=monster_shared_runtime('maze:'.$battle['battle_id'],0,$monster['shared']);
        if($runtime['guard_until']>=(int)$battle['turn_no'])$amount*=1-$runtime['guard']/100;
    }
    if (!empty($monster['dg_strong_code']) && $monster['dg_strong_code'] === $code) $amount *= (float)$monster['dg_strong_value'];
    if (!empty($monster['dg_weak_code']) && $monster['dg_weak_code'] === $code) {
        $amount *= (float)$monster['dg_weak_value'];
        if (!$battle['weak_turn'] && !empty($monster['dg_weak_effect_count'])) {
            $battle['weak_count']++;
            if ($battle['weak_count'] >= $monster['dg_weak_effect_count']) { $battle['weak_count'] = 0; $battle['weak_turn'] = (int)$monster['dg_weak_effect_turn']; }
        }
    }
    $battle['hp'] = max(0, (int)$battle['hp'] - max(0, (int)$amount));
    return max(0,(int)$amount);
}
function maze_skill_enemy_apply(&$battle,$skill,$value,$person) {
    $monster=maze_data($battle['monster']);
    if($skill['sk_function']==='공격') {
        $mod=0;
        if($skill['sk_def_enermy']==='체력')$mod=$battle['hp']+maze_monster_bonus($monster,'체력');
        elseif($skill['sk_def_enermy']==='공격')$mod=$monster['dg_d_attack_max']+maze_monster_bonus($monster,'공격');
        elseif($skill['sk_def_enermy']==='방어')$mod=$monster['dg_defence']+maze_monster_bonus($monster,'방어');
        if($skill['sk_def_type']==='+')$value+=$mod;elseif($skill['sk_def_type']==='-')$value-=$mod;
        $damage=maze_attack_monster($battle,$monster,$skill['sk_status_code'],$value+maze_effect_value($person,'CODE','공통대미지'));
        if($skill['sk_keep_limit']>0)$monster['_effects']['skill:'.$skill['sh_id']]=array('type'=>'DOT','value'=>$damage,'remaining'=>(int)$skill['sk_keep_limit']);
    } else {
        if(!in_array($skill['sk_mod_enermy'],array('체력','공격','방어'),true))throw new RuntimeException('적 스탯 대상이 잘못되었습니다.');
        $monster['_effects']['skill:'.$skill['sh_id']]=array('type'=>'STAT','key'=>$skill['sk_mod_enermy'],'value'=>$skill['sk_mod_type']==='-'?-$value:$value,'remaining'=>max(1,(int)$skill['sk_keep_limit']));
    }
    $battle['monster']=maze_json($monster);
}
function maze_skill_execute($session, &$battle, &$party, $id, $action, &$enemies = null) {
    $person =& $party[$id]; $skill = maze_skill($person, $action['reference_id']);
    if ($skill['sh_limit'] > 0) throw new RuntimeException('스킬 재사용 대기 중');
    $settings = maze_data($session['snapshot']); $stats = maze_data($person['stats']);
    $cost_id = (int)$skill['sk_use_st_id']; $cost = max(0, (int)$skill['sl_use_value']);
    if ($cost_id) {
        $available = $cost_id === (int)$settings['hp_id'] ? (int)$person['hp'] : (isset($stats[$cost_id]) ? $stats[$cost_id] : 0);
        if ($available < $cost || ($cost_id === (int)$settings['hp_id'] && $available === $cost)) throw new RuntimeException('스킬 자원 부족');
    }
    $value = $skill['sk_status_code'] ? maze_code($session, $person, $skill['sk_status_code']) : 0;
    if ($skill['sl_set_value']) $value = $skill['sk_value_type'] === 'x' ? $value * (float)$skill['sl_set_value'] : $value + (float)$skill['sl_set_value'];
    $function = $skill['sk_function'];
    if ($function === '공격' || ($skill['sk_target']==='적' && in_array($function,array('스탯강화','연동코드강화'),true))) {
        if($enemies===null)maze_skill_enemy_apply($battle,$skill,$value,$person);
        else {
            $living=maze_battle_living($enemies);$mode=$skill['sk_dungeon_target_mode']??'SINGLE';
            if($mode==='ALL')$targets=array_keys($living);
            elseif($mode==='RANDOM')$targets=$living?array(array_rand($living)):array();
            else $targets=array((int)(($action['monster_id']??0)?:$battle['battle_id']));
            if(!$targets)throw new RuntimeException('살아 있는 몬스터가 없습니다.');
            foreach($targets as $enemy) {
                if(!isset($enemies[$enemy]) || $enemies[$enemy]['hp']<=0)throw new RuntimeException('선택한 몬스터가 이미 전투불능입니다.');
                maze_skill_enemy_apply($enemies[$enemy],$skill,$value,$person);
            }
        }
    } else {
        $targets = maze_skill_targets($skill, $id, (int)$action['target_id'], $party);
        if (!$targets) throw new RuntimeException('유효한 스킬 대상 없음');
        foreach ($targets as $target_id) {
            $target =& $party[$target_id]; $amount = $value;
            if ($skill['sk_def_code']) $amount += ($skill['sk_def_type'] === '-' ? -1 : 1) * maze_code($session, $target, $skill['sk_def_code']);
            if ($skill['sk_mod_type'] === '-') $amount *= -1;
            $component = array('value' => $amount);
            if ($function === '스탯회복') {
                $stat_id = (int)$skill['sk_mod_st_id'];
                if ($stat_id === (int)$settings['hp_id']) maze_heal($target, $amount);
                else { $target_stats = maze_data($target['stats']); $limit = isset($target_stats['_maximum'][$stat_id]) ? (int)$target_stats['_maximum'][$stat_id] : (int)($target_stats[$stat_id] ?? 0); $target_stats[$stat_id] = min($limit, max(0, (isset($target_stats[$stat_id]) ? $target_stats[$stat_id] : 0) + (int)$amount)); $target['stats'] = maze_json($target_stats); }
                continue;
            } elseif ($function === '스탯강화') {
                $component += array('type' => 'STAT_MODIFIER', 'stat_id' => (int)$skill['sk_mod_st_id'], 'mode' => isset($skill['sk_effect_type']) ? $skill['sk_effect_type'] : 'flat');
                if ($component['mode'] === 'final') $component['value'] = (float)$skill['sl_set_value'];
            } elseif ($function === '연동코드강화') $component += array('type' => 'CODE', 'key' => $skill['sk_mod_code']);
            elseif ($function === '방어') { $component['type'] = 'GUARD'; $component['value'] = min(90, max(0, $amount)); }
            elseif ($function === '회피') $component['type'] = 'EVASION';
            elseif ($function === '도발') $component['type'] = 'AGGRO';
            else throw new RuntimeException('스킬 기능 설정 오류');
            $effects = maze_data($target['effects']);
            $effects['skill:'.$skill['sh_id']] = array('remaining' => max(1, (int)$skill['sk_keep_limit']), 'components' => array($component));
            $target['effects'] = maze_json($effects);
            unset($target);
        }
    }
    if ($cost_id === (int)$settings['hp_id']) $person['hp'] -= $cost;
    elseif ($cost_id) { $stats = maze_data($person['stats']); $stats[$cost_id] -= $cost; $person['stats'] = maze_json($stats); }
    $skills = maze_data($person['skills']);
    foreach ($skills as &$saved) if ((int)$saved['sh_id'] === (int)$skill['sh_id']) $saved['sh_limit'] = (int)$skill['sk_limit'] + 1;
    unset($saved); $person['skills'] = maze_json($skills);
}

function maze_resolve($session, $battle, $skip_time) {
    $enemies=maze_battle_group($session,$battle);$leader_id=(int)$battle['battle_id'];$battle=&$enemies[$leader_id];
    $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
    $actions = maze_actions($battle);
    $submitted = maze_all_submitted($party, $actions);
    if ($battle['deadline_at']) {
        if (strtotime($battle['deadline_at']) > time() && (!$skip_time || !$submitted)) throw new RuntimeException('아직 턴을 종료할 수 없습니다.');
    } elseif (!$submitted) throw new RuntimeException('모든 생존자의 행동을 기다리고 있습니다.');
    $room = maze_room($session);
    $alive_at_start = array(); $escape = !$room['is_boss'];
    foreach ($party as $id => $person) if ($person['hp'] > 0) { $alive_at_start[] = $id; if (empty($actions[$id]['escape_vote'])) $escape = false; }
    if (!$alive_at_start) { maze_finish($session, false); return; }
    $settings = maze_data($session['snapshot']); $monster = maze_data($battle['monster']);
    if ($escape && random_int(1,10000) <= (int)round($settings['escape_percent'] * 100)) {
        maze_battle_end($session, $battle, 'ESCAPED'); return;
    }
    if (!$escape) foreach ($alive_at_start as $id) {
        if ($party[$id]['hp'] <= 0 || maze_effect_value($party[$id], 'ACTION_DISABLE') > 0 || empty($actions[$id]['action'])) continue;
        $action = $actions[$id];
        // Validation failures caused by an earlier action skip this action only.
        // SQL errors must propagate and roll back the entire turn.
        $before_party = $party;
        $before_enemies = array_map(function($unit){return array_merge(array(),$unit);},$enemies);
        try {
            $item_label='아이템';
            if($action['action']==='ITEM') { $stored=maze_inventory_owned($session,$party[$id],$action['reference_id']);$item_label=inventory_boundary_decode($stored['item_snapshot'])['it_name']; }
            if ($action['action'] === 'ATTACK') {
                $enemy=(int)($action['monster_id']?:$leader_id);
                if(!isset($enemies[$enemy]) || $enemies[$enemy]['hp']<=0)throw new RuntimeException('선택한 몬스터가 이미 전투불능입니다.');
                maze_attack_monster($enemies[$enemy], maze_data($enemies[$enemy]['monster']), $settings['attack_code'], maze_code($session, $party[$id], $settings['attack_code']) + maze_effect_value($party[$id], 'CODE', '공통대미지'));
            }
            elseif ($action['action'] === 'GUARD') {
                $effects = maze_data($party[$id]['effects']); $effects['guard'] = array('remaining' => 1, 'components' => array(array('type' => 'GUARD', 'value' => min(90, maze_code($session, $party[$id], $settings['guard_code']))))); $party[$id]['effects'] = maze_json($effects);
            } elseif ($action['action'] === 'HEAL') {
                $target = (int)$action['target_id'];
                if (!isset($party[$target]) || $party[$target]['hp'] <= 0) throw new RuntimeException('회복 대상 없음');
                maze_heal($party[$target], maze_code($session, $party[$id], $settings['heal_code']));
            } elseif ($action['action'] === 'SKILL') maze_skill_execute($session, $battle, $party, $id, $action, $enemies);
            elseif ($action['action'] === 'ITEM') {
                $target = (int)$action['target_id'];
                if (!isset($party[$target])) throw new RuntimeException('아이템 대상 없음');
                maze_item_apply($session, $party[$id], $party[$target], (int)$action['reference_id']);
            }
            $paid=$action['action']==='SKILL'?maze_skill($before_party[$id],$action['reference_id']):null;
            $hp_cost=$paid && (int)$paid['sk_use_st_id']===(int)$settings['hp_id']?max(0,(int)$paid['sl_use_value']):0;
            $healed=0;foreach($party as $pid=>$after)$healed+=max(0,(int)$after['hp']-(int)$before_party[$pid]['hp']+($pid==$id?$hp_cost:0));
            foreach($enemies as $enemy_id=>$unit) {
                $monster=maze_data($unit['monster']);
                if(empty($monster['shared']['enmity_enabled']))continue;
                require_once G5_PATH.'/lib/monster_shared.lib.php';
                $r=monster_shared_runtime('maze:'.$enemy_id,0,$monster['shared']);
                $participants=array();foreach($before_party as $pid=>$p)$participants[]=array('id'=>$pid,'hp'=>$p['hp']);monster_shared_prune($r,$participants);
                monster_shared_enmity($r,$id,max(0,(int)$before_enemies[$enemy_id]['hp']-(int)$unit['hp'])+$healed*.5);
                if($paid && $paid['sk_function']==='도발' && $unit['hp']>0) {
                    monster_shared_enmity($r,$id,0,true);
                    maze_combat_event($session,$battle['turn_no'],$party[$id]['name'],$monster['dg_mon_name'],'도발 · 위협 1위',0,'PROVOKE');
                }
                monster_shared_save('maze:'.$enemy_id,0,$r);
            }
            $label=$action['action'];
            if($label==='SKILL')$label=maze_skill($before_party[$id],$action['reference_id'])['sk_name'];
            elseif($label==='ITEM') $label=$item_label;
            foreach($enemies as $enemy_id=>$unit)if((int)$before_enemies[$enemy_id]['hp']!==(int)$unit['hp'])maze_combat_event($session,$battle['turn_no'],$party[$id]['name'],maze_data($unit['monster'])['dg_mon_name'],$label,(int)$before_enemies[$enemy_id]['hp']-(int)$unit['hp'],'DAMAGE');
            foreach($party as $target_id=>$after) {
                $diff=(int)$after['hp']-(int)$before_party[$target_id]['hp']+($target_id==$id?$hp_cost:0);
                if($diff)maze_combat_event($session,$battle['turn_no'],$party[$id]['name'],$after['name'],$label,abs($diff),$diff>0?'HEAL':'DAMAGE');
                $before_stats=maze_data($before_party[$target_id]['stats']);$after_stats=maze_data($after['stats']);
                if($target_id==$id && $paid && !empty($paid['sk_use_st_id']) && (int)$paid['sk_use_st_id']!==(int)$settings['hp_id'])$before_stats[(int)$paid['sk_use_st_id']]-=max(0,(int)$paid['sl_use_value']);
                unset($before_stats[(int)$settings['hp_id']],$after_stats[(int)$settings['hp_id']]);
                if(maze_data($after['effects'])!=maze_data($before_party[$target_id]['effects']) || $after_stats!=$before_stats)maze_combat_event($session,$battle['turn_no'],$party[$id]['name'],$after['name'],$label,0,'STATUS');
            }
        } catch (RuntimeException $error) {
            // Storage errors cannot be treated as a harmless invalid action.
            if ($error instanceof InventoryStorageException) throw $error;
            $party = $before_party; unset($battle);$enemies=$before_enemies;$battle=&$enemies[$leader_id];
            maze_log($session['ds_id'], $id, 'SKIP', $error->getMessage());
        }
        if (!maze_battle_living($enemies)) break;
    }
    foreach($enemies as &$unit) {
        if($unit['hp']>0 && array_filter($party,function($p){return $p['hp']>0;}))maze_monster_phase($session,$unit,$party,$enemies);
    }
    unset($unit);
    foreach($enemies as &$unit) if($unit['hp']>0) {
        $monster=maze_data($unit['monster']);
        if (!empty($monster['_effects'])) foreach ($monster['_effects'] as $key=>&$effect) {
            if($effect['type']==='DOT') { $before=$unit['hp'];$unit['hp']=max(0,$unit['hp']-max(0,(int)$effect['value']));maze_combat_event($session,$battle['turn_no'],'지속 효과',$monster['dg_mon_name'],'지속 피해',$before-$unit['hp'],'DAMAGE'); }
            $effect['remaining']--;if($effect['remaining']<=0)unset($monster['_effects'][$key]);
            if($unit['hp']<=0)break;
        }
        unset($effect);$unit['monster']=maze_json($monster);
    }
    unset($unit);
    $boss_killed = !maze_battle_living($enemies) && $room['is_boss'];
    foreach ($party as &$person) {
        if ($boss_killed) { maze_person_save($person); continue; }
        $effects = maze_data($person['effects']);
        foreach ($effects as $key => &$effect) {
            if ($person['hp'] > 0) foreach ($effect['components'] as $component) if ($component['type'] === 'DOT_HP') { $before=$person['hp'];$person['hp'] = max(0, $person['hp'] - max(0, (int)$component['value']));maze_combat_event($session,$battle['turn_no'],'지속 효과',$person['name'],'지속 피해',$before-$person['hp'],'DAMAGE'); }
            $effect['remaining']--; if ($effect['remaining'] <= 0) unset($effects[$key]);
        }
        unset($effect); $person['effects'] = maze_json($effects);
        $skills = maze_data($person['skills']); foreach ($skills as &$skill) $skill['sh_limit'] = max(0, (int)$skill['sh_limit'] - 1); unset($skill);
        $person['skills'] = maze_json($skills); maze_person_save($person);
    }
    unset($person);
    maze_log($session['ds_id'], 0, 'TURN', '턴 '.$battle['turn_no'].' 처리 완료'.($escape ? ' · 도주 실패' : ''));
    $living = array_filter($party, function ($person) { return $person['hp'] > 0; });
    if (!$living) { foreach($enemies as $unit)maze_battle_unit_save($unit,'LOST');maze_finish($session, false); return; }
    if (!maze_battle_living($enemies)) { maze_battle_end($session, $battle, 'WON', $enemies); return; }
    $deadline=maze_deadline();
    foreach($enemies as $unit) {
        $m=maze_data($unit['monster']);if($unit['hp']>0 && !empty($m['shared']['enmity_enabled'])){$r=monster_shared_runtime('maze:'.$unit['battle_id'],0,$m['shared']);monster_shared_prepare($r,(int)$battle['turn_no']+1,array('hp'=>$unit['hp'],'max_hp'=>$m['dg_mon_hp']));monster_shared_save('maze:'.$unit['battle_id'],0,$r);}
        $unit['turn_no']=(int)$battle['turn_no']+1;$unit['deadline_at']=$deadline;maze_battle_unit_save($unit);
    }
    maze_update('session', array('version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
}
function maze_battle_end($session, $battle, $state, $enemies = null) {
    $room = maze_one('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$session['room_id']);
    if($enemies===null){$enemies=maze_battle_group($session,$battle);$enemies[(int)$battle['battle_id']]=$battle;}
    foreach($enemies as $unit)maze_battle_unit_save($unit,$state);
    maze_update('room', array('encounter_done' => 1), 'ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$session['room_id']);
    maze_update('session', array('phase' => $room['is_boss'] && $state === 'WON' ? 'BOSS_RESULT' : 'EXPLORE', 'version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
    maze_log($session['ds_id'], 0, 'BATTLE', $state);
    if($state==='WON')foreach($enemies as $unit)maze_combat_event($session,$battle['turn_no'],maze_data($unit['monster'])['dg_mon_name'],maze_data($unit['monster'])['dg_mon_name'],'전투불능',0,'DEAD');
}
function maze_monster_phase($session, &$battle, &$party, &$enemies = null) {
    require_once G5_PATH.'/lib/monster_adapters.lib.php';
    if(monster_shared_maze($session,$battle,$party,$enemies))return;
    $monster = maze_data($battle['monster']); $settings = maze_data($session['snapshot']);
    foreach (array('d','w','s1','s2') as $pattern) {
        $prefix = 'dg_'.$pattern.'_attack_'; $interval = (int)$monster[$prefix.'turn'];
        if ($interval < 1 || $battle['turn_no'] % $interval !== 0) continue;
        if ($pattern === 'd' && empty($monster[$prefix.'count'])) continue;
        if ($battle['weak_turn'] > 0) { $battle['weak_turn']--; continue; }
        $living = array_keys(array_filter($party, function ($person) { return $person['hp'] > 0; }));
        if (!$living) return;
        if ($pattern === 'd') {
            shuffle($living); $living = array_slice($living, 0, (int)$monster[$prefix.'count']);
        }
        $aggro=array();$aggro_damage=0;
        if($pattern==='d') {
            foreach($party as $id=>$person) if($person['hp']>0 && maze_effect_value($person,'AGGRO')>0)$aggro[]=$id;
            if($aggro) {
                foreach($living as $unused) $aggro_damage+=random_int((int)$monster[$prefix.'min'],max((int)$monster[$prefix.'min'],(int)$monster[$prefix.'max']))+maze_monster_bonus($monster,'공격');
                $aggro_damage=(int)floor($aggro_damage/count($aggro));$living=$aggro;
            }
        }
        foreach ($living as $id) {
            if ($pattern === 's1' || $pattern === 's2') {
                $value = maze_stat($party[$id], (int)$monster[$prefix.'st_id']);
                $threshold = (int)$monster[$prefix.'st_value'];
                if (($monster[$prefix.'type'] === '이상' && $value < $threshold) || ($monster[$prefix.'type'] === '이하' && $value > $threshold)) continue;
            }
            if (maze_evasion($party[$id])) continue;
            foreach ($settings['codes'] as $code) if ($code['ex_name']==='자동회피' && random_int(0,1000)<=maze_code($session,$party[$id],'자동회피')) continue 2;
            $damage = $aggro ? (int)floor($aggro_damage*(1-min(100,max(0,maze_effect_value($party[$id],'AGGRO')))/100)) : random_int((int)$monster[$prefix.'min'], max((int)$monster[$prefix.'min'], (int)$monster[$prefix.'max']))+maze_monster_bonus($monster,'공격');
            $damage = max(0, $damage - maze_code($session, $party[$id], $settings['defense_code']));
            $damage = (int)round($damage * (1 - min(90, max(0, maze_effect_value($party[$id], 'GUARD'))) / 100));
            $before=$party[$id]['hp'];
            $party[$id]['hp'] = max(0, $party[$id]['hp'] - $damage);
            maze_combat_event($session,$battle['turn_no'],$monster['dg_mon_name'],$party[$id]['name'],$monster[$prefix.'comment']?:'공격',$before-$party[$id]['hp'],'DAMAGE');
            if($party[$id]['hp']<=0)maze_combat_event($session,$battle['turn_no'],$party[$id]['name'],$party[$id]['name'],'전투불능',0,'DEAD');
            if (!empty($monster['status_id']) && $party[$id]['hp'] > 0) maze_status_apply($session, $party[$id], (int)$monster['status_id']);
        }
    }
}
