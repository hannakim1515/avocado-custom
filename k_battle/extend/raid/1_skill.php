<?php

/*스킬 및 전투*/

function get_k_battle_skill($id, $select='*', $info=false){//전투용 스킬 정보 
    global $g5, $battle_table;

    $id = (int)$id;
    $join = '';
    if ($info) {
        $join = " INNER JOIN {$g5['k_skill_info_table']} si ON sk.si_id = si.si_id ";
    }

    $sql = "
        SELECT {$select}
        FROM {$battle_table}_skill bs
        INNER JOIN {$g5['k_ch_skill_table']} cs ON bs.cs_id = cs.cs_id
        INNER JOIN {$g5['k_skill_table']}    sk ON cs.sk_id = sk.sk_id
        {$join}
        WHERE bs.bs_id = '{$id}'
    ";

    return sql_fetch($sql);
}

/*전투 액션*/
function use_k_action($action_type, $unit, $target_id, $target_type, $ra_id='0', $msg='', $option='', $system='')//일반공격
{
    global $kb_cf;

    $rm_id = ses($unit, 'rm_id', 0, 'int');
    if ($rm_id <= 0) return '참가자 정보를 찾을 수 없습니다.';

    $tid = (int)$target_id;
    $target_type = ($target_type === 'ch' || $target_type === 'mo') ? $target_type : 'ch';
    $ra_id = (string)$ra_id;

    $select = "origin.{$target_type}_name AS unit_name, unit.*";
    $target = get_k_unit($tid, $target_type, $select);
    if (!$target) return '대상 정보를 찾을 수 없습니다.';

    $pass = false;
    $cri  = '';

    if ($action_type === 'atk') {
        $action_tag='공격';
        $miss = get_k_battle_func('miss', $target, $unit, true);
        $miss_val = ses($miss, 'value', 0, 'int');
        $rand = rand(1, 100);
        if ($rand <= $miss_val) {
            $pass = true;
            if (!empty($miss['cri'])) $cri = '<span class="cri">크리티컬!</span>';
        }
        $multi = -1;
    } else {
        $action_tag='치유';
        $multi = 1;
    }

    $dmg = array('value'=>0,'cri'=>0);
    $dead_msg = '';

    if (!$pass) {
        $dmg = get_k_battle_func($action_type, $unit, $target);
        $dmg_val = ses($dmg, 'value', 0, 'int');
        $dmg_val *= $multi;
        $dmg['value'] = $dmg_val;
        $dead_msg = set_k_dmg($target, 'hp', $dmg_val);
    }
    if (!empty($dmg['cri'])) $cri = '<span class="cri">크리티컬!</span>';

    $hp_label = ses($kb_cf, 'hp_name', 'hp');
    $tname = h($target['unit_name']);

    if ($pass) {
        $msg .= '<p class="act-title atk">공격</p><p>'.$cri.'<span class="name">'.$tname.'</span>에게는 명중하지 않았다!</p>';
    } elseif ($action_type === 'atk') {
        $msg .= '<p class="act-title atk">공격</p><p>'.$cri.'<span class="name">'.$tname.
                '</span>의 '.$hp_label.' <span class="dmg atk">'.abs($dmg['value']).
                '</span>'.$dead_msg.'</p>';
    } else {
        $msg .= '<p class="act-title heal">치유</p><p>'.$cri.'<span class="name">'.$tname.
                '</span>의 '.$hp_label.' <span class="dmg heal">'.$dmg['value'].'</span></p>';
    }

    if($system){
        $system=$system.'의 '.$action_tag.'!';
    }

    insert_k_log($msg, $ra_id, ses($unit, 'unit_type', ''), $option,$system);
    return false;
}
function use_k_skill($sk, $unit, $target_id, $target_type, $ra_id=0, $msg='', $option='',$system='')//스킬사용
{
    global $g5, $battle_table, $k_unit_stat, $kb_cf;

    $ra_id = (string)$ra_id;
    $target_type = ($target_type === 'ch' || $target_type === 'mo') ? $target_type : 'ch';

    if (empty($unit['rm_id']) || empty($sk['sk_id'])) return '스킬 정보를 찾을 수 없습니다.';
    $unit['mp_now'] = (int)$unit['mp_now'];
    $need_mp        = ses($sk, 'sk_mp', 0, 'int');
    if ($unit['mp_now'] < $need_mp) return 'mp가 부족합니다.';

    $sk_effect = '';
    $bonus = 0;
    $msg .= '<p class="act-title skill">스킬 사용</p>';

    $select = "origin.{$target_type}_name AS unit_name, unit.*";
   
    $target = array();
    // target_id가 배열인 경우: 배열의 각 rm_id로 유닛 조회
    if (is_array($target_id)) {
        foreach ($target_id as $tid) {
            $tid_int = (int)$tid;
            if ($tid_int > 0) {
                $one = get_k_unit($tid_int, $target_type, $select);
                if ($one) $target[] = $one;
            }
        }
        if (empty($target)) {
            return '타겟을 찾을 수 없습니다.';
        }
    } elseif ($target_id === 'all') {
        $si_code = ses($sk, 'si_code', '');
        if ($si_code === 'rev') {
            $where = " AND unit.hp_now <= 0";
        } elseif ($si_code === 'heal') {
            $where = " AND unit.hp_now < unit.hp_max AND unit.hp_now > 0";
        } else {
            $where = " AND unit.hp_now > 0";
        }
        $target = get_k_unit_list($target_type, $ra_id, $select, $where);
        if (empty($target)) {
            return '타겟을 찾을 수 없습니다.'; // 타겟 없음
        }
    } else {
        $tid = (int)$target_id;
        if ($tid <= 0) {
            return '타겟을 찾을 수 없습니다.'; // 유효하지 않은 타겟 ID
        }
        $one = get_k_unit($tid, $target_type, $select);
        if ($one) $target[] = $one;
        if (empty($target)) {
            return '타겟을 찾을 수 없습니다.'; // 타겟을 찾을 수 없음
        }
    }

    // 보정 스탯 계산
    $bonus_stat = 0;
    if (!empty($sk['sc_id']) && isset($k_unit_stat[$sk['sc_id']]) && isset($unit[$k_unit_stat[$sk['sc_id']]])) {
        $bonus_stat = (int)$unit[$k_unit_stat[$sk['sc_id']]];
    }

    $sk_value = ses($sk, 'sk_value', 0.0, 'float');
    $bonus_calc = ses($sk, 'bonus_calc', '');
    $bonus_loop=false;
    if ($bonus_calc === 'p') {
        $bonus = $bonus_stat + $sk_value;
    } elseif ($bonus_calc === 'm') {
        if(empty($sk['sc_id'])&&$sk['si_code']=='buff'){
            $bonus_loop=true;
        }else{
            $bonus = round(($bonus_stat ? $bonus_stat : 1) * $sk_value);
        }
    }

    if (ses($sk, 'default_calc', '') === 'i' && !empty($sk['sc_id']) && isset($k_unit_stat[$sk['sc_id']])) {
        $key = $k_unit_stat[$sk['sc_id']];
        $unit[$key] = ses($unit, $key, 0, 'int') + (int)$bonus;
    }

    // 스킬 파일 include
    $skill_dir = G5_PATH . '/k_battle/extend/skill';
    if (is_dir($skill_dir)) {
        $files = glob($skill_dir . '/*.php', GLOB_NOSORT) ?: array();
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            include $file;
        }
    }

    // MP 차감
    if ($need_mp > 0) {
        $unit['mp_now'] = max(0, $unit['mp_now'] - $need_mp);
        $sql = "
            UPDATE {$battle_table}_unit
            SET mp_now = '{$unit['mp_now']}'
            WHERE rm_id = '" . (int)$unit['rm_id'] . "'
        ";
        sql_query($sql);
    }

    // 쿨다운 설정 (캐릭터 스킬만 해당)
    if (!empty($sk['sk_cool']) && !empty($sk['bs_id'])) {
        sql_query("
            UPDATE {$battle_table}_skill
            SET sk_cool_now = " . (int)$sk['sk_cool'] . "
            WHERE bs_id = '" . (int)$sk['bs_id'] . "'
        ");
    }

    // 스킬 이미지/정보 출력 (캐릭터: cs_*, 몬스터: sk_*)
    $sk_img = ses($sk, 'cs_img', '');
    if (empty($sk_img)) $sk_img = ses($sk, 'sk_img', '');
    if (!empty($sk_img)) {
        $msg .= '<div class="sk-img"><img src="' . $sk_img . '"></div>';
    }

    $icon = ses($sk, 'cs_icon', '');
    if (empty($icon)) $icon = ses($sk, 'sk_icon', '');

    $name = ses($sk, 'cs_name', '');
    if (empty($name)) $name = ses($sk, 'sk_name', '');

    $cont = ses($sk, 'cs_content', '');
    if (empty($cont)) $cont = ses($sk, 'sk_content', '');

    $msg .= '<div class="sk-info"><p class="sc-name">' . (!empty($icon) ? '<img src="'.$icon.'">' : '') . h($name) . '</p><p class="sk-content">' . $cont . '</p></div>' . $sk_effect;
    
    if($system){
        $system=$system.'의 '.$name.'!';
    }

    insert_k_log($msg, $ra_id, $unit['unit_type'], $option, $system);
    return false;
}
function use_k_item($unit, $target_id, $ra_id=0, $msg='', $option='')//아이템사용
{
    global $g5, $kb_cf;

    $in = sql_fetch("
            SELECT it.it_value, it.it_type, inven.in_id, it.it_img, it.it_content, it.it_name
            FROM {$g5['inventory_table']} AS inven
            JOIN {$g5['item_table']} AS it ON it.it_id = inven.it_id
            WHERE inven.in_id = '{$target_id}'
            LIMIT 1
            ");

    if (empty($in['in_id'])) {
        return '아이템 정보를 찾을 수 없습니다.';
    }

    $recover = '';
    $hp_name = '';

    if ($in['it_type'] === 'HP회복(K)') {
        $recover = 'hp';
        $hp_name = ses($kb_cf, 'hp_name', 'HP');
    } elseif ($in['it_type'] === 'MP회복(K)') {
        $recover = 'mp';
        $hp_name = ses($kb_cf, 'mp_name', 'MP');
    } else {
        return '아이템 정보를 찾을 수 없습니다.';
    }

    if ($recover !== '') {
        set_k_dmg($unit, $recover, (int)$in['it_value']);
        delete_inventory($target_id);

        $msg .= "<p class=\"act-title item\">아이템 사용</p>";
        $msg .= "<div class=\"sk-info\"><p class=\"sc-name\"><img src=\"{$in['it_img']}\">{$in['it_name']}</p><p class=\"sk-content\">{$in['it_content']}</p></div>";
        $msg .= "<p><span class=\"name\">{$unit['unit_name']}</span>의 {$hp_name} <span class=\"dmg heal\">{$in['it_value']}</span></p>";
        
        insert_k_log($msg, $ra_id, 'ch', $option);
        return false;
    }

    return '아이템 정보를 찾을 수 없습니다.';
}
function insert_k_buff($target, $sk, $bonus, $ra_id=0)//버프삽입
{
    global $g5, $battle_table;

    $target = (int)$target;
    $ra_id  = (string)$ra_id;
    $bonus  = (int)$bonus;

    $sql = '';
    $check = array();

    if (!empty($sk['si_code']) && $sk['si_code'] === 'buff') {
        $check = sql_fetch("
            SELECT bf_id
            FROM {$battle_table}_buff
            WHERE cs_id = '".(int)$sk['cs_id']."'
              AND rm_id = '{$target}'
            LIMIT 1
        ");
    }

    if (!empty($check['bf_id'])) {
        $sql = "
            UPDATE {$battle_table}_buff
            SET turn_left = turn_left + ".(int)$sk['sk_turn']."
            WHERE bf_id = ".(int)$check['bf_id']."
        ";
    } else {
        $sql = "
            INSERT INTO {$battle_table}_buff
                (si_code, sc_id, bf_value, cs_id, turn_left, rm_id, ra_id)
            VALUES
                ('".sql_real_escape_string($sk['si_code'])."',
                 '".(int)$sk['target_sc']."',
                 '{$bonus}',
                 '".(int)$sk['cs_id']."',
                 '".(int)$sk['sk_turn']."',
                 '{$target}',
                 '{$ra_id}')
        ";
    }
    sql_query($sql);
    return true;
}

/*몬스터 행동*/
function get_k_mo_act($rm_id, $ra_id, $turn) //몬스터 행동 선택
{
    global $g5, $battle_table;

    $rm_id = (int)$rm_id;
    $turn = (int)$turn;
    if ($rm_id <= 0 || $turn <= 0) {
        return false;
    }

    // 1. rm_id를 기반으로 몬스터 정보 추출
    $unit = get_k_unit($rm_id, 'mo', 'origin.mo_id, origin.mo_pattern, origin.mo_default_act');
    if (empty($unit['mo_id'])) {
        return false;
    }

    $mo_id = (int)$unit['mo_id'];

    $default_act=($unit['mo_default_act']==2)?'act':false;

    $mo_pattern = ses($unit, 'mo_pattern', 1, 'int');
    if ($mo_pattern < 1 || $mo_pattern > 2) {
        $mo_pattern = 1;
    }

    // 패턴 데이터 배열
    $pattern_data = array();

    // 2. 패턴 타입에 따른 로직
    if ($mo_pattern === 1) {
        // 2.1 패턴 타입 1: 순차 실행 (최대값 단위 반복)
        // pt_turn의 최대값 가져오기
        $max_row = sql_fetch("
            SELECT MAX(pt_turn) as max_turn 
            FROM {$g5['k_mo_pattern_table']} 
            WHERE mo_id = '{$mo_id}'
        ");
        $max_turn = ses($max_row, 'max_turn', 0, 'int');

        if ($max_turn <= 0) {
            return $default_act;
        }

        // 현재 턴을 최대값 단위로 순환 
        $effective_turn = (($turn - 1) % $max_turn) + 1;

        // 해당 턴의 패턴 조회
        $pt_sql = sql_query("
            SELECT * FROM {$g5['k_mo_pattern_table']} 
            WHERE mo_id = '{$mo_id}' AND pt_turn = '{$effective_turn}'
        ");
        while ($row = sql_fetch_array($pt_sql)) {
            $pattern_data[] = $row;
        }

    } else {
        // 2.2 패턴 타입 2: 조건 실행 (현재 턴이 pt_turn의 배수인 패턴 사용)
        $pt_sql = sql_query("
            SELECT * FROM {$g5['k_mo_pattern_table']} 
            WHERE mo_id = '{$mo_id}' 
              AND pt_turn > 0 
              AND ({$turn} % pt_turn) = 0
            ORDER BY pt_turn ASC
        ");
        while ($row = sql_fetch_array($pt_sql)) {
            $pattern_data[] = $row;
        }
    }

    // 3. 패턴 데이터가 없으면 false 반환
    if (empty($pattern_data)) {
        return $default_act;
    }

    // 4. 각 패턴에서 스킬 ID 추출
    $selected_skill_ids = array();

    foreach ($pattern_data as $pattern) {
        $pt_skill = $pattern['pt_skill'];
        $pt_skill_cnt = (int)$pattern['pt_skill_cnt'];

        if (empty($pt_skill)) {
            continue;
        }

        // 스킬 후보 배열
        $skill_candidates = array_filter(explode(',', $pt_skill), function($v) {
            return (int)$v > 0;
        });
        $skill_candidates = array_values($skill_candidates);

        if (empty($skill_candidates)) {
            continue;
        }

        // 사용 갯수 조정 (최소 1, 최대 후보 수)
        $pt_skill_cnt = max(1, min($pt_skill_cnt, count($skill_candidates)));

        // 랜덤으로 pt_skill_cnt만큼 뽑기
        shuffle($skill_candidates);
        $selected = array_slice($skill_candidates, 0, $pt_skill_cnt);

        // 선택된 스킬 ID 누적
        foreach ($selected as $sk_id) {
            $sk_id = (int)$sk_id;
            if ($sk_id > 0) {
                $selected_skill_ids[] = $sk_id;
            }
        }
    }

    // 5. 스킬 ID가 없으면 false 반환
    if (empty($selected_skill_ids)) {
        return $default_act;
    }

    // 6. get_k_mo_skill 함수를 사용하여 스킬 정보 조회
    $sk_list = array();
    foreach ($selected_skill_ids as $sk_id) {
        $sk_id = (int)$sk_id;
        if ($sk_id <= 0) continue;
        
        // 몬스터별 커스텀 정보 포함하여 조회
        $sk = get_k_mo_skill($sk_id, $mo_id, '*');
        if (!empty($sk['sk_id'])) {
            $sk_list[] = $sk;
        }
    }

    return !empty($sk_list) ? $sk_list : $default_act;
}
function get_k_mo_skill_target($sk, $rm_id, $ra_id) //몬스터 스킬 타겟 선정
{
    global $battle_table;

    $sk_target = ses($sk, 'sk_target', 'enemy');
    $sk_target_cnt = ses($sk, 'sk_target_cnt', 'single', 'string');
    $cs_target_cnt = ses($sk, 'cs_target_cnt', 0, 'int');
    if ($cs_target_cnt <= 0) $cs_target_cnt = 1;

    $target_ids = array();
    $target_type = 'ch';

    if ($sk_target === 'self') {
        $target_type = 'mo';
        $target_ids = $rm_id;
    } elseif ($sk_target_cnt === 'single') {
        if ($sk_target === 'ally') {
            $target_type = 'mo';
            $mo_where = (ses($sk, 'si_code', '') === 'rev') ? " AND hp_now <= 0" : " AND hp_now > 0";
            $rand_mo = sql_fetch("SELECT rm_id FROM {$battle_table}_unit WHERE ra_id='" . sql_escape_string($ra_id) . "' AND unit_type='mo' {$mo_where} ORDER BY RAND() LIMIT 1");
            if (!empty($rand_mo['rm_id'])) {
                $target_ids = (int)$rand_mo['rm_id'];
            }
        } elseif ($sk_target === 'enemy') {
            $target_type = 'ch';
            $rand_result = get_k_rand_target($ra_id, $cs_target_cnt, 'ch', false);
            $target_csv = ses($rand_result, 'csv', '');
            $target_ids = array_map('intval', array_filter(explode(',', $target_csv), function($v) {
                return (int)$v > 0;
            }));
        }
    } else {
        $target_type = ($sk_target === 'enemy') ? 'ch' : 'mo';
        $target_ids = 'all';
    }

    return array(
        'target_ids' => $target_ids,
        'target_type' => $target_type
    );
}
function exec_k_mo_act($rm_id, $ra_id, $option = '', $system='') //몬스터 행동 지정
{
    global $battle_table, $ar_title;

    $rm_id = (int)$rm_id;
    if ($rm_id <= 0) {
        return false;
    }

    // 몬스터 정보 조회
    $mo_select = "origin.mo_name AS unit_name, origin.mo_id, origin.mo_pattern, origin.mo_default_act, unit.*";
    $mo_unit = get_k_unit($rm_id, 'mo', $mo_select);

    if (empty($mo_unit) || ses($mo_unit, 'hp_now', 0, 'int') <= 0) {
        return false;
    }

    // 레이드 턴 정보 가져오기
    $ra_info = sql_fetch("SELECT ra_turn, ra_count FROM {$battle_table} WHERE {$ar_title} = '" . sql_escape_string($ra_id) . "'");
    $current_turn = ses($ra_info, 'ra_turn', 1, 'int');

    // 패턴 기반 행동 결정
    $mo_act = get_k_mo_act($rm_id, $ra_id, $current_turn);

    $mo_msg = "<p class=\"log-title\">" . h($mo_unit['unit_name']) . "의 행동</p>";
    $mo_action_done = false;
    $first_skill = true;

    if($system){$system=$mo_unit['unit_name'];}

    if (is_array($mo_act) && !empty($mo_act)) {
        // 스킬 사용
        foreach ($mo_act as $sk) {
            // 타겟 선정
            $target_result = get_k_mo_skill_target($sk, $rm_id, $ra_id);
            $target_ids = $target_result['target_ids'];
            $mo_target_type = $target_result['target_type'];

            // 스킬 사용 - target_ids 배열을 그대로 use_k_skill에 전달
            if (!empty($target_ids)) {
                // 첫 번째 스킬에만 제목 로그 포함
                $skill_msg = $first_skill ? $mo_msg : '';
                $first_skill = false;
                
                // target_ids 배열을 직접 전달하여 use_k_skill 내부에서 처리
                $sk_result = use_k_skill($sk, $mo_unit, $target_ids, $mo_target_type, $ra_id, $skill_msg, $option,$system);
                // use_k_skill은 성공 시 false 반환, 실패 시 에러 메시지(문자열) 반환
                if ($sk_result === false) {
                    $mo_action_done = true;
                }
            }
        }
    } elseif ($mo_act === 'act') {
        // 기본 공격
        $rand_result = get_k_rand_target($ra_id, 1, 'ch', false);
        $target_csv = ses($rand_result, 'csv', '');
        $target_ids = array_filter(explode(',', $target_csv), function($v) {
            return (int)$v > 0;
        });
        foreach ($target_ids as $tid) {
            $atk_result = use_k_action('atk', $mo_unit, (int)$tid, 'ch', $ra_id, $mo_msg, $option, $system);
            // use_k_action도 동일하게 성공 시 false 반환
            if ($atk_result === false) {
                $mo_action_done = true;
            }
            $mo_msg = '';
        }
    }

    // 행동 성공하지 못했을 때만 무행동 로그 삽입
    if (!$mo_action_done) {
        $idle_msg = "<p class=\"log-title\">" . h($mo_unit['unit_name']) . "의 행동</p>";
        $idle_msg .= "<p>" . h($mo_unit['unit_name']) . "은(는) 상황을 보고 있다.</p>";
        if($system){
            $system=h($mo_unit['unit_name']) . "은(는) 상황을 보고 있다.";
        }
        insert_k_log($idle_msg, $ra_id, 'mo', $option, $system);
    }

    return $mo_action_done;
}

/*대미지 셋*/
function set_k_dmg($unit, $type, $value, $rev=false)//대미지처리
{
    global $battle_table;

    $value = (int)$value;
    if ($value === 0) return false;

    $rm_id = ses($unit, 'rm_id', 0, 'int');
    if ($rm_id <= 0) return false;

    $unit_name = ses($unit, 'unit_name', '');
    $type_now  = "{$type}_now";
    $type_max  = "{$type}_max";

    $now = ses($unit, $type_now, 0, 'int');
    $max = ses($unit, $type_max, 0, 'int');

    // 이미 행동불능이면 HP 감소 스킵
    if ($now <= 0 && ($value <= 0 || ($rev !== 'rev' && $type === 'hp'))) return false;

    $now += $value;

    if ($type === 'hp' && $now <= 0) {
        k_unit_dead($rm_id);
        return '<span class="retire">'.h($unit_name).'</span>';
    }

    if ($max > 0 && $now > $max) $now = $max;


    $sql = "UPDATE {$battle_table}_unit SET {$type_now} = '{$now}' WHERE rm_id = '{$rm_id}'";
    sql_query($sql);
    return '';
}
function get_k_dot($ra_id=0, $type='atk', $msg='', $option='')//도트처리
{
    global $g5, $battle_table, $kb_cf;

    $ra_id = (string)$ra_id;
    $type  = ($type === 'atk') ? 'atk' : 'heal';
    $equal = ($type === 'atk') ? '<' : '>';

    $sql = "
        SELECT SUM(bf_value) AS bf_value, rm_id
        FROM {$battle_table}_buff
        WHERE ra_id = '".sql_real_escape_string($ra_id)."'
          AND si_code <> 'buff'
          AND bf_value {$equal} 0
          AND turn_left > 0
        GROUP BY rm_id
    ";
    $buff = sql_query($sql);

    $sk_effect = '';
    for ($i = 0; $row = sql_fetch_array($buff); $i++) {
        $rm_id = (int)$row['rm_id'];
        $unit = sql_fetch("
            SELECT rm_id, unit_id, unit_type, hp_now, hp_max
            FROM {$battle_table}_unit
            WHERE rm_id = '{$rm_id}'
            LIMIT 1
        ");
        if (!$unit) continue;

        if ($unit['unit_type'] === 'ch') {
            $origin = sql_fetch("SELECT ch_name AS unit_name FROM {$g5['character_table']} WHERE ch_id = '".(int)$unit['unit_id']."' LIMIT 1");
        } else {
            $origin = sql_fetch("SELECT mo_name AS unit_name FROM {$g5['k_monster_table']} WHERE mo_id = '".(int)$unit['unit_id']."' LIMIT 1");
        }

        $unit['unit_name'] = $origin ? $origin['unit_name'] : '';
        $delta = (int)$row['bf_value']; // atk: 음수, heal: 양수

        $dead_msg = set_k_dmg($unit, 'hp', $delta);
        $hp_label = ses($kb_cf, 'hp_name', 'hp');
        $sk_effect .= '<p><span class="name">'.$unit['unit_name'].'</span> 의 '.$hp_label.' <span class="dmg '.$type.'">'.abs($delta).'</span>'.$dead_msg.'</p>';
    }

    if ($sk_effect !== '') {
        $msg .= $sk_effect;
        insert_k_log($msg, $ra_id, 'dot', $option);
    }
    return true;
}
function k_unit_dead($rm_id)//사망시 처리
{
    global $battle_table;
    $rm_id = (int)$rm_id;

    sql_query("UPDATE {$battle_table}_unit SET is_stun=0, is_aggr=0, hp_now=0 WHERE rm_id='{$rm_id}'");
    sql_query("DELETE FROM {$battle_table}_buff  WHERE rm_id='{$rm_id}'");
    sql_query("UPDATE {$battle_table}_skill SET sk_cool_now=0 WHERE rm_id='{$rm_id}'");
    return true;
}
