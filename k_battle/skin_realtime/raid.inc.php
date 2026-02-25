<?php

$ra = array();
$ra = sql_fetch("SELECT * FROM {$battle_table} WHERE {$ar_title} = '{$ar_value}'");

// 접근 제어
$ra_state = ses($ra, 'ra_state', 0, 'int');
$my_ch_id = ses($character, 'ch_id', 0, 'int');
$is_participant = false;
$my_unit_row = array();

if ($my_ch_id > 0) {
    $my_unit_row = sql_fetch("
        SELECT rm_id FROM {$battle_table}_unit 
        WHERE ra_id = '{$ra_id}' 
          AND unit_id = '{$my_ch_id}' 
          AND unit_type = 'ch'
    ");
    $is_participant = !empty($my_unit_row['rm_id']);
}


if ($ra_state == 1 && !$is_participant && $is_admin !== 'super') {
    alert('참가하지 않은 레이드입니다.', G5_URL.'/k_battle/raid.php?raid_type=list');
}

$ally_select = isset($ally_select) ? $ally_select : "origin.ch_name as unit_name, origin.ch_thumb as unit_thumb, unit.*";
$enemy_select = isset($enemy_select) ? $enemy_select : "origin.mo_name as unit_name, origin.mo_thumb as unit_thumb, unit.*";

$sc_list = array();
$sc_sql = sql_query("SELECT sc_id, sc_name FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
for ($i = 0; ($row = sql_fetch_array($sc_sql)); $i++) {
    $sc_list[(int)$row['sc_id']] = $row['sc_name'];
}

$turn_type = ses($ra, 'ra_turn_type', 'speed');
$reload_type = ses($ra, 'ra_reload', 'none');
$reload_time = !empty($ra['ra_reload_time']) ? (int)$ra['ra_reload_time'] * 1000 : 5000;
$ra_system = ses($ra, 'ra_system', 'normal');

// Pusher 설정
$use_pusher = false;
$pusher_key = '';
$pusher_cluster = '';
if ($ra_system === 'pusher' && !empty($kb_cf['pusher_key'])) {
    $use_pusher = true;
    $pusher_key = $kb_cf['pusher_key'];
    $pusher_cluster = ses($kb_cf, 'pusher_cluster', 'ap3');
}

// 제한시간 설정
$time_limit = ses($ra, 'ra_time_limit', 0, 'int');
$time_start = ses($ra, 'ra_time_start', 0, 'int');
$time_remaining = 0;
if ($time_limit > 0 && $time_start > 0) {
    $time_remaining = max(0, $time_limit - (time() - $time_start));
}
?>

<style>
    @import url('<?php echo G5_URL?>/k_battle/skin_realtime/raid.realtime.css');
</style>

<div class="all-wrapper" <?php if($ra['ra_bg_img']){ echo "style=\"background-image:url('".h($ra['ra_bg_img'])."')\""; } ?>>
    <div class="left-area">
        <div id="enemy-area">
            <div id="enemy-inner">
                <?php
                // 적 유닛 리스트
                $unit_list = get_k_unit_list('mo', $ra_id, $enemy_select, '', '', true);
                $list_type = 'enemy';
                include G5_PATH."/k_battle/skin_default/unit_list.php";
                ?>
            </div>
        </div>
        <div id="center-area">
            <div id="turn-area">
                <div id="turn-inner">
                    <?php echo ses($ra, 'ra_turn', 0, 'int'); ?>
                </div>
                <?php if ($time_limit > 0): ?>
                <div id="time-limit-area">
                    <span id="time-remaining"><?php echo $time_remaining; ?></span>
                </div>
                <?php endif; ?>
              </div>

            <?php
                // 내 유닛 정보
                $rm = array();
                $rm = sql_fetch("SELECT * FROM {$battle_table}_unit WHERE ra_id = '{$ra_id}' AND unit_id = '".(int)$character['ch_id']."' AND unit_type = 'ch'");


                // 캐릭터 진영 정보
                $rm['ch_side'] = ses($character, 'ch_side', '');


                // 상태값 기본값
                $hp_now  = ses($rm, 'hp_now', 0, 'int');
                $hp_max  = ses($rm, 'hp_max', 0, 'int');
                $hp_pct  = clamp_pct($hp_now, $hp_max);
                $mp_now  = ses($rm, 'mp_now', 0, 'int');
                $mp_max  = ses($rm, 'mp_max', 0, 'int');
                $mp_pct  = clamp_pct($mp_now, $mp_max);
                $is_aggr = ses($rm, 'is_aggr', 0, 'int');
                $is_stun = ses($rm, 'is_stun', 0, 'int');
                $tt_done = ses($rm, 'tt_done', 0, 'int');

                $not_start = (!empty($ra['ra_state']) && (int)$ra['ra_state'] === 1) ? false : '레이드 준비중입니다.';
                $not_myturn = (isset($ra['now_turn'], $rm['rm_id']) && $ra['now_turn'] == $rm['rm_id']) ? false : '내 턴이 아닙니다.';
                $my_class='active';

                // 액션 비활성화 플래그 (action_select.php에서 사용하는 값)
                if ($hp_now <= 0 || $tt_done || $is_stun || $not_start || $not_myturn) {
                    $i_false = $h_false = $a_false = $s_false = "false";
                    $my_class='';
                }
                // 버프
                $buff_html   = '';
                $debuff_html = '';
                $buff_cnt = 0;
                $debuff_cnt = 0;
                
                $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value > 0 AND turn_left > 0 AND rm_id = '".ses($rm, 'rm_id', 0, 'int')."'");
                while ($row = sql_fetch_array($bf_list)) {
                    $name = (ses($row, 'si_code', '')) === 'buff'
                        ? (ses($sc_list, $row['sc_id'], ''))
                        : (ses($kb_cf, 'hp_name', ''));
                    $name = h($name);
                    $val  = ses($row, 'bf_value', 0, 'int');
                    $turn = ses($row, 'turn_left', 0, 'int');
                    $buff_html .= "<span>{$name} +{$val} | 남은 턴 {$turn}</span>";
                    $buff_cnt++;
                }
                // 디버프
                $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value < 0 AND turn_left > 0 AND rm_id = '".ses($rm, 'rm_id', 0, 'int')."'");
                while ($row = sql_fetch_array($bf_list)) {
                    $name = (ses($row, 'si_code', '')) === 'buff'
                        ? (ses($sc_list, $row['sc_id'], ''))
                        : (ses($kb_cf, 'hp_name', ''));
                    $name = h($name);
                    $val  = ses($row, 'bf_value', 0, 'int');
                    $turn = ses($row, 'turn_left', 0, 'int');
                    $debuff_html .= "<span>{$name} {$val} | 남은 턴 {$turn}</span>";
                    $debuff_cnt++;
                }
            ?>
            <div id="system-msg"><?php echo isset($ra['ra_system_msg']) ? h($ra['ra_system_msg']) : ''; ?></div>
            <div id="my-area" class="<?php echo $my_class; ?>">
                <div id="my-inner">
                    <div id="my-action">
                         <?php include G5_PATH."/k_battle/skin_default/action_select.php"; ?>
                    </div>
                    <div id="my-info">
                        <div class="rm-buff unit-status">
                            <div class="aggr" data-cnt="<?php echo $is_aggr ?>">
                            <p class="bf-inner"><span>도발 | 남은 턴 <?php echo $is_aggr ?></span></p>
                            </div>
                            <div class="stun" data-cnt="<?php echo $is_stun ?>">
                                <p class="bf-inner"><span>기절 | 남은 턴 <?php echo $is_stun ?></span></p>
                            </div>

                            <div class="buff" data-cnt="<?php echo $buff_cnt ?>">
                                <?php echo $buff_cnt ?>
                                <p class="bf-inner"><?php echo $buff_html ?></p>
                            </div>

                            <div class="debuff" data-cnt="<?php echo $debuff_cnt ?>">
                                <?php echo $debuff_cnt ?>
                                <p class="bf-inner"><?php echo $debuff_html ?></p>
                            </div>

                            <div class="done" data-cnt="<?php echo $tt_done ?>">행동완료</div>
                        </div>
                        <p class="unit-hp">
                            <span style="width:<?php echo $hp_pct ?>%"></span>
                            <i><?php echo $hp_now ?>/<?php echo max(1, $hp_max) ?></i>
                        </p>
                        <?php if (!empty($kb_cf['mp'])): ?>
                        <p class="unit-mp">
                            <span style="width:<?php echo $mp_pct ?>%"></span>
                            <i><?php echo $mp_now ?>/<?php echo max(1, $mp_max) ?></i>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="ally-area">
            <div id="ally-inner">
                <?php
                // 아군 유닛 리스트
                $unit_list = get_k_unit_list('ch', $ra_id, $ally_select, '', '', true);
                $list_type = 'ally';
                include G5_PATH."/k_battle/skin_default/unit_list.php";
                ?>
            </div>
        </div>
    </div>
    <div class="right-area">
        <div id="log-area">
            <div id="log-inner">
                <?php include G5_PATH."/k_battle/skin_default/raid_log.php"; ?>
            </div>
        </div>
        <div class="log_btn">
            <textarea id="raid-msg"></textarea>
            <div class="btn-area">
                <button class="ui-btn" type="button" onclick="messageInsert();">메시지 입력</button>
                <button class="ui-btn" type="button" onclick='window.open("<?php echo G5_URL ?>/k_battle/skin_realtime/raid.unit.php?<?php echo $raid_get ?>&type=ch","raid_unit","width=500,height=800");'>상세정보</button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($ra['ra_state']) && (int)$ra['ra_state'] < 2): ?>
    <?php if ($use_pusher): ?>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <?php endif; ?>
    <script>
        var $get = "?<?php echo $raid_get?>" + "&turn_type=<?php echo h($turn_type)?>";
        var $rm_id    = "<?php echo ses($rm, 'rm_id', '', 'string') ?>";
        var $lo_id    = "";
        var $ra_turn  = <?php echo ses($ra, 'ra_turn', 0, 'int') ?>;
        var $ra_count = <?php echo ses($ra, 'ra_count', 0, 'int') ?>;
        var $ra_state = <?php echo ses($ra, 'ra_state', 0, 'int') ?>;
        var $reload_time = <?php echo $reload_time?>;
        var $reload_type = "<?php echo h($reload_type)?>";
        var $turn_type = "<?php echo h($turn_type) ?>";

        var $my_reload = <?php echo (isset($ra['now_turn'], $rm['rm_id']) && $ra['now_turn'] == $rm['rm_id']) ? 0 : 1?>;

        // 제한시간 변수
        var $time_limit = <?php echo $time_limit ?>;
        var $time_remaining = <?php echo $time_remaining ?>;
        var $now_turn = "<?php echo ses($ra, 'now_turn', '', 'string') ?>";
        
        // Pusher 설정
        var $use_pusher = <?php echo $use_pusher ? 'true' : 'false' ?>;
        var $pusher_key = "<?php echo h($pusher_key) ?>";
        var $pusher_cluster = "<?php echo h($pusher_cluster) ?>";
        var $pusher_channel = "raid-<?php echo h($ra_id) ?>";
    </script>
    <script src="<?php echo G5_URL?>/k_battle/js/action.js"></script>
    <script src="<?php echo G5_URL?>/k_battle/skin_realtime/action.realtime.js"></script>
<?php endif; ?>
