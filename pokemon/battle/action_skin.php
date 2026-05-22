<?php

$isend    = false;
$mybattle = false;

// 안전 캐스팅
$lo_id = (int)($po_data_log[2]      ?? 0);
$wr_id = (int)($log_comment['wr_id'] ?? 0);
$battle_msg = isset($po_data_log[4]) && $po_data_log[4] ? $po_data_log[4] : '……… ……… ………';

// 현재 배틀 이벤트 로그
$lo = sql_fetch("SELECT * FROM {$g5['pokemon_event_log_table']} WHERE lo_id='{$lo_id}'");

// 내 배틀 참여 여부
if ($lo && ((int)$lo['lo_3'] === (int)$character['ch_id'] || (int)$lo['lo_5'] === (int)$character['ch_id'])) {
    $mybattle = true;
}
$end_log= sql_fetch("SELECT lo_id FROM {$g5['pokemon_battle_log_table']} lo WHERE wr_id='{$wr_id}' AND bo_table='".addslashes($bo_table)."' AND lo_order=4 limit 1");
if($end_log['lo_id']){$isend=true;}
// 상대 캐릭터 / 타이틀
$ba_ch    = get_character($lo['lo_5']);
$battle_wrap_id = 'battle-view-' . (int)$wr_id;

$ch_title = get_title($ch['ch_title'])['ti_title'] ?? '';
if ($ch_title) { $ch['ch_name'] = $ch_title.' '.$ch['ch_name']; }
?>
<div class="battle_bar"></div>
<div class="battle_page">"<?= htmlspecialchars($battle_msg) ?>"</div>
<div class="battle_start">
    <img src="<?= htmlspecialchars($ch['ch_thumb'] ?? '', ENT_QUOTES) ?>" alt="">
    <img src="<?= htmlspecialchars($ba_ch['ch_thumb'] ?? '', ENT_QUOTES) ?>" alt="" style="right:initial;left:0;">
    <?= htmlspecialchars($ch['ch_name'] ?? '', ENT_QUOTES) ?>(이)가 승부를 걸어왔다!<br>
    <?= htmlspecialchars($ba_ch['ch_name'] ?? '', ENT_QUOTES) ?>(은)는 어떻게 하지?
