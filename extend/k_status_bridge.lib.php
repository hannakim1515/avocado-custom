<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('k_status_bridge_trim')) {
    function k_status_bridge_trim($value) {
        return trim((string)$value);
    }
}

if (!function_exists('k_status_bridge_ready')) {
    function k_status_bridge_ready() {
        global $g5;

        return !empty($g5['status_config_table'])
            && !empty($g5['status_table'])
            && !empty($g5['k_stat_table'])
            && function_exists('get_k_status')
            && function_exists('get_k_equip_bonus')
            && function_exists('get_k_passive_bonus');
    }
}

if (!function_exists('k_status_bridge_get_avo_stat_list')) {
    function k_status_bridge_get_avo_stat_list() {
        global $g5;

        $list = array();
        $result = sql_query("SELECT st_id, st_name FROM {$g5['status_config_table']} ORDER BY st_order ASC");
        while ($row = sql_fetch_array($result)) {
            $st_id = (int)$row['st_id'];
            if ($st_id <= 0) continue;

            $list[$st_id] = array(
                'st_id' => $st_id,
                'st_name' => k_status_bridge_trim($row['st_name'])
            );
        }

        return $list;
    }
}

if (!function_exists('k_status_bridge_get_k_stat_list')) {
    function k_status_bridge_get_k_stat_list() {
        global $g5;

        $list = array();
        if (empty($g5['k_stat_table'])) return $list;

        $result = sql_query("SELECT sc_id, sc_name FROM {$g5['k_stat_table']} WHERE sc_category = 'stat' ORDER BY sc_id ASC", false);
        if (!$result) return $list;

        while ($row = sql_fetch_array($result)) {
            $sc_id = (int)$row['sc_id'];
            if ($sc_id <= 0) continue;

            $list[$sc_id] = array(
                'sc_id' => $sc_id,
                'sc_name' => k_status_bridge_trim($row['sc_name'])
            );
        }

        return $list;
    }
}

if (!function_exists('k_status_bridge_get_stat_map')) {
    function k_status_bridge_get_stat_map() {
        $map = array();
        $avo_list = k_status_bridge_get_avo_stat_list();
        $k_list = k_status_bridge_get_k_stat_list();

        // 아보카도 st_id와 K 커스텀 sc_id는 직접 키가 다르므로, 관리자가 보는 스탯명을 1차 매칭 기준으로 삼는다.
        $k_name_map = array();
        foreach ($k_list as $k_stat) {
            if ($k_stat['sc_name'] === '') continue;
            $k_name_map[$k_stat['sc_name']] = $k_stat['sc_id'];
        }

        foreach ($avo_list as $st_id => $avo_stat) {
            if ($avo_stat['st_name'] !== '' && isset($k_name_map[$avo_stat['st_name']])) {
                $map[$st_id] = $k_name_map[$avo_stat['st_name']];
            }
        }

        return $map;
    }
}

if (!function_exists('k_status_bridge_get_raw_values')) {
    function k_status_bridge_get_raw_values($ch_id) {
        global $g5;

        $ch_id = (int)$ch_id;
        $values = array();
        if ($ch_id <= 0) return $values;

        $bonus_list = function_exists('get_k_equip_bonus') ? get_k_equip_bonus($ch_id) : array();
        if (!is_array($bonus_list)) $bonus_list = array();

        $avo_list = k_status_bridge_get_avo_stat_list();
        foreach ($avo_list as $st_id => $stat) {
            $row = sql_fetch("SELECT sc_max FROM {$g5['status_table']} WHERE ch_id = '{$ch_id}' AND st_id = '{$st_id}'");
            $base = isset($row['sc_max']) ? (int)$row['sc_max'] : 0;
            $bonus = isset($bonus_list[$st_id]) ? (int)$bonus_list[$st_id] : 0;
            $values[$st_id] = $base + $bonus;
        }

        return $values;
    }
}

if (!function_exists('k_status_bridge_get_k_values')) {
    function k_status_bridge_get_k_values($ch_id) {
        $ch_id = (int)$ch_id;
        $values = array();
        if ($ch_id <= 0 || !function_exists('get_k_status')) return $values;

        $k_list = k_status_bridge_get_k_stat_list();

        // 패시브 보너스는 K 레이드 등록 로직과 동일하게, 먼저 K 커스텀 스탯 기본값을 모두 계산한 뒤 더한다.
        foreach ($k_list as $sc_id => $stat) {
            $values[$sc_id] = (int)get_k_status($ch_id, $sc_id, 'ch');
        }

        foreach ($k_list as $sc_id => $stat) {
            $passive = function_exists('get_k_passive_bonus') ? (int)get_k_passive_bonus($ch_id, $sc_id, $values) : 0;
            $values[$sc_id] = (isset($values[$sc_id]) ? (int)$values[$sc_id] : 0) + $passive;
        }

        return $values;
    }
}

