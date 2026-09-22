<?php

include_once('./_common.php');

if(!isset($page)||$page<1) $page = 1;

// 현재 선택된 raid_type
$cur_raid_type = ses($_REQUEST, 'raid_type', 'all', 'raw');
if (!isset($raid_types[$cur_raid_type])) {
    $cur_raid_type = 'all';
}

$type_keys = array_keys($raid_types); 
$type_index = array_search($cur_raid_type, $type_keys); 
$unit_index = ($_REQUEST['unit_type']=='ch')?2:4;
$sub_menu = "98{$type_index}{$unit_index}10";



// 정렬·검색 허용 컬럼 화이트리스트
$allow_sort = array('sk_id','sk_name','si_id','unit_type');
if (!in_array($sst, $allow_sort, true)) $sst = 'sk_id';
$sod = strtolower($sod) === 'desc' ? 'desc' : 'asc';

$allow_search = array('sk_name','sk_id');
if (!in_array($sfl, $allow_search, true)) $sfl = 'sk_name';

// unit_type 필터
$unit_type_filter = ses($_REQUEST, 'unit_type', '', 'raw');
if ($unit_type_filter !== 'ch' && $unit_type_filter !== 'mo') {
    $unit_type_filter = '';
}

// 설정값 기본 방어
$kb_cf['limit_skill'] = ses($kb_cf, 'limit_skill', 0, 'int');
$kb_cf['skill_max']   = ses($kb_cf, 'skill_max', 0, 'int');
$kb_cf['skill_img']   = !empty($kb_cf['skill_img']) ? 1 : 0;

// 스킬 타입 목록
$type_list = array();
$type_si1  = array();
$si_info   = array();

$type_sql = sql_query("SELECT * FROM {$g5['k_skill_info_table']} WHERE si_use = 1");
for ($i = 0; $row = sql_fetch_array($type_sql); $i++) {
    $type_list[] = $row;
    $type_si1[$row['si_id']] = $row['si_1'];
    $si_info[$row['si_id']]  = $row['si_info'];
}

// 첨번째 타입의 정보 (등록폼용)
$first_si1  = isset($type_list[0]['si_1']) ? $type_list[0]['si_1'] : '';
$first_info = isset($type_list[0]['si_info']) ? $type_list[0]['si_info'] : '';

// 스탯 목록
$sc_list = array();
$sc_sql = sql_query("SELECT * FROM {$g5['k_stat_table']} WHERE sc_category = 'stat'");
for ($i = 0; $row = sql_fetch_array($sc_sql); $i++) {
    $sc_list[] = $row;
}

// 목록 쿼리
$sql_common = " FROM {$g5['k_skill_table']} ";
$sql_search = " WHERE (1) ";

if ($unit_type_filter !== '') {
    $sql_search .= " AND unit_type = '{$unit_type_filter}' ";
}

