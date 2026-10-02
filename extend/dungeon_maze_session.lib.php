<?php
if (!defined('_GNUBOARD_')) exit;

function maze_config($dg) {
    $row = maze_one('SELECT settings FROM `'.maze_table('config').'` WHERE dg_id='.(int)$dg['dg_id']);
    $settings = array_replace(maze_defaults($dg), $row ? maze_data($row['settings']) : array());
    // dg_count remains the single source of maximum party size.
    $settings['max_players'] = (int)$dg['dg_count'];
    foreach ($settings['monsters'] as &$monster) $monster['data'] = maze_monster_definition($monster['data'], $dg);
    unset($monster);
    foreach ($settings['events'] as &$event) {
        if (isset($event['monster'])) $event['monster'] = maze_monster_definition($event['monster'], $dg);
        if (!empty($event['choices'])) foreach ($event['choices'] as &$choice) if (isset($choice['monster'])) $choice['monster'] = maze_monster_definition($choice['monster'], $dg);
        unset($choice);
    }
    unset($event);
    maze_validate_config($settings);
    return $settings;
}
function maze_validate_config($settings) {
    foreach (array('min_players','max_players','rooms_min','rooms_max','branches_min','branches_max') as $field) {
        if (!isset($settings[$field]) || filter_var($settings[$field], FILTER_VALIDATE_INT) === false) throw new RuntimeException('정수 설정이 필요합니다: '.$field);
    }
    if ($settings['min_players'] < 1 || $settings['min_players'] > $settings['max_players']) throw new RuntimeException('최소/최대 참가 인원을 확인해 주세요.');
    if ($settings['rooms_min'] < 3 || $settings['rooms_min'] > $settings['rooms_max'] || $settings['rooms_max'] > 500) throw new RuntimeException('방 수는 3~500 범위로 설정해 주세요.');
    if ($settings['branches_min'] < 0 || $settings['branches_max'] < $settings['branches_min'] || $settings['branches_max'] > floor(($settings['rooms_min'] - 1) / 2)) throw new RuntimeException('방 수에 맞는 갈림길 범위가 필요합니다.');
    foreach (array('dead_end_percent','encounter_percent','escape_percent') as $field) if (!is_numeric($settings[$field]) || $settings[$field] < 0 || $settings[$field] > 100) throw new RuntimeException('확률은 0~100입니다.');
    if (array_keys($settings['weights']) !== array('EMPTY','SEARCH','TREASURE','TRAP') || min($settings['weights']) < 0 || array_sum($settings['weights']) <= 0) throw new RuntimeException('방 종류별 가중치를 확인해 주세요.');
    if ($settings['boss'] && $settings['rooms_min'] < 2 * $settings['branches_max'] + 2) throw new RuntimeException('Boss room needs additional depth.');
    $bosses = 0;
    foreach ($settings['monsters'] as $monster) {
        if (empty($monster['data']['dg_mon_hp']) || $monster['data']['dg_mon_hp'] < 1 || $monster['weight'] < 0) throw new RuntimeException('몬스터 HP/가중치를 확인해 주세요.');
        if (!empty($monster['enabled']) && !empty($monster['boss']) && $monster['weight'] > 0) $bosses++;
    }
    if ($settings['boss'] && !$bosses) throw new RuntimeException('사용 가능한 보스 몬스터가 필요합니다.');
    if ($settings['points'] < 0 || $settings['trap_damage'] < 0) throw new RuntimeException('보상/피해는 음수가 될 수 없습니다.');
    foreach ($settings['rewards'] as $reward) if (empty($reward['it_id']) || $reward['count'] < 1 || $reward['count'] > 100 || $reward['percent'] < 0 || $reward['percent'] > 100) throw new RuntimeException('보상 설정이 잘못되었습니다.');
    foreach ($settings['events'] as $event) {
        if (!isset($settings['weights'][$event['room_kind']]) || $event['weight'] < 0) throw new RuntimeException('이벤트 방 종류/가중치를 확인해 주세요.');
        if (!empty($event['choices'])) {
            foreach ($event['choices'] as $choice) {
                if (!isset($choice['label']) || trim($choice['label']) === '' || !empty($choice['choices'])) throw new RuntimeException('선택지는 이름과 한 단계 결과가 필요합니다.');
                maze_validate_event_result($choice);
            }
        } else maze_validate_event_result($event);
    }
}
function maze_validate_event_result($event) {
    if (!isset($event['type']) || !in_array($event['type'], array('NONE','TEXT','ITEM','TRAP','MONSTER','HEAL'), true)) throw new RuntimeException('이벤트 결과 종류가 올바르지 않습니다.');
    if (isset($event['target']) && !in_array($event['target'], array('ACTOR','ALL','RANDOM'), true)) throw new RuntimeException('이벤트 대상이 올바르지 않습니다.');
    if ($event['type'] === 'ITEM' && (empty($event['it_id']) || empty($event['count']) || $event['count'] > 100)) throw new RuntimeException('이벤트 아이템/개수를 확인해 주세요.');
    if ($event['type'] === 'MONSTER' && empty($event['monster']['dg_mon_hp'])) throw new RuntimeException('이벤트 몬스터 HP가 필요합니다.');
    if (isset($event['value']) && (!is_numeric($event['value']) || $event['value'] < 0)) throw new RuntimeException('이벤트 수치는 0 이상이어야 합니다.');
}

