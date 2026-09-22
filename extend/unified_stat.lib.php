<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * A/K 공통 최종 스탯 계산기
 *
 * 원본 sc_max는 배분 스탯으로 보존한다. 장착 장비와 A 패시브만 여기서 합산한다.
 * K 캐릭터 패시브/커스텀 스탯은 통합 원본이 아니며, 레이드 슬롯은 A 타입 값을
 * 참조한다. 계산 결과는 요청 동안만 캐시하고 레이드 입장 때 battle_unit에
 * 스냅샷한다. 전투 턴은 스탯/장비/스킬 테이블을 다시 읽지 않는다.
 */

function unified_table_exists($table)
{
    static $checked = array();

    $table = (string)$table;
    if (isset($checked[$table])) return $checked[$table];

    $row = sql_fetch("SHOW TABLES LIKE '".sql_escape_string($table)."'", false);
    $checked[$table] = is_array($row) && count($row) > 0;
    return $checked[$table];
}

/* 설치 후 통합본만 덮어써도 설정 테이블을 안전하게 확장하기 위한 확인 함수다. */
function unified_table_column_exists($table, $column)
{
    static $checked = array();
    $key = (string)$table.'|'.(string)$column;
    if (isset($checked[$key])) return $checked[$key];
    if (!unified_table_exists($table)) return $checked[$key] = false;

    $row = sql_fetch("SHOW COLUMNS FROM `{$table}` LIKE '".sql_escape_string($column)."'", false);
    return $checked[$key] = !empty($row['Field']);
}

/* 요청 단위 캐시다. 영구 캐시는 사용하지 않아 장비/스킬 변경이 다음 요청에 즉시 반영된다. */
function &unified_stat_runtime_cache()
{
    if (!isset($GLOBALS['unified_stat_runtime_cache'])) {
        $GLOBALS['unified_stat_runtime_cache'] = array(
            'status_config' => null,
            'base' => array(),
            'equip' => array(),
            'raw' => array(),
            'a_passive' => array(),
            'k_definitions' => null,
            'k_base' => array(),
            'k_derived' => array(),
            'k_passive_rows' => array(),
            'k_passive_bonus' => array(),
            'k_final' => array(),
            'type_map' => array()
        );
    }
    return $GLOBALS['unified_stat_runtime_cache'];
}

/* 같은 요청에서 장비/스킬을 바꾼 직후 재계산해야 하는 확장 코드용 훅이다. */
function unified_stat_clear_character_cache($ch_id)
{
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0) return;

    $cache =& unified_stat_runtime_cache();
    foreach (array('base', 'equip', 'raw', 'a_passive', 'k_base', 'k_derived', 'k_passive_rows', 'k_passive_bonus', 'k_final') as $key) {
        unset($cache[$key][$ch_id]);
    }
}

function unified_status_config_rows()
{
    global $g5;
    $cache =& unified_stat_runtime_cache();
    if ($cache['status_config'] !== null) return $cache['status_config'];

    $cache['status_config'] = array();
    if (empty($g5['status_config_table']) || !unified_table_exists($g5['status_config_table'])) return $cache['status_config'];

    $query = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC, st_id ASC", false);
    if ($query) while ($row = sql_fetch_array($query)) {
        if (!empty($row['st_id'])) $cache['status_config'][(int)$row['st_id']] = $row;
    }
    return $cache['status_config'];
}

/* 체력 원본은 A 스탯 설정의 st_use_hp 한 곳이다. */
function unified_stat_hp_id()
{
    $rows = unified_status_config_rows();
    foreach ($rows as $st_id => $row) {
        if (!empty($row['st_use_hp'])) return (int)$st_id;
    }
    return 0;
}

function unified_stat_hp_value($ch_id, $fallback_st_id = 0)
{
    $st_id = unified_stat_hp_id();
    if ($st_id <= 0) $st_id = (int)$fallback_st_id;
    return $st_id > 0 ? unified_stat_value((int)$ch_id, $st_id) : 0;
}

function unified_stat_base_list($ch_id)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['base'][$ch_id])) return $cache['base'][$ch_id];

    $cache['base'][$ch_id] = array();
    if ($ch_id <= 0 || empty($g5['status_table']) || !unified_table_exists($g5['status_table'])) return $cache['base'][$ch_id];

    $query = sql_query("SELECT st_id, sc_max FROM {$g5['status_table']} WHERE ch_id = '{$ch_id}'", false);
    if ($query) while ($row = sql_fetch_array($query)) {
        $cache['base'][$ch_id][(int)$row['st_id']] = (int)$row['sc_max'];
    }
    return $cache['base'][$ch_id];
}

function unified_stat_base($ch_id, $st_id)
{
    $base = unified_stat_base_list($ch_id);
    $st_id = (int)$st_id;
    return isset($base[$st_id]) ? (int)$base[$st_id] : 0;
}

