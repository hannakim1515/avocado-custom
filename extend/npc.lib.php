<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// NPC 호감도 관리
$g5['npc_table'] = G5_TABLE_PREFIX.'npc_setting'; // 셋팅
$g5['npc_item_table'] = G5_TABLE_PREFIX.'npc_item'; // NPC가 받을 아이템들
$g5['npc_log_table'] = G5_TABLE_PREFIX.'npc_log'; // 호감도작 로그 내역

if (!isset($config['cf_shop_npc'])) {
	// 상점 NPC 지정
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_shop_npc` int(11) NOT NULL default '0' AFTER `cf_10` ");
}
if(!sql_query(" DESC {$g5['npc_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['npc_table']}` (
		`ns_id` int(11) not null AUTO_INCREMENT,
		`ns_name` varchar(255) not null default '',
		`ns_talk` text not null ,
		`ns_talk_item` text not null ,
		`ns_talk_point` int(11) not null default '0',
		`ns_talk_max` int(11) not null default '0',
		`ns_talk_max_txt` text not null ,
		`ns_talk_hate_point` int(11) not null default '0',
		`ns_talk_hate` int(11) not null default '0',
		`ns_talk_hate_txt` text not null ,
		
		`ns_lv0_name` varchar(255) not null default '',
		`ns_lv0_point` int(11) not null default '0',
		`ns_lv0_txt` text not null ,
		`ns_lv0_item` int(11) not null default '0',
		`ns_lv0_cost` varchar(11) not null default '',
		`ns_lv0_color` varchar(255) not null default '',
		
		`ns_lv1_name` varchar(255) not null default '',
		`ns_lv1_point` int(11) not null null default '0',
		`ns_lv1_txt` text not null ,
		`ns_lv1_item` int(11) not null null default '0',
		`ns_lv1_cost` varchar(11) not null default '',
		`ns_lv1_color` varchar(255) not null default '',
		
		`ns_lv2_name` varchar(255) not null default '',
		`ns_lv2_point` int(11) not null null default '0',
		`ns_lv2_txt` text not null ,
		`ns_lv2_item` int(11) not null null default '0',
		`ns_lv2_cost` varchar(11) not null default '',
		`ns_lv2_color` varchar(11) not null default '',
		
		`ns_lv3_name` varchar(255) not null default '',
		`ns_lv3_point` int(11) not null null default '0',
		`ns_lv3_txt` text not null ,
		`ns_lv3_item` int(11) not null null default '0',
		`ns_lv3_cost` varchar(11) not null default '',
		`ns_lv3_color` varchar(11) not null default '',
		
		`ns_lv4_name` varchar(255) not null default '',
		`ns_lv4_point` int(11) not null null default '0',
		`ns_lv4_txt` text not null ,
		`ns_lv4_item` int(11) not null null default '0',
		`ns_lv4_cost` varchar(11) not null default '',
		`ns_lv4_color` varchar(11) not null default '',

		PRIMARY KEY (`ns_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['npc_item_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['npc_item_table']}` (
		`ni_id` INT(11) NOT NULL AUTO_INCREMENT ,
		`ch_id` INT(11) NOT NULL default '0',
		`it_id` INT(11) NOT NULL default '0',
		`it_name` VARCHAR(255) NOT NULL default '',
		`ni_value` INT(11) NOT NULL default '0',
		`ni_comment` VARCHAR(255) NOT NULL default '',
		`ni_max_count` INT(11) NOT NULL default '0',
		`ni_max_comment` VARCHAR(255) NOT NULL default '',
		`ni_max_is_total` INT(4) NOT NULL default '0',
		PRIMARY KEY (`ni_id`)
	) ", false);
}
if(!sql_query(" DESC {$g5['npc_log_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['npc_log_table']}` (
		`nl_id` INT(11) NOT NULL AUTO_INCREMENT ,
		`nl_item` INT(11) NOT NULL default '0',
		`ns_id` INT(11) NOT NULL default '0',
		`ni_id` INT(11) NOT NULL default '0',
		`ch_id` INT(11) NOT NULL default '0',
		`nl_log` VARCHAR(255) NOT NULL default '',
		`nl_value` INT(11) NOT NULL default '0',
		`it_id` INT(11) NOT NULL default '0',
		`ns_state` VARCHAR(255) NOT NULL default '',
		`nl_date` VARCHAR(255) NOT NULL default '',
		PRIMARY KEY ( `nl_id` )
	) ", false);
}

// 호감도 로그 기록
function insert_npc_log($data) {
	global $g5;

	$date = date('Y-m-d');

	$log_sql = " insert into {$g5['npc_log_table']}
					set	ni_id = '{$data['get_item']}',
						nl_item = '{$data['inven_item']}',
						ns_id = '{$data['npc']}',
						ch_id = '{$data['ch_id']}',

						nl_value = '{$data['value']}',
						it_id = '{$data['give']}',
						ns_state = '{$data['state']}',

						nl_log = '{$data['log']}',
						nl_date = '{$date}'";
	//echo $log_sql;
	sql_query($log_sql);
}

// 호감도 변동 처리
function insert_npc_point($ns_id, $ch_id, $value, $talk = '') {
	global $g5;

	$npc = get_npc_state($ns_id, $ch_id);
	$log_data = array(
		"get_item" => '',
		"npc" => $ns_id,
		"ch_id" => $ch_id,
		"value" => $value,
		"state" => $npc['state'],
		"log" => $talk
	);
	insert_npc_log($log_data);
}

// NPC 데이터 받아오기
function get_npc_data($ch_id) {
	global $g5;
	$result = sql_fetch("select * from {$g5['character_table']} ch LEFT JOIN {$g5['npc_table']} ns on ch.ch_id = ns.ns_id where ch.ch_id = '{$ch_id}'");
	return $result;
}


// 호감도 전체 포인트
function get_npc_point($ns_id, $ch_id) {
	global $g5;

	$result = sql_fetch("select SUM(nl_value) as total from {$g5['npc_log_table']} where ch_id = '{$ch_id}' and ns_id = '{$ns_id}'");
	return $result['total'] == null ? 0 : $result['total'];
}

// 호감도에 따른 상점가 변동 수치
function get_npc_cost($ns_id, $ch_id) {
	global $g5;

	$status = get_npc_state($ns_id, $ch_id);
	$status['sale'] = trim($status['sale']);

	if($status['sale'] != "" && $status['sale'] > 0) {
		return $status['sale'];
	} else {
		return 1;
	}
}

// 호감도 현재 상태
function get_npc_state($ns_id, $ch_id, $npc = '') {
	global $g5;

	if($npc == '') {
		$npc = get_npc_data($ns_id);
	}

	$point = get_npc_point($ns_id, $ch_id);
	$result = array();
	
	$data = array();
	$data_index = 0;

	if($npc['ns_lv0_name']) {
		$data[$data_index]['state'] = $npc['ns_lv0_name'];
		$data[$data_index]['level'] = 0;
		$data[$data_index]['item'] = $npc['ns_lv0_item'];
		$data[$data_index]['talk'] = trim($npc['ns_lv0_txt']);
		$data[$data_index]['sale'] = $npc['ns_lv0_cost'];
		$data[$data_index]['color'] = $npc['ns_lv0_color'];
		$data[$data_index]['now_point'] = $npc['ns_lv0_point'];
		$data_index++;
	}
	if($npc['ns_lv1_name']) {
		$data[$data_index]['state'] = $npc['ns_lv1_name'];
		$data[$data_index]['level'] = 1;
		$data[$data_index]['item'] = $npc['ns_lv1_item'];
		$data[$data_index]['talk'] = trim($npc['ns_lv1_txt']);
		$data[$data_index]['sale'] = $npc['ns_lv1_cost'];
		$data[$data_index]['color'] = $npc['ns_lv1_color'];
		$data[$data_index]['now_point'] = $npc['ns_lv1_point'];
		$data_index++;
	}
	if($npc['ns_lv2_name']) {
		$data[$data_index]['state'] = $npc['ns_lv2_name'];
		$data[$data_index]['level'] = 2;
		$data[$data_index]['item'] = $npc['ns_lv2_item'];
		$data[$data_index]['talk'] = trim($npc['ns_lv2_txt']);
		$data[$data_index]['sale'] = $npc['ns_lv2_cost'];
		$data[$data_index]['color'] = $npc['ns_lv2_color'];
		$data[$data_index]['now_point'] = $npc['ns_lv2_point'];
		$data_index++;
	}
	if($npc['ns_lv3_name']) {
		$data[$data_index]['state'] = $npc['ns_lv3_name'];
		$data[$data_index]['level'] = 3;
		$data[$data_index]['item'] = $npc['ns_lv3_item'];
		$data[$data_index]['talk'] = trim($npc['ns_lv3_txt']);
		$data[$data_index]['sale'] = $npc['ns_lv3_cost'];
		$data[$data_index]['color'] = $npc['ns_lv3_color'];
		$data[$data_index]['now_point'] = $npc['ns_lv3_point'];
		$data_index++;
	}
	if($npc['ns_lv4_name']) {
		$data[$data_index]['state'] = $npc['ns_lv4_name'];
		$data[$data_index]['level'] = 4;
		$data[$data_index]['item'] = $npc['ns_lv4_item'];
		$data[$data_index]['talk'] = trim($npc['ns_lv4_txt']);
		$data[$data_index]['sale'] = $npc['ns_lv4_cost'];
		$data[$data_index]['color'] = $npc['ns_lv4_color'];
		$data[$data_index]['now_point'] = $npc['ns_lv4_point'];
		$data_index++;
	}
	$data[$data_index] = $data[$data_index-1];

	for($i=0; $i < $data_index; $i++) {
		$d = $data[$i];
		$n_d = $data[$i+1];

		if($d['now_point'] <= $point) {
			$result = $d;
			$result['next_point'] = $n_d['now_point'];
			$result['next_state'] = $n_d;
		} else {
			break;
		}
	}

	$result['ready_talk'] = $npc['ns_talk'];
	$result['point'] = $point;
	$result['name'] = $npc['ch_name'];

	return $result;
}



?>