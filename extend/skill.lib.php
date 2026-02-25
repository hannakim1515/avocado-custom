<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**********************************************************
	스킬 관련 기본 셋팅
**********************************************************/

$g5['skill_table'] = G5_TABLE_PREFIX.'skill';
$g5['skill_has_table'] = G5_TABLE_PREFIX.'skill_has';
$g5['skill_level_table'] = G5_TABLE_PREFIX.'skill_level';

if(!sql_query(" DESC {$g5['skill_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['skill_table']}` (
		`sk_id` int(11) NOT NULL AUTO_INCREMENT,
		`sk_type` varchar(255) NOT NULL default '',
		`sk_cate` varchar(255) NOT NULL default '',
		`sk_name` varchar(255) NOT NULL default '',
		`sk_img` varchar(255) NOT NULL default '',
		`sk_status_code` varchar(255) NOT NULL default '',
		`sk_limit` int(11) NOT NULL default '0',
		`sk_keep_limit` int(11) NOT NULL default '0',
		`sk_function` varchar(255) NOT NULL default '',
		`sk_value_type` varchar(255) NOT NULL default '',
		`sk_target` varchar(255) NOT NULL default '',
		`sk_descript` text NOT NULL,
		`sk_mod_st_id` varchar(255) NOT NULL default '',
		`sk_mod_code` varchar(255) NOT NULL default '',
		`sk_mod_enermy` varchar(255) NOT NULL default '',
		`sk_mod_type` varchar(255) NOT NULL default '',
		`sk_def_type` varchar(255) NOT NULL default '',
		`sk_def_code` varchar(255) NOT NULL default '',
		`sk_def_enermy` varchar(255) NOT NULL default '',
		`sk_use_single` int(11) NOT NULL default '0',
		`sk_use_st_id` int(11) NOT NULL default '0',
		PRIMARY KEY (`sk_id`)
	) ", false);

	
	// 랭킹에 스킬 확득 제한 추가하기
	sql_query(" ALTER TABLE `{$g5['level_table']}` ADD `lv_skill_limit` text NOT NULL AFTER `lv_add_state` ");

	// 관리자에 스킬 획득 가능 갯수 넣기
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_skill_count` int(11) NOT NULL default '0' AFTER `cf_10` ");

	// 관리자에 스킬 최대 획득 가능 갯수 넣기
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_skill_count_max` int(11) NOT NULL default '0' AFTER `cf_skill_count` ");

	// 캐릭터 정보에 스킬 슬롯 갯수 넣기
	sql_query(" ALTER TABLE `{$g5['character_table']}` ADD `ch_skill_slot` int(11) NOT NULL default '0' AFTER `ch_point` ");
}
if(!sql_query(" DESC {$g5['skill_has_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['skill_has_table']}` (
		`sh_id` int(11) NOT NULL AUTO_INCREMENT,
		`sk_id` int(11) NOT NULL default '0',
		`ch_id` int(11) NOT NULL default '0',
		`sh_level` int(11) NOT NULL default '0',
		`sh_limit` int(11) NOT NULL default '0',
		`sh_name` varchar(255) NOT NULL default '',
		`sh_descript` text NOT NULL,
		`sh_use` int(11) NOT NULL default '0',
		`sh_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`sh_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['skill_level_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['skill_level_table']}` (
		`sl_id` int(11) NOT NULL AUTO_INCREMENT,
		`sk_id` int(11) NOT NULL default '0',
		`sl_level` int(11) NOT NULL default '0',
		`sl_name` varchar(255) NOT NULL default '',
		`sl_set_value` varchar(255) NOT NULL default '',
		`sl_use_value` varchar(255) NOT NULL default '',
		PRIMARY KEY (`sl_id`)
	) ", false);
}


if(!strstr($config['cf_item_category'], '스킬획득')) {
	$config['cf_item_category'] .= "||스킬획득";
}
if(!strstr($config['cf_item_category'], '스킬레벨업')) {
	$config['cf_item_category'] .= "||스킬레벨업";
}
if(!strstr($config['cf_item_category'], '스킬슬롯추가')) {
	$config['cf_item_category'] .= "||스킬슬롯추가";
}


/**********************************************************
	스킬 관리 리스트 출력
**********************************************************/

function skill_setting($ch_id, $ch = null) {
	global $g5, $member, $config;

	if(!$ch['ch_id']) {
		$ch = get_character($ch_id);
	}
	
	if($ch['ch_skill_slot'] < $config['cf_skill_count']) {
		sql_query(" update {$g5['character_table']} set ch_skill_slot = '{$config['cf_skill_count']}' where ch_id = '{$ch['ch_id']}'"); 
		$ch['ch_skill_slot'] = $config['cf_skill_count'];
	}


	$skill_list = get_skill_list($ch_id);
	$set_list = $skill_list['set'];
	$has_list = $skill_list['has'];

	$is_mine = $ch['mb_id'] == $member['mb_id'] ? true : false;

	// 최대 획득 가능한 슬롯 설정
	$max_slot = $ch['ch_skill_slot'];
	$maxium_slot = get_skill_maxium_count();

	if($max_slot > $maxium_slot) {
		$max_slot = $maxium_slot;
		sql_query(" update {$g5['character_table']} set ch_skill_slot = '{$maxium_slot}' where ch_id = '{$ch['ch_id']}'");
	}

	ob_start();
	include G5_PATH.'/skill/skill.inc.php';
	$content = ob_get_contents();
	ob_end_clean();

	return $content;
}

function get_skill_list($ch_id) {
	global $g5, $is_admin;

	$set_index = 0;
	$list = array();
	$list['set'] = array();
	$list['has'] = array();

	$skill_list = sql_query("select * from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl where sh.sk_id = sk.sk_id and sh.ch_id = '{$ch_id}' and sh.sh_level = sl.sl_level and sk.sk_id = sl.sk_id  order by sk.sk_type desc, sh.sh_id asc");

	for ($i=0; $row = sql_fetch_array($skill_list); $i++) {
		$row['st_id'] = 0;
		$row['st_name'] = '';
		$row['st_max'] = 0;
		$row['st_min'] = 0;
		$row['st_use_max'] = 0;
		$row['st_use_hp'] = 0;
		$row['st_order'] = 0;

		if($row['sk_use_st_id']) {
			$temp = sql_fetch("select * from {$g5['status_config_table']} where st_id = '{$row['sk_use_st_id']}'");
			$row['st_id'] = $temp['st_id'];
			$row['st_name'] = $temp['st_name'];
			$row['st_max'] = $temp['st_max'];
			$row['st_min'] = $temp['st_min'];
			$row['st_use_max'] = $temp['st_use_max'];
			$row['st_use_hp'] = $temp['st_use_hp'];
			$row['st_order'] = $temp['st_order'];
		}

		$list['has'][$i] = $row;
		if($row['sh_use']) {
			$list['set'][$set_index] = $row;
			$set_index++;
		}
	}
	return $list;
}

function get_skill_has_list($ch_id) {
	global $g5;

	$list = array();
	$skill_list = sql_query("select * from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl where sh.sk_id = sk.sk_id and sh.ch_id = '{$ch_id}' and sh.sh_level = sl.sl_level and sk.sk_id = sl.sk_id  order by sk.sk_type desc, sh.sh_id asc");
	for ($i=0; $row = sql_fetch_array($skill_list); $i++) {
		$row['st_id'] = 0;
		$row['st_name'] = '';
		$row['st_max'] = 0;
		$row['st_min'] = 0;
		$row['st_use_max'] = 0;
		$row['st_use_hp'] = 0;
		$row['st_order'] = 0;

		if($row['sk_use_st_id']) {
			$temp = sql_fetch("select * from {$g5['status_config_table']} where st_id = '{$row['sk_use_st_id']}'");
			$row['st_id'] = $temp['st_id'];
			$row['st_name'] = $temp['st_name'];
			$row['st_max'] = $temp['st_max'];
			$row['st_min'] = $temp['st_min'];
			$row['st_use_max'] = $temp['st_use_max'];
			$row['st_use_hp'] = $temp['st_use_hp'];
			$row['st_order'] = $temp['st_order'];
		}

		$list[$i] = $row;
	}
	return $list;
}
function get_skill_set_list($ch_id) {
	global $g5;

	$list = array();
	$skill_list = sql_query("select * from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl where sh.sk_id = sk.sk_id and sh.ch_id = '{$ch_id}' and sh.sh_level = sl.sl_level and sk.sk_id = sl.sk_id  and sh.sh_use = 1 order by sk.sk_type desc, sh.sh_id asc");
	for ($i=0; $row = sql_fetch_array($skill_list); $i++) {
		$row['st_id'] = 0;
		$row['st_name'] = '';
		$row['st_max'] = 0;
		$row['st_min'] = 0;
		$row['st_use_max'] = 0;
		$row['st_use_hp'] = 0;
		$row['st_order'] = 0;

		if($row['sk_use_st_id']) {
			$temp = sql_fetch("select * from {$g5['status_config_table']} where st_id = '{$row['sk_use_st_id']}'");
			$row['st_id'] = $temp['st_id'];
			$row['st_name'] = $temp['st_name'];
			$row['st_max'] = $temp['st_max'];
			$row['st_min'] = $temp['st_min'];
			$row['st_use_max'] = $temp['st_use_max'];
			$row['st_use_hp'] = $temp['st_use_hp'];
			$row['st_order'] = $temp['st_order'];
		}

		$list[$i] = $row;
	}
	return $list;
}
function get_skill_has_by_has_id($ch_id, $sh_id) {
	global $g5;

	$result = sql_fetch("select * from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl where sh.sh_id = '{$sh_id}' and sh.sk_id = sk.sk_id and sh.ch_id = '{$ch_id}' and sh.sh_level = sl.sl_level and sk.sk_id = sl.sk_id ");

	return $result;
}
function get_skill_has_by_skill_id($ch_id, $sk_id) {
	global $g5;

	$result = sql_fetch("select * from {$g5['skill_has_table']} sh, {$g5['skill_table']} sk, {$g5['skill_level_table']} sl where sk.sk_id = '{$sk_id}' and sh.sk_id = sk.sk_id and sh.ch_id = '{$ch_id}' and sh.sh_level = sl.sl_level and sk.sk_id = sl.sk_id ");

	return $result;
}


/**********************************************************
	스킬 관련 정보 함수
**********************************************************/

/*
-- 랭킹에 따른 스킬슬롯 자동 오픈 설정 시에 사용 하는 코드입니다.
-- 랭킹 업을 할 때 스킬 슬롯을 자동으로 오픈하고 싶으실 경우, 랭킹관리 페이지를 수정해야 합니다. (랭킹 정보 입력 시, 각 랭킹 별 슬롯을 입력하는 기능을 설정해주어야 합니다.)
function get_skill_max_count($lv_id) {
	global $g5, $config;

	$max_slot = sql_fetch("select lv_skill_limit from {$g5['level_table']} where lv_id = '{$lv_id}'");
	$max_slot = $max_slot['lv_skill_limit'];
	$max_slot = $max_slot ? $max_slot : $config['cf_skill_count'];

	return $max_slot;
}
*/

function get_skill_maxium_count() {
	global $g5, $config;
	$max_slot = $config['cf_skill_count_max'];
	return $max_slot;
}


/**********************************************************
	스킬 관련 액션 함수
**********************************************************/

// 신규 스킬 등록
function add_skill_inven($ch_id, $sk_id, $sh_level = 1) {
	global $g5;

	// 유효 체크
	$chk_id = sql_fetch("select count(*) as cnt, MAX(sl_level) as sl_level from {$g5['skill_level_table']} where sk_id = '{$sk_id}' and sl_level <= '{$sh_level}'");
	if($chk_id['cnt'] == 0) {
		$sh_level = 0;
	} else {
		$sh_level = $chk_id['sl_level'];
	}

	
	$sql = " insert into {$g5['skill_has_table']}
				set ch_id = '{$ch_id}',
					sk_id = '{$sk_id}',
					sh_level = '{$sh_level}'
	";
	sql_query($sql);
}


/**********************************************************
	아이템 기능 관련 추가 함수
**********************************************************/

// 기능을 가진 아이템 소지유무
function is_hasItem_func($ch_id, $it_type) {
	global $g5;

	$check = sql_fetch("select count(*) as cnt from {$g5['inventory_table']} inven, {$g5['item_table']} item where item.it_type = '{$it_type}' and inven.it_id = item.it_id and inven.ch_id = '{$ch_id}'");
	$check = $check['cnt'] > 0 ? true : false;

	return $check;
}

// 기능을 가진 아이템 검색하여 1개
function get_hasItem_func($ch_id, $it_type) {
	global $g5;
	$item = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where item.it_type = '{$it_type}' and inven.it_id = item.it_id and inven.ch_id = '{$ch_id}' order by in_id asc limit 0, 1");
	return $item;
}

// 스탯 최대값 감소 처리
function set_status_max($ch_id, $st_id, $hunt, $msg='') {
	global $g5;

	$result = array(); 
	
	$sl = sql_fetch("select st_id, st_max from {$g5['status_config_table']} where st_id = '{$st_id}'");
	$sc = sql_fetch("select sc_id, sc_value, sc_max from {$g5['status_table']} where ch_id = '{$ch_id}' and st_id = '{$sl['st_id']}'");

	$sc['sc_max'] = $sc['sc_max'] + $hunt; 

	$message = ""; 

	if($sc['sc_max'] >= $sl['st_max']) { 
		$message = $msg; 
		$sc['sc_max'] = $sl['st_max']; 
	} else if($sc['sc_max'] < 0) { 
		$sc['sc_max'] = 0; 
	} 
	sql_query(" update {$g5['status_table']} set sc_max = '{$sc['sc_max']}' where sc_id = '{$sc['sc_id']}'"); 

	return $message; 
}


?>