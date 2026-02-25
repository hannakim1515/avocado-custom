<?php
// JSON 응답
header('Content-Type: application/json');

require_once './_common.php';

/* ---------- 입력 방어 ---------- */

$rm_id        = ses($_REQUEST, 'rm_id', 0, 'int');
$type         = ses($_REQUEST, 'type', '', 'raw');
$turn_type    = ses($_REQUEST, 'turn_type', '', 'raw');
$bs_id        = ses($_REQUEST, 'bs_id', 0, 'int');
$act          = ses($_REQUEST, 'act', '', 'raw');
$notmyturn = false;
$tt_check = false;
$target_load = true;

if ($battle_table === '' || $rm_id <= 0) {
    echo json_encode(array('warning' => '요청이 유효하지 않습니다.'), JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- 기본 구조 ---------- */
$data = array(
    'warning' => '',
    'info'    => '',
    'disable' => '',
    'target'  => ''
);

/* ---------- 현재 유닛 조회 ---------- */
$rm = sql_fetch("
    SELECT unit_id, hp_now, mp_now, unit_type, tt_done
    FROM {$battle_table}_unit
    WHERE rm_id = '{$rm_id}'
");

if (empty($rm['unit_type'])) {
    $data['warning'] = '대상 정보를 찾을 수 없습니다.';
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- 턴 및 상태 검사 ---------- */
if ((int)$rm['hp_now'] <= 0) {
    $data['warning'] = '현재 행동불능 상태입니다.';
}else{
    switch ($raid_type) {
        case 'mmbraid': 
            if ($turn_type === 'all' || ($turn_type === 'skill' && $type === 'skill')) $tt_check = true;
            break;
        case 'realtime':
               $ra = sql_fetch("SELECT now_turn FROM {$battle_table} WHERE ra_id = '{$ra_id}'");
               $notmyturn=k_turn_type_check($turn_type, $ra['now_turn'], $rm_id);
            break;
        default:
            # code...
            break;
    }
}

if($tt_check&&(int)$rm['tt_done'] === 1){
    $data['warning'] = '이번 턴 행동이 종료되었습니다.';
}elseif($notmyturn){
    $data['warning'] = "내 턴이 아닙니다.";
}

/* ---------- 액션별 처리 ---------- */
$target_list = false;  
$target_type = '';     

if ($data['warning'] === '') {

    if ($type === 'atk') {
        $data['info'] = '지정한 적 1체를 공격합니다.';
        $target_type  = ($rm['unit_type'] === 'ch') ? 'mo' : 'ch';
        $target_list  = true;

    } elseif ($type === 'heal') {
        $data['info'] = '지정한 아군 1명을 치유합니다.';
        $target_type  = (string)$rm['unit_type'];
        $target_list  = true;

    } elseif ($type === 'item') {
        $target_load = false;
        $data['info']  = '아이템을 사용합니다.';
        $target_type   = 'item';
        $target_list   = array();

        $unit_id = (int)$rm['unit_id'];
        $it_list = sql_query("
            SELECT inven.in_id, it.it_name, it.it_type, inven.se_ch_id, it.it_value
            FROM {$g5['item_table']} AS it
            JOIN {$g5['inventory_table']} AS inven ON inven.it_id = it.it_id
            WHERE inven.ch_id = '{$unit_id}'
              AND (it.it_type = 'HP회복(K)' OR it.it_type = 'MP회복(K)')
            ORDER BY it.it_type ASC, it.it_id ASC, inven.se_ch_id ASC
        ");

        for ($i = 0; $row = sql_fetch_array($it_list); $i++) {
            $pre = '';
            $val = ($row['it_type'] === 'HP회복(K)') ? "(HP +{$row['it_value']})" : "(MP +{$row['it_value']})";
            if (!empty($row['se_ch_id'])) {
                $pre = '[선물]';
            }
            $target_list[$i] = array(
                'id'   => (int)$row['in_id'],
                'name' => "{$pre}{$row['it_name']} {$val}",
            );
        }

        if (count($target_list) <= 0) {
            $data['disable'] = 'disable';
            $data['warning'] = '사용 가능한 아이템이 없습니다.';
        }

    } elseif ($type === 'skill') {
        if($rm['unit_type']=='ch'){
            $sk = get_k_battle_skill($bs_id, '*', true);
        }else{
            $sk = get_k_mo_skill($bs_id, $rm['unit_id']);
            $bs_id=$sk['sk_id'];
            $target_load=false;
        }
        if (!is_array($sk)) $sk = array();

        // 1차 방어: 잘못된 bs_id 또는 스킬 미존재
        if (!$bs_id || empty($sk)) {
            $data['warning'] = '스킬 정보를 찾을 수 없습니다.';
           ;
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }

        global $kb_cf;
        $kb_mp_name = ses($kb_cf, 'mp_name', 'MP');

        if (!empty($sk['sk_cool_now'])) {
            $data['disable'] = 'disable';
            $act_warning     = "스킬 쿨타임 중입니다. ({$sk['sk_cool_now']}턴 후 가능)";
        } elseif ((int)$sk['sk_mp'] > (int)$rm['mp_now']) {
            $data['disable'] = 'disable';
            $act_warning     = "{$kb_mp_name} 자원이 부족합니다. ({$sk['sk_mp']} 필요)";
        } else {
            $act_warning = "{$kb_mp_name} {$sk['sk_mp']} / 쿨타임 {$sk['sk_cool']} / {$sk['sk_turn']}턴 지속";
        }

        if ($data['disable'] === '') {
            $sk_target     = ses($sk, 'sk_target', '');
            $sk_target_cnt = ses($sk, 'sk_target_cnt', '');

            if ($sk_target === 'self') {
                $data['target']  = '<input type="hidden" id="target_type" name="target_type" value="' . $rm['unit_type'] . '">'
                                . '<input type="hidden" id="action_target" name="action_target" value="' . $rm_id . '">'
                                . '<input type="hidden" name="bs_id" id="bs_id" value="' . $bs_id . '"><span>본인</span>';
            } else {
                $target = array();
                if ($rm['unit_type'] === 'mo') {
                    $target['enemy'] = 'ch';
                    $target['ally']  = 'mo';
                } else {
                    $target['enemy'] = 'mo';
                    $target['ally']  = 'ch';
                }
                $target_type = ses($target, $sk_target, '');

                if ($sk_target_cnt === 'single'&&$rm['unit_type']=='ch') {
                    $target_list = true;
                } else {
                    $data['target']  = '<input type="hidden" id="target_type" name="target_type" value="' . $target_type . '">'
                                    . '<input type="hidden" name="bs_id" id="bs_id" value="' . $bs_id . '">';
                    if($rm['unit_type']=='ch'){
                        $data['target'] .='<input type="hidden" id="action_target" name="action_target" value="all"><span>대상 전체</span>';
                    }else{
                        if($sk_target_cnt=='all'){
                            $data['target'] .='<input type="hidden" id="action_target" name="action_target" value="all"><span>대상 전체</span>';
                        }else{
                            $sk['cs_target_cnt']=$sk['cs_target_cnt']>0?$sk['cs_target_cnt']:1;
                            $data['target'].='<input type="hidden" id="action_target" name="action_target" value="rand"><input type="hidden" id="action_target_cnt" name="action_target_cnt" value="'.$sk['cs_target_cnt'].'"><span>무작위 '.$sk['cs_target_cnt'].'명</span>';
                        }
                    }
                }
            }
        }

        $data['warning'] = isset($act_warning) ? $act_warning : '';
        $data['info']    = '[' . ses($sk, 'sk_name', '') . '] ' . ses($sk, 'sk_info', '');
    }
}

/* ---------- 대상 목록 생성 ---------- */
if ($data['disable'] === '' && $target_list) {

    $data['target'] .= '<input type="hidden" name="target_type" id="target_type" value="' . $target_type . '">';

    // 아이템이 아니면 유닛 목록 조회
    if ($type !== 'item') {
        if ($bs_id) {
            $data['target'] .= '<input type="hidden" name="bs_id" id="bs_id" value="' . $bs_id . '">';
        }
   
        $select = "origin.{$target_type}_name AS name, unit.rm_id AS id, unit.hp_now, unit.hp_max";
        $order  = " ORDER BY origin.{$target_type}_name ASC";

        // 부활/치유/공격 조건
        $sk_si_code = isset($sk) ? ses($sk, 'si_code', '') : '';
        if ($sk_si_code === 'rev') {
            $where = ' AND unit.hp_now <= 0';
        } elseif ($sk_si_code === 'heal' || $type === 'heal') {
            $where = ' AND unit.hp_now < unit.hp_max AND unit.hp_now > 0';
        } else {
            $where = ' AND unit.hp_now > 0';
            $aggr  = sql_fetch("SELECT rm_id FROM {$battle_table}_unit WHERE is_aggr > 0 AND hp_now > 0 AND unit_type = '{$target_type}' AND ra_id = '".sql_escape_string($ra_id)."' LIMIT 1");
            if (!empty($aggr['rm_id'])) {
                $where .= ' AND unit.is_aggr > 0';
            }
        }

        $target_list = get_k_unit_list($target_type, $ra_id, $select, $where, $order);
        
        if (!is_array($target_list)) $target_list = array();
    }

    // 셀렉트 옵션 구성
    $target_option = '';
    if (is_array($target_list)) {
        foreach ($target_list as $row) {
            $id  = ses($row, 'id', 0, 'int');
            $nm  = ses($row, 'name', '');
            if ($type !== 'item') {
                $hp_now = ses($row, 'hp_now', 0, 'int');
                $hp_max = ses($row, 'hp_max', 1, 'int');
                $hp  = '(' . $hp_now . '/' . max(1, $hp_max) . ')';
                $nm .= $hp;
            }
            $target_option .= '<option value="' . $id . '">' . $nm . '</option>';
        }
    }

    if ($target_option !== '') {
        $data['target'] .= '<select name="action_target" id="action_target">' . $target_option . '</select>';
    } else {
        $data['target'] ='';
        $data['disable'] = 'disable';
        $data['warning'] = '사용 가능한 대상이 없습니다.';
    }
}

/* ---------- 액션 버튼/폼 히든 ---------- */
if (!empty($data['target'])) {
    if ($act === 'form') {
        $data['target'] .= '<input type="hidden" name="action_type" value="' . $type . '">';
    } else {
        $data['target'] .= '<span class="ui-btn point" id="raid_act_btn" onclick="action(\'' . $type . '\', \'' . $rm['unit_type'] . '\')">행동</span>'
                         .  '<input type="hidden" name="action_type" value="' . $type . '">';
    }
}

/* ---------- 출력 ---------- */
echo json_encode($data, JSON_UNESCAPED_UNICODE);
