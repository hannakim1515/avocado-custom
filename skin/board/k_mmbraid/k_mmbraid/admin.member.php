<?php
include_once('../../../../common.php');
include_once(G5_PATH.'/head.sub.php');

if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}

// 권한 체크
if ($is_admin !== 'super') {
    echo '<script>alert("권한이 없습니다."); window.close();</script>';
    exit;
}

/** 참여 유닛 목록 */
$unit_sql = "
    SELECT origin.*, unit.*
    FROM {$g5['character_table']} AS origin
    JOIN {$battle_table}_unit AS unit
      ON unit.unit_id = origin.ch_id
     AND unit.unit_type = 'ch'
     AND unit.ra_id = '{$ra_id}'
    ORDER BY origin.ch_name ASC
";

$unit_list = sql_query($unit_sql);

/** 스탯 정의 목록 */
$sc_list = [];
if (count($k_stat) > 1) {
    for ($si = 0; $si < count($k_stat); $si++) {
        $sid = (int)$k_stat[$si];
        $sc_list[] = sql_fetch("SELECT * FROM {$g5['k_stat_table']} WHERE sc_id='{$sid}'");
    }
}

/** 전체 캐릭터 목록 */
$ch_list = get_character_list();
?>
<style>@import url('<?php echo G5_URL; ?>/k_battle/css/admin.css');</style>

<div class="btns">
    <a class="ui-btn" href="./admin.php?bo_table=<?php echo h($bo_table); ?>">기본설정</a>
    <a class="ui-btn" href="./admin.css.php?bo_table=<?php echo h($bo_table); ?>">스타일설정</a>
    <a class="ui-btn" href="./admin.text.php?bo_table=<?php echo h($bo_table); ?>">텍스트설정</a>
    <a class="ui-btn point" href="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">참여자관리</a>
</div>

<div class="search_area">
    <form action="./admin_update.php" method="post">
        <input type="hidden" name="url" value="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">
        <input type="hidden" name="bo_table" value="<?php echo h($bo_table); ?>">
        <select name="unit_id">
            <option value="">캐릭터 선택</option>
            <?php
            if (is_array($ch_list)) {
                $clen = count($ch_list);
                for ($ci = 0; $ci < $clen; $ci++) {
                    $ch_id = ses($ch_list[$ci], 'ch_id', 0, 'int');
                    $ch_name = ses($ch_list[$ci], 'ch_name', '');
                    if ($ch_id <= 0) { continue; }
                    $check = sql_fetch("SELECT unit_id FROM {$battle_table}_unit WHERE unit_id='{$ch_id}' AND unit_type='ch' AND ra_id='{$ra_id}'");
                    if (empty($check['unit_id'])) {
                        echo '<option value="'.h($ch_id).'">'.h($ch_name).'</option>';
                    }
                }
            }
            ?>
        </select>
        <input type="submit" class="ui-btn" name="act_button" value="참가등록" onclick="document.pressed=this.value">
        <span class="ui-btn" onclick='window.open("<?php echo G5_URL; ?>/k_battle/unit.php?bo_table=<?php echo h($bo_table); ?>&type=ch","raid_unit","width=500,height=800");'>참여자 일람</span>
    </form>
</div>

<form action="./admin_update.php" method="post" onsubmit="return f_submit(this);">
    <input type="hidden" name="url" value="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">
    <input type="hidden" name="bo_table" value="<?php echo h($bo_table); ?>">
    <table class="theme-form member">
        <colgroup>
            <col style="width:50px"><col style="width:100px"><col>
            <?php if (!empty($kb_cf['mp'])): ?><col><?php endif; ?>
            <?php for ($si = 0; $si < count($sc_list); $si++): ?><col><?php endfor; ?>
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
                <th>캐릭터</th>
                <th><?php echo h(ses($kb_cf, 'hp_name', 'HP')); ?></th>
                <?php if (!empty($kb_cf['mp'])): ?>
                    <th><?php echo h(ses($kb_cf, 'mp_name', 'MP')); ?></th>
                <?php endif; ?>
                <?php
                for ($si = 0; $si < count($sc_list); $si++) {
                    echo '<th>'.h(ses($sc_list[$si], 'sc_name', '')).'</th>';
                }
                ?>
            </tr>
        </thead>
        <tbody>
        <?php
        for ($ri = 0; $row = sql_fetch_array($unit_list); $ri++) {
            $bg = 'bg'.($ri % 2);
            $rm_id  = ses($row, 'rm_id', 0, 'int');
            $ch_name= ses($row, 'ch_name', '');
            $hp_now = ses($row, 'hp_now', 0, 'int');
            $hp_max = ses($row, 'hp_max', 0, 'int');
            $mp_now = ses($row, 'mp_now', 0, 'int');
            $mp_max = ses($row, 'mp_max', 0, 'int');?>
            <tr class="<?php echo h($bg); ?>">
                <td>
                    <input type="hidden" name="rm_id[<?php echo h($ri); ?>]" value="<?php echo h($rm_id); ?>">
                    <input type="checkbox" name="chk[]" value="<?php echo h($ri); ?>" id="chk_<?php echo h($ri); ?>">
                </td>
                <td><?php echo h($ch_name); ?></td>
                <td><?php echo h($hp_now).'/'.h($hp_max); ?></td>
                <?php if (!empty($kb_cf['mp'])): ?>
                    <td><?php echo h($mp_now).'/'.h($mp_max); ?></td>
                <?php endif; ?>
                <?php
                for ($si = 0; $si < count($sc_list); $si++) {
                    $st_tag = 'st_'.($si + 1);
                    echo '<td>'.h(ses($row, $st_tag, '')).'</td>';
                }
                ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <div style="text-align:right;">
        <input type="submit" class="ui-btn" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
    </div>
</form>

<script>
function check_all(f){
    var chk = document.getElementsByName("chk[]");
    for (var i=0;i<chk.length;i++){ chk[i].checked = f.chkall.checked; }
}
function f_submit(f){
    if(!is_checked("chk[]")){
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if(document.pressed === "선택삭제"){
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")){ return false; }
    }
    return true;
}
</script>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>
