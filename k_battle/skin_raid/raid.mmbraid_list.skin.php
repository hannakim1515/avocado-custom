<?php
// 입력·환경 기본값
if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}

$turn_type      = ses($board, 'bo_10_subj', '');
$raid_css    = ses($board, 'bo_9', '') !== '' ? explode('|', (string)$board['bo_9']) : array();
$comment_css = ses($board, 'bo_9_subj', '') !== '' ? explode('|', (string)$board['bo_9_subj']) : array();
$log_css     = ses($board, 'bo_12', '') !== '' ? explode('|', (string)$board['bo_12']) : array();

// 내 유닛 rm 조회
$rm = array();
if ($battle_table && !empty($character['ch_id'])) {
    $rm = sql_fetch("
        SELECT rm_id
        FROM {$battle_table}_unit
        WHERE unit_id = '".(int)$character['ch_id']."'
          AND ra_id   = '".sql_escape_string($ra_id)."'
          AND unit_type = 'ch'
    ");
    if (!empty($rm['rm_id'])) {
        $select = "origin.ch_name as unit_name, origin.ch_thumb as unit_thumb, unit.*";
        $rm = get_k_unit((int)$rm['rm_id'], 'ch', $select);
    }
}

// 몬스터 유닛 조회
$mo = array();
if ($battle_table && !empty($board['bo_2'])) {
    $mo = sql_fetch("
        SELECT *
        FROM {$battle_table}_unit AS unit
        JOIN {$g5['k_monster_table']} AS origin ON unit.unit_id = origin.mo_id
        WHERE unit.unit_id = '".(int)$board['bo_2']."'
          AND unit.unit_type = 'mo'
          AND unit.ra_id = '".sql_escape_string($ra_id)."'
        LIMIT 1
    ");
}

// 스탯 이름 목록
$sc_list = array();
$sc_sql = sql_query("SELECT sc_id, sc_name FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
while ($row = sql_fetch_array($sc_sql)) {
    $sc_list[(int)$row['sc_id']] = $row['sc_name'];
}
?>

<style>
    @import url('<?php echo G5_URL ?>/k_battle/css/raid.mmbraid.css');
</style>
<?php include_once G5_PATH . '/k_battle/css/raid.mmbraid.custom.php'; ?>

<?php if ($is_admin === 'super'): ?>
    <div class="mmbraid">
        <div class="ui-btn admin"
             onclick='window.open("<?php echo $board_skin_url ?>/k_mmbraid/admin.php?bo_table=<?php echo h($bo_table) ?>","mmbraid_admin","width=500,height=800");'>
            mmb 레이드 설정
        </div>
    </div>
<?php endif;?>

<?php if (!empty($board['bo_1']) && $board['bo_1'] === 'true' && !empty($mo['rm_id'])): ?>
    <?php if (!empty($board['bo_10'])) $mo['mo_thumb'] = $board['bo_10']; ?>
    <div class="monster-area theme-box">
        <div class="monster-image">
            <img src="<?php echo h(ses($mo, 'mo_thumb', '')) ?>" alt="">
        </div>
        <div class="system-msg">
            <?php
            $msg = nl2br(ses($board, 'bo_8', ''));
            echo get_k_rand_text($msg);
            ?>
        </div>
        <div class="monster-info">
            <p class="mo-name"><?php echo h(ses($mo, 'mo_name', '')) ?></p>
            <div class="rm-buff">
                <?php
                $mo_is_aggr = ses($mo, 'is_aggr', 0, 'int');
                $mo_is_stun = ses($mo, 'is_stun', 0, 'int');
                ?>
                <div class="aggr" data-cnt="<?php echo $mo_is_aggr ?>"><p class="bf-inner"><span>도발 | 남은 턴 <?php echo $mo_is_aggr ?></span></p></div>
                <div class="stun" data-cnt="<?php echo $mo_is_stun ?>"><p class="bf-inner"><span>기절 | 남은 턴 <?php echo $mo_is_stun ?></span></p></div>
                <?php
                // 버프
                $bf_cnt = 0; $bf_inner = '';
                if ($battle_table && !empty($mo['rm_id'])) {
                    $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value > 0 AND rm_id = '".(int)$mo['rm_id']."'");
                    while ($row = sql_fetch_array($bf_list)) {
                        $name = ses($row, 'si_code', '') === 'buff' ? ses($sc_list, (int)$row['sc_id'], '') : ses($kb_cf, 'hp_name', '');
                        $bf_inner .= "<span>".h($name)." +".(int)$row['bf_value']." | 남은 턴 ".(int)$row['turn_left']."</span>";
                        $bf_cnt++;
                    }
                }
                echo "<div class='buff' data-cnt='{$bf_cnt}'>{$bf_cnt}<p class='bf-inner'>{$bf_inner}</p></div>";

                // 디버프
                $bf_cnt = 0; $bf_inner = '';
                if ($battle_table && !empty($mo['rm_id'])) {
                    $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value < 0 AND rm_id = '".(int)$mo['rm_id']."'");
                    while ($row = sql_fetch_array($bf_list)) {
                        $name = ses($row, 'si_code', '') === 'buff' ? ses($sc_list, (int)$row['sc_id'], '') : ses($kb_cf, 'hp_name', '');
                        $bf_inner .= "<span>".h($name)." ".(int)$row['bf_value']." | 남은 턴 ".(int)$row['turn_left']."</span>";
                        $bf_cnt++;
                    }
                }
                echo "<div class='debuff' data-cnt='{$bf_cnt}'>{$bf_cnt}<p class='bf-inner'>{$bf_inner}</p></div>";
                ?>
                <div class="done" data-cnt="<?php echo ses($mo, 'tt_done', 0, 'int') ?>">행동완료</div>
            </div>
            <?php
            $mo_hp_now = ses($mo, 'hp_now', 0, 'int');
            $mo_hp_max = ses($mo, 'hp_max', 0, 'int');
            $mo_hp_pct = clamp_pct($mo_hp_now, $mo_hp_max);
            ?>
            <p class="hp-bar">
                <span class="bar-inner" style="width:<?php echo $mo_hp_pct ?>%;"></span>
                <i class="bar-info"><?php echo $mo_hp_now ?>/<?php echo max(1, $mo_hp_max) ?></i>
            </p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($rm['rm_id'])): ?>
    <?php
        $rm_retire = (ses($rm, 'hp_now', 0, 'int') <= 0) ? 'retire' : '';
        $rm_is_aggr = ses($rm, 'is_aggr', 0, 'int');
        $rm_is_stun = ses($rm, 'is_stun', 0, 'int');
        $rm_hp_now  = ses($rm, 'hp_now', 0, 'int');
        $rm_hp_max  = ses($rm, 'hp_max', 0, 'int');
        $rm_mp_now  = ses($rm, 'mp_now', 0, 'int');
        $rm_mp_max  = ses($rm, 'mp_max', 0, 'int');
        $rm_hp_pct  = clamp_pct($rm_hp_now, $rm_hp_max);
        $rm_mp_pct  = clamp_pct($rm_mp_now, $rm_mp_max);
    ?>
    <div class="rm-area theme-box <?php echo $rm_retire ?>">
        <div class="btn-area">
            <span class="ui-btn"
                  onclick='window.open("<?php echo G5_URL ?>/k_battle/unit.php?bo_table=<?php echo h($bo_table) ?>&type=ch","raid_unit","width=500,height=800");'>
                참여자 일람
            </span>
            <span class="ui-btn"
                    onclick='window.open("<?php echo G5_URL ?>/k_battle/skill.php?bo_table=<?php echo h($bo_table) ?>&type=<?php echo h(ses($rm, 'unit_type', '')) ?>&rm_id=<?php echo ses($rm, 'rm_id', 0, 'int') ?>","raid_skill","width=500,height=800");'>
                스킬 일람
            </span>
            <?php if (!empty($board['bo_2_subj']) && $board['bo_2_subj'] === 'true'): ?>
                <span class="ui-btn admin" onclick="raidIn('out');">참여 취소</span>
            <?php endif; ?>
        </div>

        <div class="ui-thumb">
            <img src="<?php echo h(ses($rm, 'unit_thumb', '')) ?>" alt="">
        </div>

        <div class="bar-area">
            <div class="rm-name">
                <?php echo h(ses($rm, 'unit_name', '')) ?>
                <div class="rm-buff">
                    <div class="aggr" data-cnt="<?php echo $rm_is_aggr ?>"><p class="bf-inner"><span>도발 | 남은 턴 <?php echo $rm_is_aggr ?></span></p></div>
                    <div class="stun" data-cnt="<?php echo $rm_is_stun ?>"><p class="bf-inner"><span>기절 | 남은 턴 <?php echo $rm_is_stun ?></span></p></div>
                    <?php
                    // 버프
                    $bf_cnt = 0; $bf_inner = '';
                    if ($battle_table) {
                        $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value > 0 AND rm_id = '".(int)$rm['rm_id']."'");
                        while ($row = sql_fetch_array($bf_list)) {
                            $name = ses($row, 'si_code', '') === 'buff' ? ses($sc_list, (int)$row['sc_id'], '') : ses($kb_cf, 'hp_name', '');
                            $bf_inner .= "<span>".h($name)." +".(int)$row['bf_value']." | 남은 턴 ".(int)$row['turn_left']."</span>";
                            $bf_cnt++;
                        }
                    }
                    echo "<div class='buff' data-cnt='{$bf_cnt}'>{$bf_cnt}<p class='bf-inner'>{$bf_inner}</p></div>";

                    // 디버프
                    $bf_cnt = 0; $bf_inner = '';
                    if ($battle_table) {
                        $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value < 0 AND rm_id = '".(int)$rm['rm_id']."'");
                        while ($row = sql_fetch_array($bf_list)) {
                            $name = ses($row, 'si_code', '') === 'buff' ? ses($sc_list, (int)$row['sc_id'], '') : ses($kb_cf, 'hp_name', '');
                            $bf_inner .= "<span>".h($name)." ".(int)$row['bf_value']." | 남은 턴 ".(int)$row['turn_left']."</span>";
                            $bf_cnt++;
                        }
                    }
                    echo "<div class='debuff' data-cnt='{$bf_cnt}'>{$bf_cnt}<p class='bf-inner'>{$bf_inner}</p></div>";
                    ?>
                    <div class="done" data-cnt="<?php echo ses($rm, 'tt_done', 0, 'int') ?>">행동완료</div>
                </div>
            </div>

            <div class="hp-bar">
                <span class="bar-inner" style="width:<?php echo $rm_hp_pct ?>%"></span>
                <i><?php echo $rm_hp_now ?>/<?php echo max(1, $rm_hp_max) ?></i>
            </div>

            <?php if (!empty($kb_cf['mp'])): ?>
                <div class="mp-bar">
                    <span class="bar-inner" style="width:<?php echo $rm_mp_pct ?>%"></span>
                    <i><?php echo $rm_mp_now ?>/<?php echo max(1, $rm_mp_max) ?></i>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>

    <p class="member-in">
        <?php if (!empty($board['bo_2_subj']) && $board['bo_2_subj'] === 'true'): ?>
            레이드에 참여할 수 있습니다.<br>
            <span class="ui-btn point" onclick="raidIn('in','<?php echo h(ses($character, 'ch_name', '')) ?>')">참여하기</span>
        <?php elseif (!empty($board['bo_1']) && $board['bo_1'] === 'true' && isset($board['bo_2_subj']) && $board['bo_2_subj'] === 'false'): ?>
            레이드 참여 신청이 종료되었습니다.
        <?php endif; ?>
    </p>

<?php endif; ?>

<?php if (!empty($board['bo_1']) && $board['bo_1'] === 'true' && !empty($mo['rm_id'])): ?>
    <div style="margin-top:5px; text-align:center">
        <div class="ui-btn" style="height:20px; padding:0 5px;"
             onclick='window.open("<?php echo G5_URL ?>/k_battle/log.php?bo_table=<?php echo h($bo_table) ?>&raid_type=<?php echo $raid_type?>","mmbraid_log","width=350,height=600");'>
            전체 로그 보기
        </div>
    </div>
<?php endif; ?>

<script>
    var $get   = "?bo_table=<?php echo h($bo_table) ?>&raid_type=mmbraid&turn_type=<?php echo h($turn_type) ?>&return_url=<?php echo urlencode(G5_BBS_URL.'/board.php?bo_table='.$bo_table) ?>";
    var $rm_id = "<?php echo ses($rm, 'rm_id', 0, 'int') ?>";

    function raidIn(type, chName) {
        chName = chName || '';
        var msg = '';
        if (type === 'in') {
            if (!chName) { alert('캐릭터 설정을 살펴봐주세요.'); return false; }
            msg = chName + " 캐릭터로 레이드에 참가하시겠습니까?";
        } else if (type === 'out') {
            msg = "레이드 참가를 취소하시겠습니까?";
        } else {
            return false;
        }

        if (!confirm(msg)) return false;
        location.href = g5_url + "/k_battle/ajax/member_update.php" + $get + "&action=" + type;
    }

    $(".ui-mmb-button").append("");
</script>
