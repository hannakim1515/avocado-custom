<?php
$sub_menu = '980420';
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
$sub_menu = "98{$type_index}420";

// 필터 파라미터
$mo_id_filter = ses($_REQUEST, 'mo_id', 0, 'int');

// 몬스터 목록 (필터용) - raid_type 필터 적용
$mo_list = array();
$mo_sql_where = "";
if ($cur_raid_type !== 'all') {
    $mo_sql_where = " WHERE raid_type LIKE '%".sql_escape_string($cur_raid_type)."%' ";
}
$mo_sql = sql_query("SELECT mo_id, mo_name, mo_thumb, mo_pattern, mo_default_act FROM {$g5['k_monster_table']} {$mo_sql_where} ORDER BY mo_name ASC");
for ($i = 0; $row = sql_fetch_array($mo_sql); $i++) {
    $mo_list[] = $row;
}

// 현재 몬스터 정보
$current_mo = null;
$current_pattern = 1;
$current_default_act = 1;
if ($mo_id_filter > 0) {
    foreach ($mo_list as $mo) {
        if ((int)$mo['mo_id'] === $mo_id_filter) {
            $current_mo = $mo;
            $current_pattern = ses($mo, 'mo_pattern', 1, 'int');
            if ($current_pattern < 1 || $current_pattern > 2) $current_pattern = 1;
            $current_default_act = ses($mo, 'mo_default_act', 1, 'int');
            if ($current_default_act < 1 || $current_default_act > 2) $current_default_act = 1;
            break;
        }
    }
}

