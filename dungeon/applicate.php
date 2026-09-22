<?php
error_reporting( E_ALL );
ini_set( "display_errors", 1 );

include_once('./_common.php');

// 게이트 정보 가져오기
$ds = get_dungeon_state($ds_id);
$state = is_able_dungeon($character['ch_id'], $ds_id, false, $ds);

if($state['no_member'] && $state['is_able']) {
	// 입장 정보가 없는 경우
	// 입장 정보 추가하기

	$double_check = sql_fetch("select * from {$g5['dungeon_member_table']} where ch_id = '{$character['ch_id']}' and ds_id = '{$ds_id}'");
	if(!$double_check['dm_id']) {
		// A/K 양쪽에서 획득한 연결 스킬의 소유·장착 상태를 맞춘 뒤 스냅샷을 만든다.
		if(function_exists('unified_skill_sync_character')) {
			unified_skill_sync_character($character['ch_id']);
		}

		// 최초 스탯 셋팅하기
		$status_result = sql_query("select st_id from {$g5['status_config_table']}");
		$status_sql = "";

		for($i=0; $st = sql_fetch_array($status_result); $i++) { 
			$check_firled = sql_query("SHOW COLUMNS FROM {$g5['dungeon_member_table']} LIKE 'st_id_{$st['st_id']}'");
			$check_firled = $check_firled->num_rows > 0 ? true : false;
			if(!$check_firled) {
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}` int(11) NOT NULL DEFAULT '0' ", true);
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}_mod` int(11) NOT NULL DEFAULT '0' ", true);
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}_use` int(11) NOT NULL DEFAULT '0' ", true);
			}

			// 던전 입장 시점의 공통 최종 스탯을 저장한다.
			// 장비/패시브 변경은 진행 중인 던전에 소급 적용되지 않는다.
			$add_status = function_exists('unified_stat_value')
				? unified_stat_value($character['ch_id'], $st['st_id'])
				: get_status($character['ch_id'], $st['st_id'])['now'];

			// --- 던전 버프 적용
			if($ds['dg_status'] == $st['st_id']) {
				if($ds['dg_status_type'] == '+') {
					// 단순 포인트 더하기
					$add_status = $add_status + $ds['dg_status_value'];

				} else if($ds['dg_status_type'] == 'x') {
					$add_status = $add_status * $ds['dg_status_value'];
				}
			}

			$status_sql .= ", st_id_{$st['st_id']} = {$add_status} ";
		}

		$app_sql = " insert into {$g5['dungeon_member_table']}
				set ch_id = '{$character['ch_id']}',
					ds_id = '{$ds_id}',
					dm_state = 'S',
					dm_datetime = '".date('Y-m-d')."'
					{$status_sql}";
		sql_query($app_sql);
	}
} else if(!$state['is_able']) {
	alert($state['message']);
}
goto_url("./ground.php?ds_id={$ds_id}");



?>
