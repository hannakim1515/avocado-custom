<?php
include_once('./_common.php');

global $qu_cf;
$qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');

header('Content-Type: application/json; charset=UTF-8');

// 로그인 체크
if (!$is_member || !$character['ch_id']) {
    echo json_encode(array('success' => false, 'message' => '로그인이 필요합니다.'));
    exit;
}

$qu_id = ses($_POST, 'qu_id', 0, 'int');
$log_url = ses($_POST, 'log_url', '', 'raw'); // URL은 raw로 받기

if (!$qu_id) {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

// 퀘스트 정보 조회
$qu = get_quest($qu_id, $character['ch_id']);

if (!$qu['qu_id']) {
    echo json_encode(array('success' => false, 'message' => h($qc_title).'를 찾을 수 없습니다.'));
    exit;
}

// direct 타입인지 확인
if (ses($qu, 'qu_submit_type', 'mmb') != 'direct') {
    echo json_encode(array('success' => false, 'message' => '직접 완료가 불가능한 '.h($qc_title).'입니다.'));
    exit;
}

// 수행중 상태인지 확인
if ($qu['qh_state'] != '수행중') {
    echo json_encode(array('success' => false, 'message' => '수행중인 '.h($qc_title).'만 완료할 수 있습니다.'));
    exit;
}

// log 타입인 경우 URL 체크
if ($qu['qu_complete_type'] == 'log') {
    if (empty($log_url)) {
        echo json_encode(array('success' => false, 'message' => '로그 URL을 입력해주세요.'));
        exit;
    }
    
    // URL 형식 검증 (간단한 검증)
    $log_url = trim($log_url);
    if (strpos($log_url, 'http') !== 0 && strpos($log_url, '/') !== 0) {
        echo json_encode(array('success' => false, 'message' => '올바른 URL 형식이 아닙니다.'));
        exit;
    }
}

// complete_quest 함수로 완료 처리
$result = complete_quest($qu['qh_id'], $character['ch_id'], $log_url);

if ($result['success']) {
    $msg = $result['message']; // qu_end_msg
    if (!empty($result['reward_msg'])) {
        $msg .= "\n\n보상: " . $result['reward_msg'];
    }
    echo json_encode(array(
        'success' => true, 
        'message' => $msg
    ));
} else {
    echo json_encode(array(
        'success' => false, 
        'message' => $result['message']
    ));
}
