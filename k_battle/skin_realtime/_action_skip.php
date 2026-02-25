<?php
header('Content-Type: application/json; charset=UTF-8');

include_once './_common.php';

// $ra 조회 후 turn_type, mo_auto 설정
$ra = sql_fetch("SELECT ra_turn, ra_count, ra_turn_type, ra_mo_auto, now_turn FROM {$battle_table} WHERE {$ar_title} = '{$ra_id}'");
if (!is_array($ra)) $ra = array();

$turn_type = ses($ra, 'ra_turn_type', 'speed');
$mo_auto   = (ses($ra, 'ra_mo_auto', 'auto') === 'free');

$ra_turn  = ses($ra, 'ra_turn', 0, 'int');
$ra_count = ses($ra, 'ra_count', 0, 'int');
$rm_id    = ses($ra, 'now_turn', 0, 'int');

$option  = ", lo_2 = '{$ra_turn}', lo_3 = '{$ra_count}'";
$log_msg = '턴이 스킵되었습니다.';
insert_k_log($log_msg, $ra_id, 'system', $option);


if($turn_type==='speed'){
    if ($rm_id) {
        sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$rm_id}");
    }

    if ($mo_auto) { //몬스터 자동 행동시 
        $turn_break   = false;
        $loop_cnt     = 0;
        $max_loop_cnt = 100;

        while ($turn_break === false && $loop_cnt < $max_loop_cnt) {
            $loop_cnt++;
            $next = null;
            include './_action_turnchange.'.$turn_type.'.php';

            if (!empty($next['unit_type']) && $next['unit_type'] === 'mo' && !empty($next['rm_id'])) {
                $mo_rm_id = (int)$next['rm_id'];
                exec_k_mo_act($mo_rm_id, $ra_id, $option);
                sql_query("UPDATE {$battle_table}_unit SET tt_done = 1 WHERE rm_id = {$mo_rm_id}");
            } else {
                $turn_break = true;
            }
        }
    } else { //몬스터 수동행동시
        $next = null;
        include './_action_turnchange.'.$turn_type.'.php';
    }
}else{
    if ($mo_auto) { //몬스터 자동 행동시 
        $turn_break   = false;
        include './_action_turnchange.'.$turn_type.'.php';
        if(!$turn_break){
            $mo_list=get_k_unit_list('mo', $ra_id, 'unit.rm_id', 'AND unit.hp_now > 0');
            foreach ($mo_list as $m) {
                exec_k_mo_act($m['rm_id'], $ra_id, $option);
            }
        }
    } else { //몬스터 수동행동시
        $next = null;
        include './_action_turnchange.'.$turn_type.'.php';
    }
}

echo json_encode(true);
exit;
