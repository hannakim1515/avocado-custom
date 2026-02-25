<?php
/**
 * K-Battle Plugin Migration v2.0.0
 * - 신규 컬럼 추가
 * - 신규 테이블 생성
 */

// 1. k_battle_plugin_config
$config_table = G5_TABLE_PREFIX. 'k_battle_plugin_config';
if (table_exists($config_table)) {
    if (!column_exists($config_table, 'equip_per_hide')) {
        run_migration('equip_per_hide 컬럼 추가', "ALTER TABLE `{$config_table}` ADD `equip_per_hide` int(11) NOT NULL DEFAULT 0");
    }
    if (!column_exists($config_table, 'ver_plugin')) {
        run_migration('ver_plugin 컬럼 추가', "ALTER TABLE `{$config_table}` ADD `ver_plugin` varchar(20) NOT NULL DEFAULT '2.0.0'");
    }
    $col_info = sql_fetch("SHOW COLUMNS FROM `{$config_table}` LIKE 'limit_item'");
    if ($col_info && strpos(strtolower($col_info['Type']), 'varchar') !== false) {
        run_migration('limit_item 타입 수정', "ALTER TABLE `{$config_table}` MODIFY `limit_item` int(11) NOT NULL DEFAULT 0");
    }
}

// 2. k_battle_monster
$monster_table = G5_TABLE_PREFIX. 'k_battle_monster';
if (table_exists($monster_table)) {
    if (!column_exists($monster_table, 'raid_type')) {
        run_migration('monster.raid_type 추가', "ALTER TABLE `{$monster_table}` ADD `raid_type` varchar(50) NOT NULL DEFAULT 'realtime'");
    }
    if (!column_exists($monster_table, 'mo_pattern')) {
        run_migration('mo_pattern 추가', "ALTER TABLE `{$monster_table}` ADD `mo_pattern` int NOT NULL DEFAULT '1'");
    }
    if (!column_exists($monster_table, 'mo_default_act')) {
        run_migration('mo_default_act 추가', "ALTER TABLE `{$monster_table}` ADD `mo_default_act` int NOT NULL DEFAULT '1'");
    }
    if (!index_exists($monster_table, 'idx_raid_type')) {
        run_migration('monster.idx_raid_type 추가', "ALTER TABLE `{$monster_table}` ADD KEY `idx_raid_type` (`raid_type`)");
    }
}

// 3. k_battle_skill
$skill_table = G5_TABLE_PREFIX. 'k_battle_skill';
if (table_exists($skill_table)) {
    if (!column_exists($skill_table, 'unit_type')) {
        run_migration('skill.unit_type 추가', "ALTER TABLE `{$skill_table}` ADD `unit_type` varchar(10) NOT NULL DEFAULT ''");
    }
    if (!column_exists($skill_table, 'raid_type')) {
        run_migration('skill.raid_type 추가', "ALTER TABLE `{$skill_table}` ADD `raid_type` varchar(50) NOT NULL DEFAULT 'realtime'");
    }
    if (!index_exists($skill_table, 'idx_raid_type')) {
        run_migration('skill.idx_raid_type 추가', "ALTER TABLE `{$skill_table}` ADD KEY `idx_raid_type` (`raid_type`)");
    }
}

// 4. k_battle_skill_mo
$skill_mo_table = G5_TABLE_PREFIX. 'k_battle_skill_mo';
if (!table_exists($skill_mo_table)) {
    run_migration('k_battle_skill_mo 생성', "CREATE TABLE IF NOT EXISTS `{$skill_mo_table}` (
        `cs_id` int NOT NULL AUTO_INCREMENT,
        `mo_id` int NOT NULL,
        `sk_id` int NOT NULL,
        `cs_use` int NOT NULL DEFAULT '1',
        `cs_name` varchar(255) NOT NULL,
        `cs_content` text NOT NULL,
        `cs_icon` varchar(255) NOT NULL,
        `cs_img` text NOT NULL,
        `cs_target_cnt` int NOT NULL DEFAULT '1',
        PRIMARY KEY (`cs_id`),
        KEY `idx_mo_id` (`mo_id`),
        KEY `idx_sk_id` (`sk_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
} else {
    if (!column_exists($skill_mo_table, 'cs_target_cnt')) {
        run_migration('skill_mo.cs_target_cnt 추가', "ALTER TABLE `{$skill_mo_table}` ADD `cs_target_cnt` int NOT NULL DEFAULT '1'");
    }
}

// 5. k_battle_pattern_mo
$pattern_mo_table = G5_TABLE_PREFIX. 'k_battle_pattern_mo';
if (!table_exists($pattern_mo_table)) {
    run_migration('k_battle_pattern_mo 생성', "CREATE TABLE IF NOT EXISTS `{$pattern_mo_table}` (
        `pt_id` int NOT NULL AUTO_INCREMENT,
        `mo_id` int NOT NULL DEFAULT '0',
        `pt_turn` int NOT NULL DEFAULT '0',
        `pt_skill` varchar(255) NOT NULL DEFAULT '',
        `pt_skill_cnt` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`pt_id`),
        KEY `idx_mo_id` (`mo_id`),
        KEY `idx_turn` (`pt_turn`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

// 6. k_raid_list
$raid_list_table = G5_TABLE_PREFIX. 'k_raid_list';
if (!table_exists($raid_list_table)) {
    run_migration('k_raid_list 생성', "CREATE TABLE IF NOT EXISTS `{$raid_list_table}` (
        `li_id` INT(11) NOT NULL AUTO_INCREMENT,
        `li_title` VARCHAR(255) NOT NULL DEFAULT '',
        `li_use` TINYINT(1) NOT NULL DEFAULT '1',
        `raid_type` VARCHAR(50) NOT NULL DEFAULT '',
        `ra_ids` TEXT NOT NULL,
        PRIMARY KEY (`li_id`),
        KEY `idx_raid_type` (`raid_type`),
        KEY `idx_li_use` (`li_use`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
}
