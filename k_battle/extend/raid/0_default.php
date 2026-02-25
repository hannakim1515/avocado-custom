<?php

/*커스텀 스탯, 전투 함수 로드*/
function get_k_status($unit_id, $sc_id, $unit_type)//커스텀 스탯
{
    global $g5;

    if (!$sc_id || !$unit_type) {
        return false;
    }

    $result   = 0;
    $maxvalue = 0;
    $round    = '';
    $minvalue = 0;
    $unit_st  = array();

    // 스탯 정의 목록
    $sl = sql_query("SELECT st_id FROM {$g5['status_config_table']} ORDER BY st_order ASC");

    // 1) 테스트 모드: 배열 그대로 사용
    if ($unit_type === 'test') {
        if (!is_array($unit_id)) {
            return false;
        }
        // unit_id는 st_1..st_N 형태의 배열이어야 함
        $idx = 1;
        while ($row = sql_fetch_array($sl)) {
            $st_id  = $row['st_id'];
            $st_tag = 'st_' . $idx;
            $unit_st[$st_id] = ses($unit_id, $st_tag, 0, 'int');
            $idx++;
        }

    // 2) 캐릭터 모드
    } elseif ($unit_type === 'ch') {
        $unit_id = (int)$unit_id;
        $ch = get_character($unit_id);
        if (empty($ch['ch_id'])) {
            return false;
        }

        $bonus_list = get_k_equip_bonus($unit_id);
        if (!is_array($bonus_list)) $bonus_list = array();

        while ($row = sql_fetch_array($sl)) {
            $st_id = $row['st_id'];
            $sc = sql_fetch("SELECT sc_max FROM {$g5['status_table']} WHERE ch_id = '{$unit_id}' AND st_id = '{$st_id}'");
            $base  = ses($sc, 'sc_max', 0, 'int');
            $bonus = ses($bonus_list, $st_id, 0, 'int');
            $unit_st[$st_id] = $base + $bonus;
        }

    // 3) 몬스터 모드
    } elseif ($unit_type === 'mo') {
        $unit_id = (int)$unit_id;
        $unit = get_k_monster($unit_id);
        if (empty($unit['mo_id'])) {
            return false;
        }

        $idx = 0;
        while ($row = sql_fetch_array($sl)) {
            $st_id  = $row['st_id'];
            $st_tag = 'st_' . ($idx+1);
            $unit_st[$st_id] = ses($unit, $st_tag, 0, 'int');
            $idx++;
        }

    } else {
        return false;
    }

    // 외부 수식 파일에서 $result/$round/$minvalue/$maxvalue 계산
    $inc = G5_PATH . '/k_battle/extend/battle/status.inc.php';
    if (is_file($inc)) {
        include $inc;
    }

    // 최대값 처리
    if (!empty($maxvalue) && $result > $maxvalue) {
        $result = $maxvalue;
    }

    // 반올림 처리
    if ($round === 'round') {
        $result = round($result);
    } elseif ($round === 'ceil') {
        $result = ceil($result);
    } elseif ($round === 'floor') {
        $result = floor($result);
    }

    // 최소값 처리
    if ($result < $minvalue) {
        $result = $minvalue;
    }

    return $result;
}
function get_k_battle_func($type, $unit, $target = '', $bonus = false)//전투 함수
{
    global $g5, $battle_table, $k_unit_stat;

    // 입력 방어
    $type = (string)$type;
    if ($type === '') {
        return array('value' => 0, 'cri' => 0);
    }

    $result    = 0;
    $randbonus = 0;
    $maxvalue  = 0;
    $round     = '';
    $cri       = 0; // 크리티컬 사용 여부 (DB에서 결정)
    $iscri     = 0;
    $minvalue  = 0;

    // 크리티컬 사용 여부를 DB에서 조회 (sc_name = $type)
    $type_esc = function_exists('sql_escape_string') ? sql_escape_string($type) : addslashes($type);
    $row_cri  = sql_fetch("SELECT sc_4 FROM {$g5['k_stat_table']} WHERE sc_name = '{$type_esc}' LIMIT 1");
    if (isset($row_cri['sc_4']) && (int)$row_cri['sc_4'] > 0) {
        $cri = 1;
    }

    $inc = G5_PATH . '/k_battle/extend/battle/battlefunc.inc.php';
    if (is_file($inc)) {
        include $inc;
    }

    // 랜덤 보정
    if (!empty($randbonus)) {
        $from = $result - $randbonus;
        $to   = $result + $randbonus;
        if ($from > $to) {
            $tmp  = $from;
            $from = $to;
            $to   = $tmp;
        }
        $result = mt_rand((int)$from, (int)$to);
    }

    // 상한
    if (!empty($maxvalue) && $result > $maxvalue) {
        $result = $maxvalue;
    }

    // 크리티컬
    if (!empty($cri)) {
        $criper = get_k_battle_func('criper', $unit, $target);
        $crival = get_k_battle_func('crival', $unit, $target);

        $cp = 0.0;
        $cv = 0.0;
        if (is_array($criper) && isset($criper['value'])) {
            $cp = (float)$criper['value'];
        }
        if (is_array($crival) && isset($crival['value'])) {
            $cv = (float)$crival['value'];
        }

        $rand = mt_rand(1, 100);
        if ($cp >= $rand) {
            $result = $result * (1 + ($cv / 100.0));
            $iscri  = 1;
        }
    }

    // 반올림
    if ($round === 'round') {
        $result = round($result);
    } elseif ($round === 'ceil') {
        $result = ceil($result);
    } elseif ($round === 'floor') {
        $result = floor($result);
    }

    // 하한
    if ($result < $minvalue) {
        $result = $minvalue;
    }

    return array(
        'value' => (int)$result,
        'cri'   => $iscri,
    );
}

