<?php
/**
 * 레이드 참가 처리 AJAX
 */

include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

// 비회원 체크
if (!$is_member) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

// 캐릭터 체크
$ch_id = ses($character, 'ch_id', 0, 'int');
if ($ch_id <= 0) {
    echo json_encode(array('success' => false, 'message' => '캐릭터가 없습니다.'));
    exit;
}

// 파라미터
$ra_id = ses($_POST, 'ra_id', '', 'string');

if ($ra_id === '') {
    echo json_encode(array('success' => false, 'message' => '레이드 ID가 없습니다.'));
    exit;
}

// 레이드 정보 조회
$raid = sql_fetch("SELECT * FROM {$g5['k_realtime_table']} WHERE ra_id = '{$ra_id}'");

if (empty($raid['ra_id'])) {
    echo json_encode(array('success' => false, 'message' => '존재하지 않는 레이드입니다.'));
    exit;
}

// 상태 체크 (0:준비중만 참가 가능)
$ra_state = ses($raid, 'ra_state', 0, 'int');
if ($ra_state >= 1) {
    echo json_encode(array('success' => false, 'message' => '이미 진행 중인 레이드에는 참가할 수 없습니다.'));
    exit;
}

// 전투 테이블 설정 (skin_realtime 폴더이므로 항상 realtime)
$battle_table = $g5['k_realtime_table'];

// 인원 제한 체크 - 실제 유닛 카운트로 확인
$ra_limit = ses($raid, 'ra_limit', 0, 'int');
if ($ra_limit > 0) {
    $count_row = sql_fetch("SELECT COUNT(*) as cnt FROM {$battle_table}_unit WHERE ra_id = '{$ra_id}' AND unit_type = 'ch'");
    $current_count = ses($count_row, 'cnt', 0, 'int');
    if ($current_count >= $ra_limit) {
        echo json_encode(array('success' => false, 'message' => '참가 인원이 가득 찼습니다.'));
        exit;
    }
}

// 이미 참가 여부 확인
$exists = sql_fetch("
    SELECT rm_id FROM {$battle_table}_unit 
    WHERE ra_id = '{$ra_id}' 
      AND unit_id = '{$ch_id}' 
      AND unit_type = 'ch'
");

if (!empty($exists['rm_id'])) {
    echo json_encode(array('success' => false, 'message' => '이미 참가 중입니다.'));
    exit;
}

// 유닛 등록
$result = insert_k_battle_unit($ch_id, 'ch', 'realtime', $ra_id);

if (!$result) {
    echo json_encode(array('success' => false, 'message' => '참가 처리에 실패했습니다.'));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => '레이드에 참가했습니다.',
    'redirect' => G5_URL.'/k_battle/raid.php?ra_id='.urlencode($ra_id).'&raid_type=realtime'
));
