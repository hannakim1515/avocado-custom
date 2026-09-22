<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['battle_config_table'] = G5_TABLE_PREFIX.'battle_config';
$g5['battle_log_table'] = G5_TABLE_PREFIX.'battle_log';


if(!strstr($config['cf_item_category'], '공격력증가')) {
	$config['cf_item_category'] .= "||공격력증가";
}

if(!sql_query(" DESC {$g5['battle_config_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['battle_config_table']}` (
		`bc_id` int(11) NOT NULL AUTO_INCREMENT,
		`bc_proc` varchar(255) NOT NULL default '',
		`bc_before` varchar(255) NOT NULL default '',
		`bc_after` varchar(255) NOT NULL default '',
		`bc_damage_proc` varchar(255) NOT NULL default '',
		`bc_damage_type` varchar(255) NOT NULL default '',
		`bc_damage_min_point` int(11) NOT NULL default '0',
		`bc_damage_max_point` int(11) NOT NULL default '0',
		`bc_damage_before` varchar(255) NOT NULL default '',
		`bc_damage_after` varchar(255) NOT NULL default '',
		`bc_reward_proc` varchar(255) NOT NULL default '',
		`bc_reward_win_point` int(11) NOT NULL default '0',
		`bc_reward_win_exp` int(11) NOT NULL default '0',
		`bc_reward_win_item` int(11) NOT NULL default '0',
		`bc_reward_lose_point` int(11) NOT NULL default '0',
		`bc_reward_lose_exp` int(11) NOT NULL default '0',
		`bc_reward_lose_item` int(11) NOT NULL default '0',
		`bc_reward_both_point` int(11) NOT NULL default '0',
		`bc_reward_both_exp` int(11) NOT NULL default '0',
		`bc_reward_both_item` int(11) NOT NULL default '0',
		PRIMARY KEY (`bc_id`)
	) ", false);

	// 배틀기능 사용 여부 저장하기
	sql_query(" ALTER TABLE `{$g5['config_table']}` ADD `cf_use_status_battle` int(11) NOT NULL default '0' AFTER `cf_10` ");
}

if(!sql_query(" DESC {$g5['battle_log_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['battle_log_table']}` (
		`bl_id` int(11) NOT NULL AUTO_INCREMENT,
		`ch_id` int(11) NOT NULL default '0',
		`ch_name` varchar(255) NOT NULL default '',
		`re_ch_id` int(11) NOT NULL default '0',
		`re_ch_name` varchar(255) NOT NULL default '',
		`ch_point` int(11) NOT NULL default '0',
		`re_ch_point` int(11) NOT NULL default '0',
		`ch_damage` int(11) NOT NULL default '0',
		`re_ch_damage` int(11) NOT NULL default '0',
		`bl_win` int(11) NOT NULL default '0',
		`bl_lose` int(11) NOT NULL default '0',
		`bl_both` int(11) NOT NULL default '0',
		`bl_log` text NOT NULL,
		`bl_datetime` varchar(255) NOT NULL default '',
		PRIMARY KEY (`bl_id`)
	) ", false);
}

$battle_config = array();

// 배틀 기능 설정값 가져오기
function get_battle_config() {
	global $g5;
	$result = sql_fetch("select * from {$g5['battle_config_table']}");
	return $result;
}

// 전투 관련 수치 가져오기
// $type = before : 선행자 / after : 후행자
function get_battle_point($ch_id, $type, $pre_value=0, $last_value=0) {
	global $g5, $battle_config;

	if(!$battle_config['bc_id']) {
		$battle_config = get_battle_config();
	}

	if($type != 'before' && $type != 'after') {
		$type = '';
	}

	// 선/후행자의 값이 있을 경우 계산이 돌아간다.
	if($type != "" && $battle_config['bc_'.$type]) {
		$result = get_status_extra($battle_config['bc_'.$type], $ch_id, $prev_value, $last_value);
	} else {
		$result = null;
	}

	// 반환값
	// default : 기본 스탯 수치
	// is_cri : 크리티컬 여부
	// cri_value : 추가되는 크리티컬 수치
	// value : 최종 결과
	return $result;
}

