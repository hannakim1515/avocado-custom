<?php
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
$sub_menu = '980320';

$token = get_token();

// 입력 기본값 방어
$rows = isset($config['cf_page_rows']) && (int)$config['cf_page_rows'] > 0 ? (int)$config['cf_page_rows'] : 15;

// 목록 총계
$sql_common = " FROM {$g5['k_upgrade_table']} ";
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common}");
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

$total_page  = $total_count ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) { $page = $total_page; }
$from_record = ($page - 1) * $rows;
if ($from_record < 0) { $from_record = 0; }

// 정렬 추가(안정적인 페이징)
$sql = "SELECT * {$sql_common} ORDER BY ug_id ASC LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// 전체목록 링크
$self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
$listall = '<a href="'.$self.'" class="ov_listall">전체목록</a>';

// 랭크 목록
$lv_list = array();
$rank_sql = sql_query("SELECT lv_name, lv_id FROM {$g5['level_table']} ORDER BY lv_exp DESC");
for ($ri = 0; $row = sql_fetch_array($rank_sql); $ri++) {
    $lv_list[] = $row;
}

$g5['title'] = '강화 레벨';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

// 테이블 헤더 수 = 9
$colspan = 9;

// 앵커
$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">강화 레벨 목록</a></li>
    <li><a href="#anc_002">강화 레벨 등록</a></li>
</ul>';
?>
<style>
    @import url('<?php echo G5_URL?>/k_battle/css/custom.css');
    @import url('<?php echo G5_URL?>/k_battle/css/equip.css');
    .equip-img{
        position:relative;
        margin:2px auto;
        width:44px;
        height:44px;
        background:var(--action-select-background);
        border-radius:var(--action-select-border-radius);
        border:var(--action-select-border);
    }
    <?php include G5_PATH.'/k_battle/css/equip_custom.php'; ?>
</style>

<section id="anc_001">
    <h2 class="h2_frm">강화 레벨 목록</h2>
    <?php echo $pg_anchor ?>

    <div class="local_ov01 local_ov">
        <?php echo $listall ?>
        전체 <?php echo number_format($total_count) ?> 건
    </div>

    <form name="fpointlist" id="fpointlist" method="post" action="./980_k_equip_upgrade_update.php" onsubmit="return f_submit(this);" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">

        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption><?php echo $g5['title']; ?> 목록</caption>
                <colgroup>
                    <col style="width: 50px;" />
                    <col style="width: 100px;"/>
                    <col style="width: 100px;"/>
                    <col style="width: 100px;"/>
                    <col style="width: 100px;"/>
                    <col style="width: 130px;"/>
                    <col style="width: 130px;"/>
                    <col>
                    <col style="width: 150px;"/>
                </colgroup>
                <thead>
                <tr>
                    <th scope="col">
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">레벨</th>
                    <th scope="col">표기</th>
                    <th scope="col">랭크제한</th>
                    <th scope="col">증가수치</th>
                    <th scope="col">확률(%)</th>
                    <th scope="col">소모화폐</th>
                    <th scope="col">소모아이템</th>
                    <th scope="col">색상표기</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0; // 루프 전 초기화
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    $bg = 'bg'.($i % 2);
                ?>
                <tr class="<?php echo $bg; ?>">
                    <td style="text-align: center">
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                    </td>
                    <td>
                        <input type="hidden" name="ug_id[<?php echo $i ?>]" value="<?php echo (int)$row['ug_id'] ?>" id="ug_id_<?php echo $i ?>">
                        <?php echo (int)$row['ug_id'] ?>
                    </td>
                    <td>
                        <input type="text" name="ug_name[<?php echo $i ?>]" value="<?php echo get_text($row['ug_name']) ?>" class="frm_input" style="width: 98%;">
                    </td>
                    <td>
                        <select name="ch_rank[<?php echo $i ?>]">
                            <option value="">제한없음</option>
                            <?php for ($h = 0; $h < count($lv_list); $h++) { ?>
                                <option value="<?php echo (int)$lv_list[$h]['lv_id'] ?>" <?php if ((string)$row['ch_rank'] === (string)$lv_list[$h]['lv_id']) { echo 'selected'; } ?>>
                                    <?php echo get_text($lv_list[$h]['lv_name']) ?> 이상
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="ug_min[<?php echo $i ?>]" value="<?php echo h($row['ug_min']) ?>" class="frm_input" style="width: 50px;"> ~
                        <input type="text" name="ug_max[<?php echo $i ?>]" value="<?php echo h($row['ug_max']) ?>" class="frm_input" style="width: 50px;">
                    </td>
                    <td>
                        <input type="text" name="ug_per[<?php echo $i ?>]" value="<?php echo h($row['ug_per']) ?>" class="frm_input" style="width: 70%;">
                    </td>
                    <td>
                        사용 <input type="checkbox" name="ug_use_money[<?php echo $i ?>]" value="1" <?php if (!empty($row['ug_use_money'])) { echo 'checked'; } ?>>
                        <br><input type="text" name="ug_money[<?php echo $i ?>]" value="<?php echo h($row['ug_money']) ?>" class="frm_input">
                    </td>
                    <td style="text-align:left">
                        사용 <input type="checkbox" name="ug_use_it[<?php echo $i ?>]" value="1" <?php if (!empty($row['ug_use_it'])) { echo 'checked'; } ?>>
                        <br>
                        <div>
                            <input type="text" name="ug_it_name[<?php echo $i ?>]" id="ug_it_name_<?php echo $i ?>" class="frm_input" value="<?php echo get_text(get_item_name($row['ug_item'])) ?>" placeholder="아이템" onkeyup="get_ajax_item(this, 'ug_it_list_<?php echo $i ?>', 'ug_item_<?php echo $i ?>');">
                            <input type="hidden" name="ug_item[<?php echo $i ?>]" id="ug_item_<?php echo $i ?>" value="<?php echo h($row['ug_item']) ?>" />
                            <div id="ug_it_list_<?php echo $i ?>" class="ajax-list-box"><div class="list"></div></div>
                        </div>
                    </td>
                    <td style="text-align:left">
                        <p><div class="equip-img"><span class="eq_lv lv_<?php echo (int)$row['ug_id'] ?>"><?php echo get_text($row['ug_name']) ?></span></div></p>
                        <p style="display:flex;">글자 <input type="text" name="ug_color[<?php echo $i ?>]" value="<?php echo h($row['ug_color']) ?>"></p>
                        <p style="display:flex;">태그 <input type="text" name="ug_tag_color[<?php echo $i ?>]" class="frm_input" value="<?php echo h($row['ug_tag_color']) ?>"></p>
                        <p style="display:flex;">배경 <input type="text" name="ug_bg_color[<?php echo $i ?>]" class="frm_input" value="<?php echo h($row['ug_bg_color']) ?>"></p>
                    </td>
                </tr>
                <?php } // for ?>
                <?php
                if ($i === 0) {
                    echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>

        <div class="btn_list01 btn_list">
            <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
            <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
        </div>
    </form>

    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$self}?{$qstr}&amp;page="); ?>
