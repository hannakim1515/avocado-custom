<?php
require_once '../../common.php';

/* 입력 기본값 및 방어 */
$id = ses($_REQUEST, 'id', 0, 'int');
$viewslot = ses($_REQUEST, 'viewslot', '', 'raw');

/* 장비 조회 */
$eq = get_k_equip_item($id, "eq.*, it.*, ug.ug_name, inven.ch_id, inven.se_ch_id, inven.in_memo", true);
if (!is_array($eq)) $eq = array();

if (empty($eq['it_id'])) {
    echo '장비 정보를 찾을 수 없습니다.';
    return;
}

/* 스탯 목록 */
$st_list = array();
$stat_sql = sql_query("SELECT st_id, st_name FROM {$g5['status_config_table']} ORDER BY st_order ASC LIMIT 10");
while ($row = sql_fetch_array($stat_sql)) {
    $st_list[] = $row;
}

/* 강화 한도 계산 */
$upgrade = true;

if (empty($kb_cf['upgrade_limit'])) {
    $max = sql_fetch("SELECT ug_id FROM {$g5['k_upgrade_table']} ORDER BY ug_id DESC LIMIT 1");
    $kb_cf['upgrade_limit'] = ses($max, 'ug_id', 0, 'int');
}
$character['ch_rank'] = ses($character, 'ch_rank', 0, 'int');

$eq['ug_id'] = ses($eq, 'ug_id', 0, 'int');
$eq['ug_limit'] = ses($eq, 'ug_limit', '', 'raw');

if ($eq['ug_limit'] === '9999') {
    $upgrade = false;
    $ug_max = '강화 불가';
} else {
    if (!empty($eq['ug_limit']) && (int)$eq['ug_limit'] < (int)$kb_cf['upgrade_limit']) {
        $kb_cf['upgrade_limit'] = (int)$eq['ug_limit'];
    }
    $max = sql_fetch("SELECT ug_name FROM {$g5['k_upgrade_table']} WHERE ug_id='{$kb_cf['upgrade_limit']}'");
    $ug_max = '최대 ' . ses($max, 'ug_name', '');
}

if ((int)$kb_cf['upgrade_limit'] >= (int)$eq['ug_id']) {
    $ug = sql_fetch(
        "SELECT * FROM {$g5['k_upgrade_table']}
         WHERE ug_id > {$eq['ug_id']}
           AND (ch_rank = '0' OR ch_rank <= {$character['ch_rank']})
         ORDER BY ug_id ASC
         LIMIT 1"
    );
    if (empty($ug['ug_id'])) {
        $upgrade = false;
    }
}

/* 소유자 판정 */
$isMine = isset($eq['ch_id'], $character['ch_id']) && (int)$eq['ch_id'] === (int)$character['ch_id'];

/* 출력에 사용될 안전 값 */
$eq_img   = h(ses($eq, 'eq_img', '', 'raw'));
$eq_name  = h(ses($eq, 'eq_name', '', 'raw'));
$ug_name  = h(ses($eq, 'ug_name', '', 'raw'));
$eq_type  = h(ses($eq, 'eq_type', '', 'raw'));
$ug_max_s = h($ug_max);
$it_sell  = ses($eq, 'it_sell', 0, 'int');
$can_sell = !empty($eq['it_use_sell']);
$has_item = !empty($eq['it_has']);
$memo_safe = h(ses($eq, 'in_memo', '', 'raw'));
$se_ch_id  = ses($eq, 'se_ch_id', 0, 'int');
$money_unit = h(ses($config, 'cf_money_pice', '', 'raw'));
?>

