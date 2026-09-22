<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/*실시간 레이드 전용*/

$g5['k_realtime_table'] = G5_TABLE_PREFIX.'k_battle_realtime';

/*유닛 데이터*/
function get_k_unit_list_simple($ra_id = 0, $select = '*', $where = '', $order = '', $buff = false)//데이터 갱신용 유닛 리스트
{
    global $g5, $battle_table;

    $result = array();

    $sql = "SELECT {$select} 
              FROM {$battle_table}_unit 
             WHERE ra_id = '{$ra_id}'
             {$where}
             {$order}";
    $list = sql_query($sql);

    for ($i = 0; $row = sql_fetch_array($list); $i++) {
        if ($buff) {
            // 합산값과 상세 목록을 한 번에 조회
            $buff_data = get_k_buff($row['rm_id'], true);
            $layers = ses($buff_data, 'layers', array(), 'array');
            $row['buff_list'] = ses($buff_data, 'list', array('buff' => array(), 'debuff' => array()), 'array');
            $row = apply_k_buff_layers($row, $layers);
        } else {
            $row['buff_list'] = array('buff' => array(), 'debuff' => array());
        }
        $result[] = $row;
    }

    return $result;
}
function get_k_unit_name($rm_id, $unit_type = 'ch')//유닛 이름만 추출
{
    global $g5, $battle_table;

    $rm_id = (int)$rm_id;

    if ($unit_type === 'mo') {
        $origin_table = $g5['k_monster_table'];
        $origin_id    = 'mo_id';
        $select       = "origin.mo_name AS unit_name";
    } else {
        $origin_table = $g5['character_table'];
        $origin_id    = 'ch_id';
        $select       = "origin.ch_name AS unit_name";
    }

    $sql = "SELECT {$select}, unit.rm_id AS rm_id
              FROM {$battle_table}_unit AS unit
              JOIN {$origin_table} AS origin
                ON unit.unit_id = origin.{$origin_id}
             WHERE unit.rm_id = '{$rm_id}'";

    $rm = sql_fetch($sql);

    return ses($rm, 'unit_name', '');
}

