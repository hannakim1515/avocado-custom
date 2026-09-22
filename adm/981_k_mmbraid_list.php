<?php
$sub_menu = "981100";
include_once './_common.php';
if(!isset($page)||$page<1) $page = 1;
$token = get_token();

// mmbraid용 battle_table 접두사
$battle_table = $g5['board_table'];

$sql_common = " FROM {$g5['board_table']} ";
$sql_search = " WHERE bo_1_subj = 'mmbraid' ";

// 검색 조건
if ($stx !== '') {
    $stx_esc = sql_escape_string($stx);
    switch ($sfl) {
        case 'bo_table':
            $sql_search .= " AND bo_table LIKE '{$stx_esc}%' ";
            break;
        case 'bo_subject':
            $sql_search .= " AND bo_subject LIKE '%{$stx_esc}%' ";
            break;
        default:
            $sql_search .= " AND (bo_table LIKE '%{$stx_esc}%' OR bo_subject LIKE '%{$stx_esc}%') ";
            break;
    }
}

// 정렬 기준
$allow_sort = array('bo_table', 'bo_subject', 'bo_count_write');
if (!in_array($sst, $allow_sort, true)) {
    $sst = 'bo_table';
}
$sod = strtolower($sod) === 'desc' ? 'desc' : 'asc';
$sql_order = " ORDER BY {$sst} {$sod} ";

// 전체 개수
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = ses($row, 'cnt', 0, 'int');

$rows = ses($config, 'cf_page_rows', 20, 'int');
if ($rows < 1) $rows = 20;
$total_page = $total_count > 0 ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) {
    $page = $total_page;
}
$from_record = ($page - 1) * $rows;

// 목록 조회
$sql = "SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);

$listall = '<a href="'.h($_SERVER['SCRIPT_NAME']).'" class="ov_listall">전체목록</a>';
$g5['title'] = 'MMB 레이드 관리';
include_once './admin.head.php';
include_once './990_unified_menu_bootstrap.php';

?>