</div>
<?if(!$isend&&$mybattle){?>
    <div class="battle_refresh" style="text-align:center;margin:12px 0 20px;">
    <button type="button" class="fight-btn js-battle-reload"
            data-battle-id="<?= (int)$wr_id ?>" style="border:none;">
        새로고침
    </button>
    </div>
<?}?>
<div class="battle-wrap" data-battle-id="<?= (int)$wr_id ?>">
    <?php // 참가 체크
    if (empty($lo['lo_6']) && $mybattle): ?>
        <form action="<?= G5_URL ?>/pokemon/battle/search_update.php" method="post" style="margin:0 auto; text-align:center;">
            <?php if ((int)$ba_ch['ch_id'] === (int)$character['ch_id'] || $is_admin):
                // 내보낼 포켓몬 목록
                $ph_list = '';
                $battle_ph_sql = sql_query("
                    SELECT ph_id, po_name
                    FROM {$g5['pokemon_battle_table']}
                    WHERE ch_id='".(int)$ba_ch['ch_id']."' AND is_battle=0
                ");
                while ($row2 = sql_fetch_array($battle_ph_sql)) {
                    $ph_list .= '<option value="'.(int)$row2['ph_id'].'">'.htmlspecialchars($row2['po_name'], ENT_QUOTES).'</option>';
                }
                ?>
                <input type="hidden" name="wr_id" value="<?= $wr_id ?>">
                <input type="hidden" name="hp_reset" value="1">
                <input type="hidden" name="bo_table" value="<?= htmlspecialchars($bo_table, ENT_QUOTES) ?>">
                <input type="hidden" name="lo_id" value="<?= (int)$lo['lo_id'] ?>">
                <input type="hidden" name="ch_id" value="<?= (int)$ba_ch['ch_id'] ?>">

                <div class="fight-btn">
                    <select name="battle_ph_id" style="width:100%; font-family:galmuri9">
                        <option>내보낼 포켓몬 선택</option><?= $ph_list ?>
                    </select><br>
                    <button type="submit" class="fight-btn" style="border:none;">수락한다</button>
                </div>
            <?php else: ?>
                <div class="fight-btn" style="margin:0 auto;">상대의 응답을 기다리고 있습니다...</div>
            <?php endif; ?>
        </form>

    <?php
    else:
        // 턴 로그 로드 및 그룹화
        $res = sql_query("
            SELECT lo.*,
                atk.hp_max AS atk_hp_max, def.hp_max AS def_hp_max,
                atk.ch_id  AS atk_ch_id,  atk.po_name AS atk_name, atk.po_dot AS atk_dot,
                def.po_name AS def_name,   def.po_dot  AS def_dot,  def.ch_id  AS def_ch_id
            FROM {$g5['pokemon_battle_log_table']} lo
            LEFT JOIN {$g5['pokemon_battle_table']} atk ON lo.ph_id    = atk.ph_id
            LEFT JOIN {$g5['pokemon_battle_table']} def ON lo.re_ph_id = def.ph_id
            WHERE lo.wr_id='{$wr_id}' AND lo.bo_table='".addslashes($bo_table)."'
            ORDER BY lo.lo_turn ASC, lo.lo_order ASC, lo.lo_id ASC
        ");

        $pages = [];       // [turn] => [logs...]
        $end_logs = [];    // lo_order >= 4
        while ($row = sql_fetch_array($res)) {
            if ((int)$row['lo_order'] < 4) {
                $t = (int)$row['lo_turn'];
                if (!isset($pages[$t])) $pages[$t] = [];
                $pages[$t][] = $row;
            } else {
                $end_logs[] = $row;
            }
        }
        ksort($pages);
        $isend = !empty($end_logs);

        $pager_id = 'turnPagerWrap-' . $wr_id;
        $page_idx = 0;
        ?>

        <div id="<?= $pager_id ?>" class="turn-pager-wrap">
            <div class="turn-pager">
                <?php foreach ($pages as $turn => $logs): ?>
                    <section class="turn-page" data-page="<?= $page_idx ?>" id="<?= $pager_id ?>-turn-<?= $page_idx ?>">
                        <?php foreach ($logs as $log):
                            // HP/퍼센트 계산
                            $atk_hp_max = max(1, (int)($log['atk_hp_max'] ?? 1));
                            $def_hp_max = max(1, (int)($log['def_hp_max'] ?? 1));
                            $atk_hp     = max(0, (int)($log['ph_hp_now'] ?? 0));
                            $def_hp     = max(0, (int)($log['re_hp_now'] ?? 0));

                            $atk_percent = max(0, min(100, (int)round($atk_hp * 100 / $atk_hp_max)));
                            $def_percent = max(0, min(100, (int)round($def_hp * 100 / $def_hp_max)));

                            $atk_bar_class = ($atk_percent >= 50) ? 'hpbar-green' : (($atk_percent >= 10) ? 'hpbar-yellow' : 'hpbar-red');
                            $def_bar_class = ($def_percent >= 50) ? 'hpbar-green' : (($def_percent >= 10) ? 'hpbar-yellow' : 'hpbar-red');

                            $atk_is_me = (int)$log['atk_ch_id'] === (int)$log_comment['ch_id'];
                            $def_is_me = (int)$log['def_ch_id'] === (int)$log_comment['ch_id'];
                        ?>
                        <div class="turn-log-block">
                            <div class="turn-chars">
                                <div class="char <?= $atk_is_me ? 'attacker' : 'defender' ?>">
                                    <img src="<?= htmlspecialchars($log['atk_dot'] ?? '', ENT_QUOTES) ?>" alt="<?= htmlspecialchars($log['atk_name'] ?? '', ENT_QUOTES) ?>">
                                    <div class="name"><?= htmlspecialchars($log['atk_name'] ?? '', ENT_QUOTES) ?></div>
                                    <div class="hpbar-wrap">
                                        <div class="hpbar <?= $atk_bar_class ?>" style="width:<?= $atk_percent ?>%;"></div>
                                        <div class="hpbar-text"><?= $atk_hp ?> / <?= $atk_hp_max ?></div>
                                    </div>
                                </div>
                                <div class="char <?= $def_is_me ? 'attacker' : 'defender' ?>">
                                    <img src="<?= htmlspecialchars($log['def_dot'] ?? '', ENT_QUOTES) ?>" alt="<?= htmlspecialchars($log['def_name'] ?? '', ENT_QUOTES) ?>">
                                    <div class="name"><?= htmlspecialchars($log['def_name'] ?? '', ENT_QUOTES) ?></div>
                                    <div class="hpbar-wrap">
                                        <div class="hpbar <?= $def_bar_class ?>" style="width:<?= $def_percent ?>%;"></div>
                                        <div class="hpbar-text"><?= $def_hp ?> / <?= $def_hp_max ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="turn-msgs border_box border_2">
                                <?= $log['lo_msg'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </section>
                    <?php $page_idx++; ?>
                <?php endforeach; ?>
            </div>

            <div class="turn-nav">
                <button type="button" class="btn-first"><<</button>
                <button type="button" class="btn-prev"><</button>
                <span class="page-indicator"><b class="curr">1</b> / <span class="total">1</span></span>
                <button type="button" class="btn-next">></button>
                <button type="button" class="btn-last">>></button>
            </div>
        
        </div>

        <?php
        // ===== 엔드 블록 (배틀 종료·점수·레코드 등록 등) =====
        if (!empty($end_logs)):
            // 999 턴(최종점수) 우선 표시
            $end999 = null;
            foreach ($end_logs as $el) { if ((int)$el['lo_turn'] === 999) { $end999 = $el; break; } }
            $final = $end999 ?: end($end_logs);
            ?>
            <div class="battle_end border_box border_2">
                <?php if (!empty($final) && (int)$final['lo_turn'] === 999):
                    $score       = explode('!!', (string)$final['lo_debug']);
                    $score_win   = array_pad(explode('|', $score[0] ?? ''), 6, 0);
                    $score_lose  = array_pad(explode('|', $score[1] ?? ''), 6, 0);
                    $score_win[1]  = max(-1, min(2, (int)$score_win[1]));
                    $score_lose[1] = max(-1, min(2, (int)$score_lose[1])); ?>
                    <div class="points">
                        <p class="name">
                            <span><?= htmlspecialchars($final['atk_name'] ?? '', ENT_QUOTES) ?></span>
                            <span><?= htmlspecialchars($final['def_name'] ?? '', ENT_QUOTES) ?></span>
                        </p>
                        <p class="heart"><span class="score<?= (int)$score_win[1] ?>"></span><span class="score<?= (int)$score_lose[1] ?>"></span></p>
                        <p class="hp"><span class="score<?= (int)$score_win[5] ?>"></span><span class="score<?= (int)$score_lose[5] ?>"></span></p>
                        <p class="talent"><span class="score<?= (int)$score_win[0] ?>"></span><span class="score<?= (int)$score_lose[0] ?>"></span></p>
                    </div>
                <?php endif; ?>
                <?= $final['lo_msg'] ?? '' ?>
            </div>
        <?php endif; // end end_logs 

        if (!$isend && $mybattle) {
            // 기술선택
            $move_select = false;
            if ((!$lo['lo_7'] || !$lo['lo_8']) && $mybattle) {
                if (($lo['lo_3'] == $character['ch_id']) && !$lo['lo_7']) { // p1
                    $move_select      = true;
                    $wa_id_ar         = "lo_7";
                    $re_wa_id_ar      = "lo_8";
                    $battle_ph_id     = (int)$lo['lo_4'];
                    $battle_re_ph_id  = (int)$lo['lo_6'];
                } elseif (($lo['lo_5'] == $character['ch_id']) && !$lo['lo_8']) { // p2
                    $move_select      = true;
                    $wa_id_ar         = "lo_8";
                    $re_wa_id_ar      = "lo_7";
                    $battle_ph_id     = (int)$lo['lo_6'];
                    $battle_re_ph_id  = (int)$lo['lo_4'];
                } ?>
                <form action="<?= G5_URL ?>/pokemon/battle/search_update.php" method="post" style="margin:0 auto; text-align:center;">
                    <?php if ($move_select) {
                        $waza_list_html = '';
                        $wazas = get_waza_list($battle_ph_id, $battle_re_ph_id);
                        $idx = 0;
                        $pp_empty=true;
                        foreach ($wazas as $wa) {
                            $wa_name = htmlspecialchars($wa['wa_name'] ?? '', ENT_QUOTES);
                            $wa_pp   = 'PP '.(int)($wa['pp_now'] ?? 0).'/'.(int)($wa['pp_max'] ?? 0);
                            $wa_type = '';
                            $wa_class = ($wa['wa_category'] ?? '') === '보조'
                                ? 'ast' : ((($wa['wa_category'] ?? '') === '공격') ? 'atk' : 'def');
                            if (($wa['wa_category'] ?? '') === '공격') {
                                $wa_type = htmlspecialchars(($wa['wa_type'] ?? '').'/'.($wa['wa_type2'] ?? ''), ENT_QUOTES);
                            }
                            $id = 'wa_'.$wr_id.'_'.$idx;
                            if($wa['pp_now']>0){
                                $pp_empty=false;
                                // 효과 메시지 덧붙임
                                $content = (string)($wa['wa_content'] ?? '');
                                if (!empty($wa['wa_msg'])) { $content .= "<br>".(string)$wa['wa_msg']; }
                                $wa_content = htmlspecialchars($content, ENT_QUOTES);
                                $input='<input type="radio" name="wh_id" id="'.$id.'" value="'.(int)$wa['wh_id'].'" onclick="changemsg('.$wr_id.', \''.$wa_content.'\')">';
                            }else{
                                $input='';
                                $wa_class.=" disabled";
                            }
                            $waza_list_html .= '<label for="'.$id.'">
                                                '.$input.'
                                                <div class="select_waza '.$wa_class.'">
                                                    <p class="wa_name">'.$wa_name.'</p>
                                                    <p class="wa_pp">'.$wa_pp.'</p>
                                                    <p class="wa_type">'.$wa_type.'</p>
                                                </div>
                                                </label>';
                            $idx++;
                        } ?>

                        <input type="hidden" name="wr_id" value="<?= $wr_id ?>">
                        <input type="hidden" name="wa_id_ar" value="<?= $wa_id_ar ?>">
                        <input type="hidden" name="re_wa_id_ar" value="<?= $re_wa_id_ar ?>">
                        <input type="hidden" name="bo_table" value="<?= htmlspecialchars($bo_table, ENT_QUOTES) ?>">
                        <input type="hidden" name="lo_id" value="<?= (int)$lo['lo_id'] ?>">
                        <input type="hidden" name="f_type" value="wa_select">
                        <div>
                            <div class="waza_list" style="margin: 0 auto;"><?= $waza_list_html ?></div>
                            <?if($pp_empty){?>
                               <input type="radio" name="wh_id" value="99999" style="display:none;" checked>
                               <button type="submit" class="fight-btn">
                                    <p id="s_msg_<?= $wr_id ?>">사용할 수 있는 기술이 없다!</p>
                                    발버둥
                                </button>  
                            <?}else{?>
                                <button type="submit" class="fight-btn">
                                    <p id="s_msg_<?= $wr_id ?>">사용할 기술을 선택해 주세요.</p>
                                    기술 선택
                                </button>
                            <?}?>
                        </div>
                    <?php } else { ?>
                        <div class="fight-btn" style="margin: 0 auto;">상대의 응답을 기다리고 있습니다...</div>
                    <?php } ?>
                </form>
            <?php }
        }
    endif; // 참가 체크 else ?>
</div>
<div class="battle_bar"></div>