// 몬스터 보유 스킬 목록
$mo_skill_list = array();
if ($mo_id_filter > 0) {
    $sk_sql = sql_query("
        SELECT ms.cs_id, ms.sk_id, ms.cs_name, ms.cs_icon,
               sk.sk_name, sk.sk_icon, si.si_type
        FROM {$g5['k_mo_skill_table']} ms
        INNER JOIN {$g5['k_skill_table']} sk ON ms.sk_id = sk.sk_id
        INNER JOIN {$g5['k_skill_info_table']} si ON sk.si_id = si.si_id
        WHERE ms.mo_id = '{$mo_id_filter}' AND ms.cs_use = 1
        ORDER BY ms.cs_id ASC
    ");
    for ($i = 0; $row = sql_fetch_array($sk_sql); $i++) {
        $mo_skill_list[] = $row;
    }
}

// 패턴 목록 쿼리
$pattern_list = array();
if ($mo_id_filter > 0) {
    $pt_sql = sql_query("
        SELECT * FROM {$g5['k_mo_pattern_table']} 
        WHERE mo_id = '{$mo_id_filter}' 
        ORDER BY pt_turn ASC, pt_id ASC
    ");
    for ($i = 0; $row = sql_fetch_array($pt_sql); $i++) {
        $pattern_list[] = $row;
    }
}

$g5['title'] = '몬스터 패턴 관리 (' . $raid_types[$cur_raid_type] . ')';
include_once('./admin.head.php');

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_001">패턴 목록</a></li>
    <li><a href="#anc_002">패턴 등록</a></li>
    <li><a href="#anc_003">패턴 시뮬레이션</a></li>
</ul>';
?>

<style>
.mo-thumb { max-height: 40px; vertical-align: middle; margin-right: 5px; }
.sk-icon { max-height: 24px; vertical-align: middle; margin-right: 3px; }
.pattern-type-box { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; }
.skill-checkbox-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; }
.skill-checkbox-list label { display: inline-block; margin-right: 15px; margin-bottom: 5px; white-space: nowrap; }
.pattern-skills { display: flex; flex-wrap: wrap; gap: 5px; }
.pattern-skill-tag { background: #e0e0e0; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
.raid_type_tabs { margin-bottom: 15px; }
.raid_type_tabs a { display: inline-block; padding: 8px 20px; background: #f5f5f5; border: 1px solid #ddd; margin-right: 5px; text-decoration: none; color: #333; }
.raid_type_tabs a.active { background: #2196F3; color: #fff; border-color: #2196F3; }
</style>

<!-- 레이드 타입 탭 -->
<div class="raid_type_tabs">
    <?php foreach ($raid_types as $rt_key => $rt_name): 
        // 몬스터 패턴은 MMB 레이드에서 사용하지 않음
        if ($rt_key === 'mmbraid') continue;
    ?>
    <a href="?raid_type=<?php echo urlencode($rt_key); ?><?php if ($mo_id_filter > 0) echo '&mo_id='.$mo_id_filter; ?>" class="<?php echo $cur_raid_type === $rt_key ? 'active' : ''; ?>"><?php echo h($rt_name); ?></a>
    <?php endforeach; ?>
</div>

<!-- 몬스터 선택 -->
<div class="local_ov01 local_ov">
    <form method="get" style="display:inline;">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <label for="mo_id_select"><strong>몬스터 선택:</strong></label>
        <select name="mo_id" id="mo_id_select" class="frm_input" onchange="this.form.submit();" style="min-width:200px;">
            <option value="">-- 몬스터를 선택하세요 --</option>
            <?php foreach ($mo_list as $mo) { ?>
                <option value="<?php echo (int)$mo['mo_id'] ?>" <?php if ($mo_id_filter === (int)$mo['mo_id']) echo 'selected'; ?>>
                    <?php echo get_text($mo['mo_name']) ?>
                </option>
            <?php } ?>
        </select>
    </form>
    <?php if ($mo_id_filter > 0) { ?>
        <a href="./980_k_monster.php?raid_type=<?php echo urlencode($cur_raid_type) ?>" style="margin-left:20px;">← 몬스터 관리로 돌아가기</a>
    <?php } ?>
</div>

<?php if ($mo_id_filter > 0 && $current_mo) { ?>

<!-- 몬스터 정보 및 패턴 타입 설정 -->
<div class="pattern-type-box">
    <form method="post" action="./982_k_mo_pattern_update.php" style="display:inline;">
        <input type="hidden" name="type" value="set_pattern_type">
        <input type="hidden" name="mo_id" value="<?php echo (int)$mo_id_filter ?>">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">
        
        <div style="display:flex; align-items:center; gap:20px;">
            <div>
                <?php if (!empty($current_mo['mo_thumb'])) { ?>
                    <img src="<?php echo h($current_mo['mo_thumb']) ?>" alt="" class="mo-thumb">
                <?php } ?>
                <strong style="font-size:16px;"><?php echo get_text($current_mo['mo_name']) ?></strong>
            </div>
            <div>
                <label><strong>패턴 타입:</strong></label>
                <label style="margin-left:10px;">
                    <input type="radio" name="mo_pattern" value="1" <?php if ($current_pattern === 1) echo 'checked'; ?>> 
                    타입1 (순차 실행)
                </label>
                <label style="margin-left:10px;">
                    <input type="radio" name="mo_pattern" value="2" <?php if ($current_pattern === 2) echo 'checked'; ?>> 
                    타입2 (조건 실행)
                </label>
            </div>
            <div>
                <label><strong>기본 행동:</strong></label>
                <label style="margin-left:10px;">
                    <input type="radio" name="mo_default_act" value="1" <?php if ($current_default_act === 1) echo 'checked'; ?>> 
                    무행동
                </label>
                <label style="margin-left:10px;">
                    <input type="radio" name="mo_default_act" value="2" <?php if ($current_default_act === 2) echo 'checked'; ?>> 
                    기본공격
                </label>
                <input type="submit" value="설정 저장" class="btn_submit" style="margin-left:10px;">
            </div>
        </div>
    </form>
    <p style="margin-top:10px; color:#666; font-size:12px;">
        * 타입1: 턴 순서대로 패턴을 순차 실행합니다.<br>
        * 타입2: 특정 조건(턴)에 맞는 패턴을 실행합니다.<br>
        * 기본 행동: 패턴에 해당하는 턴이 없을 때 수행할 행동입니다.
    </p>
</div>

<?php if (count($mo_skill_list) === 0) { ?>
<div class="alert" style="background:#fff3cd; padding:15px; border:1px solid #ffc107; border-radius:5px; margin-bottom:20px;">
    <strong>주의:</strong> 이 몬스터에 등록된 스킬이 없습니다. 
    <a href="./980_k_mo_skill.php?mo_id=<?php echo (int)$mo_id_filter ?>&raid_type=<?php echo urlencode($cur_raid_type) ?>">보유 스킬 관리</a>에서 먼저 스킬을 등록해 주세요.
</div>
<?php } else { ?>

<section id="anc_001">
    <h2 class="h2_frm">패턴 목록</h2>
    <?php echo $pg_anchor ?>

    <form method="post" action="./982_k_mo_pattern_update.php" onsubmit="return flist_submit(this);">
        <input type="hidden" name="mo_id" value="<?php echo (int)$mo_id_filter ?>">
        <input type="hidden" name="raid_type" value="<?php echo h($cur_raid_type) ?>">
        <input type="hidden" name="token" value="<?php echo $token ?>">

        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption>패턴 목록</caption>
                <colgroup>
                    <col style="width: 40px;">
                    <col style="width: 80px;">
                    <col>
                    <col style="width: 100px;">
                </colgroup>
                <thead>
                <tr>
                    <th scope="col">
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">발동 턴</th>
                    <th scope="col">스킬 후보</th>
                    <th scope="col">사용 갯수</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (count($pattern_list) > 0) {
                    for ($i = 0; $row = sql_fetch_array($pt_sql); $i++) {} // reset
                    $i = 0;
                    foreach ($pattern_list as $row) {
                        $bg = 'bg' . ($i % 2);
                        $pt_skills = !empty($row['pt_skill']) ? explode(',', $row['pt_skill']) : array();
                ?>
                <tr class="<?php echo $bg ?>">
                    <td style="text-align: center;">
                        <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
                        <input type="hidden" name="pt_id[<?php echo $i ?>]" value="<?php echo (int)$row['pt_id'] ?>">
                    </td>
                    <td style="text-align: center;">
                        <input type="number" name="pt_turn[<?php echo $i ?>]" value="<?php echo (int)$row['pt_turn'] ?>" 
                               min="0" style="width:60px; text-align:center;">
                    </td>
                    <td>
                        <div class="skill-checkbox-list">
                            <?php foreach ($mo_skill_list as $sk) { 
                                $sk_id = (int)$sk['sk_id'];
                                $is_checked = in_array((string)$sk_id, $pt_skills, true);
                                $display_name = !empty($sk['cs_name']) ? $sk['cs_name'] : $sk['sk_name'];
                                $display_icon = !empty($sk['cs_icon']) ? $sk['cs_icon'] : $sk['sk_icon'];
                            ?>
                                <label>
                                    <input type="checkbox" name="pt_skill[<?php echo $i ?>][]" value="<?php echo $sk_id ?>" <?php if ($is_checked) echo 'checked'; ?>>
                                    <?php if (!empty($display_icon)) { ?>
                                        <img src="<?php echo h($display_icon) ?>" alt="" class="sk-icon">
                                    <?php } ?>
                                    <?php echo get_text($display_name) ?>
                                </label>
                            <?php } ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <input type="number" name="pt_skill_cnt[<?php echo $i ?>]" value="<?php echo (int)$row['pt_skill_cnt'] ?>" 
                               min="0" style="width:60px; text-align:center;">
                    </td>
                </tr>
                <?php 
                        $i++;
                    }
                } else { ?>
                    <tr><td colspan="4" class="empty_table">등록된 패턴이 없습니다.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="btn_list01 btn_list">
            <input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
            <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
        </div>
    </form>
</section>

<section id="anc_002">
    <h2 class="h2_frm">패턴 등록</h2>
    <?php echo $pg_anchor ?>

    <form method="post" action="./982_k_mo_pattern_update.php" autocomplete="off">
        <input type="hidden" name="type" value="insert">
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
                    <th scope="row"><label for="ins_pt_turn">발동 턴</label></th>
                    <td>
                        <input type="number" name="pt_turn" id="ins_pt_turn" class="frm_input" value="0" min="0" style="width:100px;">
                        <span style="color:#888; margin-left:10px;">1 = 1턴, 2 = 2턴 ...</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">스킬 후보</th>
                    <td>
                        <div class="skill-checkbox-list">
                            <?php foreach ($mo_skill_list as $sk) { 
                                $display_name = !empty($sk['cs_name']) ? $sk['cs_name'] : $sk['sk_name'];
                                $display_icon = !empty($sk['cs_icon']) ? $sk['cs_icon'] : $sk['sk_icon'];
                            ?>
                                <label>
                                    <input type="checkbox" name="pt_skill[]" value="<?php echo (int)$sk['sk_id'] ?>">
                                    <?php if (!empty($display_icon)) { ?>
                                        <img src="<?php echo h($display_icon) ?>" alt="" class="sk-icon">
                                    <?php } ?>
                                    [<?php echo get_text($sk['si_type']) ?>] <?php echo get_text($display_name) ?>
                                </label>
                            <?php } ?>
                        </div>
                        <p style="margin-top:5px; color:#888; font-size:12px;">
                            * 해당 턴에 사용할 수 있는 스킬 후보들을 선택하세요.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="ins_pt_skill_cnt">사용 갯수</label></th>
                    <td>
                        <input type="number" name="pt_skill_cnt" id="ins_pt_skill_cnt" class="frm_input" value="1" min="0" style="width:100px;">
                        <span style="color:#888; margin-left:10px;">후보 중 실제로 사용할 스킬 갯수 (0 입력시 1로 조정)</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="btn_confirm01 btn_confirm">
            <input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
        </div>
    </form>
</section>

<section id="anc_003">
    <h2 class="h2_frm">패턴 시뮬레이션</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 150px;">
                <col>
            </colgroup>
            <tbody>
            <tr>
                <th scope="row"><label for="sim_turn">시뮬레이션 턴</label></th>
                <td>
                    <input type="number" id="sim_turn" class="frm_input" value="1" min="1" style="width:100px;">
                    <input type="button" id="btn_simulate" value="시뮬레이션 실행" class="btn_submit" style="margin-left:10px;">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="sim_turn_from">일괄 시뮬레이션</label></th>
                <td>
                    <input type="number" id="sim_turn_from" class="frm_input" value="1" min="1" style="width:70px;">
                    <span>~</span>
                    <input type="number" id="sim_turn_to" class="frm_input" value="20" min="1" style="width:70px;">
                    <span>턴</span>
                    <input type="button" id="btn_simulate_range" value="일괄 시뮬레이션" class="btn_submit" style="margin-left:10px;">
                </td>
            </tr>
            <tr>
                <th scope="row">결과</th>
                <td>
                    <div id="sim_result" style="min-height:100px; padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:5px;">
                        <span style="color:#888;">턴 수를 입력하고 시뮬레이션을 실행하세요.</span>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</section>

<script>
// 패턴 데이터
var $patternData = <?php echo json_encode($pattern_list); ?>;
var $moPattern = <?php echo (int)$current_pattern; ?>;
var $moSkillList = <?php echo json_encode($mo_skill_list); ?>;

// 스킬 ID로 스킬 정보 찾기
function getSkillInfo(skId) {
    skId = parseInt(skId);
    for (var i = 0; i < $moSkillList.length; i++) {
        if (parseInt($moSkillList[i].sk_id) === skId) {
            var sk = $moSkillList[i];
            sk.ms_name = sk.cs_name;
            sk.ms_icon = sk.cs_icon;
            return sk;
        }
    }
    return null;
}

// 배열 섞기
function shuffleArray(arr) {
    var newArr = arr.slice();
    for (var i = newArr.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = newArr[i];
        newArr[i] = newArr[j];
        newArr[j] = temp;
    }
    return newArr;
}

// 시뮬레이션 함수
function simulatePattern(turn) {
    turn = parseInt(turn);
    if (turn <= 0 || $patternData.length === 0) {
        return { patterns: [], skills: [], error: turn <= 0 ? '턴은 1 이상이어야 합니다.' : '등록된 패턴이 없습니다.' };
    }

    var matchedPatterns = [];
    var selectedSkillIds = [];

    if ($moPattern === 1) {
        //순차
        var maxTurn = 0;
        for (var i = 0; i < $patternData.length; i++) {
            var ptTurn = parseInt($patternData[i].pt_turn);
            if (ptTurn > maxTurn) maxTurn = ptTurn;
        }
        
        if (maxTurn <= 0) {
            return { patterns: [], skills: [], error: '유효한 패턴이 없습니다.' };
        }

        var effectiveTurn = ((turn - 1) % maxTurn) + 1;

        for (var i = 0; i < $patternData.length; i++) {
            if (parseInt($patternData[i].pt_turn) === effectiveTurn) {
                matchedPatterns.push({
                    pattern: $patternData[i],
                    effectiveTurn: effectiveTurn
                });
            }
        }
    } else {
        //조건
        for (var i = 0; i < $patternData.length; i++) {
            var ptTurn = parseInt($patternData[i].pt_turn);
            if (ptTurn > 0 && turn % ptTurn === 0) {
                matchedPatterns.push({
                    pattern: $patternData[i],
                    effectiveTurn: ptTurn
                });
            }
        }
    }

    for (var i = 0; i < matchedPatterns.length; i++) {
        var pt = matchedPatterns[i].pattern;
        var ptSkill = pt.pt_skill || '';
        var ptSkillCnt = parseInt(pt.pt_skill_cnt) || 1;

        if (!ptSkill) continue;

        var candidates = ptSkill.split(',').filter(function(v) { return parseInt(v) > 0; });
        if (candidates.length === 0) continue;

        ptSkillCnt = Math.max(1, Math.min(ptSkillCnt, candidates.length));

        var shuffled = shuffleArray(candidates);
        var selected = shuffled.slice(0, ptSkillCnt);

        for (var j = 0; j < selected.length; j++) {
            selectedSkillIds.push(parseInt(selected[j]));
        }
    }

    return {
        patterns: matchedPatterns,
        skills: selectedSkillIds,
        error: null
    };
}

function renderResult(turn, result) {
    if (result.error) {
        return '<div style="color:#d00;"><strong>오류:</strong> ' + result.error + '</div>';
    }

    if (result.patterns.length === 0) {
        return '<div style="color:#888;"><strong>' + turn + '턴:</strong> 발동하는 패턴이 없습니다.</div>';
    }

    var html = '<div style="margin-bottom:10px;"><strong style="color:#007bff;">' + turn + '턴 시뮬레이션 결과</strong>';
    if ($moPattern === 1 && result.patterns.length > 0) {
        html += ' <span style="color:#666; font-size:12px;">(적용 턴: ' + result.patterns[0].effectiveTurn + '턴)</span>';
    }
    html += '</div>';

    html += '<div style="margin-bottom:10px; padding:10px; background:#e8f4fc; border-radius:3px;">';
    html += '<strong>매칭된 패턴:</strong><br>';
    for (var i = 0; i < result.patterns.length; i++) {
        var pt = result.patterns[i].pattern;
        html += '- pt_turn=' + pt.pt_turn + ', 스킬후보=' + pt.pt_skill + ', 사용갯수=' + pt.pt_skill_cnt + '<br>';
    }
    html += '</div>';

    if (result.skills.length > 0) {
        html += '<div style="padding:10px; background:#e8fce8; border-radius:3px;">';
        html += '<strong>사용할 스킬 (랜덤 선택):</strong><br>';
        for (var i = 0; i < result.skills.length; i++) {
            var skInfo = getSkillInfo(result.skills[i]);
            if (skInfo) {
                var displayName = skInfo.cs_name || skInfo.sk_name;
                var displayIcon = skInfo.cs_icon || skInfo.sk_icon;
                html += '<span style="display:inline-block; margin:3px; padding:3px 8px; background:#fff; border:1px solid #ccc; border-radius:3px;">';
                if (displayIcon) {
                    html += '<img src="' + displayIcon + '" alt="" style="max-height:20px; vertical-align:middle; margin-right:3px;">';
                }
                html += '[' + (skInfo.si_type || '') + '] ' + displayName + ' (ID:' + result.skills[i] + ')';
                html += '</span>';
            } else {
                html += '<span style="display:inline-block; margin:3px; padding:3px 8px; background:#fff; border:1px solid #ccc; border-radius:3px;">스킬 ID: ' + result.skills[i] + '</span>';
            }
        }
        html += '</div>';
    } else {
        html += '<div style="color:#d00;">선택된 스킬이 없습니다.</div>';
    }

    return html;
}

// 단일
document.getElementById('btn_simulate').onclick = function() {
    var turn = parseInt(document.getElementById('sim_turn').value);
    var result = simulatePattern(turn);
    document.getElementById('sim_result').innerHTML = renderResult(turn, result);
};

// 일괄
document.getElementById('btn_simulate_range').onclick = function() {
    var fromTurn = parseInt(document.getElementById('sim_turn_from').value) || 1;
    var toTurn = parseInt(document.getElementById('sim_turn_to').value) || 20;
    
    if (fromTurn < 1) fromTurn = 1;
    if (toTurn < 1) toTurn = 1;
    if (fromTurn > toTurn) {
        var temp = fromTurn;
        fromTurn = toTurn;
        toTurn = temp;
    }
    
    if (toTurn - fromTurn > 100) {
        toTurn = fromTurn + 100;
        alert('최대 100턴까지만 시뮬레이션할 수 있습니다.');
    }
    
    var html = '<div style="max-height:400px; overflow-y:auto;">';
    for (var turn = fromTurn; turn <= toTurn; turn++) {
        var result = simulatePattern(turn);
        html += '<div style="margin-bottom:15px; padding-bottom:15px; border-bottom:1px dashed #ccc;">';
        html += renderResult(turn, result);
        html += '</div>';
    }
    html += '</div>';
    document.getElementById('sim_result').innerHTML = html;
};

document.getElementById('sim_turn').onkeypress = function(e) {
    if (e.keyCode === 13 || e.which === 13) {
        document.getElementById('btn_simulate').click();
        return false;
    }
};
</script>

<?php } ?>

<?php } else { ?>
<div style="text-align:center; padding:50px; color:#888;">
    <p style="font-size:18px;">몬스터를 선택하면 패턴을 관리할 수 있습니다.</p>
</div>
<?php } ?>

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