function maze_admin_end($ds_id, $operation, $target_id = 0) {
    global $is_admin;
    if (!$is_admin) throw new RuntimeException('관리자 권한이 필요합니다.');
    maze_transaction($ds_id, function ($session) use ($operation, $target_id) {
        if (in_array($session['phase'], array('CLEAR','FAILED','CLOSED'), true)) return;
        if ($operation === 'clear' || $operation === 'fail') {
            if ($session['phase'] === 'WAITING' && $operation === 'clear') throw new RuntimeException('출발 전 세션에는 클리어 보상을 지급할 수 없습니다.');
            maze_finish($session, $operation === 'clear');
        } elseif ($operation === 'kick') {
            $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
            if (!isset($party[$target_id])) throw new RuntimeException('현재 참가자가 아닙니다.');
            inventory_boundary_lock_owners(array_column($party, 'ch_id'));
            maze_leave($session, $party[$target_id], 'ADMIN_KICK'); maze_recount_votes($session);
        } else throw new RuntimeException('관리자 작업이 올바르지 않습니다.');
        maze_log($session['ds_id'], 0, 'ADMIN', $operation.': '.(int)$target_id);
    });
    maze_sync_legacy_state((int)$ds_id);
    maze_pay_rewards((int)$ds_id);
}

/** POST join: no inventory movement and no status snapshot in WAITING. */
function maze_join($ds_id) {
    global $g5, $member, $character, $config;
    maze_require_ready();
    $owner = inventory_boundary_owner((int)$character['ch_id']);
    if (!isset($owner['ch_state']) || $owner['ch_state'] !== '승인') throw new RuntimeException('승인된 캐릭터만 참가할 수 있습니다.');
    $ds_id = (int)$ds_id; $ch_id = (int)$owner['ch_id'];
    $ds = get_dungeon_state($ds_id);
    if (empty($ds['ds_id']) || empty($config['cf_dungeon_open']) || empty($ds['dg_use']) || $ds['ds_state'] === 'E') throw new RuntimeException('입장할 수 없는 던전입니다.');
    if (!empty($config['cf_dungeon_map']) && (int)$owner['ma_id'] !== (int)$ds['ds_ma_id']) throw new RuntimeException('던전이 있는 지역에서 입장해 주세요.');
    $settings = maze_config($ds);
    $expires = date('Y-m-d H:i:s', strtotime($config['cf_dungeon_reset'].' +'.max(1, (int)$config['cf_dungeon_time']).' hours'));
    inventory_boundary_query('START TRANSACTION');
    try {
        inventory_boundary_query('INSERT INTO `'.maze_table('session').'` (ds_id,dg_id,snapshot,expires_at,created_at) VALUES ('.$ds_id.','.(int)$ds['dg_id'].','.inventory_boundary_quote(maze_json($settings)).','.inventory_boundary_quote($expires).',NOW()) ON DUPLICATE KEY UPDATE ds_id=VALUES(ds_id)');
        $session = maze_session($ds_id, true);
        $existing = maze_one('SELECT * FROM `'.maze_table('member').'` WHERE ds_id='.$ds_id.' AND ch_id='.$ch_id);
        if ($existing && $existing['state'] === 'ACTIVE') { inventory_boundary_query('COMMIT'); return; }
        if ($session['phase'] !== 'WAITING' || ($session['expires_at'] && strtotime($session['expires_at']) <= time())) throw new RuntimeException('이미 출발했거나 모집이 종료된 던전입니다.');
        inventory_boundary_lock_owners(array($ch_id));
        $other = maze_one('SELECT dm_id FROM `'.maze_table('member')."` WHERE ch_id={$ch_id} AND state='ACTIVE' AND ds_id<>{$ds_id} LIMIT 1");
        if ($other || is_has_dungeon($ch_id, $ds_id)) throw new RuntimeException('이미 다른 던전에 참가 중입니다.');
        $party = maze_party($ds_id);
        if (count($party) >= $settings['max_players']) throw new RuntimeException('최대 참가 인원에 도달했습니다.');
        $today = inventory_boundary_quote(date('Y-m-d').' 00:00:00');
        $count = maze_one('SELECT COUNT(*) AS cnt FROM `'.maze_table('member')."` WHERE ch_id={$ch_id} AND settled=1 AND exited_at>={$today}");
        $old = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['dungeon_member_table']} WHERE ch_id={$ch_id} AND dm_result<>'' AND dm_datetime='".date('Y-m-d')."'");
        if ((int)$count['cnt'] + (int)$old['cnt'] >= (int)$config['cf_dungeon_enter']) throw new RuntimeException('하루 입장 횟수를 초과했습니다.');
        $data = array('ds_id' => $ds_id, 'ch_id' => $ch_id, 'mb_id' => $member['mb_id'], 'name' => $owner['ch_name'], 'state' => 'ACTIVE', 'ready' => 0, 'settled' => 0, 'stats' => '{}', 'skills' => '{}', 'effects' => '{}', 'joined_at' => date('Y-m-d H:i:s'), 'exited_at' => null);
        if ($existing) maze_update('member', $data, 'dm_id='.(int)$existing['dm_id']); else maze_insert('member', $data);
        maze_log($ds_id, 0, 'PARTY', $owner['ch_name'].' 입장');
        inventory_boundary_query('COMMIT');
    } catch (Throwable $error) { sql_query('ROLLBACK', false); throw $error; }
}

