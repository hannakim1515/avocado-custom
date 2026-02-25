DROP TABLE IF EXISTS `avo_k_battle_realtime`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_realtime` (
  `ra_id` varchar(255) NOT NULL,
  `ra_title` text NOT NULL,
  `ra_content` text NOT NULL,
  `ra_list_img` text NOT NULL,
  `ra_bg_img` text NOT NULL,
  `ra_state` int NOT NULL,
  `ra_limit` int NOT NULL,
  `ra_limit_now` int NOT NULL,
  `ra_reload` varchar(255) NOT NULL,
  `ra_reload_time` int NOT NULL,
  `ra_turn_type` varchar(255) NOT NULL,
  `ra_mo_auto` varchar(50) NOT NULL DEFAULT '',
  `ra_type` varchar(255) NOT NULL,
  `ra_turn` int NOT NULL,
  `ra_count` int NOT NULL,
  `now_turn` int NOT NULL,
  `ra_system` text NOT NULL,
  `ra_time_limit` int NOT NULL DEFAULT '0',
  `ra_time_start` int NOT NULL DEFAULT '0',
  `ra_reward_money` int DEFAULT NULL,
  `ra_reward_exp` int DEFAULT NULL,
  `ra_reward_title` int DEFAULT NULL,
  `ra_reward_item` varchar(50) DEFAULT NULL,
  `ra_bgm` varchar(225) DEFAULT NULL,
  `ra_bgm_volume` int DEFAULT '30',
  `ra_bgm_type` varchar(50) DEFAULT 'single',
  `ra_system_msg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ra_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_realtime_buff`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_realtime_buff` (
  `bf_id` int NOT NULL AUTO_INCREMENT,
  `rm_id` int NOT NULL DEFAULT '0',
  `si_code` varchar(50) NOT NULL DEFAULT '0',
  `bf_value` int NOT NULL DEFAULT '0',
  `sc_id` varchar(50) NOT NULL DEFAULT '0',
  `cs_id` int NOT NULL DEFAULT '0',
  `turn_left` int NOT NULL DEFAULT '0',
  `ra_id` varchar(55) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bf_id`),
  KEY `ra_id` (`ra_id`),
  KEY `rm_id` (`rm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_realtime_log`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_realtime_log` (
  `lo_id` int NOT NULL AUTO_INCREMENT,
  `ra_id` varchar(55) NOT NULL DEFAULT '0',
  `lo_content` text NOT NULL,
  `lo_1` varchar(255) NOT NULL DEFAULT '',
  `lo_2` varchar(255) NOT NULL DEFAULT '',
  `lo_3` varchar(255) NOT NULL DEFAULT '',
  `lo_4` varchar(255) NOT NULL DEFAULT '',
  `lo_5` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`lo_id`),
  KEY `ra_id` (`ra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_realtime_skill`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_realtime_skill` (
  `bs_id` int NOT NULL AUTO_INCREMENT,
  `rm_id` int NOT NULL DEFAULT '0',
  `unit_id` int NOT NULL DEFAULT '0',
  `unit_type` varchar(10) NOT NULL DEFAULT '',
  `cs_id` int NOT NULL DEFAULT '0',
  `sk_cool_now` int NOT NULL DEFAULT '0',
  `ra_id` varchar(55) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bs_id`),
  KEY `ra_id` (`ra_id`),
  KEY `rm_id` (`rm_id`),
  KEY `cs_id` (`cs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `avo_k_battle_realtime_unit`;
CREATE TABLE IF NOT EXISTS `avo_k_battle_realtime_unit` (
  `rm_id` int NOT NULL AUTO_INCREMENT,
  `unit_type` varchar(255) NOT NULL,
  `unit_id` int NOT NULL,
  `hp_now` int NOT NULL DEFAULT '0',
  `hp_max` int NOT NULL DEFAULT '0',
  `mp_now` int NOT NULL DEFAULT '0',
  `mp_max` int NOT NULL DEFAULT '0',
  `st_1` int NOT NULL DEFAULT '0',
  `st_2` int NOT NULL DEFAULT '0',
  `st_3` int NOT NULL DEFAULT '0',
  `st_4` int NOT NULL DEFAULT '0',
  `st_5` int NOT NULL DEFAULT '0',
  `st_6` int NOT NULL DEFAULT '0',
  `st_7` int NOT NULL DEFAULT '0',
  `st_8` int NOT NULL DEFAULT '0',
  `st_9` int NOT NULL DEFAULT '0',
  `st_10` int NOT NULL DEFAULT '0',
  `is_stun` int NOT NULL DEFAULT '0',
  `is_aggr` int NOT NULL DEFAULT '0',
  `tt_done` int NOT NULL DEFAULT '0',
  `ra_id` varchar(55) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rm_id`),
  KEY `ra_id` (`ra_id`),
  KEY `unit_id` (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
