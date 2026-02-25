<?php
header('Content-Type: application/json');

require_once './_common.php';

/* 기본값/입력 방어 */
$data = array('result'=>'', 'warning'=>'', 'info'=>'', 'disable'=>'', 'target'=>'', 'list'=>'', 'text'=>'');
$load   = ses($_REQUEST, 'load', '', 'raw');
$slot   = ses($_REQUEST, 'slot', '', 'raw');
$id     = ses($_REQUEST, 'id', 0, 'int');
$ch_id  = ses($_REQUEST, 'ch_id', 0, 'int');
$eq_id  = ses($_REQUEST, 'eq_id', 0, 'int');
$tg_id  = ses($_REQUEST, 'tg_id', 0, 'int');
$type   = ses($_REQUEST, 'type', '', 'raw');
$act    = ses($_REQUEST, 'act', '', 'raw');
$btn    = ses($_REQUEST, 'btn', 0, 'int');

$kb_cf['equip_per_hide']  = ses($kb_cf, 'equip_per_hide', 0, 'int');
$kb_cf['upgrade_limit']   = ses($kb_cf, 'upgrade_limit', 0, 'int');
$character['ch_rank']     = ses($character, 'ch_rank', 0, 'int');

if (!isset($g5['k_ch_equip_table'], $g5['inventory_table'], $g5['item_table'], $g5['k_upgrade_table'])) {
    echo json_encode(array('result'=>'F','warning'=>'테이블 정보가 없습니다.'), JSON_UNESCAPED_UNICODE);
    exit;
}

