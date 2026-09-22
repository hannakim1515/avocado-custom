<?php

/* 기본 */
$g5['k_stat_table']         = G5_TABLE_PREFIX.'k_battle_stat_func';
$g5['k_monster_table']      = G5_TABLE_PREFIX.'k_battle_monster';
$g5['k_raid_list']          = G5_TABLE_PREFIX.'k_raid_list';
/* 스킬 */
$g5['k_skill_table']        = G5_TABLE_PREFIX.'k_battle_skill';
$g5['k_skill_info_table']   = G5_TABLE_PREFIX.'k_battle_skill_info';
$g5['k_ch_skill_table']     = G5_TABLE_PREFIX.'k_battle_skill_ch';
$g5['k_mo_skill_table']     = G5_TABLE_PREFIX.'k_battle_skill_mo';
$g5['k_mo_pattern_table']   = G5_TABLE_PREFIX.'k_battle_pattern_mo';
/* 장비 */
$g5['k_ch_equip_table']     = G5_TABLE_PREFIX.'k_battle_equip_ch';
$g5['k_upgrade_table']      = G5_TABLE_PREFIX.'k_battle_equip_ug';
$g5['k_upgrade_log_table']  = G5_TABLE_PREFIX.'k_battle_equip_ug_log';
/* 레이드 리스트 */

$kb_cf = sql_fetch("SELECT * FROM `{$g5['k_battle_config']}` WHERE cf_id = 1", false);
if (!is_array($kb_cf)) {
    $kb_cf = array();
}

$k_stat = array();
$k_unit_stat = array();

if (!empty($kb_cf['stat_list'])) {
    $k_stat = explode("|", $kb_cf['stat_list']);
    for ($i = 0; $i < count($k_stat); $i++) {
        if ($k_stat[$i] !== '') {
            $k_unit_stat[$k_stat[$i]] = 'st_' . ($i+1);
        }
    }
}

/*
 * K의 stat_list는 더 이상 독립 설정이 아니다. 연결표에 등록된 A 스탯 타입을
 * 레이드 유닛의 st_1~st_10 슬롯에 담기 위한 호환 목록으로 매 요청 동기화한다.
 * extend 로드 순서와 무관하게 여기서는 테이블을 직접 읽는다.
 */
$unified_map_table = G5_TABLE_PREFIX.'unified_combat_stat_map';
$unified_map_exists = sql_fetch("SHOW TABLES LIKE '".sql_escape_string($unified_map_table)."'", false);
if (!empty($unified_map_exists)) {
    $unified_stat_ids = array();
    $unified_map_query = sql_query("SELECT k_sc_id FROM {$unified_map_table} WHERE is_active = 1 ORDER BY map_id ASC", false);
    if ($unified_map_query) while ($unified_map_row = sql_fetch_array($unified_map_query)) {
        $unified_sc_id = (int)$unified_map_row['k_sc_id'];
        if ($unified_sc_id > 0 && !in_array($unified_sc_id, $unified_stat_ids, true)) $unified_stat_ids[] = $unified_sc_id;
    }
    if (count($unified_stat_ids)) {
        $k_stat = $unified_stat_ids;
        $kb_cf['stat_list'] = implode('|', $k_stat);
        $k_unit_stat = array();
        foreach ($k_stat as $unified_index => $unified_sc_id) $k_unit_stat[$unified_sc_id] = 'st_'.($unified_index + 1);
    }
}

/*아이템 카테고리 확장*/
if (!empty($config['cf_item_category']) && !strstr($config['cf_item_category'], '장비(K)')) {
    $config['cf_item_category'] .= "||장비(K)||커스텀장비제작(K)||커스텀장비(K)||스킬지급(K)||스탯증가(K)||HP회복(K)||MP회복(K)";
}