/** All gameplay mutations acquire session first, then participant owner guards. */
function maze_transaction($ds_id, $callback) {
    maze_require_ready();
    inventory_boundary_query('START TRANSACTION');
    try {
        $session = maze_session((int)$ds_id, true);
        if (!$session) throw new RuntimeException('미궁 세션이 없습니다.');
        $result = $callback($session);
        inventory_boundary_query('COMMIT');
        return $result;
    } catch (Throwable $error) { sql_query('ROLLBACK', false); throw $error; }
}
function maze_monster_definition($input, $base) {
    if (!is_array($input)) throw new RuntimeException('몬스터 설정은 객체여야 합니다.');
    $monster = array_replace($base, $input);
    foreach (array('d','w','s1','s2') as $pattern) {
        foreach (array('turn','count','min','max','st_id','st_value') as $suffix) {
            $key='dg_'.$pattern.'_attack_'.$suffix;
            if (!isset($monster[$key])) $monster[$key]=0;
            if (!is_numeric($monster[$key]) || $monster[$key]<0) throw new RuntimeException('몬스터 공격 설정 수치가 올바르지 않습니다.');
            $monster[$key]=(int)$monster[$key];
        }
        $prefix='dg_'.$pattern.'_attack_';
        if($monster[$prefix.'min']>$monster[$prefix.'max'])throw new RuntimeException('몬스터 최소 피해가 최대 피해보다 큽니다.');
        if(!isset($monster[$prefix.'type']))$monster[$prefix.'type']='이상';
    }
    foreach(array('dg_defence','dg_weak_effect_count','dg_weak_effect_turn') as $key)if(!isset($monster[$key]))$monster[$key]=0;
    foreach(array('dg_strong_code','dg_weak_code') as $key)if(!isset($monster[$key]))$monster[$key]='';
    foreach(array('dg_strong_value','dg_weak_value') as $key)if(!isset($monster[$key]))$monster[$key]=1;
    return $monster;
}
function maze_expire_waiting() {
    if (!maze_installed()) return;
    $rows = inventory_boundary_rows('SELECT ds_id FROM `'.maze_table('session')."` WHERE phase='WAITING' AND expires_at<=NOW() LIMIT 100");
    foreach ($rows as $row) maze_transaction($row['ds_id'], function ($session) {
        if ($session['phase'] !== 'WAITING' || !$session['expires_at'] || strtotime($session['expires_at']) > time()) return;
        maze_update('member', array('state' => 'LEFT', 'ready' => 0, 'exited_at' => date('Y-m-d H:i:s')), 'ds_id='.(int)$session['ds_id']." AND state='ACTIVE'");
        maze_update('session', array('phase' => 'CLOSED', 'finished_at' => date('Y-m-d H:i:s')), 'ds_id='.(int)$session['ds_id']);
    });
}

