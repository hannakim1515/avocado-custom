<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 맵 관련 테이블값 추가
$g5['map_table'] = G5_TABLE_PREFIX.'newmap_config'; // 지역 테이블
$g5['map_event_table'] = G5_TABLE_PREFIX.'newmap_event'; // 지역이벤트 테이블
$g5['map_log'] = G5_TABLE_PREFIX.'newmap_log'; // 지역설정 테이블
$g5['map_comment_table'] = G5_TABLE_PREFIX.'newmap_comment'; // 지역 코멘트 테이블
$g5['map_action_table'] = G5_TABLE_PREFIX.'newmap_action'; // 지역 행동 테이블
$g5['map_work_table'] = G5_TABLE_PREFIX.'newmap_work'; // 지역 방치형 작업 테이블
$g5['map_npc_place_table'] = G5_TABLE_PREFIX.'newmap_npc_place'; // 지역별 NPC 등장 설정
$g5['map_npc_script_table'] = G5_TABLE_PREFIX.'newmap_npc_script'; // MAP 전용 NPC 대사/이벤트
$g5['map_npc_choice_table'] = G5_TABLE_PREFIX.'newmap_npc_choice'; // MAP 전용 NPC 선택지
$g5['map_npc_encounter_table'] = G5_TABLE_PREFIX.'newmap_npc_encounter'; // MAP NPC 이벤트 발생/보상 처리 로그

function map_ensure_column($table, $column, $sql)
{
	$row = sql_fetch(" SHOW COLUMNS FROM `{$table}` LIKE '{$column}' ", false);
	if(!isset($row['Field']) || !$row['Field']) {
		sql_query(" ALTER TABLE `{$table}` ADD {$sql} ", false);
	}
}


// 맵 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_table']}` (
		`ma_id` int(11) NOT NULL AUTO_INCREMENT,
		`ma_name` varchar(255) NOT NULL default '',
		`ma_parent` int(11) NOT NULL default '0',
		`ma_use_dungeon` int(11) NOT NULL default '0',
		`ma_top` int(111) NOT NULL default '0',
		`ma_left` int(11) NOT NULL default '0',
		`ma_width` int(11) NOT NULL default '0',
		`ma_height` int(11) NOT NULL default '0',
		`ma_start` int(11) NOT NULL default '0',
		`ma_img` varchar(255) NOT NULL default '',
		`ma_npc_img` varchar(255) NOT NULL default '',
		`ma_npc_name` varchar(255) NOT NULL default '',
		`ma_npc_chance` tinyint(3) unsigned NOT NULL default '15',
		`ma_content` text NOT NULL,
		`ma_move` text NOT NULL,
		`ma_use` int(11) NOT NULL default '0',
		PRIMARY KEY (`ma_id`)
	) ", false);
}
map_ensure_column($g5['map_table'], 'ma_npc_img', "`ma_npc_img` varchar(255) NOT NULL DEFAULT '' AFTER `ma_img`");
map_ensure_column($g5['map_table'], 'ma_npc_name', "`ma_npc_name` varchar(255) NOT NULL DEFAULT '' AFTER `ma_npc_img`");

// 맵 이벤트 설정값이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_event_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_event_table']}` (
		`me_id` int(11) NOT NULL AUTO_INCREMENT,
		`ma_id` int(11) NOT NULL default '0',
		`action_id` int(11) NOT NULL default '0',
		`me_type` varchar(255) NOT NULL default '',
		`me_title` varchar(255) NOT NULL default '',
		`me_img` varchar(255) NOT NULL default '',
		`me_content` text NOT NULL,
		`me_get_item` int(11) NOT NULL default '0',
		`me_get_money` int(11) NOT NULL default '0',
		`me_move_map` int(11) NOT NULL default '0',
		`me_per_s` int(11) NOT NULL default '0',
		`me_per_e` int(11) NOT NULL default '0',
		`me_replay_cnt` int(11) NOT NULL default '0',
		`me_now_cnt` int(11) NOT NULL default '0',
		`me_use` int(11) NOT NULL default '0',
		PRIMARY KEY (`me_id`)
	) ", false);
}
map_ensure_column($g5['map_event_table'], 'action_id', "`action_id` INT(11) NOT NULL DEFAULT '0' AFTER `ma_id`");
map_ensure_column($g5['map_event_table'], 'me_type', "`me_type` varchar(255) NOT NULL DEFAULT '' AFTER `action_id`");

// 맵 행동 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_action_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_action_table']}` (
		`action_id` int(11) NOT NULL AUTO_INCREMENT,
		`ma_id` int(11) NOT NULL default '0',
		`action_type` varchar(50) NOT NULL default 'parttime',
		`action_name` varchar(255) NOT NULL default '',
		`action_desc` text NOT NULL,
		`action_order` int(11) NOT NULL default '0',
		`action_use` int(11) NOT NULL default '1',
		`is_timed` int(11) NOT NULL default '1',
		`duration_minutes` int(11) NOT NULL default '240',
		`available_time_use` int(11) NOT NULL default '0',
		`available_start` varchar(5) NOT NULL default '00:00',
		`available_end` varchar(5) NOT NULL default '00:00',
		PRIMARY KEY (`action_id`),
		KEY `ma_id` (`ma_id`),
		KEY `action_type` (`action_type`)
	) ", false);
}