if ($cur_raid_type !== 'all') {
    $sql_search .= " AND raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}

if ($stx !== '') {
    $like = sql_escape_string($stx);
    $sql_search .= " AND {$sfl} LIKE '{$like}%'";
}

$sql_order = " ORDER BY {$sst} {$sod} ";

// 전체 건수
$row = sql_fetch("SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}");
$total_count = ses($row, 'cnt', 0, 'int');

// 페이징
$rows = 50;
$total_page = $total_count ? (int)ceil($total_count / $rows) : 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;
if ($from_record < 0) $from_record = 0;

// 목록
$sql = "SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows}";
$result = sql_query($sql);
$title_pre=($unit_type_filter=='mo')?'몬스터':'캐릭터';
$g5['title'] = $title_pre . ' 스킬 등록 (' . $raid_types[$cur_raid_type] . ')';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>

<style>
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
.raid_chk_group label { margin-right: 8px; white-space: nowrap; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name): 
        if($unit_type_filter=='mo'&&$rt_key=='mmbraid') continue;?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?><?php echo $unit_type_filter !== '' ? '&amp;unit_type='.urlencode($unit_type_filter) : ''; ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<?php if ($unit_type_filter !== 'mo') { ?>
<h2 class="h2_frm">스킬 설정</h2>
<form method="post" action="./980_k_skill_update.php">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 100px;" />
                <col/>
            </colgroup>
            <thead>
            <tr>
                <th scope="col" colspan="2">스킬 설정</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>스킬 사용</td>
                <td style="text-align:left;">
                    <select name="limit_skill">
                        <option value="">제한 없음</option>
                        <option value="999" <?php if ($kb_cf['limit_skill'] === 999) echo 'selected'; ?>>스킬 사용 안함</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>최대 갯수</td>
                <td style="text-align:left;">
                    <input type="text" name="skill_max" value="<?php echo (int)$kb_cf['skill_max'] ?>">개
                </td>
            </tr>
            <tr>
                <td>스킬컷 사용</td>
                <td style="text-align:left;">
                    <input type="checkbox" name="skill_img" value="1" <?php if ($kb_cf['skill_img']) echo 'checked'; ?>>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="업데이트" onclick="document.pressed=this.value">
    </div>
</form>
<?php } // unit_type_filter !== 'mo' ?>

<h2 class="h2_frm">스킬 등록</h2>
<form action="./980_k_skill_update.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="type" value="insert">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 100px;" />
                <col style="width: 100px;" />
                <col style="width: 100px;" />
                <col />
                <col style="width: 100px;" />
                <col style="width: 150px;" />
                <col style="width: 50px;" />
                <col style="width: 100px;" />
     
            </colgroup>
            <thead>
            <tr>
                <th>타입</th>
                <th>스킬이름</th>
                <th>스킬종류</th>
                <th>적용값</th>
                <th>사용대상</th>
                <th>지속턴</th>
                <th>소모mp</th>
                <th>커스텀</th>
                <th>레이드타입</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <select name="unit_type" class="frm_input">
                        <option value="">미지정</option>
                        <option value="ch" <?php if ($unit_type_filter === 'ch') echo 'selected'; ?>>캐릭터용</option>
                        <option value="mo" <?php if ($unit_type_filter === 'mo') echo 'selected'; ?>>몬스터용</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="sk_name" class="frm_input" value="">
                </td>
                <td class="txt-left">
                    <select name="si_id" class="frm_input" id="si_id" onchange="fn_type_change('si_id','insert','');">
                        <?php for ($k = 0; $k < count($type_list); $k++) { ?>
                            <option
                                data-passive="<?php echo (int)$type_list[$k]['si_passive'] ?>"
                                data-attr="<?php echo h($type_list[$k]['si_1']) ?>"
                                data-info="<?php echo get_text($type_list[$k]['si_info']) ?>"
                                value="<?php echo (int)$type_list[$k]['si_id'] ?>">
                                <?php echo get_text($type_list[$k]['si_type']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
                <td class="txt-left">
                    <p><span style="background:yellow;">아이콘</span>
                        <input type="text" placeholder="URL 직접입력" size="40" name="sk_icon">
                        <input type="file" name="sk_icon_file" accept="image/*">
                    </p>
                    <p id="sk_info"><?php echo $first_info ?></p>
                    <p>
                        <span style="background:yellow;">스킬설명</span>
                        <input type="text" size="50" name="sk_content" placeholder="커스텀 가능 부분">
                        <input type="text" size="50" name="sk_info" placeholder="커스텀 불가 부분(스킬효과등)">
                    </p>
                    <span style="background:yellow;">적용값</span>
                        <select name="target_sc" class="sk_changes" id="target_sc" <?php if ($first_si1 !== 'target_sc') { ?>style="display:none;"<?php } ?>>
                        <option value="">적용대상스탯</option>
                        <?php for ($k = 0; $k < count($sc_list); $k++) { ?>
                            <option value="<?php echo (int)$sc_list[$k]['sc_id'] ?>"><?php echo get_text($sc_list[$k]['sc_name']) ?></option>
                        <?php } ?>
                    </select>
                    <select name="default_calc" class="sk_changes" id="default" <?php if ($first_si1 !== 'default') { ?>style="display:none;"<?php } ?>>
                        <option value="">기본수치 제외</option>
                        <option value="p">기본수치 +</option>
                        <option value="m">기본수치 *</option>
                        <option value="i">기본수치 계산시</option>
                    </select>
                    <span>(</span>
                    <select name="sc_id">
                        <option value="">관여스탯 없음</option>
                        <?php for ($k = 0; $k < count($sc_list); $k++) { ?>
                            <option value="<?php echo (int)$sc_list[$k]['sc_id'] ?>"><?php echo get_text($sc_list[$k]['sc_name']) ?></option>
                        <?php } ?>
                    </select>
                    <select name="bonus_calc">
                        <option value="p">+</option>
                        <option value="m">*</option>
                    </select>
                    <input type="text" placeholder="음수/소수 ok" size="12" name="sk_value">
                    <span>)</span>
                        <span id="percent" class="sk_changes" <?php if ($first_si1 !== 'percent') { ?>style="display:none;"<?php } ?>>% 확률로 발동</span>
                </td>
                <td class="txt-left">
                    <select name="sk_target" class="frm_input" id="sk_target">
                        <option value="self">본인</option>
                        <option value="ally">아군</option>
                        <option value="enemy">적</option>
                        <?php if ($unit_type_filter !== 'mo') { ?><option value="passive" disabled>패시브</option><?php } ?>
                    </select>
                    <select name="sk_target_cnt" class="frm_input">
                        <option value="single">단일</option>
                        <option value="all">전체</option>
                    </select>
                </td>
                <td>
                    발동턴 + <input type="text" name="sk_turn" value="0" size="2">턴 지속<br>
                    <?php if ($unit_type_filter !== 'mo') { ?>
                    쿨타임 <input type="text" name="sk_cool" value="0" size="2">턴 후 재사용
                    <?php } else { ?>
                    <input type="hidden" name="sk_cool" value="0">
                    <?php } ?>
                </td>
                <td>
                    <?php if ($unit_type_filter !== 'mo') { ?>
                    <input type="text" name="sk_mp" size="8" value="0">
                    <?php } else { ?>
                    <input type="hidden" name="sk_mp" value="0">-
                    <?php } ?>
                </td>
                <td>
                    <?php if ($unit_type_filter !== 'mo') { ?>
                    <p>커스텀 <input type="checkbox" name="sk_use" value="custom"></p>
                    <?php } else { ?>
                    <input type="hidden" name="sk_use" value="custom">
                    <?php } ?>
                </td>
                <td>
                    <div class="raid_chk_group">
                        <?php foreach ($raid_types as $rt_key => $rt_name): 
                            if ($rt_key === 'all') continue;
                            $rt_checked = ($cur_raid_type === 'all' || $cur_raid_type === $rt_key) ? 'checked' : '';
                        ?>
                        <label><input type="checkbox" name="raid_type[]" value="<?php echo h($rt_key); ?>" <?php echo $rt_checked; ?>> <?php echo h($rt_name); ?></label>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
    </div>
</form>

<h2 class="h2_frm">등록스킬관리</h2>
<form action="./980_k_skill_update.php" onsubmit="return f_submit(this);" method="post" enctype="multipart/form-data">
    <input type="hidden" name="sst" value="<?php echo h($sst) ?>">
    <input type="hidden" name="sod" value="<?php echo h($sod) ?>">
    <input type="hidden" name="sfl" value="<?php echo h($sfl) ?>">
    <input type="hidden" name="stx" value="<?php echo h($stx) ?>">
    <input type="hidden" name="page" value="<?php echo (int)$page ?>">
    <input type="hidden" name="unit_type_filter" value="<?php echo h($unit_type_filter) ?>">
    <input type="hidden" name="raid_type_filter" value="<?php echo h($cur_raid_type) ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 50px;" />
                <col style="width: 100px;" />
                <col style="width: 100px;" />
                <col />
                <col style="width: 100px;" />
                <col style="width: 150px;" />
                <col style="width: 50px;" />
                <col style="width: 80px;" />
                <col style="width: 120px;" />
                <col style="width: 80px;" />
            </colgroup>
            <thead>
            <tr>
                <th scope="col">
                    <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                </th>
                <th>스킬이름</th>
                <th>스킬종류</th>
                <th>적용값</th>
                <th>사용대상</th>
                <th>지속턴</th>
                <th>소모mp</th>
                <th>커스텀</th>
                <th>레이드타입</th>
                <th>유닛타입</th>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 0; $row = sql_fetch_array($result); $i++) {
                $bg = 'bg' . ($i % 2);
                // 현재 행의 si_id에 해당하는 타입 정보
                $row_si1 = isset($type_si1[$row['si_id']]) ? $type_si1[$row['si_id']] : '';
                $row_si_info = isset($si_info[$row['si_id']]) ? get_text($si_info[$row['si_id']]) : '';
                ?>
                <tr class="<?php echo $bg; ?>">
                    <td>
                        <input type="hidden" name="sk_id[<?php echo $i ?>]" value="<?php echo (int)$row['sk_id'] ?>" id="sk_id_<?php echo $i ?>">
                        <p>ID: <?php echo (int)$row['sk_id'] ?></p>
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                    </td>
                    <td>
                        <img src="<?php echo h($row['sk_icon']) ?>" alt="" style="max-height:40px;">
                        <input type="text" name="sk_name[<?php echo $i ?>]" class="frm_input" value="<?php echo get_text($row['sk_name']) ?>">
                    </td>
                    <td class="txt-left">
                        <select name="si_id[<?php echo $i ?>]" class="frm_input" id="si_id<?php echo $i ?>" onchange="fn_type_change('si_id','insert','<?php echo $i ?>');">
                            <option>종류 선택</option>
                            <?php for ($k = 0; $k < count($type_list); $k++) { ?>
                                <option
                                    data-passive="<?php echo (int)$type_list[$k]['si_passive'] ?>"
                                    data-attr="<?php echo h($type_list[$k]['si_1']) ?>"
                                    data-info="<?php echo get_text($type_list[$k]['si_info']) ?>"
                                    value="<?php echo (int)$type_list[$k]['si_id'] ?>"
                                    <?php if ((string)$row['si_id'] === (string)$type_list[$k]['si_id']) echo 'selected'; ?>>
                                    <?php echo get_text($type_list[$k]['si_type']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td class="txt-left">
                        <p><span style="background:yellow;">아이콘</span>
                            <input type="text" placeholder="URL 직접입력" size="40" name="sk_icon[<?php echo $i ?>]" value="<?php echo h($row['sk_icon']) ?>">
                            <input type="file" name="sk_icon_file_<?php echo $i ?>" accept="image/*">
                        </p>
                        <p id="sk_info<?php echo $i ?>"><?php echo $row_si_info ?></p>
                        <p>
                            <span style="background:yellow;">스킬설명</span>
                            <input type="text" placeholder="" size="50" name="sk_content[<?php echo $i ?>]" value="<?php echo get_text($row['sk_content']) ?>">
                            <input type="text" placeholder="" size="50" name="sk_info[<?php echo $i ?>]" value="<?php echo get_text($row['sk_info']) ?>">
                        </p>
                        <span style="background:yellow;">적용값</span>
                        <select name="target_sc[<?php echo $i ?>]" class="sk_changes<?php echo $i ?>" id="target_sc<?php echo $i ?>" <?php if ($row_si1 !== 'target_sc') { ?>style="display:none;"<?php } ?>>
                            <option value="">적용대상</option>
                            <?php for ($k = 0; $k < count($sc_list); $k++) { ?>
                                <option value="<?php echo (int)$sc_list[$k]['sc_id'] ?>" <?php if ((string)$row['target_sc'] === (string)$sc_list[$k]['sc_id']) echo 'selected'; ?>>
                                    <?php echo get_text($sc_list[$k]['sc_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <select name="default_calc[<?php echo $i ?>]" class="sk_changes<?php echo $i ?>" id="default<?php echo $i ?>" <?php if ($row_si1 !== 'default') { ?>style="display:none;"<?php } ?>>
                            <option value=""  <?php if ($row['default_calc'] === '')  echo 'selected'; ?>>기본수치 제외</option>
                            <option value="p" <?php if ($row['default_calc'] === 'p') echo 'selected'; ?>>기본수치 +</option>
                            <option value="m" <?php if ($row['default_calc'] === 'm') echo 'selected'; ?>>기본수치 *</option>
                            <option value="i" <?php if ($row['default_calc'] === 'i') echo 'selected'; ?>>기본수치 계산시</option>
                        </select>
                        <span>(</span>
                        <select name="sc_id[<?php echo $i ?>]">
                            <option value="">관여스탯 없음</option>
                            <?php for ($k = 0; $k < count($sc_list); $k++) { ?>
                                <option value="<?php echo (int)$sc_list[$k]['sc_id'] ?>" <?php if ((string)$row['sc_id'] === (string)$sc_list[$k]['sc_id']) echo 'selected'; ?>>
                                    <?php echo get_text($sc_list[$k]['sc_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <select name="bonus_calc[<?php echo $i ?>]">
                            <option value="p" <?php if ($row['bonus_calc'] === 'p') echo 'selected'; ?>>+</option>
                            <option value="m" <?php if ($row['bonus_calc'] === 'm') echo 'selected'; ?>>*</option>
                        </select>
                        <input type="text" placeholder="음수/소수 ok" size="8" name="sk_value[<?php echo $i ?>]" value="<?php echo h($row['sk_value']) ?>">
                        <span>)</span>
                        <span id="percent<?php echo $i ?>" class="sk_changes<?php echo $i ?>" <?php if ($row_si1 !== 'percent') { ?>style="display:none;"<?php } ?>>% 확률로 발동</span>
                    </td>
                    <td class="txt-left">
                        <select name="sk_target[<?php echo $i ?>]" class="frm_input" id="sk_target<?php echo $i ?>">
                            <option value="self"    <?php if ($row['sk_target'] === 'self')    echo 'selected'; ?>>본인</option>
                            <option value="ally"    <?php if ($row['sk_target'] === 'ally')    echo 'selected'; ?>>아군</option>
                            <option value="enemy"   <?php if ($row['sk_target'] === 'enemy')   echo 'selected'; ?>>적</option>
                            <?php if ($unit_type_filter !== 'mo') { ?><option value="passive" <?php if ($row['sk_target'] === 'passive') echo 'selected'; ?>>패시브</option><?php } ?>
                        </select>
                        <select name="sk_target_cnt[<?php echo $i ?>]" class="frm_input">
                            <option value="single" <?php if ($row['sk_target_cnt'] === 'single') echo 'selected'; ?>>단일</option>
                            <option value="all"    <?php if ($row['sk_target_cnt'] === 'all')    echo 'selected'; ?>>전체</option>
                        </select>
                    </td>
                    <td>
                        발동턴 + <input type="text" name="sk_turn[<?php echo $i ?>]" value="<?php echo h($row['sk_turn']) ?>" size="2">턴 지속<br>
                        <?php if ($unit_type_filter !== 'mo') { ?>
                        쿨타임 <input type="text" name="sk_cool[<?php echo $i ?>]" value="<?php echo h($row['sk_cool']) ?>" size="2">턴 후 재사용
                        <?php } else { ?>
                        <input type="hidden" name="sk_cool[<?php echo $i ?>]" value="0">
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($unit_type_filter !== 'mo') { ?>
                        <input type="text" name="sk_mp[<?php echo $i ?>]" size="8" value="<?php echo h($row['sk_mp']) ?>">
                        <?php } else { ?>
                        <input type="hidden" name="sk_mp[<?php echo $i ?>]" value="0">-
                        <?php } ?>
                    </td>
                    <td>
                        <?php if ($unit_type_filter !== 'mo') { ?>
                        <p>커스텀 <input type="checkbox" name="sk_use[<?php echo $i ?>]" value="custom" <?php if (strpos((string)$row['sk_use'], 'custom') !== false) echo 'checked'; ?>></p>
                        <?php } else { ?>
                        <input type="hidden" name="sk_use[<?php echo $i ?>]" value="custom">
                        <?php } ?>
                    </td>
                    <td>
                        <div class="raid_chk_group">
                        <?php 
                        $row_raid_type = ses($row, 'raid_type', 'realtime', 'raw');
                        foreach ($raid_types as $rt_key => $rt_name): 
                            if ($rt_key === 'all') continue;
                            $rt_checked = (strpos($row_raid_type, $rt_key) !== false) ? 'checked' : '';
                        ?>
                        <label><input type="checkbox" name="raid_type[<?php echo $i ?>][]" value="<?php echo h($rt_key); ?>" <?php echo $rt_checked; ?>> <?php echo h($rt_name); ?></label>
                        <?php endforeach; ?>
                        </div>
                    </td>
                    <td>
                        <select name="unit_type[<?php echo $i ?>]" class="frm_input">
                            <option value="">미지정</option>
                            <option value="ch" <?php if (ses($row, 'unit_type', '', 'raw') === 'ch') echo 'selected'; ?>>캐릭터용</option>
                            <option value="mo" <?php if (ses($row, 'unit_type', '', 'raw') === 'mo') echo 'selected'; ?>>몬스터용</option>
                        </select>
                    </td>
                </tr>
            <?php } // for ?>
            <?php if (!isset($i) || $i === 0) { ?>
                <tr><td colspan="10" class="empty_table">자료가 없습니다.</td></tr>
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
$paging_qstr = $qstr;
if ($unit_type_filter !== '') {
    $paging_qstr .= '&amp;unit_type=' . urlencode($unit_type_filter);
}
if ($cur_raid_type !== 'all') {
    $paging_qstr .= '&amp;raid_type=' . urlencode($cur_raid_type);
}
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$paging_qstr.'&amp;page=');
?>

<script>
function fn_type_change(e,type,num){
    if(type==='insert'){
        var $opt = $("#"+e+num+" option:selected");
        var attr = $opt.data('attr');
        var info = $opt.data('info');
        var passive = Number($opt.data('passive'))===1;

        if(passive){
            $("#sk_target"+num+" option[value='passive']").prop('disabled', false);
        }else{
            var cur = $("#sk_target"+num).val();
            if(cur==='passive'){ $("#sk_target"+num).val(''); }
            $("#sk_target"+num+" option[value='passive']").prop('disabled', true);
        }

        if(attr){
            $(".sk_changes"+num+"#"+attr+num).show();
            $(".sk_changes"+num).not("#"+attr+num).hide();
        }else{
            $(".sk_changes"+num).hide();
        }

        $(".sk_changes"+num).val('');
        $("#sk_info"+num).html(info);
    }
}
function f_submit(f){
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
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php'); ?>
