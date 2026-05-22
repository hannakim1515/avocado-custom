<?php
include_once('../../common.php');

// === JSON 응답 전용 세팅 (가장 위!) ===
@ini_set('display_errors','0');           // PHP Notice/Warning이 응답에 섞이는 것 방지
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
http_response_code(200);

// 출력 버퍼에 뭐가 쌓였으면 비우기 (BOM/우발 출력 제거)
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
}

// 공통 JSON 응답 함수 (항상 여기로만 응답)
function json_exit($arr){
    // 혹시나 모를 추가 출력 차단
    if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// 0) 인증
if (!$is_member) json_exit(['result'=>'error','msg'=>'로그인이 필요합니다.']);

// 1) 입력
$ph_id = isset($_POST['ph_id']) ? (int)$_POST['ph_id']
       : (isset($_GET['ph_id']) ? (int)$_GET['ph_id'] : 0);
$slot  = isset($_POST['slot'])  ? (int)$_POST['slot']  : -1;
$in_id = isset($_POST['in_id']) ? (int)$_POST['in_id'] : 0;

if ($ph_id <= 0)          json_exit(['result'=>'error','msg'=>'포켓몬 정보가 없습니다']);
if ($slot < 0 || $slot>3) json_exit(['result'=>'error','msg'=>'잘못된 슬롯 선택']);
if ($in_id <= 0)          json_exit(['result'=>'error','msg'=>'아이템 정보가 없습니다']);

$ch_id    = (int)$character['ch_id'];
$wa_order = $slot + 1; // 1~4

// 2) 배틀 포켓몬 검증 (요청대로 false 유지)
$ph = get_battle_pokemon($ph_id, false);
if (!$ph['ph_id'] || (int)$ph['ch_id'] !== $ch_id) {
    json_exit(['result'=>'error','msg'=>'포켓몬 정보가 잘못되었습니다.']);
}

// 3) 인벤토리 보유 + 기술 조인
$item = sql_fetch("
    SELECT inven.*, it.*, wz.*
    FROM {$g5['inventory_table']} AS inven
    JOIN {$g5['item_table']}     AS it ON it.it_id   = inven.it_id
    JOIN {$g5['pokemon_waza_table']}     AS wz ON wz.wa_id   = it.it_value
    WHERE inven.in_id = '{$in_id}'
      AND inven.ch_id = '{$ch_id}'
");
if (!$item['in_id']) {
    json_exit(['result'=>'error','msg'=>'보유하지 않은 기술입니다']);
}

$wa_id   = (int)$item['wa_id'];
$wa_type = (string)($item['wa_type2'] ?? '');

// 4) 동일 기술 중복(다른 슬롯) 방지
$row_dup = sql_fetch("
    SELECT wh_id, wa_order
      FROM {$g5['pokemon_waza_has_table']}
     WHERE ph_id='{$ph_id}' AND wa_id='{$wa_id}'
     LIMIT 1
");
if ($row_dup && (int)$row_dup['wa_order'] !== $wa_order) {
    json_exit(['result'=>'error','msg'=>'이미 다른 슬롯에 습득한 기술입니다']);
}

// 5) PP 상한 산정
$pp_max = 5; // 기본 5 (요청하신 값 유지)
if (isset($item['wa_pp_max']) && is_numeric($item['wa_pp_max'])) {
    $pp_max = (int)$item['wa_pp_max'];
}

// 자속 미일치 시 하향
$po_type1 = (string)($ph['po_type1'] ?? '');
$po_type2 = (string)($ph['po_type2'] ?? '');
if ($item['wa_category']=='공격'&&$wa_type!='무'&& $wa_type !== $po_type1 && $wa_type !== $po_type2) {
    $pp_max = floor($pp_max/2);
}
$pp_max = max(1, $pp_max);

// 6) UPSERT
$exists = sql_fetch("
    SELECT wh_id
      FROM {$g5['pokemon_waza_has_table']}
     WHERE ph_id='{$ph_id}' AND wa_order='{$wa_order}'
     LIMIT 1
");
if ($exists && (int)$exists['wh_id'] > 0) {
    $ok = sql_query("
        UPDATE {$g5['pokemon_waza_has_table']}
           SET wa_id='{$wa_id}', pp_max='{$pp_max}', pp_now='{$pp_max}'
         WHERE ph_id='{$ph_id}' AND wa_order='{$wa_order}'
         LIMIT 1
    ");
} else {
    $ok = sql_query("
        INSERT INTO {$g5['pokemon_waza_has_table']} (ph_id, wa_order, wa_id, pp_max, pp_now)
        VALUES ('{$ph_id}', '{$wa_order}', '{$wa_id}', '{$pp_max}', '{$pp_max}')
    ");
}

delete_inventory($in_id);

if ($ok) json_exit(['result'=>'ok']);
json_exit(['result'=>'error','msg'=>'업데이트에 실패했습니다']);
