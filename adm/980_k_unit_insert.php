<?php
include_once('./_common.php');



// 입력값 기본 확보
$type = ses($_REQUEST, 'type', '', 'raw');

$h_type = h($type);
$h_raid_type = h($raid_type);
$h_ra_id = h($ra_id);

$raid=get_k_raid_type($h_raid_type, $h_ra_id);
$battle_table = $raid['battle_table'];
$ar_value = $raid['ar_value'];
$ar_title = $raid['ar_title'];
$raid_get = $raid['raid_get'];

// 타입별 설정
$type_title = '';
$origin_table = '';
$insert_list = array();
$type_id = '';
$type_name = '';

if ($type === 'mo') {
    $type_title   = '에너미';
    $origin_table = $g5['k_monster_table'];
    // raid_type에 맞는 몬스터만 조회
    $insert_list  = get_k_monster_list('*', $raid_type);
    $type_id   = 'mo_id';
    $type_name = 'mo_name';
} elseif ($type === 'ch') {
    $type_title   = '캐릭터';
    $origin_table = $g5['character_table'];
    $insert_list  = get_character_list();
    $type_id   = 'ch_id';
    $type_name = 'ch_name';
}

// raid_type 기반으로 레이드 설정 테이블 결정
$ra = array();
$raid_config_table = '';
switch ($raid_type) {
    case 'realtime':
        $raid_config_table = $g5['k_realtime_table'];
        break;
    case 'masraid':
        $raid_config_table = $g5['k_masu_table'];
        break;
    case 'mmbraid':
        $raid_config_table = $g5['board_table'];
        break;
}

// 기본 레코드 조회
if ($raid_config_table && $ra_id) {
    $sql = "SELECT * FROM {$raid_config_table} WHERE {$ar_title} = '{$ra_id}'";
    $ra  = sql_fetch($sql);
}

// 유닛 목록
$unit_list = null;
if ($origin_table && $battle_table && $ra_id) {
    $sql = "
        SELECT origin.*, unit.*
          FROM {$origin_table} origin
          JOIN {$battle_table}_unit unit
            ON unit.unit_id = origin.{$type_id}
         WHERE unit.unit_type = '".sql_escape_string($type)."'
           AND unit.ra_id     = '{$ra_id}'
         ORDER BY origin.{$type_name} ASC
    ";
    $unit_list = sql_query($sql);
} else {
    // 빈 결과 대체
    $unit_list = sql_query("SELECT 1 WHERE 0");
}

// 스탯 리스트 구성
$sc_list = array();
// 기존 코드가 $k_stat 배열을 사용. 미정의 방어.
$k_stat = isset($k_stat) && is_array($k_stat) ? $k_stat : array();
if (count($k_stat) > 1) {
    for ($i = 0; $i < count($k_stat); $i++) {
        $sid = (int)$k_stat[$i];
        if ($sid > 0) {
            $sc_list[] = sql_fetch("SELECT * FROM {$g5['k_stat_table']} WHERE sc_id = '{$sid}'");
        }
    }
}

// 타이틀
$g5['title'] = ses($ra, 'ra_title', '').' '.$type_title.' 리스트';

// URL 조합 (폼에서 반복 사용)
$base_url = "./980_k_unit_insert.php?{$ar_title}={$ar_value}&amp;raid_type={$raid_type}";

include_once('./admin.head.sub.php');

?>
<div class='btns'>
    <a class='ui-btn' href="<?php echo $base_url; ?>&amp;type=ch">참여자</a>
    <a class='ui-btn' href="<?php echo $base_url; ?>&amp;type=mo">에너미</a>
</div>

