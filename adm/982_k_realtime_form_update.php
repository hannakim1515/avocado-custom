<?php
$sub_menu = '930090';
include_once './_common.php';

// w, ra_id, POST 값 정리
$w        = ses($_REQUEST, 'w', '', 'raw');
$ra_id = ses($_POST, 'ra_id', '', 'raw');

// 필수 값 검증
if ($ra_id === '') {
    alert('TABLE명은 반드시 입력하세요.');
}
if (!preg_match('/^([A-Za-z0-9_]{1,20})$/', $ra_id)) {
    alert('TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)');
}
if (empty($_POST['ra_title'])) {
    alert('레이드 이름을 입력하세요.');
}

// 이미지 업로드 디렉토리
$upload_dir = G5_DATA_PATH . '/k_realtime/';
$upload_url = G5_DATA_URL . '/k_realtime/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// 이미지 업로드 함수
function upload_raid_image_form($file_input, $ra_id, $prefix) {
    global $upload_dir, $upload_url;
    
    if (empty($file_input['name']) || $file_input['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    
    // 확장자 검사
    $ext = strtolower(pathinfo($file_input['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allowed)) {
        return '';
    }
    
    // 파일명 생성
    $filename = $ra_id . '_' . $prefix . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file_input['tmp_name'], $filepath)) {
        return $upload_url . $filename;
    }
    
    return '';
}

// 입력 값 정리
$ra_title       = ses($_POST, 'ra_title', '', 'string');
$ra_content     = ses($_POST, 'ra_content', '', 'string');
$ra_list_img    = ses($_POST, 'ra_list_img', '', 'string');
$ra_bg_img      = ses($_POST, 'ra_bg_img', '', 'string');

// 목록 이미지 파일 업로드
if (!empty($_FILES['ra_list_img_file']['name'])) {
    $uploaded = upload_raid_image_form($_FILES['ra_list_img_file'], $ra_id, 'list');
    if ($uploaded) {
        $ra_list_img = $uploaded;
    }
}

// 배경 이미지 파일 업로드
if (!empty($_FILES['ra_bg_img_file']['name'])) {
    $uploaded = upload_raid_image_form($_FILES['ra_bg_img_file'], $ra_id, 'bg');
    if ($uploaded) {
        $ra_bg_img = $uploaded;
    }
}

// 유형 및 설정
$ra_type        = 'pve'; // ses($_POST, 'ra_type', 'pve', 'string');
$ra_limit       = ses($_POST, 'ra_limit', 5, 'int');
$ra_reload      = ses($_POST, 'ra_reload', 'turn', 'string');
$ra_reload_time = ses($_POST, 'ra_reload_time', 5, 'int');
$ra_turn_type   = ses($_POST, 'ra_turn_type', 'speed', 'string');
$ra_mo_auto     = ses($_POST, 'ra_mo_auto', 'auto', 'string');
$ra_system      = ses($_POST, 'ra_system', 'normal', 'string');
$ra_time_limit  = ses($_POST, 'ra_time_limit', 0, 'int');

// 보상 설정
$ra_reward_money = ses($_POST, 'ra_reward_money', 0, 'int');
$ra_reward_exp   = ses($_POST, 'ra_reward_exp', 0, 'int');
$ra_reward_item  = ses($_POST, 'ra_reward_item', '', 'string');
$ra_reward_title = ses($_POST, 'ra_reward_title', 0, 'int');

// BGM 설정
$ra_bgm        = ses($_POST, 'ra_bgm', '', 'string');
$ra_bgm_volume = ses($_POST, 'ra_bgm_volume', 30, 'int');
$ra_bgm_type   = ses($_POST, 'ra_bgm_type', 'single', 'string');
if (!in_array($ra_bgm_type, array('single', 'list'))) $ra_bgm_type = 'single';
if ($ra_bgm_volume < 0) $ra_bgm_volume = 0;
if ($ra_bgm_volume > 100) $ra_bgm_volume = 100;

$sql_common = "
    ra_title       = '{$ra_title}',
    ra_content     = '{$ra_content}',
    ra_list_img    = '{$ra_list_img}',
    ra_bg_img      = '{$ra_bg_img}',
    ra_type        = '{$ra_type}',
    ra_limit       = '{$ra_limit}',
    ra_reload      = '{$ra_reload}',
    ra_reload_time = '{$ra_reload_time}',
    ra_turn_type   = '{$ra_turn_type}',
    ra_mo_auto     = '{$ra_mo_auto}',
    ra_system      = '{$ra_system}',
    ra_time_limit  = '{$ra_time_limit}',
    ra_reward_money = '{$ra_reward_money}',
    ra_reward_exp   = '{$ra_reward_exp}',
    ra_reward_item  = '{$ra_reward_item}',
    ra_reward_title = '{$ra_reward_title}',
    ra_bgm          = '{$ra_bgm}',
    ra_bgm_volume   = '{$ra_bgm_volume}',
    ra_bgm_type     = '{$ra_bgm_type}'
";

// 신규 생성
if ($w === '') {

    $row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['k_realtime_table']} WHERE ra_id = '{$ra_id}'");
    if (!empty($row['cnt'])) {
        alert($ra_id.' 은(는) 이미 존재하는 TABLE 입니다.');
    }

    $sql = "
        INSERT INTO {$g5['k_realtime_table']}
            SET ra_id = '{$ra_id}',
                {$sql_common}
    ";
    sql_query($sql);

// 수정
} elseif ($w === 'u') {

    $sql = "
        UPDATE {$g5['k_realtime_table']}
           SET {$sql_common}
         WHERE ra_id = '{$ra_id}'
    ";
    sql_query($sql);
}

// &amp; 는 문자열 안에서는 실제 & 로 써야 함
goto_url("./982_k_realtime_form.php?w=u&ra_id={$ra_id}&{$qstr}");
