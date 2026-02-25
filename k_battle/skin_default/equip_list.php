<?php
// 방어: 설정값 분해
$equip_max  = ses($kb_cf, 'equip_max', '', 'raw');
$equip_type = ses($kb_cf, 'equip_type', '', 'raw');
$slot = $equip_max !== '' ? explode('|', $equip_max) : array();
$type = $equip_type !== '' ? explode('|', $equip_type) : array();
array_unshift($type, '공용');

// 방어: 캐릭터 식별자
$ch_id = ses($ch, 'ch_id', 0, 'int');

// 장착 목록(슬롯별)
$eq_list = array();
if ($ch_id > 0) {
    $eq_sql = sql_query("
        SELECT eq.in_id, eq.eq_img, eq.eq_use, ug.ug_name, eq.ug_id
        FROM {$g5['k_ch_equip_table']} AS eq
        INNER JOIN {$g5['inventory_table']} AS inven ON inven.in_id = eq.in_id
        LEFT JOIN {$g5['k_upgrade_table']} AS ug ON eq.ug_id = ug.ug_id
        WHERE inven.ch_id = '{$ch_id}'
          AND eq.eq_use <> ''
    ");
    while ($row = sql_fetch_array($eq_sql)) {
        $use = ses($row, 'eq_use', '', 'raw');
        if (!isset($eq_list[$use])) $eq_list[$use] = array();
        $eq_list[$use][] = $row;
    }
}

?>
<style>
<?php
// 타입 라벨 CSS
$cnt_type = count($type);
for ($i = 0; $i < $cnt_type; $i++) {
    $t = $type[$i];
    // CSS content 보안: 작은따옴표 이스케이프
    $label = str_replace("'", "\\'", $t);
    echo "#equip_list li." . h($t) . ":after{content:'{$label}';}";
}
?>
</style>

<div class="asset-area" id="eq_area">
    <div class="list-area">
        <ul class="asset-list equip use" id="equip_list">
            <?php
            $cnt_slot = count($slot);
            for ($i = 0; $i < $cnt_slot; $i++):
                $t = ses($type, $i, 'TYPE_'.$i);
                $cap = ses($slot, $i, 0, 'int');
                if ($cap <= 0) { continue; }

                $used = isset($eq_list[$t]) ? count($eq_list[$t]) : 0; // count()가 필요하므로 isset 유지

                for ($hidx = 0; $hidx < $cap; $hidx++):
                    if ($hidx < $used):
                        $row = $eq_list[$t][$hidx];
                        $in_id  = ses($row, 'in_id', 0, 'int');
                        $eq_img = h(ses($row, 'eq_img', '', 'raw'));
                        $ug_id  = ses($row, 'ug_id', 0, 'int');
                        $ug_nm  = h(ses($row, 'ug_name', '', 'raw'));
                        $lv_badge = $ug_nm !== '' ? "<span class=\"eq_lv lv_{$ug_id}\">{$ug_nm}</span>" : '';
            ?>
                        <li class="equip-img <?php echo h($t) ?>"
                            data-id="<?php echo $in_id ?>"
                            onclick="assetInfo(<?php echo $in_id ?>,'equip','<?php echo h($t) ?>')">
                            <img src="<?php echo $eq_img ?>"><?php echo $lv_badge ?>
                        </li>
            <?php   else: ?>
                        <li class="none <?php echo h($t) ?>" onclick="loadItemList('<?php echo h($t) ?>')"></li>
            <?php   endif;
                endfor;
            endfor; ?>
        </ul>
    </div>

    <div class="info-area theme-box">
        <?php
        // 보유 장비 전체 목록
        $has_list = array();
        if ($ch_id > 0) {
            $eq_sql = sql_query("
                SELECT eq.in_id, eq.eq_img, eq.eq_use, ug.ug_name, eq.ug_id
                FROM {$g5['k_ch_equip_table']} AS eq
                INNER JOIN {$g5['inventory_table']} AS inven ON inven.in_id = eq.in_id
                LEFT JOIN {$g5['k_upgrade_table']} AS ug ON eq.ug_id = ug.ug_id
                WHERE inven.ch_id = '{$ch_id}'
                ORDER BY eq.eq_use DESC, eq.ug_id DESC
            ");
            while ($row = sql_fetch_array($eq_sql)) {
                $has_list[] = $row;
            }
        }
        ?>
        <ul class="asset-list equip has" id="equip_has_list">
            <?php foreach ($has_list as $row):
                $in_id  = ses($row, 'in_id', 0, 'int');
                $eq_img = h(ses($row, 'eq_img', '', 'raw'));
                $ug_id  = ses($row, 'ug_id', 0, 'int');
                $ug_nm  = h(ses($row, 'ug_name', '', 'raw'));
                $sel    = !empty($row['eq_use']) ? 'selected' : '';
                $lv_badge = $ug_nm !== '' ? "<span class=\"eq_lv lv_{$ug_id}\">{$ug_nm}</span>" : '';
            ?>
                <li class="equip-img <?php echo $sel ?>" data-id="<?php echo $in_id ?>" onclick="assetInfo(<?php echo $in_id ?>,'equip')">
                    <img src="<?php echo $eq_img ?>"><?php echo $lv_badge ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="asset-info" id="equip_info"></div>
    </div>
</div>
