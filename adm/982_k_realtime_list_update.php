<?php
$sub_menu = "981100";
include_once './_common.php';

$chk = ses($_POST, 'chk', array(), 'array');
if (!count($chk)) {
    alert(ses($_POST, 'act_button', '', 'raw')." 하실 항목을 하나 이상 체크하세요.");
}

$act_button = ses($_POST, 'act_button', '', 'raw');

// 이미지 업로드 디렉토리
$upload_dir = G5_DATA_PATH . '/k_realtime/';
$upload_url = G5_DATA_URL . '/k_realtime/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

// 이미지 업로드 함수
function upload_raid_image($file_input, $ra_id, $prefix) {
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

// 선택수정
if ($act_button === "선택수정") {


    for ($i = 0; $i < count($chk); $i++) {
        // 실제 번호를 넘김
        $k = (int)$chk[$i];

        $ra_id      = ses($_POST['ra_id'], $k, '', 'string');
        $ra_title       = ses($_POST['ra_title'], $k, '', 'string');
        $ra_content     = ses($_POST['ra_content'], $k, '', 'string');
        $ra_list_img    = ses($_POST['ra_list_img'], $k, '', 'string');
        $ra_bg_img      = ses($_POST['ra_bg_img'], $k, '', 'string');
        
        // 목록 이미지 파일 업로드
        if (!empty($_FILES['ra_list_img_file']['name'][$k])) {
            $file_input = array(
                'name' => $_FILES['ra_list_img_file']['name'][$k],
                'tmp_name' => $_FILES['ra_list_img_file']['tmp_name'][$k],
                'error' => $_FILES['ra_list_img_file']['error'][$k]
            );
            $uploaded = upload_raid_image($file_input, $ra_id, 'list');
            if ($uploaded) {
                $ra_list_img = $uploaded;
            }
        }
        
        // 배경 이미지 파일 업로드
        if (!empty($_FILES['ra_bg_img_file']['name'][$k])) {
            $file_input = array(
                'name' => $_FILES['ra_bg_img_file']['name'][$k],
                'tmp_name' => $_FILES['ra_bg_img_file']['tmp_name'][$k],
                'error' => $_FILES['ra_bg_img_file']['error'][$k]
            );
            $uploaded = upload_raid_image($file_input, $ra_id, 'bg');
            if ($uploaded) {
                $ra_bg_img = $uploaded;
            }
        }
        
        $ra_type        = 'pve'; // ses($_POST['ra_type'], $k, 'pve', 'string');
        $ra_limit       = ses($_POST['ra_limit'], $k, 5, 'int');
        $ra_turn_type   = ses($_POST['ra_turn_type'], $k, 'speed', 'string');
        $ra_mo_auto     = ses($_POST['ra_mo_auto'], $k, 'auto', 'string');
        $ra_reload      = ses($_POST['ra_reload'], $k, 'turn', 'string');
        $ra_reload_time = ses($_POST['ra_reload_time'], $k, 5, 'int');
        $ra_system      = 'normal'; // ses($_POST['ra_system'], $k, 'normal', 'string');
        $ra_time_limit  = ses($_POST['ra_time_limit'], $k, 0, 'int');
        $ra_bgm         = ses($_POST['ra_bgm'], $k, '', 'string');
        $ra_bgm_volume  = ses($_POST['ra_bgm_volume'], $k, 30, 'int');
        $ra_bgm_type    = ses($_POST['ra_bgm_type'], $k, 'single', 'string');
        if (!in_array($ra_bgm_type, array('single', 'list'))) $ra_bgm_type = 'single';
        if ($ra_bgm_volume < 0) $ra_bgm_volume = 0;
        if ($ra_bgm_volume > 100) $ra_bgm_volume = 100;

        if ($ra_id === '') {
            continue;
        }

        $sql = " UPDATE {$g5['k_realtime_table']}
                    SET ra_title       = '{$ra_title}',
                        ra_content     = '{$ra_content}',
                        ra_list_img    = '{$ra_list_img}',
                        ra_bg_img      = '{$ra_bg_img}',
                        ra_type        = '{$ra_type}',
                        ra_limit       = '{$ra_limit}',
                        ra_turn_type   = '{$ra_turn_type}',
                        ra_mo_auto     = '{$ra_mo_auto}',
                        ra_reload      = '{$ra_reload}',
                        ra_reload_time = '{$ra_reload_time}',
                        ra_system      = '{$ra_system}',
                        ra_time_limit  = '{$ra_time_limit}',
                        ra_bgm         = '{$ra_bgm}',
                        ra_bgm_volume  = '{$ra_bgm_volume}',
                        ra_bgm_type    = '{$ra_bgm_type}'
                  WHERE ra_id          = '{$ra_id}' ";
        sql_query($sql);
    }

// 선택삭제
} elseif ($act_button === "선택삭제") {

    check_token();

    for ($i = 0; $i < count($_POST['chk']); $i++) {
        $k = (int)$_POST['chk'][$i];

        $ra_id = ses($_POST['ra_id'], $k, '', 'string');
        if ($ra_id === '') {
            continue;
        }

        // 레이드 테이블명 가져오기
        $battle_table = $g5['k_realtime_table'];

        // 관련 데이터 삭제 (유닛, 버프, 로그, 스킬)
        sql_query(" DELETE FROM {$battle_table}_unit  WHERE ra_id = '{$ra_id}' ");
        sql_query(" DELETE FROM {$battle_table}_buff  WHERE ra_id = '{$ra_id}' ");
        sql_query(" DELETE FROM {$battle_table}_log   WHERE ra_id = '{$ra_id}' ");
        sql_query(" DELETE FROM {$battle_table}_skill WHERE ra_id = '{$ra_id}' ");

        // 메인 레이드 삭제
        sql_query(" DELETE FROM {$g5['k_realtime_table']} WHERE ra_id = '{$ra_id}' ");
    }
}

// 목록으로 이동
$redirect_url = './982_k_realtime_list.php';
if ($qstr) {
    $redirect_url .= '?' . html_entity_decode($qstr);
}
goto_url($redirect_url);
