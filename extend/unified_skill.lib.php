<?php
if (!defined('_GNUBOARD_')) exit;

/*
 * A 스킬 정의·레벨·장착 상태가 유일한 원본이다.
 * K 테이블은 레이드 실행 호환용 캐시이며, 관리 화면이나 역방향 동기화의 원본이 아니다.
 */
function unified_skill_table_exists($table)
{
    if (function_exists('unified_table_exists')) return unified_table_exists($table);
    static $checked = array();
    $table = (string)$table;
    if (isset($checked[$table])) return $checked[$table];
    $row = sql_fetch("SHOW TABLES LIKE '".sql_escape_string($table)."'", false);
    $checked[$table] = is_array($row) && count($row) > 0;
    return $checked[$table];
}

function unified_skill_column_exists($table, $column)
{
    static $checked = array();
    $key = $table.'.'.$column;
    if (isset($checked[$key])) return $checked[$key];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    $row = sql_fetch("SHOW COLUMNS FROM {$table} LIKE '".sql_escape_string($column)."'", false);
    $checked[$key] = !empty($row['Field']);
    return $checked[$key];
}

function unified_skill_prepare_schema()
{
    global $g5;
    static $prepared = false;
    if ($prepared) return;
    $prepared = true;

    $skill = isset($g5['k_skill_table']) ? $g5['k_skill_table'] : G5_TABLE_PREFIX.'k_battle_skill';
    $has = isset($g5['k_ch_skill_table']) ? $g5['k_ch_skill_table'] : G5_TABLE_PREFIX.'k_battle_skill_ch';
    if (unified_skill_table_exists($skill) && !unified_skill_column_exists($skill, 'unified_a_sk_id')) {
        sql_query("ALTER TABLE {$skill} ADD unified_a_sk_id int(11) NOT NULL DEFAULT '0', ADD KEY idx_unified_a_sk_id (unified_a_sk_id)", false);
    }
    if (unified_skill_table_exists($has) && !unified_skill_column_exists($has, 'unified_a_sh_id')) {
        sql_query("ALTER TABLE {$has} ADD unified_a_sh_id int(11) NOT NULL DEFAULT '0', ADD KEY idx_unified_a_sh_id (unified_a_sh_id)", false);
    }
}

/* 레이드별 스킬값은 입장 때 저장한다. 턴 요청에서 원본 스킬/스탯을 재계산하지 않는다. */
function unified_skill_prepare_battle_snapshot($battle_table)
{
    static $prepared = array();
    $table = (string)$battle_table.'_skill';
    if (isset($prepared[$table])) return;
    $prepared[$table] = true;
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !unified_skill_table_exists($table)) return;

    $columns = array(
        'unified_a_sk_id' => "int(11) NOT NULL DEFAULT '0'",
        'unified_value'   => "float NOT NULL DEFAULT '0'",
        'unified_mp'      => "int(11) NOT NULL DEFAULT '0'",
        'unified_turn'    => "int(11) NOT NULL DEFAULT '0'",
        'unified_cool'    => "int(11) NOT NULL DEFAULT '0'"
    );
    foreach ($columns as $column => $definition) {
        if (!unified_skill_column_exists($table, $column)) {
            sql_query("ALTER TABLE {$table} ADD {$column} {$definition}", false);
        }
    }
}

function unified_skill_raid_code($skill)
{
    $function = isset($skill['sk_function']) ? $skill['sk_function'] : '';
    if ($function === '공격') return 'atk';
    if ($function === '스탯회복') return 'heal';
    if ($function === '도발') return 'aggr';
    if ($function === '회피') return 'unified_evade';
    if ($function === '방어') return 'unified_guard';
    return 'buff';
}

function unified_skill_raid_target($skill)
{
    $target = isset($skill['sk_target']) ? $skill['sk_target'] : '';
    if ($target === '자신') return array('self', 'single');
    if ($target === '아군전체' || $target === '타인전체') return array('ally', 'all');
    if ($target === '아군' || $target === '타인') return array('ally', 'single');
    return array('enemy', 'single');
}