</section>

<section id="anc_002">
    <h2 class="h2_frm">강화 레벨 등록</h2>
    <?php echo $pg_anchor ?>

    <form name="fpointlist2" method="post" id="fpointlist2" action="./980_k_equip_upgrade_update.php" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width: 130px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">레벨 표기</th>
                    <td><input type="text" name="ug_name" id="ug_name" class="required frm_input" required></td>
                </tr>
                <tr>
                    <th scope="row">랭크제한</th>
                    <td>
                        <select name="ch_rank">
                            <option value="">제한없음</option>
                            <?php for ($h = 0; $h < count($lv_list); $h++) { ?>
                                <option value="<?php echo (int)$lv_list[$h]['lv_id'] ?>"><?php echo get_text($lv_list[$h]['lv_name']) ?> 이상</option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">증가수치(범위중 랜덤)</th>
                    <td>
                        <input type="text" name="ug_min" value="" class="required frm_input" style="width: 50px;"> ~
                        <input type="text" name="ug_max" value="" class="required frm_input" style="width: 50px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row">성공확률(%)</th>
                    <td>
                        <input type="text" name="ug_per" value="" class="required frm_input" style="width: 50px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row">소모화폐</th>
                    <td>
                        사용 <input type="checkbox" name="ug_use_money" value="1">
                        <br><input type="text" name="ug_money" value="" class="frm_input">
                    </td>
                </tr>
                <tr>
                    <th scope="row">소모아이템</th>
                    <td>
                        사용 <input type="checkbox" name="ug_use_it" value="1">
                        <br>
                        <div>
                            <input type="text" name="ug_it_name" id="ug_it_name" class="frm_input" value="" placeholder="아이템" onkeyup="get_ajax_item(this, 'ug_it_list', 'ug_item');">
                            <input type="hidden" name="ug_item" id="ug_item" value="" />
                            <div id="ug_it_list" class="ajax-list-box"><div class="list"></div></div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" value="등록" class="btn_submit">
        </div>
    </form>
</section>

<script>
function f_submit(f){
    if(!is_checked("chk[]")){
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if(document.pressed==="선택삭제"){
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")){ return false; }
    }
    return true;
}
</script>

<?php include_once('./admin.tail.php'); ?>