/*스킬*/
function get_k_skill($id, $custom = false, $select = '*')//캐릭터 스킬 단일
{
    global $g5;

    $id = (int) $id;
    if ($id <= 0) {
        return array();
    }

    $sql_join   = '';
    $sql_search = '';

    if ($custom) {
        $sql_join   = " INNER JOIN {$g5['k_ch_skill_table']} cs ON cs.sk_id = sk.sk_id ";
        $sql_search = " WHERE cs.cs_id = '{$id}' ";
    } else {
        $sql_search = " WHERE sk.sk_id = '{$id}' ";
    }


    $sql = "
        SELECT {$select}
        FROM {$g5['k_skill_table']} sk
        INNER JOIN {$g5['k_skill_info_table']} si ON si.si_id = sk.si_id
        {$sql_join}
        {$sql_search}
    ";

    $row = sql_fetch($sql, false);
    if (!is_array($row)) {
        return array();
    }

   
    $row['sk_icon']    = ses($row, 'sk_icon', '');
    $row['cs_icon']    = ses($row, 'cs_icon', '');

    if (empty($row['sk_icon'])) {
        $row['sk_icon'] = G5_URL.'/k_battle/img/default_skill.png';
    }
    
    if (empty($row['cs_icon'])) {
        $row['cs_icon'] = $row['sk_icon'];
    }else{
        $row['sk_icon'] = $row['cs_icon'];
    }

    if ($custom) {
        $row['sk_content'] = ses($row, 'cs_content', ses($row, 'sk_content', ''));
        $row['sk_name']    = ses($row, 'cs_name', ses($row, 'sk_name', ''));
    }

    return $row;
}
function get_k_skill_list($type, $id, $raid_type = '', $select = '*')//캐릭터 스킬 목록
{
    global $g5, $battle_table;

    $sk_list = array();

    if ($type === 'battle') {
        if (empty($battle_table)) {
            return $sk_list;
        }

        $id = (int) $id;

        $sql = "
            SELECT {$select}
            FROM {$battle_table}_skill bs
            INNER JOIN {$g5['k_ch_skill_table']} cs ON bs.cs_id = cs.cs_id
            INNER JOIN {$g5['k_skill_table']} sk ON cs.sk_id = sk.sk_id
            WHERE bs.rm_id = '{$id}'
            ORDER BY cs.cs_id ASC
        ";
    } else {
        $id = (int) $id;
        if ($id <= 0) {
            return $sk_list;
        }

        $sql_search = " WHERE cs.ch_id = '{$id}' ";

        if ($type === 'use') {
            $sql_search .= " AND cs.cs_use = 1 ";
        }

        if ($raid_type !== '') {
            $raid_type = sql_escape_string($raid_type);
            $sql_search .= " AND sk.raid_type LIKE '%{$raid_type}%' ";
        }

        $sql = "
            SELECT {$select}
            FROM {$g5['k_ch_skill_table']} cs
            INNER JOIN {$g5['k_skill_table']} sk ON cs.sk_id = sk.sk_id
            INNER JOIN {$g5['k_skill_info_table']} si ON si.si_id = sk.si_id
            {$sql_search}
            ORDER BY cs.cs_use DESC, cs.cs_id ASC
        ";
    }

    $res = sql_query($sql, false);
    if (!$res) {
        return $sk_list;
    }

    while ($row = sql_fetch_array($res)) {
        $row['sk_icon']    = ses($row, 'sk_icon', '');
        $row['cs_icon']    = ses($row, 'cs_icon', '');

        if (empty($row['sk_icon'])) {
            $row['sk_icon'] = G5_URL.'/k_battle/img/default_skill.png';
        }
        
        if (empty($row['cs_icon'])) {
            $row['cs_icon'] = $row['sk_icon'];
        }else{
            $row['sk_icon'] = $row['cs_icon'];
        }
        $row['sk_content'] = ses($row, 'cs_content', ses($row, 'sk_content', ''));
        $row['sk_name']    = ses($row, 'cs_name', ses($row, 'sk_name', ''));
       
    
        $sk_list[] = $row;
    }

    return $sk_list;
}
function get_k_mo_skill($sk_id, $mo_id = 0, $select = '*')//몬스터 스킬 단일
{
    global $g5;

    $sk_id = (int) $sk_id;
    if ($sk_id <= 0) {
        return array();
    }

    $mo_id = (int) $mo_id;

    // mo_id가 있으면 해당 몬스터의 커스텀 정보와 JOIN
    if ($mo_id > 0) {
        $sql = "
            SELECT {$select}
            FROM {$g5['k_skill_table']} sk
            INNER JOIN {$g5['k_skill_info_table']} si ON si.si_id = sk.si_id
            LEFT JOIN {$g5['k_mo_skill_table']} ms ON ms.sk_id = sk.sk_id AND ms.mo_id = '{$mo_id}'
            WHERE sk.sk_id = '{$sk_id}'
        ";
    } else {
        $sql = "
            SELECT {$select}
            FROM {$g5['k_skill_table']} sk
            INNER JOIN {$g5['k_skill_info_table']} si ON si.si_id = sk.si_id
            WHERE sk.sk_id = '{$sk_id}'
        ";
    }

    $row = sql_fetch($sql, false);
    if (!is_array($row)) {
        return array();
    }

    // 커스텀 값이 있으면 적용 (sk_* 값을 덮어씌움, 단 sk_target_cnt는 유지)
    if (!empty($row['cs_name'])) {
        $row['sk_name'] = $row['cs_name'];
    }
    if (!empty($row['cs_content'])) {
        $row['sk_content'] = $row['cs_content'];
    }
    if (!empty($row['cs_icon'])) {
        $row['sk_icon'] = $row['cs_icon'];
    }
    if (!empty($row['cs_img'])) {
        $row['sk_img'] = $row['cs_img'];
    }

    if (empty($row['sk_icon'])) {
        $row['sk_icon'] = G5_URL.'/k_battle/img/default_skill.png';
    }

    return $row;
}
function get_k_mo_skill_list($mo_id, $raid_type = '', $select = '*')//몬스터 스킬 목록
{
    global $g5;

    $sk_list = array();
    $mo_id = (int) $mo_id;

    if ($mo_id <= 0) {
        return $sk_list;
    }

    $sql_search = " WHERE ms.mo_id = '{$mo_id}' ";

    if ($raid_type !== '') {
        $raid_type = sql_escape_string($raid_type);
        $sql_search .= " AND sk.raid_type LIKE '%{$raid_type}%' ";
    }

    $sql = "
        SELECT {$select}
        FROM {$g5['k_mo_skill_table']} ms
        INNER JOIN {$g5['k_skill_table']} sk ON ms.sk_id = sk.sk_id
        INNER JOIN {$g5['k_skill_info_table']} si ON si.si_id = sk.si_id
        {$sql_search}
        ORDER BY ms.cs_id ASC
    ";

    $res = sql_query($sql, false);
    if (!$res) {
        return $sk_list;
    }

    while ($row = sql_fetch_array($res)) {
        // 커스텀
        if (!empty($row['cs_name'])) {
            $row['sk_name'] = $row['cs_name'];
        }
        if (!empty($row['cs_content'])) {
            $row['sk_content'] = $row['cs_content'];
        }
        if (!empty($row['cs_icon'])) {
            $row['sk_icon'] = $row['cs_icon'];
        }
        if (!empty($row['cs_img'])) {
            $row['sk_img'] = $row['cs_img'];
        }

        if (empty($row['sk_icon'])) {
            $row['sk_icon'] = G5_URL.'/k_battle/img/default_skill.png';
        }

        $sk_list[] = $row;
    }

    return $sk_list;
}

