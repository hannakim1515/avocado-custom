<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['status_extra_table'] = G5_TABLE_PREFIX.'status_extra';


if(!strstr($config['cf_item_category'], '체력회복')) {
	$config['cf_item_category'] .= "||체력회복";
}
if(!strstr($config['cf_item_category'], '사망해제')) {
	$config['cf_item_category'] .= "||사망해제";
}


if(!sql_query(" DESC {$g5['status_extra_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['status_extra_table']}` (
		`ex_id` int(11) NOT NULL AUTO_INCREMENT,
		`ex_name` varchar(255) NOT NULL default '',

		`ex_main_min` int(11) NOT NULL default '0',
		`ex_main_max` int(11) NOT NULL default '0',
		`ex_is_main_status` int(11) NOT NULL default '0',
		`ex_main_status_type` varchar(255) NOT NULL default '',
		`ex_main_status_per` varchar(255) NOT NULL default '',

		`ex_cri` int(11) NOT NULL default '0',
		`ex_is_cri_status` int(11) NOT NULL default '0',
		`ex_cri_status_type` varchar(255) NOT NULL default '',
		`ex_cri_status_per` varchar(255) NOT NULL default '',

		`ex_cri_add_per` varchar(255) NOT NULL default '',
		`ex_is_cri_add_status` int(11) NOT NULL default '0',
		`ex_cri_add_status_type` varchar(255) NOT NULL default '',
		`ex_cri_add_status_per` varchar(255) NOT NULL default '',

		`ex_all_per` varchar(255) NOT NULL default '',

		PRIMARY KEY (`ex_id`)
	) ", false);


	// 관리자에 각 선택항목 텍스트 지정하기
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_status_select_type` text NOT NULL AFTER `cf_10` ");

	// 체력 스탯 값 지정하기
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_use_hp` int(4) NOT NULL DEFAULT '0' AFTER `st_use_max` ");

	// 선택 항목 추가하기 (1~10)
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type10` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type9` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type8` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type7` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type6` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type5` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type4` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type3` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type2` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
	sql_query(" ALTER TABLE `{$g5['status_config_table']}` ADD `st_type1` int(4) NOT NULL DEFAULT '0' AFTER `st_help` ");
}


// 스탯 타입의 필드명을 가져오는 함수
function get_status_type_filed($st_type) {
	global $g5, $config;

	$status_type = explode("||", $config['cf_status_select_type']);
	$status_type = array_filter($status_type);
	$status_type = array_values($status_type);
	$result = array_search($st_type, $status_type);

	return "st_type".($result+1);
}

// 타입별 스탯값 합계 가져오기
function get_status_total($st_type, $ch_id) {
	global $g5;
	$filed = get_status_type_filed($st_type);

	$result = sql_fetch("select sum(sc_max) as total from {$g5['status_config_table']} st, {$g5['status_table']} sc where st.st_id = sc.st_id and sc.ch_id = '{$ch_id}' and st.{$filed} = 1");
	return $result['total'];
}


// 연동코드 결과값 가져오기
// ex) get_status_extra('공격력', $ch_id, 기본스탯에 추가할 값, 최종 마지막에 추가할 값)
// 반환값 ::: $result['default'] : 기본 스탯 수치 / $result['is_cri'] : 크리티컬 여부 / $result['cri_value'] : 추가되는 크리티컬 수치 / $result['value'] : 최종 결과

// 연동 코드에 추가로 옵션을 더하고 싶을 경우 (ex. RANK 별 대미지, 추가 스탯 효과 등) 해당 코드 혹은 get_status_total 함수를 커스텀 할 것을 추천합니다.
function get_status_extra($code_name, $ch_id, $prev_value = 0, $last_value=0) {
	global $g5;

	$result = array();

	$default_status = 0; // 기본 수치
	$is_critical = false; // 크리티컬 여뷰
	$critical_status = 0; // 크리티컬로 더해지는 추가 수치
	$total_status = 0; // 최종 수치

	// 연동코드 설정 가져오기
	$ex = sql_fetch("select * from {$g5['status_extra_table']} where ex_name = '{$code_name}'");

	// 기본 수치
	$default_status = rand($ex['ex_main_min'], $ex['ex_main_max']);
	$status = 0;
	if($ex['ex_is_main_status']) {
		// 스탯 연동을 사용할 경우
		$status = get_status_total($ex['ex_main_status_type'], $ch_id);
		if($ex['ex_main_status_per']) {
			$status = $status * $ex['ex_main_status_per'];
		}
	}
	$default_status = (int)($default_status + $status + $prev_value);

	// 변동수치
	$cri_succed_per = $ex['ex_cri'];
	$cri_succed_per2 = 0;
	if($ex['ex_is_cri_status']) {
		// 스탯 연동을 사용할 경우
		$cri_succed_per2 = get_status_total($ex['ex_cri_status_type'], $ch_id);
		if($ex['ex_cri_status_per']) {
			$cri_succed_per2 = $cri_succed_per2 * $ex['ex_cri_status_per'];
		}
	}
	$cri_succed_per = $cri_succed_per + $cri_succed_per2;

	// 변동 수치의 퍼센트가 0 보다 클때
	if($cri_succed_per > 0) {
		// 크리티컬 판정 여부 확인
		$cri_succed_seed = rand(0, 100);
		if($cri_succed_seed <= $cri_succed_per) {
			// 크리티컬 성공
			$is_critical = true;

			// 크리티컬 성공할 경우 추가적으로 더해지는 수치를 계산한다.
			// 이 경우에는 기본수치에 기반한 비율이 더해지므로, 비율 수치를 계산한다.
			$add_status_per = $ex['ex_cri_add_per'];
			$add_status_per2 = 0;
			if($ex['ex_is_cri_add_status']) {
				// 스탯 연동을 사용할 경우
				$add_status_per2 = get_status_total($ex['ex_cri_add_status_type'], $ch_id);
				if($ex['ex_cri_add_status_per']) {
					$add_status_per2 = $add_status_per2 * $ex['ex_cri_add_status_per'];
				}
			}

			$critical_status = (int)($default_status * (($add_status_per + $add_status_per2)/100));
		}
	}

	$total_status = $default_status + $critical_status;
	if($ex['ex_all_per']) {
		$total_status = (int)($total_status * $ex['ex_all_per']);
	}

	$total_status = $total_status + $last_value;

	$result['default'] = $default_status;
	$result['is_cri'] = $is_critical;
	$result['cri_value'] = $critical_status;
	$result['value'] = $total_status;

	return $result;
}

// 현재 캐릭터 체력 정보
function get_extra_hp($ch_id) {
	global $g5;

	$result = sql_fetch("select * from {$g5['status_table']} sc, {$g5['status_config_table']} st where sc.ch_id = '{$ch_id}' and st.st_id = sc.st_id and st.st_use_hp = 1");

	$result['now'] = $result['sc_max'] - $result['sc_value'];
	$result['has'] = $result['sc_max'];

	return $result;
}

// 현재 캐릭터 체력 상태값 반환
function is_extra_hp($ch_id) {
	global $g5;

	// hp로 설정된 스탯 가져오기
	$check = get_extra_hp($ch_id);

	if($check['sc_value'] >= $check['sc_max']) {
		return "사망";
	} else {
		return "생존";
	}
}

// HP 변동 설정하는 부분
// value 가 양수값으로 들어오면 회복, 음수값으로 들어오면 차감
// 반환값 : 현재 hp 수치
function set_extra_hp($ch_id, $value) {
	global $g5;

	$result = 0;

	// hp로 설정된 스탯 가져오기
	$status = get_extra_hp($ch_id);

	if($status['sc_value'] >= $status['sc_max']) {
		// 사망 상태로 처리, 변동처리 하지 않음
		$result = 0;
	} else {
		// 아직 사망이 아님
		// 변동값 적용
		$status['sc_value'] = $status['sc_value'] + ($value * -1);

		if($status['sc_value'] >= $status['sc_max']) { 
			// 사망상태가 됩니다.
			$status['sc_value'] = $status['sc_max']; 
		} else if($status['sc_value'] < 0) { 
			$status['sc_value'] = 0; 
		} 

		$result = $status['sc_max'] - $status['sc_value'];
		sql_query(" update {$g5['status_table']} set sc_value = '{$status['sc_value']}' where sc_id = '{$status['sc_id']}'"); 
	}

	$hp_result['now'] = $result;
	$hp_result['has'] = $status['sc_max'];

	return $hp_result;
}

// 대미지 적용하는 부분
// -- 반환값 : 사망 여부
function set_extra_damage($ch_id, $value) {
	global $g5;
	
	$hp = set_extra_hp($ch_id, ($value * -1));

	return $hp;
}

// 사망 상태 해제하는 부분
function set_extra_revive($ch_id) {
	global $g5;
	$status = get_extra_hp($ch_id);
	sql_query(" update {$g5['status_table']} set sc_value = 0 where sc_id = '{$status['sc_id']}'");
}

?>