<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * 핵심 유틸리티 함수 (가장 먼저 로드되어야 함)
 * 파일명 00_ 접두사로 알파벳순 로드에서 최우선
 */

// -------- HTML 이스케이프(빠른 출력용)
if (!function_exists('h')) {
    function h($v, $flags = ENT_QUOTES, $encoding = 'UTF-8'){
        return htmlspecialchars((string)$v, $flags | ENT_SUBSTITUTE, $encoding, false);
    }
}

// -------- 배열 키 안전 접근 + SQL 이스케이프
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

// -------- 퍼센트 클램프 (0~100)
if (!function_exists('clamp_pct')) {
    function clamp_pct($now, $max){
        $max = max(1, $max);
        $pct = ($now / $max) * 100;
        if ($pct < 0)   $pct = 0;
        if ($pct > 100) $pct = 100;
        return $pct;
    }
}
