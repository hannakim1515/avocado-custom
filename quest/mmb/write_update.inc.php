<?php
	// write_table에 qh_id 컬럼이 있는지 확인하고 없으면 추가
	$check_column = sql_query("SHOW COLUMNS FROM `{$write_table}` LIKE 'qh_id'");
	if (!sql_num_rows($check_column)) {
		sql_query("ALTER TABLE `{$write_table}` ADD COLUMN `qh_id` int(11) NOT NULL DEFAULT '0' AFTER `wr_id`", false);
	}
	
	$qh_id = ses($_REQUEST, 'qh_id', 0, 'int');
	if ($qh_id) {
		// 로그 링크 생성
		if (!$log_link) {
			$log_link = G5_BBS_URL . "/board.php?bo_table=" . $bo_table . "&log=" . ($wr_num * -1);
		}
		
		// 퀘스트 완료 처리 (멤버 퀘스트 완료 보상 포함)
		$quest_result = complete_quest($qh_id, $character['ch_id'], $log_link);
		
		if ($quest_result['success']) {
			$customer_sql .= $quest_result['customer_sql'];
		}
	}
?>