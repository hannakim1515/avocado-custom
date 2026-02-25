<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['k_quest_table'] = G5_TABLE_PREFIX.'k_quest';
$g5['k_quest_has_table'] = G5_TABLE_PREFIX.'k_quest_has';
$g5['k_quest_config_table'] = G5_TABLE_PREFIX.'k_quest_config';
$g5['k_quest_inven_table'] = G5_TABLE_PREFIX.'k_quest_inven';

// 퀘스트 설정 전역 변수
$qu_cf = get_quest_config();


/**
 * 퀘스트 설정 조회 (정적 캐시)
 */
function get_quest_config() {
    global $g5;
    static $config_cache = null;
    
    if ($config_cache !== null) {
        return $config_cache;
    }
    
    $config_cache = sql_fetch("SELECT * FROM {$g5['k_quest_config_table']} LIMIT 1");
    
    // 기본값 설정 (테이블이 없거나 데이터가 없는 경우)
    if (!$config_cache) {
        $config_cache = array(
            'qc_title' => '퀘스트',
            'qc_register_reward_type' => 'money',
            'qc_register_reward_value' => 30,
            'qc_complete_reward_type' => 'money',
            'qc_complete_reward_value' => 5,
            'qc_quarter_receive_max' => 3,
            'qc_quarter_give_max' => 2,
            'qc_member_take_max' => 3,
            'qc_last_reset_date' => null,
            'qc_reset_days' => 7,
            'qc_main_display_count' => 3,
            'qc_member_quest_enabled' => 1,
        );
    }
    
    return $config_cache;
}


/**
 * 멤버 의뢰 등록 보상 지급
 */
function give_quest_register_reward($ch_id) {
    global $qu_cf, $config;
    
    $ch_id = (int)$ch_id;
    $type = $qu_cf['qc_register_reward_type'];
    $value = (int)$qu_cf['qc_register_reward_value'];
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
    $money_name = ses($config, 'cf_money', '화폐', 'raw');
    
    switch ($type) {
        case 'exp':
            insert_exp($ch_id, $value, '멤버 '.$qc_title.' 등록', G5_URL . '/quest');
            return "경험치 {$value}";
        case 'money':
            insert_point($ch_id, $value, '멤버 '.$qc_title.' 등록', G5_URL . '/quest');
            return "{$money_name} {$value}";
        case 'item':
            insert_inventory($ch_id, $value, null, 1);
            $item = get_item($value);
            return $item['it_name'];
        default:
            return '';
    }
}


/**
 * 멤버 의뢰 완료 보상 지급
 */
function give_quest_complete_reward($ch_id) {
    global $qu_cf, $config;
    
    $ch_id = (int)$ch_id;
    $type = ses($qu_cf, 'qc_complete_reward_type', 'money', 'raw');
    $value = ses($qu_cf, 'qc_complete_reward_value', 5, 'int');
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
    $money_name = ses($config, 'cf_money', '화폐', 'raw');
    
    if ($value <= 0) {
        return '';
    }
    
    switch ($type) {
        case 'exp':
            insert_exp($ch_id, $value, '멤버 '.$qc_title.' 완료', G5_URL . '/quest');
            return "경험치 {$value}";
        case 'money':
            insert_point($ch_id, $value, '멤버 '.$qc_title.' 완료', G5_URL . '/quest');
            return "{$money_name} {$value}";
        case 'item':
            insert_inventory($ch_id, $value, null, 1);
            $item = get_item($value);
            return $item['it_name'];
        default:
            return '';
    }
}


