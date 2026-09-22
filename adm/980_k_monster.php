<?php
$sub_menu = '980401';
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
$token = get_token();


// 현재 선택된 raid_type 필터
$cur_raid_type = ses($_REQUEST, 'raid_type', 'all', 'raw');
if (!isset($raid_types[$cur_raid_type])) {
    $cur_raid_type = 'all';
}

$type_keys = array_keys($raid_types); 
$type_index = array_search($cur_raid_type, $type_keys); 
$sub_menu = "98{$type_index}401";

// raid_type 컨럼 존재 확인 및 추가
$chk_col = sql_fetch("SHOW COLUMNS FROM {$g5['k_monster_table']} LIKE 'raid_type'");
if (!$chk_col) {
    sql_query("ALTER TABLE {$g5['k_monster_table']} ADD `raid_type` VARCHAR(100) NOT NULL DEFAULT 'realtime' COMMENT '레이드타입(CSV)' AFTER `mo_1`");
    sql_query("ALTER TABLE {$g5['k_monster_table']} ADD INDEX `idx_raid_type` (`raid_type`)");
}

// 입력 기본값 방어 및 정렬 필드 제한

$allow_sort = array('mo_id','mo_name','mo_hp','mo_mp');
if (!in_array($sst, $allow_sort, true)) $sst = 'mo_id';
$sod = strtolower($sod) === 'asc' ? 'asc' : 'desc';

$sql_common = " FROM {$g5['k_monster_table']} ";
$sql_search = " WHERE (1) ";

// raid_type 필터 (LIKE 검색)
if ($cur_raid_type !== 'all') {
    $sql_search .= " AND raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}

// 총계
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;

$rows = (isset($config['cf_page_rows']) && (int)$config['cf_page_rows'] > 0) ? (int)$config['cf_page_rows'] : 15;
$total_page  = $total_count ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;
if ($from_record < 0) $from_record = 0;

$sql_order = " ORDER BY {$sst} {$sod} ";

// 목록 조회
$sql = "SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

// 전체목록 링크
$self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
$listall = '<a href="'.$self.'" class="ov_listall">전체목록</a>';

// 능력치 항목
$status = array();
$st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
for ($si = 0; $row = sql_fetch_array($st_result); $si++) {
    $status[] = $row;
}

$g5['title'] = '몬스터 관리 (' . $raid_types[$cur_raid_type] . ')';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan = 7 + count($status);
?>

<style>
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
.raid_chk_group label { margin-right: 10px; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name): ?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<?php
$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">몬스터 목록</a></li>
    <li><a href="#anc_002">몬스터 등록</a></li>
</ul>';
?>
<section id="anc_001">
    <h2 class="h2_frm">몬스터 목록</h2>
    <?php echo $pg_anchor ?>

    <div class="local_ov01 local_ov">
        <?php echo $listall ?>
        전체 <?php echo number_format($total_count) ?> 건
    </div>

    <form method="post" action="./980_k_monster_update.php" onsubmit="return flist_submit(this);" enctype="multipart/form-data">
        <input type="hidden" name="sst" value="<?php echo $sst ?>">
        <input type="hidden" name="sod" value="<?php echo $sod ?>">
        <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
        <input type="hidden" name="stx" value="<?php echo $stx ?>">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">

        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption><?php echo $g5['title']; ?> 목록</caption>
                <colgroup>
                    <col style="width: 50px;" />
                    <col style="width: 50px;" />
                    <col style="width: 120px;"/>
                    <col />
                    <col style="width: 150px;" />
                    <col style="width: 80px;" />
                    <col style="width: 80px;" />
                    <?php for ($ci=0; $ci < count($status); $ci++) { echo '<col style="width: 80px;">'; } ?>
                </colgroup>
                <thead>
                <tr>
                    <th scope="col">
                        <label for="chkall" class="sound_only">몬스터 내역 전체</label>
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col" colspan="2">이미지</th>
                    <th scope="col">몬스터명</th>
                    <th scope="col">레이드타입</th>
                    <th scope="col">hp</th>
                    <th scope="col">mp</th>
                    <?php for ($hi=0; $hi < count($status); $hi++) { echo '<th scope="col">'.get_text($status[$hi]['st_name']).'</th>'; } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                for ($i=0; $row = sql_fetch_array($result); $i++) {
                    $bg = 'bg'.($i % 2);
                ?>
                <tr class="<?php echo $bg; ?>">
                    <td style="text-align: center">
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                        <input type="hidden" name="mo_id[<?php echo $i ?>]" value="<?php echo (int)$row['mo_id'] ?>" />
                    </td>
                    <td style="text-align: center">
                        <?php if (!empty($row['mo_thumb'])) { ?>
                            <img src="<?php echo h($row['mo_thumb']) ?>" alt="<?php echo get_text($row['mo_name']) ?>" style="max-height:50px;">
                            <input type="hidden" name="old_mo_thumb[<?php echo $i ?>]" value="<?php echo h($row['mo_thumb']) ?>" />
                        <?php } ?>
                    </td>
                    <td>
                        <input type="file" name="mo_thumb[<?php echo $i ?>]">
                    </td>
                    <td>
                        <input type="text" name="mo_name[<?php echo $i ?>]" value="<?php echo get_text($row['mo_name']) ?>" class="frm_input" style="width: 98%;">
                        <input type="text" name="mo_1[<?php echo $i ?>]" value="<?php echo h($row['mo_1']) ?>" class="frm_input" style="width: 98%;" placeholder="기타 적용값">
                    </td>
                    <td class="raid_chk_group">
                        <?php 
                        $row_raid_type = ses($row, 'raid_type', 'realtime', 'raw');
                        foreach ($raid_types as $rt_key => $rt_name): 
                            if ($rt_key === 'all') continue;
                            $rt_checked = (strpos($row_raid_type, $rt_key) !== false) ? 'checked' : '';
                        ?>
                        <label><input type="checkbox" name="mo_raid_type[<?php echo $i ?>][]" value="<?php echo h($rt_key); ?>" <?php echo $rt_checked; ?>> <?php echo h($rt_name); ?></label>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <input type="text" name="mo_hp[<?php echo $i ?>]" value="<?php echo h($row['mo_hp']) ?>" class="frm_input" style="width: 98%;">
                    </td>
                    <td>
                        <input type="text" name="mo_mp[<?php echo $i ?>]" value="<?php echo h($row['mo_mp']) ?>" class="frm_input" style="width: 98%;">    
                    </td>
                    <?php
                    for ($k=0; $k < count($status); $k++) {
                        $st_tag = 'st_'.($k+1);
                    ?>
                        <td>
                            <input type="text" name="<?php echo $st_tag ?>[<?php echo $i ?>]" value="<?php echo h(isset($row[$st_tag]) ? $row[$st_tag] : '') ?>" class="frm_input" style="width: 98%;">
                        </td>
                    <?php } ?>
                </tr>
                <?php } ?>
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

    <?php 
    $paging_url = "{$self}?{$qstr}";
    if ($cur_raid_type !== 'all') {
        $paging_url .= '&amp;raid_type=' . urlencode($cur_raid_type);
    }
    echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $paging_url."&amp;page="); 
    ?>
