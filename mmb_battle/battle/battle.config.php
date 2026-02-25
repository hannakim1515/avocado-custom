<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 배틀 가능 여부 체크
$is_able_battle = false;

// 글쓰기 페이지 여부 체크 (autosave_count 는 글쓰기 페이지에서만 불러옵니다.)
$is_page_write = isset($autosave_count) ? true : false;

// 리스트 페이지 여부 체크
$is_page_list = isset($list) && isset($notice_count) ? true : false;

// 저장 프로세스 여부 체크
$is_write_update = defined('G5_CAPTCHA') || $g5['title'] == '게시글 저장' ? true : false;

if($config['cf_use_status_battle']) {
	// 배틀 관련 기능을 사용합니다.
	// : 배틀 관련 설정값 가져오기
	$battle_config = get_battle_config();
	$is_able_battle = true;


	/**********************************************
		리스트 / 글쓰기 페이지 삽입 코드
	**********************************************/
	if($is_page_write || $is_page_list) {
		add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/battle/battle.style.css">', 0);
	}


	/**********************************************
		글쓰기 페이지 삽입 코드
	**********************************************/
	if($is_page_write) {
		include($board_skin_path.'/battle/write.action.php');

		if(!isset($write['wr_battle_ch_value'])) {
			// 배틀 로그 선공자 수치 저장하는 필드
			sql_query(" ALTER TABLE `{$write_table}` ADD `wr_battle_ch_value` int(11) NOT NULL default '0' AFTER `wr_log` ");

			// 배틀 로그 후공자 수치 저장하는 필드
			sql_query(" ALTER TABLE `{$write_table}` ADD `wr_battle_re_ch_value` int(11) NOT NULL default '0' AFTER `wr_log` ");
			// 배틀 로그 후공자 정보 저장
			sql_query(" ALTER TABLE `{$write_table}` ADD `wr_battle_re_ch` int(11) NOT NULL default '0' AFTER `wr_log` ");
		}
		
	}


	/**********************************************
		글 작성 업데이트 페이지 삽입 코드
	**********************************************/
	if($is_write_update) {

		if($action == 'BATTLE') {
			// 배틀을 시작하는 로그일 경우
			$ch = $character;
			$re_ch = get_character($battle_re_character);
			if($re_ch['ch_id']) {
				// 상대 캐릭터 정보가 확인되고
				// 대미지 옵션이 적용되고 있거나 내 HP가 충분할 경우
				$is_battle = true;
				$ch_hp = get_extra_hp($ch['ch_id']);
				$re_ch_hp = get_extra_hp($re_ch['ch_id']);

				if($battle_config['bc_damage_proc']) {
					// 대미지 정보가 활성화 되어 있을 경우
					// 나의 HP 확인해보기
					if($ch_hp['sc_value'] >= $ch_hp['sc_max']) {
						$is_battle = false;
						alert("현재 전투 불가 상태입니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
					}
					if($re_ch_hp['sc_value'] >= $re_ch_hp['sc_max']) {
						$is_battle = false;
						alert("대상이 전투 불가 상태입니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
					}
				}

				// -- 나의 선공 수치 가져오기
				$add_status = 0;
				if($use_item) { 
					$battle_it = get_inventory_item($use_item);
					if($battle_it['it_type'] == '공격력증가') {
						$add_status = $battle_it['it_value'];
					}
				}

				if($is_battle) {
					include($board_skin_path.'/battle/battle.result.php');
					$battle_sql .= " wr_log = '{$battle_log}' ";
					$battle_sql .= " , wr_battle_re_ch = '{$re_ch['ch_id']}' ";
					$battle_sql .= " , wr_battle_ch_value = '{$ch_value}' ";
					$battle_sql .= " , wr_battle_re_ch_value = '{$re_ch_value}' ";

					$sql = " update {$write_table}
								set {$battle_sql}
							  where wr_id = '{$wr_id}' ";
					sql_query($sql);
				}
			} else {
				alert("상대의 캐릭터 정보가 확인되지 않습니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
			}
		}

		if($action == 'BATTLE_ANSWER') {
			// 공격 대응할 경우의 코드
			$original_write = sql_fetch("select * from {$write_table} where wr_id = '{$wr_id}'");
			$ch = get_character($original_write['ch_id']);
			$re_ch = $character;

			if($ch['ch_id']) {
				$is_battle = true;
				$ch_hp = get_extra_hp($ch['ch_id']);
				$re_ch_hp = get_extra_hp($re_ch['ch_id']);

				if($battle_config['bc_damage_proc']) {
					// 대미지 정보가 활성화 되어 있을 경우
					// 나의 HP 확인해보기
					if($ch_hp['sc_value'] >= $ch_hp['sc_max']) {
						$is_battle = false;
						alert("대상이 전투 불가 상태입니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
					}
					if($re_ch_hp['sc_value'] >= $re_ch_hp['sc_max']) {
						$is_battle = false;
						alert("현재 전투 불가 상태입니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
					}
				}

				// -- 나의 선공 수치 가져오기
				$add_after_status = 0;
				if($use_item) { 
					$battle_it = get_inventory_item($use_item);
					if($battle_it['it_type'] == '공격력증가') {
						$add_after_status = $battle_it['it_value'];
					}
				}

				if($is_battle) {
					include($board_skin_path.'/battle/battle.result.php');

					$comment_battle_sql .= " wr_log = '{$battle_log}' ";

					$battle_sql .= " wr_battle_ch_value = '{$ch_value}' ";
					$battle_sql .= " , wr_battle_re_ch_value = '{$re_ch_value}' ";

					$sql = " update {$write_table}
								set {$battle_sql}
							  where wr_id = '{$wr_id}' ";
					sql_query($sql);

					$sql = " update {$write_table}
								set {$comment_battle_sql}
							  where wr_id = '{$comment_id}' ";
					sql_query($sql);
				}
			
			} else {
				alert("상대의 캐릭터 정보가 확인되지 않습니다.", G5_BBS_URL."/board.php?bo_table={$bo_table}");
			}

		}
	}



}


?>