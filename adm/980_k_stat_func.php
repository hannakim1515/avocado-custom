<?php
include_once('./_common.php');
if(!isset($page)||$page<1) $page = 1;
// 입력 기본값
$category = ses($_REQUEST, 'category', '');
if ($category === 'stat') {
    $sub_menu = '980110';
    $title = '커스텀 스탯';
} elseif ($category === 'battle') {
    $sub_menu = '980120';
    $title = '전투 함수';
} else {
    // 기본값
    $sub_menu = '980110';
    $title = '커스텀 스탯';
}

$token = get_token();

// 공통 쿼리
$sql_common = " FROM {$g5['k_stat_table']}";
$sql_search = " WHERE sc_category='" . sql_escape_string($category) . "'";

// 전체 카운트
$sql = "SELECT COUNT(*) AS cnt {$sql_common} {$sql_search}";
$row = sql_fetch($sql);
$total_count = ses($row, 'cnt', 0, 'int');

// 목록
$sql = "SELECT * {$sql_common} {$sql_search}";
$result = sql_query($sql);

// 전체목록 링크
$self = ses($_SERVER, 'PHP_SELF', '');

$g5['title'] = $title.' 설정';
include_once('./admin.head.php');
include_once('./admin.stat.php'); 

// 샘플 유닛/타겟 분해
$sample_unit_str  = ses($kb_cf, 'sample_unit', '');
$sample_target_str = ses($kb_cf, 'sample_target', '');
$sample_unit  = ($sample_unit_str !== '') ? explode('|', $sample_unit_str) : array();
$sample_target = ($sample_target_str !== '') ? explode('|', $sample_target_str) : array();

// kb_cf 값 미리 추출 (hp/mp 설정 폼용)
$h_hp_name = h(ses($kb_cf, 'hp_name', ''));
$h_mp_name = h(ses($kb_cf, 'mp_name', ''));
$kb_hp = ses($kb_cf, 'hp', '');
$kb_mp = ses($kb_cf, 'mp', '');
$kb_speed = ses($kb_cf, 'speed', '');

// 상태 설정 불러오기
$status = array();
$default_stat = array();
$st_name = array();
$is = array(); // battle 모드에서 존재 체크용
$default_unit = array();
$default_target = array();
$unit = array();
$target = array();
$k_stat_name = array();
$sc_list = array(); // speed 선택용

$st_result = sql_query("SELECT * FROM {$g5['status_config_table']} ORDER BY st_order ASC");
for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
    $default_stat[] = $row;
    $st_tag = 'st_' . ($i + 1);
    if (isset($sample_unit[$i]) && $sample_unit[$i] !== '') {
        $default_unit[$st_tag] = $sample_unit[$i];
    }
    if (isset($sample_target[$i]) && $sample_target[$i] !== '') {
        $default_target[$st_tag] = $sample_target[$i];
    }
}