function unified_stat_equip_bonus_list($ch_id)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['equip'][$ch_id])) return $cache['equip'][$ch_id];

    $cache['equip'][$ch_id] = array();
    $equip_table = isset($g5['k_ch_equip_table']) ? $g5['k_ch_equip_table'] : G5_TABLE_PREFIX.'k_battle_equip_ch';
    if ($ch_id <= 0 || empty($g5['inventory_table']) || !unified_table_exists($equip_table) || !unified_table_exists($g5['inventory_table'])) {
        return $cache['equip'][$ch_id];
    }

    $stat_rows = array_slice(unified_status_config_rows(), 0, 10, true);
    if (!count($stat_rows)) return $cache['equip'][$ch_id];

    $columns = array();
    $index = 0;
    foreach ($stat_rows as $st_id => $row) {
        $index++;
        $columns[] = "COALESCE(SUM(eq.st_{$index}), 0) AS st_{$index}";
    }
    $sum = sql_fetch(
        "SELECT ".implode(', ', $columns)."
         FROM {$equip_table} eq
         INNER JOIN {$g5['inventory_table']} inven ON inven.in_id = eq.in_id
         WHERE inven.ch_id = '{$ch_id}' AND eq.eq_use <> ''",
        false
    );

    $index = 0;
    foreach ($stat_rows as $st_id => $row) {
        $index++;
        $cache['equip'][$ch_id][(int)$st_id] = isset($sum['st_'.$index]) ? (int)$sum['st_'.$index] : 0;
    }
    return $cache['equip'][$ch_id];
}

/* A의 st_id는 기본 스탯 ID, K의 sc_id는 파생 스탯 ID다. 두 ID를 절대로 섞지 않는다. */
function unified_stat_before_passive($ch_id, $st_id)
{
    $ch_id = (int)$ch_id;
    $st_id = (int)$st_id;
    if ($ch_id <= 0 || $st_id <= 0) return 0;

    $bonus = unified_stat_equip_bonus_list($ch_id);
    return unified_stat_base($ch_id, $st_id) + (isset($bonus[$st_id]) ? (int)$bonus[$st_id] : 0);
}

function unified_k_stat_definitions()
{
    global $g5;
    $cache =& unified_stat_runtime_cache();
    if ($cache['k_definitions'] !== null) return $cache['k_definitions'];

    $cache['k_definitions'] = array();
    $table = isset($g5['k_stat_table']) ? $g5['k_stat_table'] : G5_TABLE_PREFIX.'k_battle_stat_func';
    if (!unified_table_exists($table)) return $cache['k_definitions'];

    $query = sql_query("SELECT * FROM {$table} WHERE sc_category = 'stat' ORDER BY sc_id ASC", false);
    if ($query) while ($row = sql_fetch_array($query)) {
        if (!empty($row['sc_id'])) $cache['k_definitions'][(int)$row['sc_id']] = $row;
    }
    return $cache['k_definitions'];
}

function unified_k_stat_definition($k_sc_id)
{
    $definitions = unified_k_stat_definitions();
    $k_sc_id = (int)$k_sc_id;
    return isset($definitions[$k_sc_id]) ? $definitions[$k_sc_id] : array();
}

/* UI가 저장하는 stat/cons/math 토큰만 계산한다. DB 수식을 PHP 코드로 실행하지 않는다. */
function unified_k_stat_formula_value($k_sc_id, $base_stats)
{
    $definition = unified_k_stat_definition($k_sc_id);
    if (empty($definition['sc_id']) || !is_array($base_stats)) return 0;

    $values = explode('|', (string)$definition['sc_value']);
    $types = explode('|', (string)$definition['sc_type']);
    if (count($values) !== count($types) || !count($values)) return 0;

    $output = array();
    $operators = array();
    $priority = array('+' => 1, '-' => 1, '*' => 2, '/' => 2);
    for ($i = 0; $i < count($values); $i++) {
        $type = $types[$i];
        $token = trim($values[$i]);
        if ($type === 'stat') {
            $output[] = isset($base_stats[(int)$token]) ? (float)$base_stats[(int)$token] : 0.0;
        } elseif ($type === 'cons') {
            if (!is_numeric($token)) return 0;
            $output[] = (float)$token;
        } elseif ($type === 'math' && $token === '(') {
            $operators[] = $token;
        } elseif ($type === 'math' && $token === ')') {
            while (!empty($operators) && end($operators) !== '(') $output[] = array_pop($operators);
            if (empty($operators) || array_pop($operators) !== '(') return 0;
        } elseif ($type === 'math' && isset($priority[$token])) {
            while (!empty($operators) && end($operators) !== '(' && $priority[end($operators)] >= $priority[$token]) {
                $output[] = array_pop($operators);
            }
            $operators[] = $token;
        } else {
            return 0;
        }
    }
    while (!empty($operators)) {
        $operator = array_pop($operators);
        if ($operator === '(') return 0;
        $output[] = $operator;
    }

    $stack = array();
    foreach ($output as $token) {
        if (is_float($token) || is_int($token)) {
            $stack[] = (float)$token;
            continue;
        }
        if (count($stack) < 2) return 0;
        $right = array_pop($stack);
        $left = array_pop($stack);
        if ($token === '+') $stack[] = $left + $right;
        elseif ($token === '-') $stack[] = $left - $right;
        elseif ($token === '*') $stack[] = $left * $right;
        elseif ($token === '/') $stack[] = $right == 0.0 ? 0.0 : $left / $right;
        else return 0;
    }
    if (count($stack) !== 1) return 0;

    $result = array_pop($stack);
    if (isset($definition['sc_3']) && $definition['sc_3'] !== '' && $result > (float)$definition['sc_3']) $result = (float)$definition['sc_3'];
    if (isset($definition['sc_1']) && $definition['sc_1'] === 'round') $result = round($result);
    elseif (isset($definition['sc_1']) && $definition['sc_1'] === 'ceil') $result = ceil($result);
    elseif (isset($definition['sc_1']) && $definition['sc_1'] === 'floor') $result = floor($result);
    if (isset($definition['sc_2']) && $definition['sc_2'] !== '' && $result < (float)$definition['sc_2']) $result = (float)$definition['sc_2'];
    return (int)$result;
}

