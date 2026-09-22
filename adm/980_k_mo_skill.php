<?php
$sub_menu = '980411';
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
$token = get_token();

// 현재 선택된 raid_type 필터
$cur_raid_type = ses($_REQUEST, 'raid_type', 'all', 'raw');
if (!isset($raid_types[$cur_raid_type])) {
    $cur_raid_type = 'all';
}

// 동적 sub_menu 설정
$type_keys = array_keys($raid_types);
$type_index = array_search($cur_raid_type, $type_keys);
$sub_menu = "98{$type_index}411";

// 몬스터 스킬 테이블 존재 확인 및 생성
$chk_table = sql_fetch("SHOW TABLES LIKE '{$g5['k_mo_skill_table']}'");
if (!$chk_table) {
    $create_sql = "
        CREATE TABLE IF NOT EXISTS `{$g5['k_mo_skill_table']}` (
          `cs_id` int(11) NOT NULL AUTO_INCREMENT,
          `mo_id` int(11) NOT NULL COMMENT '몬스터 ID',
          `sk_id` int(11) NOT NULL COMMENT '스킬 ID',
          `cs_use` int(11) NOT NULL DEFAULT 1 COMMENT '사용여부',
          `cs_name` varchar(255) NOT NULL COMMENT '커스텀 스킬명',
          `cs_content` text NOT NULL COMMENT '커스텀 스킬 설명',
          `cs_icon` varchar(255) NOT NULL COMMENT '커스텀 아이콘 URL',
          `cs_img` text NOT NULL COMMENT '스킬컷 이미지',
          `cs_target_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '타겟 명수 (0=기본)',
          PRIMARY KEY (`cs_id`),
          KEY `idx_mo_id` (`mo_id`),
          KEY `idx_sk_id` (`sk_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ";
    sql_query($create_sql);
}


// 정렬·검색 허용 컬럼 화이트리스트
$allow_sort = array('cs_id', 'mo_id', 'sk_id', 'cs_use');
if (!in_array($sst, $allow_sort, true) || $sst === '') $sst = 'cs_id';
if ($sod === '' || strtolower($sod) !== 'asc') $sod = 'desc';

$allow_search = array('mo_name', 'sk_name', 'cs_name');
if (!in_array($sfl, $allow_search, true)) $sfl = 'mo_name';

// 필터 파라미터
$mo_id_filter = ses($_REQUEST, 'mo_id', 0, 'int');

// 몬스터 목록 (필터용) - raid_type 필터 적용
$mo_list = array();
$mo_sql_where = "";
if ($cur_raid_type !== 'all') {
    $mo_sql_where = " WHERE raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}
$mo_sql = sql_query("SELECT mo_id, mo_name, mo_thumb FROM {$g5['k_monster_table']} {$mo_sql_where} ORDER BY mo_name ASC");
for ($i = 0; $row = sql_fetch_array($mo_sql); $i++) {
    $mo_list[] = $row;
}

// 스킬 목록 (등록용) - 몬스터용 스킬만, raid_type 필터 적용
$sk_list = array();
$sk_sql_where = "WHERE (sk.unit_type = 'mo' OR sk.unit_type = '' OR sk.unit_type IS NULL)";
if ($cur_raid_type !== 'all') {
    $sk_sql_where .= " AND sk.raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}
$sk_sql = sql_query("
    SELECT sk.sk_id, sk.sk_name, sk.sk_icon, si.si_type 
    FROM {$g5['k_skill_table']} sk
    INNER JOIN {$g5['k_skill_info_table']} si ON sk.si_id = si.si_id
    {$sk_sql_where}
    ORDER BY sk.sk_name ASC
");
for ($i = 0; $row = sql_fetch_array($sk_sql); $i++) {
    $sk_list[] = $row;
}

// 현재 몬스터가 이미 보유한 스킬 ID 목록
$owned_sk_ids = array();
if ($mo_id_filter > 0) {
    $owned_sql = sql_query("SELECT sk_id FROM {$g5['k_mo_skill_table']} WHERE mo_id = '{$mo_id_filter}'");
    for ($i = 0; $row = sql_fetch_array($owned_sql); $i++) {
        $owned_sk_ids[] = (int)$row['sk_id'];
    }
}

// 목록 쿼리
$sql_common = "
    FROM {$g5['k_mo_skill_table']} ms
    INNER JOIN {$g5['k_monster_table']} mo ON ms.mo_id = mo.mo_id
    INNER JOIN {$g5['k_skill_table']} sk ON ms.sk_id = sk.sk_id
    INNER JOIN {$g5['k_skill_info_table']} si ON sk.si_id = si.si_id
";
$sql_search = " WHERE (1) ";

// raid_type 필터 (몬스터 테이블 기준)
if ($cur_raid_type !== 'all') {
    $sql_search .= " AND mo.raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}

if ($mo_id_filter > 0) {
    $sql_search .= " AND ms.mo_id = '{$mo_id_filter}' ";
}

if ($stx !== '') {
    $like = sql_escape_string($stx);
    if ($sfl === 'mo_name') {
        $sql_search .= " AND mo.mo_name LIKE '%{$like}%' ";
    } elseif ($sfl === 'sk_name') {
        $sql_search .= " AND sk.sk_name LIKE '%{$like}%' ";
    } elseif ($sfl === 'cs_name') {
        $sql_search .= " AND ms.cs_name LIKE '%{$like}%' ";
    }
}

$sql_order = " ORDER BY ms.mo_id ASC, ms.{$sst} {$sod} ";

// 전체 건수
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = ses($row, 'cnt', 0, 'int');

// 페이징
$rows = 30;
$total_page = $total_count ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;
if ($from_record < 0) $from_record = 0;

// 목록
$sql = "
    SELECT ms.*, mo.mo_name, mo.mo_thumb, sk.sk_name AS orig_sk_name, sk.sk_icon AS orig_sk_icon, 
           sk.sk_content AS orig_sk_content, sk.sk_info AS orig_sk_info,
           sk.sk_value, sk.sc_id, sk.default_calc, sk.bonus_calc,
           sk.sk_target, sk.sk_target_cnt AS orig_sk_target_cnt, sk.sk_turn,
           si.si_type
    {$sql_common} {$sql_search} {$sql_order} 
    LIMIT {$from_record}, {$rows}
";
$result = sql_query($sql);

$g5['title'] = '몬스터 스킬 관리 (' . $raid_types[$cur_raid_type] . ')';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">스킬 등록/수정</a></li>
    <li><a href="#anc_002">보유 스킬 목록</a></li>
</ul>';
?>

<style>
.mo-thumb { max-height: 40px; vertical-align: middle; margin-right: 5px; }
.sk-icon { max-height: 30px; vertical-align: middle; margin-right: 5px; }
.use-off { opacity: 0.5; }
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name):
    if($rt_key=='mmbraid') continue;?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?><?php if ($mo_id_filter > 0) echo '&mo_id='.$mo_id_filter; ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<section id="anc_001">
    <h2 class="h2_frm">스킬 등록/수정 (<?php echo h($raid_types[$cur_raid_type]); ?>)</h2>
    <?php echo $pg_anchor ?>

    <?php if ($mo_id_filter > 0) { ?>
    <form method="post" action="./980_k_mo_skill_update.php" autocomplete="off">
        <input type="hidden" name="type" value="bulk_update">
        <input type="hidden" name="mo_id" value="<?php echo (int)$mo_id_filter ?>">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">

        <div class="tbl_frm01 tbl_wrap">
            <table>
                <colgroup>
                    <col style="width: 150px;">
                    <col>
                </colgroup>
                <tbody>
                <tr>
                    <th scope="row">몬스터</th>
                    <td>
                        <strong>
                            <?php 
                            foreach ($mo_list as $mo) {
                                if ((int)$mo['mo_id'] === $mo_id_filter) {
                                    if (!empty($mo['mo_thumb'])) {
                                        echo '<img src="'.h($mo['mo_thumb']).'" alt="" class="mo-thumb">';
                                    }
                                    echo get_text($mo['mo_name']);
                                    break;
                                }
                            }
                            ?>
                        </strong>
                        <a href="?mo_id=<?php if ($cur_raid_type !== 'all') echo '&raid_type='.urlencode($cur_raid_type); ?>" style="margin-left:10px;">[다른 몬스터 선택]</a>
                    </td>
                </tr>
                <tr>
                    <th scope="row">스킬 선택</th>
                    <td>
                        <p style="margin-bottom:10px;">
                            <button type="button" onclick="$('input[name=\'sk_ids[]\']').prop('checked', true);">전체선택</button>
                            <button type="button" onclick="$('input[name=\'sk_ids[]\']').prop('checked', false);">전체해제</button>
                            <span style="margin-left:20px; color:#888;">✓ 체크된 스킬은 보유, 체크 해제하면 삭제됩니다.</span>
                        </p>
                        <div style="max-height:400px; overflow-y:auto; border:1px solid #ddd; padding:10px;">
                            <?php foreach ($sk_list as $sk) { 
                                $is_owned = in_array((int)$sk['sk_id'], $owned_sk_ids, true);
                            ?>
                                <label style="display:block; margin-bottom:5px; <?php if ($is_owned) echo 'background:#ffffd0;'; ?>">
                                    <input type="checkbox" name="sk_ids[]" value="<?php echo (int)$sk['sk_id'] ?>" <?php if ($is_owned) echo 'checked'; ?>>
                                    <?php if (!empty($sk['sk_icon'])) { ?>
                                        <img src="<?php echo h($sk['sk_icon']) ?>" alt="" style="max-height:20px; vertical-align:middle;">
                                    <?php } ?>
                                    [<?php echo get_text($sk['si_type']) ?>] <?php echo get_text($sk['sk_name']) ?>
                                    <?php if ($is_owned) { ?><span style="color:#090;">(보유중)</span><?php } ?>
                                </label>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" value="저장" onclick="document.pressed=this.value">
        </div>
    </form>
    <?php } else { ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 150px;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th scope="row"><label for="select_mo_id">몬스터 선택</label></th>
                <td>
                    <form method="get" style="display:inline;">
                        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
                        <select name="mo_id" id="select_mo_id" class="frm_input" onchange="this.form.submit();">
                            <option value="">-- 몬스터를 선택하세요 --</option>
                            <?php foreach ($mo_list as $mo) { ?>
                                <option value="<?php echo (int)$mo['mo_id'] ?>">
                                    <?php echo get_text($mo['mo_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php } ?>
</section>
<section id="anc_002">
    <h2 class="h2_frm">몬스터 스킬 목록 (<?php echo h($raid_types[$cur_raid_type]); ?>)</h2>
    <?php echo $pg_anchor ?>

    <div class="local_ov01 local_ov">
        <a href="<?php echo $_SERVER['PHP_SELF'] ?>" class="ov_listall">전체목록</a>
        전체 <?php echo number_format($total_count) ?> 건
        <?php if ($mo_id_filter > 0) { ?>
            | <a href="./980_k_monster.php">← 몬스터 관리로 돌아가기</a>
        <?php } ?>
    </div>

    <!-- 필터 폼 -->
    <form method="get" class="local_sch01 local_sch">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <select name="mo_id" id="mo_id_filter" onchange="this.form.submit();">
            <option value="">전체</option>
            <?php foreach ($mo_list as $mo) { ?>
                <option value="<?php echo (int)$mo['mo_id'] ?>" <?php if ($mo_id_filter === (int)$mo['mo_id']) echo 'selected'; ?>>
                    <?php echo get_text($mo['mo_name']) ?>
                </option>
            <?php } ?>
        </select>
        <label for="sfl" class="sound_only">검색대상</label>
        <select name="sfl" id="sfl">
            <option value="mo_name" <?php if ($sfl === 'mo_name') echo 'selected'; ?>>몬스터명</option>
            <option value="sk_name" <?php if ($sfl === 'sk_name') echo 'selected'; ?>>스킬명</option>
            <option value="cs_name" <?php if ($sfl === 'cs_name') echo 'selected'; ?>>커스텀명</option>
        </select>
        <input type="text" name="stx" value="<?php echo h($stx) ?>" id="stx" class="frm_input">
        <input type="submit" value="검색" class="btn_submit">
    </form>

    <form method="post" action="./980_k_mo_skill_update.php" onsubmit="return flist_submit(this);">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <input type="hidden" name="sst" value="<?php echo h($sst) ?>">
        <input type="hidden" name="sod" value="<?php echo h($sod) ?>">
        <input type="hidden" name="sfl" value="<?php echo h($sfl) ?>">
        <input type="hidden" name="stx" value="<?php echo h($stx) ?>">
        <input type="hidden" name="page" value="<?php echo (int)$page ?>">
        <input type="hidden" name="mo_id" value="<?php echo (int)$mo_id_filter ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">

        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption><?php echo $g5['title'] ?> 목록</caption>
                <colgroup>
                    <col style="width: 40px;">
                    <col style="width: 100px;">
                    <col style="width: 120px;">
                    <col style="width: 200px;">
                    <col>
                    <col style="width: 80px;">
                </colgroup>
                <thead>
                <tr>
                    <th scope="col">
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">몬스터</th>
                    <th scope="col">스킬</th>
                    <th scope="col">스킬정보</th>
                    <th scope="col">커스텀 설정</th>
                    <th scope="col">사용</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                // 계산기호 매핑
                $default_calc_map = array('p' => '+', 'm' => '*', 'i' => '계산시');
                $bonus_calc_map   = array('p' => '+', 'm' => '*');
                
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    $bg = 'bg' . ($i % 2);
                    $use_class = (int)$row['cs_use'] === 0 ? 'use-off' : '';
                    
                    $display_name = !empty($row['cs_name']) ? $row['cs_name'] : $row['orig_sk_name'];
                    $display_icon = !empty($row['cs_icon']) ? $row['cs_icon'] : $row['orig_sk_icon'];
                    
                    // 스킬 정보 계산
                    $default_calc = isset($row['default_calc'], $default_calc_map[$row['default_calc']]) ? $default_calc_map[$row['default_calc']] : '';
                    $bonus_calc   = isset($row['bonus_calc'], $bonus_calc_map[$row['bonus_calc']]) ? $bonus_calc_map[$row['bonus_calc']] : '';
                    
                    $base_label = '';
                    if ($default_calc !== '') {
                        $base_label = ($row['si_type'] === '공격') ? '기본공격력' : '기본치유력';
                    }
                    
                    // 능력치명 조회
                    $sc_name = '';
                    if (!empty($row['sc_id'])) {
                        $sc_row = sql_fetch("SELECT sc_name FROM {$g5['k_stat_table']} WHERE sc_id = ".(int)$row['sc_id']);
                        if (!empty($sc_row['sc_name'])) {
                            $sc_name = $sc_row['sc_name'];
                        }
                    }
                    
                    // 대상 처리
                    $target_txt = '';
                    switch (isset($row['sk_target']) ? $row['sk_target'] : '') {
                        case 'self': $target_txt = '본인'; break;
                        case 'ally': $target_txt = '아군'; break;
                        case 'enemy': $target_txt = '적군'; break;
                    }
                    $orig_target_cnt = isset($row['orig_sk_target_cnt']) ? $row['orig_sk_target_cnt'] : '';
                    if ($orig_target_cnt === 'single') { $target_txt .= ' 단일'; }
                    elseif ($orig_target_cnt === 'all') { $target_txt .= ' 전체'; }
                    
                    // 타겟 명수 지정 가능 여부 (적군 단일일 때만)
                    $can_set_target_cnt = ($row['sk_target'] === 'enemy' && $orig_target_cnt === 'single');
                    $cs_target_cnt = ses($row, 'cs_target_cnt', 0, 'int');
                ?>
                <tr class="<?php echo "{$bg} {$use_class}" ?>">
                    <td style="text-align: center;">
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                        <input type="hidden" name="cs_id[<?php echo $i ?>]" value="<?php echo (int)$row['cs_id'] ?>">
                    </td>
                    <td>
                        <span><?php echo get_text($row['mo_name']) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($display_icon)) { ?>
                            <img src="<?php echo h($display_icon) ?>" alt="" class="sk-icon">
                        <?php } ?>
                        <span><?php echo get_text($display_name) ?></span>
                        <br><small style="color:#888;">[<?php echo get_text($row['si_type']) ?>]</small>
                    </td>
                    <td class="txt-left" style="font-size:12px;">
                        <p><b>적용값:</b> 
                            <?php 
                            if ($base_label !== '') echo $base_label . $default_calc . '(';
                            echo get_text($sc_name) . ' ' . $bonus_calc . ' ' . h($row['sk_value']);
                            if ($base_label !== '') echo ')';
                            ?>
                        </p>
                        <p><b>대상:</b> <?php echo $target_txt; ?></p>
                        <p><b>지속:</b> <?php echo h($row['sk_turn']); ?>턴</p>
                        <?php if ($can_set_target_cnt) { ?>
                        <p style="margin-top:5px;"><b>타겟 명수:</b> 
                            <input type="number" name="cs_target_cnt[<?php echo $i ?>]" value="<?php echo $cs_target_cnt ?>" 
                                   min="0" max="99" style="width:50px;"> 
                            <small style="color:#888;">(0=기본1명)</small>
                        </p>
                        <?php } else { ?>
                        <input type="hidden" name="cs_target_cnt[<?php echo $i ?>]" value="0">
                        <?php } ?>
                    </td>
                    <td class="txt-left">
                        <p>
                            <span style="background:yellow;">이름</span>
                            <input type="text" name="cs_name[<?php echo $i ?>]" value="<?php echo get_text($row['cs_name']) ?>" 
                                   placeholder="<?php echo get_text($row['orig_sk_name']) ?>" style="width:150px;">
                        </p>
                        <p>
                            <span style="background:yellow;">아이콘</span>
                            <input type="text" name="cs_icon[<?php echo $i ?>]" value="<?php echo h($row['cs_icon']) ?>" 
                                   placeholder="URL" style="width:200px;">
                        </p>
                        <p>
                            <span style="background:yellow;">설명</span>
                            <input type="text" name="cs_content[<?php echo $i ?>]" value="<?php echo get_text($row['cs_content']) ?>" 
                                   placeholder="<?php echo get_text($row['orig_sk_content']) ?>" style="width:300px;">
                        </p>
                    </td>
                    <td style="text-align: center;">
                        <select name="cs_use[<?php echo $i ?>]">
                            <option value="1" <?php if ((int)$row['cs_use'] === 1) echo 'selected'; ?>>사용</option>
                            <option value="0" <?php if ((int)$row['cs_use'] === 0) echo 'selected'; ?>>미사용</option>
                        </select>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($i === 0) { ?>
                    <tr><td colspan="6" class="empty_table">자료가 없습니다.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="btn_list01 btn_list">
            <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
            <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
        </div>
    </form>

    <?php 
    $paging_qstr = "mo_id={$mo_id_filter}&sfl={$sfl}&stx=".urlencode($stx);
    if ($cur_raid_type !== 'all') {
        $paging_qstr .= '&raid_type=' . urlencode($cur_raid_type);
    }
    echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "?{$paging_qstr}&page="); 
    ?>
</section>



<script>
function flist_submit(f) {
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

<?php include_once('./admin.tail.php'); ?>
