<?php
include_once('./_common.php');

$return_url = get_board_link($bo_table, $wr_id);

$valid_col = function($c){ return is_string($c) && preg_match('/^[A-Za-z0-9_]+$/', $c); };
if (isset($wa_id_ar) && !$valid_col($wa_id_ar))   alert('잘못된 요청(wa_id_ar)', $return_url);
if (isset($re_wa_id_ar) && !$valid_col($re_wa_id_ar)) alert('잘못된 요청(re_wa_id_ar)', $return_url);

sql_query("START TRANSACTION");

$lo = sql_fetch("SELECT * FROM {$g5['pokemon_event_log_table']} WHERE lo_id='{$lo_id}' FOR UPDATE");
if (!$lo || !$lo['lo_id']) {
    sql_query("ROLLBACK");
    alert('배틀 로그가 존재하지 않습니다.', $return_url);
}

if (empty($lo['lo_6'])) {
    if (!$battle_ph_id) {
        sql_query("ROLLBACK");
        alert('내보낼 포켓몬을 선택해 주세요', $return_url);
    }

    $ok = sql_query("UPDATE {$g5['pokemon_event_log_table']}
                        SET lo_6='{$battle_ph_id}'
                    WHERE lo_id='{$lo['lo_id']}'");
    if (!$ok) {
        sql_query("ROLLBACK");
        alert('배틀 수락 처리에 실패했습니다.', $return_url);
    }
    sql_query (" UPDATE {$g5['pokemon_battle_table']} set is_battle=1 where ph_id='{$battle_ph_id}' ");

    sql_query("COMMIT");
    alert('배틀을 수락했습니다. 사용할 기술을 선택해 주세요!', $return_url);
    exit;

} elseif ($f_type === 'wa_select') {

    if (!$wh_id) {
        sql_query("ROLLBACK");
        alert('사용할 기술을 선택해 주세요!.', $return_url);
    }

    if (!empty($lo[$wa_id_ar])) {
        sql_query("ROLLBACK");
        alert('이미 기술을 선택했습니다.', $return_url);
    }

    $ok = sql_query("UPDATE {$g5['pokemon_event_log_table']}
                        SET {$wa_id_ar}='{$wh_id}'
                    WHERE lo_id='{$lo['lo_id']}'");
    if (!$ok) {
        sql_query("ROLLBACK");
        alert('기술 선택 저장에 실패했습니다.', $return_url);
    }

    $lo_after = sql_fetch("SELECT * FROM {$g5['pokemon_event_log_table']} WHERE lo_id='{$lo_id}' FOR UPDATE");

    sql_query("COMMIT");

    if (!empty($lo_after[$re_wa_id_ar])) {
        include(G5_PATH . "/pokemon/battle/battle_update.php");
        alert('턴이 진행되었습니다.', $return_url);
    } else {
        alert('기술을 선택했습니다.', $return_url);
    }
    exit;
}

sql_query("COMMIT");
alert('요청이 정상적으로 처리되었습니다.', $return_url);
