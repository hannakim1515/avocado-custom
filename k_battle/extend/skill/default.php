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

            $result=round($result*$multi);
            if($func_type=='atk' && function_exists('k_guard_damage_value')){
                $result=k_guard_damage_value($target[$i], $result);
            }
            
            $dead_msg=set_k_dmg($target[$i], 'hp', $result, $sk['si_code'], $func_type=='atk');
            $sk_effect.="<p><span class=\"name\">{$target[$i]['unit_name']}</span> 의 {$kb_cf['hp_name']}{$turn}<span class=\"dmg {$func_type}\">".abs($result)."</span> {$dead_msg}</p>";
            
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
            insert_k_buff($target[$i]['rm_id'], $sk, $bonus, $ra_id);
            $sk_effect.="<p><span class=\"name\">{$target[$i]['unit_name']}</span> 의 {$sc['sc_name']} {$turn}<span class=\"buff {$func_type}\">".abs($bonus)."</span></p>";
        }
    }
}
?>
