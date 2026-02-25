<?php
$sub_menu = '981010';
include_once './_common.php';

// k_battle_config 에 realtime 설정이 없으면 초기 세팅
if (!isset($kb_cf['realtime'])) {
    // 실시간 레이드 테이블 생성
    sql_query(
        "CREATE TABLE IF NOT EXISTS `{$g5['k_realtime_table']}` (
            `ra_id`          VARCHAR(255) NOT NULL,
            `ra_title`       TEXT         NOT NULL,
            `ra_content`     TEXT         NOT NULL,
            `ra_list_img`    TEXT         NOT NULL,
            `ra_bg_img`      TEXT         NOT NULL,
            `ra_state`       INT(11)      NOT NULL DEFAULT 0,
            `ra_limit`       INT(11)      NOT NULL DEFAULT 4,
            `ra_limit_now`   INT(11)      NOT NULL DEFAULT 0,
            `ra_reload`      VARCHAR(255) NOT NULL DEFAULT 'count',
            `ra_reload_time` INT(11)      NOT NULL DEFAULT 3000,
            `ra_turn_type`   VARCHAR(255) NOT NULL DEFAULT 'speed',
            `ra_mo_auto`     VARCHAR(255) NOT NULL DEFAULT 'free',
            `ra_type`        VARCHAR(255) NOT NULL DEFAULT 'pve',
            `ra_turn`        INT(11)      NOT NULL DEFAULT 1,
            `ra_count`       INT(11)      NOT NULL DEFAULT 0,
            `now_turn`       INT(11)      NOT NULL DEFAULT 0,
            `ra_system`      TEXT         NOT NULL,
            `ra_time_limit`  INT(11)      NOT NULL DEFAULT 0,
            `ra_time_start`  INT(11)      NOT NULL DEFAULT 0,
            PRIMARY KEY (`ra_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        false
    );

    // 설정 테이블에 realtime 컬럼 추가
    sql_query(
        "ALTER TABLE `{$g5['k_battle_config']}` 
            ADD `realtime` INT(11) NOT NULL DEFAULT '1'",
        false
    );

    // 설정 다시 로드
    $kb_cf = sql_fetch("SELECT * FROM {$g5['k_battle_config']}");
}

// 기존 테이블에 누락 컬럼 추가 (업데이트 대응)
$cols_to_check = array(
    'ra_time_limit' => 'INT(11) NOT NULL DEFAULT 0',
    'ra_time_start' => 'INT(11) NOT NULL DEFAULT 0',
    'ra_reward_money' => 'INT(11) NOT NULL DEFAULT 0',
    'ra_reward_exp' => 'INT(11) NOT NULL DEFAULT 0',
    'ra_reward_item' => 'VARCHAR(255) NOT NULL DEFAULT \'\'',
    'ra_reward_title' => 'INT(11) NOT NULL DEFAULT 0'
);
foreach ($cols_to_check as $col => $col_type) {
    $col_chk = sql_fetch("SHOW COLUMNS FROM `{$g5['k_realtime_table']}` LIKE '{$col}'", false);
    if (!$col_chk) {
        sql_query("ALTER TABLE `{$g5['k_realtime_table']}` ADD `{$col}` {$col_type}", false);
    }
}

include_once G5_ADMIN_PATH . '/admin.tail.php';
