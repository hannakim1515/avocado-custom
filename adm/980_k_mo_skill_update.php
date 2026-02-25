<?php
$sub_menu = '981410';
include_once('./_common.php');

check_token();

// 입력 파라미터
$type       = ses($_REQUEST, 'type', '', 'raw');
$act_button = ses($_POST, 'act_button', '', 'raw');

$msg = '';

// 리다이렉트용 쿼리스트링
$redirect_params = array();
$redirect_params['mo_id'] = ses($_REQUEST, 'mo_id', 0, 'int');
$redirect_params['sfl']   = ses($_REQUEST, 'sfl', '', 'raw');
$redirect_params['stx']   = ses($_REQUEST, 'stx', '', 'raw');
$redirect_params['page']  = ses($_REQUEST, 'page', 1, 'int');

// 신규 등록
if ($type === 'insert') {
    $mo_id       = ses($_POST, 'mo_id', 0, 'int');
    $sk_id       = ses($_POST, 'sk_id', 0, 'int');
    $cs_use      = ses($_POST, 'cs_use', 1, 'int');
    $cs_name     = ses($_POST, 'cs_name', '', 'string');
    $cs_content  = ses($_POST, 'cs_content', '', 'string');
    $cs_icon     = ses($_POST, 'cs_icon', '', 'string');
    $cs_img      = ses($_POST, 'cs_img', '', 'string');

    if ($mo_id <= 0) {
        alert('몬스터를 선택해 주세요.');
    }
    if ($sk_id <= 0) {
        alert('스킬을 선택해 주세요.');
    }

    // 중복 체크
    $exists = sql_fetch("
        SELECT cs_id FROM {$g5['k_mo_skill_table']} 
        WHERE mo_id = '{$mo_id}' AND sk_id = '{$sk_id}'
    ");
    if (!empty($exists['cs_id'])) {
        alert('이미 해당 몬스터에 동일한 스킬이 등록되어 있습니다.');
    }

    $sql = "
        INSERT INTO {$g5['k_mo_skill_table']}
        SET mo_id       = '{$mo_id}',
            sk_id       = '{$sk_id}',
            cs_use      = '{$cs_use}',
            cs_name     = '{$cs_name}',
            cs_content  = '{$cs_content}',
            cs_icon     = '{$cs_icon}',
            cs_img      = '{$cs_img}'
    ";
    sql_query($sql);

    $redirect_params['mo_id'] = $mo_id;
    $msg = '등록되었습니다.';

// 일괄 등록
} elseif ($type === 'bulk_insert') {
    $mo_id  = ses($_POST, 'mo_id', 0, 'int');
    $sk_ids = ses($_POST, 'sk_ids', array(), 'array');

    if ($mo_id <= 0) {
        alert('몬스터를 선택해 주세요.');
    }
    if (count($sk_ids) === 0) {
        alert('스킬을 하나 이상 선택해 주세요.');
    }

    $inserted = 0;
    $skipped = 0;

    foreach ($sk_ids as $sk_id) {
        $sk_id = (int)$sk_id;
        if ($sk_id <= 0) continue;

        // 중복 체크
        $exists = sql_fetch("
            SELECT cs_id FROM {$g5['k_mo_skill_table']} 
            WHERE mo_id = '{$mo_id}' AND sk_id = '{$sk_id}'
        ");
        if (!empty($exists['cs_id'])) {
            $skipped++;
            continue;
        }

        sql_query("
            INSERT INTO {$g5['k_mo_skill_table']}
            SET mo_id       = '{$mo_id}',
                sk_id       = '{$sk_id}',
                cs_use      = 1,
                cs_name     = '',
                cs_content  = '',
                cs_icon     = '',
                cs_img      = ''
        ");
        $inserted++;
    }

    $redirect_params['mo_id'] = $mo_id;
    $msg = "{$inserted}개 등록되었습니다.";
    if ($skipped > 0) {
        $msg .= " ({$skipped}개 중복으로 건너뜀)";
    }

// 일괄 업데이트 (체크된 것 추가, 체크 해제된 것 삭제)
} elseif ($type === 'bulk_update') {
    $mo_id  = ses($_POST, 'mo_id', 0, 'int');
    $sk_ids = ses($_POST, 'sk_ids', array(), 'array');

    if ($mo_id <= 0) {
        alert('몬스터를 선택해 주세요.');
    }

    // 정수 변환
    $sk_ids = array_map('intval', $sk_ids);
    $sk_ids = array_filter($sk_ids, function($v) { return $v > 0; });
    $sk_ids = array_values(array_unique($sk_ids));

    // 현재 보유한 스킬 목록
    $current_sk_ids = array();
    $cur_sql = sql_query("SELECT sk_id FROM {$g5['k_mo_skill_table']} WHERE mo_id = '{$mo_id}'");
    for ($i = 0; $row = sql_fetch_array($cur_sql); $i++) {
        $current_sk_ids[] = (int)$row['sk_id'];
    }

    // 추가할 스킬 (체크O, 현재X)
    $to_add = array_diff($sk_ids, $current_sk_ids);
    // 삭제할 스킬 (체크X, 현재O)
    $to_del = array_diff($current_sk_ids, $sk_ids);

    $inserted = 0;
    $deleted = 0;

    // 추가
    foreach ($to_add as $sk_id) {
        sql_query("
            INSERT INTO {$g5['k_mo_skill_table']}
            SET mo_id       = '{$mo_id}',
                sk_id       = '{$sk_id}',
                cs_use      = 1,
                cs_name     = '',
                cs_content  = '',
                cs_icon     = '',
                cs_img      = ''
        ");
        $inserted++;
    }

    // 삭제
    if (count($to_del) > 0) {
        $del_in = implode(',', $to_del);
        sql_query("DELETE FROM {$g5['k_mo_skill_table']} WHERE mo_id = '{$mo_id}' AND sk_id IN ({$del_in})");
        $deleted = count($to_del);
    }

    $redirect_params['mo_id'] = $mo_id;
    $msg_parts = array();
    if ($inserted > 0) $msg_parts[] = "{$inserted}개 추가";
    if ($deleted > 0)  $msg_parts[] = "{$deleted}개 삭제";
    if (count($msg_parts) > 0) {
        $msg = implode(', ', $msg_parts) . '되었습니다.';
    } else {
        $msg = '변경 사항이 없습니다.';
    }

// 목록에서 수정/삭제
} else {
    if ($act_button === '선택수정') {
        $chk = ses($_POST, 'chk', array(), 'array');
        if (count($chk) === 0) {
            alert('수정하실 항목을 하나 이상 선택하세요.');
        }

        foreach ($chk as $k) {
            $k = (string)$k;

            $cs_id       = ses($_POST['cs_id'], $k, 0, 'int');
            if ($cs_id <= 0) continue;

            $cs_use        = ses($_POST['cs_use'], $k, 1, 'int');
            $cs_name       = ses($_POST['cs_name'], $k, '', 'string');
            $cs_content    = ses($_POST['cs_content'], $k, '', 'string');
            $cs_icon       = ses($_POST['cs_icon'], $k, '', 'string');
            $cs_target_cnt = ses($_POST['cs_target_cnt'], $k, 0, 'int');

            sql_query("
                UPDATE {$g5['k_mo_skill_table']}
                SET cs_use        = '{$cs_use}',
                    cs_name       = '{$cs_name}',
                    cs_content    = '{$cs_content}',
                    cs_icon       = '{$cs_icon}',
                    cs_target_cnt = '{$cs_target_cnt}'
                WHERE cs_id = '{$cs_id}'
            ");
        }

        $msg = '수정되었습니다.';

    } elseif ($act_button === '선택삭제') {
        $chk = ses($_POST, 'chk', array(), 'array');
        if (count($chk) === 0) {
            alert('삭제하실 항목을 하나 이상 선택하세요.');
        }

        $ids = array();
        foreach ($chk as $k) {
            $id = ses($_POST['cs_id'], $k, 0, 'int');
            if ($id > 0) $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));

        if (count($ids) > 0) {
            $in = implode(',', $ids);
            sql_query("DELETE FROM {$g5['k_mo_skill_table']} WHERE cs_id IN ({$in})");
            $msg = '삭제되었습니다.';
        } else {
            alert('삭제할 대상이 없습니다.');
        }
    }
}

if ($msg !== '') {
    alert($msg);
}

// 리다이렉트
$url = './980_k_mo_skill.php';
$qstr_parts = array();
foreach ($redirect_params as $key => $val) {
    if ($val !== '' && $val !== 0) {
        $qstr_parts[] = $key . '=' . urlencode($val);
    }
}
if (count($qstr_parts) > 0) {
    $url .= '?' . implode('&', $qstr_parts);
}

goto_url($url);
