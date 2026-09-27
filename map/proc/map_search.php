<?php
include_once('./_common.php');

$use_vn = isset($_POST['vn']) ? (int)$_POST['vn'] : 0;

if(!$is_able_search) {
	if($use_vn) {
		map_vn_payload_marker(array(
			'type' => 'system',
			'ma_id' => (int)$character['ma_id'],
			'title' => '조사',
			'speaker' => '조사',
			'content' => '오늘 탐색 가능 횟수를 초과하였습니다.'
		));
	} else {
		@include(G5_PATH.'/map/inc/search_over.php');
	}
} else {
	// 탐색 횟수 업데이트
	sql_query("
		update {$g5['character_table']}
				set		ch_search = ch_search + 1,
						ch_search_date = '".G5_TIME_YMD."'
			where		ch_id = '{$character['ch_id']}'
	");

	$me_id = 0;
	$state = '';
	$me = array();
	$seed = rand(1,100);

	// NPC 조우는 현재 지역 ADM에서 설정한 확률(기본 15%)에서만 시도한다.
	// NPC가 발생하지 않거나 유효한 스크립트가 없으면 기존 MAP 이벤트 판정으로 자연스럽게 내려간다.
	$npc_seed = $use_vn ? random_int(1, 100) : 100;
	if($use_vn && $npc_seed <= map_get_npc_encounter_chance($character['ma_id'])) {
		$npc = map_select_place_npc($character['ma_id']);
		if(isset($npc['mn_id']) && $npc['mn_id']) {
			$script = map_select_npc_script($npc['mn_id'], $character['ch_id'], $npc['npc_id']);
			if(isset($script['script_id']) && $script['script_id']) {
				$enc_id = map_create_npc_encounter($character['ch_id'], $member['mb_id'], $character['ma_id'], $npc['mn_id'], $npc['npc_id'], $script['script_id'], $script['script_type']);
				if($script['script_type'] != 'event') {
					sql_query("
						update {$g5['map_npc_encounter_table']}
							set reward_claimed = '1',
								enc_status = 'COMPLETE',
								claimed_at = '".G5_TIME_YMDHIS."'
							where enc_id = '{$enc_id}'
					");
				}
				$map_name = get_map_name($character['ma_id']);
				$content = map_replace_script_vars($script['script_content'], array(
					'npc_name' => $npc['ch_name'],
					'map_name' => $map_name
				));
				$npc_cast = map_extract_script_cast($content, $npc);
				$choices = array();
				if($script['script_type'] == 'event') {
					$choice_list = map_get_npc_choices($script['script_id']);
					for($i=0; $i < count($choice_list); $i++) {
						$choices[] = array(
							'choice_id' => (int)$choice_list[$i]['choice_id'],
							'text' => $choice_list[$i]['choice_text']
						);
					}
				}
				$rewards = array();
				if($script['script_type'] != 'event' && isset($script['script_favor_value']) && (int)$script['script_favor_value'] != 0) {
					$favor_value = map_apply_npc_favor($npc['npc_id'], $character['ch_id'], (int)$script['script_favor_value'], "[MAP] {$script['script_title']}");
					if($favor_value) {
						$rewards[] = array(
							'type' => 'favor',
							'title' => '호감도 '.($favor_value > 0 ? '+' : '').$favor_value,
							'desc' => $npc['ch_name'],
							'img' => ''
						);
					}
				}

				set_map_log($character['ch_id'], $character['ch_name'], "[npc_search] {$npc['ch_name']} / {$script['script_title']}", 0);
				map_vn_payload_marker(array(
					'type' => $script['script_type'] == 'event' ? 'npc_event' : 'npc_dialogue',
					'encounter_id' => (int)$enc_id,
					'ma_id' => (int)$character['ma_id'],
					'title' => $script['script_title'],
					'speaker' => $npc['ch_name'],
					'npc_id' => (int)$npc['npc_id'],
					'npc_name' => $npc['ch_name'],
					'npc_img' => $npc['ch_body'],
					'npc_cast' => $npc_cast,
					'content' => $content,
					'choices' => $choices,
					'rewards' => $rewards
				));
				exit;
			}
		}
	}

	$me = sql_fetch("
		select *
			from {$g5['map_event_table']}
			where	ma_id = '".$character['ma_id']."'
				and action_id = '0'
				and (me_type = '' or me_type = 'search')
				and (me_per_s <= '{$seed}' and me_per_e >= '{$seed}')
				and me_use = '1'
				and (me_replay_cnt = 0 or me_replay_cnt > me_now_cnt)
			order by me_per_e asc, RAND()
			limit 0, 1
	");

	if($me['me_id']) {
		$log = "이벤트『{$me['me_title']}』획득 ({$me['me_content']})";
		
		//--------------------------- 보상 획득 처리 한다.
			if($me['me_get_item']) {
				$it_id = $me['me_get_item'];
				$item = get_item($it_id);
				insert_inventory($character['ch_id'], $it_id);
				$log .= "/ 아이템 《{$item['it_name']}》 획득 ";
			}
			if($me['me_get_money']) {
				insert_point($member['mb_id'], $me['me_get_money'], "[이벤트]{$me['me_title']}", 'money', time(), '');
				$log .= "/ {$config['cf_money']} {$me['me_get_money']} {$config['cf_money_pice']} 획득";
			}
		//--------------------------- 보상획득 처리 부분 종료
		// 이벤트 획득에 성공한 경우, 해당 이벤트를 실행한다.
		// 이벤트 획득 카운터 추가
		$sql = " update {$g5['map_event_table']}
					set me_now_cnt = me_now_cnt+1
				  where me_id = '{$me['me_id']}' ";
		sql_query($sql);
		set_map_log($character['ch_id'], $character['ch_name'], $log, $me['me_id']);

		if($use_vn) {
			$rewards = array();
			if($me['me_get_item'] && isset($item['it_name'])) {
				$rewards[] = array(
					'type' => 'item',
					'title' => "아이템 《{$item['it_name']}》 획득",
					'desc' => isset($item['it_content']) ? $item['it_content'] : '',
					'img' => isset($item['it_img']) ? $item['it_img'] : ''
				);
			}
			if($me['me_get_money']) {
				$rewards[] = array(
					'type' => 'money',
					'title' => "{$config['cf_money']} ".number_format($me['me_get_money'])." {$config['cf_money_pice']} 획득",
					'desc' => '',
					'img' => ''
				);
			}
			$content = map_replace_script_vars($me['me_content'], array('map_name' => get_map_name($character['ma_id'])));
			map_vn_payload_marker(array(
				'type' => 'map_event',
				'ma_id' => (int)$character['ma_id'],
				'title' => $me['me_title'],
				'speaker' => '조사',
				'content' => $content,
				'rewards' => $rewards
			));
		} else {
			@include(G5_PATH.'/map/inc/search_event.php');
		}
	} else {
		if($use_vn) {
			map_vn_payload_marker(array(
				'type' => 'none',
				'ma_id' => (int)$character['ma_id'],
				'title' => '조사',
				'speaker' => '조사',
				'content' => '열심히 조사해봤지만 아무것도 없었다.'
			));
		} else {
			@include(G5_PATH.'/map/inc/search_none.php');
		}
	}
}

?>