function unified_a_passive_modifiers($ch_id)
{
    global $g5;
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['a_passive'][$ch_id])) return $cache['a_passive'][$ch_id];

    $cache['a_passive'][$ch_id] = array();
    $skill_table = isset($g5['skill_table']) ? $g5['skill_table'] : G5_TABLE_PREFIX.'skill';
    $has_table = isset($g5['skill_has_table']) ? $g5['skill_has_table'] : G5_TABLE_PREFIX.'skill_has';
    $level_table = isset($g5['skill_level_table']) ? $g5['skill_level_table'] : G5_TABLE_PREFIX.'skill_level';
    if ($ch_id <= 0 || !unified_table_exists($skill_table) || !unified_table_exists($has_table) || !unified_table_exists($level_table)) {
        return $cache['a_passive'][$ch_id];
    }

    /* A 패시브만 최종 스탯에 반영한다. K 캐시 스킬은 절대 다시 합산하지 않는다. */
    $query = sql_query(
        "SELECT sk.sk_mod_st_id, sk.sk_mod_type, sl.sl_set_value
         FROM {$has_table} sh
         INNER JOIN {$skill_table} sk ON sk.sk_id = sh.sk_id
         INNER JOIN {$level_table} sl ON sl.sk_id = sk.sk_id AND sl.sl_level = sh.sh_level
         WHERE sh.ch_id = '{$ch_id}'
           AND sh.sh_use = 1
           AND sk.sk_type = '패시브'
           AND sk.sk_function = '스탯강화'
         ORDER BY sk.sk_mod_st_id ASC, sh.sh_id ASC",
        false
    );
    if ($query) while ($row = sql_fetch_array($query)) {
        $st_id = (int)$row['sk_mod_st_id'];
        if ($st_id <= 0) continue;
        if (!isset($cache['a_passive'][$ch_id][$st_id])) $cache['a_passive'][$ch_id][$st_id] = array('+' => 0, '-' => 0, 'x' => array());
        $type = isset($row['sk_mod_type']) ? $row['sk_mod_type'] : '';
        $total = isset($row['sl_set_value']) ? (float)$row['sl_set_value'] : 0;
        if ($type === '+' || $type === '-') $cache['a_passive'][$ch_id][$st_id][$type] += $total;
        elseif ($type === 'x') $cache['a_passive'][$ch_id][$st_id]['x'][] = $total;
    }
    return $cache['a_passive'][$ch_id];
}

function unified_stat_a_passive_value($ch_id, $st_id, $value)
{
    $modifiers = unified_a_passive_modifiers($ch_id);
    $st_id = (int)$st_id;
    if (empty($modifiers[$st_id])) return (int)$value;

    $modifier = $modifiers[$st_id];
    $value = $value + (float)$modifier['+'] - (float)$modifier['-'];
    if (!empty($modifier['x'])) foreach ($modifier['x'] as $multiply) $value *= (float)$multiply;
    return (int)$value;
}

function unified_stat_value($ch_id, $st_id)
{
    $ch_id = (int)$ch_id;
    $st_id = (int)$st_id;
    if ($ch_id <= 0 || $st_id <= 0) return 0;

    $cache =& unified_stat_runtime_cache();
    if (isset($cache['raw'][$ch_id]) && array_key_exists($st_id, $cache['raw'][$ch_id])) return $cache['raw'][$ch_id][$st_id];

    if (!isset($cache['raw'][$ch_id])) $cache['raw'][$ch_id] = array();
    $value = unified_stat_before_passive($ch_id, $st_id);
    $value = unified_stat_a_passive_value($ch_id, $st_id, $value);
    $cache['raw'][$ch_id][$st_id] = max(0, (int)$value);
    return $cache['raw'][$ch_id][$st_id];
}

function unified_k_base_stats_for_character($ch_id)
{
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['k_base'][$ch_id])) return $cache['k_base'][$ch_id];

    $cache['k_base'][$ch_id] = array();
    foreach (unified_status_config_rows() as $row) {
        $id = (int)$row['st_id'];
        $cache['k_base'][$ch_id][$id] = unified_stat_value($ch_id, $id);
    }
    return $cache['k_base'][$ch_id];
}

function unified_k_derived_stats_for_character($ch_id)
{
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['k_derived'][$ch_id])) return $cache['k_derived'][$ch_id];

    $cache['k_derived'][$ch_id] = array();
    $base = unified_k_base_stats_for_character($ch_id);
    foreach (unified_k_stat_definitions() as $k_sc_id => $definition) {
        $cache['k_derived'][$ch_id][(int)$k_sc_id] = unified_k_stat_formula_value((int)$k_sc_id, $base);
    }
    return $cache['k_derived'][$ch_id];
}

