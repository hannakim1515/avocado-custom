<?php
/*
 * 전투 관련 관리 진입점은 이 메뉴 하나다.
 * 각 링크의 기존 권한 번호는 보존해 기존 관리자 권한 설정을 깨지 않는다.
 */
$menu['menu990'] = array(
    array('990000', 'A/K 통합 전투', G5_ADMIN_URL.'/990_unified_skill_map.php', ''),
    array('990000', '스탯', 'javascript:void(0)', 'section'),
    array('990100', '스탯 및 스킬', G5_ADMIN_URL.'/990_unified_skill_map.php', ''),
    array('990110', '기본 스탯', G5_ADMIN_URL.'/status_list.php', ''),
    array('990120', '전투 연동 코드', G5_ADMIN_URL.'/status_extra_list.php', ''),
    array('990130', '스킬 정의', G5_ADMIN_URL.'/skill_list.php', ''),
    array('990140', '보유·장착 스킬', G5_ADMIN_URL.'/skill_has_list.php', ''),

    array('990000', '지역 관리', 'javascript:void(0)', 'section'),
    array('990210', '지역 정보', G5_ADMIN_URL.'/map_list.php', ''),
    array('990220', '통행 설정', G5_ADMIN_URL.'/map_move_list.php', ''),
    array('990230', '캐릭터 위치 관리', G5_ADMIN_URL.'/map_member_list.php', ''),

    array('990000', '전투 콘텐츠', 'javascript:void(0)', 'section'),
    array('990310', '1:1 배틀 설정', G5_ADMIN_URL.'/battle_config.php', ''),
    array('990320', '1:1 배틀 로그', G5_ADMIN_URL.'/battle_log_list.php', ''),
    array('990330', '던전 관리', G5_ADMIN_URL.'/dungeon_list.php', ''),
    array('990340', '던전 발생 현황', G5_ADMIN_URL.'/dungeon_state_list.php', ''),

    array('990000', '레이드', 'javascript:void(0)', 'section'),
    array('990410', '실시간 레이드 관리', G5_ADMIN_URL.'/982_k_realtime_list.php', ''),
    array('990420', 'MMB 레이드 관리', G5_ADMIN_URL.'/981_k_mmbraid_list.php', ''),
    array('990430', '레이드 입장 페이지', G5_ADMIN_URL.'/981_k_raid_list.php?raid_type=realtime', ''),
    array('990440', '장비 관리', G5_ADMIN_URL.'/980_k_equip_list.php', ''),
    array('990450', '보유 장비 관리', G5_ADMIN_URL.'/980_k_ch_equip.php', ''),
    array('990460', '장비 강화 관리', G5_ADMIN_URL.'/980_k_equip_upgrade.php', ''),
    array('990470', '장비 강화 로그', G5_ADMIN_URL.'/980_k_equip_upgrade_log.php', ''),
    array('990480', '몬스터 관리', G5_ADMIN_URL.'/980_k_monster.php?raid_type=realtime', ''),
    array('990490', '몬스터 스킬', G5_ADMIN_URL.'/980_k_skill.php?unit_type=mo&raid_type=realtime', ''),
    array('990500', '몬스터 보유 스킬', G5_ADMIN_URL.'/980_k_mo_skill.php?raid_type=realtime', ''),
    array('990510', '실시간 레이드 패턴', G5_ADMIN_URL.'/982_k_mo_pattern.php?raid_type=realtime', '')
);
?>