/*실시간 헬퍼*/
function get_k_last_log($ra_id = 0, $select = '*', $where = '', $order = '', $buff = false)//최신 로그 갱신
{
    global $g5, $battle_table;


    $log = sql_fetch("SELECT * 
                        FROM {$battle_table}_log 
                       WHERE ra_id = '{$ra_id}' 
                    ORDER BY lo_id DESC 
                       LIMIT 1");

    $result = array(
        'lo_id'      => ses($log, 'lo_id', null),
        'lo_content' => ''
    );

    if ($log && isset($log['lo_content'], $log['lo_1'])) {
        $cls            = h($log['lo_1']);
        $result['lo_content'] = "<li class=\"{$cls}\">{$log['lo_content']}</li>";
    }

    return $result;
}
function k_turn_change_realtime($ra_id, $type='speed')//턴 변경
{
    global $g5, $battle_table;

    $option = '';

    $ra     = k_count_up($ra_id, 'turn');
    $turn   = ses($ra, 'ra_turn', 0);
    $count  = ses($ra, 'ra_count', 0);

    $option = ", lo_2 = '{$turn}', lo_3 = '{$count}'";

    k_turn_change($ra_id, $option, $type);
    
    return true;
}
function k_next_unit_realtime($ra_id, $type = '')//다음 유닛 체크
{
    global $g5, $battle_table, $k_unit_stat, $kb_cf, $ar_title;

    $next  = array('ra_done' => '');

    // 몬스터 생존 여부
    $sum = sql_fetch("SELECT SUM(hp_now) AS sum 
                        FROM {$battle_table}_unit 
                       WHERE hp_now > 0 
                         AND unit_type = 'mo' 
                         AND ra_id = '{$ra_id}'");

    if (empty($sum['sum']) || (int)$sum['sum'] <= 0) {
        $next['ra_done'] = 'ch_win';
        return $next;
    }

    // 캐릭터 생존 여부
    $sum = sql_fetch("SELECT SUM(hp_now) AS sum 
                        FROM {$battle_table}_unit 
                       WHERE hp_now > 0 
                         AND unit_type = 'ch' 
                         AND ra_id = '{$ra_id}'");

    if (empty($sum['sum']) || (int)$sum['sum'] <= 0) {
        $next['ra_done'] = 'mo_win';
        return $next;
    }

    // 다음 턴 유닛 선정
    $type_sql = $type ? "AND unit_type = '{$type}'" : '';
    $speed_slot = function_exists('unified_k_stat_slot_column')
        ? unified_k_stat_slot_column((int)ses($kb_cf, 'speed', 0, 'int'))
        : ses($k_unit_stat, $kb_cf['speed'], '');
    $speed_col = $speed_slot !== '' ? $speed_slot.' desc' : 'unit_type asc';

    $sql = "SELECT rm_id, unit_type 
              FROM {$battle_table}_unit 
             WHERE hp_now > 0
               AND unit_type != 'etc'
               AND ra_id = '{$ra_id}'
               AND is_stun = 0
               AND tt_done = 0
               {$type_sql}
          ORDER BY {$speed_col}
             LIMIT 0, 1";

    $next = sql_fetch($sql);

    if (empty($next['rm_id'])) {
        // 모두 행동을 끝냈다면 턴 증가 후 다시 검색
        k_turn_change_realtime($ra_id);

        $sql = "SELECT rm_id, unit_type 
                  FROM {$battle_table}_unit 
                 WHERE hp_now > 0
                   AND unit_type != 'etc'
                   AND ra_id = '{$ra_id}'
                   AND is_stun = 0
                   AND tt_done = 0
                   {$type_sql}
              ORDER BY {$speed_col}
                 LIMIT 0, 1";

        $next = sql_fetch($sql);
    }

    if (!empty($next['rm_id'])) {
        sql_query("UPDATE {$battle_table} 
                      SET now_turn = '{$next['rm_id']}' 
                    WHERE {$ar_title} = '{$ra_id}'");

        $unit_type = ses($next, 'unit_type', 'ch');
        $next['unit_name'] = get_k_unit_name($next['rm_id'], $unit_type);
        $next['ra_done']   = 'next';
    }

    return $next;
}
function k_count_up($ra_id, $type = 'count')//카운트 올림
{
    global $g5, $battle_table, $ar_title;

    $turn_sql = '';

    if ($type === 'turn') {
        $turn_sql = ", ra_turn = ra_turn + 1";
    }

    $sql = "UPDATE {$battle_table}
               SET ra_count = ra_count + 1
                   {$turn_sql}
             WHERE {$ar_title} = '{$ra_id}'";
    sql_query($sql);

    $ra = sql_fetch("SELECT ra_turn, ra_count, now_turn 
                       FROM {$battle_table} 
                      WHERE {$ar_title} = '{$ra_id}'");

    return $ra ? $ra : array('ra_turn' => 0, 'ra_count' => 0, 'now_turn' => 0);
}

/*제한시간*/
function k_reset_time_limit($ra_id)//제한시간 리셋
{
    global $battle_table, $ar_title;
    
    $current_time = time();
    
    sql_query("UPDATE {$battle_table}
               SET ra_time_start = '{$current_time}'
               WHERE {$ar_title} = '" . sql_escape_string($ra_id) . "'");
    
    return $current_time;
}
function k_get_time_limit_info($ra_id)//제한시간 조회
{
    global $battle_table, $ar_title;
    
    $ra = sql_fetch("SELECT ra_time_limit, ra_time_start, ra_turn_type
                     FROM {$battle_table}
                     WHERE {$ar_title} = '" . sql_escape_string($ra_id) . "'");
    
    if (!$ra) {
        return array('limit' => 0, 'remaining' => 0, 'expired' => false);
    }
    
    $time_limit = (int)$ra['ra_time_limit'];
    $time_start = (int)$ra['ra_time_start'];
    
    if ($time_limit <= 0) {
        return array('limit' => 0, 'remaining' => 0, 'expired' => false);
    }
    
    $elapsed = time() - $time_start;
    $remaining = max(0, $time_limit - $elapsed);
    $expired = ($remaining <= 0);
    
    return array(
        'limit'     => $time_limit,
        'start'     => $time_start,
        'elapsed'   => $elapsed,
        'remaining' => $remaining,
        'expired'   => $expired
    );
}

/*데이터 관리*/
function k_raid_reset($ra_id, $type = 'all')//레이드 초기화
{
    global $g5, $battle_table;
    
    $ra_id = sql_escape_string($ra_id);
    
    if ($ra_id === '' || $type === '' || $battle_table === '') {
        return false;
    }
    
    if ($type === 'log') {
        // 로그만 삭제
        sql_query("DELETE FROM {$battle_table}_log WHERE ra_id = '{$ra_id}'");
        
    } elseif ($type === 'buff') {
        // 버프 삭제 + 스턴/어그로 초기화
        sql_query("DELETE FROM {$battle_table}_buff WHERE ra_id = '{$ra_id}'");
        sql_query("UPDATE {$battle_table}_unit
                      SET is_stun = 0,
                          is_aggr = 0
                    WHERE ra_id = '{$ra_id}'");
        
    } elseif ($type === 'all') {
        // 전체 초기화
        sql_query("DELETE FROM {$battle_table}_log  WHERE ra_id = '{$ra_id}'");
        sql_query("DELETE FROM {$battle_table}_buff WHERE ra_id = '{$ra_id}'");
        
        // 레이드 상태 초기화
        sql_query("UPDATE {$battle_table}
                      SET now_turn     = 0,
                          ra_state     = 0,
                          ra_turn      = 0,
                          ra_count     = 0,
                          ra_system_msg = ''
                    WHERE ra_id = '{$ra_id}'");
        
        // 유닛 상태 초기화 (HP/MP 풀 회복)
        sql_query("UPDATE {$battle_table}_unit
                      SET tt_done = 0,
                          is_stun = 0,
                          is_aggr = 0,
                          hp_now  = hp_max,
                          mp_now  = mp_max
                    WHERE ra_id = '{$ra_id}'");
        
        // 스킬 쿨타임 초기화
        sql_query("UPDATE {$battle_table}_skill
                      SET sk_cool_now = 0
                    WHERE ra_id = '{$ra_id}'");
    }
    
    return true;
}


?>