// 맵 방치형 작업 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_work_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_work_table']}` (
		`work_id` int(11) NOT NULL AUTO_INCREMENT,
		`ch_id` int(11) NOT NULL default '0',
		`mb_id` varchar(255) NOT NULL default '',
		`action_id` int(11) NOT NULL default '0',
		`ma_id` int(11) NOT NULL default '0',
		`started_at` varchar(19) NOT NULL default '',
		`complete_at` varchar(19) NOT NULL default '',
		`work_status` varchar(20) NOT NULL default 'WORKING',
		`result_event_id` int(11) NOT NULL default '0',
		`reward_claimed` int(11) NOT NULL default '0',
		`claimed_at` varchar(19) NOT NULL default '',
		PRIMARY KEY (`work_id`),
		KEY `ch_id` (`ch_id`),
		KEY `action_id` (`action_id`),
		KEY `reward_claimed` (`reward_claimed`)
	) ", false);
}

// 지역별 NPC 등장 설정 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_npc_place_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_npc_place_table']}` (
		`mn_id` int(11) NOT NULL AUTO_INCREMENT,
		`ma_id` int(11) NOT NULL default '0',
		`npc_id` int(11) NOT NULL default '0',
		`mn_weight` int(11) NOT NULL default '1',
		`mn_order` int(11) NOT NULL default '0',
		`mn_use` int(11) NOT NULL default '1',
		PRIMARY KEY (`mn_id`),
		KEY `ma_use` (`ma_id`,`mn_use`),
		KEY `npc_id` (`npc_id`)
	) ", false);
}


// MAP 전용 NPC 대사/선택지 이벤트 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_npc_script_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_npc_script_table']}` (
		`script_id` int(11) NOT NULL AUTO_INCREMENT,
		`mn_id` int(11) NOT NULL default '0',
		`script_type` varchar(20) NOT NULL default 'dialogue',
		`script_title` varchar(255) NOT NULL default '',
		`script_content` text NOT NULL,
		`script_weight` int(11) NOT NULL default '1',
		`script_favor_s` int(11) NULL default NULL,
		`script_favor_e` int(11) NULL default NULL,
		`script_favor_value` int(11) NOT NULL default '0',
		`script_repeat_type` varchar(20) NOT NULL default 'always',
		`script_cooldown` int(11) NOT NULL default '0',
		`script_require_script_id` int(11) NULL default NULL,
		`script_order` int(11) NOT NULL default '0',
		`script_use` int(11) NOT NULL default '1',
		PRIMARY KEY (`script_id`),
		KEY `mn_id` (`mn_id`),
		KEY `script_type` (`script_type`)
	) ", false);
}

// MAP 전용 NPC 선택지 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_npc_choice_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_npc_choice_table']}` (
		`choice_id` int(11) NOT NULL AUTO_INCREMENT,
		`script_id` int(11) NOT NULL default '0',
		`choice_text` varchar(255) NOT NULL default '',
		`choice_result` text NOT NULL,
		`choice_fail_result` text NOT NULL,
		`choice_get_item` int(11) NOT NULL default '0',
		`choice_get_money` int(11) NOT NULL default '0',
		`choice_favor_value` int(11) NOT NULL default '0',
		`choice_check_type` varchar(30) NULL default NULL,
		`choice_check_target` varchar(100) NULL default NULL,
		`choice_check_operator` varchar(10) NULL default NULL,
		`choice_check_value` int(11) NULL default NULL,
		`choice_fail_get_money` int(11) NOT NULL default '0',
		`choice_fail_get_item` int(11) NOT NULL default '0',
		`choice_fail_favor_value` int(11) NOT NULL default '0',
		`choice_order` int(11) NOT NULL default '0',
		`choice_use` int(11) NOT NULL default '1',
		PRIMARY KEY (`choice_id`),
		KEY `script_id` (`script_id`)
	) ", false);
}

