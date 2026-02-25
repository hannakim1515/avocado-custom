DROP TABLE IF EXISTS `avo_k_battle_plugin_config`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_plugin_config` (
  `cf_id` int(11) NOT NULL AUTO_INCREMENT,
  `stat_list` text NOT NULL,
  `hp` int(11) NOT NULL,
  `hp_name` varchar(11) NOT NULL DEFAULT 'hp',
  `mp` int(11) NOT NULL,
  `mp_name` varchar(11) NOT NULL DEFAULT 'mp',
  `speed` int(11) NOT NULL,
  `skill_max` int(11) NOT NULL DEFAULT 5,
  `equip_max` varchar(50) NOT NULL DEFAULT '5',
  `equip_type` text NOT NULL,
  `sample_target` text NOT NULL,
  `sample_unit` text NOT NULL,
  `limit_atk` int(11) NOT NULL,
  `limit_heal` int(11) NOT NULL,
  `limit_item` int(11) NOT NULL,
  `limit_skill` int(11) NOT NULL,
  `limit_equip` int(11) NOT NULL DEFAULT 1,
  `skill_img` int(11) NOT NULL DEFAULT 1,
  `equip_per_hide` int(11) NOT NULL DEFAULT 0,
  `upgrade_limit` int(11) NOT NULL DEFAULT 0,
  `ver_plugin` varchar(20) NOT NULL DEFAULT '2.0.0',
  PRIMARY KEY (`cf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_equip_ch`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_equip_ch` (
  `eq_id` int(11) NOT NULL AUTO_INCREMENT,
  `it_id` int(11) NOT NULL,
  `in_id` int(11) NOT NULL,
  `eq_img` text NOT NULL,
  `eq_name` text NOT NULL,
  `eq_content` text NOT NULL,
  `eq_memo` text NOT NULL,
  `eq_memo_id` int(11) NOT NULL,
  `eq_use` varchar(55) NOT NULL DEFAULT '',
  `ug_id` int(11) NOT NULL DEFAULT 0,
  `st_1` int(11) NOT NULL DEFAULT 0,
  `st_2` int(11) NOT NULL DEFAULT 0,
  `st_3` int(11) NOT NULL DEFAULT 0,
  `st_4` int(11) NOT NULL DEFAULT 0,
  `st_5` int(11) NOT NULL DEFAULT 0,
  `st_6` int(11) NOT NULL DEFAULT 0,
  `st_7` int(11) NOT NULL DEFAULT 0,
  `st_8` int(11) NOT NULL DEFAULT 0,
  `st_9` int(11) NOT NULL DEFAULT 0,
  `st_10` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`eq_id`),
  KEY (`it_id`),
  KEY (`in_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_equip_ug`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_equip_ug` (
  `ug_id` int(11) NOT NULL AUTO_INCREMENT,
  `ug_name` varchar(255) NOT NULL,
  `ug_per` int(11) NOT NULL,
  `ug_min` int(11) NOT NULL,
  `ug_max` varchar(255) NOT NULL,
  `ch_rank` int(11) NOT NULL,
  `ug_use_it` int(11) NOT NULL,
  `ug_use_money` int(11) NOT NULL,
  `ug_item` int(11) NOT NULL,
  `ug_money` int(11) NOT NULL,
  `ug_color` varchar(50) NOT NULL,
  `ug_tag_color` varchar(50) NOT NULL,
  `ug_bg_color` varchar(50) NOT NULL,
  PRIMARY KEY (`ug_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_equip_ug_log`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_equip_ug_log` (
  `lo_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL,
  `ug_log` varchar(255) NOT NULL,
  `ug_name` varchar(255) NOT NULL,
  `ug_per` varchar(255) NOT NULL,
  `eq_id` int(11) NOT NULL,
  `st_id` int(11) NOT NULL,
  `ug_plus` int(11) NOT NULL,
  `ug_item` int(11) NOT NULL,
  `ug_money` int(11) NOT NULL,
  `ug_datetime` varchar(255) NOT NULL,
  PRIMARY KEY (`lo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_monster`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_monster` (
  `mo_id` int NOT NULL AUTO_INCREMENT,
  `mo_name` varchar(255) NOT NULL,
  `mo_thumb` text NOT NULL,
  `mo_hp` int NOT NULL,
  `mo_mp` int NOT NULL,
  `st_1` int NOT NULL,
  `st_2` int NOT NULL,
  `st_3` int NOT NULL,
  `st_4` int NOT NULL,
  `st_5` int NOT NULL,
  `st_6` int NOT NULL,
  `st_7` int NOT NULL,
  `st_8` int NOT NULL,
  `st_9` int NOT NULL,
  `st_10` int NOT NULL,
  `mo_1` text NOT NULL,
  `raid_type` varchar(50) NOT NULL DEFAULT 'realtime',
  `mo_pattern` int NOT NULL DEFAULT '1',
  `mo_default_act` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`mo_id`),
  KEY `idx_raid_type` (`raid_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_skill`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_skill` (
  `sk_id` int NOT NULL AUTO_INCREMENT,
  `si_id` int NOT NULL,
  `sk_name` varchar(255) NOT NULL,
  `sk_content` text NOT NULL,
  `sk_info` text NOT NULL,
  `sk_value` float(10,2) NOT NULL,
  `default_calc` varchar(255) NOT NULL,
  `bonus_calc` varchar(255) NOT NULL,
  `sk_turn` int NOT NULL,
  `sk_cool` int NOT NULL,
  `sk_target` varchar(255) NOT NULL,
  `sk_target_cnt` varchar(255) NOT NULL,
  `sk_icon` varchar(255) NOT NULL,
  `sk_mp` int NOT NULL,
  `sc_id` int NOT NULL,
  `target_sc` int NOT NULL,
  `sk_use` varchar(255) NOT NULL,
  `unit_type` varchar(10) NOT NULL DEFAULT '',
  `raid_type` varchar(50) NOT NULL DEFAULT 'realtime',
  PRIMARY KEY (`sk_id`),
  KEY `si_id` (`si_id`),
  KEY `idx_raid_type` (`raid_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_skill_mo`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_skill_mo` (
  `cs_id` int NOT NULL AUTO_INCREMENT,
  `mo_id` int NOT NULL,
  `sk_id` int NOT NULL,
  `cs_use` int NOT NULL DEFAULT '1',
  `cs_name` varchar(255) NOT NULL,
  `cs_content` text NOT NULL,
  `cs_icon` varchar(255) NOT NULL,
  `cs_img` text NOT NULL,
  `cs_target_cnt` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`cs_id`) USING BTREE,
  KEY `idx_mo_id` (`mo_id`),
  KEY `idx_sk_id` (`sk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_skill_ch`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_skill_ch` (
  `cs_id` int(11) NOT NULL AUTO_INCREMENT,
  `ch_id` int(11) NOT NULL,
  `sk_id` int(11) NOT NULL,
  `cs_use` int(11) NOT NULL,
  `cs_name` varchar(255) NOT NULL,
  `cs_content` text NOT NULL,
  `cs_icon` varchar(255) NOT NULL,
  `cs_img` text NOT NULL,
  PRIMARY KEY (`cs_id`),
  KEY (`sk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_skill_info`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_skill_info` (
  `si_id` int(11) NOT NULL AUTO_INCREMENT,
  `si_type` varchar(255) NOT NULL,
  `si_code` varchar(255) NOT NULL,
  `si_passive` int(11) NOT NULL DEFAULT 0,
  `si_category` int(11) NOT NULL DEFAULT 0,
  `si_default` int(11) NOT NULL DEFAULT 0,
  `si_use` int(11) NOT NULL DEFAULT 1,
  `si_1` varchar(255) NOT NULL,
  `si_info` text NOT NULL,
  PRIMARY KEY (`si_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_stat_func`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_stat_func` (
  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
  `sc_name` varchar(255) NOT NULL,
  `sc_value` varchar(255) NOT NULL,
  `sc_type` varchar(255) NOT NULL,
  `sc_category` varchar(255) NOT NULL,
  `sc_1` varchar(255) NOT NULL,
  `sc_2` varchar(255) NOT NULL,
  `sc_3` varchar(255) NOT NULL,
  `sc_4` varchar(255) NOT NULL,
  `sc_5` varchar(255) NOT NULL,
  `sc_6` varchar(255) NOT NULL,
  `sc_7` varchar(255) NOT NULL,
  `sc_8` varchar(255) NOT NULL,
  `sc_9` varchar(255) NOT NULL,
  `sc_10` varchar(255) NOT NULL,
  PRIMARY KEY (`sc_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_pattern_mo`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_pattern_mo` (
  `pt_id` int NOT NULL AUTO_INCREMENT,
  `mo_id` int NOT NULL DEFAULT '0',
  `pt_turn` int NOT NULL DEFAULT '0',
  `pt_skill` varchar(255) NOT NULL DEFAULT '',
  `pt_skill_cnt` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`pt_id`),
  KEY `idx_mo_id` (`mo_id`),
  KEY `idx_turn` (`pt_turn`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_raid_list`;
CREATE TABLE IF NOT EXISTS `avo_k_raid_list` (
    `li_id` INT(11) NOT NULL AUTO_INCREMENT,
    `li_title` VARCHAR(255) NOT NULL DEFAULT '',
    `li_use` TINYINT(1) NOT NULL DEFAULT '1',
    `raid_type` VARCHAR(50) NOT NULL DEFAULT '',
    `ra_ids` TEXT NOT NULL,
    PRIMARY KEY (`li_id`),
    KEY `idx_raid_type` (`raid_type`),
    KEY `idx_li_use` (`li_use`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 
INSERT INTO `avo_k_battle_plugin_config` (cf_id) VALUES (1);
INSERT INTO `avo_k_battle_skill_info` (si_type,si_code,si_category,si_1,si_info,si_passive) VALUES
('공격','atk',980,'default','적을 공격할 때 추가 대미지를 입힙니다.<br>지속턴 설정시 출혈 대미지가 들어갑니다.<br>적 단일/전체를 대상으로 선택하여야 합니다.',0),
('치유','heal',980,'default','hp가 0 이상인 아군 또는 자신을 회복합니다.<br>지속턴 설정시 지속회복이 들어갑니다.<br>아군 단일/전체를 대상으로 선택하여야 합니다.',0),
('부활','rev',980,'default','hp가 0인 아군을 회복합니다.<br>지속턴 설정시 지속회복이 들어갑니다.<br>아군 단일/전체를 대상으로 선택하여야 합니다.',0),
('능력증감','buff',980,'target_sc','지정한 턴 동안 아군과 적군의 적용대상스탯에 적용값만큼 수치를 더하거나 빼 줍니다.<br>패시브의 경우 영구히 지속됩니다.<br>본인/아군/적 단일/전체를 대상으로 선택할 수 있습니다.',1),
('도발','aggr',980,'percent','지정한 턴 동안 적의 공격대상을 자기 자신에게로 돌립니다. 전체 대상 공격에는 효과가 없습니다. 무작위 대상 공격의 경우 확정적으로 공격 대상이 됩니다.<br>발동 성공 확률을 지정할 수 있습니다.<br>본인/아군 단일을 대상으로 선택하여야 합니다.',0),
('기절','stun',980,'percent','지정한 턴 동안 적을 공격불능으로 만듭니다.<br>발동 성공 확률을 지정할 수 있습니다.<br>적 단일/전체를 대상으로 선택하여야 합니다.',0);

ALTER TABLE `avo_item` ADD `ug_limit` int(11) NOT NULL default 0;
ALTER TABLE `avo_item` ADD `eq_type` VARCHAR(50) NOT NULL default '';