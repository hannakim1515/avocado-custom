<?php
include_once('./_common.php');

global $qu_cf;

$return_url = G5_URL . '/quest';
$qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
$max_give = (int)$qu_cf['qc_quarter_give_max'];
$max_take = (int)$qu_cf['qc_member_take_max'];

// 파라미터
$qu_title = ses($_POST, 'qu_title', '', 'string');
$qu_take_max = ses($_POST, 'qu_take_max', 1, 'int');
$qu_blind = ses($_POST, 'qu_blind', 0, 'int');
$qu_submit_type = ses($_POST, 'qu_submit_type', 'mmb', 'string');
$qu_content = ses($_POST, 'qu_content', '', 'string');
$qu_content2 = ses($_POST, 'qu_content2', '', 'string');
$qu_end_msg = ses($_POST, 'qu_end_msg', '', 'string');
$re_it_id = ses($_POST, 're_it_id', 0, 'int');

// 허용된 완료 방식 확인
$qc_member_quest_type = ses($qu_cf, 'qc_member_quest_type', 'mmb,direct', 'raw');
$allowed_submit_types = array_map('trim', explode(',', $qc_member_quest_type));

// qu_submit_type은 설정에서 허용된 것만 가능 (admin 불가)
if (!in_array($qu_submit_type, $allowed_submit_types) || $qu_submit_type == 'admin') {
    // 허용된 첫번째 타입으로 설정
    $qu_submit_type = !empty($allowed_submit_types) ? $allowed_submit_types[0] : 'mmb';
}

// 수행 인원 범위 제한 (1~max_take)
$qu_take_max = max(1, min($max_take, $qu_take_max));

if ($character['ch_give_quest'] >= $max_give) {
    alert("이미 ".$qc_title."를 충분히 올렸다.", $return_url);
}

// 보상 아이템 수량 체크
if ($re_it_id) {
    $it_cnt = sql_fetch("
        SELECT COUNT(*) AS cnt 
        FROM {$g5['inventory_table']} 
        WHERE it_id = '{$re_it_id}' 
          AND ch_id = '{$character['ch_id']}' 
          AND se_ch_id = '' 
    ");
    if ($it_cnt['cnt'] < $qu_take_max) {
        alert("보상 아이템의 수가 부족합니다.({$it_cnt['cnt']}개 보유/{$qu_take_max}개 필요)", $return_url);
    }
}

// 퀘스트 등록
sql_query("
    INSERT INTO {$g5['k_quest_table']} SET 
        qu_type = 'member',
        qu_submit_type = '{$qu_submit_type}',
        qu_title = '{$qu_title}',
        qu_take_max = '{$qu_take_max}',
        qu_blind = '{$qu_blind}',
        qu_content = '{$qu_content}',
        qu_content2 = '{$qu_content2}',
        qu_end_msg = '{$qu_end_msg}',
        event_id = '999',
        event_week = '12',
        it_id = '{$re_it_id}',
        qu_ch_id = '{$character['ch_id']}'
");
$qu_id = sql_insert_id();

// 보상 아이템 연결
if ($re_it_id) {
    move_quest_item($qu_id, 'insert', $re_it_id, $qu_take_max);
}

// 캐릭터 등록 카운트 증가
sql_query("
    UPDATE {$g5['character_table']} 
    SET ch_give_quest = ch_give_quest + 1 
    WHERE ch_id = '{$character['ch_id']}'
");

// 멤버 퀘스트 등록 보상 지급
$reward_result = give_quest_register_reward($character['ch_id']);
alert($qc_title."를 등록했다! 퀘스트 등록 보상:{$reward_result}", $return_url);
?>