function maze_start($session, $actor) {
    global $g5;
    if ($session['phase'] !== 'WAITING' || !$actor['ready'] || ($session['expires_at'] && strtotime($session['expires_at']) <= time())) throw new RuntimeException('지금은 출발할 수 없습니다.');
    $ds = get_dungeon_state((int)$session['ds_id']);
    if (empty($ds['dg_use']) || $ds['ds_state'] === 'E') throw new RuntimeException('모집이 종료되었습니다.');
    $settings = maze_config($ds);
    $party = maze_party($session['ds_id']);
    if (count($party) < $settings['min_players'] || count($party) > $settings['max_players']) throw new RuntimeException('출발 인원을 확인해 주세요.');
    foreach ($party as $person) if (!$person['ready']) throw new RuntimeException('참가자 전원이 READY 상태여야 합니다.');
    inventory_boundary_lock_owners(array_column($party, 'ch_id'));
    $definitions = inventory_boundary_rows("SELECT * FROM `{$g5['status_config_table']}` ORDER BY st_id");
    $hp_id = 0;
    foreach ($definitions as $definition) if (!empty($definition['st_use_hp'])) { $hp_id = (int)$definition['st_id']; break; }
    if (!$hp_id) throw new RuntimeException('HP 스탯 설정이 필요합니다.');
    $settings['title'] = isset($ds['dg_title']) ? $ds['dg_title'] : '미궁';
    $settings['stat_definitions'] = $definitions;
    $reward_ids = array_unique(array_map('intval',array_column($settings['rewards'],'it_id')));
    $settings['reward_items'] = array();
    if ($reward_ids) foreach (inventory_boundary_rows('SELECT * FROM `'.$g5['item_table'].'` WHERE it_id IN ('.implode(',',$reward_ids).')') as $reward_item) $settings['reward_items'][(int)$reward_item['it_id']] = $reward_item;
    if (count($settings['reward_items']) !== count($reward_ids)) throw new RuntimeException('클리어 보상 아이템 정의가 없습니다.');
    $settings['hp_id'] = $hp_id;
    $settings['attack_code'] = unified_combat_action_code('atk');
    $settings['defense_code'] = '방어력';
    $settings['guard_code'] = unified_combat_action_code('guard');
    $settings['heal_code'] = unified_combat_action_code('heal');
    $settings['codes'] = inventory_boundary_rows("SELECT * FROM `{$g5['status_extra_table']}`");
    $code_names=array_column($settings['codes'],'ex_name');
    foreach(array('attack_code','defense_code','heal_code','guard_code') as $key) if($settings[$key]==='' || !in_array($settings[$key],$code_names,true)) throw new RuntimeException('출발 전에 공격·방어·회복 연동 코드를 설정해 주세요.');
    $settings['status_registry'] = inventory_boundary_rows('SELECT * FROM `'.maze_table('status').'` WHERE enabled=1');
    foreach ($party as $person) {
        $stats = array();
        foreach ($definitions as $definition) {
            $id = (int)$definition['st_id'];
            $value = function_exists('unified_stat_value') ? unified_stat_value((int)$person['ch_id'], $id) : get_status($person['ch_id'], $id)['now'];
            if ((int)$ds['dg_status'] === $id) $value = $ds['dg_status_type'] === 'x' ? $value * (float)$ds['dg_status_value'] : $value + (float)$ds['dg_status_value'];
            $stats[$id] = (int)$value;
        }
        if ($stats[$hp_id] < 1) throw new RuntimeException('전투 가능한 HP가 없는 참가자가 있습니다.');
        $stats['_maximum'] = $stats;
        $skills = get_skill_set_list((int)$person['ch_id']);
        foreach ($skills as &$skill) $skill['sh_limit'] = 0;
        unset($skill);
        maze_update('member', array('stats' => maze_json($stats), 'skills' => maze_json($skills), 'hp' => $stats[$hp_id], 'max_hp' => $stats[$hp_id], 'effects' => '{}'), 'dm_id='.(int)$person['dm_id']);
    }
    maze_inventory_isolate($session, $party);
    $generated_rooms = array();
    foreach (maze_generate($settings) as $room) {
        $room['ds_id'] = $session['ds_id']; $room['payload'] = maze_json($room['payload']);
        $generated_rooms[] = $room;
    }
    maze_insert_many('room',$generated_rooms);
    maze_update('session', array('phase' => 'EXPLORE', 'room_id' => 1, 'snapshot' => maze_json($settings), 'started_at' => date('Y-m-d H:i:s'), 'version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
    maze_log($session['ds_id'], $actor['dm_id'], 'START', '미궁 탐험을 시작했습니다.');
}

function maze_finish($session, $clear) {
    if (in_array($session['phase'], array('CLEAR','FAILED','CLOSED'), true)) return;
    $party = maze_party($session['ds_id']);
    inventory_boundary_lock_owners(array_column($party, 'ch_id'));
    foreach ($party as $person) maze_settle_member($session, $person, $clear ? 'CLEAR' : 'FAILED', $clear);
    if ($session['battle_id']) inventory_boundary_query('UPDATE `'.maze_table('battle').'` SET state="CLOSED" WHERE battle_id='.(int)$session['battle_id'].' AND state="ACTIVE"');
    maze_update('session', array('phase' => $clear ? 'CLEAR' : 'FAILED', 'finished_at' => date('Y-m-d H:i:s'), 'version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
    inventory_boundary_query('DELETE FROM `'.maze_table('vote').'` WHERE ds_id='.(int)$session['ds_id']);
}