<style>
.btn-link { display: inline-block; padding: 3px 8px; margin: 2px; font-size: 11px; text-decoration: none; border-radius: 3px; }
.btn-admin { background: #5bc0de; color: #fff!important; }
.btn-admin:hover { background: #31b0d5; color: #fff!important; }
.btn-view { background: #5cb85c; color: #fff!important; }
.btn-view:hover { background: #449d44; color: #fff!important; }
.btn-edit { background: #f0ad4e; color: #fff!important; }
.btn-edit:hover { background: #ec971f; color: #fff!important; }
.btn-skin { background: #9b59b6; color: #fff!important; }
.btn-skin:hover { background: #8e44ad; color: #fff!important; }
.bo-subj-list { font-size: 11px; color: #666; margin-top: 3px; }
.bo-subj-list span { display: inline-block; margin-right: 10px; }
.mo-info { font-size: 12px; }
.mo-info .mo-name { font-weight: bold; color: #333; }
.participant-cnt { font-size: 12px; color: #5cb85c; font-weight: bold; }
.raid-status { font-size: 11px; padding: 2px 6px; border-radius: 3px; }
.raid-status.active { background: #5cb85c; color: #fff; }
.raid-status.inactive { background: #999; color: #fff; }
.opt-badge { display: inline-block; font-size: 10px; padding: 1px 5px; border-radius: 3px; margin: 1px; }
.opt-badge.on { background: #5cb85c; color: #fff; }
.opt-badge.off { background: #d9534f; color: #fff; }
.opt-badge.mode { background: #5bc0de; color: #fff; }
.raid-opts { font-size: 11px; line-height: 1.8; }
.join-status { font-size: 11px; padding: 2px 6px; border-radius: 3px; }
.join-status.open { background: #5cb85c; color: #fff; }
.join-status.closed { background: #d9534f; color: #fff; }

</style>

<div class="local_ov01 local_ov">
    <?php echo $listall; ?>
    MMB 레이드 게시판 <?php echo number_format($total_count); ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="bo_table" <?php if ($sfl === 'bo_table') echo 'selected'; ?>>게시판 ID</option>
        <option value="bo_subject" <?php if ($sfl === 'bo_subject') echo 'selected'; ?>>게시판명</option>
    </select>
    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" id="stx" value="<?php echo h($stx); ?>" class="frm_input">
    <input type="submit" value="검색" class="btn_submit">
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <colgroup>
            <col style="width: 100px;">
            <col style="width: 120px;">
            <col style="width: 120px;">
            <col style="width: 120px;">
            <col style="width: 120px;">
            <col>
            <col style="width: 120px;">
            <col>
        </colgroup>
        <thead>
        <tr>
            <th scope="col">게시판 ID</th>
            <th scope="col">게시판명</th>
            <th scope="col">상태</th>
            <th scope="col">참여</th>
            <th scope="col">레이드옵션</th>
            <th scope="col">몬스터</th>
            <th scope="col">참가자</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 0;
        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            $bg = 'bg' . ($i % 2);
            $bo_table = $row['bo_table'];
            
            // 레이드 상태 (bo_1 = 'true'면 활성)
            $raid_active = (ses($row, 'bo_1', '') === 'true');
            
            
            // 몬스터 정보 조회
            $mo_info = array();
            $chk_mo_table = sql_fetch("SHOW TABLES LIKE '{$battle_table}_unit'");
            if ($chk_mo_table) {
                $mo_row = sql_fetch("SELECT rm_id, hp_now, hp_max, unit_id FROM {$battle_table}_unit WHERE unit_type = 'mo' and ra_id = '{$bo_table}' LIMIT 1");
                if (!empty($mo_row['rm_id'])) {
                    $mo_id = ses($mo_row, 'unit_id', 0, 'int');
                    $mo_data = sql_fetch("SELECT mo_name, mo_thumb FROM {$g5['k_monster_table']} WHERE mo_id = '{$mo_id}'");
                    $mo_info = array(
                        'name' => ses($mo_data, 'mo_name', ''),
                        'thumb' => ses($mo_data, 'mo_thumb', ''),
                        'hp_now' => ses($mo_row, 'hp_now', 0, 'int'),
                        'hp_max' => ses($mo_row, 'hp_max', 0, 'int'),
                    );
                }
            }
            
            // 참가자 수 조회
            $participant_cnt = 0;
            if ($chk_mo_table) {
                $cnt_row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$battle_table}_unit WHERE unit_type = 'ch' and ra_id = '{$bo_table}'");
                $participant_cnt = ses($cnt_row, 'cnt', 0, 'int');
            }
            
        ?>
        <tr class="<?php echo $bg; ?>">
            <td style="text-align: center;">
                <strong><?php echo h($bo_table); ?></strong>
            </td>
            <td>
                <?php echo get_text($row['bo_subject']); ?>
            </td>
            <td style="text-align: center;">
                <?php if ($raid_active): ?>
                    <span class="raid-status active">사용중</span>
                <?php else: ?>
                    <span class="raid-status inactive">미사용</span>
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <?php
                // 참여마감 여부 (bo_2_subj = 'true'면 참여가능, 'false'면 마감)
                $join_open = (ses($row, 'bo_2_subj', 'true') === 'true');
                ?>
                <?php if ($join_open): ?>
                    <span class="join-status open">가능</span>
                <?php else: ?>
                    <span class="join-status closed">마감</span>
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <?php
                // 레이드 방식 (bo_10_subj)
                $raid_mode = ses($row, 'bo_10_subj', 'all', 'raw');
                $raid_mode_text = array(
                    'all' => '턴별 행동제한',
                    'skill' => '턴별 스킬제한',
                    'no' => '턴별 제한없음'
                );
                $raid_mode_label = isset($raid_mode_text[$raid_mode]) ? $raid_mode_text[$raid_mode] : $raid_mode;
                
                // 몬스터 반격 (bo_7)
                $counter_active = (ses($row, 'bo_7', '') === 'true');
                
                // 아이템 사용 (bo_6)
                $item_active = (ses($row, 'bo_6', '') === 'true');
                ?>
                <div class="raid-opts">
                    <span class="opt-badge mode"><?php echo h($raid_mode_label); ?></span><br>
                    <span class="opt-badge <?php echo $counter_active ? 'on' : 'off'; ?>">반격<?php echo $counter_active ? 'O' : 'X'; ?></span>
                    <span class="opt-badge <?php echo $item_active ? 'on' : 'off'; ?>">아이템<?php echo $item_active ? 'O' : 'X'; ?></span>
                </div>
            </td>
            <td>
                <?php if (!empty($mo_info['name'])): ?>
                    <div class="mo-info">
                        <span class="mo-name"><?php echo h($mo_info['name']); ?></span><br>
                        <span class="mo-hp"><?php echo number_format($mo_info['hp_now']); ?> / <?php echo number_format($mo_info['hp_max']); ?></span>
                    </div>
                <?php else: ?>
                    <span style="color:#999;">-</span>
                <?php endif; ?>
            </td>
            <td style="text-align: center;">
                <span class="participant-cnt"><?php echo number_format($participant_cnt); ?>명</span>
                <span class="btn-link btn-view" onclick='window.open("<?php echo G5_URL; ?>/k_battle/unit.php?bo_table=<?php echo h($bo_table); ?>&type=ch","raid_unit","width=500,height=800");'>일람</span>
            </td>
            <td style="text-align: center;">
                <a href="<?php echo G5_URL; ?>/skin/board/k_mmbraid/k_mmbraid/admin.php?bo_table=<?php echo urlencode($bo_table); ?>" class="btn-link btn-skin" title="스킨 관리" target="_blank">레이드관리</a>
                <a href="./board_form.php?w=u&bo_table=<?php echo urlencode($bo_table); ?>" class="btn-link btn-admin" title="게시판 관리" target="_blank">게시판관리</a>
                <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo urlencode($bo_table); ?>" class="btn-link btn-view" title="게시판 보기" target="_blank">보기</a>
            </td>
        </tr>
        <?php } ?>
        <?php if ($i === 0) { ?>
            <tr><td colspan="9" class="empty_table">MMB 레이드 게시판이 없습니다.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>


<?php
$paging_url = $_SERVER['SCRIPT_NAME'] . '?' . $qstr;
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $paging_url . '&amp;page=');
?>


<?php include_once './admin.tail.php'; ?>