<h2 class="h2_frm">등록</h2>
<div class="tbl_head01 tbl_wrap">
    <form action="./980_k_unit_update.php" method="post">
        <input type="hidden" name="url" value="<?php echo $base_url; ?>&amp;type=<?php echo $h_type; ?>">
        <input type="hidden" name="ra_id" value="<?php echo $h_ra_id; ?>">
        <input type="hidden" name="type" value="<?php echo $h_type; ?>">
        <input type="hidden" name="raid_type" value="<?php echo $h_raid_type; ?>">
        <input type="hidden" name="act" value="in">

        <select name="unit_id">
            <option value="">추가 대상 선택</option>
            <?php
            for ($i = 0; $i < count($insert_list); $i++) {
                $candidate_id   = ses($insert_list[$i], $type_id, '');
                $candidate_name = ses($insert_list[$i], $type_name, '');
                if ($candidate_id === '') continue;

                $check = sql_fetch("SELECT unit_id FROM {$battle_table}_unit WHERE unit_id = '".sql_escape_string($candidate_id)."' AND unit_type = '".sql_escape_string($type)."' AND ra_id = '{$ra_id}'");
                if (empty($check['unit_id'])) {
                    echo '<option value="'.h($candidate_id).'">'.get_text($candidate_name).'</option>';
                }
            }
            ?>
        </select>
        <div class="btn_list01 btn_list" style="display:inline-block">
            <input type="submit" class="ui-btn" name="act_button" value="참가등록" onclick="document.pressed=this.value">
        </div>
    </form>
</div>

<h2 class="h2_frm">목록</h2>
<div class="tbl_head01 tbl_wrap">
<form action="./980_k_unit_update.php" onsubmit="return f_submit(this);" method="post">
    <input type="hidden" name="url" value="<?php echo $base_url; ?>&amp;type=<?php echo $h_type; ?>">
    <input type="hidden" name="ra_id" value="<?php echo $h_ra_id; ?>">
    <input type="hidden" name="type" value="<?php echo $h_type; ?>">
    <input type="hidden" name="raid_type" value="<?php echo $h_raid_type; ?>">

    <table class="theme-form">
        <colgroup>
            <col style="width: 50px;" />
            <col style="width: 100px;" />
            <col />
            <col />
            <?php for ($i = 0; $i < count($sc_list); $i++) { echo '<col />'; } ?>
 
        </colgroup>
        <thead>
            <tr>
                <th><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
                <th>이름</th>
                <th>HP</th>
                <th>MP</th>
                <?php for ($i = 0; $i < count($sc_list); $i++) { echo '<th>'.get_text($sc_list[$i]['sc_name']).'</th>'; } ?>
     
            </tr>
        </thead>
        <tbody>
        <?php
        for ($i = 0; $row = sql_fetch_array($unit_list); $i++) {
            $bg = 'bg'.($i%2);
            ?>
            <tr class="<?php echo $bg; ?>">
                <td>
                    <input type="hidden" name="rm_id[<?php echo $i; ?>]" value="<?php echo (int)$row['rm_id']; ?>">
                    <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
                </td>
                <td><?php echo get_text($row[$type_name]); ?></td>
                <td><?php echo (int)$row['hp_now'].'/'.(int)$row['hp_max']; ?></td>
                <td><?php echo (int)$row['mp_now'].'/'.(int)$row['mp_max']; ?></td>
                <?php
                for ($j = 0; $j < count($sc_list); $j++) {
                    $st_tag = 'st_'.($j+1);
                    echo '<td>'.h(ses($row, $st_tag, '')).'</td>';
                }
                ?>
            </tr>
        <?php } ?>
        <?php if (!isset($i) || $i === 0) { ?>
            <tr><td colspan="<?php echo 5 + max(0, count($sc_list)); ?>" class="empty_table">자료가 없습니다.</td></tr>
        <?php } ?>
        </tbody>
    </table>

    <div class="btn_list01 btn_list">
        <input type="submit" class="ui-btn" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
    </div>
</form>
</div>

<script>
function check_all(f){
    var chk = document.getElementsByName("chk[]");
    for (var i = 0; i < chk.length; i++) chk[i].checked = f.chkall.checked;
}
function f_submit(f){
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if (document.pressed === "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) return false;
    }
    return true;
}
</script>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
