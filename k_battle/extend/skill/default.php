<?php
if($sk['si_category']==980){

    if($sk['sk_turn']>0){
        $turn = "<span class=\"turn\">{$sk['sk_turn']}</span>";
    } else {
        $turn = "";
    }

    if($sk['si_1']=='default'){
        //공격/치유/부활 -> 지정된 수치를 공격/치유치에 더해 계산한다 
        if($sk['si_code']=='atk'){
            $func_type='atk';
            $multi=-1;
        }else{
            $func_type='heal';
            $multi=1;
        }

        for ($i=0; $i < count($target); $i++) {
            $result=0;
            if($sk['default_calc']){
                $func=get_k_battle_func($func_type, $unit, $target[$i]);
                $result=$func['value'];
                if($sk['default_calc']=='p'){
                    $result=$func['value']+$bonus;
                }elseif($sk['default_calc']=='m'){
                    $result=$func['value']*$bonus;
                }
            }else{
                $result=$bonus;
            }

            /* A 스킬의 ② 결과수정(대상의 연동코드)은 회복 대상의 레이드 유닛
             * 스냅샷을 기준으로 계산한다. A 캐릭터/스킬 테이블을 행동마다 JOIN하지
             * 않으면서 던전의 sk_def_code +/- 규칙을 유지한다. */
            if ($sk['si_code'] == 'heal' && !empty($sk['unified_def_code']) && function_exists('unified_status_extra_value_from_types')) {
                $modifier = unified_status_extra_value_from_types($sk['unified_def_code'], function($type) use ($target, $i) {
                    return unified_combat_unit_type_value($target[$i], $type);
                });
                $modifier_value = isset($modifier['value']) ? (int)$modifier['value'] : 0;
                if ($sk['unified_def_type'] === '-') $result -= $modifier_value;
                else $result += $modifier_value;
            }
            if ($sk['si_code'] == 'atk' && !empty($sk['unified_def_enermy'])) {
                $enemy_value = 0;
                if ($sk['unified_def_enermy'] === '체력') {
                    $enemy_value = ses($target[$i], 'hp_now', 0, 'int');
                } elseif (function_exists('unified_combat_unit_type_value')) {
                    $enemy_value = unified_combat_unit_type_value($target[$i], $sk['unified_def_enermy']);
                }
                if ($sk['unified_def_type'] === '-') $result -= $enemy_value;
                else $result += $enemy_value;
                if ($result < 0) $result = 0;
            }
            if ($sk['si_code'] == 'heal' && isset($sk['unified_mod_type']) && $sk['unified_mod_type'] === '-') {
                $result *= -1;
            }

            $result=round($result*$multi);
            if($func_type=='atk' && function_exists('k_guard_damage_value')){
                $result=k_guard_damage_value($target[$i], $result);
            }
            
            $dead_msg=set_k_dmg($target[$i], 'hp', $result, $sk['si_code'], $func_type=='atk');
            $result_type = $result < 0 ? 'atk' : $func_type;
            $sk_effect.="<p><span class=\"name\">{$target[$i]['unit_name']}</span> 의 {$kb_cf['hp_name']}{$turn}<span class=\"dmg {$result_type}\">".abs($result)."</span> {$dead_msg}</p>";
            
            if($sk['sk_turn']>0&&!$dead_msg){
                insert_k_buff($target[$i]['rm_id'], $sk, $result, $ra_id);
            }
        }
    }elseif($sk['si_1']=='percent'){
        //도발-지정된 턴 동안 사용자가 적의 공격을 대신 받는다.(사망시 해제)
        //기절-지정된 턴 동안 타겟이 행동할 수 없다.
        $rand=rand(1,100);
        if($rand<=$bonus){
           
            if($target_id=='all'){
                if($sk['si_code']=='aggr'){
                    $type_text="주의를 돌립니다.";
                }else{
                    $type_text="움직임을 막습니다.";
                }
                $sql=" UPDATE {$battle_table}_unit set is_{$sk['si_code']} = is_{$sk['si_code']} + {$sk['sk_turn']} where ra_id = '{$ra_id}' and unit_type='{$target_type}' and hp_now>0";
                sql_query($sql);
                $sk_effect="<p>{$turn}<span class=\"name\">모든 적</span>의 {$type_text}</p>";
            }else{
                if($sk['si_code']=='aggr'){
                    $type_text="에게로 적의 주의를 돌립니다.";
                }else{
                    $type_text="의 움직임을 막습니다.";
                }
                for ($i=0; $i < count($target); $i++) {
                    $sql=" UPDATE {$battle_table}_unit set is_{$sk['si_code']} = is_{$sk['si_code']} + {$sk['sk_turn']} where rm_id = {$target[$i]['rm_id']}";
                    sql_query($sql);
                    $sk_effect="<p>{$turn}<span class=\"name\">{$target[$i]['unit_name']}</span>{$type_text}</p>";
                }
            }
            
        }else{
            $sk_effect='<p class\"fail\">발동 실패</p>';
        }
    }elseif($sk['si_code']=='buff'){
  
        //버프,디버프 -> 지정된 턴 동안 적용값만큼 지정된 능력치를 증감한다.
        $sc=sql_fetch (" SELECT sc_name from {$g5['k_stat_table']} where sc_id = '{$sk['target_sc']}' ");
        $effect_type = isset($sk['unified_effect_type']) ? $sk['unified_effect_type'] : 'flat';
        if (!in_array($effect_type, array('flat', 'percent', 'final'), true)) $effect_type = 'flat';
        if($bonus<0){
            $func_type="minus";
        }else{
            $func_type="plus";
        }
        
        for ($i=0; $i < count($target); $i++) {
            if($bonus_loop){
                $target_col = function_exists('unified_k_stat_slot_column')
                    ? unified_k_stat_slot_column((int)$sk['target_sc'])
                    : ses($k_unit_stat, $sk['target_sc'], '');
                $bonus_stat = $target_col !== '' ? ses($target[$i], $target_col, 0, 'int') : 0;
                $bonus = round(($bonus_stat ? $bonus_stat : 1) * $sk_value) - $bonus_stat;
                if($bonus<0){
                    $func_type="minus";
                }else{
                    $func_type="plus";
                }
            }
            $applied_bonus = $bonus;
            if (!empty($sk['unified_def_code']) && function_exists('unified_status_extra_value_from_types')) {
                $modifier = unified_status_extra_value_from_types($sk['unified_def_code'], function($type) use ($target, $i) {
                    return unified_combat_unit_type_value($target[$i], $type);
                });
                $modifier_value = isset($modifier['value']) ? (int)$modifier['value'] : 0;
                if ($sk['unified_def_type'] === '-') $applied_bonus -= $modifier_value;
                else $applied_bonus += $modifier_value;
            }
            if (!empty($sk['unified_def_enermy'])) {
                $enemy_value = $sk['unified_def_enermy'] === '체력'
                    ? ses($target[$i], 'hp_now', 0, 'int')
                    : (function_exists('unified_combat_unit_type_value') ? unified_combat_unit_type_value($target[$i], $sk['unified_def_enermy']) : 0);
                if ($sk['unified_def_type'] === '-') $applied_bonus -= $enemy_value;
                else $applied_bonus += $enemy_value;
            }
            if (isset($sk['unified_mod_type']) && $sk['unified_mod_type'] === '-') $applied_bonus *= -1;
            $func_type = $applied_bonus < 0 ? 'minus' : 'plus';
            $effect_skill = $sk;
            $effect_skill['unified_effect_type'] = $effect_type;
            $stored_bonus = $effect_type === 'final' ? (int)round($applied_bonus * 100) : (int)round($applied_bonus);
            insert_k_buff($target[$i]['rm_id'], $effect_skill, $stored_bonus, $ra_id);
            $effect_label = $effect_type === 'percent' ? '%' : ($effect_type === 'final' ? '×' : '');
            $effect_value = $effect_type === 'final' ? rtrim(rtrim(number_format(abs($applied_bonus), 2, '.', ''), '0'), '.') : abs($applied_bonus);
            $sk_effect.="<p><span class=\"name\">{$target[$i]['unit_name']}</span> 의 {$sc['sc_name']} {$turn}<span class=\"buff {$func_type}\">{$effect_label}{$effect_value}</span></p>";
        }
    }
}
?>