if (!function_exists('k_status_bridge_get_final_values')) {
    function k_status_bridge_get_final_values($ch_id) {
        $ch_id = (int)$ch_id;
        if ($ch_id <= 0 || !k_status_bridge_ready()) return array();

        $raw_values = k_status_bridge_get_raw_values($ch_id);
        $k_values = k_status_bridge_get_k_values($ch_id);
        $map = k_status_bridge_get_stat_map();

        // 던전/기존 플러그인은 st_id_* 컬럼을 기대하므로 반환 키는 항상 아보카도 st_id로 유지한다.
        $final_values = $raw_values;
        foreach ($map as $st_id => $sc_id) {
            if (isset($k_values[$sc_id])) {
                $final_values[$st_id] = (int)$k_values[$sc_id];
            }
        }

        return $final_values;
    }
}

if (!function_exists('k_status_bridge_get_final_value')) {
    function k_status_bridge_get_final_value($ch_id, $st_id, $default = 0) {
        $st_id = (int)$st_id;
        $values = k_status_bridge_get_final_values($ch_id);

        return isset($values[$st_id]) ? (int)$values[$st_id] : (int)$default;
    }
}

if (!function_exists('k_status_bridge_make_dungeon_member_set')) {
    function k_status_bridge_make_dungeon_member_set($ch_id, $with_runtime_columns = true) {
        $values = k_status_bridge_get_final_values($ch_id);
        $set = array();

        foreach ($values as $st_id => $value) {
            $st_id = (int)$st_id;
            $value = (int)$value;
            if ($st_id <= 0) continue;

            // applicate.php의 INSERT/UPDATE 문에 붙일 수 있도록 기존 dungeon_member 컬럼명 규칙을 그대로 만든다.
            $set[] = "`st_id_{$st_id}` = '{$value}'";
            if ($with_runtime_columns) {
                $set[] = "`st_id_{$st_id}_mod` = '0'";
                $set[] = "`st_id_{$st_id}_use` = '0'";
            }
        }

        return implode(",\n", $set);
    }
}

if (!function_exists('k_status_bridge_has_battle_func')) {
    function k_status_bridge_has_battle_func($code_name) {
        global $g5;

        $code_name = trim((string)$code_name);
        if ($code_name === '' || empty($g5['k_stat_table']) || !function_exists('get_k_battle_func') || !k_status_bridge_ready()) {
            return false;
        }

        $code = sql_escape_string($code_name);
        $row = sql_fetch("SELECT sc_id FROM {$g5['k_stat_table']} WHERE sc_category = 'battle' AND sc_name = '{$code}' LIMIT 1", false);

        return !empty($row['sc_id']);
    }
}

if (!function_exists('k_status_bridge_make_battle_unit')) {
    function k_status_bridge_make_battle_unit($ch_id) {
        global $k_stat;

        $ch_id = (int)$ch_id;
        $unit = array();
        if ($ch_id <= 0 || !is_array($k_stat)) return $unit;

        $k_values = k_status_bridge_get_k_values($ch_id);

        // K 전투 공식은 st_1~st_10 형태를 기대하므로 stat_list 순서에 맞춰 임시 유닛 배열을 만든다.
        for ($i = 0; $i < count($k_stat); $i++) {
            if ($k_stat[$i] === '') continue;
            $sc_id = (int)$k_stat[$i];
            $unit['st_'.($i + 1)] = isset($k_values[$sc_id]) ? (int)$k_values[$sc_id] : 0;
        }

        return $unit;
    }
}

if (!function_exists('k_status_bridge_get_battle_point')) {
    function k_status_bridge_get_battle_point($code_name, $ch_id, $target_ch_id = 0, $last_value = 0) {
        if (!k_status_bridge_has_battle_func($code_name)) return null;

        $unit = k_status_bridge_make_battle_unit($ch_id);
        $target = ((int)$target_ch_id > 0) ? k_status_bridge_make_battle_unit($target_ch_id) : array();
        $result = get_k_battle_func($code_name, $unit, $target);
        if (!is_array($result)) return null;

        $value = isset($result['value']) ? (int)$result['value'] : 0;
        $value += (int)$last_value;

        // 기존 1:1 전투 로그가 기대하는 get_status_extra() 반환 형태와 맞춰 준다.
        return array(
            'default' => $value,
            'is_cri' => !empty($result['cri']) ? 1 : 0,
            'cri_value' => 0,
            'value' => $value
        );
    }
}

if (!function_exists('k_status_bridge_get_battle_value')) {
    function k_status_bridge_get_battle_value($code_name, $ch_id, $target_ch_id = 0) {
        $result = k_status_bridge_get_battle_point($code_name, $ch_id, $target_ch_id);

        return is_array($result) ? (int)$result['value'] : null;
    }
}
?>