<div class="info-header">
    <?php if ($isMine): ?>
        <div class="asset-mine">
            <?php if ($can_sell): ?>
                <a class="ui-btn"
                   href="javascript:equipAction('<?php echo (int)$eq['in_id'] ?>','sell');"
                   data-idx="<?php echo (int)$eq['in_id'] ?>" data-type="sell">판매(<?php echo $it_sell . $money_unit ?>)</a>
            <?php endif; ?>

            <?php if (!$has_item): ?>
                <a class="ui-btn"
                   href="javascript:equipAction('<?php echo (int)$eq['in_id'] ?>','take');"
                   data-idx="<?php echo (int)$eq['in_id'] ?>" data-type="take">선물</a>
            <?php endif; ?>

            <div class="sk-upgrade ui-btn"
                 onclick='window.open("<?php echo G5_URL ?>/k_battle/equip.php?type=custom","강화","width=400,height=500");'>커스텀</div>

            <?php if ($upgrade): ?>
                <div class="sk-upgrade ui-btn"
                     onclick='window.open("<?php echo G5_URL ?>/k_battle/equip.php?type=upgrade","강화","width=400,height=500");'
                     id="upgrade">강화</div>
            <?php else: ?>
                <div class="sk-upgrade ui-btn point" style="cursor:default">최대 강화</div>
            <?php endif; ?>

            <?php
            if (!empty($viewslot)) {
                $slot = !empty($kb_cf['equip_max']) ? explode('|', (string)$kb_cf['equip_max']) : array();
                $type = !empty($kb_cf['equip_type']) ? explode('|', (string)$kb_cf['equip_type']) : array();
                array_unshift($type, '공용');

                $max_list = array();
                $len = max(count($type), count($slot));
                for ($i = 0; $i < $len; $i++) {
                    $k = ses($type, $i, 'TYPE_' . $i);
                    $v = ses($slot, $i, 0, 'int');
                    $max_list[$k] = $v;
                }

                $now = sql_fetch(
                    "SELECT COUNT(*) AS cnt
                     FROM {$g5['k_ch_equip_table']} eq
                     JOIN {$g5['inventory_table']} inven ON inven.in_id = eq.in_id
                     WHERE inven.ch_id = '" . (int)$character['ch_id'] . "'
                       AND eq.eq_use = '" . sql_escape_string($viewslot) . "'"
                );

                $now_cnt = ses($now, 'cnt', 0, 'int');
                $limit_for_slot = ses($max_list, $viewslot, 0, 'int');

                if (!empty($eq['eq_use']) || $limit_for_slot > $now_cnt):
            ?>
                <div class="asset-use ui-btn" data-id="<?php echo (int)$id ?>" id="equip_equip">
                    <?php echo !empty($eq['eq_use']) ? '해제' : '장착' ?>
                </div>
            <?php
                endif;
            }
            ?>
        </div>
    <?php endif; ?>

    <p class="asset-icon equip-img">
        <img src="<?php echo $eq_img ?>">
        <?php if (!empty($ug_name)): ?>
            <span class="eq_lv lv_<?php echo (int)$eq['ug_id'] ?>"><?php echo $ug_name ?></span>
        <?php endif; ?>
    </p>
    <p class="asset-name"><?php echo $eq_name ?> <?php if (!empty($ug_name)) { echo "[{$ug_name}]"; } ?></p>
</div>

<div class="asset-option">
    <?php
    for ($i = 0; $i < 10; $i++) {
        $tag = 'st_' . ($i+1);
        if (!empty($eq[$tag])) {
            $st_nm = isset($st_list[$i]) ? h(ses($st_list[$i], 'st_name', 'ST'.($i+1))) : ('ST'.($i+1));
            echo "<span>{$st_nm} +" . (int)$eq[$tag] . "</span> ";
        }
    }
    ?>
</div>

<div class="asset-content"><?php echo h(ses($eq, 'eq_content', '')) ?></div>

<div class="info-footer">
    <?php if (!empty($eq['it_content2'])): ?>
        <p class="asset-content2"><?php echo $eq['it_content2'] ?></p>
    <?php endif; ?>
    <p class="sk-mp">
        <?php echo $eq_type ?> | <?php echo $ug_max_s ?>
        <?php if ($se_ch_id): ?>
            <span onclick="viewImg('memo','<?php echo $memo_safe ?>')"><?php echo get_character_name($se_ch_id) ?></span>
        <?php endif; ?>
    </p>
</div>

<script>
$("#equip_equip").on("click", function(){
    var formData = new FormData();
    var id = $(this).attr('data-id');
    var text = $(this).text();

    $.ajax({
        url: g5_url + "/k_battle/ajax/equip.php?load=equip&id=" + id + "&slot=" + viewslot,
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        type: "POST",
        success: function (data) {
            if (data && data.result === 'S') {
                if (text.trim() === '장착') {
                    $("#equip_equip").text('해제');
                    $("#equip_has_list li[data-id='" + id + "']").addClass("selected");
                    $("#equip_list li.none." + viewslot + ":first").before(data.in);
                    $("#equip_list li.none." + viewslot + ":first").remove();
                } else {
                    $("#equip_equip").text('장착');
                    $("#equip_has_list li[data-id='" + id + "']").removeClass("selected");
                    $("#equip_list li[data-id='" + id + "']").after(
                        '<li class="none selected ' + viewslot + '" onclick="loadItemList(\'' + viewslot + '\')"></li>'
                    );
                    $("#equip_list li[data-id='" + id + "']").remove();
                }
            }
        }
    });
});
</script>
