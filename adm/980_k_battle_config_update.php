<?php
$sub_menu = "980010";
include_once('./_common.php');

check_token();

// 값 초기화 (POST에서 안전하게 획득)
$slot            = ses($_POST, 'slot', array(), 'array');
$limit_item      = ses($_POST, 'limit_item', 0, 'int');
$limit_atk       = ses($_POST, 'limit_atk', 0, 'int');
$limit_equip     = ses($_POST, 'limit_equip', 0, 'int');
$limit_heal      = ses($_POST, 'limit_heal', 0, 'int');
$limit_skill     = ses($_POST, 'limit_skill', 0, 'int');
$skill_max       = ses($_POST, 'skill_max', 0, 'int');
$skill_img       = ses($_POST, 'skill_img', 0, 'int');
$equip_type      = ses($_POST, 'equip_type', '', 'string');
$upgrade_limit   = ses($_POST, 'upgrade_limit', 0, 'int');
$equip_per_hide  = ses($_POST, 'equip_per_hide', 0, 'int');
/*
// Pusher 설정
$pusher_app_id   = ses($_POST, 'pusher_app_id', '', 'string');
$pusher_key      = ses($_POST, 'pusher_key', '', 'string');
$pusher_secret   = ses($_POST, 'pusher_secret', '', 'string');
$pusher_cluster  = ses($_POST, 'pusher_cluster', 'ap3', 'string');
*/
$equip_max = implode('|', $slot);
$equip_max = sql_escape_string($equip_max);

// 설정값 갱신
$sql = "
    UPDATE {$g5['k_battle_config']}
    SET limit_item      = '{$limit_item}',
        limit_atk       = '{$limit_atk}',
        limit_equip     = '{$limit_equip}',
        limit_heal      = '{$limit_heal}',
        limit_skill     = '{$limit_skill}',
        skill_max       = '{$skill_max}',
        skill_img       = '{$skill_img}',
        equip_max       = '{$equip_max}',
        equip_type      = '{$equip_type}',
        upgrade_limit   = '{$upgrade_limit}',
        equip_per_hide  = '{$equip_per_hide}'
";
sql_query($sql);

goto_url('./980_k_battle_config.php');
?>
