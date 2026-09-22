<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['dungeon_table'] = G5_TABLE_PREFIX.'dungeon'; // 던전 테이블
$g5['dungeon_item_table'] = G5_TABLE_PREFIX.'dungeon_item'; // 던전 보상 테이블
$g5['dungeon_state_table'] = G5_TABLE_PREFIX.'dungeon_state'; // 던전 생성 현황 테이블
$g5['dungeon_member_table'] = G5_TABLE_PREFIX.'dungeon_member'; // 던전 멤버 테이블
$g5['dungeon_log_table'] = G5_TABLE_PREFIX.'dungeon_log'; // 던전 로그 테이블

define('G5_DUNGEON_URL', G5_URL."/dungeon");
define('G5_DUNGEON_PATH', G5_PATH."/dungeon");

if (!isset($config['cf_dungeon_open'])) {
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_open` 	varchar(255) NOT NULL DEFAULT '' AFTER `cf_rank_name` ", true);
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_map` 	int(4) NOT NULL DEFAULT '0' AFTER `cf_rank_name` ", true);
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_reset` 	varchar(255) NOT NULL DEFAULT '' AFTER `cf_rank_name` ", true);
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_time` 	varchar(255) NOT NULL DEFAULT '' AFTER `cf_rank_name` ", true);
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_count` 	varchar(255) NOT NULL DEFAULT '' AFTER `cf_rank_name` ", true);
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_dungeon_enter` 	varchar(255) NOT NULL DEFAULT '' AFTER `cf_rank_name` ", true);
	
	// 아이템에 전투 중 사용 가능 항목 추가하기
	sql_query(" ALTER TABLE `{$g5['item_table']}` ADD `it_use_battle_able` int(11) NOT NULL default '0' AFTER `it_use_mmb_able` ");
	
}
if(!sql_query(" DESC {$g5['dungeon_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['dungeon_table']}` (
		`dg_id` int(11) NOT NULL AUTO_INCREMENT,
		`dg_title` varchar(255) NOT NULL default '',
		`dg_count` int(11) NOT NULL default '0',

		`ma_id` int(11) NOT NULL default '0',
		`ma_ids` varchar(255) NOT NULL default '',

		`dg_rank` varchar(255) NOT NULL default '',

		`dg_per_s` int(11) NOT NULL default '0',
		`dg_per_e` int(11) NOT NULL default '0',

		`dg_point` int(11) NOT NULL default '0',

		`dg_status` int(11) NOT NULL default '0',
		`dg_status_value` varchar(11) NOT NULL default '',
		`dg_status_type` varchar(11) NOT NULL default '',

		`dg_mon_name` varchar(255) NOT NULL default '',
		`dg_mon_img` varchar(255) NOT NULL default '',
		`dg_mon_hp` int(11) NOT NULL default '0',
		`dg_mon_descript` text NOT NULL,

		`dg_defence` int(11) NOT NULL default '0',

		`dg_d_attack_min` int(11) NOT NULL default '0',
		`dg_d_attack_max` int(11) NOT NULL default '0',
		`dg_d_attack_turn` int(11) NOT NULL default '0',
		`dg_d_attack_count` int(11) NOT NULL default '0',
		`dg_d_attack_comment` varchar(255) NOT NULL default '',

		`dg_w_attack_min` int(11) NOT NULL default '0',
		`dg_w_attack_max` int(11) NOT NULL default '0',
		`dg_w_attack_turn` int(11) NOT NULL default '0',
		`dg_w_attack_comment` varchar(255) NOT NULL default '',

		`dg_strong_code` varchar(255) NOT NULL default '',
		`dg_strong_value` varchar(255) NOT NULL default '',
		`dg_weak_code` varchar(255) NOT NULL default '',
		`dg_weak_value` varchar(255) NOT NULL default '',
		`dg_weak_effect_count` int(11) NOT NULL default '0',
		`dg_weak_effect_turn` int(11) NOT NULL default '0',
		`dg_s1_attack_turn` int(11) NOT NULL default '0',
		`dg_s1_attack_st_id` int(11) NOT NULL default '0',
		`dg_s1_attack_st_value` int(11) NOT NULL default '0',
		`dg_s1_attack_type` varchar(255) NOT NULL default '',
		`dg_s1_attack_min` int(11) NOT NULL default '0',
		`dg_s1_attack_max` int(11) NOT NULL default '0',
		`dg_s1_attack_comment` varchar(255) NOT NULL default '',
		`dg_s2_attack_turn` int(11) NOT NULL default '0',
		`dg_s2_attack_st_id` int(11) NOT NULL default '0',
		`dg_s2_attack_st_value` int(11) NOT NULL default '0',
		`dg_s2_attack_type` varchar(255) NOT NULL default '',
		`dg_s2_attack_min` int(11) NOT NULL default '0',
		`dg_s2_attack_max` int(11) NOT NULL default '0',
		`dg_s2_attack_comment` varchar(255) NOT NULL default '',
		`dg_use` int(11) NOT NULL default '0',
		PRIMARY KEY (`dg_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['dungeon_item_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['dungeon_item_table']}` (
		`di_id` int(11) NOT NULL AUTO_INCREMENT,
		`dg_id` int(11) NOT NULL default '0',
		`it_id` int(11) NOT NULL default '0',
		`di_count` int(11) NOT NULL default '0',
		`di_per_s` int(11) NOT NULL default '0',
		`di_per_e` int(11) NOT NULL default '0',
		PRIMARY KEY (`di_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['dungeon_state_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['dungeon_state_table']}` (
		`ds_id` int(11) NOT NULL AUTO_INCREMENT,
		`dg_id` int(11) NOT NULL default '0',
		`dg_title` varchar(255) NOT NULL default '',
		`ds_ma_id` int(11) NOT NULL default '0',
		`ds_state` varchar(10) NOT NULL default '',
		`ds_hp` int(11) NOT NULL default '0',
		`ds_hurt` int(11) NOT NULL default '0',
		`ds_weak_count` int(11) NOT NULL default '0',
		`ds_weak_turn` int(11) NOT NULL default '0',
		`ds_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`ds_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['dungeon_member_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['dungeon_member_table']}` (
		`dm_id` int(11) NOT NULL AUTO_INCREMENT,
		`ds_id` int(11) NOT NULL default '0',
		`ch_id` int(11) NOT NULL default '0',
		`dm_comment` text NOT NULL,
		`dm_evasion` varchar(255) NOT NULL default '',
		`dm_aggro` varchar(255) NOT NULL default '',
		`dm_aggro_per` int(11) NOT NULL default '0',
		`dm_state` char(4) NOT NULL default '',
		`dm_result` text NOT NULL,
		`dm_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`dm_id`)
	) ", false);
}
// ----------------------------- 필드 존재 여부 확인
$check_field = sql_fetch("SHOW COLUMNS FROM {$g5['dungeon_member_table']} LIKE 'dm_aggro_per'");
if(!$check_field) {
	sql_query(" ALTER TABLE `{$g5['dungeon_member_table']}` ADD `dm_aggro_per` int(11) NOT NULL default '0' AFTER `dm_aggro` ");
}
//--------------------------------------------

if(!sql_query(" DESC {$g5['dungeon_log_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['dungeon_log_table']}` (
		`dl_id` int(11) NOT NULL AUTO_INCREMENT,
		`ds_id` int(11) NOT NULL default '0',
		
		`ch_id` int(11) NOT NULL default '0',
		`ch_name` varchar(255) NOT NULL default '',

		`dl_cate` varchar(255) NOT NULL default '',
		`dl_function` varchar(255) NOT NULL default '',
		`dl_keep_limit` int(11) NOT NULL default '0',
		`dl_disposable` int(11) NOT NULL default '0',
		
		`sk_id` int(11) NOT NULL default '0',
		`sh_id` int(11) NOT NULL default '0',
		`sh_level` int(11) NOT NULL default '0',
		`sk_name` varchar(255) NOT NULL default '',
		`sh_name` varchar(255) NOT NULL default '',

		`st_id` int(11) NOT NULL default '0',
		`st_code` varchar(255) NOT NULL default '',
		`st_enermy` varchar(255) NOT NULL default '',

		`dl_value` int(11) NOT NULL default '0',
		
		`dl_log` text NOT NULL,
		`dl_is_turn` int(11) NOT NULL default '0',
		`dl_is_ciritical` int(11) NOT NULL default '0',
		`dl_datetime` varchar(255) NOT NULL default '',

		PRIMARY KEY (`dl_id`)
	) ", false);
}

$use_dungeon_map = ($config['cf_dungeon_open'] && $config['cf_dungeon_map']) ? true : false;

function set_reset_dungeon() {
	global $g5, $config;

	$now = date('Y-m-d H:i:s');
	if($config['cf_dungeon_reset'] == '0000-00-00 00:00:00' || $config['cf_dungeon_reset'] == '') {
		$config['cf_dungeon_reset'] = date('Y-m-d H').":00:00";
		sql_query("update {$g5['config_table']} set cf_dungeon_reset = '{$config['cf_dungeon_reset']}'");
	}

	$next_date = date('Y-m-d H:i:s', strtotime($config['cf_dungeon_reset']." +{$config['cf_dungeon_time']} Hour"));
	if($next_date < $now) {

		// 날짜 비교
		$to_time = strtotime($next_date); 
		$from_time = strtotime($now);
		$hour = floor(abs($to_time - $from_time) / 60 / 60); /* 시간 */
		$check_hour = floor($hour/$config['cf_dungeon_time']); /* 갱신 텀 체크 */

		if($check_hour > 0) {
			$reset_date = date('Y-m-d H:i:s', strtotime($next_date." +{$hour} Hour"));
		} else {
			$reset_date = $next_date;
		}

		$config['cf_dungeon_reset'] = $reset_date;
		sql_query("update {$g5['config_table']} set cf_dungeon_reset = '{$reset_date}'");

		// 갱신 시간이 되었다. 던전 발동 구간
		// 기존 발동되었던 던전들 close
		sql_query("update {$g5['dungeon_state_table']} set ds_state = 'E'");
		//sql_query("update {$g5['dungeon_member_table']} set dm_state = 'E'");
		sql_query("update {$g5['dungeon_member_table']} set dm_state = 'E', dm_result = '던전 공략에 실패하였습니다.' where dm_state != 'E' ");
		// 스킬 쿨 타임 초기화 (스킬 시스템을 사용하고 있어야 합니다.)
		sql_query("update {$g5['skill_has_table']} set sh_limit = '0'");


		if($config['cf_dungeon_map']) { 
			// 지역 사용 설정
			// 던전 오픈 지역 뽑아내기
			// -- map id 랜덤으로 정해진 갯수 만큼 뽑기
			$ma_sql = sql_query("select ma_id from {$g5['map_table']} where ma_use = 1 and ma_use_dungeon = 1 order by rand() limit 0, {$config['cf_dungeon_count']}");

			for($i=0; $ma = sql_fetch_array($ma_sql); $i++) {
				// 지역별 던전 오픈
				$seed = rand(0, 100);
				$dg = sql_fetch("select * from {$g5['dungeon_table']} where dg_use = 1 and ma_ids like '%||{$ma['ma_id']}||%' and (dg_per_s <= '{$seed}' and dg_per_e >= '{$seed}') order by RAND() limit 0, 1");

				if($dg['dg_id']) {
					// 던전 오픈 정보 추가
					$sql = " insert into {$g5['dungeon_state_table']}
							set dg_id = '{$dg['dg_id']}',
								ds_ma_id = '{$ma['ma_id']}',
								dg_title = '{$dg['dg_title']}',
								ds_state = 'S',
								ds_hp = '{$dg['dg_mon_hp']}',
								ds_hurt = '0',
								ds_datetime = '".date('Y-m-d H:i:s')."'
							";
					sql_query($sql);
				}
			}
		} else {
			// 지역 사용 설정하지 않았을 시
			// 등록된 던전 중에서 랜덤으로 정해진 갯수 만큼 뽑아낸다.
			$seed = rand(0, 100);
			$dg_result = sql_query("select * from {$g5['dungeon_table']} where dg_use = 1 and (dg_per_s <= '{$seed}' and dg_per_e >= '{$seed}') order by RAND() limit 0, {$config['cf_dungeon_count']}");

			for($i=0; $dg = sql_fetch_array($dg_result); $i++) {
				if($dg['dg_id']) {
					// 던전 오픈 정보 추가
					$sql = " insert into {$g5['dungeon_state_table']}
							set dg_id = '{$dg['dg_id']}',
								ds_ma_id = '0',
								dg_title = '{$dg['dg_title']}',
								ds_state = 'S',
								ds_hp = '{$dg['dg_mon_hp']}',
								ds_hurt = '0',
								ds_datetime = '".date('Y-m-d H:i:s')."'
							";
					sql_query($sql);
				}
			}
		}
	}
	return $config['cf_dungeon_reset'];
}

function get_map_dungeon($ma_id) {
	global $g5;
	// 던전 오픈 현황 체크하기
	$result = sql_fetch("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.dg_id = dg.dg_id and ds.ds_ma_id = '{$ma_id}' and ds.ds_state != 'E' limit 0,1");
	return $result;
}

function get_dungeon($dg_id) {
	global $g5;

	$result = sql_fetch("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.dg_id = dg.dg_id and dg.dg_id = '{$dg_id}'");
	return $result;
}

function get_dungeon_state($ds_id) {
	global $g5, $is_admin;

	$result = sql_fetch("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.dg_id = dg.dg_id and ds.ds_id = '{$ds_id}'");

	if(!$result['ds_id']) {
		$check = sql_fetch("select dg_title from {$g5['dungeon_state_table']} where ds_id = '{$ds_id}'");
		$result = sql_fetch("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.dg_title = dg.dg_title and ds.dg_title = '{$check['dg_title']}' limit 0,1");
	}

	if($is_admin) { 
		//echo "select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.dg_title = dg.dg_title limit 0,1";
	}

	return $result;
}

function get_dungeon_member($ds_id, $state = 'S') {
	global $g5;

	$sql = "";
	if($state == '') {
		$sql = "select * from {$g5['dungeon_member_table']} dm, {$g5['character_table']} ch where dm.ds_id = '{$ds_id}' and dm.ch_id = ch.ch_id";
	} else if($state == 'S') {
		$sql = "select * from {$g5['dungeon_member_table']} dm, {$g5['character_table']} ch where dm.ds_id = '{$ds_id}' and dm.ch_id = ch.ch_id and dm.dm_state='S'";
	} else if($state == 'E') {
		$sql = "select * from {$g5['dungeon_member_table']} dm, {$g5['character_table']} ch where dm.ds_id = '{$ds_id}' and dm.ch_id = ch.ch_id and dm.dm_state='E'";
	}

	$result = sql_query($sql);
	$mem = array();

	for($i=0; $row = sql_fetch_array($result); $i++) {
		$mem[$i] = $row;
	}

	return $mem;
}

function get_dungeon_member_count($ds_id) {
	global $g5;
	$result = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ds_id = '{$ds_id}'");

	return $result['cnt'];
}

function get_dungeon_character($ds_id, $ch_id) {
	global $g5;
	$result = sql_fetch("select * from {$g5['dungeon_member_table']} dm, {$g5['character_table']} ch where dm.ds_id = '{$ds_id}' and dm.ch_id = '{$ch_id}' and dm.ch_id = ch.ch_id");
	return $result;
}

function set_dungeon_character_damage($ds_id, $ch_id, $dm = null, $value = 0) {
	global $g5, $config;

	$log = "";

	// HP 스탯 정보 가져오기
	$st_hp_id = sql_fetch("select st_id from {$g5['status_config_table']} st where st_use_hp = 1");
	$st_hp_id = $st_hp_id['st_id'];

	// 던전 스탯 정보 가져오기
	//if(!$dm['ch_id']) { $dm = get_dungeon_character($ds_id, $ch_id); }
	$dm = get_dungeon_character($ds_id, $ch_id);

	// 자동회피 여부 가져오기
	$is_evasion_value = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function = '회피' and ch_id = '{$dm['ch_id']}' and dl_keep_limit > 0 and dl_value > 0 order by dl_keep_limit asc");
	$is_evasion_value = $is_evasion_value['cnt'];

	// -- 회피 부터 처리 합니다. 회피 성공 시 방어력 처리는 X
	if($is_evasion_value) {
		$is_evasion = true;
		sql_query("update {$g5['dungeon_log_table']} set dl_value = dl_value-1 where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function = '회피' and ch_id = '{$dm['ch_id']}' and dl_keep_limit > 0");
	}
	
	if(!$is_evasion) {
		// 회피가 적용 안되어 있을 시, 자동 회피 체크하기
		$code_evasion = get_status_dungeon("자동회피", $ds_id, $dm['ch_id'], $dm);
		$code_evasion = intval($code_evasion['value']);
		$evasion_seed = rand(0,1000);
		if($evasion_seed <= $code_evasion) {$is_evasion = true;}
	}

	if($is_evasion) {
		// 회피 성공 시 
		$log = "<p>【{$dm['ch_name']}】회피 성공<i>!</i></p>";
	} else {
		// 회피 실패 시
		// -- 방어력 가져오기
		$code_def = get_status_dungeon("방어력", $ds_id, $dm['ch_id'], $dm);
		$code_def = intval($code_def['value']);

		$value = $value - $code_def;
		if($value < 0) $value = 0;

		// A 통합 방어 스킬: 레이드의 unified_guard와 같은 비율만큼 받는 피해를 줄인다.
		$guard = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']}
			where ds_id = '{$ds_id}' and dl_cate = '효과' and dl_function = '방어'
			and ch_id = '{$dm['ch_id']}' and dl_keep_limit > 0");
		$guard_rate = min(90, max(0, intval($guard['total'])));
		if($guard_rate > 0) {
			$value = intval(round($value * (100 - $guard_rate) / 100));
		}

		if($value > 0) {
			// hp 현황
			$max_status_value = $dm['st_id_'.$st_hp_id] + $dm['st_id_'.$st_hp_id.'_mod'];
			$status_value = $dm['st_id_'.$st_hp_id.'_use'] + $value;

			$log = "<p>【{$dm['ch_name']}】에게 <strong>{$value}</strong>대미지<i>!</i></p>";

			if($status_value >= $max_status_value) {
				// 사망상태 적용
				$status_value = $max_status_value;
				// 던전 참여 상태 변경
				sql_query("update {$g5['dungeon_member_table']} set dm_state = 'E' where ch_id = '{$ch_id}'");
				$log .= "<p>【{$dm['ch_name']}】이(가) 행동불능이 되었습니다.</p>";
			}
			
			// 체력 스탯 감소
			$sql = " update {$g5['dungeon_member_table']} set st_id_{$st_hp_id}_use ={$status_value} where dm_id = '{$dm['dm_id']}'";
			sql_query($sql);
		} else {
			$log = "<p>【{$dm['ch_name']}】이(가) 모든 대미지를 상쇄하였습니다<i>!</i></p>";
		}
	}
	return $log;
}

//HP 포션 사용 가능 여부
function is_able_hp($ds_id, $ch_id) {
	global $g5;

	$result = true;
	/*$dm = sql_fetch("select ch_id from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_state != 'E' and ds_id = '{$ds_id}'");
	if($dm['ch_id']) {
		$result = false;
	}*/
	return $result;
}

function is_has_dungeon($ch_id, $not_ds_id = 0, $ds_id = 0) {
	global $g5;

	if($not_ds_id) {
		$result = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_state != 'E' and ds_id != '{$not_ds_id}'");
	} else if($ds_id) {
		$result = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_state != 'E' and ds_id = '{$ds_id}'");
	} else {
		$result = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_state != 'E'");
	}

	return $result['cnt'] > 0 ? true : false;
}

// 던전 진행 가능 여부 체크하기
function is_able_dungeon($ch_id, $ds_id, $is_opend = false, $ds = array()) {
	global $g5, $config;

	$is_able = true;
	$is_no_member = false;
	$message = "";
	
	$character = get_character($ch_id);

	if(!$ds['ds_id']) {
		$ds = get_dungeon_state($ds_id);
		$ds_id = $ds['ds_id'];
	}
	
	if(!$ds['ds_id'] && $message == "") {
		$is_able = false;
		$message = "던전 정보가 확인되지 않습니다.";
	}

	// 던전 입장 유무 체크
	$mem_status = get_dungeon_character($ds['ds_id'], $character['ch_id']);
	
	if(!$mem_status['ch_id']) {
		$is_no_member = true;
		if($ds['ds_state'] == 'E' && $message == "" && !$is_opend) {
			$is_able = false;
			$message = "던전이 폐쇄 되었습니다.";
		}

		if($character['ma_id'] != $ds['ds_ma_id'] && $message == "" && !$is_opend && $config['cf_dungeon_map']) {
			$is_able = false;
			$message = "던전 지역과 다른 곳에 있습니다.";
		}

		// 현재까지 수행한 던전 횟수 : 하루동안 체크
		/*cf_dungeon_enter*/
		//$apply_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_datetime = '".date('Y-m-d')."'");

		$apply_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_member_table']} where ch_id = '{$ch_id}' and dm_result != '' and dm_datetime = '".date('Y-m-d')."'");
		$apply_count = $apply_count['cnt'];
		if($apply_count >= $config['cf_dungeon_enter']) {
			$is_able = false;
			$message = "하루 입장할 수 있는 횟수를 초과하였습니다.";
		}

		// 인원 제한 체크하기
		$check_count = get_dungeon_member_count($ds_id);
		if($check_count >= $ds['dg_count'] && $message == "") {
			$is_able = false;
			$message = "입장할 수 있는 인원이 초과되었습니다.";
		}

		// 현재 입장중인 던전이 있는지 체크
		if(is_has_dungeon($character['ch_id'], $ds_id) && $message == "") {
			$is_able = false;
			$message = "현재 진행중인 던전이 있습니다.";
		}
	}

	$result['no_member'] = $is_no_member;
	$result['state'] = $mem_status['dm_state'];
	$result['is_able'] = $is_able;
	$result['message'] = $message;

	return $result;
}

// 던전 내 몹 대미지 입히기 처리 부분
function set_dungeon_mon_damage($ds_id, $ds = null, $value = 0, $is_weak = false) {
	global $g5;

	if(!$ds['ds_id']) {
		$ds = get_dungeon_state($ds_id);
	}

	$ds_state = "S";
	$ds_hurt = $ds['ds_hurt'] + $value;
	if($ds['ds_hp'] <= $ds_hurt) {
		// 레이드몹 사망처리
		$ds_hurt = $ds['ds_hp'];
		$ds_state = "E";
		sql_query("update {$g5['dungeon_state_table']} set ds_state = 'E', ds_hurt = '{$ds_hurt}'  where ds_id = '{$ds_id}'");
		insert_dungeon_log("시스템", $ds, null, null, 0, 0, "전투가 완료되었습니다!");
	} else {
		$sql = "update {$g5['dungeon_state_table']} set ds_state = '{$ds_state}', ds_hurt = '{$ds_hurt}' ";

		if($is_weak && $ds['dg_weak_effect_count']) {
			$is_weak_state = ($ds['ds_weak_count'] + 1) >= $ds['dg_weak_effect_count'] ? true : false;

			if(!$ds['ds_weak_turn']) {
				if($is_weak_state) {
					// 취약공격 패턴이 왔을 경우
					// 취약 공격 카운트 저장
					$sql .= ", ds_weak_count = 0, ds_weak_turn = {$ds['dg_weak_effect_turn']} ";
					$ds_state = "G";
				} else {
					// 공격 패턴이 아직 오지 않았을 경우
					$sql .= ", ds_weak_count = ds_weak_count+1 ";
				}
			}
		}
		
		$sql .= "where ds_id = '{$ds_id}'";
		sql_query($sql);
	}
	return $ds_state;
}

// 던전 내 처리 용 확장 스탯 기능 재정의
function get_status_dungeon_total($st_type, $ds_id, $ch_id, $dm = null) {
	global $g5;

	if($dm == null) $dm = get_dungeon_character($ds_id, $ch_id);
	if(!$dm['dm_id']) return;

	// 관리자에서 A 연동 타입을 K 파생 스탯에 연결한 경우,
	// 던전 스냅샷과 던전 내 버프를 입력으로 같은 K 수식을 다시 계산한다.
	if(function_exists('unified_k_stat_id_for_type') && function_exists('unified_dungeon_k_stat_value')) {
		$k_sc_id = unified_k_stat_id_for_type($st_type);
		if($k_sc_id) return unified_dungeon_k_stat_value($ds_id, $ch_id, $dm, $k_sc_id);
	}

	$filed = get_status_type_filed($st_type);
	$result = sql_query("select st_id from {$g5['status_config_table']} st where {$filed} = 1");
	$total = 0;
	for($i=0; $row = sql_fetch_array($result); $i++) {
		$temp_val = ($dm['st_id_'.$row['st_id']] + $dm['st_id_'.$row['st_id'].'_mod'] - $dm['st_id_'.$row['st_id'].'_use']);
		$buff_state = get_status_buffer_state($ds_id, $ch_id, $row['st_id']);
		$temp_val = $temp_val + $buff_state;
		$total += $temp_val;
	}
	return $total;
}

function get_status_dungeon($code_name, $ds_id, $ch_id, $dm = null, $prev_value = 0, $last_value=0) {
	global $g5;

	if($dm == null) $dm = get_dungeon_character($ds_id, $ch_id);
	if(!$dm['dm_id']) return;

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
		$status = get_status_dungeon_total($ex['ex_main_status_type'], $ds_id, $ch_id, $dm);
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
		$cri_succed_per2 = get_status_dungeon_total($ex['ex_cri_status_type'], $ds_id, $ch_id, $dm );
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
				$add_status_per2 = get_status_dungeon_total($ex['ex_cri_add_status_type'], $ds_id, $ch_id, $dm );
				if($ex['ex_cri_add_status_per']) {
					$add_status_per2 = $add_status_per2 * $ex['ex_cri_add_status_per'];
				}
			}
			$temp_check_value = $add_status_per + $add_status_per2;
			$critical_status = $temp_check_value > 0 ? (int)($default_status * ($temp_check_value/100)) : 0;
		}
	}

	$total_status = $default_status + $critical_status;
	if($ex['ex_all_per']) {
		$total_status = (int)($total_status * $ex['ex_all_per']);
	}

	// 버프 코드 적용
	$buff_code_value = get_status_buffer_code($ds_id, $ch_id, $code_name);
	$total_status = $total_status + $buff_code_value;

	$total_status = $total_status + $last_value;

	$result['default'] = $default_status;
	$result['is_cri'] = $is_critical;
	$result['cri_value'] = $critical_status;
	$result['value'] = $total_status;

	return $result;
}

/*
 * 스킬 슬롯과 무관한 던전 일반 공격이다.
 * A/K 통합 전투에서 지정한 일반 공격 연동 코드를 쓰며, 던전 입장 스냅샷·던전 버프·
 * 몬스터의 강점/취약점 규칙까지 공격 스킬과 같은 순서로 적용한다.
 */
function unified_dungeon_basic_attack_result($ds_id, $ds, $ch_id, $dm, $code_name) {
	$result = array('value' => 0, 'is_critical' => 0, 'is_weak' => false, 'message' => '');
	if (!is_array($ds) || empty($ds['ds_id']) || !is_array($dm) || empty($dm['dm_id']) || $code_name === '') return $result;

	$code_result = get_status_dungeon($code_name, (int)$ds_id, (int)$ch_id, $dm);
	$value = isset($code_result['value']) ? (int)$code_result['value'] : 0;
	$value += (int)get_status_buffer_code((int)$ds_id, (int)$ch_id, '공통대미지');
	$is_weak = false;
	$message = '';

	if (!empty($ds['dg_strong_code']) && $ds['dg_strong_code'] === $code_name) {
		$value = (int)round($value * (float)$ds['dg_strong_value']);
		$message = "「{$ds['dg_mon_name']}」이(가) 대미지의 일부를 상쇄시켰습니다.";
	}
	if (!empty($ds['dg_weak_code']) && $ds['dg_weak_code'] === $code_name) {
		$value = (int)round($value * (float)$ds['dg_weak_value']);
		$is_weak = true;
		$message = "「{$ds['dg_mon_name']}」이(가) 경직됩니다.";
	}

	$result['value'] = max(0, (int)$value);
	$result['is_critical'] = !empty($code_result['is_cri']) ? 1 : 0;
	$result['is_weak'] = $is_weak;
	$result['message'] = $message;
	return $result;
}

function get_status_buffer_state($ds_id, $ch_id, $st_id) {
	global $g5;
	$result = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and ch_id = '{$ch_id}' and st_id = '{$st_id}' and dl_cate = '효과' and dl_function='스탯강화' and dl_keep_limit > 0");
	return $result['total'];
}

function get_status_buffer_code($ds_id, $ch_id, $code) {
	global $g5;
	$result = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and ch_id = '{$ch_id}' and st_code = '{$code}' and dl_cate = '효과' and dl_function='연동코드강화' and dl_keep_limit > 0");
	return $result['total'];
	//return 0;
}

function get_status_buffer_enermy($ds_id, $code) {
	global $g5;
	$result = sql_fetch("select SUM(dl_value) as total from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and st_enermy = '{$code}' and dl_cate = '효과' and dl_keep_limit > 0");
	return $result['total'];
	//return 0;
}


// 던전 로그 삽입 : dungeon_state - ds / user - dm / skill - sh
function insert_dungeon_log($category, $dungeon_state, $user, $skill, $value, $is_cri = "", $log = "") {
	global $g5;

	$dl_is_turn = 1;
	$dm_comment = "";

	switch($category) {
		case "스킬":
			$dm_comment = $log;
			// keep limit 가 있는 경우, 스킬이 아니라 효과 카테고리로
			// 별도로 추가하는 로직 만들 것
			$skill['sk_keep_limit'] = 0;
		break;
		case "아이템":
			$dm_comment = $log;
			$dl_is_turn = 0;
		break;
		case "대화":
			$dm_comment = $log;
			$dl_is_turn = 0;
		break;
		case "시스템":
			$dl_is_turn = 0;
		break;
		case "효과":
			$dl_is_turn = 0;
		break;
		case "몬스터":
			$user = array();
			$dl_is_turn = 0;
			$user['ch_id'] = -1;
			$user['ch_name'] = $ds['dg_mon_name'];
			//$dm_comment = $log;
		break;
	}

	$ds_id = $dungeon_state['ds_id'];
	$ch_id = $user['ch_id'];
	$ch_name = $user['ch_name'];
	$dl_function = $skill['sk_function'];
	$dl_keep_limit = $skill['sk_keep_limit'];
	

	$sk_id = $skill['sk_id'];
	$sh_id = $skill['sh_id'];
	$sh_level = $skill['sh_level'];
	$sk_name = $skill['sk_name'];
	$sh_name = $skill['sh_name'];

	$st_id = $skill['sk_mod_st_id'];
	$st_code = $skill['sk_mod_code'];
	$st_enermy = $skill['sk_mod_enermy'];

	
	if($dl_is_turn && $ch_id > 0) { // 유지턴수 감소 처리
		sql_query("update {$g5['dungeon_log_table']} set dl_keep_limit = dl_keep_limit-1 where ds_id = '{$ds_id}' and ch_id = '{$ch_id}' and dl_keep_limit > 0");
		sql_query("update {$g5['skill_has_table']} set sh_limit = sh_limit-1 where ch_id = '{$ch_id}' and sh_limit > 0");
	}

	$sql = " insert into {$g5['dungeon_log_table']}
			set ds_id		= '{$ds_id}',
				ch_id		= '{$ch_id}',
				ch_name		= '{$ch_name}',
				
				dl_cate			= '{$category}',
				dl_function		= '{$dl_function}',
				dl_keep_limit	= '{$dl_keep_limit}',

				sk_id		= '{$sk_id}',
				sh_id		= '{$sh_id}',
				sh_level	= '{$sh_level}',
				sk_name		= '{$sk_name}',
				sh_name		= '{$sh_name}',

				st_id		= '{$st_id}',
				st_code		= '{$st_code}',
				st_enermy	= '{$st_enermy}',

				dl_value	= '{$value}',
				dl_log		= '{$log}',

				dl_is_turn			= '{$dl_is_turn}',
				dl_is_ciritical		= '{$is_cri}',
				dl_datetime			= '".date('Y-m-d H:i:s')."'
			";
	sql_query($sql);

	if($dm_comment) {
		sql_query("update {$g5['dungeon_member_table']} set dm_comment = '{$dm_comment}' where dm_id = '{$user['dm_id']}'");
	}
}


?>
