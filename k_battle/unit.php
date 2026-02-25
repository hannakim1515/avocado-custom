<?php
include_once('./_common.php');
include G5_PATH . '/k_battle/_raid_common.php';

include_once('./_head.sub.php');

/* ---- 입력값/기본값 ---- */
$type     = ses($_REQUEST, 'type', '', 'raw');

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
$unit_list = get_k_unit_list($type, $ra_id, $select);
if (!is_array($unit_list)) {
    $unit_list = array();
}
?>
<style>
    @import url('./css/raid.default.css');
    @import url('./css/admin.css');
</style>

<div class='btns'>
    <a class='ui-btn <?php echo ($type === "ch" ? "point" : ""); ?>'
       href="./unit.php?<?php echo $raid_get ?>&type=ch">
       캐릭터
    </a>
    <a class='ui-btn <?php echo ($type === "mo" ? "point" : ""); ?>'
       href="./unit.php?<?php echo $raid_get ?>&type=mo">
       몬스터
    </a>
</div>

<ul class='unit-list-detail'>
<?php
if (!empty($unit_list)) {
    foreach ($unit_list as $rm) {
        include("./skin_default/unit_list.detail.php");
    }
} else {
    echo "<li class='theme-box'>유닛이 없습니다.</li>";
}
?>
</ul>

<?php include_once('./_tail.sub.php'); ?>
