<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$memo_custom_sql = '';

// $wr_id_safe는 write_update.skin.php에서 (int)$wr_id로 정의됨
// 여기서는 상위 스코프에서 전달받음 (없으면 0)
if (!isset($wr_id_safe)) $wr_id_safe = ses($GLOBALS, 'wr_id', 0, 'int');

// ******************** 호출 관련, 호출 시 알람테이블에 기재한다. 확인은 Y / N
// 멤버 닉네임 서칭
// -- 치환 문자로 사용될 문자가 본문에 사용 시 지워준다. (오류 방지)
$str = str_replace("||", "", $wr_content);
$str = str_replace("&&", "", $str);

// -- 괄호를 치환한다.
$str = str_replace("[[", "||&&", $str);
$str = str_replace("]]", "&&", $str);

// explode 로 해당 문자만 추출 할 수 있도록 작업한다.
$str = explode("||", $str);
// -- 추출한 배열을 토대로 정규식으로 닉네임을 추출한다.

$call_pattern = "/&&(.*)&&/";
$mb_nick_array = array();

for($i=0; $i < count($str); $i++) { 
	preg_match_all($call_pattern, $str[$i], $matches);
	if($matches[1]) {
		$mb_nick_array[] = $matches[1][0];
	}
}

// 배열 중복값 처리
$mb_nick_array = array_unique($mb_nick_array);


if(count($mb_nick_array) > 0) { 
	// -- 괄호를 치환한다.
	$memo = str_replace("[[", "", $wr_content);
	$memo = str_replace("]]", "", $memo);
	$memo_escaped = sql_escape_string($memo);

	for($i=0; $i < count($mb_nick_array); $i++) { 
		// 회원 정보 있는지 여부 확인 (SQL Injection 방지)
		$nick_escaped = sql_escape_string($mb_nick_array[$i]);
		$memo_search = sql_fetch("select mb_id, mb_name from {$g5['member_table']} where mb_nick = '{$nick_escaped}' or  mb_name = '{$nick_escaped}'");
		if($memo_search['mb_id']) { 
			// 회원정보가 있을 시, 알람테이블에 저장한다.
			// 저장하기 전에 동일한 정보가 있는지 확인한다.
			// 저장정보 : wr_id / wr_num / bo_table/ mb_id / mb_name / re_mb_id / re_mb_name / ch_side / memo / bc_datetime

			$bc_sql_common = "
				wr_id = '{$wr_id_safe}',
				wr_num = '{$wr_num}',
				bo_table = '{$bo_table}',
				mb_id = '{$member['mb_id']}',
				mb_name = '".sql_escape_string($member['mb_nick'])."',
				re_mb_id = '{$memo_search['mb_id']}',
				re_mb_name = '".sql_escape_string($memo_search['mb_name'])."',
				ch_side = '{$character['ch_side']}',
				memo = '{$memo_escaped}',
				bc_datetime = '".G5_TIME_YMDHIS."'
			";

			
			// 동일 정보 있는지 확인 - wr_id/ bo_table / re_mb_id 로 판별
			$bc = sql_fetch(" select bc_id from {$g5['call_table']} where wr_id= '{$wr_id_safe}' and bo_table= '{$bo_table}' and re_mb_id = '{$memo_search['mb_id']}' and mb_id = '{$member['mb_id']}' ");
			
			if($bc['bc_id']) { 
				// 정보가 있을 경우
				$sql = " update {$g5['call_table']} set {$bc_sql_common} where bc_id = '{$bc['bc_id']}' ";
				sql_query($sql);
			} else { 
				// 정보가 없을 경우
				$sql = " insert into {$g5['call_table']} set {$bc_sql_common} ";
				sql_query($sql);

				// 회원 테이블에서 알람 업데이트를 해준다.
				// 실시간 호출 알림 기능
				$log_link = G5_BBS_URL."/board.php?bo_table=".$bo_table."&log=".($wr_num * -1);
				$sql = " update {$g5['member_table']} 
							set mb_board_call = '".$member['mb_nick']."',
								mb_board_link = '{$log_link}'
						where mb_id = '".$memo_search['mb_id']."' ";
				sql_query($sql);
			}
		} else { 
			// 회원정보가 없을 시, content 에 해당 닉네임을 블러 처리 하고
			// content 를 업데이트 한다.
			$wr_content = str_replace("[[".$mb_nick_array[$i]."]]", "[[???]]", $wr_content);
			$memo_custom_sql .= " , wr_content = '{$wr_content}' ";
		}
	}
}
// ******************** 호출 관련, 호출 시 해당 멤버에게 쪽지 보내기 기능 종료

if($w != 'cu') {
	$customer_sql = "
		{$memo_custom_sql}
	";

	// $use_item은 외부 입력일 수 있으므로 int 캠스팅
	$use_item = ses($GLOBALS, 'use_item', 0, 'int');
	$game = ses($GLOBALS, 'game', '', 'raw');
	if($use_item) { 
		$it = sql_fetch("select it.it_type, it.it_use_ever, it.it_name, it.it_id, it.it_value, it.it_content, it.it_content2 from {$g5['item_table']} it, {$g5['inventory_table']} inven where inven.in_id = '{$use_item}' and inven.it_id = it.it_id");

		// 아이템 제거
		if(!$it['it_use_ever']) { 
			// 영구성 아이템이 아닐 시, 사용했을 때 인벤에서 제거한다.
			delete_inventory($use_item);
		}
		
		// 아이템이 뽑기 아이템의 경우 
		if($it['it_type'] == '뽑기') { 
			$seed = rand(0, 100);

			// 템 검색 시작
			$item_result = sql_fetch("
				select re_it_id as it_id
					from {$g5['explorer_table']}
					where	it_id = '".$it['it_id']."'
						and (ie_per_s <= '{$seed}' and ie_per_e >= '{$seed}')
					order by RAND()
					limit 0, 1
			");
			
			if($item_result['it_id']) {
				// 아이템 획득에 성공한 경우, 해당 아이템을 인벤토리에 삽입
				// 아이템 획득에 성공 시
				$item_result['it_name'] = get_item_name($item_result['it_id']);
				insert_inventory($character['ch_id'], $item_result['it_id']);
				$item_log = "S||".$it['it_id']."||".$it['it_name']."||".$item_result['it_id']."||".$item_result['it_name'];
			} else { 
				$item_log = "F||".$it['it_id']."||".$it['it_name'];
			}
		} else {
			// 일반 아이템의 경우, 기본 사용 로그를 반환한다.
			$item_log = "D||".$it['it_id']."||".$it['it_name']."||".$it['it_type']."||".$it['it_value']."||".$it['it_content']."||".$it['it_content2'];
		}
		$customer_sql .= " , wr_item = '{$it['it_id']}', wr_item_log = '{$item_log}'";

	}

	if($game == "dice") {
		// 주사위 굴리기
		$dice1 = rand(1, 6);
		$dice2 = rand(1, 6);
		$customer_sql .= " , wr_dice1 = '{$dice1}',  wr_dice2 = '{$dice2}'";
	}

	$log = "";

	//--------------------------------------------------------
	//	탐색 : 아이템 사용 없이 행위만으로 아이템 획득 가능
	// - 아이템 획득 제한 체크 필요
	//----------------------------------------------------------

}
?>

<!--퀘스트 기능-->
<?php include(G5_PATH.'/quest/mmb/write_update.inc.php')?>
<!--퀘스트 기능 끝-->