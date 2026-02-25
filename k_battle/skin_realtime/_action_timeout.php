<?php
/**
 * 제한시간 타임아웃 처리
 * - 클라이언트에서 제한시간 만료 시 호출
 * - 현재 턴 유닛 행동 스킵 후 다음 유닛으로 진행
 * - 중복 호출 방지: ra_time_start를 조건부 업데이트하여 락 처리
 */

// 모든 에러 무시 (common.php의 Notice 에러 등)
error_reporting(0);
ini_set('display_errors', 0);
set_error_handler(function() { return true; });

header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// _debug.php 로드 시도 (없으면 스킵)
if (file_exists('./_debug.php')) {
    include_once './_debug.php';
}

// 디버그 함수가 없으면 빈 함수 정의
if (!function_exists('k_debug_init')) {
    function k_debug_init($name) {}
}
if (!function_exists('k_debug_log')) {
    function k_debug_log($msg, $type = 'info', $data = array()) {}
}
if (!function_exists('k_debug_var')) {
    function k_debug_var($name, $value) {}
}
if (!function_exists('k_debug_error')) {
    function k_debug_error($msg) {}
}
if (!function_exists('k_debug_warn')) {
    function k_debug_warn($msg, $data = array()) {}
}
if (!function_exists('k_debug_flow')) {
    function k_debug_flow($name, $state) {}
}
if (!function_exists('k_debug_append_to_response')) {
    function k_debug_append_to_response(&$data) {}
}

k_debug_init('timeout');

$data = array(
    'result'  => 'error',
    'message' => '',
    'ra'      => null,
    'unit'    => array()
);

k_debug_log('Timeout action started', 'param', array('ra_id' => $ra_id));

