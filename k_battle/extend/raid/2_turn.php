<?php

/*로그삽입*/
function insert_k_log($msg, $ra_id, $unit_type, $option='', $system=''){
    global $battle_table;

    $ra_id = (string)$ra_id;
    $msg   = sql_real_escape_string($msg);
    $unit_type = sql_real_escape_string($unit_type);

    $sql = "INSERT INTO {$battle_table}_log
            SET lo_content='{$msg}', ra_id='{$ra_id}', lo_1='{$unit_type}' {$option}";
    sql_query($sql);

    if($system){
        $sql = "UPDATE {$battle_table}
            SET ra_system_msg='{$system}'
            where  ra_id='{$ra_id}'";
        sql_query($sql);
    }

    return true;
}

/*턴 관리*/
function k_turn_type_check($turn_type, $now_rm, $rm_id){//내 턴 체크
    $now_rm = (int)$now_rm;
    $rm_id = (int)$rm_id;
    $notmyturn=false;
    switch ($turn_type) {
        case 'speed':
            if($now_rm !== $rm_id) $notmyturn=true;
            break;
        default:
            break;
    }
    return $notmyturn;
}
function k_turn_change($ra_id=0, $option='', $type='speed'){//턴 변경
    global $battle_table, $ar_title;

    $ra_id = (string)$ra_id;
    if($type!='free'){
        // 현재 턴 수 조회
        $turn_msg = '다음 턴 시작';
        if (!empty($ar_title) && !empty($battle_table)) {
            $col_check = sql_fetch("SHOW COLUMNS FROM {$battle_table} LIKE 'ra_turn'");
            if (!empty($col_check)) {
                $ra_info = sql_fetch("SELECT ra_turn FROM {$battle_table} WHERE {$ar_title} = '" . sql_escape_string($ra_id) . "'");
                $ra_turn = ses($ra_info, 'ra_turn', 0, 'int');
                if ($ra_turn > 0) {
                    $turn_msg = $ra_turn . '턴 시작';
                }
            }
        }
        insert_k_log($turn_msg, $ra_id, 'system', $option);
    }

    get_k_dot($ra_id, 'atk',  '<p class="log-title">출혈 발생</p>', $option);
    get_k_dot($ra_id, 'heal', '<p class="log-title">지속 회복</p>', $option);
    if($type!='free'){
        sql_query("UPDATE {$battle_table}_unit  SET tt_done=0       WHERE ra_id='{$ra_id}'");
    }
    sql_query("UPDATE {$battle_table}_unit  SET is_aggr=is_aggr-1 WHERE is_aggr>0 AND ra_id='{$ra_id}'");
    sql_query("UPDATE {$battle_table}_unit  SET is_stun=is_stun-1 WHERE is_stun>0 AND ra_id='{$ra_id}'");
    sql_query("UPDATE {$battle_table}_skill SET sk_cool_now=sk_cool_now-1 WHERE sk_cool_now>0 AND ra_id='{$ra_id}'");
    sql_query("UPDATE {$battle_table}_buff  SET turn_left=turn_left-1 WHERE turn_left>0 AND ra_id='{$ra_id}'");
    sql_query("DELETE FROM {$battle_table}_buff WHERE turn_left=0 AND ra_id='{$ra_id}'");

    return true;
}
function k_next_unit($battle_table, $ra_id, $type=''){//다음 유닛 선택
    global $k_unit_stat, $kb_cf;

    $ra_id = (string)$ra_id;
    $spd_id = ses($kb_cf, 'speed', 0, 'int');
    $spd_col = ses($k_unit_stat, $spd_id, '') ? $k_unit_stat[$spd_id].' desc' : 'unit_type asc';

    $sql = "SELECT * FROM {$battle_table}_unit
            WHERE hp_now>0 AND unit_type!='etc' AND tt_done=0 AND ra_id='{$ra_id}'
            ORDER BY {$spd_col}
            LIMIT 1";
    $next = sql_fetch($sql);

    if (empty($next['rm_id'])) {
        $sum = sql_fetch("SELECT SUM(hp_now) AS sum FROM {$battle_table}_unit WHERE hp_now>0 AND unit_type='mo' AND ra_id='{$ra_id}'");
        if ((int)$sum['sum'] <= 0) {
            $next['ra_done'] = 'ch_win';
        } else {
            $sum = sql_fetch("SELECT SUM(hp_now) AS sum FROM {$battle_table}_unit WHERE hp_now>0 AND unit_type='ch' AND ra_id='{$ra_id}'");
            if ((int)$sum['sum'] <= 0) {
                $next['ra_done'] = 'mo_win';
            } else {
                $sql = "SELECT * FROM {$battle_table}_unit
                        WHERE hp_now>0 AND unit_type!='etc' AND ra_id='{$ra_id}'
                        ORDER BY {$spd_col}
                        LIMIT 1";
                $next = sql_fetch($sql);
                $next['ra_done'] = 'next';
            }
        }
    }
    return $next;
}


