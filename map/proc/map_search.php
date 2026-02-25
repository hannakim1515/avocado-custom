<?php
include_once('./_common.php');

if(!$is_able_search) {
	@include(G5_PATH.'/map/inc/search_over.php');
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

	$seed = rand(0,100);
	$me = sql_fetch("
		select *
			from {$g5['map_event_table']}
			where	ma_id = '".$character['ma_id']."'
				and (me_per_s <= '{$seed}' and me_per_e >= '{$seed}')
				and me_use = '1'
				and (me_replay_cnt = 0 or me_replay_cnt > me_now_cnt)
			order by RAND()
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

		@include(G5_PATH.'/map/inc/search_event.php');
	} else {
		@include(G5_PATH.'/map/inc/search_none.php');
	}
}

?>