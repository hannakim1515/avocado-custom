<?php
include_once('./_common.php');

$sub_menu = '980320';
check_token();

$act_button = ses($_POST, 'act_button', '', 'raw');

if ($act_button === '등록') {
    $sql = "
        INSERT INTO {$g5['k_upgrade_table']}
        SET ug_name      = '".ses($_POST, 'ug_name', '', 'string')."',
            ug_per       = '".ses($_POST, 'ug_per', '', 'string')."',
            ug_min       = '".ses($_POST, 'ug_min', '', 'string')."',
            ug_max       = '".ses($_POST, 'ug_max', '', 'string')."',
            ch_rank      = '".ses($_POST, 'ch_rank', '', 'string')."',
            ug_use_it    = '".ses($_POST, 'ug_use_it', '', 'string')."',
            ug_use_money = '".ses($_POST, 'ug_use_money', '', 'string')."',
            ug_item      = '".ses($_POST, 'ug_item', '', 'string')."',
            ug_money     = '".ses($_POST, 'ug_money', '', 'string')."'
    ";
    sql_query($sql);

} else {
    $chk = ses($_POST, 'chk', array(), 'array');
    if (!count($chk)) {
        alert($act_button.' 하실 항목을 하나 이상 체크하세요.');
    }

    if ($act_button === '선택수정') {
        $msg = '';
        foreach ($chk as $k) {
            $ug_id = ses($_POST['ug_id'], $k, 0, 'int');
            if ($ug_id <= 0) {
                $msg .= $ug_id.' : 잘못된 키입니다.\\n';
                continue;
            }

            // 존재 확인
            $row = sql_fetch("SELECT ug_id FROM {$g5['k_upgrade_table']} WHERE ug_id = '{$ug_id}'");
            if (empty($row['ug_id'])) {
                $msg .= $ug_id.' : 기존 자료가 존재하지 않습니다.\\n';
                continue;
            }

            $sql = "
                UPDATE {$g5['k_upgrade_table']}
                SET ug_name      = '".ses($_POST['ug_name'], $k, '', 'string')."',
                    ug_per       = '".ses($_POST['ug_per'], $k, '', 'string')."',
                    ug_min       = '".ses($_POST['ug_min'], $k, '', 'string')."',
                    ug_max       = '".ses($_POST['ug_max'], $k, '', 'string')."',
                    ch_rank      = '".ses($_POST['ch_rank'], $k, '', 'string')."',
                    ug_use_it    = '".ses($_POST['ug_use_it'], $k, '', 'string')."',
                    ug_use_money = '".ses($_POST['ug_use_money'], $k, '', 'string')."',
                    ug_item      = '".ses($_POST['ug_item'], $k, '', 'string')."',
                    ug_color     = '".ses($_POST['ug_color'], $k, '', 'string')."',
                    ug_tag_color = '".ses($_POST['ug_tag_color'], $k, '', 'string')."',
                    ug_bg_color  = '".ses($_POST['ug_bg_color'], $k, '', 'string')."',
                    ug_money     = '".ses($_POST['ug_money'], $k, '', 'string')."'
                WHERE ug_id = '{$ug_id}'
            ";
            sql_query($sql);
        }
        if (!empty($msg)) alert($msg);

    } elseif ($act_button === '선택삭제') {
        // 유효 ug_id만 수집 후 일괄 삭제
        $ids = array();
        foreach ($chk as $k) {
            $id = ses($_POST['ug_id'], $k, 0, 'int');
            if ($id > 0) $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        if ($ids) {
            $in = implode(',', $ids);
            sql_query("DELETE FROM {$g5['k_upgrade_table']} WHERE ug_id IN ({$in})");
        } else {
            alert('삭제할 대상이 없습니다.');
        }
    }
}

goto_url('./980_k_equip_upgrade.php?type='.$type.'&'.$qstr);
