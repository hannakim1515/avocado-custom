<?php
$sub_menu = "980301";
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
/* 검색 필드 화이트리스트 */
$allowed_sfl = array('it_name','it_content');
if (!in_array($sfl, $allowed_sfl, true)) {
    $sfl = 'it_name';
}

/* 정렬 필드 화이트리스트 */
$allowed_sst = array('it_id','it_name','st_id','ug_limit');
if (!in_array($sst, $allowed_sst, true)) {
    $sst = 'it_id';
}
$sod = strtolower($sod) === 'desc' ? 'desc' : 'asc';

/* 공통 SQL */
$sql_common = " FROM {$g5['item_table']} ";
$sql_search = " WHERE it_type='장비(K)' ";

/* LIKE 검색어 안전화 */
if ($stx !== '') {
    $kw = sql_escape_string($stx);
    $sql_search .= " AND ({$sfl} LIKE '%{$kw}%') ";
}

$sql_order = " ORDER BY {$sst} {$sod} ";

/* 전체 건수 */
$sql = " SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = ses($row, 'cnt', 0, 'int');

/* 페이징 */
$rows = ses($config, 'cf_page_rows', 20, 'int');
if ($rows < 1) $rows = 20;
$total_page  = $rows > 0 ? (int)ceil($total_count / $rows) : 1;
$from_record = ($page - 1) * $rows;

/* 목록 조회 */
$sql = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";
$result = sql_query($sql);

/* 상단 */
$listall = '<a href="'.h($_SERVER['PHP_SELF'], ENT_QUOTES).'" class="ov_listall">전체목록</a>';

$g5['title'] = '장비 관리';
include_once('./admin.head.php');

/* 스탯 목록 */
$st_list = array();
$stat_sql = sql_query("SELECT st_id, st_name FROM {$g5['status_config_table']} ORDER BY st_order ASC");
for ($i=0; $row = sql_fetch_array($stat_sql); $i++) {
    $st_list[] = $row;
}

/* 강화 단계 목록 */
$lv_list = array();
$lv_sql = sql_query("SELECT ug_id, ug_name FROM {$g5['k_upgrade_table']}");
for ($i=0; $row = sql_fetch_array($lv_sql); $i++) {
    $lv_list[] = $row;
}

/* 장비 타입 목록 */
$eq_type_str = ses($kb_cf, 'equip_type', '');
$eq_list = ($eq_type_str !== '') ? explode("|", $eq_type_str) : array();

/* 컬럼 수(헤더와 일치) */
$colspan = 14;

/* 쿼리스트링 */
$qstr = '';
$qstr .= $sfl ? '&amp;sfl='.urlencode($sfl) : '';
$qstr .= $stx ? '&amp;stx='.urlencode($stx) : '';
$qstr .= $sst ? '&amp;sst='.urlencode($sst) : '';
$qstr .= $sod ? '&amp;sod='.urlencode($sod) : '';
$qstr .= $page ? '&amp;page='.$page : '';

/* 관리 링크 보조 */
$one_copy = ''; // 사용하지 않으면 공백으로
?>
<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    추가된 장비 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="it_name"  <?php echo $sfl==='it_name'   ? 'selected' : '' ?>>장비 이름</option>
        <option value="it_content" <?php echo $sfl==='it_content' ? 'selected' : '' ?>>장비 설명</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo h($stx, ENT_QUOTES)?>" id="stx">
    <input type="submit" value="검색" class="btn_submit">
</form>

<?php if (isset($is_admin) && $is_admin === 'super') { ?>
<div class="btn_add01 btn_add">
    <a href="./980_k_equip_form.php" id="bo_add">장비 추가</a>
</div>
<?php } ?>