// MAP NPC 이벤트 발생/보상 처리 로그 테이블이 없을 경우 생성
if(!sql_query(" DESC {$g5['map_npc_encounter_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_npc_encounter_table']}` (
		`enc_id` int(11) NOT NULL AUTO_INCREMENT,
		`ch_id` int(11) NOT NULL default '0',
		`mb_id` varchar(255) NOT NULL default '',
		`ma_id` int(11) NOT NULL default '0',
		`mn_id` int(11) NOT NULL default '0',
		`npc_id` int(11) NOT NULL default '0',
		`script_id` int(11) NOT NULL default '0',
		`script_type` varchar(20) NOT NULL default '',
		`choice_id` int(11) NOT NULL default '0',
		`reward_claimed` int(11) NOT NULL default '0',
		`enc_status` varchar(20) NOT NULL default 'START',
		`created_at` varchar(19) NOT NULL default '',
		`claimed_at` varchar(19) NOT NULL default '',
		PRIMARY KEY (`enc_id`),
		KEY `ch_script_status_time` (`ch_id`,`script_id`,`enc_status`,`claimed_at`),
		KEY `npc_id` (`npc_id`),
		KEY `script_id` (`script_id`),
		KEY `reward_claimed` (`reward_claimed`)
	) ", false);
}

