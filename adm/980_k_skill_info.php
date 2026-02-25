<?php
// AJAX 호출 시에만 _common.php 로드 (include 방식일 때는 이미 로드됨)
if (!defined('_GNUBOARD_')) {
    include_once('./_common.php');
}

// 입력 방어: include 시 $row/$sk 변수 우선, 없으면 GET 파라미터
if (!isset($sk) || empty($sk['sk_id'])) {
    // include 방식: $row 변수가 있으면 그것을 $sk로 사용
    if (isset($row) && !empty($row['sk_id'])) {
        $sk = $row;
    } else {
        // AJAX 방식: GET 파라미터로 조회
        $type = ses($_GET, 'type', 0, 'int');
        $sk_id = ses($_GET, 'sk_id', 0, 'int');
        $sk = get_k_skill($sk_id, $type);
    }
}

if (empty($sk['sk_id'])) {
    echo '스킬 정보를 찾을 수 없습니다.';
    return;
}

// 계산기호 매핑
$default_calc_map = array('p' => '+', 'm' => '*', 'i' => '계산시');
$bonus_calc_map   = array('p' => '+', 'm' => '*');

$default_calc = isset($sk['default_calc'], $default_calc_map[$sk['default_calc']]) ? $default_calc_map[$sk['default_calc']] : '';
$bonus_calc   = isset($sk['bonus_calc'], $bonus_calc_map[$sk['bonus_calc']]) ? $bonus_calc_map[$sk['bonus_calc']] : '';

$base_label = '';
if ($default_calc !== '') {
    $base_label = ($sk['si_type'] === '공격') ? '기본 공격력 ' : '기본 치유력 ';
}

// 능력치명 조회
$sc_name = '0';
if (!empty($sk['sc_id'])) {
    $sc_id = (int)$sk['sc_id'];
    $row = sql_fetch("SELECT sc_name FROM {$g5['k_stat_table']} WHERE sc_id = {$sc_id}");
    if (!empty($row['sc_name'])) {
        $sc_name = $row['sc_name'];
    }
}

// 대상 처리
$target_txt = '';
$target_cnt = '';
switch (isset($sk['sk_target']) ? $sk['sk_target'] : '') {
    case 'passive':
        $target_txt = '패시브';
        break;
    case 'single':
        $target_txt = '본인';
        break;
    case 'ally':
        $target_txt = '아군';
        $target_cnt = isset($sk['sk_target_cnt']) ? $sk['sk_target_cnt'] : '';
        break;
    case 'enemy':
        $target_txt = '적군';
        $target_cnt = isset($sk['sk_target_cnt']) ? $sk['sk_target_cnt'] : '';
        break;
}
if ($target_cnt === 'single') { $target_txt .= ' 단일'; }
elseif ($target_cnt === 'all') { $target_txt .= ' 전체'; }
?>
<p><?php echo get_text($sk['sk_info']); ?></p>
<p><?php echo get_text($sk['sk_content']); ?></p>

<p>적용값:
    <?php
    if ($default_calc !== '') {
        echo $base_label . $default_calc . ' (';
    }

    echo get_text($sc_name) . ' ';

    echo ($bonus_calc !== '' ? $bonus_calc : '') . ' ' . h($sk['sk_value']);

    if ($default_calc !== '') {
        echo ')';
    }
    ?>
</p>

<p>대상: <?php echo $target_txt !== '' ? $target_txt : '미지정'; ?></p>

<p>mp: <?php echo h(isset($sk['sk_mp']) ? $sk['sk_mp'] : 0); ?>
    소모 / 쿨타임: <?php echo h(isset($sk['sk_cool']) ? $sk['sk_cool'] : 0); ?>
    / 지속: <?php echo h(isset($sk['sk_turn']) ? $sk['sk_turn'] : 0); ?>턴
</p>
