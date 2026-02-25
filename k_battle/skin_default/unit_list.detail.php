<li class="rm-area theme-box <?php echo (isset($rm['hp_now']) && (int)$rm['hp_now'] <= 0) ? 'retire' : ''; ?>">
    <div class="btn-area">
        <span class="ui-btn"
                onclick='window.open("<?php echo G5_URL ?>/k_battle/skill.php?<?php echo $raid_get ?>&type=<?php echo ses($rm, 'unit_type', '') ?>&rm_id=<?php echo ses($rm, 'rm_id', 0, 'int') ?>","raid_skill","width=500,height=800");'>
            스킬 일람
        </span>
    </div>

    <div class="ui-thumb">
        <img src="<?php echo h(ses($rm, 'unit_thumb', ''))?>">
    </div>

    <?php
    // 수치 계산
    $hp_now  = ses($rm, 'hp_now', 0, 'int');
    $hp_max  = max(1, ses($rm, 'hp_max', 1, 'int'));
    $mp_now  = ses($rm, 'mp_now', 0, 'int');
    $mp_max  = max(1, ses($rm, 'mp_max', 1, 'int'));
    $hp_pct  = max(0, min(100, ($hp_now / $hp_max) * 100));
    $mp_pct  = max(0, min(100, ($mp_now / $mp_max) * 100));

    $is_aggr = ses($rm, 'is_aggr', 0, 'int');
    $is_stun = ses($rm, 'is_stun', 0, 'int');
    $tt_done = ses($rm, 'tt_done', 0, 'int');

    // 버프/디버프 목록
    $buff_html   = '';
    $debuff_html = '';
    $buff_cnt = 0;
    $debuff_cnt = 0;

    if (preg_match('/^\w+$/', $battle_table) && isset($rm['rm_id'])) {
        $rm_id_int = (int)$rm['rm_id'];

        // 버프
        $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value > 0 AND rm_id = '{$rm_id_int}'");
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
        $bf_list = sql_query("SELECT * FROM {$battle_table}_buff WHERE bf_value < 0 AND rm_id = '{$rm_id_int}'");
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
    }
    ?>

    <div class="bar-area">
        <div class="rm-name">
            <?php echo h(ses($rm, 'unit_name', ''))?>
            <div class="rm-buff">
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
        </div>

        <div class="hp-bar">
            <span class="bar-inner" style="width:<?php echo $hp_pct ?>%"></span>
            <i><?php echo $hp_now . '/' . $hp_max ?></i>
        </div>

        <?php if (!empty($kb_cf['mp'])): ?>
            <div class="mp-bar">
                <span class="bar-inner" style="width:<?php echo $mp_pct ?>%"></span>
                <i><?php echo $mp_now . '/' . $mp_max ?></i>
            </div>
        <?php endif; ?>
    </div>
</li>
