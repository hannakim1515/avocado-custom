<?php
require_once './_common.php';
require_once G5_PATH.'/head.sub.php';

/* ---- 입력값/기본값 ---- */
$type     = ses($_REQUEST, 'type', '');
/* ---- select 컬럼 결정 ---- */
$select = '';
if ($type === 'ch' && isset($ally_select) && is_string($ally_select) && $ally_select !== '') {
    $select = $ally_select;
} elseif ($type === 'mo' && isset($enemy_select) && is_string($enemy_select) && $enemy_select !== '') {
    $select = $enemy_select;
}

/* ---- 스탯 목록 ---- */
$sc_list = array();
$sc_sql = sql_query("SELECT sc_id, sc_name FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
for ($i = 0; ($row = sql_fetch_array($sc_sql)); $i++) {
    $sc_list[(int)$row['sc_id']] = $row['sc_name'];
}

/* ---- 유닛 목록 ---- */
$unit_list = array();
$tmp = get_k_unit_list($type, $ra_id, $select);
if (is_array($tmp)) $unit_list = $tmp;
?>

<style>
    @import url(<?php echo G5_URL?>/k_battle/css/raid.default.css);
    @import url(<?php echo G5_URL?>/k_battle/css/admin.css);
</style>


<div class='btns'>
    <a class='ui-btn point'
    href="./raid.unit.php?<?php echo $raid_get ?>&type=ch">
    캐릭터
    </a>
    <a class='ui-btn'
    href="./raid.log.php?<?php echo $raid_get ?>">
    로그
    </a>
    <?php if ($is_admin){?>
        <a class='ui-btn'
        href="./raid.admin.php?<?php echo $raid_get ?>">
        관리자
    </a>
    <?php }?>
</div>


<ul class='unit-list-detail'>
<?php
if (!empty($unit_list)) {
    foreach ($unit_list as $rm) {
        include(G5_PATH."/k_battle/skin_default/unit_list.detail.php");
    }
} else {
    echo "<li class='theme-box'>유닛이 없습니다.</li>";
}
?>
</ul>

<?php require_once G5_PATH.'/tail.sub.php'; ?>
