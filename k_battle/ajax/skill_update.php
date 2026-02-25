<?php
header('Content-Type: application/json');
require_once './_common.php';

/* 기본 구조 */
$data = array('result' => 'F');

/* 입력 방어 */
$cs_id = ses($_REQUEST, 'cs_id', 0, 'int');
$type  = ses($_REQUEST, 'type', '', 'raw');

if ($cs_id <= 0 || $type === '') {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 스킬 조회 및 소유권 확인 */
$sk = get_k_skill($cs_id, 'cs');
if (!is_array($sk)) $sk = array();
$ch_id_check = ses($character, 'ch_id', 0, 'int');
if (empty($sk['sk_id']) || !isset($character['ch_id']) || (int)$sk['ch_id'] !== $ch_id_check) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 분기 처리 */
$sql_set = '';

if ($type === 'custom') {
    // 커스텀 가능 여부 확인
    $sk_use = ses($sk, 'sk_use', '');
    if (strpos($sk_use, 'custom') === false) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 입력값 수집 및 이스케이프
    $cs_icon    = ses($_REQUEST, 'cs_icon', '', 'string');
    $cs_content = ses($_REQUEST, 'cs_content', '', 'string');
    $cs_name    = ses($_REQUEST, 'cs_name', '', 'string');
    $cs_img     = ses($_REQUEST, 'cs_img', '', 'string');

    $sql_set = "cs_icon='{$cs_icon}', cs_content='{$cs_content}', cs_name='{$cs_name}', cs_img='{$cs_img}'";

} elseif ($type === 'equip') {
    // 장착 토글
    $use = !empty($sk['cs_use']) ? 0 : 1;

    // 리스트에 추가될 아이템 이미지 보정
    $icon = ses($sk, 'sk_icon', '');
    if ($icon === '') {
        $icon = G5_URL . '/k_battle/img/default_skill.png';
    }
    if ($use === 1) {
        $data['in'] = "<li data-id='{$cs_id}' onclick=\"assetInfo({$cs_id},'skill')\"><img src='{$icon}'></li>";
    }

    $sql_set = "cs_use='{$use}'";
} else {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* 업데이트 실행 */
if ($sql_set !== '') {
    $sql = "UPDATE {$g5['k_ch_skill_table']} SET {$sql_set} WHERE cs_id = '{$cs_id}'";
    sql_query($sql);
    $data['result'] = 'S';
}

/* 응답 */
echo json_encode($data, JSON_UNESCAPED_UNICODE);
