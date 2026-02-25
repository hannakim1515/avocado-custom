<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// 파라미터 기본값 방어 (REQUEST 값이 있으면 덮어씀)
$ra_turn    = ses($_REQUEST, 'ra_turn', 0, 'int');
$ra_count   = ses($_REQUEST, 'ra_count', 0, 'int');
$rm_id      = ses($_REQUEST, 'rm_id', 0, 'int');
$type       = ses($_REQUEST, 'type', '');
$my_reload  = ses($_REQUEST, 'my_reload', 1, 'int');

// 레이드 정보
$ra = sql_fetch("SELECT * FROM {$battle_table} WHERE {$ar_title} = '{$ar_value}'");
if (!is_array($ra)) {
    $ra = array();
}

$reload = array(
    'type' => '',
    'check'=>''
);

if (!empty($ra['ra_reload'])) {

    if ($type === 'pre' && !empty($ra['ra_state']) && (int)$ra['ra_state'] > 0) {
        // 시작 전(pre)인데 이미 진행 중이면 전체 새로고침
        $reload['type'] = 'all';
    } elseif ($type === 'default') {
        $reload['ra']=$ra;
        
        // 제한시간 정보 추가
        $time_limit_val = ses($ra, 'ra_time_limit', 0, 'int');
        $time_start_val = ses($ra, 'ra_time_start', 0, 'int');
        if ($time_limit_val > 0 && $time_start_val > 0) {
            $reload['ra']['time_remaining'] = max(0, $time_limit_val - (time() - $time_start_val));
        }

        // 내 턴이고, 클라이언트에서 my_reload가 true라면 전체 새로고침
        if($ra['ra_state']>1){
            $reload['type'] = 'all';
        }elseif (!empty($ra['now_turn']) && (int)$ra['now_turn'] === (int)$rm_id && $my_reload==1) {
            $reload['type'] = 'all';
        }elseif($ra['ra_reload']!='myturn'){
            $page_reload    = false;
            $need_turn_check = false;
            $reload_mode    = ses($ra, 'ra_reload', 'turn');
            $srv_count      = ses($ra, 'ra_count', 0, 'int');
            $srv_turn       = ses($ra, 'ra_turn', 0, 'int');

            switch ($reload_mode) {
                case 'count': // count가 다르면 즉시 리로드 후 turn 비교는 안 함
                    if ($ra_count != $srv_count) {
                        $page_reload = true;
                        break;
                    }
                    $need_turn_check = true;
                    break;

                case 'turn': // turn 모드는 무조건 턴만 검사
                default:
                    $need_turn_check = true;
                    break;
            }
            // turn 비교
            if ($need_turn_check && $ra_turn != $srv_turn) {
                $page_reload = true;
            }
            if ($page_reload) {
                $reload['unit'] = get_k_unit_list_simple($ra_id, '*', '', '', true);
                $reload['type'] = 'page';
            }
        }
        
    }
}

echo json_encode($reload);
exit;