// 레이드 정보 조회
$ra = sql_fetch("SELECT ra_state, ra_turn_type, ra_mo_auto, ra_time_limit, ra_time_start, now_turn, ra_turn 
                 FROM {$battle_table} 
                 WHERE {$ar_title} = '{$ra_id}'");

if (!is_array($ra) || empty($ra)) {
    $data['message'] = '레이드 정보를 찾을 수 없습니다.';
    k_debug_error('Raid not found');
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}

k_debug_var('ra', $ra);

// 진행 중인 레이드인지 확인
if ((int)$ra['ra_state'] !== 1) {
    $data['message'] = '진행 중인 레이드가 아닙니다.';
    k_debug_warn('Raid not in progress', array('ra_state' => $ra['ra_state']));
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}

$turn_type   = ses($ra, 'ra_turn_type', 'speed');
$mo_auto     = ses($ra, 'ra_mo_auto', 'auto');
$time_limit  = ses($ra, 'ra_time_limit', 0, 'int');
$time_start  = ses($ra, 'ra_time_start', 0, 'int');
$now_turn    = ses($ra, 'now_turn', 0, 'int');

k_debug_log('Timeout params', 'info', array(
    'turn_type' => $turn_type,
    'mo_auto' => $mo_auto,
    'time_limit' => $time_limit,
    'time_start' => $time_start,
    'now_turn' => $now_turn
));

// free 모드는 제한시간 미적용
if ($turn_type === 'free') {
    $data['message'] = 'free 모드는 제한시간이 적용되지 않습니다.';
    k_debug_warn('Free mode - no time limit');
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}

// 제한시간 미설정 시
if ($time_limit <= 0) {
    $data['message'] = '제한시간이 설정되지 않았습니다.';
    k_debug_warn('No time limit set');
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}

// 실제로 타임아웃인지 서버에서 검증
$current_time = time();
$elapsed = $current_time - $time_start;

k_debug_log('Time check', 'info', array(
    'current_time' => $current_time,
    'elapsed' => $elapsed,
    'time_limit' => $time_limit
));

if ($elapsed < $time_limit) {
    $data['message'] = '아직 제한시간이 남아있습니다.';
    $data['remaining'] = $time_limit - $elapsed;
    k_debug_warn('Time remaining - not timeout yet', array('remaining' => $data['remaining']));
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}

// ============================================
// 중복 처리 방지: 조건부 업데이트로 락 획득
// ra_time_start를 새 값으로 업데이트하되, 
// 기존 값이 일치할 때만 업데이트 (atomic operation)
// ============================================
k_debug_flow('lock_acquire', 'start');
$new_time_start = $current_time;
$lock_sql = "UPDATE {$battle_table} 
             SET ra_time_start = '{$new_time_start}' 
             WHERE {$ar_title} = '{$ra_id}' 
               AND ra_time_start = '{$time_start}'";

sql_query($lock_sql);
$affected_rows = mysqli_affected_rows($g5['connect_db']);

k_debug_log('Lock attempt', 'info', array('affected_rows' => $affected_rows));

// 업데이트된 행이 없으면 다른 클라이언트가 이미 처리 중
if ($affected_rows === 0) {
    $data['result'] = 'already_processed';
    $data['message'] = '이미 처리 중입니다.';
    k_debug_warn('Lock failed - already being processed by another client');
    k_debug_flow('lock_acquire', 'end');
    k_debug_append_to_response($data);
    echo json_encode($data);
    exit;
}
k_debug_log('Lock acquired successfully', 'info');
k_debug_flow('lock_acquire', 'end');

// ============================================
// 타임아웃 처리 시작
// ============================================
k_debug_flow('timeout_process', 'start');
$option = '';
$next = array();

if ($turn_type === 'speed') {
    k_debug_log('Speed mode timeout processing', 'info');
    // speed 모드: 현재 유닛 행동 스킵
    if ($now_turn > 0) {
        // 현재 유닛 정보 조회
        $current_unit = sql_fetch("SELECT unit.rm_id, unit.unit_type, unit.unit_id,
                                          CASE unit.unit_type 
                                              WHEN 'ch' THEN ch.ch_name 
                                              WHEN 'mo' THEN mo.mo_name 
                                          END AS unit_name
                                   FROM {$battle_table}_unit unit
                                   LEFT JOIN {$g5['character_table']} ch ON unit.unit_type = 'ch' AND unit.unit_id = ch.ch_id
                                   LEFT JOIN {$g5['k_monster_table']} mo ON unit.unit_type = 'mo' AND unit.unit_id = mo.mo_id
                                   WHERE unit.rm_id = '{$now_turn}'");
        
        $unit_name = ses($current_unit, 'unit_name', '');
        k_debug_log('Current unit timed out', 'info', array('unit_name' => $unit_name, 'rm_id' => $now_turn));
        
        // 행동 완료 처리
        sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = '{$now_turn}'");
        
        // 타임아웃 로그
        $ra_info = k_count_up($ra_id);
        $ra_turn_val  = ses($ra_info, 'ra_turn', 0, 'int');
        $ra_count_val = ses($ra_info, 'ra_count', 0, 'int');
        $option = ", lo_2 = '{$ra_turn_val}', lo_3 = '{$ra_count_val}'";
        
        $log_msg = $unit_name . '의 행동 시간이 초과되었습니다.';
        insert_k_log($log_msg, $ra_id, 'system', $option);
    }
    
    // 다음 유닛으로 턴 변경
    if ($mo_auto=='auto') {
        k_debug_log('Monster auto mode - loop for next turn', 'info');
        $turn_break = false;
        $loop_cnt = 0;
        $max_loop_cnt = 100;
        
        while ($turn_break === false && $loop_cnt < $max_loop_cnt) {
            $loop_cnt++;
            k_debug_log("Loop iteration {$loop_cnt}", 'info');
            include './_action_turnchange.speed.php';
            
            if (!empty($next['unit_type']) && $next['unit_type'] === 'mo' && !empty($next['rm_id'])) {
                $mo_rm_id = (int)$next['rm_id'];
                k_debug_log('Monster turn in loop - executing action', 'info', array('mo_rm_id' => $mo_rm_id));
                exec_k_mo_act($mo_rm_id, $ra_id, $option);
                sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$mo_rm_id}");
            } else {
                k_debug_log('Character turn or no next unit - break loop', 'info');
                $turn_break = true;
            }
        }
    } else {
        k_debug_log('Monster manual mode - single turn change', 'info');
        include './_action_turnchange.speed.php';
    }
    
} elseif ($turn_type === 'turn') {
    k_debug_log('Turn mode timeout processing', 'info');
    // turn 모드: 현재 턴 종료, 모든 캐릭터 행동 완료 처리
    sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 
               WHERE ra_id = '{$ra_id}' AND unit_type = 'ch' AND hp_now > 0");
    k_debug_log('All characters marked as done', 'info');
    
    // 타임아웃 로그
    $ra_info = k_count_up($ra_id);
    $ra_turn_val  = ses($ra_info, 'ra_turn', 0, 'int');
    $ra_count_val = ses($ra_info, 'ra_count', 0, 'int');
    $option = ", lo_2 = '{$ra_turn_val}', lo_3 = '{$ra_count_val}'";
    
    $log_msg = '턴 제한시간이 초과되었습니다.';
    insert_k_log($log_msg, $ra_id, 'system', $option);
    
    // 몬스터 자동 행동
    if ($mo_auto=='auto') {
        k_debug_log('Monster auto mode in turn type', 'info');
        $turn_break = false;
        include './_action_turnchange.turn.php';
        
        if (!$turn_break) {
            $mo_list = get_k_unit_list('mo', $ra_id, 'unit.rm_id', 'AND unit.hp_now > 0 AND unit.tt_done = 0');
            k_debug_log('Executing monster actions', 'info', array('monster_count' => count($mo_list)));
            foreach ($mo_list as $m) {
                exec_k_mo_act($m['rm_id'], $ra_id, $option);
            }
            sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 
                       WHERE ra_id = '{$ra_id}' AND unit_type = 'mo' AND hp_now > 0");
            
            // 몬스터 행동 완료 후 다음 턴으로 전환
            k_debug_log('Monster actions complete - checking turn change', 'info');
            include './_action_turnchange.turn.php';
        }
    } else {
        k_debug_log('Monster manual mode in turn type', 'info');
        include './_action_turnchange.turn.php';
    }
}
k_debug_flow('timeout_process', 'end');

$data['result'] = 'success';
$data['message'] = '타임아웃 처리 완료';
$data['ra']   = (is_array($next) && isset($next['ra'])) ? $next['ra'] : null;
$data['unit'] = get_k_unit_list_simple($ra_id);

// 에러 로그 포함 (디버그용)
if (!empty($_timeout_errors)) {
    $data['_errors'] = $_timeout_errors;
}

k_debug_log('Timeout response prepared', 'info', array(
    'result' => $data['result'],
    'unit_count' => count($data['unit'])
));

k_debug_append_to_response($data);
echo json_encode($data);
exit;
