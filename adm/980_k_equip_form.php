<?php
$sub_menu = "980300";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

$it_id = ses($_GET, 'it_id', ses($_POST, 'it_id', 0, 'int'), 'int');
$qstr = isset($_GET['qstr']) ? $_GET['qstr'] : '';

$html_title = '장비';
$required = "";
$readonly = "";

if ($w == '') {
    $html_title .= ' 생성';
    $required = 'required';
    $sound_only = '<strong class="sound_only">필수</strong>';
    $item = array(
        'it_use' => 'Y'
    );
} else if ($w == 'u') {
    $html_title .= ' 수정';
    $item = sql_fetch("select * from {$g5['item_table']} where it_id = '{$it_id}'");
    if (!isset($item['it_id']) || !$item['it_id'])
        alert('존재하지 않는 장비 입니다.');
    $readonly = 'readonly';
} else {
    $item = array();
}

$g5['title'] = $html_title;
include_once('./admin.head.php');

$pg_anchor = '<ul class="anchor"><li><a href="#anc_001">기본 설정</a></li>';
if (isset($config['cf_4']) && $config['cf_4']) {
    $pg_anchor .= '<li><a href="#anc_002">탐색 설정</a></li>';
}
$pg_anchor .= '</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
<input type="submit" value="확인" class="btn_submit" accesskey="s">
<a href="./980_k_equip_list.php?' . (isset($qstr) ? $qstr : '') . '">목록</a>' . PHP_EOL . '</div>';

$st_list = array();
$stat_sql = sql_query("select st_id, st_name from {$g5['status_config_table']} order by st_order asc");
for ($i = 0; $row = sql_fetch_array($stat_sql); $i++) {
    $st_list[] = $row;
}

$lv_list = array();
$lv_sql = sql_query("select ug_id, ug_name from {$g5['k_upgrade_table']}");
for ($i = 0; $row = sql_fetch_array($lv_sql); $i++) {
    $lv_list[] = $row;
}

$equip_type_raw = '';
if (isset($kb_cf['equip_type'])) $equip_type_raw = $kb_cf['equip_type'];
$eq_list = $equip_type_raw != '' ? explode("|", $equip_type_raw) : array();
?>

<form action="./980_k_equip_form_update.php" onsubmit="return f_submit(this)" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="it_id" value="<?php echo $it_id; ?>">
<input type="hidden" name="page" value="<?php echo $page; ?>">
<input type="hidden" name="qstr" value="<?php echo $qstr; ?>">

