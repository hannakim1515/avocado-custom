<?php
if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}

$hIdent = function ($v) { return preg_match('/^\w+$/', (string)$v); };

$admin_action  = ses($_REQUEST, 'admin_action', '');
$raid_msg      = ses($_REQUEST, 'raid_msg', '', 'raw');
$turn_type     = ses($board, 'bo_10_subj', '');
$mo_rm_id      = ses($_REQUEST, 'mo_rm_id', 0, 'int');
$admin_turn    = !empty($_REQUEST['admin_turn']);
$mo_atk_bonus  = ses($_REQUEST, 'mo_atk_bonus', 0, 'int');
$rand_cnt      = ses($_REQUEST, 'rand_cnt', 0, 'int');
$return_url    = isset($return_url) ? (string)$return_url : '';
$action_type   = ses($_REQUEST, 'action_type', '');
$action_target = ses($_REQUEST, 'action_target', 0, 'int');
$target_type   = ses($_REQUEST, 'target_type', '');
$rm_id         = ses($_REQUEST, 'rm_id', 0, 'int');
$bs_id         = ses($_REQUEST, 'bs_id', 0, 'int');
$option        = '';

// action.inc.php에서 사용하는 $data 배열 미리 초기화
$data = array('warning' => '', 'info' => '', 'disable' => '', 'target' => '');