function unified_k_passive_skill_rows($ch_id)
{
    $ch_id = (int)$ch_id;
    $cache =& unified_stat_runtime_cache();
    if (isset($cache['k_passive_rows'][$ch_id])) return $cache['k_passive_rows'][$ch_id];

    /*
     * K 패시브는 과거 데이터 호환용 행일 뿐이다. 통합본에서는 A 패시브가 이미
     * unified_stat_value()에 반영되므로 여기서 다시 적용하면 중복이 된다.
     */
    $cache['k_passive_rows'][$ch_id] = array();
    return $cache['k_passive_rows'][$ch_id];
}

function unified_k_passive_bonus($ch_id, $target_k_sc_id, $derived_stats = null)
{
    $ch_id = (int)$ch_id;
    $target_k_sc_id = (int)$target_k_sc_id;
    if ($ch_id <= 0 || $target_k_sc_id <= 0) return 0;

    $cache =& unified_stat_runtime_cache();
    $can_cache = !is_array($derived_stats);
    if ($can_cache && isset($cache['k_passive_bonus'][$ch_id]) && array_key_exists($target_k_sc_id, $cache['k_passive_bonus'][$ch_id])) {
        return $cache['k_passive_bonus'][$ch_id][$target_k_sc_id];
    }
    if (!is_array($derived_stats)) $derived_stats = unified_k_derived_stats_for_character($ch_id);

    $bonus = 0;
    $rows = unified_k_passive_skill_rows($ch_id);
    if (!empty($rows[$target_k_sc_id])) foreach ($rows[$target_k_sc_id] as $row) {
        $source_id = (int)$row['sc_id'];
        $source = isset($derived_stats[$source_id]) ? (int)$derived_stats[$source_id] : 0;
        $amount = isset($row['sk_value']) ? (float)$row['sk_value'] : 0;
        if ($row['bonus_calc'] === 'p') $bonus += (int)($source + $amount);
        elseif ($row['bonus_calc'] === 'm') $bonus += (int)round(($source ? $source : 1) * $amount);
    }

    if ($can_cache) {
        if (!isset($cache['k_passive_bonus'][$ch_id])) $cache['k_passive_bonus'][$ch_id] = array();
        $cache['k_passive_bonus'][$ch_id][$target_k_sc_id] = $bonus;
    }
    return $bonus;
}

function unified_k_stat_value($ch_id, $k_sc_id)
{
    $ch_id = (int)$ch_id;
    $k_sc_id = (int)$k_sc_id;
    if ($ch_id <= 0 || $k_sc_id <= 0 || empty(unified_k_stat_definition($k_sc_id))) return 0;

    $cache =& unified_stat_runtime_cache();
    if (isset($cache['k_final'][$ch_id]) && array_key_exists($k_sc_id, $cache['k_final'][$ch_id])) return $cache['k_final'][$ch_id][$k_sc_id];

    if (!isset($cache['k_final'][$ch_id])) $cache['k_final'][$ch_id] = array();

    /*
     * K 전투 슬롯은 A 스탯 타입 하나를 가리키는 출력 슬롯이다. 매핑된 슬롯은
     * K 수식이 아니라 A의 기본 스탯·장비·A 패시브만 사용한다.
     * 매핑하지 않은 기존 슬롯은 서비스 중단 방지를 위해 과거 수식을 읽되,
     * 통합 설정 화면에서 경고로 보이므로 운영 시에는 모두 매핑해야 한다.
     */
    $status_type = unified_status_type_for_k_stat($k_sc_id);
    if ($status_type !== '') {
        $value = unified_stat_raw_total_by_type($status_type, $ch_id);
    } else {
        $derived = unified_k_derived_stats_for_character($ch_id);
        $value = isset($derived[$k_sc_id]) ? (int)$derived[$k_sc_id] : 0;
    }
    $cache['k_final'][$ch_id][$k_sc_id] = max(0, (int)$value);
    return $cache['k_final'][$ch_id][$k_sc_id];
}

