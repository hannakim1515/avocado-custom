<?php
/*
 * A 통합 스킬의 레이드 전용 어댑터.
 * default.php가 처리할 수 없는 회피·방어를 여기서 처리한다. 지속시간은 기존 buff
 * 테이블의 turn_left를 그대로 쓰므로 실시간 턴 처리 쿼리가 추가되지 않는다.
 */
if (isset($sk['si_code']) && $sk['si_code'] === 'unified_evade') {
    $turn = max(1, (int)$sk['sk_turn']);
    foreach ((array)$target as $target_row) {
        $rm_id = isset($target_row['rm_id']) ? (int)$target_row['rm_id'] : 0;
        if ($rm_id <= 0) continue;
        sql_query("INSERT INTO {$battle_table}_buff
            SET si_code = 'unified_evade', sc_id = 0, bf_value = 0,
                cs_id = '".(int)$sk['cs_id']."', turn_left = '{$turn}',
                rm_id = '{$rm_id}', ra_id = '".sql_escape_string($ra_id)."'", false);
        $sk_effect .= '<p><span class="name">'.h($target_row['unit_name']).'</span>의 회피가 '.$turn.'턴 동안 유지됩니다.</p>';
    }
}

if (isset($sk['si_code']) && $sk['si_code'] === 'unified_guard') {
    $turn = max(1, (int)$sk['sk_turn']);
    $rate = min(90, max(0, (int)$bonus));
    foreach ((array)$target as $target_row) {
        $rm_id = isset($target_row['rm_id']) ? (int)$target_row['rm_id'] : 0;
        if ($rm_id <= 0) continue;
        sql_query("INSERT INTO {$battle_table}_buff
            SET si_code = 'unified_guard', sc_id = 0, bf_value = '{$rate}',
                cs_id = '".(int)$sk['cs_id']."', turn_left = '{$turn}',
                rm_id = '{$rm_id}', ra_id = '".sql_escape_string($ra_id)."'", false);
        $sk_effect .= '<p><span class="name">'.h($target_row['unit_name']).'</span>의 받는 피해가 '.$turn.'턴 동안 '.$rate.'% 감소합니다.</p>';
    }
}
?>
