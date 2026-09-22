<?php
$menu['menu910'] = array (
	array('910000', '스탯연동 시스템', ''.G5_ADMIN_URL.'/status_extra_list.php', ''),
	array('910100', '연동코드설정', ''.G5_ADMIN_URL.'/status_extra_list.php', 'status_extra'),

	array('910200', '1:1 배틀 설정', ''.G5_ADMIN_URL.'/battle_config.php', 'battle_config'),
	array('910210', '1:1 배틀 로그', ''.G5_ADMIN_URL.'/battle_log_list.php', 'battle_log')
);
unset($menu['menu910']); // 스탯연동 시스템 메뉴 제거: menu990으로 통합


?>
