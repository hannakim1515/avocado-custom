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

/*아이템 카테고리 확장*/
if (!empty($config['cf_item_category']) && !strstr($config['cf_item_category'], '장비(K)')) {
    $config['cf_item_category'] .= "||장비(K)||커스텀장비제작(K)||커스텀장비(K)||스킬지급(K)||스탯증가(K)||HP회복(K)||MP회복(K)";
}