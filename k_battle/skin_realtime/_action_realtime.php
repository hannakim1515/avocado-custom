<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// 기본 파라미터 방어 
$rm_id       = ses($_REQUEST, 'rm_id', 0, 'int');
$unit_type   = ses($_REQUEST, 'unit_type', 'ch');
$type        = ses($_REQUEST, 'type', '');
$target_id   = ses($_REQUEST, 'target_id', null);
$target_type = ses($_REQUEST, 'target_type', '');
$bs_id       = ses($_REQUEST, 'bs_id', 0, 'int');
if (!isset($option))               $option      = '';

$select = "origin.{$unit_type}_name AS unit_name, unit.*";
$rm     = get_k_unit($rm_id, $unit_type, $select);
if (!is_array($rm)) $rm = array();

$ra = sql_fetch("SELECT now_turn, ra_turn_type, ra_mo_auto FROM {$battle_table} WHERE {$ar_title} = '{$ra_id}'");
if (!is_array($ra)) $ra = array('now_turn' => null, 'ra_turn_type' => 'speed', 'ra_mo_auto' => 'free');

$data = array(
    'warning'     => '',
    'info'        => '',
    'disable'     => '',
    'target'      => '',
    'raid_action' => true,
);

$turn_type = ses($ra, 'ra_turn_type', 'speed');
$mo_auto   = ses($ra, 'ra_mo_auto', 'auto');
$next      = array();

// 기본 상태 체크
if (empty($rm) || !isset($rm['hp_now'])) {
    $data['warning'] = '유닛 정보를 찾을 수 없습니다.';
} elseif ((int)$rm['hp_now'] <= 0) {
    $data['warning'] = '현재 행동불능 상태입니다.';
} elseif (isset($ra['now_turn']) && (int)$ra['now_turn'] !== (int)$rm_id && $turn_type === 'speed') {
    $data['warning'] = '내 차례가 아닙니다.';
} elseif (!empty($rm['tt_done']) && $turn_type != 'free') {
    $data['warning'] = '이번 턴 행동이 종료되었습니다.';
} else {
    $unit_name = ses($rm, 'unit_name', '');
    $msg       = "<p class=\"log-title\">{$unit_name}의 행동</p>";

    if ($type === 'atk' || $type === 'heal') {
        $data['warning']=use_k_action($type, $rm, $target_id, $target_type, $ra_id, $msg, $option, $unit_name);
    } elseif ($type === 'item') {
        $data['warning']=use_k_item($rm, $target_id, $ra_id, $msg, $option);
    } elseif ($type === 'skill' && $bs_id) {
        if($rm['unit_type']=='ch'){
            $sk = get_k_battle_skill($bs_id, '*', true);
        }else{
            $sk = get_k_mo_skill($bs_id, $rm['unit_id']);
            $target_result = get_k_mo_skill_target($sk, $rm_id, $ra_id);
            $target_id = $target_result['target_ids'];
            $target_type = $target_result['target_type'];
        }
        $data['warning']=use_k_skill($sk, $rm, $target_id, $target_type, $ra_id, $msg, $option, $unit_name);
    }

    /**turn change**/
    if($turn_type==='speed'){
        if ($rm_id) {sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$rm_id}");}

        if ($mo_auto==='auto') { //몬스터 자동 행동시 
            $turn_break   = false;
            $loop_cnt     = 0;
            $max_loop_cnt = 100;

            while ($turn_break === false && $loop_cnt < $max_loop_cnt) {
                $loop_cnt++;
                $next = null;
                include './_action_turnchange.'.$turn_type.'.php';

                if (!empty($next['unit_type']) && $next['unit_type'] === 'mo' && !empty($next['rm_id'])) {
                    $mo_rm_id = (int)$next['rm_id'];
                    exec_k_mo_act($mo_rm_id, $ra_id, $option, true);
                    sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$mo_rm_id}");
                } else {
                    $turn_break = true;
                }
            }
        } else { //몬스터 수동행동시
            $next = null;
            include './_action_turnchange.'.$turn_type.'.php';
        }
    }elseif($turn_type==='turn'){
        if ($rm_id) {sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$rm_id}");}

        if ($mo_auto==='auto') { //몬스터 자동 행동시 
            $turn_break   = false;
            $loop_cnt     = 0;
            $max_loop_cnt = 100;

            while ($turn_break === false && $loop_cnt < $max_loop_cnt) {
                $loop_cnt++;
                $next = null;
                include './_action_turnchange.'.$turn_type.'.php';

                // 턴 브레이크 아니면 몬스터 행동 실행
                if (!$turn_break) {
                    $mo_list = get_k_unit_list('mo', $ra_id, 'unit.rm_id', 'AND unit.hp_now > 0 AND unit.tt_done = 0');
                    foreach ($mo_list as $m) {
                        exec_k_mo_act($m['rm_id'], $ra_id, $option);
                    }
                    sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE ra_id = '{$ra_id}' AND unit_type = 'mo' AND hp_now > 0");
                }
            }
        } else { //몬스터 수동행동시
            $next = null;
            include './_action_turnchange.'.$turn_type.'.php';
        }
    }elseif($turn_type==='free'){
        if ($mo_auto==='auto') { //몬스터 자동 행동시 
            $turn_break   = false;
            include './_action_turnchange.'.$turn_type.'.php';
            if(!$turn_break){
                $mo_list=get_k_unit_list('mo', $ra_id, 'unit.rm_id', 'AND unit.hp_now > 0');
                foreach ($mo_list as $m) {
                    exec_k_mo_act($m['rm_id'], $ra_id, $option);
                }
            }
        } else { //몬스터 수동행동시
            $next = null;
            include './_action_turnchange.'.$turn_type.'.php';
        }
    }

}

// next 정보가 없을 수 있으므로 방어
$data['ra']   = (is_array($next) && isset($next['ra'])) ? $next['ra'] : null;
$data['unit'] = get_k_unit_list_simple($ra_id, '*', '', '', true);

// Pusher broadcast (ra_system이 pusher일 때)
$ra_full = sql_fetch("SELECT ra_system, ra_time_limit, ra_time_start, ra_turn, ra_count, now_turn FROM {$battle_table} WHERE {$ar_title} = '{$ra_id}'");
if (ses($ra_full, 'ra_system', '') === 'pusher') {
    // 제한시간 정보 추가
    $time_limit_val = ses($ra_full, 'ra_time_limit', 0, 'int');
    $time_start_val = ses($ra_full, 'ra_time_start', 0, 'int');
    $broadcast_data = array(
        'type' => 'page',
        'unit' => $data['unit'],
        'ra' => array(
            'ra_turn' => ses($ra_full, 'ra_turn', 0, 'int'),
            'ra_count' => ses($ra_full, 'ra_count', 0, 'int'),
            'now_turn' => ses($ra_full, 'now_turn', 0, 'int'),
        ),
        'actor_rm_id' => $rm_id
    );
    if ($time_limit_val > 0 && $time_start_val > 0) {
        $broadcast_data['ra']['time_remaining'] = max(0, $time_limit_val - (time() - $time_start_val));
    }
    broadcast_pusher_event('raid-' . $ra_id, 'raid-update', $broadcast_data);
}

echo json_encode($data);
exit;