function get_quest_list($ch_id, $type = 'now', $type2 = 'all', $limit = 0){
    global $g5, $config;

    $ch_id = (int)$ch_id;
    $limit = (int)$limit;

    // 기본 쿼리: 모든 퀘스트 + 내 참여 정보 + 참여자 수
    $sql = "
        SELECT qu.qu_id AS q_id, qu.*, 
               qh.qh_id, qh.qh_state, qh.qh_starttime, qh.qh_endtime, qh.qh_log,
               (SELECT COUNT(*) FROM {$g5['k_quest_has_table']} 
                WHERE qu_id = qu.qu_id AND qh_state != '실패') AS joined_count
        FROM {$g5['k_quest_table']} qu
        LEFT JOIN {$g5['k_quest_has_table']} qh 
            ON qh.qu_id = qu.qu_id AND qh.ch_id = '{$ch_id}'
    ";

    switch ($type) {
    case 'member':
        $sql .= " WHERE qu.qu_type = 'member' AND qu.qu_state = 'active'";
        break;
    case 'past':
        $sql .= " WHERE qu.qu_state = 'done'
                  ORDER BY FIELD(qu.qu_type, 'main', 'sub', 'member'), qu.qu_id ASC";
        break;
    case 'now':
    default:
        $sql .= " WHERE qu.qu_state = 'active'
                  ORDER BY FIELD(qu.qu_type, 'main', 'sub', 'member'), qu.qu_id ASC";
        break;
    }

    $res = sql_query($sql);
    $quest_list = array();
    
    while ($row = sql_fetch_array($res)) {
        // sort_order 계산 + 모집종료 상태 설정
        $row = _calc_quest_sort_order($row, $ch_id);

        /*
        // 필터링: type2 != 'all'이면 내 퀘스트 제외 (member 타입)
        if ($type2 != 'all' && $row['qu_type'] == 'member' && $row['qu_ch_id'] == $ch_id && !$row['qh_id']) {
            continue;
        }*/
        
        $quest_list[] = $row;
    }

    // 정렬
    if ($type != 'past') {
        usort($quest_list, '_quest_sort_compare');
    }
    
    /*// member 타입에서 랜덤 제한 적용
    if ($type != 'past' && $type2 != 'all') {
        $quest_list = _apply_member_rand_limit($quest_list, 2);
    }*/

    // 결과 제한
    if ($limit > 0) {
        $quest_list = array_slice($quest_list, 0, $limit);
    }

    return $quest_list;
}


function _calc_quest_sort_order($row, $ch_id) {
    $is_member = ($row['qu_type'] == 'member');
    $is_sub = ($row['qu_type'] == 'sub');
    $is_main = ($row['qu_type'] == 'main');
    $is_joined = !empty($row['qh_id']);
    $is_full = ($row['qu_take_max'] > 0 && $row['joined_count'] >= $row['qu_take_max']);
    
    // 내가 참여 중
    if ($is_joined) {
        if ($is_main) {
            $row['sort_order'] = 4;
        } elseif ($is_sub) {
            $row['sort_order'] = 5;
        } else { // member
            $row['sort_order'] = 6;
        }
        return $row;
    }
    
    // 멤버 퀘스트
    if ($is_member) {
        if ($is_full) {
            $row['qh_state'] = '모집종료';
            $row['sort_order'] = 7;
        } else {
            $row['sort_order'] = 3;
        }
        return $row;
    }
    
    // 일반 퀘스트 (미참여)
    if ($is_main) {
        $row['sort_order'] = 1;
    } else { // sub
        $row['sort_order'] = 2;
    }
    return $row;
}


/**
 * 퀘스트 정렬 비교 함수
 */
function _quest_sort_compare($a, $b) {
    $aSort = isset($a['sort_order']) ? (int)$a['sort_order'] : 99;
    $bSort = isset($b['sort_order']) ? (int)$b['sort_order'] : 99;
    if ($aSort == $bSort) return 0;
    return ($aSort < $bSort) ? -1 : 1;
}


/**
 * 참여 가능한 member 퀘스트에 랜덤 제한 적용
 */
function _apply_member_rand_limit($quest_list, $rand_limit) {
    $available = array();
    $others = array();
    
    foreach ($quest_list as $row) {
        // sort_order=1: 참여 가능한 member 퀘스트
        if ($row['sort_order'] == 1) {
            $available[] = $row;
        } else {
            $others[] = $row;
        }
    }
    
    // 랜덤 셔플 후 제한
    if (count($available) > $rand_limit) {
        shuffle($available);
        $available = array_slice($available, 0, $rand_limit);
    }
    
    return array_merge($available, $others);
}


/**
 * 단일 퀘스트 조회 (수행자 목록 포함)
 */
