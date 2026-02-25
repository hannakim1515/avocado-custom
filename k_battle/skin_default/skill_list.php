<div class="asset-area" id="sk_area">
    <div class="list-area">
        <ul class="asset-list skill use" id="skill_list">
            <?php
            // 방어
            $max_use = ses($kb_cf, 'skill_max', 0, 'int');
            $ch_id = ses($ch, 'ch_id', 0, 'int');

            // 장착중 스킬
            $use_list = get_k_skill_list('use', $ch_id);
            if (!is_array($use_list)) $use_list = array();

            $use_cnt = count($use_list);
            foreach ($use_list as $row):
                $cs_id   = ses($row, 'cs_id', 0, 'int');
                $sk_icon = h(ses($row, 'sk_icon', '', 'raw'));
            ?>
                <li data-id="<?php echo $cs_id ?>" onclick="assetInfo(<?php echo $cs_id ?>,'skill')">
                    <img src="<?php echo $sk_icon ?>">
                </li>
            <?php endforeach; ?>

            <?php
            // 빈 슬롯 채우기
            $empty = max(0, $max_use - $use_cnt);
            for ($i = 0; $i < $empty; $i++): ?>
                <li class="none"></li>
            <?php endfor; ?>
        </ul>
    </div>

    <div class="info-area theme-box">
        <?php
        // 전체 보유 스킬
        $all_list = get_k_skill_list('', $ch_id);
        if (!is_array($all_list)) $all_list = array();
        ?>
        <ul class="asset-list skill has" id="skill_has_list">
            <?php foreach ($all_list as $row):
                $cs_id   = ses($row, 'cs_id', 0, 'int');
                $sk_icon = h(ses($row, 'sk_icon', '', 'raw'));
                $sel     = !empty($row['cs_use']) ? 'selected' : '';
            ?>
                <li class="<?php echo $sel ?>" data-id="<?php echo $cs_id ?>" onclick="assetInfo(<?php echo $cs_id ?>,'skill')">
                    <img src="<?php echo $sk_icon ?>">
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="asset-info" id="skill_info"></div>
    </div>
</div>