function k_raid_rewards($ra_info, $alive_only = false)
{
    global $g5, $battle_table, $config;
    
    $result = array(
        'success' => false,
        'count' => 0,
        'rewards' => array(),
        'log_text' => ''
    );
    
    if (!$ra_info) {
        return $result;
    }
    
    // ra_id 추출
    $ra_id = ses($ra_info, 'ra_id', '', 'raw');
    if ($ra_id === '') {
        return $result;
    }
    
    $ra_title_text = ses($ra_info, 'ra_title', '레이드', 'raw');
    
    // 보상 정보
    $reward_money = ses($ra_info, 'ra_reward_money', 0, 'int');
    $reward_exp   = ses($ra_info, 'ra_reward_exp', 0, 'int');
    $reward_item  = ses($ra_info, 'ra_reward_item', '', 'raw');
    $reward_title = ses($ra_info, 'ra_reward_title', 0, 'int');
    
    // 보상이 없으면 종료
    if ($reward_money <= 0 && $reward_exp <= 0 && $reward_item === '' && $reward_title <= 0) {
        $result['success'] = true;
        return $result;
    }
    
    // 참가 캐릭터 목록 조회
    $where_hp = $alive_only ? "AND hp_now > 0" : "";
    $participants = sql_query("
        SELECT unit_id 
        FROM {$battle_table}_unit 
        WHERE ra_id = '" . sql_escape_string($ra_id) . "' 
          AND unit_type = 'ch' 
          {$where_hp}
    ");
    
    $reward_log = array();
    $rewarded_count = 0;
    
    // 보상 지급
    while ($participant = sql_fetch_array($participants)) {
        $ch_id = (int)$participant['unit_id'];
        if ($ch_id <= 0) continue;
        
        // 캐릭터 정보 조회 (mb_id 필요)
        $ch_info = sql_fetch("SELECT ch_id, mb_id, ch_name FROM {$g5['character_table']} WHERE ch_id = '{$ch_id}'");
        if (empty($ch_info['mb_id'])) continue;
        $ch_name = $ch['ch_name'];
        $mb_id = $ch_info['mb_id'];
        
        // 포인트(화폐) 보상
        if ($reward_money > 0) {
            insert_point($mb_id, $reward_money, "[레이드] {$ra_title_text} 클리어 보상", 'raid', time(), '지급');
            if (!in_array("{$config['cf_money']} {$reward_money}{$config['cf_money_pice']}", $reward_log)) {
                $reward_log[] = "{$config['cf_money']} {$reward_money}{$config['cf_money_pice']}";
            }
        }
        
        // 경험치 보상
        if ($reward_exp > 0) {
            insert_exp($ch_id, $reward_exp, "[레이드] {$ra_title_text} 클리어 보상", G5_URL . '/k_battle/raid.php?ra_id=' . $ra_id);
            if (!in_array("{$config['cf_exp_name']} {$reward_exp}{$config['cf_exp_pice']}", $reward_log)) {
                $reward_log[] = "{$config['cf_exp_name']} {$reward_exp}{$config['cf_exp_pice']}";
            }
        }
        
        // 아이템 보상 (쉼표로 구분된 여러 아이템 가능)
        if ($reward_item !== '') {
            $item_ids = explode(',', $reward_item);
            foreach ($item_ids as $it_id) {
                $it_id = (int)trim($it_id);
                if ($it_id > 0) {
                    insert_inventory($ch_id, $it_id);
                    $item_name = function_exists('get_item_name') ? get_item_name($it_id) : "ID:{$it_id}";
                    if (!$item_name) $item_name = "ID:{$it_id}";
                    $log_entry = "아이템 [{$item_name}]";
                    if (!in_array($log_entry, $reward_log)) {
                        $reward_log[] = $log_entry;
                    }
                }
            }
        }
        
        // 칭호 보상
        if ($reward_title > 0) {
            $m_ti = sql_fetch("select count(*) as cnt from {$g5['title_has_table']} where ti_id = '{$reward_title}' and ch_id = '{$ch_id}'");
            if(!$m_ti['cnt']) { 
                $title_info = sql_fetch("SELECT ti_title FROM {$g5['title_table']} WHERE ti_id = '{$reward_title}' LIMIT 1");
                $title_name = ses($title_info, 'ti_title', '', 'raw');
                $sql = " insert into {$g5['title_has_table']}
                            set ch_id = '{$ch_id}',
                                ch_name = '{$ch_name}',
                                ti_id = '{$reward_title}',
                                hi_use = '1'";
                sql_query($sql);
                $log_entry = "타이틀 [{$title_name}]";
                if (!in_array($log_entry, $reward_log)) {
                    $reward_log[] = $log_entry;
                }
            }
  
        }
        
        $rewarded_count++;
    }
    
    $result['success'] = true;
    $result['count'] = $rewarded_count;
    $result['rewards'] = $reward_log;
    $result['log_text'] = count($reward_log) > 0 ? implode('<br>', $reward_log) : '';
    
    return $result;
}

/*레이드 종료*/
function k_raid_end($ra_id, $raid_result='end', $alive_only=false){
    global $g5, $battle_table;

    $reward_msg = '';
    $ra = sql_fetch("SELECT * FROM {$battle_table} WHERE ra_id = '{$ra_id}'");
    if(!$ra){ return false; }

    $option = ", lo_2 = '{$ra['ra_turn']}', lo_3 = '{$ra['ra_count']}'";
    $ra_state = 3;

    if($raid_result=='ch_win'){
        $msg="레이드에서 승리했습니다.";
        $reward=k_raid_rewards($ra, $alive_only);
        if($reward['success'] && $reward['log_text']){
            $reward_msg = "<p class=\"log-title\">승리 보상</p><p class=\"rewards\">{$reward['log_text']}</p>";
        }
        $ra_state = 2;
    }elseif($raid_result=='mo_win'){
        $msg="레이드에서 패배했습니다.";
        $ra_state = 3;
    }elseif($raid_result=='end'){
        $msg="레이드가 종료됩니다.";
        $ra_state = 3;
    }

    sql_query("UPDATE {$battle_table}
                SET now_turn = 0,
                    ra_turn=ra_turn+1,
                    ra_count=ra_count+1,
                    ra_state = '{$ra_state}'
                WHERE ra_id = '{$ra_id}'");

    insert_k_log($msg, $ra_id, 'system', $option, $msg);
    if($reward_msg){ insert_k_log($reward_msg, $ra_id, 'reward', $option); }

    return true;
}
