<?php
$sub_menu = "982100";
include_once './_common.php';
if(!isset($page)||$page<1) $page = 1;

$sql_common = " FROM {$g5['k_realtime_table']} ra ";
$sql_search = " WHERE (1) ";

// 검색 조건
if ($stx !== '') {
    $stx = preg_replace('#[#\';"]#', '', $stx);
    $sql_search .= " AND ( ";
    switch ($sfl) {
        case 'ra_id':
            $sql_search .= " (ra.{$sfl} LIKE '{$stx}%') ";
            break;
        default:
            $sql_search .= " (ra.{$sfl} LIKE '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

// 정렬 기준
if ($sst === '') {
    $sst = 'ra_id';
}
if ($sod === '') {
    $sod = 'asc';
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

$listall      = '<a href="'.h($_SERVER['SCRIPT_NAME']).'" class="ov_listall">전체목록</a>';
$g5['title']  = '실시간 레이드 관리';
include_once './admin.head.php';

$colspan = 12;
?>

<style>
/* 공통 버튼 스타일 */
.btn-link { display: inline-block; padding: 3px 8px; margin: 2px; font-size: 11px; text-decoration: none; border-radius: 3px; cursor: pointer; }
.btn-view { background: #5cb85c; color: #fff!important; }
.btn-view:hover { background: #449d44; color: #fff!important; }
.btn-edit { background: #f0ad4e; color: #fff!important; }
.btn-edit:hover { background: #ec971f; color: #fff!important; }
.btn-reset { background: #d9534f; color: #fff!important; }
.btn-reset:hover { background: #c9302c; color: #fff!important; }
.btn-unit { background: #5bc0de; color: #fff!important; }
.btn-unit:hover { background: #31b0d5; color: #fff!important; }

/* 상태 배지 */
.status-badge { display: inline-block; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin: 1px; }
.status-badge.turn-speed { background: #5cb85c; color: #fff; }
.status-badge.turn-free { background: #f0ad4e; color: #fff; }
.status-badge.turn-turn { background: #5bc0de; color: #fff; }
.status-badge.mo-auto { background: #5cb85c; color: #fff; }
.status-badge.mo-free { background: #9b59b6; color: #fff; }
.status-badge.reload-on { background: #337ab7; color: #fff; }
.status-badge.reload-off { background: #999; color: #fff; }

/* 입력 필드 */
.tbl_head01 select { font-size: 11px; padding: 2px; }
.tbl_head01 input[type="text"] { font-size: 11px; padding: 2px 4px; }
.tbl_head01 .small-input { width: 50px; }
.tbl_head01 .url-input { width: 100%; font-size: 10px; }

/* 이미지/BGM 업로드 행 */
.img-upload-row { display: flex; align-items: center; gap: 5px; margin-top: 3px; font-size: 10px; }
.img-upload-row .img-label { min-width: 30px; color: #666; }
.img-upload-row .img-preview { color: #0066cc; text-decoration: underline; }

/* 참가자 정보 */
.participant-info { font-size: 12px; }
.participant-cnt { font-weight: bold; color: #5cb85c; }
.participant-limit { color: #999; }

/* 보상 정보 */
.reward-info { font-size: 10px; line-height: 1.6; }
.reward-info .reward-item { display: block; color: #666; }
.reward-info .reward-none { color: #999; }

/* 관리 버튼 영역 */
.manage-btns { display: flex; flex-direction: column; gap: 3px; align-items: center; }
</style>

<div class="local_ov01 local_ov">
    <?php echo $listall;?>
    생성된 실시간 레이드 수 <?php echo number_format($total_count); ?>개
    
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="ra_id"<?php echo get_selected($sfl, 'ra_id', true); ?>>TABLE</option>
        <option value="ra_title"<?php echo get_selected($sfl, 'ra_title'); ?>>제목</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo h($stx); ?>" id="stx" required class="required frm_input">
    <input type="submit" value="검색" class="btn_submit">
</form>

<?php if ($is_admin === 'super') { ?>
<div class="btn_add01 btn_add">
    <a href="./982_k_realtime_form.php" id="bo_add">실시간 레이드 추가</a>
</div>
<?php } ?>

<form action="./982_k_realtime_list_update.php" onsubmit="return f_submit(this);" method="post" enctype="multipart/form-data">
    <input type="hidden" name="sst" value="<?php echo h($sst); ?>">
    <input type="hidden" name="sod" value="<?php echo h($sod); ?>">
    <input type="hidden" name="sfl" value="<?php echo h($sfl); ?>">
    <input type="hidden" name="stx" value="<?php echo h($stx); ?>">
    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
    <input type="hidden" name="token" value="<?php echo h($token); ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <colgroup>
			<col style="width: 50px;" />
			<col style="width: 100px;"/>
            <col>
			<col style="width: 100px;"/>
			<col style="width: 100px;"/>
			<col style="width: 100px;"/>
			<col style="width: 100px;"/>
			<col style="width: 70px;"/>
			<col style="width: 150px;"/>
			<col style="width: 100px;"/>
		</colgroup>
        <thead>
        <tr>
            <th scope="col">
                <label for="chkall" class="sound_only">실시간 레이드전체</label>
                <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
            </th>
            <th scope="col">TABLE</th>
            <th scope="col">제목/설명</th>
            <th scope="col">참가자</th>
            <th scope="col">턴타입</th>
            <th scope="col">몬스터</th>
            <th scope="col">새로고침</th>
            <th scope="col">제한시간</th>
            <th scope="col">보상</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i = 0; $row = sql_fetch_array($result); $i++) {

            $ra_id = $row['ra_id'];
            $battle_table = $g5['k_realtime_table'];
            $party = sql_fetch("SELECT COUNT(*) AS cnt FROM {$battle_table}_unit WHERE ra_id = '{$ra_id}' AND unit_type = 'ch'");
            $party_cnt = ses($party, 'cnt', 0, 'int');
            $ra_title = get_text($row['ra_title']);

            $bg = 'bg'.($i % 2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td>
                <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $ra_title; ?></label>
                <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
                <input type="hidden" name="ra_id[<?php echo $i; ?>]" value="<?php echo $ra_id; ?>">
            </td>
            <td>
                <?php echo $ra_id; ?>
            </td>
            <td>
                <input type="text" name="ra_title[<?php echo $i; ?>]" value="<?php echo $ra_title; ?>" id="ra_title_<?php echo $i; ?>" required class="required frm_input url-input" placeholder="제목">
                <input type="text" name="ra_content[<?php echo $i; ?>]" value="<?php echo get_text($row['ra_content']); ?>" class="frm_input url-input" placeholder="설명">
                <div class="img-upload-row">
                    <span class="img-label">목록:</span>
                    <?php if (!empty($row['ra_list_img'])): ?>
                    <a href="<?php echo h($row['ra_list_img']); ?>" target="_blank" class="img-preview">보기</a>
                    <?php endif; ?>
                    <input type="file" name="ra_list_img_file[<?php echo $i; ?>]" accept="image/*" class="frm_input" style="width:100px;font-size:10px;">
                    <input type="text" name="ra_list_img[<?php echo $i; ?>]" value="<?php echo get_text($row['ra_list_img']); ?>" class="frm_input" style="width:100px;font-size:10px;" placeholder="또는 URL">
                </div>
                <div class="img-upload-row">
                    <span class="img-label">배경:</span>
                    <?php if (!empty($row['ra_bg_img'])): ?>
                    <a href="<?php echo h($row['ra_bg_img']); ?>" target="_blank" class="img-preview">보기</a>
                    <?php endif; ?>
                    <input type="file" name="ra_bg_img_file[<?php echo $i; ?>]" accept="image/*" class="frm_input" style="width:100px;font-size:10px;">
                    <input type="text" name="ra_bg_img[<?php echo $i; ?>]" value="<?php echo get_text($row['ra_bg_img']); ?>" class="frm_input" style="width:100px;font-size:10px;" placeholder="또는 URL">
                </div>
                <div class="img-upload-row">
                    <span class="img-label">BGM:</span>
                    <select name="ra_bgm_type[<?php echo $i; ?>]" style="font-size:10px;">
                        <option value="single"<?php echo $row['ra_bgm_type'] === 'single' ? ' selected' : ''; ?>>영상</option>
                        <option value="list"<?php echo $row['ra_bgm_type'] === 'list' ? ' selected' : ''; ?>>리스트</option>
                    </select>
                    <input type="text" name="ra_bgm[<?php echo $i; ?>]" value="<?php echo get_text($row['ra_bgm']); ?>" class="frm_input" style="width:100px;font-size:10px;" placeholder="YouTube ID" title="YouTube 영상/재생목록 ID">
                    <input type="text" name="ra_bgm_volume[<?php echo $i; ?>]" value="<?php echo (int)$row['ra_bgm_volume']; ?>" class="frm_input" style="width:30px;font-size:10px;" placeholder="%" title="볼륨 (0~100)">
                </div>
            </td>
            <td class="txt-center">
                <div class="participant-info">
                    <span class="participant-cnt"><?php echo $party_cnt; ?></span>
                    <span class="participant-limit">/ <input type="text" name="ra_limit[<?php echo $i; ?>]" value="<?php echo (int)$row['ra_limit']; ?>" class="frm_input" style="width:25px;font-size:10px;text-align:center;"></span>
                </div>
                <div style="margin-top:5px;">
                    <span class="btn-link btn-unit" onclick="window.open('./980_k_unit_insert.php?ra_id=<?php echo $row['ra_id']; ?>&amp;raid_type=realtime&amp;type=ch', 'big_viewer', 'width=800,height=800'); return false;">캐릭터</span>
                    <span class="btn-link btn-unit" onclick="window.open('./980_k_unit_insert.php?ra_id=<?php echo $row['ra_id'];; ?>&amp;raid_type=realtime&amp;type=mo', 'big_viewer', 'width=800,height=800'); return false;">몬스터</span>
                </div>
            </td>
            <td class="txt-center">
                <?php
                $turn_class = 'turn-' . $row['ra_turn_type'];
                $turn_labels = array('speed' => '속도순', 'free' => '자유', 'turn' => '턴제');
                $turn_label = isset($turn_labels[$row['ra_turn_type']]) ? $turn_labels[$row['ra_turn_type']] : $row['ra_turn_type'];
                ?>
                <span class="status-badge <?php echo $turn_class; ?>"><?php echo $turn_label; ?></span>
                <input type="hidden" name="ra_turn_type[<?php echo $i; ?>]" value="<?php echo $row['ra_turn_type']; ?>">
            </td>
            <td class="txt-center">
                <?php
                $mo_class = ($row['ra_mo_auto'] === 'auto') ? 'mo-auto' : 'mo-free';
                $mo_label = ($row['ra_mo_auto'] === 'auto') ? '자동' : '수동';
                ?>
                <span class="status-badge <?php echo $mo_class; ?>"><?php echo $mo_label; ?></span>
                <input type="hidden" name="ra_mo_auto[<?php echo $i; ?>]" value="<?php echo $row['ra_mo_auto']; ?>">
            </td>
            <td class="txt-center">
                <?php
                $reload_labels = array('turn' => '턴변경시', 'count' => '모든행동', 'myturn' => '내턴만', 'none' => '없음');
                $reload_label = isset($reload_labels[$row['ra_reload']]) ? $reload_labels[$row['ra_reload']] : $row['ra_reload'];
                $reload_class = ($row['ra_reload'] === 'none') ? 'reload-off' : 'reload-on';
                ?>
                <span class="status-badge <?php echo $reload_class; ?>"><?php echo $reload_label; ?></span>
                <input type="hidden" name="ra_reload[<?php echo $i; ?>]" value="<?php echo $row['ra_reload']; ?>">
                <?php if ($row['ra_reload'] !== 'none'): ?>
                <br><span style="font-size:10px;color:#666;"><?php echo (int)$row['ra_reload_time']; ?>초</span>
                <?php endif; ?>
                <input type="hidden" name="ra_reload_time[<?php echo $i; ?>]" value="<?php echo (int)$row['ra_reload_time']; ?>">
            </td>
            <td class="txt-center">
                <?php if ((int)$row['ra_time_limit'] > 0): ?>
                <span style="font-size:12px;font-weight:bold;"><?php echo (int)$row['ra_time_limit']; ?>초</span>
                <?php else: ?>
                <span style="color:#999;font-size:11px;">없음</span>
                <?php endif; ?>
                <input type="hidden" name="ra_time_limit[<?php echo $i; ?>]" value="<?php echo (int)$row['ra_time_limit']; ?>">
            </td>
            <td class="txt-left">
                <div class="reward-info">
                <?php
                // 보상 정보 표시 (읽기 전용)
                $reward_parts = array();
                
                $r_money = (int)$row['ra_reward_money'];
                $r_exp = (int)$row['ra_reward_exp'];
                $r_item = trim($row['ra_reward_item']);
                $r_title = (int)$row['ra_reward_title'];
                
                if ($r_money > 0) {
                    $reward_parts[] = "<span class='reward-item'>화폐 {$r_money}</span>";
                }
                if ($r_exp > 0) {
                    $reward_parts[] = "<span class='reward-item'>경험치 {$r_exp} EXP</span>";
                }
                if ($r_item !== '') {
                    $item_names = array();
                    $item_ids = explode(',', $r_item);
                    foreach ($item_ids as $it_id) {
                        $it_id = (int)trim($it_id);
                        if ($it_id > 0) {
                            $it_name = function_exists('get_item_name') ? get_item_name($it_id) : "ID:{$it_id}";
                            $item_names[] = $it_name;
                        }
                    }
                    if (count($item_names) > 0) {
                        $reward_parts[] = "<span class='reward-item'>아이템 " . implode(', ', $item_names) . "</span>";
                    }
                }
                if ($r_title > 0) {
                    $ti_name = function_exists('get_title_name') ? get_title_name($r_title) : '';
                    if ($ti_name === '') {
                        $ti_name = "ID:{$r_title}";
                    }
                    $reward_parts[] = "<span class='reward-item'>타이틀 {$ti_name}</span>";
                }
                
                if (count($reward_parts) > 0) {
                    echo implode('', $reward_parts);
                } else {
                    echo '<span class="reward-none">없음</span>';
                }
                ?>
                </div>
            </td>
            <td class="txt-center">
                <div class="manage-btns">
                    <a href="<?php echo G5_URL?>/k_battle/raid.php?ra_id=<?php echo $ra_id; ?>&raid_type=realtime" class="btn-link btn-view"target="_blank">보기</a>
                    <a href="./982_k_realtime_form.php?w=u&amp;ra_id=<?php echo $ra_id; ?>&amp;<?php echo $qstr; ?>" class="btn-link btn-edit">수정</a>
                    <a href="./982_k_realtime_reset.php?ra_id=<?php echo $ra_id; ?>&amp;raid_type=realtime&amp;type=all&amp;<?php echo $qstr; ?>" class="btn-link btn-reset k_realtime_reset">초기화</a>
                </div>
            </td>
        </tr>
        <?php
        }

        if ($i === 0) {
            echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
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

<?php
echo get_paging(
    G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'],
    $page,
    $total_page,
    $_SERVER['SCRIPT_NAME'].'?'.$qstr.'&amp;page='
);
?>

<script>
function f_submit(f) {
    if (!is_checked("chk[]")) {
        alert(document.pressed + " 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if (document.pressed === "선택삭제") {
        if (!confirm("선택한 자료를 정말 삭제하시겠습니까?\n관련된 유닛, 버프, 로그 데이터도 함께 삭제됩니다.")) {
            return false;
        }
    }

    return true;
}

$(function() {
    $(".k_realtime_reset").click(function() {
        return confirm("정말로 초기화하시겠습니까?\n버프, 로그가 삭제되고 유닛 상태가 초기화됩니다.");
    });
});
</script>

<?php
include_once './admin.tail.php';
?>
