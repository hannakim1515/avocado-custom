<?php
$sub_menu = '990000';
include_once('./_common.php');

auth_check($auth[$sub_menu], 'w');
check_token();

$action = isset($_POST['action']) ? $_POST['action'] : '';
$stat_map = isset($g5['unified_combat_stat_map_table']) ? $g5['unified_combat_stat_map_table'] : G5_TABLE_PREFIX.'unified_combat_stat_map';

if ($action === 'save_unified_options') {
    $hp_st_id = isset($_POST['hp_st_id']) ? (int)$_POST['hp_st_id'] : 0;
    $mp_st_id = isset($_POST['mp_st_id']) ? (int)$_POST['mp_st_id'] : 0;
    $speed_status_type = isset($_POST['speed_status_type']) ? trim($_POST['speed_status_type']) : '';
    $basic_atk_code = isset($_POST['basic_atk_code']) ? trim($_POST['basic_atk_code']) : '';
    $basic_heal_code = isset($_POST['basic_heal_code']) ? trim($_POST['basic_heal_code']) : '';
    $basic_guard_code = isset($_POST['basic_guard_code']) ? trim($_POST['basic_guard_code']) : '';
    $skill_slot_min = max(0, isset($_POST['skill_slot_min']) ? (int)$_POST['skill_slot_min'] : 0);
    $skill_slot_max = max($skill_slot_min, isset($_POST['skill_slot_max']) ? (int)$_POST['skill_slot_max'] : 0);
    $hp_row = sql_fetch("SELECT st_id FROM {$g5['status_config_table']} WHERE st_id = '{$hp_st_id}' LIMIT 1", false);
    if (empty($hp_row['st_id'])) alert('체력 원본으로 사용할 A 스탯을 선택하세요.');
    if ($mp_st_id > 0) {
        $mp_row = sql_fetch("SELECT st_id FROM {$g5['status_config_table']} WHERE st_id = '{$mp_st_id}' LIMIT 1", false);
        if (empty($mp_row['st_id'])) alert('MP 원본으로 사용할 A 스탯을 선택하세요.');
    }
    $available_types = array_filter(explode('||', isset($config['cf_status_select_type']) ? $config['cf_status_select_type'] : ''));
    if ($speed_status_type !== '' && !in_array($speed_status_type, $available_types, true)) alert('턴 순서 원본은 A 스탯 타입 중에서 선택하세요.');
    $speed_k_sc_id = $speed_status_type !== '' && function_exists('unified_k_stat_id_for_type')
        ? (int)unified_k_stat_id_for_type($speed_status_type) : 0;
    if ($speed_status_type !== '' && $speed_k_sc_id <= 0) alert('턴 순서 원본을 먼저 레이드 표시 슬롯에 연결하세요.');
    foreach (array($basic_atk_code, $basic_heal_code, $basic_guard_code) as $code) {
        if ($code === '') continue;
        $extra = sql_fetch("SELECT ex_id FROM {$g5['status_extra_table']} WHERE ex_name = '".sql_escape_string($code)."' LIMIT 1", false);
        if (empty($extra['ex_id'])) alert('일반 행동 연동 코드는 A 전투 연동 코드에서 선택하세요.');
    }

    sql_query("UPDATE {$g5['status_config_table']} SET st_use_hp = 0", false);
    sql_query("UPDATE {$g5['status_config_table']} SET st_use_hp = 1 WHERE st_id = '{$hp_st_id}'", false);
    sql_query("UPDATE {$g5['config_table']} SET cf_skill_count = '{$skill_slot_min}', cf_skill_count_max = '{$skill_slot_max}'", false);
    $combat_config = isset($g5['unified_combat_config_table']) ? $g5['unified_combat_config_table'] : G5_TABLE_PREFIX.'unified_combat_config';
    $combat_row = sql_fetch("SELECT uc_id FROM {$combat_config} ORDER BY uc_id ASC LIMIT 1", false);
    $combat_set = "basic_atk_code = '".sql_escape_string($basic_atk_code)."', basic_heal_code = '".sql_escape_string($basic_heal_code)."', basic_guard_code = '".sql_escape_string($basic_guard_code)."', speed_status_type = '".sql_escape_string($speed_status_type)."'";
    if (!empty($combat_row['uc_id'])) sql_query("UPDATE {$combat_config} SET {$combat_set} WHERE uc_id = '".(int)$combat_row['uc_id']."'", false);
    else sql_query("INSERT INTO {$combat_config} SET {$combat_set}", false);
    sql_query("UPDATE {$g5['k_battle_config']} SET hp = '{$hp_st_id}', mp = '{$mp_st_id}', speed = '{$speed_k_sc_id}', limit_atk = 0, skill_max = '{$skill_slot_max}'", false);
    if (function_exists('unified_sync_k_stat_list')) unified_sync_k_stat_list();
    alert('공통 전투 자원과 스킬 슬롯을 저장했습니다. 다음 레이드 입장부터 체력이 새 원본으로 스냅샷됩니다.', './990_unified_skill_map.php');
} elseif ($action === 'save_stat_map') {
    $status_type = isset($_POST['status_type']) ? trim($_POST['status_type']) : '';
    $k_sc_id = isset($_POST['k_sc_id']) ? (int)$_POST['k_sc_id'] : 0;
    if ($status_type === '' || $k_sc_id <= 0) alert('레이드 슬롯과 A 스탯 타입을 모두 선택하세요.');
    sql_query("DELETE FROM {$stat_map} WHERE status_type = '".sql_escape_string($status_type)."' OR k_sc_id = '{$k_sc_id}'", false);
    sql_query("INSERT INTO {$stat_map} SET status_type = '".sql_escape_string($status_type)."', k_sc_id = '{$k_sc_id}', is_active = 1", false);
    if (function_exists('unified_sync_k_stat_list')) unified_sync_k_stat_list();
    alert('레이드 슬롯을 공통 A 스탯 타입으로 지정했습니다.', './990_unified_skill_map.php');
} elseif ($action === 'delete_stat_map') {
    $map_id = isset($_POST['map_id']) ? (int)$_POST['map_id'] : 0;
    if ($map_id > 0) sql_query("DELETE FROM {$stat_map} WHERE map_id = '{$map_id}'", false);
    if (function_exists('unified_sync_k_stat_list')) unified_sync_k_stat_list();
    alert('레이드 슬롯 지정을 해제했습니다.', './990_unified_skill_map.php');
} elseif ($action === 'compile' && function_exists('unified_skill_compile_all')) {
    $count = unified_skill_compile_all();
    alert($count.'개의 A 스킬 실행 정의를 레이드용으로 갱신했습니다.', './990_unified_skill_map.php');
}

goto_url('./990_unified_skill_map.php');
?>
