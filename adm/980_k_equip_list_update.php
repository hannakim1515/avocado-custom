<?php
$sub_menu = "980301";
include_once('./_common.php');

$chk = ses($_POST, 'chk', array(), 'array');
if (count($chk) === 0) {
    alert(ses($_POST, 'act_button', '', 'raw') . " 하실 항목을 하나 이상 체크하세요.");
}

$act = ses($_POST, 'act_button', '', 'raw');

// 공통: 토큰 검사 권장
check_token();

if ($act === "선택수정") {

    $it_id_arr = ses($_POST, 'it_id', array(), 'array');
    foreach ($chk as $k) {
        // 방어코드: 인덱스 존재 확인
        if (!isset($it_id_arr[$k])) continue;

        $it_id = ses($_POST['it_id'], $k, 0, 'int');

        // 텍스트 입력값 이스케이프
        $it_name   = ses($_POST['it_name'], $k, '', 'string');
        $it_sell   = ses($_POST['it_sell'], $k, 0, 'int');
        $it_value  = ses($_POST['it_value'], $k, 0, 'int');
        $ug_limit_arr = ses($_POST, 'ug_limit', array(), 'array');
        $ug_limit  = isset($ug_limit_arr[$k]) && $ug_limit_arr[$k] !== '' ? ses($_POST['ug_limit'], $k, 0, 'int') : null;
        $eq_type   = ses($_POST['eq_type'], $k, '', 'string');

        // 체크박스: 전송 안 되면 0/공백 처리
        $it_use_recepi_arr = ses($_POST, 'it_use_recepi', array(), 'array');
        $it_has_arr        = ses($_POST, 'it_has', array(), 'array');
        $it_use_sell_arr   = ses($_POST, 'it_use_sell', array(), 'array');
        $it_use_arr        = ses($_POST, 'it_use', array(), 'array');
        $it_use_recepi = isset($it_use_recepi_arr[$k]) ? '1' : '0';
        $it_has        = isset($it_has_arr[$k]) ? '1' : '0';
        $it_use_sell   = isset($it_use_sell_arr[$k]) ? '1' : '0';
        $it_use        = isset($it_use_arr[$k]) ? 'Y' : '';
        $it_2          = ses($_POST['it_2'], $k, '', 'string');

        // ug_limit NULL 허용 처리
        $ug_limit_sql = is_null($ug_limit) ? "ug_limit = NULL" : "ug_limit = '{$ug_limit}'";

        $sql = "
            UPDATE {$g5['item_table']}
               SET it_category      = '일반',
                   it_name          = '{$it_name}',
                   it_sell          = '{$it_sell}',
                   it_use_recepi    = '{$it_use_recepi}',
                   it_has           = '{$it_has}',
                   it_use_sell      = '{$it_use_sell}',
                   it_type          = '장비(K)',
                   it_value         = '{$it_value}',
                   it_use           = '{$it_use}',
                   it_2             = '{$it_2}',
                   {$ug_limit_sql},
                   eq_type          = '{$eq_type}'
             WHERE it_id            = '{$it_id}'
        ";
        sql_query($sql);
    }

} elseif ($act === "선택삭제") {

    $it_id_arr = ses($_POST, 'it_id', array(), 'array');
    foreach ($chk as $k) {
        if (!isset($it_id_arr[$k])) continue;

        $temp_it_id = ses($_POST['it_id'], $k, 0, 'int');
        if ($temp_it_id <= 0) continue;

        // 이미지 경로 조회
        $it = sql_fetch("SELECT it_img FROM {$g5['item_table']} WHERE it_id = '{$temp_it_id}'");

        // 서버 파일이면 삭제 시도
        if (!empty($it['it_img'])) {
            $prev_file_path = str_replace(G5_URL, G5_PATH, $it['it_img']);
            if ($prev_file_path && @is_file($prev_file_path)) {
                @unlink($prev_file_path);
            }
        }

        // 연관 데이터 정리
        sql_query("DELETE FROM {$g5['item_table']}       WHERE it_id = '{$temp_it_id}'");
        sql_query("DELETE FROM {$g5['k_ch_equip_table']} WHERE it_id = '{$temp_it_id}'");
        sql_query("DELETE FROM {$g5['inventory_table']}  WHERE it_id = '{$temp_it_id}'");
        sql_query("DELETE FROM {$g5['order_table']}      WHERE it_id = '{$temp_it_id}'");
        sql_query("DELETE FROM {$g5['shop_table']}       WHERE it_id = '{$temp_it_id}'");
    }
}

goto_url('./980_k_equip_list.php');
