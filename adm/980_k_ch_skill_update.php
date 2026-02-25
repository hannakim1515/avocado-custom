<?php
$sub_menu = '980200';
include_once('./_common.php');

/* CSRF */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('check_token')) {
    check_token();
}

/* 입력 안전 처리 */
$type   = ses($_POST, 'type', '', 'raw');
$act    = ses($_POST, 'act_button', '', 'raw');
$ch_id  = ses($_POST, 'ch_id', 0, 'int');
$sk_id  = ses($_POST, 'sk_id', 0, 'int');

$msg = '';

if ($type === 'insert') {
    $ch = get_character($ch_id);

    // 명시적 JOIN과 정수 캐스팅
    $sk = sql_fetch("
        SELECT sk.*, si.si_id
        FROM {$g5['k_skill_table']} AS sk
        INNER JOIN {$g5['k_skill_info_table']} AS si ON si.si_id = sk.si_id
        WHERE sk.sk_id = {$sk_id}
        LIMIT 1
    ");

    if (!empty($ch['ch_id']) && !empty($sk['sk_id'])) {
        $check = sql_fetch("
            SELECT cs_id
            FROM {$g5['k_ch_skill_table']}
            WHERE sk_id = {$sk_id} AND ch_id = {$ch_id}
            LIMIT 1
        ");

        if (!empty($check['cs_id'])) {
            $msg = '이미 보유한 스킬입니다.';
        } else {
            // 문자열 컬럼 이스케이프
            $cs_name    = ses($sk, 'sk_name', '', 'string');
            $cs_icon    = ses($sk, 'sk_icon', '', 'string');
            $cs_content = ses($sk, 'sk_content', '', 'string');

            sql_query("
                INSERT INTO {$g5['k_ch_skill_table']}
                    (cs_name, cs_icon, cs_content, ch_id, sk_id)
                VALUES
                    ('{$cs_name}', '{$cs_icon}', '{$cs_content}', {$ch_id}, {$sk_id})
            ");
            $msg = '지급되었습니다.';
        }
    } else {
        $msg = '대상 캐릭터 또는 스킬이 없습니다.';
    }

} else {
    // 선택 삭제
    $chk = ses($_POST, 'chk', array(), 'array');
    if ($act === '선택삭제' && !empty($chk)) {

        $cs_ids = array();
        $cs_id_arr = ses($_POST, 'cs_id', array(), 'array');
        foreach ($chk as $k) {
            // 실제 인덱스 값 검증
            if (!isset($cs_id_arr[$k])) continue;
            $cid = ses($_POST['cs_id'], $k, 0, 'int');
            if ($cid > 0) $cs_ids[] = $cid;
        }

        if ($cs_ids) {
            $in = implode(',', $cs_ids);
            sql_query("DELETE FROM {$g5['k_ch_skill_table']} WHERE cs_id IN ({$in})");
            $msg = '삭제되었습니다.';
        } else {
            $msg = '삭제할 항목이 없습니다.';
        }
    }
}

/* 알림 및 리다이렉트 */
if ($msg) alert($msg);

goto_url('./980_k_ch_skill.php');