$g5['unified_combat_stat_map_table'] = G5_TABLE_PREFIX.'unified_combat_stat_map';
if (!unified_table_exists($g5['unified_combat_stat_map_table'])) {
    sql_query("CREATE TABLE IF NOT EXISTS `{$g5['unified_combat_stat_map_table']}` (
        `map_id` int(11) NOT NULL AUTO_INCREMENT,
        `status_type` varchar(255) NOT NULL,
        `k_sc_id` int(11) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '1',
        PRIMARY KEY (`map_id`),
        UNIQUE KEY `uq_status_type` (`status_type`),
        KEY `idx_k_sc_id` (`k_sc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8", false);
}

/* A 연동 타입(예: 공격력)을 K 파생 스탯에 연결했을 때만 K 수식을 사용한다. */
function unified_k_stat_id_for_type($status_type)
{
    global $g5;
    $status_type = trim((string)$status_type);
    $cache =& unified_stat_runtime_cache();
    if (array_key_exists($status_type, $cache['type_map'])) return $cache['type_map'][$status_type];

    $cache['type_map'][$status_type] = 0;
    $table = isset($g5['unified_combat_stat_map_table']) ? $g5['unified_combat_stat_map_table'] : G5_TABLE_PREFIX.'unified_combat_stat_map';
    if ($status_type === '' || !unified_table_exists($table)) return 0;

    $row = sql_fetch("SELECT k_sc_id FROM {$table} WHERE status_type = '".sql_escape_string($status_type)."' AND is_active = 1", false);
    $cache['type_map'][$status_type] = isset($row['k_sc_id']) ? (int)$row['k_sc_id'] : 0;
    return $cache['type_map'][$status_type];
}

/* K 전투 슬롯이 참조하는 A 타입. 기존 연결표를 역방향으로도 사용한다. */
function unified_status_type_for_k_stat($k_sc_id)
{
    global $g5;
    static $cache = array();
    $k_sc_id = (int)$k_sc_id;
    if ($k_sc_id <= 0) return '';
    if (isset($cache[$k_sc_id])) return $cache[$k_sc_id];
    $cache[$k_sc_id] = '';

    $table = isset($g5['unified_combat_stat_map_table']) ? $g5['unified_combat_stat_map_table'] : G5_TABLE_PREFIX.'unified_combat_stat_map';
    if (!unified_table_exists($table)) return '';
    $row = sql_fetch("SELECT status_type FROM {$table} WHERE k_sc_id = '{$k_sc_id}' AND is_active = 1 ORDER BY map_id ASC LIMIT 1", false);
    if (!empty($row['status_type'])) $cache[$k_sc_id] = trim($row['status_type']);
    return $cache[$k_sc_id];
}

/* A 스탯 설정만으로 계산하는 타입별 합계다. */
function unified_stat_raw_total_by_type($status_type, $ch_id)
{
    $status_type = trim((string)$status_type);
    $ch_id = (int)$ch_id;
    if ($status_type === '' || $ch_id <= 0 || !function_exists('get_status_type_filed')) return 0;
    $field = get_status_type_filed($status_type);
    if (!preg_match('/^st_type(?:[1-9]|10)$/', $field)) return 0;

    $total = 0;
    foreach (unified_status_config_rows() as $row) {
        if (!empty($row[$field])) $total += unified_stat_value($ch_id, (int)$row['st_id']);
    }
    return max(0, (int)$total);
}

/* DB별 효과 수집과 분리한 공통 최종값 계산. percent는 가산 합계이며 final은
 * 이미 버프된 현재값이 아니라 동일 기준값에만 적용한다. */
function unified_active_effect_value($base, $flat = 0, $percent = 0, $final_multipliers = array())
{
    $value = (float)$base + (float)$flat;
    $value *= 1 + ((float)$percent / 100);
    if (is_array($final_multipliers)) {
        foreach ($final_multipliers as $multiplier) {
            $value *= (float)$multiplier;
        }
    }
    return max(0, (int)round($value));
}

function unified_dungeon_stat_bonuses($ds_id, $ch_id)
{
    global $g5;
    $result = array('flat' => array(), 'percent' => array(), 'final' => array());
    $ds_id = (int)$ds_id;
    $ch_id = (int)$ch_id;
    if ($ds_id <= 0 || $ch_id <= 0 || empty($g5['dungeon_log_table']) || !unified_table_exists($g5['dungeon_log_table'])) return $result;

    /* 한 행동 안에서는 동일 대상의 활성 효과가 바뀌지 않는다. 여러 연동 코드가
     * 같은 스탯을 요청해도 dungeon_log를 다시 읽지 않도록 요청 범위에서만 보관한다. */
    static $cache = array();
    $cache_key = $ds_id.'|'.$ch_id;
    if (isset($cache[$cache_key])) return $cache[$cache_key];

    /* 스탯마다 SUM을 실행하지 않고 현재 효과를 한 번에 읽는다. final은 순서를
     * 보존해 특수 배율만 별도로 적용하고, 일반 percent는 같은 층에서 합산한다. */
    $query = sql_query(
        "SELECT st_id, dl_value, dl_effect_type
         FROM {$g5['dungeon_log_table']}
         WHERE ds_id = '{$ds_id}' AND ch_id = '{$ch_id}'
           AND dl_cate = '효과' AND dl_function = '스탯강화' AND dl_keep_limit > 0
         ORDER BY dl_id ASC",
        false
    );
    if ($query) while ($row = sql_fetch_array($query)) {
        $st_id = (int)$row['st_id'];
        if ($st_id <= 0) continue;
        $type = isset($row['dl_effect_type']) ? $row['dl_effect_type'] : 'flat';
        if ($type === 'percent') {
            $result['percent'][$st_id] = (isset($result['percent'][$st_id]) ? (int)$result['percent'][$st_id] : 0) + (int)$row['dl_value'];
        } elseif ($type === 'final') {
            if (!isset($result['final'][$st_id])) $result['final'][$st_id] = array();
            $result['final'][$st_id][] = (float)$row['dl_value'] / 100;
        } else {
            $result['flat'][$st_id] = (isset($result['flat'][$st_id]) ? (int)$result['flat'][$st_id] : 0) + (int)$row['dl_value'];
        }
    }
    return $cache[$cache_key] = $result;
}

function unified_dungeon_k_stat_value($ds_id, $ch_id, $dm, $k_sc_id)
{
    if (!is_array($dm) || empty($dm['dm_id'])) return 0;

    $buffs = unified_dungeon_stat_bonuses($ds_id, $ch_id);
    $base_stats = array();
    foreach (unified_status_config_rows() as $row) {
        $st_id = (int)$row['st_id'];
        $base = (int)$dm['st_id_'.$st_id] + (int)$dm['st_id_'.$st_id.'_mod'] - (int)$dm['st_id_'.$st_id.'_use'];
        $base_stats[$st_id] = unified_active_effect_value(
            $base,
            isset($buffs['flat'][$st_id]) ? $buffs['flat'][$st_id] : 0,
            isset($buffs['percent'][$st_id]) ? $buffs['percent'][$st_id] : 0,
            isset($buffs['final'][$st_id]) ? $buffs['final'][$st_id] : array()
        );
    }

    /*
     * 통합 슬롯은 K 수식이 아니라 A 타입 그 자체를 표시하는 용도다.
     * 던전에서는 입장 스냅샷과 던전 내 스탯 버프를 합친 값으로 계산해야
     * 레이드·1:1과 같은 원본을 바라본다. 기존의 `unified` 자리표시 K 수식을
     * 재실행하면 결과가 0이 되는 문제가 있었다.
     */
    $status_type = unified_status_type_for_k_stat((int)$k_sc_id);
    if ($status_type !== '' && function_exists('get_status_type_filed')) {
        $field = get_status_type_filed($status_type);
        if (preg_match('/^st_type(?:[1-9]|10)$/', $field)) {
            $total = 0;
            foreach (unified_status_config_rows() as $row) {
                $st_id = (int)$row['st_id'];
                if (!empty($row[$field])) $total += isset($base_stats[$st_id]) ? (int)$base_stats[$st_id] : 0;
            }
            return max(0, (int)$total);
        }
    }

    $derived = array();
    foreach (unified_k_stat_definitions() as $id => $definition) {
        $derived[(int)$id] = unified_k_stat_formula_value((int)$id, $base_stats);
    }

    $k_sc_id = (int)$k_sc_id;
    $value = isset($derived[$k_sc_id]) ? $derived[$k_sc_id] : 0;
    $value += unified_k_passive_bonus($ch_id, $k_sc_id, $derived);
    return max(0, (int)$value);
}

function unified_stat_total_by_type($status_type, $ch_id)
{
    return unified_stat_raw_total_by_type($status_type, $ch_id);
}

/*
 * K의 stat_list/st_1~st_10은 더 이상 별도 스탯 원본이 아니다.
 * A 타입을 레이드 DB 컬럼에 담기 위한 표시 슬롯 목록일 뿐이며, 연결표를
 * 유일한 기준으로 만들어 관리 화면과 전투 입장 경로가 어긋나지 않게 한다.
 */
function unified_k_active_stat_ids()
{
    global $g5, $kb_cf;
    static $loaded = false;
    static $ids = array();
    if ($loaded) return $ids;
    $loaded = true;

    $map_table = isset($g5['unified_combat_stat_map_table']) ? $g5['unified_combat_stat_map_table'] : G5_TABLE_PREFIX.'unified_combat_stat_map';
    if (unified_table_exists($map_table)) {
        $query = sql_query("SELECT k_sc_id FROM {$map_table} WHERE is_active = 1 ORDER BY map_id ASC", false);
        if ($query) while ($row = sql_fetch_array($query)) {
            $id = (int)$row['k_sc_id'];
            if ($id > 0 && !in_array($id, $ids, true)) $ids[] = $id;
        }
    }

    /* 이전 설치본에서 연결표가 아직 없을 때만 기존 K 슬롯 순서를 보존한다. */
    if (!count($ids) && !empty($kb_cf['stat_list'])) {
        foreach (explode('|', (string)$kb_cf['stat_list']) as $id) {
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $ids, true)) $ids[] = $id;
        }
    }
    return $ids;
}

function unified_k_stat_slot_column($k_sc_id)
{
    $k_sc_id = (int)$k_sc_id;
    if ($k_sc_id <= 0) return '';
    $ids = unified_k_active_stat_ids();
    foreach ($ids as $index => $id) {
        if ((int)$id === $k_sc_id) return 'st_'.($index + 1);
    }
    return '';
}

/* 연결표 변경 직후 다음 요청부터 항상 동일한 슬롯 순서를 쓰도록 호환 캐시도 갱신한다. */
function unified_sync_k_stat_list()
{
    global $g5;
    $ids = unified_k_active_stat_ids();
    $table = isset($g5['k_battle_config']) ? $g5['k_battle_config'] : G5_TABLE_PREFIX.'k_battle_plugin_config';
    if (unified_table_exists($table)) {
        sql_query("UPDATE {$table} SET stat_list = '".sql_escape_string(implode('|', $ids))."'", false);
    }
    return $ids;
}

/* 몬스터 입력의 st_1~st_10 역시 A 기본 스탯의 정렬 순서에 대응한다. */
function unified_monster_base_stats($monster)
{
    global $g5;
    static $cache = array();

    if (!is_array($monster)) {
        $mo_id = (int)$monster;
        if ($mo_id <= 0) return array();
        if (isset($cache[$mo_id])) return $cache[$mo_id];
        $table = isset($g5['k_monster_table']) ? $g5['k_monster_table'] : G5_TABLE_PREFIX.'k_battle_monster';
        if (!unified_table_exists($table)) return array();
        $monster = sql_fetch("SELECT * FROM {$table} WHERE mo_id = '{$mo_id}' LIMIT 1", false);
    }
    if (empty($monster['mo_id'])) return array();

    $mo_id = (int)$monster['mo_id'];
    $values = array();
    $rows = array_slice(unified_status_config_rows(), 0, 10, true);
    $index = 0;
    foreach ($rows as $st_id => $row) {
        $index++;
        $values[(int)$st_id] = isset($monster['st_'.$index]) ? max(0, (int)$monster['st_'.$index]) : 0;
    }
    $cache[$mo_id] = $values;
    return $values;
}

function unified_monster_stat_value($mo_id, $st_id)
{
    $values = unified_monster_base_stats((int)$mo_id);
    $st_id = (int)$st_id;
    return isset($values[$st_id]) ? (int)$values[$st_id] : 0;
}

function unified_monster_total_by_type($mo_id, $status_type)
{
    $status_type = trim((string)$status_type);
    if ($status_type === '' || !function_exists('get_status_type_filed')) return 0;
    $field = get_status_type_filed($status_type);
    if (!preg_match('/^st_type(?:[1-9]|10)$/', $field)) return 0;

    $values = unified_monster_base_stats((int)$mo_id);
    $total = 0;
    foreach (unified_status_config_rows() as $row) {
        $st_id = (int)$row['st_id'];
        if (!empty($row[$field])) $total += isset($values[$st_id]) ? (int)$values[$st_id] : 0;
    }
    return max(0, (int)$total);
}

function unified_monster_hp_value($monster)
{
    if (!is_array($monster)) {
        global $g5;
        $table = isset($g5['k_monster_table']) ? $g5['k_monster_table'] : G5_TABLE_PREFIX.'k_battle_monster';
        $monster = unified_table_exists($table) ? sql_fetch("SELECT * FROM {$table} WHERE mo_id = '".(int)$monster."' LIMIT 1", false) : array();
    }
    if (!is_array($monster)) return 0;

    $hp_id = unified_stat_hp_id();
    $value = $hp_id > 0 ? unified_monster_stat_value((int)$monster['mo_id'], $hp_id) : 0;
    /* 이전 몬스터 데이터에는 스탯 입력란을 채우지 않은 경우가 있어 HP만 안전하게 보존한다. */
    return $value > 0 ? $value : max(0, (int)(isset($monster['mo_hp']) ? $monster['mo_hp'] : 0));
}

function unified_k_stat_value_for_monster($mo_id, $k_sc_id)
{
    $mo_id = (int)$mo_id;
    $k_sc_id = (int)$k_sc_id;
    if ($mo_id <= 0 || $k_sc_id <= 0) return 0;

    $status_type = unified_status_type_for_k_stat($k_sc_id);
    if ($status_type !== '') return unified_monster_total_by_type($mo_id, $status_type);

    return max(0, (int)unified_k_stat_formula_value($k_sc_id, unified_monster_base_stats($mo_id)));
}

function unified_k_stat_value_for_unit($unit_id, $unit_type, $k_sc_id)
{
    if ($unit_type === 'ch') return unified_k_stat_value((int)$unit_id, (int)$k_sc_id);
    if ($unit_type === 'mo') return unified_k_stat_value_for_monster((int)$unit_id, (int)$k_sc_id);
    return 0;
}

/* 일반 공격/치유도 A의 전투 연동 코드 하나를 사용하도록 하는 공통 설정이다. */
$g5['unified_combat_config_table'] = G5_TABLE_PREFIX.'unified_combat_config';
if (!unified_table_exists($g5['unified_combat_config_table'])) {
    sql_query("CREATE TABLE IF NOT EXISTS `{$g5['unified_combat_config_table']}` (
        `uc_id` int(11) NOT NULL AUTO_INCREMENT,
        `basic_atk_code` varchar(255) NOT NULL DEFAULT '',
        `basic_heal_code` varchar(255) NOT NULL DEFAULT '',
        `basic_guard_code` varchar(255) NOT NULL DEFAULT '',
        `speed_status_type` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`uc_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8", false);
}
if (unified_table_exists($g5['unified_combat_config_table']) && !unified_table_column_exists($g5['unified_combat_config_table'], 'basic_guard_code')) {
    sql_query("ALTER TABLE `{$g5['unified_combat_config_table']}` ADD `basic_guard_code` varchar(255) NOT NULL DEFAULT '' AFTER `basic_heal_code`", false);
}

function unified_combat_config()
{
    global $g5;
    static $config = null;
    if ($config !== null) return $config;
    $table = isset($g5['unified_combat_config_table']) ? $g5['unified_combat_config_table'] : G5_TABLE_PREFIX.'unified_combat_config';
    $config = unified_table_exists($table) ? sql_fetch("SELECT * FROM {$table} ORDER BY uc_id ASC LIMIT 1", false) : array();
    if (!is_array($config)) $config = array();
    return $config;
}

function unified_combat_action_code($action)
{
    global $g5;
    $action = in_array($action, array('atk', 'heal', 'guard'), true) ? $action : 'atk';
    $config = unified_combat_config();
    $key = $action === 'heal' ? 'basic_heal_code' : ($action === 'guard' ? 'basic_guard_code' : 'basic_atk_code');
    if (!empty($config[$key])) return trim((string)$config[$key]);

    /* 기존 사이트의 일반적인 코드명을 자동 인식해, 최초 저장 전에도 기본 공격이 0이 되지 않게 한다. */
    $table = isset($g5['status_extra_table']) ? $g5['status_extra_table'] : G5_TABLE_PREFIX.'status_extra';
    if (!unified_table_exists($table)) return '';
    $candidates = $action === 'heal'
        ? array('HEAL', 'heal', '치유', '회복')
        : ($action === 'guard' ? array('GUARD', 'guard', 'DEF', 'def', '방어', '방어력') : array('ATK', 'atk', '공격', '공격력'));
    foreach ($candidates as $candidate) {
        $row = sql_fetch("SELECT ex_name FROM {$table} WHERE ex_name = '".sql_escape_string($candidate)."' LIMIT 1", false);
        if (!empty($row['ex_name'])) return (string)$row['ex_name'];
    }
    return '';
}

function unified_status_extra_value_from_types($code_name, $type_value)
{
    global $g5;
    static $definitions = array();
    $result = array('value' => 0, 'cri' => 0);
    $table = isset($g5['status_extra_table']) ? $g5['status_extra_table'] : G5_TABLE_PREFIX.'status_extra';
    if ($code_name === '' || !unified_table_exists($table) || !is_callable($type_value)) return $result;
    $cache_key = (string)$table.'|'.(string)$code_name;
    if (!array_key_exists($cache_key, $definitions)) {
        $definitions[$cache_key] = sql_fetch("SELECT * FROM {$table} WHERE ex_name = '".sql_escape_string($code_name)."' LIMIT 1", false);
    }
    $ex = $definitions[$cache_key];
    if (empty($ex['ex_id'])) return $result;

    $min = (int)$ex['ex_main_min'];
    $max = (int)$ex['ex_main_max'];
    if ($min > $max) { $swap = $min; $min = $max; $max = $swap; }
    $value = mt_rand($min, $max);
    if (!empty($ex['ex_is_main_status'])) {
        $value += (float)$type_value($ex['ex_main_status_type']) * (float)(!empty($ex['ex_main_status_per']) ? $ex['ex_main_status_per'] : 1);
    }

    $critical = (float)$ex['ex_cri'];
    if (!empty($ex['ex_is_cri_status'])) {
        $critical += (float)$type_value($ex['ex_cri_status_type']) * (float)(!empty($ex['ex_cri_status_per']) ? $ex['ex_cri_status_per'] : 1);
    }
    $is_critical = $critical > 0 && mt_rand(0, 100) <= $critical;
    if ($is_critical) {
        $add = (float)$ex['ex_cri_add_per'];
        if (!empty($ex['ex_is_cri_add_status'])) {
            $add += (float)$type_value($ex['ex_cri_add_status_type']) * (float)(!empty($ex['ex_cri_add_status_per']) ? $ex['ex_cri_add_status_per'] : 1);
        }
        $value += $value * ($add / 100);
    }
    // A 원본과 동일하게 0/빈값은 배율 미사용이다. 기본값 0을 곱해
    // 몬스터의 일반 공격이 항상 0이 되던 문제를 막는다.
    if (!empty($ex['ex_all_per'])) $value *= (float)$ex['ex_all_per'];
    $result['value'] = max(0, (int)$value);
    $result['cri'] = $is_critical ? 1 : 0;
    return $result;
}

/* 레이드에 들어온 유닛은 스냅샷 슬롯을 우선 읽어 전투 중 버프도 반영한다. */
function unified_combat_unit_type_value($unit, $status_type)
{
    if (!is_array($unit)) return 0;
    $status_type = trim((string)$status_type);
    $unit_type = isset($unit['unit_type']) ? $unit['unit_type'] : '';
    $unit_id = isset($unit['unit_id']) ? (int)$unit['unit_id'] : 0;
    if ($status_type === '' || $unit_id <= 0) return 0;

    $k_sc_id = unified_k_stat_id_for_type($status_type);
    $slot = $k_sc_id > 0 ? unified_k_stat_slot_column($k_sc_id) : '';
    if ($slot !== '' && array_key_exists($slot, $unit)) return max(0, (int)$unit[$slot]);

    /* 레이드 중에는 realtime_unit의 슬롯이 유일한 스탯 입력이다. 연결표가 빠진
     * 타입을 A 원본에서 다시 계산하면 입장 시점 스냅샷 정책과 턴당 조회 제한이
     * 깨지므로 0으로 반환해 설정 누락이 즉시 드러나게 한다. */
    if (array_key_exists('rm_id', $unit)) return 0;

    if ($unit_type === 'ch') return unified_stat_raw_total_by_type($status_type, $unit_id);
    if ($unit_type === 'mo') return unified_monster_total_by_type($unit_id, $status_type);
    return 0;
}

function unified_battle_action_value($action, $unit)
{
    $result = array('value' => 0, 'cri' => 0);
    if (!is_array($unit) || empty($unit['unit_type']) || empty($unit['unit_id'])) return $result;
    $code = unified_combat_action_code($action);
    if ($code === '') return $result;

    return unified_status_extra_value_from_types($code, function($type) use ($unit) {
        return unified_combat_unit_type_value($unit, $type);
    });
}
?>