/* 게시판 확장필드 컬럼 보강 */
if ($write_table !== '') {
    $temp_check = sql_fetch("SELECT * FROM {$write_table}");
    if (!isset($temp_check['wr_k_raid_log'])) {
        sql_query("
            ALTER TABLE `{$write_table}`
            ADD `wr_k_raid_log`   TEXT NOT NULL,
            ADD `wr_k_raid_admin` TEXT NOT NULL
        ", true);
    }
    unset($temp_check);
}

/* mmbraid 모드 */
if (ses($board, 'bo_1_subj', '') === 'mmbraid' && ses($board, 'bo_1', '') === 'true') {
    $option = ", lo_2 = '{$wr_id}'";

    if ($admin_action) {
        if ($is_admin === 'super' && !empty($board['bo_2']) && $battle_table) {
            if ($raid_msg !== '') {
                insert_k_log($raid_msg, $ra_id, 'mmbsystem', $option);
            }

            if ($admin_action !== 'msg') {
                // 몬스터 유닛
                $select = "origin.mo_name as unit_name, unit.*";
                $unit   = get_k_unit($mo_rm_id, 'mo', $select);

                $selectCh = "origin.ch_name as unit_name, unit.*";
                $act_effect = '';
                $target = array();

                if ($admin_action === 'allatk') {
                    $target = get_k_unit_list('ch', $ra_id, $selectCh, 'AND unit.hp_now>0', '', true);

                } elseif ($admin_action === 'randatk' || $admin_action === 'randstun') {
                    if ($rand_cnt <= 0) $rand_cnt = 1;

                    // 도발 우선 랜덤 타겟 선택
                    $rand_result = get_k_rand_target($ra_id, $rand_cnt, 'ch', true, $selectCh);
                    $rand_csv = $rand_result['csv'];
                    $rand_name = $rand_result['name'];

                    if ($admin_action === 'randatk') {
                        $target = $rand_result['list'];
                    } else { // randstun
                        if (!empty($rand_csv)) {
                            sql_query("UPDATE {$battle_table}_unit SET is_stun = is_stun + 2 WHERE rm_id IN ({$rand_csv})");
                            foreach ($rand_name as $r) {
                                $act_effect .= "<p><span class=\"name\">{$r}</span>의 움직임을 <span class=\"turn\">1</span> 막습니다.</p>";
                            }
                        }
                    }

                } elseif ($admin_action === 'heal') {
                    $heal = get_k_battle_func('heal', $unit, $unit, true);
                    set_k_dmg($unit, 'hp', (int)$heal['value']);
                    $act_effect .= "<p><span class=\"name\">{$unit['unit_name']}</span> 의 ".ses($kb_cf, 'hp_name', 'HP')." <span class=\"dmg heal\">{$heal['value']}</span></p>";
                }

                if ($admin_action === 'allatk' || $admin_action === 'randatk') {
                    if ($mo_atk_bonus <= 0) $mo_atk_bonus = 100;
                    foreach ($target as $t) {
                        $miss = get_k_battle_func('miss', $t, $unit, true);
                        $rand = rand(1, 100);
                        if ($rand <= (int)$miss['value']) {
                            $cri = !empty($miss['cri']) ? "<span class=\"cri\">크리티컬!</span>" : '';
                            $act_effect .= "<p>{$cri}<span class=\"name\">{$t['unit_name']}</span> 에게는 명중하지 않았다!</p>";
                        } else {
                            $dmg = get_k_battle_func('atk', $unit, $t, true);
                            $dmg['value'] = (float)$dmg['value'] * ($mo_atk_bonus / 100) * -1;
                            $cri = (!empty($dmg['cri']) || !empty($miss['cri'])) ? "<span class=\"cri\">크리티컬!</span>" : '';
                            if ($dmg['value'] >= 0) {
                                $dead_msg = '';
                                $val = 0;
                            } else {
                                $dead_msg = set_k_dmg($t, 'hp', (int)$dmg['value']);
                                $val = (int)round($dmg['value'] * -1);
                            }
                            $act_effect .= "<p>{$cri}<span class=\"name\">{$t['unit_name']}</span>의 ".ses($kb_cf, 'hp_name', 'HP')." <span class=\"dmg atk\">{$val}</span>{$dead_msg}</p>";
                        }
                    }
                }

                if ($act_effect !== '') {
                    insert_k_log($act_effect, $ra_id, 'mo', $option);
                }
            }

            if ($admin_turn) {
                k_turn_change($ra_id, $option);
            }

            $customer_sql = ", wr_k_raid_admin='1'";
        } else {
            $data['warning'] = '정상적으로 접근해 주세요';
        }

    } elseif ($rm_id && $action_target && $target_type && $action_type) {
        if ($action_type === 'skill' && $bs_id <= 0) {
            $data['warning'] = '스킬 정보에 문제가 있습니다.';
        } else {
            $unit_type   = 'ch';
            $type        = $action_type;
            $target_id   = $action_target;

            include G5_PATH . '/k_battle/ajax/action.inc.php';

            if (empty($data['warning'])) {
                $select = "origin.mo_name as unit_name, unit.*";
                $mo     = get_k_unit($mo_rm_id, 'mo', $select);

                if (ses($board, 'bo_7', '') === 'true'
                    && $target_id === $mo_rm_id
                    && ses($mo, 'hp_now', 0, 'int') > 0
                    && ses($mo, 'is_stun', 0, 'int') <= 0) {

                    $msg = "<p class=\"log-title\">{$mo['unit_name']}의 반격</p>";

                    if (!empty($board['bo_7_subj'])) {
                        $text = nl2br((string)$board['bo_7_subj']);
                        $text = get_k_rand_text($text);
                        if (!empty($text)) $msg .= "<p class=\"mmbsystem\">{$text}</p>";
                    }

                    use_k_action('atk', $mo, $rm_id, 'ch', $ra_id, $msg, $option);
                }

                // 전투 수치 로그 기록값 준비
                $rmStat = sql_fetch("SELECT hp_now, hp_max, mp_now, mp_max FROM {$battle_table}_unit WHERE rm_id = '{$rm_id}'");
                $raid_log = "{$rmStat['hp_now']}|{$rmStat['hp_max']}|{$rmStat['mp_now']}|{$rmStat['mp_max']}|{$mo['hp_now']}|{$mo['hp_max']}";
                $customer_sql = ", wr_k_raid_log='".sql_escape_string($raid_log)."'";
            }
        }
    }

    if (!empty($data['warning'])) {
        alert($data['warning'], $return_url);
    }
}