function get_quest($qu_id, $ch_id, $use_cache = true, $with_members = false){
    global $g5;
    static $cache = array();

    $qu_id = (int)$qu_id;
    $ch_id = (int)$ch_id;
    $cache_key = "{$qu_id}_{$ch_id}";

    // 캐시 확인
    if ($use_cache && isset($cache[$cache_key])) {
        $qu = $cache[$cache_key];
        // 수행자 목록이 필요하고 아직 없으면 조회
        if ($with_members && !isset($qu['members'])) {
            $qu['members'] = _get_quest_members($qu_id);
            $cache[$cache_key] = $qu;
        }
        return $qu;
    }

    $qu = sql_fetch("
        SELECT qu.qu_id AS q_id, qu.*, qh.*,
               (SELECT COUNT(*) FROM {$g5['k_quest_has_table']} 
                WHERE qu_id = qu.qu_id AND qh_state != '실패') AS joined_count
        FROM {$g5['k_quest_table']} qu 
        LEFT JOIN {$g5['k_quest_has_table']} qh 
            ON qh.qu_id = qu.qu_id AND qh.ch_id = '{$ch_id}'
        WHERE qu.qu_id = '{$qu_id}'
    ");

    // 수행자 목록 포함
    if ($with_members && $qu) {
        $qu['members'] = _get_quest_members($qu_id);
    }

    // 캐시 저장
    if ($use_cache) {
        $cache[$cache_key] = $qu;
    }

    return $qu;
}


/**
 * 퀘스트 수행자 목록 조회 (내부 함수)
 */
function _get_quest_members($qu_id) {
    global $g5;
    
    $qu_id = (int)$qu_id;
    $members = array('ing' => array(), 'done' => array());
    
    $res = sql_query("
        SELECT qh.qh_state, qh.qh_log, ch.ch_name 
        FROM {$g5['k_quest_has_table']} qh
        INNER JOIN {$g5['character_table']} ch ON ch.ch_id = qh.ch_id
        WHERE qh.qu_id = '{$qu_id}' 
          AND qh.qh_state IN ('수행중', '완료')
    ");
    
    while ($row = sql_fetch_array($res)) {
        if ($row['qh_state'] == '수행중') {
            $members['ing'][] = $row;
        } else {
            $members['done'][] = $row;
        }
    }
    
    return $members;
}


function get_has_quest($ch_id, $use_cache = true){
    global $g5;
    static $cache = array();

    $ch_id = (int)$ch_id;

    if ($use_cache && isset($cache[$ch_id])) {
        return $cache[$ch_id];
    }

    $qh_list = array();
    $res = sql_query("
        SELECT qh.*, qu.* 
        FROM {$g5['k_quest_has_table']} qh
        INNER JOIN {$g5['k_quest_table']} qu ON qu.qu_id = qh.qu_id
        WHERE qh.ch_id = '{$ch_id}' 
          AND qh.qh_state = '수행중' 
          AND qu.qu_submit_type = 'mmb'
    ");
    while ($row = sql_fetch_array($res)) {
        $qh_list[] = $row;
    }

    if ($use_cache) {
        $cache[$ch_id] = $qh_list;
    }
    
    return $qh_list;
}

function insert_quest($qu_id, $ch_id){
    global $g5, $qu_cf;

    $qu_id = (int)$qu_id;
    $ch_id = (int)$ch_id;
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');

    $qu = get_quest($qu_id, $ch_id);
    $ch = get_character($ch_id);

    if (!$qu['q_id'] || !$ch['ch_id']) {
        return "잘못된 정보입니다.";
    }
    if ($qu['qh_id']) {
        return "이미 수행한 {$qc_title}입니다.";
    }

    $qu_take = sql_fetch("SELECT count(*) as cnt 
                          FROM {$g5['k_quest_has_table']} 
                          WHERE qu_id = '{$qu['qu_id']}' AND qh_state != '실패'");
    
    if ($qu['qu_take_max'] > 0 && ($qu['qu_take_max'] <= $qu_take['cnt'])) {
        return "수행 가능한 인원이 모두 찼습니다.";
    }

    if ($qu['qu_type'] == 'member') {
        $max_receive = (int)$qu_cf['qc_quarter_receive_max'];
        if ($ch['ch_receive_quest'] >= $max_receive) {
            return "이번 분기의 멤버 {$qc_title}을(를) 모두 수행했습니다.";
        } else {
            sql_query("UPDATE {$g5['character_table']} 
                       SET ch_receive_quest = ch_receive_quest + 1 
                       WHERE ch_id = '{$ch_id}'");
        }
    }

    $time = G5_TIME_YMDHIS;
    sql_query("UPDATE {$g5['k_quest_table']} 
               SET qu_take_now = qu_take_now + 1 
               WHERE qu_id = '{$qu_id}'");
    sql_query("INSERT INTO {$g5['k_quest_has_table']} 
               SET qu_id = '{$qu_id}', ch_id = '{$ch_id}', qh_starttime = '{$time}'");

    if ($qu['qu_submit_type']=='admin') {
        return "{$qc_title}을(를) 수행합니다! 이 {$qc_title}는 자동으로 완료됩니다.";
    }elseif ($qu['qu_submit_type']=='direct'){
        return "{$qc_title}을(를) 수행합니다! {$qc_title} 페이지에서 {$qc_title}을(를) 완료할 수 있습니다.";
    } else {
        return "{$qc_title}을(를) 수행합니다! 자비란에 로그를 올려 주세요.";
    }
}


/**
 * 퀘스트 완료 처리 및 보상 지급
 */
function complete_quest($qh_id, $ch_id, $log_link = '') {
    global $g5, $member, $character, $qu_cf;
    
    $qh_id = (int)$qh_id;
    $ch_id = (int)$ch_id;
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
    
    $result = array(
        'success' => false,
        'message' => '',
        'customer_sql' => ''
    );
    
    if (!$qh_id) {
        $result['message'] = '잘못된 접근입니다.';
        return $result;
    }
    
    // 퀘스트 수행 정보 조회
    $qh = sql_fetch("SELECT * 
                     FROM {$g5['k_quest_has_table']} qh 
                     INNER JOIN {$g5['k_quest_table']} qu ON qu.qu_id = qh.qu_id 
                     WHERE qh.qh_id = '{$qh_id}'");
    
    if (!$qh['qh_id']) {
        $result['message'] = $qc_title.' 정보를 찾을 수 없습니다.';
        return $result;
    }
    
    if ($qh['ch_id'] != $ch_id) {
        $result['message'] = '본인의 '.$qc_title.'만 완료할 수 있습니다.';
        return $result;
    }
    
    if ($qh['qh_state'] != '수행중') {
        $result['message'] = '수행중인 '.$qc_title.'가 아닙니다.';
        return $result;
    }
    
    // 아이템 제출 퀘스트인 경우 아이템 보유 체크 및 차감
    if ($qh['qu_complete_type'] == 'item' && $qh['qu_request_item']) {
        $req_item_id = (int)$qh['qu_request_item'];
        $req_item_count = (int)$qh['qu_request_item_count'];
        if ($req_item_count < 1) $req_item_count = 1;
        
        // 보유 수량 확인
        $owned = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['inventory_table']} 
                            WHERE ch_id = '{$ch_id}' AND it_id = '{$req_item_id}'");
        
        if ((int)$owned['cnt'] < $req_item_count) {
            $req_item = get_item($req_item_id);
            $result['message'] = '아이템이 부족합니다. (' . $req_item['it_name'] . ' ' . $owned['cnt'] . '/' . $req_item_count . '개)';
            return $result;
        }
        
        // 아이템 차감 (요구 수량만큼 한번에 삭제)
        sql_query("DELETE FROM {$g5['inventory_table']} 
                   WHERE ch_id = '{$ch_id}' AND it_id = '{$req_item_id}' 
                   ORDER BY se_ch_id ASC 
                   LIMIT {$req_item_count}");
    }
    
    // 로그 링크 이스케이프
    $log_link_safe = sql_escape_string($log_link);
    $qu_action = '[' . $qh['qu_title'] . '] ' . $qc_title . ' 수행';
    
    // 보상 메시지 배열
    $reward_msgs = array();
    
    // 1. 포인트 보상
    if ($qh['qu_money']) {
        insert_point($member['mb_id'], $qh['qu_money'], $qu_action, 'shop', time(), '지급');
        $money_name = ses($config, 'cf_money', '화폐', 'raw');
        $reward_msgs[] = $money_name . ' ' . number_format($qh['qu_money']);
    }
    
    // 2. 경험치 보상
    if ($qh['qu_exp']) {
        insert_exp($ch_id, $qh['qu_exp'], $qu_action, G5_URL . '/quest');
        $reward_msgs[] = '경험치 ' . number_format($qh['qu_exp']);
    }
    
    // 3. 아이템 보상
    if ($qh['it_id']) {
        $it = get_item($qh['it_id']);
        if ($qh['qu_type'] == 'member') {
            // 멤버 의뢰: k_quest_inven에서 아이템 이동
            $move_result = move_quest_item($qh['qu_id'], 'reward', 0, 0);
            if ($move_result['success']) {
                $reward_msgs[] = $it['it_name'];
            }
            
            // 기명인 경우: 의뢰자 정보 추가
            if ($move_result['success'] && $qh['qu_ch_id'] && !$qh['qu_blind']) {
                $re_ch = get_character($qh['qu_ch_id']);
                $qu_end_msg_safe = sql_escape_string($qh['qu_end_msg']);
                
                // 방금 지급된 아이템 업데이트 (qu_id로 찾음)
                sql_query("UPDATE {$g5['inventory_table']} SET 
                            ch_id = '{$re_ch['ch_id']}' 
                            re_ch_id = '{$ch_id}',
                            re_ch_name = '{$character['ch_name']}',
                            se_ch_id = '{$re_ch['ch_id']}',
                            se_ch_name = '{$re_ch['ch_name']}',
                            in_memo = '{$qu_end_msg_safe}',
                            log_link = '{$log_link_safe}'
                          WHERE ch_id = '{$ch_id}' AND it_id = '{$qh['it_id']}' AND qu_id = '{$qh['qu_id']}'
                          ORDER BY in_id DESC LIMIT 1");
            }
        } else {
            // 일반 퀘스트: 새로운 아이템 생성
            sql_query("INSERT INTO {$g5['inventory_table']} SET 
                        ch_id = '{$ch_id}',
                        it_id = '{$it['it_id']}',
                        it_name = '{$it['it_name']}',
                        ch_name = '{$character['ch_name']}'");
            $reward_msgs[] = $it['it_name'];
        }
    }
    
    // 4. 칭호 보상
    if ($qh['ti_id']) {
        $ti_id = $qh['ti_id'];
        $ti = sql_fetch("SELECT ti_id, ti_name FROM {$g5['title_table']} WHERE ti_id = '{$ti_id}'");
        if ($ti['ti_id']) {
            $m_ti = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['title_has_table']} 
                               WHERE ti_id = '{$ti_id}' AND ch_id = '{$ch_id}'");
            if (!$m_ti['cnt']) {
                sql_query("INSERT INTO {$g5['title_has_table']} SET 
                            ch_id = '{$ch_id}',
                            ch_name = '{$character['ch_name']}',
                            ti_id = '{$ti_id}',
                            hi_use = '1'");
                $reward_msgs[] = '칭호: ' . $ti['ti_name'];
            }
        }
    }
    
    // 5. 퀘스트 완료 상태 업데이트
    $time = G5_TIME_YMDHIS;
    sql_query("UPDATE {$g5['k_quest_has_table']} 
               SET qh_state = '완료', qh_endtime = '{$time}', qh_log = '{$log_link_safe}' 
               WHERE qh_id = '{$qh_id}'");
    
    // 6. 멤버 의뢰 추가 처리
    if ($qh['qu_type'] == 'member') {
        // 완료 보상 지급
        $complete_reward = give_quest_complete_reward($ch_id);
        if ($complete_reward) {
            $reward_msgs[] = $complete_reward;
        }
        
        // 모든 인원 완료 시 종료 처리
        $cnt_check = sql_fetch("SELECT qu.qu_take_max, COUNT(qh.qh_id) AS cnt
                                FROM {$g5['k_quest_table']} qu
                                LEFT JOIN {$g5['k_quest_has_table']} qh 
                                    ON qu.qu_id = qh.qu_id AND qh.qh_state = '완료'
                                WHERE qu.qu_id = '{$qh['qu_id']}'
                                GROUP BY qu.qu_id");
        if ($cnt_check['qu_take_max'] <= $cnt_check['cnt']) {
            sql_query("UPDATE {$g5['k_quest_table']} SET qu_state = 'done', event_id = 'end' WHERE qu_id = '{$qh['qu_id']}'");
        }
    }
    
    $result['success'] = true;
    $result['message'] = $qh['qu_end_msg'];
    $result['reward_msg'] = count($reward_msgs) > 0 ? implode(', ', $reward_msgs) : '';
    $result['customer_sql'] = ", qh_id = '{$qh_id}'";
    
    return $result;
}


/**
 * 퀘스트 아이템 이동 함수
  */
function move_quest_item($qu_id, $type, $it_id = 0, $limit = 0) {
    global $g5, $character;
    
    $qu_id = (int)$qu_id;
    $it_id = (int)$it_id;
    $limit = (int)$limit;
    
    $result = array(
        'success' => false,
        'message' => '',
        'count' => 0
    );
    
    if (!$qu_id) {
        $result['message'] = '퀘스트 ID가 필요합니다.';
        return $result;
    }
    
    // 테이블 컬럼 비교 (공통 컬럼 추출)
    $inv_cols = array();
    $quest_inv_cols = array();
    
    // inventory_table 컬럼 조회
    $inv_result = sql_query("SHOW COLUMNS FROM {$g5['inventory_table']}");
    while ($row = sql_fetch_array($inv_result)) {
        $inv_cols[] = $row['Field'];
    }
    
    // k_quest_inven_table 컬럼 조회
    $quest_result = sql_query("SHOW COLUMNS FROM {$g5['k_quest_inven_table']}");
    while ($row = sql_fetch_array($quest_result)) {
        $quest_inv_cols[] = $row['Field'];
    }
    
    // 공통 컬럼 추출
    $common_cols = array_intersect($inv_cols, $quest_inv_cols);
    $common_cols = array_values($common_cols); // 인덱스 재정렬
    $cols_str = '`' . implode('`, `', $common_cols) . '`';
    
    if ($type == 'insert') {
        // inventory → k_quest_inven
        if (!$it_id || $limit <= 0) {
            $result['message'] = '아이템 ID와 개수가 필요합니다.';
            return $result;
        }
        
        // 1. inventory에서 qu_id 업데이트 (퀘스트 미등록 아이템만)
        sql_query("UPDATE {$g5['inventory_table']} 
                   SET qu_id = '{$qu_id}' 
                   WHERE it_id = '{$it_id}' 
                   AND ch_id = '{$character['ch_id']}' 
                   AND se_ch_id = '' 
                   ORDER BY in_id ASC 
                   LIMIT {$limit}");
        
        $updated = mysqli_affected_rows($g5['connect_db']);
        
        if ($updated > 0) {
            // 2. k_quest_inven으로 복사 (공통 컬럼만)
            sql_query("INSERT INTO {$g5['k_quest_inven_table']} ({$cols_str})
                       SELECT {$cols_str} FROM {$g5['inventory_table']} 
                       WHERE qu_id = '{$qu_id}' AND it_id = '{$it_id}'");
            
            // 3. 원본 삭제
            sql_query("DELETE FROM {$g5['inventory_table']} 
                       WHERE qu_id = '{$qu_id}' AND it_id = '{$it_id}'");
            
            $result['success'] = true;
            $result['message'] = "아이템을 퀘스트 보관함으로 이동했습니다. ({$updated}개)";
            $result['count'] = $updated;
        } else {
            $result['message'] = '이동할 아이템이 없습니다.';
        }
        
    } else {
        // k_quest_inven → inventory (return, reward)
        $limit_sql = ($type == 'reward') ? 'LIMIT 1' : '';
        $success_msg = ($type == 'reward') ? '아이템을 보상으로 지급했습니다.' : '아이템을 반환했습니다.';
        $error_msg = ($type == 'reward') ? '지급할 아이템이 없습니다.' : '반환할 아이템이 없습니다.';
        
        // 1. 이동할 in_id 목록 선택
        $in_ids = array();
        $res = sql_query("SELECT in_id FROM {$g5['k_quest_inven_table']} 
                          WHERE qu_id = '{$qu_id}' 
                          ORDER BY in_id ASC {$limit_sql}");
        while ($row = sql_fetch_array($res)) {
            $in_ids[] = $row['in_id'];
        }
        
        if (count($in_ids) > 0) {
            $in_ids_str = implode(',', $in_ids);
            
            // 2. inventory로 복사 (공통 컬럼만)
            sql_query("INSERT INTO {$g5['inventory_table']} ({$cols_str})
                       SELECT {$cols_str} FROM {$g5['k_quest_inven_table']} 
                       WHERE in_id IN ({$in_ids_str})");
            
            // 3. 원본 삭제
            sql_query("DELETE FROM {$g5['k_quest_inven_table']} 
                       WHERE in_id IN ({$in_ids_str})");
            
            $result['success'] = true;
            $result['message'] = "{$success_msg} (" . count($in_ids) . "개)";
            $result['count'] = count($in_ids);
        } else {
            $result['message'] = $error_msg;
        }
    }
    
    return $result;
}

?>