<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['k_battle_config']      = G5_TABLE_PREFIX.'k_battle_plugin_config';

/*유틸*/
function k_raid_type_csv($arr) {
    if (!is_array($arr) || !count($arr)) return 'realtime';
    $arr = array_filter($arr, function($v){ return $v !== '' && $v !== null; });
    if (!count($arr)) return 'realtime';
    return implode(',', $arr);
}

if (!function_exists('h')) {
    function h($v, $flags = ENT_QUOTES, $encoding = 'UTF-8'){
        return htmlspecialchars((string)$v, $flags | ENT_SUBSTITUTE, $encoding, false);
    }
}
if (!function_exists('ses')) {
    function ses($array, $key, $default = null, $type = null) {
        $value = (is_array($array) && isset($array[$key])) ? $array[$key] : $default;
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'string':
                return sql_escape_string(trim((string)$value));
            case 'raw':
                return trim((string)$value);
            case 'array':
                return is_array($value) ? $value : array();
            case 'bool':
                return (bool)$value;
            case 'float':
                return (float)$value;
            default:
                return $value;
        }
    }
}
if (!function_exists('clamp_pct')) {
    function clamp_pct($now, $max){
        $max = max(1, $max);
        $pct = ($now / $max) * 100;
        if ($pct < 0)   $pct = 0;
        if ($pct > 100) $pct = 100;
        return $pct;
    }
}

$chk = sql_fetch("SHOW TABLES LIKE '{$g5['k_battle_config']}'", false);
if($chk){
    include_once(G5_PATH.'/k_battle/extend/default.php');
    $raid_path = G5_PATH . '/k_battle/extend/raid';
    $k_file = [];
    if (is_dir($raid_path)) {
        $k_tmp = dir($raid_path);

        if ($k_tmp) {
            while (false !== ($k_entry = $k_tmp->read())) {
                if (preg_match('/\.php$/i', $k_entry)) {
                    $k_file[] = $k_entry;
                }
            }
            $k_tmp->close();
        }

        if (!empty($k_file)) {
            natsort($k_file);
            foreach ($k_file as $k_files) {
                include_once($raid_path . '/' . $k_files);
            }
        }
    }
    unset($k_file, $raid_path, $k_tmp, $k_entry, $k_files);
}


/*전역*/
$ra = array('ra_id' => '');
$ra_id        = ses($_REQUEST, 'ra_id', '', 'raw');
$battle_table  = ses($_REQUEST, 'battle_table', '', 'raw');
$ar_value  = ses($_REQUEST, 'ar_value', '', 'raw');
$ar_title  = ses($_REQUEST, 'ar_title', '', 'raw');
$raid_type  = ses($_REQUEST, 'raid_type', '', 'raw');
$raid_types = array(
    'all'      => '전체',
    'mmbraid'  => 'MMB 레이드'
);

$realtime_installed = sql_query("SHOW COLUMNS FROM `{$g5['k_battle_config']}` LIKE 'ver_realtime'", false);
if ($realtime_installed) {$raid_types['realtime']='실시간 레이드';}

function get_k_raid_type($raid_type, $ra_id=''){
    global $g5;

    $return=array(
        'battle_table'=>'',
        'ar_value'=>'',
        'ar_title'=>'',
        'raid_get'=>''
    );
    if($raid_type=='mmbraid'){
        $return['battle_table'] = $g5['board_table'];
        $return['ar_value'] = $board['bo_table'];
        $return['ar_title'] = 'bo_table';
        $return['raid_get'] = "{$ar_title}={$ar_value}&raid_type=mmbraid";
       
    }elseif($raid_type=='realtime'){
        $return['battle_table']  = $g5['k_realtime_table'];
        $return['ar_value'] = $ra_id;
        $return['ar_title'] = 'ra_id';
        $return['raid_get'] = "ra_id={$ra_id}&raid_type=realtime";
    }elseif($raid_type=='masraid'){
        $return['battle_table'] = $g5['k_masraid_table'];
        $return['ar_value'] = $ra_id;
        $return['ar_title'] = 'ra_id';
    }

    $return['raid_get'] = "{$return['ar_title']}={$return['ar_value']}&raid_type={$raid_type}";

    return $return;
}

?>