// 맵 코멘트 테이블이 없을 경우
if(!sql_query(" DESC {$g5['map_comment_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_comment_table']}` (
		`mc_id` int(11) NOT NULL AUTO_INCREMENT,
		`ma_id` int(11) NOT NULL default '0',
		`ch_id` int(11) NOT NULL default '0',
		`mc_content` text NOT NULL,
		`mc_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`mc_id`)
	) ", false);
}

// 맵 로그 테이블이 없을 경우
if(!sql_query(" DESC {$g5['map_log']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['map_log']}` (
		`ml_id` int(11) NOT NULL AUTO_INCREMENT,
		`me_id` int(11) NOT NULL default '0',
		`ch_id` int(11) NOT NULL default '0',
		`ch_name` varchar(255) NOT NULL default '',
		`ml_log` text NOT NULL,
		`ml_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`ml_id`)
	) ", false);
}

// 캐릭터에 맵 이동 값이 존재하지 않을 경우
if($is_member && $character['ch_id'] && !isset($character['ma_id'])) { 
	sql_query(" ALTER TABLE `{$g5['character_table']}` ADD `ma_id` INT(11) NOT NULL DEFAULT '0' AFTER `ch_side` ");
}

// 관리자에 맵 기능 사용 여부 설정값 추가
if(!isset($config['cf_use_map'])) { 
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_use_map` INT(11) NOT NULL DEFAULT '0' AFTER `cf_open` ");
}
if(!isset($config['cf_use_map_all'])) { 
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_use_map_all` INT(11) NOT NULL DEFAULT '0' AFTER `cf_use_map` ");
}
if(!isset($config['cf_map_all_img'])) { 
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_map_all_img` varchar(255) NOT NULL default '' AFTER `cf_use_map_all` ");
}
if(!isset($config['cf_map_all_w'])) { 
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_map_all_w` INT(11) NOT NULL DEFAULT '0' AFTER `cf_map_all_img` ");
}
if(!isset($config['cf_map_all_h'])) { 
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_map_all_h` INT(11) NOT NULL DEFAULT '0' AFTER `cf_map_all_w` ");
}

if(isset($character['ch_id']) && !$character['ma_id']) {
	$ma_id = sql_fetch("select ma_id from {$g5['map_table']} where ma_start = '1' limit 0, 1");
	$ma_id = $ma_id['ma_id'];
	sql_query("
		update {$g5['character_table']}
				set		ma_id = '{$ma_id}'
			where		ch_id = '{$character['ch_id']}'
	");
	$character['ma_id'] = $ma_id;
}


function get_map($ma_id) { 
	global $g5;
	$ma  = sql_fetch("select * from {$g5['map_table']} where ma_id = '{$ma_id}'");
	return $ma;
}

function get_map_name($ma_id) { 
	global $g5;
	$ma  = sql_fetch("select ma_name from {$g5['map_table']} where ma_id = '{$ma_id}'");
	$result = $ma['ma_name'] ? $ma['ma_name'] : "-";
	return $result;
}


function get_map_parnet_name($ma_id) { 
	global $g5;
	$ma  = sql_fetch("select b.ma_name from (select ma_parent from {$g5['map_table']} where ma_id = '{$ma_id}') a, {$g5['map_table']} b where a.ma_parent = b.ma_id");

	return $ma['ma_name'];
}

function set_move_map($ch_id, $ma_id) { 
	global $g5;
	sql_query("
		update {$g5['character_table']}
				set		ma_id = '{$ma_id}'
			where		ch_id = '{$ch_id}'
	");
}


function set_map_log($ch_id, $ch_name, $log, $me_id= 0) {
	global $g5;

	$sql = " insert into {$g5['map_log']}
			set ch_id = '{$ch_id}',
				ch_name = '{$ch_name}',
				me_id = '{$me_id}',
				ml_log = '{$log}',
				ml_datetime = '".date('Y-m-d H:i:s')."'";
	sql_query($sql);
}

function map_get_action_types()
{
	return array(
		'parttime' => '아르바이트/외주'
	);
}

function map_normalize_action_type($type)
{
	return 'parttime';
}

function map_get_action_type_name($type)
{
	$types = map_get_action_types();
	return isset($types[$type]) ? $types[$type] : $type;
}

function get_map_action($action_id)
{
	global $g5;
	$action_id = (int)$action_id;
	return sql_fetch(" select * from {$g5['map_action_table']} where action_id = '{$action_id}' ");
}

function get_map_actions($ma_id, $use_only=true)
{
	global $g5;
	$ma_id = (int)$ma_id;
	$sql_use = $use_only ? " and action_use = '1' " : "";
	$result = sql_query(" select * from {$g5['map_action_table']} where ma_id = '{$ma_id}' {$sql_use} order by action_order asc, action_id asc ");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) {
		$list[] = $row;
	}
	return $list;
}

function map_format_minutes($minutes)
{
	$minutes = (int)$minutes;
	if($minutes <= 0) return "즉시";

	$h = floor($minutes / 60);
	$m = $minutes % 60;
	$result = "";
	if($h > 0) $result .= $h."시간";
	if($m > 0) $result .= ($result ? " " : "").$m."분";
	return $result ? $result : "0분";
}

function map_normalize_time($time, $default='00:00')
{
	$time = trim($time);
	if(preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
		return $time;
	}
	if(preg_match('/^24:00$/', $time)) {
		return '24:00';
	}
	return $default;
}

function map_time_to_minutes($time)
{
	$time = map_normalize_time($time);
	if($time == '24:00') return 1440;
	$parts = explode(':', $time);
	return ((int)$parts[0] * 60) + (int)$parts[1];
}

function map_is_time_between($now, $start, $end)
{
	$now = map_time_to_minutes($now);
	$start = map_time_to_minutes($start);
	$end = map_time_to_minutes($end);

	if($start == $end) return true;
	if($start < $end) {
		return ($now >= $start && $now < $end);
	}
	return ($now >= $start || $now < $end);
}

function map_is_action_available_time($action)
{
	if(!$action['available_time_use']) return true;
	return map_is_time_between(substr(G5_TIME_HIS, 0, 5), $action['available_start'], $action['available_end']);
}

function map_action_time_label($action)
{
	if(!$action['available_time_use']) return "24시간 가능";
	return map_normalize_time($action['available_start'])."~".map_normalize_time($action['available_end']);
}

function map_sql_affected_rows()
{
	global $g5;
	if(function_exists('mysqli_affected_rows') && G5_MYSQLI_USE) {
		return mysqli_affected_rows($g5['connect_db']);
	}
	return mysql_affected_rows($g5['connect_db']);
}

function map_get_work($work_id)
{
	global $g5;
	$work_id = (int)$work_id;
	return sql_fetch(" select * from {$g5['map_work_table']} where work_id = '{$work_id}' ");
}

function map_refresh_work_status($work)
{
	global $g5, $member;
	if(isset($work['work_id']) && $work['work_id'] && $work['reward_claimed'] == '0' && $work['work_status'] == 'WORKING' && $work['complete_at'] && $work['complete_at'] <= G5_TIME_YMDHIS) {
		sql_query(" update {$g5['map_work_table']} set work_status = 'COMPLETE' where work_id = '{$work['work_id']}' and work_status = 'WORKING' ");
		$work['work_status'] = 'COMPLETE';

		if(isset($member['mb_id']) && $member['mb_id']) {
			sql_query(" update {$g5['member_table']} set mb_board_call = '[SYSTEM]아르바이트 완료', mb_board_link = '".G5_URL."/map/' where mb_id = '{$member['mb_id']}' ");
		}
	}
	return $work;
}

function map_get_active_work($ch_id)
{
	global $g5;
	$ch_id = (int)$ch_id;
	$work = sql_fetch(" select * from {$g5['map_work_table']} where ch_id = '{$ch_id}' and reward_claimed = '0' order by work_id desc limit 0, 1 ");
	if(isset($work['work_id']) && $work['work_id']) {
		$work = map_refresh_work_status($work);
	}
	return $work;
}

function map_remaining_seconds($work)
{
	if(!$work['complete_at']) return 0;
	$remain = strtotime($work['complete_at']) - G5_SERVER_TIME;
	return $remain > 0 ? $remain : 0;
}

function map_format_seconds($seconds)
{
	$seconds = (int)$seconds;
	if($seconds <= 0) return "00:00:00";
	$h = floor($seconds / 3600);
	$m = floor(($seconds % 3600) / 60);
	$s = $seconds % 60;
	return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

function map_select_action_event($action, $allow_common=false)
{
	global $g5;
	$seed = rand(1, 100);
	$action_id = (int)$action['action_id'];
	$ma_id = (int)$action['ma_id'];
	$me_type = map_normalize_action_type($action['action_type']);
	$legacy_type = sql_real_escape_string($action['action_type']);
	$type_search = " and me_type = '{$me_type}' ";
	if($legacy_type && $legacy_type != $me_type) {
		$type_search = " and me_type in ('{$me_type}', '{$legacy_type}') ";
	}

	$sql_common = "
		select *
			from {$g5['map_event_table']}
			where	ma_id = '{$ma_id}'
				{$type_search}
				and action_id = '{$action_id}'
				and (me_per_s <= '{$seed}' and me_per_e >= '{$seed}')
				and me_use = '1'
				and (me_replay_cnt = 0 or me_replay_cnt > me_now_cnt)
			order by me_per_e asc, RAND()
			limit 0, 1
	";
	$me = sql_fetch($sql_common);

	if((!isset($me['me_id']) || !$me['me_id']) && $allow_common) {
		$me = sql_fetch("
			select *
				from {$g5['map_event_table']}
				where	ma_id = '{$ma_id}'
					{$type_search}
					and action_id = '0'
					and (me_per_s <= '{$seed}' and me_per_e >= '{$seed}')
					and me_use = '1'
					and (me_replay_cnt = 0 or me_replay_cnt > me_now_cnt)
				order by me_per_e asc, RAND()
				limit 0, 1
		");
	}

	return $me;
}

function map_get_event($me_id)
{
	global $g5;
	$me_id = (int)$me_id;
	return sql_fetch(" select * from {$g5['map_event_table']} where me_id = '{$me_id}' ");
}

function map_apply_event_reward($ch_id, $mb_id, $me, $rel_table, $rel_id, $rel_action, &$log)
{
	global $config;

	$item = array();
	if(isset($me['me_get_item']) && $me['me_get_item']) {
		$it_id = (int)$me['me_get_item'];
		$item = get_item($it_id);
		insert_inventory($ch_id, $it_id, $item);
		$log .= "/ 아이템 《{$item['it_name']}》 획득 ";
	}
	if(isset($me['me_get_money']) && $me['me_get_money']) {
		insert_point($mb_id, (int)$me['me_get_money'], "[MAP]{$me['me_title']}", $rel_table, $rel_id, $rel_action);
		$log .= "/ {$config['cf_money']} {$me['me_get_money']} {$config['cf_money_pice']} 획득";
	}
	return $item;
}

function map_replace_script_vars($text, $context=array())
{
	global $character;

	$name = isset($context['character_name']) ? $context['character_name'] : $character['ch_name'];
	$npc_name = isset($context['npc_name']) ? $context['npc_name'] : '';
	$map_name = isset($context['map_name']) ? $context['map_name'] : '';

	$replace = array(
		'{이름}' => $name,
		'{NPC이름}' => $npc_name,
		'{장소명}' => $map_name
	);

	return str_replace(array_keys($replace), array_values($replace), $text);
}

function map_compare_value($actual, $operator, $expected)
{
	switch($operator) {
		case '>=': return $actual >= $expected;
		case '>': return $actual > $expected;
		case '<=': return $actual <= $expected;
		case '<': return $actual < $expected;
		case '=': return $actual == $expected;
		case '!=': return $actual != $expected;
	}
	return false;
}

function map_evaluate_npc_choice_condition($choice, $ch_id, $mb_id)
{
	global $g5;
	$type = isset($choice['choice_check_type']) ? $choice['choice_check_type'] : '';
	if(!$type) return true;
	$target = isset($choice['choice_check_target']) ? $choice['choice_check_target'] : '';
	$operator = isset($choice['choice_check_operator']) ? $choice['choice_check_operator'] : '';
	$value = isset($choice['choice_check_value']) ? (int)$choice['choice_check_value'] : 0;
	if(!in_array($operator, array('>=', '>', '<=', '<', '=', '!='), true)) return false;
	if($type == 'stat') {
		$target = sql_real_escape_string($target);
		$row = sql_fetch(" select sc.sc_max from {$g5['status_table']} sc join {$g5['status_config_table']} st on sc.st_id = st.st_id where sc.ch_id = '".(int)$ch_id."' and st.st_name = '{$target}' ");
		return map_compare_value(isset($row['sc_max']) ? (int)$row['sc_max'] : 0, $operator, $value);
	}
	if($type == 'favor') return map_compare_value(map_get_npc_favor((int)$target, (int)$ch_id), $operator, $value);
	if($type == 'money') {
		$mb_id = sql_real_escape_string($mb_id);
		$row = sql_fetch(" select mb_point from {$g5['member_table']} where mb_id = '{$mb_id}' ");
		return map_compare_value(isset($row['mb_point']) ? (int)$row['mb_point'] : 0, $operator, $value);
	}
	if($type == 'item') {
		$row = sql_fetch(" select count(*) as cnt from {$g5['inventory_table']} where ch_id = '".(int)$ch_id."' and it_id = '".(int)$target."' ");
		return map_compare_value((int)$row['cnt'], $operator, $value);
	}
	return false;
}

function map_get_npc_encounter_chance($ma_id=0)
{
	global $g5;
	static $cache = array();

	$default = defined('MAP_NPC_ENCOUNTER_CHANCE') ? (int)MAP_NPC_ENCOUNTER_CHANCE : 15;
	$ma_id = (int)$ma_id;
	$chance = $default;

	if($ma_id > 0) {
		if(isset($cache[$ma_id])) return $cache[$ma_id];

		// PK 1건/컬럼 1개만 조회한다. 마이그레이션 전이라 컬럼이 없으면 기존 기본값으로 fallback한다.
		$ma = sql_fetch(" select ma_npc_chance from {$g5['map_table']} where ma_id = '{$ma_id}' ", false);
		if(is_array($ma) && isset($ma['ma_npc_chance']) && $ma['ma_npc_chance'] !== '') {
			$chance = (int)$ma['ma_npc_chance'];
		}
	}

	if($chance < 0) $chance = 0;
	if($chance > 100) $chance = 100;
	if($ma_id > 0) $cache[$ma_id] = $chance;
	return $chance;
}

function map_find_npc_by_name($name)
{
	global $g5;
	$name = trim((string)$name);
	if(!$name) return array();
	$name = sql_real_escape_string($name);
	return sql_fetch("
		select ch_id, ch_name, ch_body, ch_thumb
			from {$g5['character_table']}
			where ch_type = 'npc'
				and ch_name = '{$name}'
			limit 0, 1
	");
}

function map_find_npc_by_id($npc_id)
{
	global $g5;
	$npc_id = (int)$npc_id;
	if(!$npc_id) return array();
	return sql_fetch("
		select ch_id, ch_name, ch_body, ch_thumb
			from {$g5['character_table']}
			where ch_id = '{$npc_id}'
				and ch_type = 'npc'
	");
}

function map_extract_script_cast($content, $default_npc=array())
{
	$cast = array();
	$seen = array();
	$default_name = isset($default_npc['ch_name']) ? trim($default_npc['ch_name']) : '';
	$default_id = isset($default_npc['ch_id']) ? (int)$default_npc['ch_id'] : 0;
	if($default_name) {
		$cast[] = array(
			'id' => $default_id,
			'name' => $default_name,
			'img' => isset($default_npc['ch_body']) ? $default_npc['ch_body'] : ''
		);
		if($default_id) $seen['id:'.$default_id] = true;
		$seen['name:'.strtolower($default_name)] = true;
	}
	if(!$content) return $cast;

	preg_match_all('/\[NPC\s*:\s*([^\]]+)\]/i', $content, $matches);
	if(!isset($matches[1]) || !count($matches[1])) return $cast;

	$references = array();
	$id_list = array();
	$name_list = array();
	$reference_seen = array();

	foreach($matches[1] as $reference) {
		$reference = trim($reference);
		$reference = preg_replace('/\s+퇴장\s*$/u', '', $reference);
		if(!$reference) continue;

		if(preg_match('/^(\d+)\s*\|/', $reference, $id_match)) {
			$npc_id = (int)$id_match[1];
			if(!$npc_id) continue;
			$key = 'id:'.$npc_id;
			if(isset($seen[$key]) || isset($reference_seen[$key])) continue;
			$reference_seen[$key] = true;
			$references[] = array('type' => 'id', 'key' => $key, 'value' => $npc_id);
			$id_list[$npc_id] = $npc_id;
		} else {
			$name = trim($reference);
			$key = 'name:'.strtolower($name);
			if(isset($seen[$key]) || isset($reference_seen[$key])) continue;
			$reference_seen[$key] = true;
			$references[] = array('type' => 'name', 'key' => $key, 'value' => $name);
			$name_list[$key] = $name;
		}
	}

	$npc_by_id = array();
	if(count($id_list)) {
		global $g5;
		$result = sql_query("\n\t\t\tselect ch_id, ch_name, ch_body, ch_thumb\n\t\t\t\tfrom {$g5['character_table']}\n\t\t\t\twhere ch_type = 'npc'\n\t\t\t\t\tand ch_id in (".implode(',', array_map('intval', array_values($id_list))).")\n\t\t");
		for($i=0; $row = sql_fetch_array($result); $i++) $npc_by_id[(int)$row['ch_id']] = $row;
	}

	$npc_by_name = array();
	if(count($name_list)) {
		global $g5;
		$escaped = array();
		foreach($name_list as $name) $escaped[] = "'".sql_real_escape_string($name)."'";
		$result = sql_query("\n\t\t\tselect ch_id, ch_name, ch_body, ch_thumb\n\t\t\t\tfrom {$g5['character_table']}\n\t\t\t\twhere ch_type = 'npc'\n\t\t\t\t\tand ch_name in (".implode(',', $escaped).")\n\t\t\t\torder by ch_id asc\n\t\t");
		for($i=0; $row = sql_fetch_array($result); $i++) {
			$key = 'name:'.strtolower($row['ch_name']);
			if(!isset($npc_by_name[$key])) $npc_by_name[$key] = $row;
		}
	}

	foreach($references as $reference) {
		$npc = array();
		if($reference['type'] == 'id') {
			$npc_id = (int)$reference['value'];
			if(isset($npc_by_id[$npc_id])) $npc = $npc_by_id[$npc_id];
		} else if(isset($npc_by_name[$reference['key']])) {
			$npc = $npc_by_name[$reference['key']];
		}
		if(!isset($npc['ch_id']) || !$npc['ch_id']) continue;
		$key = 'id:'.(int)$npc['ch_id'];
		if(isset($seen[$key])) continue;
		$cast[] = array(
			'id' => (int)$npc['ch_id'],
			'name' => $npc['ch_name'],
			'img' => $npc['ch_body'],
			'position' => count($cast) === 0 ? 'center' : (count($cast) % 2 ? 'left' : 'right')
		);
		$seen[$key] = true;
	}
	return $cast;
}

function map_get_npc($npc_id)
{
	global $g5;
	$npc_id = (int)$npc_id;
	return sql_fetch("
		select ch_id, ch_name, ch_body, ch_thumb
			from {$g5['character_table']}
			where ch_id = '{$npc_id}'
				and ch_type = 'npc'
	");
}

function map_get_npc_favor($npc_id, $ch_id)
{
	$npc_id = (int)$npc_id;
	$ch_id = (int)$ch_id;
	if(function_exists('get_npc_point')) {
		return (int)get_npc_point($npc_id, $ch_id);
	}
	return 0;
}

function map_apply_npc_favor($npc_id, $ch_id, $value, $log='')
{
	$npc_id = (int)$npc_id;
	$ch_id = (int)$ch_id;
	$value = (int)$value;
	if(!$npc_id || !$ch_id || !$value || !function_exists('insert_npc_point')) {
		return 0;
	}
	insert_npc_point($npc_id, $ch_id, $value, $log);
	return $value;
}

function map_get_place_npcs($ma_id, $use_only=true)
{
	global $g5;
	$ma_id = (int)$ma_id;
	$sql_use = $use_only ? " and mn.mn_use = '1' " : "";
	$result = sql_query("
		select mn.*, ch.ch_name, ch.ch_body, ch.ch_thumb
			from {$g5['map_npc_place_table']} mn
			left join {$g5['character_table']} ch on mn.npc_id = ch.ch_id
			where mn.ma_id = '{$ma_id}'
				{$sql_use}
			order by mn.mn_order asc, mn.mn_id asc
	");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) {
		$list[] = $row;
	}
	return $list;
}

function map_select_place_npc($ma_id, $seed=null)
{
	global $g5;
	$ma_id = (int)$ma_id;
	$result = sql_query("
		select mn.*, ch.ch_name, ch.ch_body, ch.ch_thumb
			from {$g5['map_npc_place_table']} mn
			join {$g5['character_table']} ch on mn.npc_id = ch.ch_id and ch.ch_type = 'npc'
			where mn.ma_id = '{$ma_id}'
				and mn.mn_use = '1'
	");
	$list = array();
	for($total = 0; $row = sql_fetch_array($result);) {
		if((int)$row['mn_weight'] <= 0) continue;
		$total += (int)$row['mn_weight'];
		$list[] = $row;
	}
	if(!$total) return array();
	$pick = random_int(1, $total);
	foreach($list as $row) { $pick -= (int)$row['mn_weight']; if($pick <= 0) return $row; }
	return array();
}

function map_get_npc_scripts($mn_id, $use_only=true)
{
	global $g5;
	$mn_id = (int)$mn_id;
	$sql_use = $use_only ? " and script_use = '1' " : "";
	$result = sql_query("
		select *
			from {$g5['map_npc_script_table']}
			where mn_id = '{$mn_id}'
				{$sql_use}
			order by script_order asc, script_id asc
	");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) {
		$list[] = $row;
	}
	return $list;
}

function map_select_npc_script($mn_id, $ch_id, $npc_id)
{
	global $g5;
	$mn_id = (int)$mn_id;
	$ch_id = (int)$ch_id;
	$npc_id = (int)$npc_id;
	$favor = map_get_npc_favor($npc_id, $ch_id);
	$result = sql_query("
		select sc.*
			from {$g5['map_npc_script_table']} sc
			where sc.mn_id = '{$mn_id}'
				and sc.script_use = '1'
				and (sc.script_type != 'event' or exists (
					select 1 from {$g5['map_npc_choice_table']} ch
					where ch.script_id = sc.script_id and ch.choice_use = '1'
				))
	");
	$scripts = array(); $ids = array();
	for($i=0; $script = sql_fetch_array($result); $i++) { $scripts[] = $script; $ids[] = (int)$script['script_id']; if($script['script_require_script_id']) $ids[] = (int)$script['script_require_script_id']; }
	if(!count($scripts)) return array();
	$ids = array_values(array_unique(array_filter($ids)));
	$history = array();
	if(count($ids)) {
		$history_result = sql_query(" select script_id, max(claimed_at) as claimed_at from {$g5['map_npc_encounter_table']} where ch_id = '{$ch_id}' and enc_status = 'COMPLETE' and script_id in (".implode(',', $ids).") group by script_id ");
		for($i=0; $row = sql_fetch_array($history_result); $i++) $history[(int)$row['script_id']] = $row['claimed_at'];
	}
	$today = G5_TIME_YMD; $now = strtotime(G5_TIME_YMDHIS); $candidates = array(); $total = 0;
	foreach($scripts as $script) {
		if((int)$script['script_weight'] <= 0) continue;
		if($script['script_favor_s'] !== null && $script['script_favor_s'] !== '' && $favor < (int)$script['script_favor_s']) continue;
		if($script['script_favor_e'] !== null && $script['script_favor_e'] !== '' && $favor > (int)$script['script_favor_e']) continue;
		if($script['script_require_script_id'] && !isset($history[(int)$script['script_require_script_id']])) continue;
		$last = isset($history[(int)$script['script_id']]) ? $history[(int)$script['script_id']] : '';
		if($script['script_repeat_type'] == 'once' && $last) continue;
		if($script['script_repeat_type'] == 'daily' && $last && substr($last, 0, 10) == $today) continue;
		if($script['script_repeat_type'] == 'cooldown' && $last && $now - strtotime($last) < max(0, (int)$script['script_cooldown'])) continue;
		$total += (int)$script['script_weight']; $candidates[] = $script;
	}
	if(!$total) return array();
	$pick = random_int(1, $total);
	foreach($candidates as $script) { $pick -= (int)$script['script_weight']; if($pick <= 0) return $script; }
	return array();
}

function map_get_npc_scripts_by_place_ids($mn_ids)
{
	global $g5;
	$mn_ids = array_values(array_unique(array_filter(array_map('intval', $mn_ids))));
	if(!count($mn_ids)) return array();
	$result = sql_query(" select * from {$g5['map_npc_script_table']} where mn_id in (".implode(',', $mn_ids).") order by script_order asc, script_id asc ");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) $list[(int)$row['mn_id']][] = $row;
	return $list;
}

function map_get_npc_choices($script_id, $use_only=true)
{
	global $g5;
	$script_id = (int)$script_id;
	$sql_use = $use_only ? " and choice_use = '1' " : "";
	$result = sql_query("
		select *
			from {$g5['map_npc_choice_table']}
			where script_id = '{$script_id}'
				{$sql_use}
			order by choice_order asc, choice_id asc
	");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) {
		$list[] = $row;
	}
	return $list;
}

function map_get_npc_choices_by_script_ids($script_ids)
{
	global $g5;
	$script_ids = array_values(array_unique(array_filter(array_map('intval', $script_ids))));
	if(!count($script_ids)) return array();
	$result = sql_query(" select * from {$g5['map_npc_choice_table']} where script_id in (".implode(',', $script_ids).") order by choice_order asc, choice_id asc ");
	$list = array();
	for($i=0; $row = sql_fetch_array($result); $i++) $list[(int)$row['script_id']][] = $row;
	return $list;
}

function map_create_npc_encounter($ch_id, $mb_id, $ma_id, $mn_id, $npc_id, $script_id, $script_type)
{
	global $g5;
	$ch_id = (int)$ch_id;
	$ma_id = (int)$ma_id;
	$mn_id = (int)$mn_id;
	$npc_id = (int)$npc_id;
	$script_id = (int)$script_id;
	$mb_id = sql_real_escape_string($mb_id);
	$script_type = sql_real_escape_string($script_type);

	sql_query("
		insert into {$g5['map_npc_encounter_table']}
			set ch_id = '{$ch_id}',
				mb_id = '{$mb_id}',
				ma_id = '{$ma_id}',
				mn_id = '{$mn_id}',
				npc_id = '{$npc_id}',
				script_id = '{$script_id}',
				script_type = '{$script_type}',
				choice_id = '0',
				reward_claimed = '0',
				enc_status = 'START',
				created_at = '".G5_TIME_YMDHIS."'
	");
	return sql_insert_id();
}

function map_get_npc_encounter($enc_id)
{
	global $g5;
	$enc_id = (int)$enc_id;
	return sql_fetch("
		select enc.*, sc.script_title, sc.script_content, sc.script_favor_value, sc.script_type as origin_script_type, mn.ma_id as place_ma_id
			from {$g5['map_npc_encounter_table']} enc
			left join {$g5['map_npc_script_table']} sc on enc.script_id = sc.script_id
			left join {$g5['map_npc_place_table']} mn on enc.mn_id = mn.mn_id
			where enc.enc_id = '{$enc_id}'
	");
}

function map_vn_payload_marker($payload)
{
	echo '<!--MAP_VN_JSON-->'.json_encode($payload);
}

?>
