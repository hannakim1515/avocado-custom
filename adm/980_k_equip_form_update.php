<?php
$sub_menu = "980300";
include_once('./_common.php');

check_token();

$item_data_path = G5_DATA_PATH . "/item";
$item_data_url  = G5_DATA_URL  . "/item";

if (!is_dir($item_data_path)) {
    @mkdir($item_data_path, G5_DIR_PERMISSION, true);
    @chmod($item_data_path, G5_DIR_PERMISSION);
}

/* ---------- 입력값 안전 획득 ---------- */
$it_id = ses($_REQUEST, 'it_id', 0, 'int');
$w = ses($_POST, 'w', '', 'raw');
$qstr = isset($_REQUEST['qstr']) ? $_REQUEST['qstr'] : '';

/* 체크박스·텍스트 기본값 */
$it_use        =  ses($_POST, 'it_use', '', 'string');
$it_name       =  ses($_POST, 'it_name', '', 'string');
$it_content    =  ses($_POST, 'it_content', '', 'string');
$it_content2   =  ses($_POST, 'it_content2', '', 'string');
$ug_limit      =  ses($_POST, 'ug_limit', '', 'string');
$eq_type       =  ses($_POST, 'eq_type', '', 'string');
$it_use_sell   =  ses($_POST, 'it_use_sell', '', 'string');
$it_sell       =  ses($_POST, 'it_sell', 0, 'int');
$it_has        =  ses($_POST, 'it_has', '', 'string');
$it_use_recepi =  ses($_POST, 'it_use_recepi', '', 'string');
$it_seeker     =  ses($_POST, 'it_seeker', '', 'string');
$it_seeker_per_s =  ses($_POST, 'it_seeker_per_s', 0, 'int');
$it_seeker_per_e =  ses($_POST, 'it_seeker_per_e', 0, 'int');
$st_id         =  ses($_POST, 'st_id', 0, 'int');
$it_value      =  ses($_POST, 'it_value', 0, 'int');
$it_2          =  ses($_POST, 'it_2', 0, 'int');

/* 커스텀 강화 배열 */
$custom_ug_st  =  ses($_POST, 'custom_ug_st', array(), 'array');
$custom_ug_min =  ses($_POST, 'custom_ug_min', array(), 'array');
$custom_ug_max =  ses($_POST, 'custom_ug_max', array(), 'array');

/* ---------- 신규 it_id 산정 ---------- */
if ($w === '') {
    $tmp_row = sql_fetch("SELECT MAX(it_id) AS max_it_id FROM {$g5['item_table']}");
    $max_id  = isset($tmp_row['max_it_id']) ? (int)$tmp_row['max_it_id'] : 0;
    $it_id   = $max_id + 1; // AUTO_INCREMENT가 아니라면 임시 사용
} else {
    $it_id = (int)$it_id;
}

/* ---------- 파일 업로드(선택적) ---------- */
$it_img = '';
if (isset($_FILES['it_img_file']['name']) && $_FILES['it_img_file']['name'] !== '' && isset($_FILES['it_img_file']['tmp_name']) && is_uploaded_file($_FILES['it_img_file']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['it_img_file']['name'], PATHINFO_EXTENSION));
    $image_name = "item_" . $it_id . "_img." . $ext;
    upload_file($_FILES['it_img_file']['tmp_name'], $image_name, $item_data_path);
    $it_img = $item_data_url . "/" . $image_name;
}

$it_1 = '';
if (isset($_FILES['it_1_file']['name']) && $_FILES['it_1_file']['name'] !== '' && isset($_FILES['it_1_file']['tmp_name']) && is_uploaded_file($_FILES['it_1_file']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['it_1_file']['name'], PATHINFO_EXTENSION));
    $image_name = "item_" . $it_id . "_detail_img." . $ext;
    upload_file($_FILES['it_1_file']['tmp_name'], $image_name, $item_data_path);
    $it_1 = $item_data_url . "/" . $image_name;
}

/* 외부 경로 입력이 있으면 업로드 경로보다 우선 적용 */
if (ses($_POST, 'it_img', '', 'raw') !== '') {
    $it_img = ses($_POST, 'it_img', '', 'string');
}
if (ses($_POST, 'it_1', '', 'raw') !== '') {
    $it_1 = ses($_POST, 'it_1', '', 'string');
}

/* ---------- 커스텀 강화 문자열 ---------- */
$custom_sql = '';
if (!empty($custom_ug_st)) {
    $it_3 = implode("|", $custom_ug_st);
    $it_4 = implode("|", $custom_ug_min);
    $it_5 = implode("|", $custom_ug_max);
    $custom_sql .= ", it_3 = '{$it_3}', it_4 = '{$it_4}', it_5 = '{$it_5}'";
}

/* ---------- 공통 SET 구성: 빈 문자열로 기존 값 덮지 않도록 분기 ---------- */
$set = array();
$set[] = "it_use = '{$it_use}'";
$set[] = "it_type = '장비(K)'";
$set[] = "it_value = '{$it_value}'";
$set[] = "it_name = '{$it_name}'";
$set[] = "it_content = '{$it_content}'";
$set[] = "it_content2 = '{$it_content2}'";
$set[] = "ug_limit = '{$ug_limit}'";
$set[] = "eq_type = '{$eq_type}'";
$set[] = "it_use_sell = '{$it_use_sell}'";
$set[] = "it_sell = '{$it_sell}'";
$set[] = "it_has = '{$it_has}'";
$set[] = "it_use_recepi = '{$it_use_recepi}'";
$set[] = "it_seeker = '{$it_seeker}'";
$set[] = "it_seeker_per_s = '{$it_seeker_per_s}'";
$set[] = "it_seeker_per_e = '{$it_seeker_per_e}'";
$set[] = "st_id = '{$st_id}'";
$set[] = "it_2 = '{$it_2}'";

if ($it_img !== '') $set[] = "it_img = '{$it_img}'";
if ($it_1   !== '') $set[] = "it_1 = '{$it_1}'";

$sql_common = implode(",\n", $set) . $custom_sql;

/* ---------- insert / update ---------- */
if ($w === '') {
    $sql = "INSERT INTO {$g5['item_table']} SET it_id = '{$it_id}', {$sql_common}";
    sql_query($sql);
} else {
    $it_prev = sql_fetch("SELECT it_id, it_img, it_1 FROM {$g5['item_table']} WHERE it_id = '{$it_id}'");
    if (!isset($it_prev['it_id']) || !$it_prev['it_id']) {
        alert("아이템 정보가 존재하지 않습니다.");
    }
    $sql = "UPDATE {$g5['item_table']} SET {$sql_common} WHERE it_id = '{$it_prev['it_id']}'";
    sql_query($sql);

    /* 업로드로 교체된 경우에만 기존 로컬 파일 정리 */
    if ($it_img !== '' && isset($it_prev['it_img']) && $it_prev['it_img'] !== '' && $it_prev['it_img'] !== $it_img) {
        $prev_path = str_replace(G5_URL, G5_PATH, $it_prev['it_img']);
        if (strpos($prev_path, G5_PATH) === 0) @unlink($prev_path);
    }
    if ($it_1 !== '' && isset($it_prev['it_1']) && $it_prev['it_1'] !== '' && $it_prev['it_1'] !== $it_1) {
        $prev_path = str_replace(G5_URL, G5_PATH, $it_prev['it_1']);
        if (strpos($prev_path, G5_PATH) === 0) @unlink($prev_path);
    }
}

/* ---------- 리다이렉트 ---------- */
goto_url('./980_k_equip_list.php?' . $qstr, false);
