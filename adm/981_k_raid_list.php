<?php
include_once './_common.php';
if(!isset($page)||$page<1) $page = 1;
// raid_type 파라미터
$cur_raid_type = ses($_REQUEST, 'raid_type', 'all', 'raw');
if (!isset($raid_types[$cur_raid_type])||$cur_raid_type=='all') {
    $cur_raid_type = 'all';
    $sub_menu = "980500";
}else{
    $type_keys = array_keys($raid_types); 
    $type_index = array_search($cur_raid_type, $type_keys); 
    $sub_menu = "98{$type_index}110";
}


// 레이드 리스트 테이블 체크 및 생성
$chk = sql_fetch("SHOW TABLES LIKE '{$g5['k_raid_list']}'", false);
if (!$chk) {
    $sql_create = "
    CREATE TABLE IF NOT EXISTS `{$g5['k_raid_list']}` (
        `li_id` INT(11) NOT NULL AUTO_INCREMENT,
        `li_title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '리스트 제목',
        `li_use` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '사용여부 (1=사용, 0=미사용)',
        `raid_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '레이드 타입 (mmbraid, realtime 등)',
        `ra_ids` TEXT NOT NULL COMMENT '레이드 ID 목록 (CSV)',
        PRIMARY KEY (`li_id`),
        KEY `idx_raid_type` (`raid_type`),
        KEY `idx_li_use` (`li_use`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='레이드 리스트 관리';
    ";
    sql_query($sql_create, false);
}

$sql_common = " FROM {$g5['k_raid_list']} ";
if ($cur_raid_type === 'all') {
    $sql_search = " WHERE (1) ";
} else {
    $sql_search = " WHERE raid_type = '".sql_escape_string($cur_raid_type)."' ";
}

// 검색 조건
if ($stx !== '') {
    $stx = preg_replace('#[#\';"]#', '', $stx);
    $sql_search .= " AND (li_title LIKE '%{$stx}%') ";
}

// 정렬 기준
if ($sst === '') {
    $sst = 'li_id';
}
if ($sod === '') {
    $sod = 'desc';
}
$sql_order = " ORDER BY {$sst} {$sod} ";

// 전체 개수
$sql = " SELECT COUNT(*) AS cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = ses($row, 'cnt', 0, 'int');

$rows = ses($config, 'cf_page_rows', 20, 'int');
if ($rows < 1) $rows = 20;
$total_page  = $total_count > 0 ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) {
    $page = $total_page;
}
$from_record = ($page - 1) * $rows;

// 목록 조회
$sql    = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";
$result = sql_query($sql);

// 레이드 목록 (체크박스용) - 전체일 때는 빈 배열
$raid_options = array();
if ($cur_raid_type !== 'all') {
    if ($cur_raid_type === 'realtime') {
        $ra_result = sql_query("SELECT ra_id, ra_title FROM {$g5['k_realtime_table']} ORDER BY ra_id ASC");
    } else {
        // mmbraid는 게시판 테이블에서 조회 (bo_1_subj = 'mmbraid')
        $ra_result = sql_query("SELECT bo_table AS ra_id, bo_subject AS ra_title FROM {$g5['board_table']} WHERE bo_1_subj = 'mmbraid' ORDER BY bo_table ASC");
    }
    while ($ra_row = sql_fetch_array($ra_result)) {
        $raid_options[$ra_row['ra_id']] = $ra_row['ra_title'];
    }
}

$listall      = '<a href="'.h($_SERVER['SCRIPT_NAME']).'?raid_type='.h($cur_raid_type).'" class="ov_listall">전체목록</a>';
$g5['title']  = '레이드 리스트 관리';
include_once './admin.head.php';
?>

<style>
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
.raid_checkbox_wrap { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa; }
.raid_checkbox_wrap label { display: block; margin-bottom: 5px; cursor: pointer; }
.raid_checkbox_wrap label:hover { background: #e3f2fd; }
.tbl_head01 input[type="text"] { font-size: 12px; padding: 4px 6px; }
.td_use { text-align: center; }
.td_use .use-on { color: #2196F3; font-weight: bold; }
.td_use .use-off { color: #999; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name): ?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?><?php echo $stx !== '' ? '&amp;stx='.urlencode($stx) : ''; ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    리스트 <?php echo number_format($total_count); ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type); ?>">
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo h($stx); ?>" id="stx" required class="required frm_input" placeholder="리스트 제목 검색">
    <input type="submit" value="검색" class="btn_submit">
</form>

<form action="./981_k_raid_list_update.php" onsubmit="return f_submit(this);" method="post">
    <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type); ?>">
    <input type="hidden" name="sst" value="<?php echo h($sst); ?>">
    <input type="hidden" name="sod" value="<?php echo h($sod); ?>">
    <input type="hidden" name="sfl" value="<?php echo h($sfl); ?>">
    <input type="hidden" name="stx" value="<?php echo h($stx); ?>">
    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
    <input type="hidden" name="token" value="<?php echo h($token); ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php echo $g5['title']; ?> (<?php echo h($raid_types[$cur_raid_type]); ?>) 목록</caption>
        <thead>
        <tr>
            <th scope="col" style="width:50px;">
                <label for="chkall" class="sound_only">리스트전체</label>
                <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
            </th>
            <th scope="col" style="width:60px;">ID</th>
            <th scope="col" style="width:200px;">리스트 제목</th>
            <th scope="col" style="width:100px;">타입</th>
            <th scope="col">레이드 선택</th>
            <th scope="col" style="width:80px;">사용</th>
            <th scope="col" style="width:80px;">바로가기</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            $li_id = $row['li_id'];
            $li_title = get_text($row['li_title']);
            $li_use = (int)$row['li_use'];
            $row_raid_type = isset($row['raid_type']) ? $row['raid_type'] : 'realtime';
            $ra_ids_arr = !empty($row['ra_ids']) ? explode(',', $row['ra_ids']) : array();
            
            // 해당 raid_type의 레이드 옵션 조회
            $row_raid_options = array();
            if ($row_raid_type === 'realtime') {
                $ra_tmp = sql_query("SELECT ra_id, ra_title FROM {$g5['k_realtime_table']} ORDER BY ra_id ASC");
            } else {
                $ra_tmp = sql_query("SELECT bo_table AS ra_id, bo_subject AS ra_title FROM {$g5['board_table']} WHERE bo_1_subj = 'mmbraid' ORDER BY bo_table ASC");
            }
            while ($ra_row = sql_fetch_array($ra_tmp)) {
                $row_raid_options[$ra_row['ra_id']] = $ra_row['ra_title'];
            }

            $bg = 'bg'.($i % 2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td>
                <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $li_title; ?></label>
                <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
                <input type="hidden" name="li_id[<?php echo $i; ?>]" value="<?php echo $li_id; ?>">
            </td>
            <td><?php echo $li_id; ?></td>
            <td>
                <input type="text" name="li_title[<?php echo $i; ?>]" value="<?php echo $li_title; ?>" class="frm_input" style="width:100%;" required>
            </td>
            <td>
                <select name="li_raid_type[<?php echo $i; ?>]" class="frm_input" style="width:100%;">
                    <?php foreach ($raid_types as $rt_key => $rt_name): 
                        if ($rt_key === 'all') continue; // '전체'는 선택 불가
                    ?>
                    <option value="<?php echo h($rt_key); ?>"<?php echo $row_raid_type === $rt_key ? ' selected' : ''; ?>><?php echo h($rt_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <div class="raid_checkbox_wrap">
                    <?php foreach ($row_raid_options as $ra_id => $ra_title): ?>
                    <label>
                        <input type="checkbox" name="ra_ids[<?php echo $i; ?>][]" value="<?php echo h($ra_id); ?>" <?php echo in_array((string)$ra_id, $ra_ids_arr) ? 'checked' : ''; ?>>
                        [<?php echo h($ra_id); ?>] <?php echo h($ra_title); ?>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($row_raid_options)): ?>
                    <p style="color:#999;">등록된 레이드가 없습니다.</p>
                    <?php endif; ?>
                </div>
            </td>
            <td class="td_use">
                <select name="li_use[<?php echo $i; ?>]">
                    <option value="1"<?php echo $li_use === 1 ? ' selected' : ''; ?>>사용</option>
                    <option value="0"<?php echo $li_use === 0 ? ' selected' : ''; ?>>미사용</option>
                </select>
            </td>
            <td style="text-align:center;">
                <a href="<?php echo G5_URL; ?>/k_battle/list.php?li_id=<?php echo $li_id; ?>&amp;raid_type=<?php echo h($row_raid_type); ?>" target="_blank" class="btn_admin small">보기</a>
            </td>
        </tr>
        <?php
        }

        if ($i === 0) {
            echo '<tr><td colspan="7" class="empty_table">등록된 리스트가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
        </table>
    </div>

    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
        <?php if ($is_admin === 'super') { ?>
        <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
        <?php } ?>
    </div>
</form>

<!-- 신규 추가 폼 -->
<form action="./981_k_raid_list_update.php" onsubmit="return f_submit_new(this);" method="post">
    <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type); ?>">
    <input type="hidden" name="sst" value="<?php echo h($sst); ?>">
    <input type="hidden" name="sod" value="<?php echo h($sod); ?>">
    <input type="hidden" name="sfl" value="<?php echo h($sfl); ?>">
    <input type="hidden" name="stx" value="<?php echo h($stx); ?>">
    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
    <input type="hidden" name="token" value="<?php echo h($token); ?>">
    <input type="hidden" name="act_button" value="신규추가">

    <div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
        <table>
        <caption>리스트 신규 추가</caption>
        <thead>
        <tr>
            <th scope="col" style="width:60px;">구분</th>
            <th scope="col" style="width:200px;">리스트 제목</th>
            <th scope="col" style="width:100px;">타입</th>
            <th scope="col">레이드 선택</th>
            <th scope="col" style="width:80px;">사용</th>
        </tr>
        </thead>
        <tbody>
        <tr style="background:#e8f5e9;">
            <td style="text-align:center;"><span style="color:#4CAF50;font-weight:bold;">신규</span></td>
            <td>
                <input type="text" name="new_li_title" value="" class="frm_input" style="width:100%;" placeholder="리스트 제목 입력">
            </td>
            <td>
                <select name="new_li_raid_type" id="new_li_raid_type" class="frm_input" style="width:100%;" onchange="updateNewRaidOptions()">
                    <?php foreach ($raid_types as $rt_key => $rt_name): 
                        if ($rt_key === 'all') continue;
                        $default_type = ($cur_raid_type !== 'all') ? $cur_raid_type : 'realtime';
                    ?>
                    <option value="<?php echo h($rt_key); ?>"<?php echo $default_type === $rt_key ? ' selected' : ''; ?>><?php echo h($rt_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <div class="raid_checkbox_wrap" id="new_raid_options">
                    <p style="color:#999;">타입을 선택하면 레이드 목록이 표시됩니다.</p>
                </div>
            </td>
            <td class="td_use">
                <select name="new_li_use">
                    <option value="1">사용</option>
                    <option value="0">미사용</option>
                </select>
            </td>
        </tr>
        </tbody>
        </table>
    </div>

    <div class="btn_list01 btn_list">
        <input type="submit" value="신규추가" class="btn_submit">
    </div>
</form>

<?php
echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    $_SERVER['SCRIPT_NAME'].'?raid_type='.urlencode($cur_raid_type).'&amp;'.$qstr.'&amp;page='
);
?>

<?php
// 레이드 옵션 데이터 (JavaScript용)
$js_raid_options = array();
// realtime
$ra_tmp = sql_query("SELECT ra_id, ra_title FROM {$g5['k_realtime_table']} ORDER BY ra_id ASC");
$js_raid_options['realtime'] = array();
while ($ra_row = sql_fetch_array($ra_tmp)) {
    $js_raid_options['realtime'][] = array('id' => $ra_row['ra_id'], 'title' => $ra_row['ra_title']);
}
// mmbraid
$ra_tmp = sql_query("SELECT bo_table AS ra_id, bo_subject AS ra_title FROM {$g5['board_table']} WHERE bo_1_subj = 'mmbraid' ORDER BY bo_table ASC");
$js_raid_options['mmbraid'] = array();
while ($ra_row = sql_fetch_array($ra_tmp)) {
    $js_raid_options['mmbraid'][] = array('id' => $ra_row['ra_id'], 'title' => $ra_row['ra_title']);
}
?>

<script>
var raidOptions = <?php echo json_encode($js_raid_options); ?>;

function updateNewRaidOptions() {
    var type = document.getElementById('new_li_raid_type').value;
    var container = document.getElementById('new_raid_options');
    var options = raidOptions[type] || [];
    
    if (options.length === 0) {
        container.innerHTML = '<p style="color:#999;">등록된 레이드가 없습니다.</p>';
        return;
    }
    
    var html = '';
    for (var i = 0; i < options.length; i++) {
        html += '<label><input type="checkbox" name="new_ra_ids[]" value="' + options[i].id + '"> [' + options[i].id + '] ' + options[i].title + '</label>';
    }
    container.innerHTML = html;
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    updateNewRaidOptions();
});

function f_submit(f) {
    if (document.pressed === "선택삭제") {
        if (!is_checked("chk[]")) {
            alert("삭제하실 항목을 하나 이상 선택하세요.");
            return false;
        }
        if (!confirm("선택한 리스트를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }
    if (document.pressed === "선택수정") {
        if (!is_checked("chk[]")) {
            alert("수정하실 항목을 하나 이상 선택하세요.");
            return false;
        }
    }
    return true;
}

function f_submit_new(f) {
    if (f.new_li_title.value.trim() === '') {
        alert("리스트 제목을 입력하세요.");
        f.new_li_title.focus();
        return false;
    }
    return true;
}
</script>

<?php
include_once './admin.tail.php';
?>
