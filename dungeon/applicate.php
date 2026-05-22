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

		// 최초 스탯 셋팅하기
		$status_result = sql_query("select st_id from {$g5['status_config_table']}");
		$status_sql = "";
		$k_bridge_status = array();

		// K 브릿지가 있으면 장비/패시브가 반영된 K 기준 최종 스탯을 먼저 준비한다.
		if(function_exists('k_status_bridge_get_final_values')) {
			$k_bridge_status = k_status_bridge_get_final_values($character['ch_id']);
			if(!is_array($k_bridge_status)) $k_bridge_status = array();
		}

		for($i=0; $st = sql_fetch_array($status_result); $i++) { 
			$check_firled = sql_query("SHOW COLUMNS FROM {$g5['dungeon_member_table']} LIKE 'st_id_{$st['st_id']}'");
			$check_firled = $check_firled->num_rows > 0 ? true : false;
			if(!$check_firled) {
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}` int(11) NOT NULL DEFAULT '0' ", true);
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}_mod` int(11) NOT NULL DEFAULT '0' ", true);
				sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `st_id_{$st['st_id']}_use` int(11) NOT NULL DEFAULT '0' ", true);
			}

			$add_status = 0;

			// 스탯 정보를 모두 저장합니다.
			// - 1. 스탯을 증가시켜 주는 패시브 스킬 확인
			// - 2. 장착중인 장비의 스탯 확인
			// - 3. 길드 별 스탯 증감치 확인

			// 기본 스탯 수치 정보
			if(isset($k_bridge_status[$st['st_id']])) {
				// 던전 컬럼명은 유지하고, 입장 시 저장되는 기준값만 K 계산 결과로 교체한다.
				$add_status = (int)$k_bridge_status[$st['st_id']];
			} else {
				// K 브릿지를 사용할 수 없을 때는 기존 아보카도 계산식을 유지한다.
				$has = get_status($character['ch_id'], $st['st_id']);
				$has = $has['now'];

			// 스킬 정보
			$add_status = $has;
			$passive_sql = "select SUM(sl.sl_set_value) as total, sk.sk_mod_type from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl
									where		sk.sk_id = sh.sk_id
										and		sh.ch_id = '{$character['ch_id']}'
										and		sh.sh_level = sl.sl_level
										and		sk.sk_id = sl.sk_id
										and		sh.sh_use = '1'
										and		sk.sk_type = '패시브'
										and		sk.sk_function = '스탯강화'
										and		sk.sk_mod_st_id = '{$st['st_id']}'
									group by sk.sk_mod_type";
			$passive_result = sql_query($passive_sql);
			for($j=0; $skill_state = sql_fetch_array($passive_result); $j++) {
				// --- 적용될 스탯 총 합
				switch($skill_state['sk_mod_type']) {
					case "+" : 
						$add_status = $add_status + $skill_state['total'];
					break;
					case "-" : 
						$add_status = $add_status - $skill_state['total'];
					break;
					case "x" : 
						$add_status = $add_status * $skill_state['total'];
					break;
				}
			}

			// --- 던전 버프 적용
			}

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
