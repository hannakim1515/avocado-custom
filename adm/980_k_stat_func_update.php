<?php
include_once('./_common.php');

$type = ses($_REQUEST, 'type', '', 'raw');

if ($type === 'stat') {
    $sub_menu = '980110';
} elseif ($type === 'battle') {
    $sub_menu = '980120';
} else {
    $sub_menu = '980110';
}

check_token();

// 유틸
function k_pipe_from_array($arr) {
    if (!is_array($arr) || !count($arr)) return '';
    $arr = array_map(static function($v){ return trim((string)$v); }, $arr);
    $arr = array_filter($arr, static function($v){ return $v !== ''; });
    return implode('|', $arr);
}

$act_button = ses($_POST, 'act_button', '', 'raw');
$category   = ses($_REQUEST, 'category', '', 'raw');

// 설정 업데이트
if ($act_button === '업데이트') {
    $hp      = ses($_POST, 'hp', '', 'string');
    $hp_name = ses($_POST, 'hp_name', '', 'string');
    $mp      = ses($_POST, 'mp', '', 'string');
    $mp_name = ses($_POST, 'mp_name', '', 'string');
    $speed   = ses($_POST, 'speed', '', 'string');

    sql_query("
        UPDATE {$g5['k_battle_config']}
           SET hp      = '{$hp}',
               hp_name = '{$hp_name}',
               mp      = '{$mp}',
               mp_name = '{$mp_name}',
               speed   = '{$speed}'
    ");

// 신규 등록
} elseif ($act_button === '등록') {

    $value_normal = ses($_POST, 'value_normal', array(), 'array');
    $type_normal  = ses($_POST, 'type_normal', array(), 'array');

    $sc_value = sql_escape_string(k_pipe_from_array($value_normal));
    $sc_type  = sql_escape_string(k_pipe_from_array($type_normal));

    $sc_name   = ses($_POST, 'sc_name', '', 'string');
    $round     = ses($_POST, 'round', '', 'string');
    $min_value = ses($_POST, 'min_value', '', 'string');
    $max_value = ses($_POST, 'max_value', '', 'string');
    $use_cri   = ses($_POST, 'use_cri', '', 'string');
    $rand      = ses($_POST, 'rand', '', 'string');
    $category  = $category !== '' ? $category : ses($_POST, 'category', '', 'raw');

    sql_query("
        INSERT INTO {$g5['k_stat_table']}
        SET sc_name     = '{$sc_name}',
            sc_value    = '{$sc_value}',
            sc_type     = '{$sc_type}',
            sc_1        = '{$round}',
            sc_2        = '{$min_value}',
            sc_3        = '{$max_value}',
            sc_4        = '{$use_cri}',
            sc_5        = '{$rand}',
            sc_category = '{$category}'
    ");

// 선택삭제
} elseif ($act_button === '선택삭제') {

    $chk = ses($_POST, 'chk', array(), 'array');
    if (!count($chk)) {
        alert('삭제하실 항목을 하나 이상 선택하세요.');
    }

    $ids = array();
    foreach ($chk as $k) {
        $id = ses($_POST['sc_id'], $k, 0, 'int');
        if ($id > 0) $ids[] = $id;
    }
    $ids = array_values(array_unique($ids));

    if ($ids) {
        $in = implode(',', $ids);
        sql_query("DELETE FROM {$g5['k_stat_table']} WHERE sc_id IN ({$in})");
    } else {
        alert('삭제할 대상이 없습니다.');
    }

// 샘플등록
} elseif ($act_button === '샘플등록') {

    $sample_unit   = ses($_POST, 'sample_unit', array(), 'array');
    $sample_target = ses($_POST, 'sample_target', array(), 'array');

    $unit = k_pipe_from_array($sample_unit);
    $target_sql = '';

    if (isset($sample_target[0])) {
        $target = k_pipe_from_array($sample_target);
        $target_sql = ", sample_target = '".sql_escape_string($target)."'";
    }

    sql_query("
        UPDATE {$g5['k_battle_config']}
           SET sample_unit = '".sql_escape_string($unit)."' {$target_sql}
    ");
}

// 카테고리별 stat_list 갱신
if ($category === 'stat') {
    $ids = array();
    $st_list = sql_query("SELECT sc_id FROM {$g5['k_stat_table']} WHERE sc_category = 'stat'");
    for ($i = 0; $row = sql_fetch_array($st_list); $i++) {
        $ids[] = (int)$row['sc_id'];
    }
    $stat_list = count($ids) ? implode('|', $ids) : '';
    sql_query("UPDATE {$g5['k_battle_config']} SET stat_list = '".sql_escape_string($stat_list)."'");
}

goto_url('./980_k_stat_func.php?category=' . urlencode($category));