if ($category === 'stat') {
    // stat 모드
    $status = $default_stat;
    foreach ($default_stat as $st_row) {
        $st_name[$st_row['st_id']] = $st_row['st_name'];
    }
    
    // 스탯 목록 (speed 선택용)
    $sc_sql = sql_query("SELECT * FROM {$g5['k_stat_table']} WHERE sc_category='stat'");
    for ($i = 0; $row = sql_fetch_array($sc_sql); $i++) {
        $sc_list[] = $row;
    }
} else {
    // battle 모드: k_stat_table 전체에서 stat과 battle 함수 구분
    $st_result = sql_query("SELECT * FROM {$g5['k_stat_table']}");
    $h = 1;
    for ($i = 0; $row = sql_fetch_array($st_result); $i++) {
        if ($row['sc_category'] === 'stat') {
            // stat을 status처럼 취급
            $row['st_id'] = $row['sc_id'];
            $row['st_name'] = $row['sc_name'];
            $status[] = $row;
            $st_name[$row['st_id']] = $row['st_name'];

            $tag = 'st_' . $h;
            if ($sample_unit_str !== '') {
                $unit[$tag] = get_k_status($default_unit, $row['sc_id'], 'test');
            }
            if ($sample_target_str !== '') {
                $target[$tag] = get_k_status($default_target, $row['sc_id'], 'test');
            }
            $k_stat_name[$tag] = $row['sc_name'];
            $h++;
        } else {
            // battle 함수 존재 체크
            $is[$row['sc_name']]['sc_id'] = $row['sc_id'];
        }
    }
}
?>
<style>
.f_in{display:flex;padding:0;align-items:center;flex-wrap:wrap;}
.f_in .ui-btn{list-style:none;padding:0 10px;margin:0 3px;background-color:lightblue;min-width:40px;font-weight:900;border:1px solid skyblue;cursor:pointer;height:20px;}
.f_in .ui-btn.stat_r{background-color:navy;color:white;font-weight:300;}
<?php if ($category === 'battle') { ?>
.f_in .ui-btn:before{content:'내 ';}
<?php } ?>
.f_in .ui-btn.stat_r:before,.f_in .ui-btn.func_r:before{content:'상대 ';}
.f_in .ui-btn.cons:before,.f_in .ui-btn.math:before{content:"";}
.f_in .ui-btn.math{min-width:0px;background-color:lightpink;border:1px solid pink;}
.f_in .ui-btn.func_m,.f_in .ui-btn.func_r{background-color:lightgreen;border:1px solid green;}
.f_in .ui-btn.func_r{background-color:darkgreen;color:white;font-weight:300;}
.f_in .ui-btn.math.open,.f_in .ui-btn.math.close{background-color:transparent;border-color:transparent;}
.f_in .ui-btn.cons{background-color:palegoldenrod;border:1px solid gold;}
.depth{display:flex;align-items:center;padding:3px;background-color:#00000022;}
.disabled *{filter:grayscale(1);pointer-events:none!important;}
</style>

<?php if ($category === 'stat') { ?>
<h2 class="h2_frm">hp/mp 설정</h2>
<form method="post" action="./980_k_stat_func_update.php?category=<?php echo urlencode($category); ?>">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 100px;" />
                <col />
            </colgroup>
            <thead>
                <tr>
                    <th scope="col" colspan="2">hp/mp 등록</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" name="hp_name" value="<?php echo $h_hp_name; ?>"></td>
                    <td style="text-align:left;">
                        <select name="hp">
                            <option value="">선택</option>
                            <?php for ($i = 0; $i < count($status); $i++) { ?>
                                <option value="<?php echo (int)$status[$i]['st_id']; ?>" <?php if ((string)$status[$i]['st_id'] === $kb_hp) echo 'selected'; ?>>
                                    <?php echo get_text($status[$i]['st_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><input type="text" name="mp_name" value="<?php echo $h_mp_name; ?>"></td>
                    <td style="text-align:left;">
                        <select name="mp">
                            <option value="">선택</option>
                            <?php for ($i = 0; $i < count($status); $i++) { ?>
                                <option value="<?php echo (int)$status[$i]['st_id']; ?>" <?php if ((string)$status[$i]['st_id'] === $kb_mp) echo 'selected'; ?>>
                                    <?php echo get_text($status[$i]['st_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>속도</td>
                    <td style="text-align:left;">
                        <select name="speed">
                            <option value="">선택</option>
                            <?php for ($i = 0; $i < count($sc_list); $i++) { ?>
                                <option value="<?php echo (int)$sc_list[$i]['sc_id']; ?>" <?php if ((string)$sc_list[$i]['sc_id'] === $kb_speed) echo 'selected'; ?>>
                                    <?php echo get_text($sc_list[$i]['sc_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="업데이트" onclick="document.pressed=this.value">
    </div>
</form>
<?php } ?>

<h2 class="h2_frm"><?php echo get_text($title); ?> 등록</h2>

<?php
// 등록 폼 노출 여부: battle 모드에서 미등록 함수가 있는지 확인
$show_register = true;
if ($category === 'battle') {
    $show_register = false;
    if (isset($func_list) && is_array($func_list)) {
        foreach ($func_list as $fl) {
            $code = ses($fl, 'code', '');
            if ($code !== '' && empty($is[$code]['sc_id'])) {
                $show_register = true;
                break;
            }
        }
    }
}
if ($category === 'stat' || $show_register) {
?>
<form method="post" action="./980_k_stat_func_update.php?category=<?php echo urlencode($category); ?>" onsubmit="return f_submit(this);">
    <div class="tbl_head01 tbl_wrap <?php echo ($total_count > 9 && $category === 'stat') ? 'disabled' : ''; ?>">
        <table>
            <colgroup>
                <col style="width: 150px;" />
                <col />
            </colgroup>
            <tbody>
                <thead>
                    <tr>
                        <th scope="col" colspan="2">등록</th>
                    </tr>
                </thead>
                <tr>
                    <td>명칭</td>
                    <td class="txt-left">
                        <?php if ($category === 'stat') { ?>
                            <input type="text" name="sc_name">
                            <input type="hidden" value="normal" id="result_type">
                        <?php } elseif ($category === 'battle') { ?>
                            <select name="sc_name" onchange="change_type(this.value);">
                                <option value="">선택</option>
                                <?php
                                if (isset($func_list) && is_array($func_list)) {
                                    for ($i = 0; $i < count($func_list); $i++) {
                                        if (empty($is[$func_list[$i]['code']]['sc_id'])) { ?>
                                            <option value="<?php echo h($func_list[$i]['code']); ?>">
                                                <?php echo get_text($func_list[$i]['name']); ?>
                                            </option>
                                <?php   }
                                    }
                                } ?>
                            </select>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo ($category === 'battle') ? '커스텀 ' : ''; ?>스탯</td>
                    <td>
                        <ul class="f_in">
                            <?php for ($i = 0; $i < count($status); $i++) { ?>
                                <li class="ui-btn" onclick="f_in('stat',<?php echo (int)$status[$i]['st_id']; ?>,'<?php echo h($status[$i]['st_name']); ?>')">
                                    <?php echo get_text($status[$i]['st_name']); ?>
                                </li>
                                <?php if ($category === 'battle') { ?>
                                    <li class="ui-btn stat_r" onclick="f_in('stat_r',<?php echo (int)$status[$i]['st_id']; ?>,'<?php echo h($status[$i]['st_name']); ?>')">
                                        <?php echo get_text($status[$i]['st_name']); ?>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>연산자</td>
                    <td>
                        <ul class="f_in">
                            <li class="ui-btn math"  onclick="f_in('math','+');">+</li>
                            <li class="ui-btn math"  onclick="f_in('math','-');">-</li>
                            <li class="ui-btn math"  onclick="f_in('math','*');">*</li>
                            <li class="ui-btn math"  onclick="f_in('math','/');">/</li>
                            <li class="ui-btn math open"  onclick="f_in('math','(');">(</li>
                            <li class="ui-btn math close" onclick="f_in('math',')');">)</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>상수</td>
                    <td class="f_in">
                        <input type="text" value="" id="cons"> <span class="ui-btn cons" onclick="f_in('cons');">입력</span>
                    </td>
                </tr>
                <tr>
                    <td>결과</td>
                    <td>
                        <ul class="f_in result" id="normal"></ul>
                    </td>
                </tr>
                <tr>
                    <td>보정</td>
                    <td style="text-align:left;">
                        <p><span>최소값</span><input type="text" name="min_value" placeholder="정수">~최대값<input type="text" name="max_value" placeholder="정수"></p>
                        <p><span>소수점</span>
                            <select name="round">
                                <option value="round">반올림</option>
                                <option value="ceil">올림</option>
                                <option value="floor">내림</option>
                            </select>
                        </p>
                        <?php if ($category === 'battle') { ?>
                            <p id="cri">
                                <span>치명타사용</span>
                                <select name="use_cri">
                                    <option value="0">미사용</option>
                                    <option value="1">사용</option>
                                </select>
                            </p>
                            <p>
                                <span>랜덤보정</span> 최종값 +-<input type="text" name="rand" placeholder="정수">
                            </p>
                        <?php } ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
    </div>
</form>
<?php } ?>

<h2 class="h2_frm"><?php echo get_text($title); ?> 목록</h2>
<form method="post" action="./980_k_stat_func_update.php?category=<?php echo urlencode($category); ?>">
    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 100px;" />
                <?php
                for ($i = 0; $i < count($default_stat); $i++) {
                    echo '<col>';
                }
                if ($category === 'battle') {
                    foreach ($unit as $k => $v) {
                        echo '<col>';
                    }
                }
                ?>
            </colgroup>
            <thead>
                <tr>
                    <th scope="col"></th>
                    <?php
                    for ($i = 0; $i < count($default_stat); $i++) {
                        echo '<th scope="col">'.get_text($default_stat[$i]['st_name']).'</th>';
                    }
                    if ($category === 'battle') {
                        foreach ($unit as $key => $value) {
                            echo '<th scope="col">'.get_text($k_stat_name[$key]).'</th>';
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>유닛 샘플</td>
                    <?php
                    for ($i = 0; $i < count($default_stat); $i++) {
                        $st_tag = 'st_' . ($i + 1);
                        $v = ses($default_unit, $st_tag, '');
                        echo '<td><input type="text" value="'.h($v).'" name="sample_unit[]"></td>';
                    }
                    if ($category === 'battle') {
                        foreach ($unit as $value) {
                            echo '<td>'.h($value).'</td>';
                        }
                    }
                    ?>
                </tr>
                <?php if ($category === 'battle') { ?>
                    <tr>
                        <td>타겟 샘플</td>
                        <?php
                        for ($i = 0; $i < count($default_stat); $i++) {
                            $st_tag = 'st_' . ($i + 1);
                            $v = ses($default_target, $st_tag, '');
                            echo '<td><input type="text" value="'.h($v).'" name="sample_target[]"></td>';
                        }
                        foreach ($target as $value) {
                            echo '<td>'.h($value).'</td>';
                        }
                        ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="샘플등록" onclick="document.pressed=this.value">
    </div>
</form>

<br>

<form name="forderlist" id="forderlist" method="post" action="./980_k_stat_func_update.php?category=<?php echo urlencode($category); ?>" onsubmit="return forderlist_submit(this);">
    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
    <input type="hidden" name="token" value="<?php echo $token; ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <colgroup>
                <col style="width: 50px;" />
                <col style="width: 50px;" />
                <col style="width: 150px;" />
                <col />
                <col style="width: 150px;" />
                <col style="width: 100px;" />
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col">ID</th>
                    <th scope="col">명칭</th>
                    <th scope="col">내용</th>
                    <th scope="col">보정</th>
                    <th scope="col">샘플</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    $bg = 'bg' . ($i % 2);
                    $val  = explode('|', (string)$row['sc_value']);
                    $type = explode('|', (string)$row['sc_type']);
                    ?>
                    <tr class="<?php echo $bg; ?>">
                        <td>
                            <input type="checkbox" name="chk[]" value="<?php echo $i; ?>" id="chk_<?php echo $i; ?>">
                            <input type="hidden" name="sc_id[<?php echo $i; ?>]" value="<?php echo (int)$row['sc_id']; ?>" />
                        </td>
                        <td><?php echo get_text($row['sc_id']); ?></td>
                        <td><?php echo get_text($row['sc_name']); ?></td>
                        <td>
                            <ul class="f_in">
                                <?php
                                $cnt = min(count($val), count($type));
                                for ($h = 0; $h < $cnt; $h++) {
                                    $inner = '';
                                    $type2 = '';
                                    $div1 = '';
                                    $div2 = '';

                                if ($type[$h] === 'stat' || $type[$h] === 'stat_r') {
                                    $key = ses($val, $h, '');
                                    $inner = ses($st_name, $key, $key);
                                } else {
                                    $inner = $val[$h];
                                }                                    if ($val[$h] === '(') {
                                        $div1 = "<div class='depth'>";
                                        $type2 = 'open';
                                    } elseif ($val[$h] === ')') {
                                        $type2 = 'close';
                                        $div2 = "</div>";
                                    }

                                    echo $div1 . "<li class='ui-btn " . h($type[$h]) . " " . $type2 . "'>" . get_text($inner) . "</li>" . $div2;
                                }
                                ?>
                            </ul>
                        </td>
                        <td style="text-align:left;">
                            <p>최소값 <?php echo h($row['sc_2']); ?> ~ 최대값 <?php echo h($row['sc_3']); ?></p>
                            <p><span>소수점</span>
                            <?php
                                if ($row['sc_1'] === 'round') {
                                    echo '반올림';
                                } elseif ($row['sc_1'] === 'ceil') {
                                    echo '올림';
                                } elseif ($row['sc_1'] === 'floor') {
                                    echo '내림';
                                }
                            ?>
                            </p>
                            <?php if ($category === 'battle') { ?>
                                <p>
                                    <span>치명타</span>
                                    <?php echo !empty($row['sc_4']) ? '사용' : '미사용'; ?>
                                </p>
                                <p>
                                    <span>랜덤보정</span> 최종값 +- <?php echo h($row['sc_5']); ?>
                                </p>
                            <?php } ?>
                        </td>
                        <td>
                            <?php
                            if ($category === 'stat') {
                                echo h(get_k_status($default_unit, $row['sc_id'], 'test'));
                            } else {
                                $value = get_k_battle_func($row['sc_name'], $unit, $target);
                                if (!empty($value['cri'])) {
                                    echo '크리티컬<br>';
                                }
                                echo h(ses($value, 'value', ''));
                            }
                            ?>
                        </td>
                    </tr>
                <?php } // for ?>
                <?php
                if (!isset($i) || $i === 0) {
                    echo '<tr><td colspan="6" class="empty_table">자료가 없습니다.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="btn_list01 btn_list">
        <input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
    </div>
</form>

<?php
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, 1, "{$self}?{$qstr}&amp;page=");
?>

<script>
function f_submit(f){
    var msg = '';
    var li = document.querySelectorAll(".result#normal li").length;
    if(li === 0){
        msg = '수식을 등록해 주세요.';
    }else{
        var last = document.querySelector(".result#normal li:last-child");
        if(last && last.classList.contains('math') && !last.classList.contains('close')){
            msg = '수식은 연산자로 종료될 수 없습니다.';
        }else{
            var open = document.querySelectorAll(".result#normal li.open").length;
            var close = document.querySelectorAll(".result#normal li.close").length;
            if(open !== close){
                msg = '괄호가 잘못되었습니다. 체크해 주세요.';
            }
        }
    }
    if(msg){
        alert(msg);
        return false;
    }
    return true;
}

function f_in(type, value='', html=''){
    var result = 'normal';
    var blank = '';

    if(type === 'cons'){
        value = document.getElementById('cons').value;
    }
    if(!value){ return false; }
    if(!html){ html = value; }

    if(!in_check(type, value, html, result)){ return false; }

    if(html === '('){ blank = 'open'; }
    else if(html === ')'){ blank = 'close'; }

    var li = document.createElement('li');
    li.className = 'ui-btn ' + type + ' ' + blank;
    li.setAttribute('onclick', 'this.remove()');

    var in1 = document.createElement('input');
    in1.type = 'hidden';
    in1.name = 'value_' + result + '[]';
    in1.value = value;

    var in2 = document.createElement('input');
    in2.type = 'hidden';
    in2.name = 'type_' + result + '[]';
    in2.value = type;

    li.appendChild(in1);
    li.appendChild(in2);
    li.appendChild(document.createTextNode(html));

    document.querySelector('.result#'+result).appendChild(li);
}

function in_check(type, value, html, result){
    var msg = '';
    var last = document.querySelector(".result#"+result+" li:last-child");
    var number = (type !== 'math');

    if(!last){
        if(type === 'math' && html !== '('){
            msg = '스탯 또는 상수로 시작해야 합니다.';
        }
    }else{
        if(number || html === '('){
            if(!last.classList.contains('math') || last.classList.contains('close')){
                msg = '연산자가 들어갈 자리입니다.';
            }
        }else if(!number && html !== '('){
            if(html === ')'){
                if(last.classList.contains('open')){
                    msg = '괄호를 바로 닫을 수 없습니다.';
                }else{
                    var open = document.querySelectorAll(".result#"+result+" li.open").length;
                    var close = document.querySelectorAll(".result#"+result+" li.close").length;
                    if(open <= close){
                        msg = '괄호가 잘못되었습니다. 체크해 주세요.';
                    }
                }
            }else if(last.classList.contains('math') && !last.classList.contains('close')){
                msg = '상수 또는 스탯치가 들어갈 자리입니다.';
            }
        }
    }
    if(msg){
        alert(msg);
        return false;
    }
    return true;
}

function change_type(val){
    var el = document.querySelector(".result#normal");
    if(el){ el.innerHTML = ''; }
    var cri = document.getElementById('cri');
    if(!cri) return;

    if(val === 'crival' || val === 'criper'){
        cri.classList.add('disabled');
        var sel = cri.querySelector('select');
        if(sel) sel.value = '0';
    }else{
        cri.classList.remove('disabled');
    }
}
</script>

<?php include_once('./admin.tail.php'); ?>
