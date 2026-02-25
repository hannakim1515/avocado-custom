<?php
include_once './_common.php';

check_admin_token();

$cur_raid_type = ses($_POST, 'raid_type', 'all', 'raw');

$act_button = ses($_POST, 'act_button', '', 'raw');
$chk = ses($_POST, 'chk', array(), 'array');

// 선택삭제
if ($act_button === '선택삭제') {
    if ($is_admin !== 'super') {
        alert('최고관리자만 삭제할 수 있습니다.');
    }

    $li_id_arr = ses($_POST, 'li_id', array(), 'array');

    foreach ($chk as $idx) {
        $li_id = ses($li_id_arr, $idx, 0, 'int');
        if ($li_id <= 0) continue;

        sql_query("DELETE FROM {$g5['k_raid_list']} WHERE li_id = '{$li_id}'", false);
    }

    goto_url('./981_k_raid_list.php?raid_type='.urlencode($cur_raid_type).'&'.$qstr);
}

// 추가/수정 (레거시 호환)
if ($act_button === '추가/수정') {

    // 신규 추가
    $new_li_title     = ses($_POST, 'new_li_title', '', 'string');
    $new_li_use       = ses($_POST, 'new_li_use', 1, 'int');
    $new_li_raid_type = ses($_POST, 'new_li_raid_type', 'realtime', 'string');
    $new_ra_ids       = ses($_POST, 'new_ra_ids', array(), 'array');

    if ($new_li_title !== '') {
        // ra_ids를 CSV로 변환 (각 값 이스케이프)
        $new_ra_ids_clean = array();
        foreach ($new_ra_ids as $ra_id) {
            $ra_id = trim($ra_id);
            if ($ra_id !== '') {
                $new_ra_ids_clean[] = sql_escape_string($ra_id);
            }
        }
        $new_ra_ids_csv = implode(',', $new_ra_ids_clean);

        sql_query("
            INSERT INTO {$g5['k_raid_list']} 
            (li_title, li_use, raid_type, ra_ids) 
            VALUES 
            ('{$new_li_title}', '{$new_li_use}', '{$new_li_raid_type}', '{$new_ra_ids_csv}')
        ", false);
    }

    // 기존 수정
    $li_id_arr        = ses($_POST, 'li_id', array(), 'array');
    $li_title_arr     = ses($_POST, 'li_title', array(), 'array');
    $li_use_arr       = ses($_POST, 'li_use', array(), 'array');
    $li_raid_type_arr = ses($_POST, 'li_raid_type', array(), 'array');
    $ra_ids_arr       = ses($_POST, 'ra_ids', array(), 'array');

    foreach ($li_id_arr as $idx => $li_id) {
        $li_id = (int)$li_id;
        if ($li_id <= 0) continue;

        $li_title     = ses($li_title_arr, $idx, '', 'string');
        $li_use       = ses($li_use_arr, $idx, 1, 'int');
        $li_raid_type = ses($li_raid_type_arr, $idx, 'realtime', 'string');
        $ra_ids       = ses($ra_ids_arr, $idx, array(), 'array');

        // ra_ids를 CSV로 변환
        $ra_ids_clean = array();
        foreach ($ra_ids as $ra_id) {
            $ra_id = trim($ra_id);
            if ($ra_id !== '') {
                $ra_ids_clean[] = sql_escape_string($ra_id);
            }
        }
        $ra_ids_csv = implode(',', $ra_ids_clean);

        sql_query("
            UPDATE {$g5['k_raid_list']} SET
                li_title  = '{$li_title}',
                li_use    = '{$li_use}',
                raid_type = '{$li_raid_type}',
                ra_ids    = '{$ra_ids_csv}'
            WHERE li_id = '{$li_id}'
        ", false);
    }

    goto_url('./981_k_raid_list.php?raid_type='.urlencode($cur_raid_type).'&'.$qstr);
}

// 신규추가 (분리된 폼)
if ($act_button === '신규추가') {
    $new_li_title     = ses($_POST, 'new_li_title', '', 'string');
    $new_li_use       = ses($_POST, 'new_li_use', 1, 'int');
    $new_li_raid_type = ses($_POST, 'new_li_raid_type', 'realtime', 'string');
    $new_ra_ids       = ses($_POST, 'new_ra_ids', array(), 'array');

    if ($new_li_title !== '') {
        $new_ra_ids_clean = array();
        foreach ($new_ra_ids as $ra_id) {
            $ra_id = trim($ra_id);
            if ($ra_id !== '') {
                $new_ra_ids_clean[] = sql_escape_string($ra_id);
            }
        }
        $new_ra_ids_csv = implode(',', $new_ra_ids_clean);

        sql_query("
            INSERT INTO {$g5['k_raid_list']} 
            (li_title, li_use, raid_type, ra_ids) 
            VALUES 
            ('{$new_li_title}', '{$new_li_use}', '{$new_li_raid_type}', '{$new_ra_ids_csv}')
        ", false);
    }

    goto_url('./981_k_raid_list.php?raid_type='.urlencode($cur_raid_type).'&'.$qstr);
}

// 선택수정
if ($act_button === '선택수정') {
    $li_id_arr        = ses($_POST, 'li_id', array(), 'array');
    $li_title_arr     = ses($_POST, 'li_title', array(), 'array');
    $li_use_arr       = ses($_POST, 'li_use', array(), 'array');
    $li_raid_type_arr = ses($_POST, 'li_raid_type', array(), 'array');
    $ra_ids_arr       = ses($_POST, 'ra_ids', array(), 'array');

    foreach ($chk as $idx) {
        $li_id = ses($li_id_arr, $idx, 0, 'int');
        if ($li_id <= 0) continue;

        $li_title     = ses($li_title_arr, $idx, '', 'string');
        $li_use       = ses($li_use_arr, $idx, 1, 'int');
        $li_raid_type = ses($li_raid_type_arr, $idx, 'realtime', 'string');
        $ra_ids       = ses($ra_ids_arr, $idx, array(), 'array');

        $ra_ids_clean = array();
        foreach ($ra_ids as $ra_id) {
            $ra_id = trim($ra_id);
            if ($ra_id !== '') {
                $ra_ids_clean[] = sql_escape_string($ra_id);
            }
        }
        $ra_ids_csv = implode(',', $ra_ids_clean);

        sql_query("
            UPDATE {$g5['k_raid_list']} SET
                li_title  = '{$li_title}',
                li_use    = '{$li_use}',
                raid_type = '{$li_raid_type}',
                ra_ids    = '{$ra_ids_csv}'
            WHERE li_id = '{$li_id}'
        ", false);
    }

    goto_url('./981_k_raid_list.php?raid_type='.urlencode($cur_raid_type).'&'.$qstr);
}

goto_url('./981_k_raid_list.php?raid_type='.urlencode($cur_raid_type));
?>
