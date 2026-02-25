<?php
$sub_menu = '980700';
include_once('./_common.php');

check_token();

$monster_path = G5_DATA_PATH . '/k_monster';
$monster_url  = G5_DATA_URL  . '/k_monster';

// 디렉터리 준비
if (!is_dir($monster_path)) {
    @mkdir($monster_path, G5_DIR_PERMISSION, true);
    @chmod($monster_path, G5_DIR_PERMISSION);
}

// 유틸
function k_image_upload($file_tmp, $file_name, $dest_dir, $prefix='monster') {
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if (!in_array($ext, array('gif','jpg','jpeg','png'), true)) {
        alert('이미지는 gif, jpg, png만 가능');
    }
    if (!is_uploaded_file($file_tmp)) {
        alert('유효하지 않은 업로드');
    }
    $new = $prefix . '_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.' . $ext;
    upload_file($file_tmp, $new, $dest_dir);
    return $new;
}

$msg = '';
$act_button = ses($_POST, 'act_button', '', 'raw');
$cur_raid_type = ses($_POST, 'raid_type', 'all', 'raw');

// 등록
if ($act_button === '등록') {

    $sql_common = '';

    // raid_type (CSV)
    $mo_raid_type_arr = ses($_POST, 'mo_raid_type', array(), 'array');
    $mo_raid_type = k_raid_type_csv($mo_raid_type_arr);

    // 썸네일
    if (isset($_FILES['mo_thumb']['name']) && $_FILES['mo_thumb']['name'] !== '') {
        $saved = k_image_upload($_FILES['mo_thumb']['tmp_name'], $_FILES['mo_thumb']['name'], $monster_path, 'monster');
        $image_url = $monster_url . '/' . $saved;
        $sql_common .= " , mo_thumb = '" . sql_escape_string($image_url) . "' ";
    }

    // 상태치 st_1 ~ st_10
    for ($h = 0; $h < 10; $h++) {
        $st_tag = 'st_' . ($h+1);
        $val = ses($_POST, $st_tag, '', 'string');
        $sql_common .= " , {$st_tag} = '{$val}' ";
    }

    $sql = "
        INSERT INTO {$g5['k_monster_table']}
        SET mo_name   = '" . ses($_POST, 'mo_name', '', 'string') . "',
            mo_1      = '" . ses($_POST, 'mo_1', '', 'string') . "',
            mo_hp     = '" . ses($_POST, 'mo_hp', '', 'string') . "',
            mo_mp     = '" . ses($_POST, 'mo_mp', '', 'string') . "',
            raid_type = '" . sql_escape_string($mo_raid_type) . "'
            {$sql_common}
    ";
    sql_query($sql);

// 선택수정
} elseif ($act_button === '선택수정') {

    $chk = ses($_POST, 'chk', array(), 'array');
    if (!count($chk)) {
        alert('수정하실 항목을 하나 이상 선택하세요.');
    }

    foreach ($chk as $k) {
        $k = (string)$k; // 배열 키 용
        $mo_id = ses($_POST['mo_id'], $k, 0, 'int');
        if ($mo_id <= 0) {
            $msg .= "{$mo_id} : 잘못된 키입니다.\\n";
            continue;
        }

        $sql_common = '';

        // 새 이미지 업로드
        if (isset($_FILES['mo_thumb']['name'][$k]) && $_FILES['mo_thumb']['name'][$k] !== '') {
            // 기존 파일 삭제
            $old_url = ses($_POST['old_mo_thumb'], $k, '', 'raw');
            if ($old_url) {
                $prev_file_path = str_replace(G5_URL, G5_PATH, $old_url);
                if (is_file($prev_file_path)) @unlink($prev_file_path);
            }
            $saved = k_image_upload($_FILES['mo_thumb']['tmp_name'][$k], $_FILES['mo_thumb']['name'][$k], $monster_path, 'monster');
            $image_url = $monster_url . '/' . $saved;
            $sql_common .= " , mo_thumb = '" . sql_escape_string($image_url) . "' ";
        }

        // 상태치 st_1 ~ st_10
        for ($h = 0; $h < 10; $h++) {
            $st_tag = 'st_' . ($h+1);
            $val = ses($_POST[$st_tag], $k, '', 'string');
            $sql_common .= " , {$st_tag} = '{$val}' ";
        }

        // raid_type 변경 (CSV)
        $mo_raid_type_arr = ses($_POST, 'mo_raid_type', array(), 'array');
        $mo_raid_type_row = isset($mo_raid_type_arr[$k]) ? $mo_raid_type_arr[$k] : array();
        $mo_raid_type = k_raid_type_csv($mo_raid_type_row);

        $sql = "
            UPDATE {$g5['k_monster_table']}
               SET mo_name   = '" . ses($_POST['mo_name'], $k, '', 'string') . "',
                   mo_1      = '" . ses($_POST['mo_1'], $k, '', 'string') . "',
                   mo_hp     = '" . ses($_POST['mo_hp'], $k, '', 'string') . "',
                   mo_mp     = '" . ses($_POST['mo_mp'], $k, '', 'string') . "',
                   raid_type = '{$mo_raid_type}'
                   {$sql_common}
             WHERE mo_id = '{$mo_id}'
        ";
        sql_query($sql);
    }

// 선택삭제
} elseif ($act_button === '선택삭제') {

    $chk = ses($_POST, 'chk', array(), 'array');
    if (!count($chk)) {
        alert('삭제하실 항목을 하나 이상 선택하세요.');
    }

    $ids = array();
    foreach ($chk as $k) {
        $id = ses($_POST['mo_id'], $k, 0, 'int');
        if ($id > 0) $ids[] = $id;
    }
    $ids = array_values(array_unique($ids));

    if ($ids) {
        $in = implode(',', $ids);
        // 필요시 이미지 파일 삭제를 원하면 여기서 조회 후 unlink 처리 가능
        sql_query("DELETE FROM {$g5['k_monster_table']} WHERE mo_id IN ({$in})");
    } else {
        alert('삭제할 대상이 없습니다.');
    }
}

if ($msg !== '') {
    alert($msg);
}

goto_url('./980_k_monster.php?raid_type='.urlencode($cur_raid_type).'&'.$qstr);
?>