/* 분기 처리 */
switch ($load) {
    /* ---------------- equip: 장착/해제 ---------------- */
    case 'equip': {
        if ($id <= 0 || $slot === '') {
            $data['result'] = 'F';
            break;
        }

        $data['result'] = 'S';
        $data['in'] = '';
        $eq = get_k_equip_item($id, '*', true);
        if (!is_array($eq)) $eq = array();

        $ch_id_check = ses($character, 'ch_id', 0, 'int');
        $eq_type_check = ses($eq, 'eq_type', '');
        if (empty($eq['in_id']) || (int)$eq['ch_id'] !== $ch_id_check || ($slot !== '공용' && $eq_type_check !== $slot)) {
            $data['result'] = 'F';
            break;
        }

        if (empty($eq['eq_use'])) {
            /* 슬롯 한도 계산 */
            $max_list = array();
            $max  = !empty($kb_cf['equip_max'])  ? explode('|', (string)$kb_cf['equip_max'])  : array();
            $typeL = !empty($kb_cf['equip_type']) ? explode('|', (string)$kb_cf['equip_type']) : array();
            array_unshift($typeL, '공용');
            $len = max(count($max), count($typeL));
            for ($i = 0; $i < $len; $i++) {
                $k = ses($typeL, $i, 'TYPE_'.$i);
                $v = ses($max, $i, 0, 'int');
                $max_list[$k] = $v;
            }

            $now = sql_fetch("
                SELECT COUNT(*) AS cnt
                FROM {$g5['k_ch_equip_table']} eq
                JOIN {$g5['inventory_table']} inven ON inven.in_id = eq.in_id
                WHERE inven.ch_id = '".(int)$character['ch_id']."'
                  AND eq.eq_use = '".sql_escape_string($slot)."'
            ");
            $now_cnt = ses($now, 'cnt', 0, 'int');

            $max_slot = ses($max_list, $slot, 0, 'int');
            if ($max_slot <= $now_cnt) {
                $data['result'] = 'F';
                break;
            }

            $eq_use = $slot;
            $eq_lv = '';
            if (!empty($eq['ug_name'])) {
                $eq_lv = "<span class=\"eq_lv lv_{$eq['ug_id']}\">{$eq['ug_name']}</span>";
            }
            $data['in'] = "<li class=\"equip-img selected {$slot}\" data-id=\"{$eq['in_id']}\" onclick=\"assetInfo({$eq['in_id']},'equip','{$slot}')\"><img src=\"{$eq['eq_img']}\">{$eq_lv}</li>";
        } else {
            $eq_use_check = ses($eq, 'eq_use', '');
            if ($eq_use_check !== $slot) {
                $data['result'] = 'F';
                break;
            }
            $eq_use = '';
        }

        if ($data['result'] === 'S') {
            sql_query("
                UPDATE {$g5['k_ch_equip_table']}
                SET eq_use = '".sql_escape_string($eq_use)."'
                WHERE in_id = '".(int)$id."'
            ");
        }
        break;
    }

    /* ---------------- proflist: 슬롯별 보유 목록 ---------------- */
    case 'proflist': {
        if ($ch_id <= 0) {
            $data['msg'] = '대상 캐릭터가 없습니다.';
            break;
        }

        if ($slot !== '공용') {
            $sql_select = " AND it.eq_type = '".sql_escape_string($slot)."' AND eq.eq_use!='공용'";
        } else {
            $sql_select = " AND (eq.eq_use = '' OR eq.eq_use='공용')";
        }

        $sql = "
            SELECT eq.in_id, eq.eq_use, eq.eq_img AS it_img, ug.ug_name, eq.ug_id
            FROM {$g5['k_ch_equip_table']} eq
            LEFT JOIN {$g5['k_upgrade_table']} ug ON eq.ug_id = ug.ug_id
            INNER JOIN {$g5['inventory_table']} inven ON eq.in_id = inven.in_id
            INNER JOIN {$g5['item_table']} it ON eq.it_id = it.it_id
            WHERE inven.ch_id = '".(int)$ch_id."'
            {$sql_select}
            ORDER BY eq.eq_use DESC, eq.ug_id DESC
        ";
        $it_sql = sql_query($sql);

        $html = '';
        while ($row = sql_fetch_array($it_sql)) {
            $selected = !empty($row['eq_use']) ? 'selected' : '';
            $eq_lv = '';
            if (!empty($row['ug_name'])) {
                $eq_lv = "<span class=\"eq_lv lv_{$row['ug_id']}\">{$row['ug_name']}</span>";
            }
            $html .= "<li class=\"equip-img {$selected}\" data-id=\"{$row['in_id']}\" onclick=\"assetInfo({$row['in_id']},'equip')\"><img src=\"{$row['it_img']}\">{$eq_lv}</li>";
        }
        $data['list'] = $html;
        break;
    }

    /* ---------------- list: 선택 목록(장비/재료) ---------------- */
    case 'list': {
        $suc = true;
        $option = '';
        $money = '';

        if (empty($kb_cf['upgrade_limit'])) {
            $max = sql_fetch("SELECT ug_id FROM {$g5['k_upgrade_table']} ORDER BY ug_id DESC LIMIT 1");
            $kb_cf['upgrade_limit'] = ses($max, 'ug_id', 0, 'int');
        }
        if (empty($character['ch_rank'])) $character['ch_rank'] = 0;

        if ($type === 'equip') {
            $slot = 'slot_1';
            if ($act === 'upgrade') {
                $data['text']   = '강화 대상 장비를 선택해 주세요.';
                $typename       = '강화 가능한 장비가';
                $it_search      = " AND eq.ug_id < {$kb_cf['upgrade_limit']}
                                    AND it.ug_limit != 9999
                                    AND (it.ug_limit = 0 OR eq.ug_id < it.ug_limit)
                                    ORDER BY eq.ug_id DESC";
            } else { // custom
                $data['text']   = '커스텀 대상 장비를 선택해 주세요.';
                $typename       = '보유중인 장비가';
                $it_search      = '';
            }

            $ch_id_for_sql = ses($character, 'ch_id', 0, 'int');
            $sql = "
                SELECT inven.in_id AS it_id, eq.eq_img AS it_img, eq.eq_name AS it_name, ug.ug_name, eq.ug_id
                FROM {$g5['k_ch_equip_table']} eq
                LEFT JOIN {$g5['k_upgrade_table']} ug ON eq.ug_id = ug.ug_id
                INNER JOIN {$g5['inventory_table']} inven ON eq.in_id = inven.in_id
                INNER JOIN {$g5['item_table']} it ON eq.it_id = it.it_id
                WHERE inven.ch_id = '".$ch_id_for_sql."'
                {$it_search}
            ";
        } else { // slot_2 재료
            $slot = 'slot_2';
            if ($act === 'upgrade') {
                $typename = '강화 재료 아이템이';
                $data['text'] = '강화 재료를 선택해 주세요.';

                $eq = get_k_equip_item($eq_id, 'eq.*, it.*', true);
                if (!is_array($eq)) $eq = array();
                $eq_ug_limit = ses($eq, 'ug_limit', '');
                if ($eq_ug_limit === '9999') {
                    $suc = false;
                    $data['msg'] = '강화 불가 장비입니다.';
                } else {
                    $eq['ug_id'] = ses($eq, 'ug_id', 0, 'int');
                    if (!empty($eq['ug_limit']) && (int)$eq['ug_limit'] < (int)$kb_cf['upgrade_limit']) {
                        $kb_cf['upgrade_limit'] = (int)$eq['ug_limit'];
                    }
                    if ((int)$kb_cf['upgrade_limit'] >= (int)$eq['ug_id']) {
                        $ug = sql_fetch("
                            SELECT *
                            FROM {$g5['k_upgrade_table']}
                            WHERE ug_id > {$eq['ug_id']}
                              AND (ch_rank='0' OR ch_rank <= {$character['ch_rank']})
                            ORDER BY ug_id ASC
                            LIMIT 1
                        ");
                    }

                    if (empty($ug['ug_id'])) {
                        $suc = false;
                        $data['msg'] = '더 이상 강화할 수 없습니다.';
                    } else {
                        $mb_point = ses($member, 'mb_point', 0, 'int');
                        if (!empty($ug['ug_use_money']) && !empty($ug['ug_money'])) {
                            if ((int)$ug['ug_money'] > $mb_point) {
                                $suc = false;
                                $data['text'] = '소지금이 부족합니다.';
                            } else {
                                $cf_money_pice = ses($config, 'cf_money_pice', '');
                                $money = (int)$ug['ug_money'] . $cf_money_pice;
                            }
                        }

                        if (empty($kb_cf['equip_per_hide'])) {
                            $option = $money ? "({$money}/{$ug['ug_per']}%)" : "({$ug['ug_per']}%)";
                        } else {
                            $option = $money ? "({$money})" : '';
                        }

                        if ($suc) {
                            if (!empty($ug['ug_use_it']) && !empty($ug['ug_item'])) {
                                $it_search = " AND it.it_id = '".(int)$ug['ug_item']."'";
                            } else {
                                $data['msg'] = '이 강화에는 아이템이 필요하지 않습니다.';
                                $data['text'] = '<span class="ui-btn" onclick="equipAction();">강화하기'.$option.'</span>';
                                $data['target'] = 'no';
                                $it_search = '';
                            }
                        }
                    }
                }
            } elseif ($act === 'custom') {
                $data['text'] = '커스텀할 아이템를 선택해 주세요.';
                $typename = '커스텀 가능한 아이템이';
                $it_search = " AND it.it_type = '커스텀장비(K)'";
            } else {
                $typename = '선택 가능한 아이템이';
                $it_search = '';
            }

            $ch_id_for_sql2 = ses($character, 'ch_id', 0, 'int');
            $sql = "
                SELECT inven.in_id AS it_id, it.it_img, it.it_name
                FROM {$g5['inventory_table']} inven
                INNER JOIN {$g5['item_table']} it ON it.it_id = inven.it_id
                WHERE inven.ch_id = '".$ch_id_for_sql2."'
                  AND inven.in_id <> '".(int)$eq_id."'
                {$it_search}
            ";
        }

        if ($suc) {
            if (!empty($option)) $data['option'] = $option;

            if (!$btn) {
                $it_sql = sql_query($sql);
                $html = '';
                while ($row = sql_fetch_array($it_sql)) {
                    $eq_lv = '';
                    if (!empty($row['ug_name'])) {
                        $eq_lv = "<span class=\"eq_lv lv_{$row['ug_id']}\">{$row['ug_name']}</span>";
                    }
                    $html .= "<li class=\"equip-img\" onclick=\"itemClick(this,'{$slot}','{$row['it_id']}','{$row['it_name']}')\"><img src=\"{$row['it_img']}\">{$eq_lv}</li>";
                }
                $data['list'] = $html;

                if ($data['list'] === '' && empty($data['msg'])) {
                    $data['msg'] = "{$typename} 없습니다.";
                }
            }
        }
        break;
    }

    /* ---------------- action: 강화/커스텀 실행 ---------------- */
    case 'action': {
        if ($act === 'upgrade') {
            $data = k_equip_upgrade($eq_id, $tg_id);
            if (!is_array($data)) $data = array('result'=>'alert','msg'=>'처리 오류가 발생했습니다.');
        } elseif ($act === 'custom') {
            $eq = get_k_equip_item($eq_id, 'eq.*, it.*, ug.ug_name', true);
            if (!is_array($eq)) $eq = array();
            if (empty($eq['eq_id'])) {
                $data['result'] = 'alert';
                $data['msg'] = '장비 정보를 찾을 수 없습니다.';
                break;
            }

            $in = sql_fetch("
                SELECT *
                FROM {$g5['inventory_table']} inven
                JOIN {$g5['item_table']} it ON inven.it_id = it.it_id
                WHERE inven.in_id = '".(int)$tg_id."'
            ");
            $ch_id_check2 = ses($character, 'ch_id', 0, 'int');
            if (empty($in['in_id']) || (int)$in['ch_id'] !== $ch_id_check2) {
                $data['result'] = 'alert';
                $data['msg'] = '커스텀 아이템 정보를 찾을 수 없습니다.';
                break;
            }

            $data['result'] = '성공';
            delete_inventory($tg_id);
            
            $in_it_img = ses($in, 'it_img', '');
            $in_it_name = ses($in, 'it_name', '');
            $in_it_content = ses($in, 'it_content', '');
            $in_in_memo = ses($in, 'in_memo', '');
            $in_se_ch_id = ses($in, 'se_ch_id', 0, 'int');
            
            sql_query("
                UPDATE {$g5['k_ch_equip_table']}
                SET
                    eq_img     = '".sql_escape_string($in_it_img)."',
                    eq_name    = '".sql_escape_string($in_it_name)."',
                    eq_content = '".sql_escape_string($in_it_content)."',
                    eq_memo    = '".sql_escape_string($in_in_memo)."',
                    eq_memo_id = '".$in_se_ch_id."'
                WHERE eq_id = '".(int)$eq['eq_id']."'
            ");
            $data['msg']  = '장비 커스텀이 완료되었습니다.';
            $eq_lv = '';
            if (!empty($eq['ug_name'])) {
                $eq_lv = "<span class=\"eq_lv lv_{$eq['ug_id']}\">{$eq['ug_name']}</span>";
            }
            $data['html'] = "<img src=\"{$in_it_img}\">{$eq_lv}";
        } else {
            $data['result'] = 'alert';
            $data['msg'] = '알 수 없는 작업입니다.';
        }
        break;
    }

    default:
        $data['result'] = 'F';
        $data['warning'] = '요청이 유효하지 않습니다.';
        break;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
