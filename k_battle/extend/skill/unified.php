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
        $effect_skill = $sk;
        $effect_skill['sk_turn'] = $turn;
        $effect_skill['target_sc'] = 0;
        insert_k_buff($rm_id, $effect_skill, 0, $ra_id);
        $sk_effect .= '<p><span class="name">'.h($target_row['unit_name']).'</span>의 회피가 '.$turn.'턴 동안 유지됩니다.</p>';
    }
}

if (isset($sk['si_code']) && $sk['si_code'] === 'unified_guard') {
    $turn = max(1, (int)$sk['sk_turn']);
    foreach ((array)$target as $target_row) {
        $rm_id = isset($target_row['rm_id']) ? (int)$target_row['rm_id'] : 0;
        if ($rm_id <= 0) continue;
        $rate = (int)$bonus;
        if (!empty($sk['unified_def_code']) && function_exists('unified_status_extra_value_from_types')) {
            $modifier = unified_status_extra_value_from_types($sk['unified_def_code'], function($type) use ($target_row) {
                return unified_combat_unit_type_value($target_row, $type);
            });
            $modifier_value = isset($modifier['value']) ? (int)$modifier['value'] : 0;
            if (isset($sk['unified_def_type']) && $sk['unified_def_type'] === '-') $rate -= $modifier_value;
            else $rate += $modifier_value;
        }
        $rate = min(90, max(0, $rate));
        $effect_skill = $sk;
        $effect_skill['sk_turn'] = $turn;
        $effect_skill['target_sc'] = 0;
        insert_k_buff($rm_id, $effect_skill, $rate, $ra_id);
        $sk_effect .= '<p><span class="name">'.h($target_row['unit_name']).'</span>의 받는 피해가 '.$turn.'턴 동안 '.$rate.'% 감소합니다.</p>';
    }
}
?>