function unified_skill_info_id($code)
{
    global $g5;
    static $ids = array();
    if (isset($ids[$code])) return $ids[$code];
    $table = isset($g5['k_skill_info_table']) ? $g5['k_skill_info_table'] : G5_TABLE_PREFIX.'k_battle_skill_info';
    if (!unified_skill_table_exists($table)) return 0;

    $row = sql_fetch("SELECT si_id FROM {$table} WHERE si_code = '".sql_escape_string($code)."' ORDER BY si_id ASC LIMIT 1", false);
    if (!empty($row['si_id'])) return $ids[$code] = (int)$row['si_id'];
    $definitions = array(
        'unified_evade' => array('회피', 'A 통합 스킬의 회피 효과입니다.'),
        'unified_guard' => array('방어', 'A 통합 스킬의 피해 감소 효과입니다.')
    );
    if (empty($definitions[$code])) return 0;

    sql_query("INSERT INTO {$table}
        SET si_type = '".sql_escape_string($definitions[$code][0])."', si_code = '".sql_escape_string($code)."', si_passive = 0,
            si_category = 990, si_default = 1, si_use = 1, si_1 = 'unified',
            si_info = '".sql_escape_string($definitions[$code][1])."'", false);
    return $ids[$code] = (int)sql_insert_id();
}

function unified_skill_status_type_for_code($code)
{
    global $g5;
    static $cache = array();
    $code = trim((string)$code);
    if ($code === '' || isset($cache[$code])) return isset($cache[$code]) ? $cache[$code] : '';
    $cache[$code] = '';
    $table = isset($g5['status_extra_table']) ? $g5['status_extra_table'] : G5_TABLE_PREFIX.'status_extra';
    if (!unified_skill_table_exists($table)) return '';
    $row = sql_fetch("SELECT ex_main_status_type FROM {$table} WHERE ex_name = '".sql_escape_string($code)."' LIMIT 1", false);
    return $cache[$code] = isset($row['ex_main_status_type']) ? trim($row['ex_main_status_type']) : '';
}

function unified_skill_k_stat_for_basic_stat($st_id)
{
    global $g5, $config;
    $st_id = (int)$st_id;
    if ($st_id <= 0 || empty($g5['status_config_table']) || !unified_skill_table_exists($g5['status_config_table'])) return 0;
    $row = sql_fetch("SELECT * FROM {$g5['status_config_table']} WHERE st_id = '{$st_id}'", false);
    $types = array_values(array_filter(explode('||', isset($config['cf_status_select_type']) ? $config['cf_status_select_type'] : '')));
    for ($i = 1; $i <= 10; $i++) {
        if (!empty($row['st_type'.$i]) && !empty($types[$i - 1]) && function_exists('unified_k_stat_id_for_type')) {
            return (int)unified_k_stat_id_for_type($types[$i - 1]);
        }
    }
    return 0;
}

function unified_skill_k_target_stat($skill)
{
    $function = isset($skill['sk_function']) ? $skill['sk_function'] : '';
    if ($function === '스탯강화') return unified_skill_k_stat_for_basic_stat(isset($skill['sk_mod_st_id']) ? $skill['sk_mod_st_id'] : 0);
    if ($function === '연동코드강화' && function_exists('unified_k_stat_id_for_type')) {
        return (int)unified_k_stat_id_for_type(unified_skill_status_type_for_code(isset($skill['sk_mod_code']) ? $skill['sk_mod_code'] : ''));
    }
    return 0;
}

/* A 정의 하나를 K 실행 호환 레코드 하나로 자동 컴파일한다. */
function unified_skill_compile_definition($a_sk_id, $skill = null)
{
    global $g5;
    unified_skill_prepare_schema();
    $a_sk_id = (int)$a_sk_id;
    $a_table = isset($g5['skill_table']) ? $g5['skill_table'] : G5_TABLE_PREFIX.'skill';
    $k_table = isset($g5['k_skill_table']) ? $g5['k_skill_table'] : G5_TABLE_PREFIX.'k_battle_skill';
    if ($a_sk_id <= 0 || !unified_skill_table_exists($a_table) || !unified_skill_table_exists($k_table)) return 0;
    if (!is_array($skill)) $skill = sql_fetch("SELECT * FROM {$a_table} WHERE sk_id = '{$a_sk_id}'", false);
    if (empty($skill['sk_id'])) return 0;

    $si_id = unified_skill_info_id(unified_skill_raid_code($skill));
    if ($si_id <= 0) return 0;
    list($target, $target_count) = unified_skill_raid_target($skill);
    if (isset($skill['sk_type']) && $skill['sk_type'] === '패시브') {
        /* 패시브는 A 최종 스탯에 이미 반영되어 레이드 유닛에 스냅샷된다. */
        $target = 'passive';
        $target_count = 'single';
    }
    $target_stat = unified_skill_k_target_stat($skill);
    $name = sql_escape_string(isset($skill['sk_name']) ? $skill['sk_name'] : '');
    $content = sql_escape_string(isset($skill['sk_descript']) ? $skill['sk_descript'] : '');
    $icon = sql_escape_string(isset($skill['sk_img']) ? $skill['sk_img'] : '');
    $turn = (int)(isset($skill['sk_keep_limit']) ? $skill['sk_keep_limit'] : 0);
    $cool = (int)(isset($skill['sk_limit']) ? $skill['sk_limit'] : 0);
    $set = "si_id = '{$si_id}', sk_name = '{$name}', sk_content = '{$content}', sk_info = '{$content}',
        sk_value = 0, default_calc = '', bonus_calc = 'p', sk_turn = '{$turn}', sk_cool = '{$cool}',
        sk_target = '{$target}', sk_target_cnt = '{$target_count}', sk_icon = '{$icon}', sk_mp = 0,
        sc_id = 0, target_sc = '{$target_stat}', sk_use = '', unit_type = 'ch', raid_type = 'realtime,mmbraid'";
    $row = sql_fetch("SELECT sk_id FROM {$k_table} WHERE unified_a_sk_id = '{$a_sk_id}' ORDER BY sk_id ASC LIMIT 1", false);
    if (!empty($row['sk_id'])) {
        sql_query("UPDATE {$k_table} SET {$set} WHERE sk_id = '".(int)$row['sk_id']."'", false);
        return (int)$row['sk_id'];
    }
    sql_query("INSERT INTO {$k_table} SET unified_a_sk_id = '{$a_sk_id}', {$set}", false);
    return (int)sql_insert_id();
}

function unified_skill_compile_all()
{
    global $g5;
    $table = isset($g5['skill_table']) ? $g5['skill_table'] : G5_TABLE_PREFIX.'skill';
    if (!unified_skill_table_exists($table)) return 0;
    $count = 0;
    $query = sql_query("SELECT * FROM {$table} ORDER BY sk_id ASC", false);
    if ($query) while ($row = sql_fetch_array($query)) {
        if (unified_skill_compile_definition((int)$row['sk_id'], $row)) $count++;
    }
    return $count;
}

/*
 * 전투에 들어갈 값만 한 번 계산한다. 공격은 A 연동 코드와 레벨식을 이용하므로
 * A에서 설정한 도트 공격도 동일한 값과 지속 턴으로 레이드에 들어간다.
 */
function unified_skill_raid_snapshot($ch_id, $sh_id)
{
    global $g5, $kb_cf;
    $result = array('a_sk_id' => 0, 'value' => 0, 'mp' => 0, 'turn' => 0, 'cool' => 0);
    $ch_id = (int)$ch_id;
    $sh_id = (int)$sh_id;
    if ($ch_id <= 0 || $sh_id <= 0) return $result;
    $has = isset($g5['skill_has_table']) ? $g5['skill_has_table'] : G5_TABLE_PREFIX.'skill_has';
    $skill = isset($g5['skill_table']) ? $g5['skill_table'] : G5_TABLE_PREFIX.'skill';
    $level = isset($g5['skill_level_table']) ? $g5['skill_level_table'] : G5_TABLE_PREFIX.'skill_level';
    if (!unified_skill_table_exists($has) || !unified_skill_table_exists($skill) || !unified_skill_table_exists($level)) return $result;

    $row = sql_fetch("SELECT sh.sh_id, sh.sh_level, sk.*, sl.sl_set_value, sl.sl_use_value
        FROM {$has} sh
        INNER JOIN {$skill} sk ON sk.sk_id = sh.sk_id
        LEFT JOIN {$level} sl ON sl.sk_id = sk.sk_id AND sl.sl_level = sh.sh_level
        WHERE sh.sh_id = '{$sh_id}' AND sh.ch_id = '{$ch_id}' LIMIT 1", false);
    if (empty($row['sk_id'])) return $result;

    $value = 0;
    $function = isset($row['sk_function']) ? $row['sk_function'] : '';
    /*
     * A의 공격·회복·강화·도발은 모두 같은 연동 코드/레벨 계산 방식을 쓴다.
     * 회피만 수치 대신 유지 시간 자체가 효과이므로 연동 코드 계산에서 제외한다.
     */
    if ($function !== '회피' && !empty($row['sk_status_code']) && function_exists('get_status_extra')) {
        $extra = get_status_extra($row['sk_status_code'], $ch_id);
        $value = isset($extra['value']) ? (float)$extra['value'] : 0;
    }
    $level_value = (isset($row['sl_set_value']) && is_numeric($row['sl_set_value'])) ? (float)$row['sl_set_value'] : 0;
    if ($value > 0 && isset($row['sk_value_type']) && $row['sk_value_type'] === 'x') $value *= $level_value;
    else $value += $level_value;

    $mp = 0;
    $resource = isset($row['sk_use_st_id']) ? (int)$row['sk_use_st_id'] : 0;
    $raid_mp = isset($kb_cf['mp']) ? (int)$kb_cf['mp'] : 0;
    if ($resource > 0 && $resource === $raid_mp && isset($row['sl_use_value']) && is_numeric($row['sl_use_value'])) $mp = max(0, (int)$row['sl_use_value']);

    $result['a_sk_id'] = (int)$row['sk_id'];
    $result['value'] = $value;
    $result['mp'] = $mp;
    $result['turn'] = max(0, (int)$row['sk_keep_limit']);
    $result['cool'] = max(0, (int)$row['sk_limit']);
    return $result;
}

/*
 * 레이드 입장 시 실행한다. A 보유목록 1회, K 캐시목록 2회만 읽고 슬롯별 행만 갱신한다.
 * K 화면에서 변경한 값은 A로 되돌아가지 않는다.
 */
function unified_skill_sync_character($ch_id, $force = false, $changed_in = 'a')
{
    global $g5;
    static $done = array();
    $ch_id = (int)$ch_id;
    if ($ch_id <= 0 || (!$force && isset($done[$ch_id]))) return 0;
    $done[$ch_id] = true;
    unified_skill_prepare_schema();

    $a_skill = isset($g5['skill_table']) ? $g5['skill_table'] : G5_TABLE_PREFIX.'skill';
    $a_has = isset($g5['skill_has_table']) ? $g5['skill_has_table'] : G5_TABLE_PREFIX.'skill_has';
    $k_skill = isset($g5['k_skill_table']) ? $g5['k_skill_table'] : G5_TABLE_PREFIX.'k_battle_skill';
    $k_has = isset($g5['k_ch_skill_table']) ? $g5['k_ch_skill_table'] : G5_TABLE_PREFIX.'k_battle_skill_ch';
    if (!unified_skill_table_exists($a_skill) || !unified_skill_table_exists($a_has) || !unified_skill_table_exists($k_skill) || !unified_skill_table_exists($k_has)) return 0;

    $owned = array();
    $query = sql_query("SELECT sh.sh_id, sh.sk_id, sh.sh_use, sk.sk_name, sk.sk_descript, sk.sk_img
        FROM {$a_has} sh INNER JOIN {$a_skill} sk ON sk.sk_id = sh.sk_id
        WHERE sh.ch_id = '{$ch_id}' ORDER BY sh.sh_id ASC", false);
    if ($query) while ($row = sql_fetch_array($query)) $owned[(int)$row['sh_id']] = $row;
    if (!$owned) return 0;

    $a_ids = array();
    $sh_ids = array();
    foreach ($owned as $row) {
        $a_ids[] = (int)$row['sk_id'];
        $sh_ids[] = (int)$row['sh_id'];
    }
    $a_ids = implode(',', array_unique($a_ids));
    $sh_ids = implode(',', array_unique($sh_ids));
    $definitions = array();
    $query = sql_query("SELECT sk_id, unified_a_sk_id FROM {$k_skill} WHERE unified_a_sk_id IN ({$a_ids})", false);
    if ($query) while ($row = sql_fetch_array($query)) $definitions[(int)$row['unified_a_sk_id']] = (int)$row['sk_id'];
    foreach ($owned as $row) {
        $a_id = (int)$row['sk_id'];
        if (empty($definitions[$a_id])) $definitions[$a_id] = unified_skill_compile_definition($a_id);
    }

    $existing = array();
    $query = sql_query("SELECT cs_id, unified_a_sh_id FROM {$k_has} WHERE ch_id = '{$ch_id}' AND unified_a_sh_id IN ({$sh_ids})", false);
    if ($query) while ($row = sql_fetch_array($query)) $existing[(int)$row['unified_a_sh_id']] = (int)$row['cs_id'];

    $changed = 0;
    foreach ($owned as $sh_id => $row) {
        $k_id = isset($definitions[(int)$row['sk_id']]) ? (int)$definitions[(int)$row['sk_id']] : 0;
        if ($k_id <= 0) continue;
        $use = !empty($row['sh_use']) ? 1 : 0;
        $name = sql_escape_string($row['sk_name']);
        $content = sql_escape_string($row['sk_descript']);
        $icon = sql_escape_string($row['sk_img']);
        if (!empty($existing[$sh_id])) {
            sql_query("UPDATE {$k_has} SET sk_id = '{$k_id}', cs_use = '{$use}', cs_name = '{$name}',
                cs_content = '{$content}', cs_icon = '{$icon}', cs_img = '{$icon}'
                WHERE cs_id = '".(int)$existing[$sh_id]."'", false);
        } else {
            sql_query("INSERT INTO {$k_has} SET ch_id = '{$ch_id}', sk_id = '{$k_id}',
                unified_a_sh_id = '".(int)$sh_id."', cs_use = '{$use}', cs_name = '{$name}',
                cs_content = '{$content}', cs_icon = '{$icon}', cs_img = '{$icon}'", false);
        }
        $changed++;
    }
    if ($changed && function_exists('unified_stat_clear_character_cache')) unified_stat_clear_character_cache($ch_id);
    return $changed;
}

unified_skill_prepare_schema();
?>