<section id="anc_001">
    <h2 class="h2_frm">장비 기본 설정</h2>
    <?php echo $pg_anchor; ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <tbody>
                <tr>
                    <th scope="row">사용여부</th>
                    <td colspan="2">
                        <input type="checkbox" name="it_use" id="it_use" value="Y" <?php if (isset($item['it_use']) && $item['it_use']=='Y') echo "checked"; ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">장비 이름</th>
                    <td colspan="2">
                        <input type="text" name="it_name" value="<?php echo isset($item['it_name']) ? get_text($item['it_name']) : ''; ?>" id="it_name" required class="required" size="50" maxlength="120">
                    </td>
                </tr>
                <tr>
                    <th scope="row" rowspan="2">장비 이미지</th>
                    <td rowspan="2" class="bo-right">
                        <?php if (isset($item['it_img']) && $item['it_img']) { ?>
                            <img src="<?php echo $item['it_img']; ?>">
                        <?php } else { ?>이미지 없음<?php } ?>
                    </td>
                    <td>직접등록 <input type="file" name="it_img_file" value="" size="50"></td>
                </tr>
                <tr>
                    <td>외부경로 <input type="text" name="it_img" value="<?php echo isset($item['it_img']) ? $item['it_img'] : ''; ?>" size="50"></td>
                </tr>
                <tr>
                    <th scope="row" rowspan="2">상세이미지</th>
                    <td rowspan="2" class="bo-right">
                        <?php if (isset($item['it_1']) && $item['it_1']) { ?>
                            <img src="<?php echo $item['it_1']; ?>">
                        <?php } else { ?>이미지 없음<?php } ?>
                    </td>
                    <td>직접등록 <input type="file" name="it_1_file" value="" size="50"></td>
                </tr>
                <tr>
                    <td>외부경로 <input type="text" name="it_1" value="<?php echo isset($item['it_1']) ? $item['it_1'] : ''; ?>" size="50"></td>
                </tr>
                <tr>
                    <th scope="row">장비 설명</th>
                    <td colspan="2">
                        <input type="text" name="it_content" value="<?php echo isset($item['it_content']) ? get_text($item['it_content']) : ''; ?>" id="it_content" required class="required" size="80">
                    </td>
                </tr>
                <tr>
                    <th scope="row">장비 효과</th>
                    <td colspan="2">
                        <input type="text" name="it_content2" value="<?php echo isset($item['it_content2']) ? get_text($item['it_content2']) : ''; ?>" id="it_content2" size="80">
                    </td>
                </tr>
                <tr>
                    <th scope="row">장비 타입</th>
                    <td>
                        <select name="eq_type">
                            <?php for ($h=0;$h<count($eq_list);$h++){ ?>
                                <option value="<?php echo $eq_list[$h]; ?>" <?php if (isset($item['eq_type']) && $item['eq_type']==$eq_list[$h]) echo 'selected'; ?>><?php echo $eq_list[$h]; ?></option>
                            <?php } ?>
                            <option value="" <?php if (!isset($item['eq_type'])||!$item['eq_type']) echo 'selected'; ?>>미설정</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">기본스탯</th>
                    <td>
                        <select name="st_id">
                            <?php for ($h=0;$h<count($st_list);$h++){ ?>
                                <option value="<?php echo $st_list[$h]['st_id']; ?>" <?php if (isset($item['st_id']) && $item['st_id']==$st_list[$h]['st_id']) echo 'selected'; ?>><?php echo $st_list[$h]['st_name']; ?></option>
                            <?php } ?>
                        </select>
                        <input type="text" name="it_value" value="<?php echo isset($item['it_value']) ? $item['it_value'] : ''; ?>" size="15">
                    </td>
                </tr>
                <tr>
                    <th scope="row">강화제한</th>
                    <td>
                        <select name="ug_limit">
                            <option value="">제한없음</option>
                            <option value="9999" <?php if (isset($item['ug_limit']) && $item['ug_limit']==9999) echo 'selected'; ?>>강화불가</option>
                            <?php for ($h=0;$h<count($lv_list);$h++){ ?>
                                <option value="<?php echo $lv_list[$h]['ug_id']; ?>" <?php if (isset($item['ug_limit']) && $item['ug_limit']==$lv_list[$h]['ug_id']) echo 'selected'; ?>><?php echo $lv_list[$h]['ug_name']; ?></option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">강화수치</th>
                    <td>
                        <select name="it_2">
                            <option value="0" <?php if (!isset($item['it_2']) || !$item['it_2']) echo 'selected'; ?>>일반</option>
                            <option value="1" <?php if (isset($item['it_2']) && $item['it_2']) echo 'selected'; ?>>커스텀</option>
                        </select>
                    </td>
                </tr>
               <?php if (!empty($item['it_2'])): ?>
                <tr>
                    <th scope="row">커스텀수치</th>
                    <td>
                        <?php
                        // 값 존재 여부 체크 후 explode
                        $custom_ug_st  = !empty($item['it_3']) ? explode('|', $item['it_3']) : array();
                        $custom_ug_min = !empty($item['it_4']) ? explode('|', $item['it_4']) : array();
                        $custom_ug_max = !empty($item['it_5']) ? explode('|', $item['it_5']) : array();

                        // 업그레이드 목록 조회
                        $sql = "select ug_id, ug_name from {$g5['k_upgrade_table']}";
                        if (!empty($item['ug_limit']) && (int)$item['ug_limit'] !== 9999) {
                            $sql .= " where ug_id <= " . (int)$item['ug_limit'];
                        }

                        $ug_list = sql_query($sql);

                        // 스탯 리스트 count 경고 방지
                        $st_count = is_array($st_list) ? count($st_list) : 0;

                        for ($i = 0; $row = sql_fetch_array($ug_list); $i++):
                            $st_value  = isset($custom_ug_st[$i])  ? $custom_ug_st[$i]  : '';
                            $min_value = isset($custom_ug_min[$i]) ? $custom_ug_min[$i] : '';
                            $max_value = isset($custom_ug_max[$i]) ? $custom_ug_max[$i] : '';
                        ?>
                            <p style="display:flex;">
                                <span style="min-width:80px;"><?php echo h($row['ug_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <select name="custom_ug_st[<?php echo $i; ?>]">
                                    <?php
                                    for ($h = 0; $h < $st_count; $h++):
                                        // 최대 10개까지만
                                        if ($h === 9) {
                                            break;
                                        }
                                        $selected = ($st_value == $st_list[$h]['st_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo h($st_list[$h]['st_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected; ?>>
                                            <?php echo h($st_list[$h]['st_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endfor; ?>
                                    <option value="99" <?php echo ($st_value == 99 ? 'selected' : ''); ?>>랜덤</option>
                                </select>
                                <input type="text"
                                    name="custom_ug_min[<?php echo $i; ?>]"
                                    value="<?php echo h($min_value, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="최소">
                                ~
                                <input type="text"
                                    name="custom_ug_max[<?php echo $i; ?>]"
                                    value="<?php echo h($max_value, ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="최대">
                            </p>
                        <?php endfor; ?>
                    </td>
                </tr>
            <?php endif; ?>

                <tr>
                    <th scope="row">되팔기 설정</th>
                    <td>사용여부</td>
                    <td>
                        <input type="checkbox" name="it_use_sell" value="1" <?php if (isset($item['it_use_sell']) && $item['it_use_sell']=='1') echo 'checked'; ?>> 판매가능
                    </td>
                </tr>
                <tr>
                    <td>가격</td>
                    <td><input type="text" name="it_sell" value="<?php echo isset($item['it_sell']) ? $item['it_sell'] : ''; ?>" size="10"></td>
                </tr>
                <tr>
                    <th scope="row">아이템 속성</th>
                    <td colspan="2">
                        <input type="checkbox" name="it_has" value="1" <?php if (isset($item['it_has']) && $item['it_has']=='1') echo 'checked'; ?>>귀속성
                        <input type="checkbox" name="it_use_recepi" value="1" <?php if (isset($item['it_use_recepi']) && $item['it_use_recepi']=='1') echo 'checked'; ?>>레시피 재료 사용
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<?php if (isset($config['cf_4']) && $config['cf_4']) { ?>
<section id="anc_002">
    <h2 class="h2_frm">탐색설정</h2>
    <?php echo $pg_anchor; ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <tbody>
                <tr>
                    <th scope="row">탐색사용여부</th>
                    <td>
                        <input type="checkbox" name="it_seeker" value="1" <?php if (isset($item['it_seeker']) && $item['it_seeker']=='1') echo 'checked'; ?>> 탐색 시 획득 가능
                    </td>
                </tr>
                <tr>
                    <th scope="row">획득 구간</th>
                    <td>
                        <input type="text" name="it_seeker_per_s" value="<?php echo isset($item['it_seeker_per_s']) ? $item['it_seeker_per_s'] : ''; ?>" size="5"> ~
                        <input type="text" name="it_seeker_per_e" value="<?php echo isset($item['it_seeker_per_e']) ? $item['it_seeker_per_e'] : ''; ?>" size="5">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
<?php echo $frm_submit; ?>
<?php } ?>
</form>

<script>
function f_submit(f){return true;}
</script>

<?php include_once('./admin.tail.php'); ?>