/*장비*/
function get_k_equip_bonus($ch_id)//장비 보너스
{
    global $g5;

    $bonus_list = array();

    $stat_res = sql_query("SELECT st_id FROM {$g5['status_config_table']} ORDER BY st_order ASC LIMIT 10", false);
    if (!$stat_res) {
        return $bonus_list;
    }

    $i = 0;
    while ($row = sql_fetch_array($stat_res)) {
        $tag = 'st_'.(++$i);

        $sql = "
            SELECT SUM({$tag}) AS value
            FROM {$g5['k_ch_equip_table']} eq
            INNER JOIN {$g5['inventory_table']} inven ON eq.in_id = inven.in_id
            WHERE inven.ch_id = '".sql_escape_string($ch_id)."'
              AND eq.eq_use != ''
        ";

        $bonus = sql_fetch($sql, false);
        $bonus_list[$row['st_id']] = ses($bonus, 'value', 0, 'int');
    }

    return $bonus_list;
}
function k_equip_upgrade($in_id, $ug_in_id = '')//장비 강화
{
    global $g5, $character, $member, $kb_cf;

    $result = array(
        'result' => '',
        'msg'    => '',
        'plus'   => '',
        'html'   => '',
    );

    // 전체 업그레이드 최대 레벨 한도 설정 (설정값 없으면 테이블 기준)
    if (empty($kb_cf['upgrade_limit'])) {
        $max = sql_fetch("SELECT ug_id FROM {$g5['k_upgrade_table']} ORDER BY ug_id DESC LIMIT 1", false);
        $kb_cf['upgrade_limit'] = ses($max, 'ug_id', 0, 'int');
    }

    if (empty($character['ch_rank'])) {
        $character['ch_rank'] = 0;
    }

    $eq = get_k_equip_item($in_id, "eq.*, it.*, ug.ug_name", true);

    if (empty($eq['eq_id'])) {
        $result['result'] = 'alert';
        $result['msg']    = '장비 정보가 잘못되었습니다.';
        return $result;
    }

    // 강화 금지 장비
    if (isset($eq['ug_limit']) && (string)$eq['ug_limit'] === '9999') {
        $result['result'] = 'alert';
        $result['msg']    = '강화 불가 장비입니다.';
        return $result;
    }

    if (empty($eq['ug_id'])) {
        $eq['ug_id'] = 0;
    }

    // 장비 개별 제한이 전체 제한보다 낮으면 그 값 우선
    if (!empty($eq['ug_limit'])) {
        $eq_ug_limit = (int)$eq['ug_limit'];
        if (empty($kb_cf['upgrade_limit']) || $eq_ug_limit < (int)$kb_cf['upgrade_limit']) {
            $kb_cf['upgrade_limit'] = $eq_ug_limit;
        }
    }

    // 더 이상 강화 불가 체크
    if (!empty($kb_cf['upgrade_limit']) && (int)$kb_cf['upgrade_limit'] < (int)$eq['ug_id']) {
        $result['result'] = 'alert';
        $result['msg']    = '더 이상 강화할 수 없습니다.';
        return $result;
    }

    // 다음 강화 구간 검색
    $ug = sql_fetch("
        SELECT *
        FROM {$g5['k_upgrade_table']}
        WHERE ug_id > '{$eq['ug_id']}'
          AND (ch_rank = '0' OR ch_rank <= '{$character['ch_rank']}')
        ORDER BY ug_id ASC
        LIMIT 1
    ", false);

    if (empty($ug['ug_id'])) {
        $result['result'] = 'alert';
        $result['msg']    = '더 이상 강화할 수 없습니다.';
        return $result;
    }

    $ug['ug_id']       = (int)$ug['ug_id'];
    $ug['ug_min']      = ses($ug, 'ug_min', 0, 'int');
    $ug['ug_max']      = ses($ug, 'ug_max', 0, 'int');
    $ug['ug_per']      = ses($ug, 'ug_per', 0, 'int');
    $ug['ug_use_money']= !empty($ug['ug_use_money']);
    $ug['ug_use_it']   = !empty($ug['ug_use_it']);

    // 포인트 소모
    if ($ug['ug_use_money'] && !empty($ug['ug_money'])) {
        $ug['ug_money'] = (int)$ug['ug_money'];

        if (empty($member['mb_point']) || $ug['ug_money'] > (int)$member['mb_point']) {
            $result['result'] = 'alert';
            $result['msg']    = '소지금이 부족합니다.';
            return $result;
        }

        insert_point(
            $member['mb_id'],
            $ug['ug_money'] * (-1),
            "[{$eq['eq_name']}] [{$ug['ug_name']}] 강화 시도"
        );
    }

    // 재료 아이템 소모
    if ($ug['ug_use_it'] && !empty($ug['ug_item'])) {
        $ug_it = sql_fetch("
            SELECT in_id
            FROM {$g5['inventory_table']}
            WHERE in_id = '".sql_escape_string($ug_in_id)."'
              AND ch_id = '".sql_escape_string($character['ch_id'])."'
        ", false);

        if (empty($ug_it['in_id'])) {
            $result['result'] = 'alert';
            $result['msg']    = '필요 아이템이 부족합니다.';
            return $result;
        }

        delete_inventory($ug_in_id);
    }

    // 성공 여부 판단
    $rand   = mt_rand(1, 100);
    $ug_per = "{$rand}/{$ug['ug_per']}";

    $ug_result = ($ug['ug_per'] >= $rand) ? '성공' : '실패';

    $plus_rand = 0;
    $st        = array();
    $st_name   = '';

    if ($ug_result === '성공') {
        // 능력치 목록
        $stat_res = sql_query("
            SELECT st_id, st_name
            FROM {$g5['status_config_table']}
            ORDER BY st_order ASC
            LIMIT 10
        ", false);

        $st_rand = array();
        if ($stat_res) {
            $idx = 0;
            while ($row = sql_fetch_array($stat_res)) {
                $idx++;
                $tag = 'st_'.$idx;
                $row['st_tag'] = $tag;
                $st[$row['st_id']] = $row;
                $st_rand[] = $row['st_id'];
            }
        }

        // 커스텀 랜덤 옵션 처리
        if (!empty($eq['it_2'])) {
            $ug_cnt = sql_fetch("
                SELECT COUNT(*) AS cnt
                FROM {$g5['k_upgrade_table']}
                WHERE ug_id <= '{$ug['ug_id']}'
            ", false);

            $cnt = !empty($ug_cnt['cnt']) ? ((int)$ug_cnt['cnt'] - 1) : 0;
            if ($cnt < 0) $cnt = 0;

            $st_list  = explode("|", (string)$eq['it_3']);
            $min_list = explode("|", (string)$eq['it_4']);
            $max_list = explode("|", (string)$eq['it_5']);

            if (!empty($st_list[$cnt])) {
                if ((int)$st_list[$cnt] === 99 && !empty($st_rand)) {
                    shuffle($st_rand);
                    $eq['st_id'] = $st_rand[0];
                } else {
                    $eq['st_id'] = (int)$st_list[$cnt];
                }
            }

            if (isset($min_list[$cnt])) {
                $ug['ug_min'] = (int)$min_list[$cnt];
            }
            if (isset($max_list[$cnt])) {
                $ug['ug_max'] = (int)$max_list[$cnt];
            }
        }

        // 적용할 스탯 태그 확인
        $st_id = ses($eq, 'st_id', 0, 'int');
        $st_tag = ($st_id && isset($st[$st_id]['st_tag'])) ? $st[$st_id]['st_tag'] : '';

        if ($st_tag && $ug['ug_min'] <= $ug['ug_max']) {
            $plus_rand = mt_rand($ug['ug_min'], $ug['ug_max']);
            $st_name   = $st[$st_id]['st_name'];

            $sql = "
                UPDATE {$g5['k_ch_equip_table']}
                SET {$st_tag} = {$st_tag} + {$plus_rand},
                    ug_id = '{$ug['ug_id']}'
                WHERE eq_id = '{$eq['eq_id']}'
            ";
            sql_query($sql, false);
        }
    }

    // 실패 시 단계 유지
    if ($ug_result === '실패') {
        $ug['ug_name'] = ses($eq, 'ug_name', $ug['ug_name']);
        $ug['ug_id']   = ses($eq, 'ug_id', $ug['ug_id'], 'int');
    }

    // 표기용 레벨 텍스트
    $eq_lv = '';
    if (!empty($ug['ug_name'])) {
        $eq_lv = '<span class="eq_lv lv_'.$ug['ug_id'].'">'.$ug['ug_name'].'</span>';
    }

    // 로그 저장
    $ug_log = '<span class="eq_name">'.ses($eq, 'eq_name', '').'</span> '
            . '<span class="ug_name '.ses($ug, 'ug_name', '').'">'.ses($ug, 'ug_name', '').'</span> '
            . '강화에 <span class="result">'.$ug_result.'</span>하였습니다.';

    $time = G5_TIME_YMDHIS;

    sql_query("
        INSERT INTO {$g5['k_upgrade_log_table']}
            (ch_id, ug_money, ug_item, ug_name, ug_per, ug_log,
             eq_id, st_id, ug_plus, ug_datetime)
        VALUES (
            '".sql_escape_string($character['ch_id'])."',
            '".ses($ug, 'ug_money', 0, 'int')."',
            '".sql_escape_string(ses($ug, 'ug_item', ''))."',
            '".sql_escape_string(ses($ug, 'ug_name', ''))."',
            '".sql_escape_string($ug_per)."',
            '".sql_escape_string($ug_log)."',
            '".sql_escape_string($eq['eq_id'])."',
            '".ses($eq, 'st_id', 0, 'int')."',
            '".(int)$plus_rand."',
            '".sql_escape_string($time)."'
        )
    ", false);

    // 반환값
    $result['result'] = $ug_result;
    $result['msg']    = $ug_log;
    if ($ug_result === '성공' && $plus_rand > 0 && $st_name !== '') {
        $result['plus'] = $st_name.' + '.$plus_rand;
    }
    $result['html']   = '<img src="'.ses($eq, 'eq_img', '').'">'."$eq_lv";

    return $result;
}
function insert_k_equip($it)//장비 등록
{
    global $g5;

    if (empty($it['in_id']) || empty($it['it_id'])) {
        return false;
    }

    $st = array();
    $stat_res = sql_query("
        SELECT st_id
        FROM {$g5['status_config_table']}
        ORDER BY st_order ASC
        LIMIT 10
    ", false);

    if ($stat_res) {
        $idx = 0;
        while ($row = sql_fetch_array($stat_res)) {
            $idx++;
            $st[$row['st_id']] = 'st_'.$idx;
        }
    }

    $sql = "
        INSERT INTO {$g5['k_ch_equip_table']}
            (it_id, in_id, eq_img, eq_name, eq_content)
        VALUES (
            '".ses($it, 'it_id', '')."',
            '".ses($it, 'in_id', '')."',
            '".ses($it, 'it_img', '')."',
            '".ses($it, 'it_name', '')."',
            '".ses($it, 'it_content', '')."'
        )
    ";

    if (!empty($it['st_id']) && isset($st[$it['st_id']]) && isset($it['it_value'])) {
        $tag = $st[$it['st_id']];
        $val = (int)$it['it_value'];

        $sql = "
            INSERT INTO {$g5['k_ch_equip_table']}
                (it_id, in_id, eq_img, eq_name, eq_content, {$tag})
            VALUES (
                '".ses($it, 'it_id', '')."',
                '".ses($it, 'in_id', '')."',
                '".ses($it, 'it_img', '')."',
                '".ses($it, 'it_name', '')."',
                '".ses($it, 'it_content', '')."',
                '{$val}'
            )
        ";
    }

    sql_query($sql, false);

    return true;
}
function get_k_equip_item($in_id, $select = "*", $check = false)//장비
{
    global $g5;

    $in_id = sql_escape_string($in_id);

    $sql = "
        SELECT {$select}
        FROM {$g5['inventory_table']} inven
        INNER JOIN {$g5['item_table']} it ON inven.it_id = it.it_id
        INNER JOIN {$g5['k_ch_equip_table']} eq ON eq.in_id = inven.in_id
        LEFT JOIN {$g5['k_upgrade_table']} ug ON ug.ug_id = eq.ug_id
        WHERE inven.in_id = '{$in_id}'
    ";

    $row = sql_fetch($sql, false);

    // 장비 레코드 없고, 체크 옵션 사용 시 자동 생성
    if (empty($row['eq_id']) && $check) {
        $it = sql_fetch("
            SELECT
                inven.in_id,
                it.it_name,
                it.it_content,
                it.it_img,
                it.it_id,
                it.st_id,
                it.it_value
            FROM {$g5['inventory_table']} inven
            INNER JOIN {$g5['item_table']} it ON inven.it_id = it.it_id
            WHERE inven.in_id = '{$in_id}'
              AND it.it_type = '장비(K)'
        ", false);

        if (!empty($it['in_id'])) {
            insert_k_equip($it);
            $row = sql_fetch($sql, false);
        }
    }

    return is_array($row) ? $row : array();
}


/*랜덤 텍스트 출력(mmb레이드)*/
function get_k_rand_text($text, $delimiter = '<br />')
{
    $text_list = explode($delimiter, (string) $text);
    $text_list = array_values(array_filter($text_list, function($v) {
        return $v !== '';
    }));

    $count = count($text_list);
    if ($count === 0) {
        return '';
    }

    $idx = mt_rand(0, $count - 1);
    return trim($text_list[$idx]);
}

/*몬스터*/
function get_k_monster($mo_id, $select = '*')
{
    global $g5;

    $mo_id = (int)$mo_id;
    if ($mo_id <= 0) {
        return array();
    }

    $sql = "SELECT {$select} FROM {$g5['k_monster_table']} WHERE mo_id = {$mo_id}";
    return sql_fetch($sql);
}
function get_k_monster_list($select = '*', $raid_type = '')
{
    global $g5;

    $mo_list = array();
    $sql_where = '';
    
    // raid_type 필터 (빈 값이거나 'all'이면 전체, CSV 저장이므로 LIKE 사용)
    if ($raid_type !== '' && $raid_type !== 'all') {
        $raid_type = sql_escape_string($raid_type);
        $sql_where = " WHERE raid_type LIKE '%{$raid_type}%' ";
    }
    
    $sql = "SELECT {$select} FROM {$g5['k_monster_table']} {$sql_where}";
    $mo = sql_query($sql);

    while ($row = sql_fetch_array($mo)) {
        $mo_list[] = $row;
    }

    return $mo_list;
}

/*유닛 */
function get_k_buff($rm_id, $detail = false)//버프 불러오기 ($detail=true면 상세 목록도 반환)
{
    global $g5, $battle_table, $k_unit_stat, $kb_cf;

    $rm_id = (int)$rm_id;
    $result = array();
    $buff_list = array('buff' => array(), 'debuff' => array());

    // 스탯 이름 캐시 ($detail=true일 때만 필요)
    static $sc_names = null;
    if ($detail && $sc_names === null) {
        $sc_names = array();
        $sc_sql = sql_query("SELECT sc_id, sc_name FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
        while ($row = sql_fetch_array($sc_sql)) {
            $sc_names[(int)$row['sc_id']] = $row['sc_name'];
        }
    }

    // 개별 버프 조회 (합산용 + 상세용)
    $q = sql_query("
        SELECT sc_id, si_code, bf_value, turn_left
        FROM {$battle_table}_buff
        WHERE turn_left > 0
          AND rm_id = '{$rm_id}'
    ");

    // 합산용 임시 배열
    $sum_data = array();

    while ($row = sql_fetch_array($q)) {
        $sc_id = (int)$row['sc_id'];
        $si_code = ses($row, 'si_code', '');
        $bf_value = (int)$row['bf_value'];
        $turn_left = (int)$row['turn_left'];

        // 스탯 버프만 합산 (si_code = 'buff')
        if ($si_code === 'buff' && isset($k_unit_stat[$sc_id])) {
            $key = $k_unit_stat[$sc_id];
            $sum_data[$key] = ses($sum_data, $key, 0, 'int') + $bf_value;
        }

        // 상세 목록 ($detail=true일 때만)
        if ($detail) {
            if ($si_code === 'buff') {
                $name = ses($sc_names, $sc_id, '');
            } else {
                $name = ses($kb_cf, 'hp_name', 'HP');
            }

            $item = array(
                'name' => $name,
                'value' => $bf_value,
                'turn' => $turn_left
            );

            if ($bf_value > 0) {
                $buff_list['buff'][] = $item;
            } else {
                $buff_list['debuff'][] = $item;
            }
        }
    }

    $result = $sum_data;

    // $detail=true면 합산값과 상세 목록 모두 반환
    if ($detail) {
        return array(
            'sum' => $result,
            'list' => $buff_list
        );
    }

    return $result;
}
function get_k_unit($rm_id, $unit_type, $select = "*", $id_type = 'rm_id')
{
    global $g5, $battle_table;

    $rm_id = (int)$rm_id;
    if ($rm_id <= 0) {
        return array();
    }

    if ($unit_type === 'mo') {
        $origin_table = $g5['k_monster_table'];
        $origin_id = 'mo_id';
        if($select=="*"){
            $select = "unit.*,
            origin.mo_name as mo_name,
            origin.mo_id as mo_id,
            origin.mo_thumb as mo_thumb,
            origin.mo_1 as mo_1
            ";
        }
    } else {
        $origin_table = $g5['character_table'];
        $origin_id = 'ch_id';
    }

    $sql = "
        SELECT {$select}
        FROM {$battle_table}_unit AS unit
        JOIN {$origin_table} AS origin
          ON unit.unit_id = origin.{$origin_id}
        WHERE unit.{$id_type} = '{$rm_id}'
        LIMIT 1
    ";

    $result = sql_fetch($sql);

    $buff = get_k_buff($rm_id);
    if (is_array($buff)) {
        foreach ($buff as $key => $value) {
            $plus_key = $key . '_buff';
            $base = ses($result, $key, 0, 'int');
            $val = $base + (int)$value;
            if ($val < 0) $val = 0;
            $result[$key] = $val;
            $result[$plus_key] = (int)$value;
        }
    }

    return $result;
}
function get_k_unit_list($type = 'ch', $ra_id = 0, $select = '*', $where = '', $order = '', $buff = false)
{
    global $g5, $battle_table;

    $ra_id = (string)$ra_id;

    if ($type === 'mo') {
        $origin_table = $g5['k_monster_table'];
    } else {
        $origin_table = $g5['character_table'];
    }

    $result = array();

    $sql = "
        SELECT {$select}
        FROM {$battle_table}_unit AS unit
        JOIN {$origin_table} AS origin
          ON unit.unit_id = origin.{$type}_id
        WHERE unit.ra_id = '{$ra_id}'
          AND unit.unit_type = '{$type}'
          {$where}
          {$order}
    ";

    $list = sql_query($sql);

    while ($row = sql_fetch_array($list)) {
        if ($buff) {
            // 합산값과 상세 목록을 한 번에 조회
            $buff_data = get_k_buff($row['rm_id'], true);
            $sum_data = ses($buff_data, 'sum', array(), 'array');
            $row['buff_list'] = ses($buff_data, 'list', array('buff' => array(), 'debuff' => array()), 'array');

            if (is_array($sum_data)) {
                foreach ($sum_data as $key => $value) {
                    $plus_key = $key . '_buff';
                    $base = ses($row, $key, 0, 'int');
                    $val = $base + (int)$value;
                    if ($val < 0) $val = 0;
                    $row[$key] = $val;
                    $row[$plus_key] = (int)$value;
                }
            }
        } else {
            $row['buff_list'] = array('buff' => array(), 'debuff' => array());
        }
        $result[] = $row;
    }

    return $result;
}
function get_k_rand_target($ra_id, $count, $unit_type = 'ch', $detail = false, $select = '*') //랜덤 타겟 지정
{
    global $g5, $battle_table;

    $count = (int)$count;
    if ($count <= 0) {
        $count = 1;
    }

    $rm_ids = array();
    $rm_names = array();

    if ($unit_type === 'ch') {
        $origin_table = $g5['character_table'];
        $origin_id = 'ch_id';
        $name_col = 'ch_name';
    } else {
        $origin_table = $g5['k_monster_table'];
        $origin_id = 'mo_id';
        $name_col = 'mo_name';
    }

    $ra_id_esc = sql_real_escape_string($ra_id);

    // 1. 도발 상태인 유닛 수 확인
    $aggr_row = sql_fetch("
        SELECT COUNT(*) AS cnt 
        FROM {$battle_table}_unit 
        WHERE unit_type = '{$unit_type}' 
          AND is_aggr > 0 
          AND hp_now > 0 
          AND ra_id = '{$ra_id_esc}'
    ");
    $aggr_cnt = (int)$aggr_row['cnt'];

    // 2. 도발 유닛 우선 선택
    if ($aggr_cnt > 0) {
        $take_aggr = min($aggr_cnt, $count);
        $count -= $take_aggr;

        $sql = "
            SELECT unit.rm_id, origin.{$name_col} AS unit_name
            FROM {$battle_table}_unit unit
            INNER JOIN {$origin_table} origin ON unit.unit_id = origin.{$origin_id}
            WHERE unit.unit_type = '{$unit_type}' 
              AND unit.is_aggr > 0 
              AND unit.hp_now > 0 
              AND unit.ra_id = '{$ra_id_esc}'
            ORDER BY RAND() 
            LIMIT {$take_aggr}
        ";
        $list = sql_query($sql);
        while ($row = sql_fetch_array($list)) {
            $rm_ids[] = (int)$row['rm_id'];
            $rm_names[] = $row['unit_name'];
        }
    }

    // 3. 남은 수만큼 일반 유닛에서 선택
    if ($count > 0) {
        $sql = "
            SELECT unit.rm_id, origin.{$name_col} AS unit_name
            FROM {$battle_table}_unit unit
            INNER JOIN {$origin_table} origin ON unit.unit_id = origin.{$origin_id}
            WHERE unit.unit_type = '{$unit_type}' 
              AND unit.is_aggr = 0 
              AND unit.hp_now > 0 
              AND unit.ra_id = '{$ra_id_esc}'
            ORDER BY RAND() 
            LIMIT {$count}
        ";
        $list = sql_query($sql);
        while ($row = sql_fetch_array($list)) {
            $rm_ids[] = (int)$row['rm_id'];
            $rm_names[] = $row['unit_name'];
        }
    }

    $csv = implode(',', $rm_ids);

    if ($detail && !empty($csv)) {
        $rm_list = get_k_unit_list($unit_type, $ra_id, $select, "AND unit.rm_id IN ({$csv})", '', true);
        return array('csv' => $csv, 'name' => $rm_names, 'list' => $rm_list);
    }

    return array('csv' => $csv, 'name' => $rm_names, 'list' => array());
}

/*데이터관리*/
function get_k_passive_bonus($ch_id, $sc_id, $default_stat) //패시브 스킬 보너스
{
    global $g5;

    $ch_id = (int)$ch_id;
    $sc_id = (int)$sc_id;

    $total_bonus = 0;

    $q = sql_query("
        SELECT sk.bonus_calc, sk.sk_value, sk.sc_id
        FROM {$g5['k_ch_skill_table']} cs
        INNER JOIN {$g5['k_skill_table']} sk ON sk.sk_id = cs.sk_id
        WHERE cs.ch_id = '{$ch_id}'
            AND sk.sk_target = 'passive'
            AND sk.target_sc = '{$sc_id}'
            AND cs.cs_use = 1
    ");

    for ($i = 0; $row = sql_fetch_array($q); $i++) {
        $calc = $row['bonus_calc'];
        $val  = (float)$row['sk_value'];
        $src  = ses($default_stat, $row['sc_id'], 0, 'int');

        if ($calc === 'p') {
            $bonus = $src + $val;
        } elseif ($calc === 'm') {
            $bonus = round(($src ? $src : 1) * $val);
        } else {
            $bonus = 0;
        }
        $total_bonus += (int)$bonus;
    }
    return (int)$total_bonus;
}
function insert_k_battle_unit($unit_id, $unit_type, $raid_type, $ra_id = 0) //전투 유닛 등록
{
    global $g5, $battle_table, $k_stat, $kb_cf;

    $unit_id = (int)$unit_id;
    $ra_id = (string)$ra_id;

    if ($unit_id <= 0) {
        return false;
    }

    if ($unit_type === 'ch') {
        $unit = get_character($unit_id);
        $origin_id = 'ch_id';
    } elseif ($unit_type === 'mo') {
        $unit = get_k_monster($unit_id);
        $origin_id = 'mo_id';
    } else {
        return false;
    }

    if (empty($unit[$origin_id])) {
        return false;
    }

    // HP/MP
    if ($unit_type === 'mo') {
        $hp_max = ses($unit, 'mo_hp', 0, 'int');
        $mp_max = ses($unit, 'mo_mp', 0, 'int');
    } else {
        $bonus_list = get_k_equip_bonus($unit_id);

        $hp_row = sql_fetch("SELECT sc_max FROM {$g5['status_table']} WHERE ch_id = '{$unit_id}' AND st_id = '{$kb_cf['hp']}'");
        $hp_max = ses($hp_row, 'sc_max', 0, 'int');
        $hp_max += ses($bonus_list, $kb_cf['hp'], 0, 'int');

        $mp_row = sql_fetch("SELECT sc_max FROM {$g5['status_table']} WHERE ch_id = '{$unit_id}' AND st_id = '{$kb_cf['mp']}'");
        $mp_max = ses($mp_row, 'sc_max', 0, 'int');
        $mp_max += ses($bonus_list, $kb_cf['mp'], 0, 'int');   
    }

    // 스탯
    $st_set = '';
    $st_value = array();

    if (!empty($k_stat) && is_array($k_stat)) {
        for ($i = 0; $i < count($k_stat); $i++) {
            if (empty($k_stat[$i])) continue;
            $val = get_k_status($unit_id, $k_stat[$i], $unit_type);
            $st_value[$k_stat[$i]] = (int)$val;
        }

        for ($i = 0; $i < count($k_stat); $i++) {
            if (empty($k_stat[$i])) continue;

            $bonus = 0;
            $bonus = (int)get_k_passive_bonus($unit_id, $k_stat[$i], $st_value);
            
            $this_value = ses($st_value, $k_stat[$i], 0, 'int') + $bonus;
            $st_set .= ", st_".($i+1)." = {$this_value}";
        }
    }

    $sql = "
        INSERT INTO {$battle_table}_unit
            SET unit_id = '{$unit[$origin_id]}',
                unit_type = '{$unit_type}',
                hp_max = '{$hp_max}',
                hp_now = '{$hp_max}',
                mp_max = '{$mp_max}',
                mp_now = '{$mp_max}',
                ra_id = '{$ra_id}'
                {$st_set}
    ";
    sql_query($sql);

    // 스킬 세팅 (캐릭터만)
    if ($unit_type !== 'mo') {
        $sk_list = get_k_skill_list('use', $unit_id, $raid_type);
        $unit_row = sql_fetch("
            SELECT rm_id, unit_id, unit_type
            FROM {$battle_table}_unit
            WHERE unit_id = {$unit_id}
              AND unit_type = '{$unit_type}'
            ORDER BY rm_id DESC
            LIMIT 1
        ");

        if (!empty($unit_row['rm_id']) && is_array($sk_list)) {
            foreach ($sk_list as $sk) {
                if (ses($sk, 'sk_target', '') === 'passive') {
                    continue;
                }
                $cs_id = ses($sk, 'cs_id', 0, 'int');
                if ($cs_id <= 0) continue;

                sql_query("
                    INSERT INTO {$battle_table}_skill
                        SET cs_id = '{$cs_id}',
                            rm_id = '{$unit_row['rm_id']}',
                            unit_id = '{$unit_row['unit_id']}',
                            unit_type = '{$unit_row['unit_type']}',
                            ra_id = '{$ra_id}'
                ");
            }
        }
    }

    return true;
}
function delete_k_battle_unit($rm_id) //전투 유닛 삭제
{
    global $battle_table;

    $rm_id = (int)$rm_id;
    if ($rm_id <= 0) {
        return false;
    }

    sql_query("DELETE FROM {$battle_table}_unit WHERE rm_id = {$rm_id}");
    sql_query("DELETE FROM {$battle_table}_skill WHERE rm_id = {$rm_id}");
    sql_query("DELETE FROM {$battle_table}_buff WHERE rm_id = {$rm_id}");

    return true;
}
function delete_k_battle_data($battle_table, $ra_id, $type = '') //전투 데이터 삭제
{
    $ra_id = sql_real_escape_string($ra_id);

    // log, buff가 아닌 경우에만 유닛/스킬 삭제
    if ($type !== 'log' && $type !== 'buff') {
        sql_query("DELETE FROM {$battle_table}_skill WHERE ra_id = '{$ra_id}'");
        sql_query("DELETE FROM {$battle_table}_unit WHERE ra_id = '{$ra_id}'");
    }

    // log가 아닌 경우 버프 삭제
    if ($type !== 'log') {
        sql_query("DELETE FROM {$battle_table}_buff WHERE ra_id = '{$ra_id}'");
    }

    // 로그는 항상 정리 (또는 조건에 맞게 조정)
    sql_query("DELETE FROM {$battle_table}_log WHERE ra_id = '{$ra_id}'");

    return true;
}








