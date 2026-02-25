<?php

if (!defined('K_DEBUG')) {
    define('K_DEBUG', k_debug_check_enabled());
}

/**
 * 디버그 활성화 여부 체크
*/
function k_debug_check_enabled() {
    global $kb_cf, $is_admin;
    
    // 관리자만 디버그 가능
    if ($is_admin !== 'super') {
        return false;
    }
    
    // GET/POST 파라미터
    if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == '1') {
        return true;
    }
    
    // 세션 설정
    if (isset($_SESSION['k_battle_debug']) && $_SESSION['k_battle_debug'] === true) {
        return true;
    }
    
    // 관리자 설정
    if (isset($kb_cf['debug']) && $kb_cf['debug'] === 'Y') {
        return true;
    }
    
    return false;
}

// 디버그 로그 저장소
if (K_DEBUG) {
    $GLOBALS['k_debug_logs'] = array();
    $GLOBALS['k_debug_start_time'] = microtime(true);
    $GLOBALS['k_debug_action'] = '';
}

/**
 * 디버그 초기화
 */
function k_debug_init($action_name = '') {
    if (!K_DEBUG) return;
    
    $GLOBALS['k_debug_start_time'] = microtime(true);
    $GLOBALS['k_debug_logs'] = array();
    $GLOBALS['k_debug_action'] = $action_name;
    
    k_debug_log('=== DEBUG START: ' . $action_name . ' ===', 'init');
}

/**
 * 디버그 로그 추가
*/
function k_debug_log($message, $type = 'info', $data = null) {
    if (!K_DEBUG) return;
    
    $elapsed = round((microtime(true) - $GLOBALS['k_debug_start_time']) * 1000, 2);
    
    $log = array(
        'time' => $elapsed . 'ms',
        'type' => $type,
        'msg' => $message
    );
    
    if ($data !== null) {
        $log['data'] = $data;
    }
    
    // 호출 위치 추적 (error, warn 타입만 - 비용이 높음)
    if ($type === 'error' || $type === 'warn') {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset($trace[1])) {
            $log['file'] = basename($trace[1]['file']) . ':' . $trace[1]['line'];
        }
    }
    
    $GLOBALS['k_debug_logs'][] = $log;
}

/**
 * 함수 진입 로그
 */
function k_debug_func_enter($func_name, $params = null) {
    if (!K_DEBUG) return;
    k_debug_log("→ {$func_name}()", 'func', $params);
}

/**
 * 함수 종료 로그
 */
function k_debug_func_exit($func_name, $result = null) {
    if (!K_DEBUG) return;
    
    $log_data = null;
    if ($result !== null) {
        if (is_array($result) && count($result) > 10) {
            $log_data = array('count' => count($result));
        } else {
            $log_data = $result;
        }
    }
    k_debug_log("← {$func_name}()", 'func', $log_data);
}

/**
 * SQL 쿼리 로그 (필요 시 사용)
 */
function k_debug_sql($sql, $result = null) {
    if (!K_DEBUG) return;
    k_debug_log('SQL', 'sql', array('q' => substr($sql, 0, 200)));
}

/**
 * 흐름 제어 로그
 */
function k_debug_flow($name, $step = '', $data = null) {
    if (!K_DEBUG) return;
    k_debug_log("[{$name}] {$step}", 'flow', $data);
}

/**
 * 변수 덤프
 */
function k_debug_var($name, $value) {
    if (!K_DEBUG) return;
    
    $display = $value;
    if (is_array($value) && count($value) > 10) {
        $display = array('_type' => 'array', '_count' => count($value));
    }
    k_debug_log('$' . $name, 'var', $display);
}

/**
 * 에러 로그
 */
function k_debug_error($message, $context = null) {
    if (!K_DEBUG) return;
    k_debug_log($message, 'error', $context);
}

/**
 * 경고 로그
 */
function k_debug_warn($message, $context = null) {
    if (!K_DEBUG) return;
    k_debug_log($message, 'warn', $context);
}

/**
 * 디버그 결과 가져오기
 */
function k_debug_get_result() {
    if (!K_DEBUG) return null;
    
    $total_time = round((microtime(true) - $GLOBALS['k_debug_start_time']) * 1000, 2);
    
    return array(
        'action' => $GLOBALS['k_debug_action'],
        'total_ms' => $total_time,
        'logs' => $GLOBALS['k_debug_logs'],
        'mem_mb' => round(memory_get_peak_usage() / 1048576, 2)
    );
}

/**
 * JSON 응답에 디버그 정보 추가
 */
function k_debug_append_to_response(&$response) {
    if (!K_DEBUG) return;
    
    if (!is_array($response)) {
        $response = array('_data' => $response);
    }
    
    $response['_debug'] = k_debug_get_result();
}

/**
 * 디버그 모드 토글 (세션)
 */
function k_debug_toggle($enable = null) {
    if ($enable === null) {
        $current = isset($_SESSION['k_battle_debug']) ? $_SESSION['k_battle_debug'] : false;
        $_SESSION['k_battle_debug'] = !$current;
    } else {
        $_SESSION['k_battle_debug'] = (bool)$enable;
    }
    return $_SESSION['k_battle_debug'];
}