</section>

<section id="anc_002">
    <h2 class="h2_frm">몬스터정보 등록</h2>
    <?php echo $pg_anchor ?>

    <form method="post" action="./980_k_monster_update.php" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
        <input type="hidden" name="stx" value="<?php echo $stx ?>">
        <input type="hidden" name="sst" value="<?php echo $sst ?>">
        <input type="hidden" name="sod" value="<?php echo $sod ?>">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width: 120px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">몬스터명</th>
                    <td>
                        <input type="text" name="mo_name" class="required frm_input" required>
                        <br>
                        <input type="text" name="mo_1" class="frm_input" style="width: 98%;" placeholder="기타 적용값">
                    </td>
                </tr>
                <tr>
                    <th scope="row">레이드타입</th>
                    <td class="raid_chk_group">
                        <?php foreach ($raid_types as $rt_key => $rt_name): 
                            if ($rt_key === 'all') continue;
                            $rt_checked = ($cur_raid_type === 'all' || $cur_raid_type === $rt_key) ? 'checked' : '';
                        ?>
                        <label><input type="checkbox" name="mo_raid_type[]" value="<?php echo h($rt_key); ?>" <?php echo $rt_checked; ?>> <?php echo h($rt_name); ?></label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">이미지</th>
                    <td><input type="file" name="mo_thumb"></td>
                </tr>
                <tr>
                    <th scope="row">hp</th>
                    <td><input type="text" name="mo_hp" value="" class="frm_input"></td>
                </tr>
                <tr>
                    <th scope="row">mp</th>
                    <td><input type="text" name="mo_mp" value="" class="frm_input"></td>
                </tr>
                <?php for ($k=0; $k < count($status); $k++) { $st_tag = 'st_'.($k+1); ?>
                    <tr>
                        <th scope="row"><?php echo get_text($status[$k]['st_name']) ?></th>
                        <td><input type="text" name="<?php echo $st_tag ?>" value="" class="frm_input"></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
        </div>
    </form>
</section>

<script>
function flist_submit(f){
    if(!is_checked("chk[]")){
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }
    if(document.pressed==="선택삭제"){
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) return false;
    }
    return true;
}
</script>

<?php include_once('./admin.tail.php'); ?>
