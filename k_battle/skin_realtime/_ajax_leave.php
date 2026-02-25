<?php
/**
 * 레이드 참가 취소 처리 AJAX
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
$rm_id = ses($_POST, 'rm_id', 0, 'int');

if ($ra_id === '') {
    echo json_encode(array('success' => false, 'message' => '레이드 ID가 없습니다.'));
    exit;
}

if ($rm_id <= 0) {
    echo json_encode(array('success' => false, 'message' => '유닛 ID가 없습니다.'));
    exit;
}

// 레이드 정보 조회
$raid = sql_fetch("SELECT * FROM {$g5['k_realtime_table']} WHERE ra_id = '{$ra_id}'");

if (empty($raid['ra_id'])) {
    echo json_encode(array('success' => false, 'message' => '존재하지 않는 레이드입니다.'));
    exit;
}

// 상태 체크 (진행 중이면 취소 불가)
$ra_state = ses($raid, 'ra_state', 0, 'int');
if ($ra_state >= 1) {
    echo json_encode(array('success' => false, 'message' => '진행 중인 레이드는 취소할 수 없습니다.'));
    exit;
}

// 전투 테이블 설정 (skin_realtime 폴더이므로 항상 realtime)
$battle_table = $g5['k_realtime_table'];

// 유닛 정보 확인 (본인 것인지 체크)
$unit = sql_fetch("
    SELECT rm_id, unit_id, unit_type FROM {$battle_table}_unit 
    WHERE rm_id = '{$rm_id}' 
      AND ra_id = '{$ra_id}' 
      AND unit_id = '{$ch_id}' 
      AND unit_type = 'ch'
");

if (empty($unit['rm_id'])) {
    echo json_encode(array('success' => false, 'message' => '참가 정보를 찾을 수 없습니다.'));
    exit;
}

// 유닛 삭제
$result = delete_k_battle_unit($rm_id);

if (!$result) {
    echo json_encode(array('success' => false, 'message' => '취소 처리에 실패했습니다.'));
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => '참가를 취소했습니다.'
));
