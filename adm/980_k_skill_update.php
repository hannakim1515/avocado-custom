<?php
$sub_menu = '980200';
include_once('./_common.php');

// 스킬 아이콘 파일 업로드 처리
function k_skill_upload_icon($file_key) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    
    $file = $_FILES[$file_key];
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_ext, true)) {
        return '';
    }
    
    // 업로드 디렉토리
    $upload_dir = G5_DATA_PATH . '/k_battle/skill/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }
    
    // 파일명 생성 (timestamp + random)
    $new_filename = 'skill_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
        return G5_DATA_URL . '/k_battle/skill/' . $new_filename;
    }
    
    return '';
}

// 입력 파라미터
$type       = ses($_REQUEST, 'type', '', 'raw');
$act_button = ses($_POST, 'act_button', '', 'raw');

$msg = '';

// 신규 등록
if ($type === 'insert') {
    $si_id         = ses($_POST, 'si_id', 0, 'int');
    $sk_name       = ses($_POST, 'sk_name', '', 'string');
    $sk_value      = ses($_POST, 'sk_value', '', 'string');
    $sk_content    = ses($_POST, 'sk_content', '', 'string');
    $sk_info       = ses($_POST, 'sk_info', '', 'string');
    $default_calc  = ses($_POST, 'default_calc', '', 'string');
    $bonus_calc    = ses($_POST, 'bonus_calc', '', 'string');
    $sk_turn       = ses($_POST, 'sk_turn', '', 'string');
    $sk_cool       = ses($_POST, 'sk_cool', '', 'string');
    $sk_target     = ses($_POST, 'sk_target', '', 'string');
    $sk_target_cnt = ses($_POST, 'sk_target_cnt', '', 'string');
    $sk_mp         = ses($_POST, 'sk_mp', '', 'string');
    $sc_id         = ses($_POST, 'sc_id', 0, 'int');
    $sk_icon       = ses($_POST, 'sk_icon', '', 'raw');
    $use           = ses($_POST, 'sk_use', '', 'raw');
    if ($use !== 'custom') { $use = ''; }
    $unit_type     = ses($_POST, 'unit_type', '', 'raw');
    if ($unit_type !== 'ch' && $unit_type !== 'mo') { $unit_type = ''; }
    $raid_type_arr = ses($_POST, 'raid_type', array(), 'array');
    $raid_type     = k_raid_type_csv($raid_type_arr);

    // 파일 업로드 처리 (파일이 있으면 URL 대신 파일 사용)
    $uploaded_icon = k_skill_upload_icon('sk_icon_file');
    if ($uploaded_icon !== '') {
        $sk_icon = $uploaded_icon;
    }

    if ($sk_target === 'passive' || $sk_target === 'self') {
        $sk_target_cnt = 'single';
    }
    if ($sk_icon === '') {
        // 파일경로를 저장해야 하면 G5_PATH, URL을 저장해야 하면 G5_URL 사용. 기존 관례에 맞추십시오.
        $sk_icon = G5_URL . '/k_battle/img/default_skill.png';
    }

    $sql = "
        INSERT INTO {$g5['k_skill_table']}
        SET si_id         = '{$si_id}',
            sk_name       = '{$sk_name}',
            sk_value      = '{$sk_value}',
            sk_content    = '{$sk_content}',
            sk_info       = '{$sk_info}',
            default_calc  = '{$default_calc}',
            bonus_calc    = '{$bonus_calc}',
            sk_turn       = '{$sk_turn}',
            sk_cool       = '{$sk_cool}',
            sk_target     = '{$sk_target}',
            sk_target_cnt = '{$sk_target_cnt}',
            sk_mp         = '{$sk_mp}',
            sc_id         = '{$sc_id}',
            sk_icon       = '{$sk_icon}',
            sk_use        = '".sql_escape_string($use)."',
            unit_type     = '{$unit_type}',
            raid_type     = '".sql_escape_string($raid_type)."'
    ";
    sql_query($sql);
    if ($msg === '') {
        $msg = '등록되었습니다.';
    } else {
        $msg .= '등록되었습니다.';
    }

// 설정 업데이트 또는 목록 수정/삭제
} else {
    if ($act_button === '업데이트') {
        $limit_skill = ses($_POST, 'limit_skill', 0, 'int');
        $skill_max   = ses($_POST, 'skill_max', 0, 'int');
        $skill_img   = ses($_POST, 'skill_img', '', 'string');
        // 테이블명 키 확인 필요: {$g5['k_battle_config']} 또는 {$g5['k_battle_config_table']}
        sql_query("
            UPDATE {$g5['k_battle_config']}
               SET limit_skill = '{$limit_skill}',
                   skill_max   = '{$skill_max}',
                   skill_img   = '{$skill_img}'
        ");
        $msg = '업데이트되었습니다.';

    } elseif ($act_button === '선택수정') {

        $chk = ses($_POST, 'chk', array(), 'array');
        if (!count($chk)) {
            alert('수정하실 항목을 하나 이상 선택하세요.');
        }

        foreach ($chk as $k) {
            $k = (string)$k;

            $sk_id        = ses($_POST['sk_id'], $k, 0, 'int');
            if ($sk_id <= 0) { continue; }

            $si_id        = ses($_POST['si_id'], $k, 0, 'int');
            $sk_name      = ses($_POST['sk_name'], $k, '', 'string');
            $sk_value     = ses($_POST['sk_value'], $k, '', 'string');
            $sk_content   = ses($_POST['sk_content'], $k, '', 'string');
            $sk_info      = ses($_POST['sk_info'], $k, '', 'string');
            $default_calc = ses($_POST['default_calc'], $k, '', 'string');
            $bonus_calc   = ses($_POST['bonus_calc'], $k, '', 'string');
            $sk_turn      = ses($_POST['sk_turn'], $k, '', 'string');
            $sk_cool      = ses($_POST['sk_cool'], $k, '', 'string');
            $sk_target    = ses($_POST['sk_target'], $k, '', 'string');
            $sk_target_cnt= ses($_POST['sk_target_cnt'], $k, '', 'string');
            $sk_mp        = ses($_POST['sk_mp'], $k, '', 'string');
            $sc_id        = ses($_POST['sc_id'], $k, 0, 'int');
            $target_sc    = ses($_POST['target_sc'], $k, 0, 'int');
            $sk_icon      = ses($_POST['sk_icon'], $k, '', 'string');
            $sk_use_val   = ses($_POST['sk_use'], $k, '', 'raw');
            $use          = ($sk_use_val === 'custom') ? 'custom' : '';
            $unit_type    = ses($_POST['unit_type'], $k, '', 'raw');
            if ($unit_type !== 'ch' && $unit_type !== 'mo') { $unit_type = ''; }
            $raid_type_arr = ses($_POST, 'raid_type', array(), 'array');
            $raid_type_row = isset($raid_type_arr[$k]) ? $raid_type_arr[$k] : array();
            $raid_type     = k_raid_type_csv($raid_type_row);

            // 파일 업로드 처리 (파일이 있으면 URL 대신 파일 사용)
            $uploaded_icon = k_skill_upload_icon('sk_icon_file_' . $k);
            if ($uploaded_icon !== '') {
                $sk_icon = $uploaded_icon;
            }

            sql_query("
                UPDATE {$g5['k_skill_table']}
                   SET si_id         = '{$si_id}',
                       sk_name       = '{$sk_name}',
                       sk_value      = '{$sk_value}',
                       sk_content    = '{$sk_content}',
                       sk_info       = '{$sk_info}',
                       default_calc  = '{$default_calc}',
                       bonus_calc    = '{$bonus_calc}',
                       sk_turn       = '{$sk_turn}',
                       sk_cool       = '{$sk_cool}',
                       sk_target     = '{$sk_target}',
                       sk_target_cnt = '{$sk_target_cnt}',
                       sk_mp         = '{$sk_mp}',
                       sc_id         = '{$sc_id}',
                       target_sc     = '{$target_sc}',
                       sk_icon       = '{$sk_icon}',
                       sk_use        = '{$use}',
                       unit_type     = '{$unit_type}',
                       raid_type     = '".sql_escape_string($raid_type)."'
                 WHERE sk_id        = '{$sk_id}'
            ");
        }
        $msg = '수정되었습니다.';

    } elseif ($act_button === '선택삭제') {

        $chk = ses($_POST, 'chk', array(), 'array');
        if (!count($chk)) {
            alert('삭제하실 항목을 하나 이상 선택하세요.');
        }

        $ids = array();
        foreach ($chk as $k) {
            $id = ses($_POST['sk_id'], $k, 0, 'int');
            if ($id > 0) $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));

        if ($ids) {
            $in = implode(',', $ids);
            sql_query("DELETE FROM {$g5['k_skill_table']} WHERE sk_id IN ({$in})");
            $msg = '삭제되었습니다.';
        } else {
            alert('삭제할 대상이 없습니다.');
        }
    }
}

if ($msg !== '') alert($msg);

// unit_type 필터 유지
$unit_type_filter = ses($_POST, 'unit_type_filter', '', 'raw');
if ($unit_type_filter !== 'ch' && $unit_type_filter !== 'mo') { $unit_type_filter = ''; }

// raid_type 필터 유지
$raid_type_filter = ses($_POST, 'raid_type_filter', '', 'raw');
if ($raid_type_filter !== '' && !isset($raid_types[$raid_type_filter]) && $raid_type_filter !== 'all') { 
    $raid_type_filter = ''; 
}

$url = './980_k_skill.php';
$url_params = array();
if ($unit_type_filter !== '') {
    $url_params[] = 'unit_type=' . urlencode($unit_type_filter);
}
if ($raid_type_filter !== '') {
    $url_params[] = 'raid_type=' . urlencode($raid_type_filter);
}
if (count($url_params) > 0) {
    $url .= '?' . implode('&', $url_params);
}
goto_url($url);
