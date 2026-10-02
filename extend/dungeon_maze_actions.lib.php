<?php
if (!defined('_GNUBOARD_')) exit;

function maze_room($session) {
    $room = maze_one('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$session['room_id']);
    if (!$room) throw new RuntimeException('현재 방을 확인할 수 없습니다.');
    return $room;
}
function maze_version($session, $input) {
    if (!isset($input['version']) || (string)$session['version'] !== (string)$input['version']) throw new RuntimeException('다른 참가자가 먼저 진행했습니다. 화면을 새로고침해 주세요.');
}
function maze_alive_or_fail($session) {
    $party = maze_party($session['ds_id']);
    foreach ($party as $person) if ($person['hp'] > 0) return true;
    maze_finish($session, false); return false;
}
function maze_leave($session, $person, $reason) {
    inventory_boundary_lock_owners(array((int)$person['ch_id']));
    if ($session['phase'] === 'WAITING') {
        maze_update('member', array('state' => 'LEFT', 'ready' => 0, 'exited_at' => date('Y-m-d H:i:s')), 'dm_id='.(int)$person['dm_id']);
    } else maze_settle_member($session, $person, $reason);
    $ds_id = (int)$session['ds_id']; $id = (int)$person['dm_id'];
    inventory_boundary_query('DELETE FROM `'.maze_table('vote')."` WHERE ds_id={$ds_id} AND (target_id={$id} OR voter_id={$id})");
    if ($session['battle_id']) inventory_boundary_query('DELETE FROM `'.maze_table('action').'` WHERE battle_id='.(int)$session['battle_id'].' AND dm_id='.$id);
}
function maze_recount_votes($session) {
    // Party shrink can make another existing vote unanimous; iterate only removals.
    do {
        $removed = false; $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
        $votes = inventory_boundary_rows('SELECT target_id,voter_id FROM `'.maze_table('vote').'` WHERE ds_id='.(int)$session['ds_id']);
        $targets = array();
        foreach ($votes as $vote) if (isset($party[$vote['target_id']], $party[$vote['voter_id']]) && $vote['target_id'] !== $vote['voter_id']) $targets[$vote['target_id']][$vote['voter_id']] = true;
        foreach ($targets as $target_id => $voters) if (count($party) > 1 && count($voters) === count($party) - 1) {
            maze_leave($session, $party[$target_id], 'KICKED'); $removed = true; break;
        }
    } while ($removed);
    if ($session['phase'] !== 'WAITING') maze_alive_or_fail($session);
}
function maze_move($session, $input) {
    $room = maze_room($session);
    $direction = isset($input['direction']) ? $input['direction'] : '';
    if ($direction === 'back') $next = maze_one('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$room['parent_id']);
    elseif (in_array($direction, array('forward','left','right'), true)) $next = maze_one('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.(int)$session['ds_id'].' AND parent_id='.(int)$room['room_id'].' AND direction='.inventory_boundary_quote($direction));
    else throw new RuntimeException('올바른 이동 방향이 아닙니다.');
    if (!$next) throw new RuntimeException('그 방향으로 이동할 수 없습니다.');
    if ($next['is_exit']) maze_require_clear_allowed($session);
    maze_update('room', array('visited' => 1), 'ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$next['room_id']);
    maze_update('session', array('room_id' => $next['room_id'], 'version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
    $session['room_id'] = $next['room_id']; $session['version']++;
    $payload = maze_data($next['payload']);
    maze_log($session['ds_id'], 0, 'MOVE', '방 '.$next['room_id'].' 이동');
    if (!$next['encounter_done'] && !empty($payload['monster'])) maze_battle_begin($session, $next, $payload['monster']);
    elseif ($next['is_exit']) maze_finish($session, true);
}
function maze_event($session, $actor, $input) {
    $room = maze_room($session);
    if ($room['resolved']) throw new RuntimeException('이미 처리한 방입니다.');
    $payload = maze_data($room['payload']); $event = $payload['event'];
    $settings = maze_data($session['snapshot']);
    if (!$event) $event = array('type' => $room['kind'] === 'TRAP' ? 'TRAP' : 'NONE', 'text' => '', 'target' => 'ACTOR');
    if (!empty($event['choices'])) {
        $choice = isset($input['choice']) ? (int)$input['choice'] : -1;
        if (!array_key_exists($choice, $event['choices'])) throw new RuntimeException('선택지를 골라 주세요.');
        $event = $event['choices'][$choice];
    }
    $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
    $living = array_keys(array_filter($party, function ($person) { return $person['hp'] > 0; }));
    $type = $event['type'];
    if ($type === 'ITEM') {
        $item = get_item((int)$event['it_id']);
        foreach ($living as $id) maze_inventory_gain($session, $party[$id], $item, (int)$event['count']);
        maze_log($session['ds_id'], $actor['dm_id'], 'ITEM_GAIN', $item['it_name'].' × '.(int)$event['count'].' · 생존 참가자 각자 획득');
    } elseif ($type === 'TRAP' || $type === 'HEAL') {
        $target = isset($event['target']) ? $event['target'] : 'ACTOR';
        $targets = $target === 'ALL' ? $living : ($target === 'RANDOM' ? array($living[random_int(0, count($living) - 1)]) : array((int)$actor['dm_id']));
        foreach ($targets as $id) {
            if ($type === 'TRAP') {
                $base = isset($event['value']) ? (int)$event['value'] : (int)$settings['trap_damage'];
                $party[$id]['hp'] = max(0, $party[$id]['hp'] - max(0, $base - maze_code($session, $party[$id], $settings['defense_code'])));
                if (!empty($event['status_id']) && $party[$id]['hp'] > 0) maze_status_apply($session, $party[$id], (int)$event['status_id']);
            } else maze_heal($party[$id], (int)$event['value']);
            maze_person_save($party[$id]);
        }
    } elseif ($type === 'MONSTER') {
        if (empty($event['monster'])) throw new RuntimeException('이벤트 몬스터 설정이 없습니다.');
        maze_battle_begin($session, $room, $event['monster']);
    } elseif (!in_array($type, array('NONE','TEXT'), true)) throw new RuntimeException('이벤트 결과 설정이 잘못되었습니다.');
    $text = isset($event['text']) ? $event['text'] : '';
    maze_update('room', array('resolved' => 1, 'result_text' => $text), 'ds_id='.(int)$session['ds_id'].' AND room_id='.(int)$room['room_id']);
    maze_update('session', array('version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
    maze_log($session['ds_id'], $actor['dm_id'], 'EVENT', $type.': '.$text);
    maze_alive_or_fail($session);
}

/** Single server authority for gameplay endpoints. CSRF belongs to HTTP adapter. */
function maze_action($ds_id, $operation, $input) {
    $input += array('battle_id'=>0,'turn_no'=>0,'room_id'=>0,'action'=>'','inventory_id'=>0,'target_id'=>0);
    $result = maze_transaction($ds_id, function ($session) use ($operation, $input) {
        $actor = maze_actor($session);
        if (in_array($session['phase'], array('CLEAR','FAILED','CLOSED'), true)) throw new RuntimeException('종료된 미궁입니다.');
        if ($operation === 'chat') {
            $message = trim(isset($input['message']) ? $input['message'] : '');
            if ($message === '' || mb_strlen($message, 'UTF-8') > 1000) throw new RuntimeException('채팅은 1~1000자로 입력해 주세요.');
            maze_log($session['ds_id'], $actor['dm_id'], 'CHAT', $message); return;
        }
        if ($session['phase'] === 'BOSS_RESULT') {
            if ($operation !== 'escape_final' || $actor['hp'] <= 0) throw new RuntimeException('보스 처치 후에는 탈출만 가능합니다.');
            maze_finish($session, true); return;
        }
        if ($session['phase'] === 'BATTLE') {
            $battle = maze_battle_current($session);
            if ($battle['deadline_at'] && strtotime($battle['deadline_at']) <= time()) {
                if ($operation === 'submit') maze_check_escape_vote($session, $input);
                maze_resolve($session, $battle, false); return;
            }
        }
        if ($operation === 'leave' || $operation === 'vote') {
            maze_version($session, $input);
            // Lock the whole small party in deterministic order before a cascading kick.
            inventory_boundary_lock_owners(array_column(maze_party($session['ds_id']), 'ch_id'));
            if ($operation === 'leave') maze_leave($session, $actor, 'EXIT');
            else {
                $id = isset($input['target_id']) ? (int)$input['target_id'] : 0;
                $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
                if (!isset($party[$id]) || $id === (int)$actor['dm_id']) throw new RuntimeException('강퇴 투표 대상이 올바르지 않습니다.');
                $where = 'ds_id='.(int)$session['ds_id'].' AND target_id='.$id.' AND voter_id='.(int)$actor['dm_id'];
                if (!empty($input['cancel'])) inventory_boundary_query('DELETE FROM `'.maze_table('vote').'` WHERE '.$where);
                else inventory_boundary_query('INSERT INTO `'.maze_table('vote').'` (ds_id,target_id,voter_id) VALUES ('.(int)$session['ds_id'].','.$id.','.(int)$actor['dm_id'].') ON DUPLICATE KEY UPDATE voter_id=VALUES(voter_id)');
            }
            maze_recount_votes($session); return;
        }
        if ($session['phase'] === 'WAITING') {
            if ($session['expires_at'] && strtotime($session['expires_at']) <= time()) throw new RuntimeException('모집이 종료되었습니다.');
            if ($operation === 'ready') maze_update('member', array('ready' => !empty($input['ready']) ? 1 : 0), 'dm_id='.(int)$actor['dm_id']);
            elseif ($operation === 'start') maze_start($session, $actor);
            else throw new RuntimeException('대기실에서 실행할 수 없는 행동입니다.');
            return;
        }
        if ($actor['hp'] <= 0) throw new RuntimeException('전투불능 상태에서는 행동할 수 없습니다.');
        if ($session['phase'] === 'BATTLE') {
            if ($operation === 'submit') maze_submit($session, $actor, $input);
            elseif ($operation === 'resolve') {
                $battle = maze_battle_current($session);
                if ((int)$input['battle_id'] !== (int)$battle['battle_id'] || (int)$input['turn_no'] !== (int)$battle['turn_no']) throw new RuntimeException('이미 처리된 턴입니다.');
                maze_resolve($session, $battle, true);
            } else throw new RuntimeException('전투 중에는 탐험할 수 없습니다.');
            return;
        }
        if ($session['phase'] !== 'EXPLORE') throw new RuntimeException('탐험 중이 아닙니다.');
        maze_version($session, $input);
        if ((int)$input['room_id'] !== (int)$session['room_id']) throw new RuntimeException('이미 다른 방으로 이동했습니다.');
        if ($operation === 'move') maze_move($session, $input);
        elseif ($operation === 'event') maze_event($session, $actor, $input);
        elseif ($operation === 'item') {
            $party = array_column(maze_party($session['ds_id']), null, 'dm_id');
            $target_id = (int)$input['target_id'];
            if (!isset($party[$target_id])) throw new RuntimeException('현재 파티의 대상만 선택할 수 있습니다.');
            maze_item_apply($session, $actor, $party[$target_id], (int)$input['inventory_id']);
            maze_person_save($party[$target_id]);
            maze_update('session', array('version' => (int)$session['version'] + 1), 'ds_id='.(int)$session['ds_id']);
        } else throw new RuntimeException('지원하지 않는 행동입니다.');
    });
    maze_pay_rewards((int)$ds_id);
    return $result;
}