<form name="fitemlist" id="fitemlist" action="./980_k_equip_list_update.php" onsubmit="return f_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo h($sst, ENT_QUOTES) ?>">
<input type="hidden" name="sod" value="<?php echo h($sod, ENT_QUOTES) ?>">
<input type="hidden" name="sfl" value="<?php echo h($sfl, ENT_QUOTES) ?>">
<input type="hidden" name="stx" value="<?php echo h($stx, ENT_QUOTES) ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <colgroup>
            <col style="width: 40px;" />
            <col style="width: 40px;" />
            <col style="width: 40px;" />
            <col style="width: 60px;" />
            <col style="width: 80px;" />
            <col style="width: 80px;" />
            <col style="width: 80px;" />
            <col />
            <col style="width: 60px;"/>
            <col style="width: 60px;"/>
            <col style="width: 40px;"/>
            <col style="width: 50px;"/>
            <col style="width: 60px;"/>
            <col style="width: 60px;"/>
        </colgroup>
        <thead>
            <tr>
                <th scope="col" class="bo-right">
                    <label for="chkall" class="sound_only">장비 전체</label>
                    <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                </th>
                <th scope="col" colspan="2">기본스탯</th>
                <th scope="col">최대강화</th>
                <th scope="col">강화타입</th>
                <th scope="col">장비타입</th>
                <th scope="col" colspan="2">장비</th>
                <th scope="col">귀속</th>
                <th scope="col">레시피</th>
                <th scope="col" colspan="2">되팔기</th>
                <th scope="col">사용</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $item = sql_fetch_array($result); $i++) {
            $one_update = '<a href="./980_k_equip_form.php?w=u&amp;it_id='.$item['it_id'].'&amp;'.$qstr.'">수정</a>';
            $bg = 'bg'.($i%2);
        ?>
            <tr class="<?php echo $bg; ?>">
                <td>
                    <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($item['it_name']) ?></label>
                    <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                    <input type="hidden" name="it_id[<?php echo $i ?>]" value="<?php echo $item['it_id'] ?>" />
                </td>
                <td>
                    <select name="st_id[<?php echo $i?>]">
                        <?php for($h=0; $h<count($st_list); $h++) { ?>
                            <option value="<?php echo $st_list[$h]['st_id']?>" <?php echo $item['st_id'] == $st_list[$h]['st_id'] ? "selected" : ""?>><?php echo $st_list[$h]['st_name']?></option>
                        <?php } ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="it_value[<?php echo $i?>]" value="<?php echo get_text($item['it_value']) ?>" id="it_value_<?php echo $i ?>" size="8">
                </td>
                <td>
                    <select name="ug_limit[<?php echo $i?>]">
                        <option value="">제한없음</option>
                        <option value="9999" <?php echo ((string)$item['ug_limit']==='9999'?'selected':'')?>>강화불가</option>
                        <?php for($h=0; $h<count($lv_list); $h++) { ?>
                            <option value="<?php echo $lv_list[$h]['ug_id']?>" <?php echo ($item['ug_limit'] == $lv_list[$h]['ug_id'] ? "selected" : "")?>><?php echo $lv_list[$h]['ug_name']?></option>
                        <?php } ?>
                    </select>
                </td>
                <td>
                    <select name="it_2[<?php echo $i?>]">
                        <option value="">일반</option>
                        <option value="9999" <?php echo ($item['it_2'] ? 'selected' : '')?>>커스텀</option>
                    </select>
                </td>
                <td>
                    <select name="eq_type[<?php echo $i?>]">
                        <?php for ($h=0; $h < count($eq_list); $h++) { ?>
                            <option value="<?php echo $eq_list[$h]?>" <?php echo ($item['eq_type']===$eq_list[$h] ? 'selected' : '')?>><?php echo $eq_list[$h]?></option>
                        <?php } ?>
                        <option value="" <?php echo (!$item['eq_type'] ? 'selected' : '')?>>미설정</option>
                    </select>
                </td>
                <td class="txt-center">
                    <?php if (!empty($item['it_img'])) { ?>
                        <img src="<?php echo h($item['it_img'], ENT_QUOTES)?>" style="max-width: 40px;"/>
                    <?php } else { ?>
                        이미지없음
                    <?php } ?>
                </td>
                <td class="txt-left">
                    <input type="text" name="it_name[<?php echo $i ?>]" value="<?php echo get_text($item['it_name']) ?>" id="it_name_<?php echo $i ?>" size="20" style="width:100%;">
                </td>
                <td>
                    <input type="checkbox" name="it_has[<?php echo $i ?>]" value="1" id="it_has_<?php echo $i ?>" <?php echo (!empty($item['it_has']) ? 'checked' : '') ?>>
                </td>
                <td>
                    <input type="checkbox" name="it_use_recepi[<?php echo $i ?>]" value="1" id="it_use_recepi_<?php echo $i ?>" <?php echo (!empty($item['it_use_recepi']) ? 'checked' : '') ?>>
                </td>
                <td>
                    <input type="checkbox" name="it_use_sell[<?php echo $i ?>]" value="1" id="it_use_sell_<?php echo $i ?>" <?php echo (!empty($item['it_use_sell']) ? 'checked' : '') ?>>
                </td>
                <td>
                    <input type="text" name="it_sell[<?php echo $i ?>]" value="<?php echo get_text($item['it_sell']) ?>" id="it_sell_<?php echo $i ?>" size="5">
                </td>
                <td>
                    <input type="checkbox" name="it_use[<?php echo $i ?>]" value="Y" id="it_use_<?php echo $i ?>" <?php echo (!empty($item['it_use']) ? 'checked' : '') ?>>
                </td>
                <td class="td_mngsmall">
                    <?php echo $one_update ?>
                    <?php echo $one_copy ?>
                </td>
            </tr>
        <?php
        }
        if (!isset($i) || $i === 0) {
            echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
    <?php if (isset($is_admin) && $is_admin === 'super') { ?>
    <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
    <?php } ?>
</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, h($_SERVER['PHP_SELF'], ENT_QUOTES).'?'.$qstr.'&amp;page='); ?>

<script>
function f_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if (document.pressed === "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }
    return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
