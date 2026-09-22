<?php
$sub_menu = "980330";
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
// 정렬 기본값
if ($sst === '') $sst = 'lo_id';
if ($sod === '') $sod = 'desc';

$sql_common  = " FROM {$g5['k_upgrade_log_table']} ";
$sql_search  = " WHERE 1 ";
$q = array();

// 검색
if ($stx !== '') {
    $safe_stx = sql_escape_string($stx);
    switch ($sfl) {
        case 'mb_id':
            $sql_search .= " AND {$sfl} = '{$safe_stx}' ";
            break;
        default:
            // 기본: 내용(ug_log) 검색
            $sql_search .= " AND ug_log LIKE '%{$safe_stx}%'";
            $sfl = 'ug_log';
            break;
    }
    $q[] = 'sfl='.urlencode($sfl);
    $q[] = 'stx='.urlencode($stx);
}

$sql_order = " ORDER BY {$sst} {$sod} ";

// 전체 개수
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = (int)$row['cnt'];

$rows        = (int)$config['cf_page_rows'];
$rows        = $rows > 0 ? $rows : 20;
$total_page  = (int)ceil($total_count / $rows);
$page        = min($page, max($total_page, 1));
$from_record = ($page - 1) * $rows;

// 목록
$sql    = "SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// 목록 전체 링크
$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '강화 로그 관리';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan = 7;
$token = get_token();
$qstr  = implode('&', $q);
?>
<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    전체 <?php echo number_format($total_count) ?> 건
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="ug_log"<?php echo get_selected($sfl, "ug_log", true); ?>>내용</option>
        <option value="mb_id" <?php echo get_selected($sfl, "mb_id"); ?>>회원아이디</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo get_text($stx) ?>" id="stx" class="frm_input" required>
    <input type="submit" class="btn_submit" value="검색">
</form>
<br>

<form method="post" action="./980_k_equip_upgrade_log_delete.php" onsubmit="return f_submit(this);">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo get_text($stx) ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>강화 로그 목록</caption>
        <thead>
            <tr>
                <th scope="col"><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
                <th scope="col">이름</th>
                <th scope="col">로그</th>
                <th scope="col">확률</th>
                <th scope="col">사용금</th>
                <th scope="col">사용아이템</th>
                <th scope="col">일시</th>
            </tr>
        </thead>
        <tbody>
        <?php for ($i=0; $row = sql_fetch_array($result); $i++): ?>
            <?php $bg = 'bg'.($i%2); ?>
            <tr class="<?php echo $bg; ?>">
                <td>
                    <input type="hidden" name="lo_id[<?php echo $i ?>]" value="<?php echo (int)$row['lo_id'] ?>" id="lo_id_<?php echo $i ?>">
                    <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                </td>
                <td><?php echo get_character_name((int)$row['ch_id']) ?></td>
                <td class="txt-left"><?php echo $row['ug_log'] ?></td>
                <td><?php echo get_text($row['ug_per']) ?></td>
                <td><?php echo (int)$row['ug_money'] ?></td>
                <td><?php echo get_item_name((int)$row['ug_item']) ?></td>
                <td><?php echo get_text($row['ug_datetime']) ?></td>
            </tr>
        <?php endfor; ?>

        <?php if ($i === 0): ?>
            <tr><td colspan="<?php echo $colspan ?>" class="empty_table">자료가 없습니다.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
               $page, $total_page,
               $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;page='); ?>

<script>
function f_submit(f){
    if (!is_checked("chk[]")) {
        alert((document.pressed || "작업") + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if (document.pressed === "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) return false;
    }
    return true;
}
</script>

<?php
include_once('./admin.tail.php');