// 전투 결과 가져오기
// ch_id : 선행자의 캐릭터 값 / ch_value : 선행자의 결과값 / re_ch_id : 후행자의 캐릭터 값 / re_ch_value : 후행자의 결과값
function get_battle_result($ch_id, $ch_value, $re_ch_id, $re_ch_value) {
	global $g5, $battle_config, $config;

	if(!$battle_config['bc_id']) {
		$battle_config = get_battle_config();
	}

	// 승패 결과 비교하기
	$win = 0;
	$lose = 0;
	$both = 0;

	$win_change_str = "";
	$lose_change_str = "";
	
	// 보상 지급 내역
	$ch_reward = "";
	$re_ch_reward = "";

	$ch_info = get_character_simple_info_battle($ch_id);
	$ch_mb_id = $ch_info['mb_id'];
	$ch_name = $ch_info['ch_name'];
	$re_ch_info = get_character_simple_info_battle($re_ch_id);
	$re_ch_mb_id = $re_ch_info['mb_id'];
	$re_ch_name = $re_ch_info['ch_name'];

	if($ch_value > $re_ch_value) {
		// 선행자가 이겼을 경우
		$win = $ch_id;
		$lose = $re_ch_id;

		$win_change_str = "ch_";
		$lose_change_str = "re_ch_";

	} else if($ch_value < $re_ch_value) {
		// 후행자가 이겼을 경우
		$win = $re_ch_id;
		$lose = $ch_id;

		$win_change_str = "re_ch_";
		$lose_change_str = "ch_";
	} else {
		// 비겼을 경우
		$both = 1;
	}

	// 대미지 산출하기
	$ch_damage = 0;
	$re_ch_damage = 0;

	if($battle_config['bc_damage_proc'] != '') {
		// 대미지가 산출되어 있을 경우
		$damage_basic = rand($battle_config['bc_damage_min_point'], $battle_config['bc_damage_max_point']);

		if($win_change_str && strstr($battle_config['bc_damage_proc'], "패자")) {
			// 패자한테만 대미지 적용
			$b = 0;
			$a = 0;

			if($battle_config['bc_damage_before']) {
				$b = get_status_extra($battle_config['bc_damage_before'], ${$win_change_str."id"});
				$b = $b['value'];
			}

			if($battle_config['bc_damage_after']) {
				$a = get_status_extra($battle_config['bc_damage_after'], ${$lose_change_str."id"});
				$a = $a['value'];
			}
			
			${$lose_change_str."damage"} = ($b - $a < 0 ? 0 : $b - $a);
			${$lose_change_str."damage"} += $damage_basic;
		} else if((!$win_change_str && strstr($battle_config['bc_damage_proc'], "비김")) ||  strstr($battle_config['bc_damage_proc'], "양쪽")) {
			// 양쪽 모두에 대미지 적용
			$b1 = 0;
			$a1 = 0;

			$b2 = 0;
			$a2 = 0;

			if($battle_config['bc_damage_before']) {
				$b1 = get_status_extra($battle_config['bc_damage_before'], $ch_id);
				$b1 = $b1['value'];

				$b2 = get_status_extra($battle_config['bc_damage_before'], $re_ch_id);
				$b2 = $b2['value'];
			}

			if($battle_config['bc_damage_after']) {
				$a1 = get_status_extra($battle_config['bc_damage_after'], $re_ch_id);
				$a1 = $a1['value'];

				$a2 = get_status_extra($battle_config['bc_damage_after'], $ch_id);
				$a2 = $a2['value'];
			}
			
			$ch_damage = ($b2 - $a2 < 0 ? 0 : $b2 - $a2);
			$re_ch_damage = ($b1 - $a1 < 0 ? 0 : $b1 - $a1);

			$damage_basic = rand($battle_config['bc_damage_min_point'], $battle_config['bc_damage_max_point']);
			$ch_damage += $damage_basic;

			$damage_basic = rand($battle_config['bc_damage_min_point'], $battle_config['bc_damage_max_point']);
			$re_ch_damage += $damage_basic;
		}
	}


	// ----------------------------------------------- 보상 지급하기
		// ▼ 골드 
		if($battle_config['bc_reward_win_point'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $win == $ch_id))) {
			// 승자 지급 골드
			insert_point(${$win_change_str."mb_id"}, $battle_config['bc_reward_win_point'], '[1:1 배틀 승리 보상] vs '.${$lose_change_str."name"}, 'battle', time(), '획득');
			${$win_change_str."reward"} .= "||".$config['cf_money']."+".$battle_config['bc_reward_win_point'];
		}
		if($battle_config['bc_reward_lose_point'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $lose == $ch_id))) {
			// 패자 지급 골드
			insert_point(${$lose_change_str."mb_id"}, $battle_config['bc_reward_lose_point'], '[1:1 배틀 패배 보상] vs '.${$win_change_str."name"}, 'battle', time(), '획득');
			${$lose_change_str."reward"} .= "||".$config['cf_money']."+".$battle_config['bc_reward_lose_point'];
		}
		if($both && $battle_config['bc_reward_both_point']) {
			// 비김 지급 골드
			insert_point($ch_mb_id, $battle_config['bc_reward_both_point'], '[1:1 배틀 비김 보상] vs '.$ch_name, 'battle', time(), '획득');
			$ch_reward .= "||".$config['cf_money']."+".$battle_config['bc_reward_both_point'];

			if($battle_config['bc_reward_proc'] != '선행자') {
				insert_point($re_ch_mb_id, $battle_config['bc_reward_both_point'], '[1:1 배틀 비김 보상] vs '.$re_ch_name, 'battle', time(), '획득');
				$re_ch_reward .= "||".$config['cf_money']."+".$battle_config['bc_reward_both_point'];
			}
		}

		// ▼ 경험치
		if($battle_config['bc_reward_win_exp'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $win == $ch_id))) {
			// 승자 지급 경험치
			insert_exp(${$win_change_str."id"}, $battle_config['bc_reward_win_exp'], '[1:1 배틀 승리 보상] vs '.${$lose_change_str."name"});
			${$win_change_str."reward"} .= "||".$config['cf_exp_name']."+".$battle_config['bc_reward_win_exp'];
		}
		if($battle_config['bc_reward_lose_exp'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $lose == $ch_id))) {
			// 패자 지급 경험치
			insert_exp(${$lose_change_str."id"}, $battle_config['bc_reward_lose_exp'], '[1:1 배틀 패배 보상] vs '.${$win_change_str."name"});
			${$lose_change_str."reward"} .= "||".$config['cf_exp_name']."+".$battle_config['bc_reward_lose_exp'];
		}
		if($both && $battle_config['bc_reward_both_exp']) {
			// 비김 지급 경험치
			insert_exp($ch_id, $battle_config['bc_reward_both_exp'], '[1:1 배틀 비김 보상] vs '.$ch_name);
			$ch_reward .= "||".$config['cf_exp_name']."+".$battle_config['bc_reward_both_exp'];

			if($battle_config['bc_reward_proc'] != '선행자') {
				insert_exp($re_ch_id, $battle_config['bc_reward_both_exp'], '[1:1 배틀 비김 보상] vs '.$re_ch_name);
				$re_ch_reward .= "||".$config['cf_exp_name']."+".$battle_config['bc_reward_both_exp'];
			}
		}

		// ▼ 아이템
		if($battle_config['bc_reward_win_item'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $win == $ch_id))) {
			// 승자 지급 아이템
			insert_inventory(${$win_change_str."id"}, $battle_config['bc_reward_win_item']);
			${$win_change_str."reward"} .= "||아이템+".get_item_name($battle_config['bc_reward_win_item']);
		}
		if($battle_config['bc_reward_lose_item'] && ($battle_config['bc_reward_proc'] == '전부' || ($battle_config['bc_reward_proc'] == '선행자' && $lose == $ch_id))) {
			// 패자 지급 아이템
			insert_inventory(${$lose_change_str."id"}, $battle_config['bc_reward_win_item']);
			${$lose_change_str."reward"} .= "||아이템+".get_item_name($battle_config['bc_reward_win_item']);
		}
		if($both && $battle_config['bc_reward_both_item']) {
			// 비김 지급 아이템
			insert_inventory($ch_id, $battle_config['bc_reward_both_item']);
			$ch_reward .= "||아이템+".get_item_name($battle_config['bc_reward_both_item']);
			
			if($battle_config['bc_reward_proc'] != '선행자') {
				insert_inventory($re_ch_id, $battle_config['bc_reward_both_item']);
				$re_ch_reward .= "||아이템+".get_item_name($battle_config['bc_reward_both_item']);
			}
		}
	//----------------------------------

	$result['win'] = $win;
	$result['lose'] = $lose;
	$result['both'] = $both;

	$result['ch_damage'] = $ch_damage;
	$result['re_ch_damage'] = $re_ch_damage;

	$result['ch_reward'] = $ch_reward."||";
	$result['re_ch_reward'] = $re_ch_reward."||";


	// 배틀 로그 내역에 기록하기
	$sql = "insert into {$g5['battle_log_table']}
			set ch_id = '{$ch_id}',
				ch_name = '{$ch_name}',
				re_ch_id = '{$re_ch_id}',
				re_ch_name = '{$re_ch_name}',
				ch_point = '{$ch_value}',
				re_ch_point = '{$re_ch_value}',
				ch_damage = '{$ch_damage}',
				re_ch_damage = '{$re_ch_damage}',
				bl_win = '{$win}',
				bl_lose = '{$lose}',
				bl_both = '{$both}',
				bl_log = '{$ch_reward}||@@{$re_ch_reward}||',
				bl_datetime = '".date('Y-m-d H:i:s')."'";
	sql_query($sql);
	return $result;
}

// 캐릭터 소유주 ID 가져오기
function get_character_simple_info_battle($ch_id)
{
	global $g5;
	$character = sql_fetch("select mb_id, ch_name from {$g5['character_table']} where ch_id ='{$ch_id}'");
	return $character;
}



?>