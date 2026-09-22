<?php
include_once('./_common.php');

if (function_exists('check_token')) check_token();

$ds_id = isset($_POST['ds_id']) ? (int)$_POST['ds_id'] : 0;
$list_type = isset($_POST['list_type']) && $_POST['list_type'] === 'log' ? 'log' : '';
$return_url = G5_URL.'/dungeon/ground.php?ds_id='.$ds_id.($list_type ? '&list_type='.$list_type : '');

if ($ds_id <= 0) alert('던전 정보를 확인할 수 없습니다.');
if (!function_exists('unified_combat_action_code') || !function_exists('unified_dungeon_basic_attack_result')) {
    alert('통합 전투 확장 파일을 확인하세요.');
}

$ds = get_dungeon_state($ds_id);
$dm = get_dungeon_character($ds_id, $character['ch_id']);
if (empty($ds['ds_id']) || $ds['ds_state'] === 'E') alert('토벌이 종료되었거나 정보를 찾을 수 없습니다.');
if (empty($dm['dm_id']) || $dm['dm_state'] === 'E') alert('행동 가능한 던전 참가자 정보를 찾을 수 없습니다.');

$attack_code = unified_combat_action_code('atk');
if ($attack_code === '') alert('통합 전투 설정에서 일반 공격 연동 코드를 선택하세요.');

$attack = unified_dungeon_basic_attack_result($ds_id, $ds, $character['ch_id'], $dm, $attack_code);
$damage = (int)$attack['value'];
$critical = !empty($attack['is_critical']) ? 1 : 0;
$critical_text = $critical ? '<i>크리티컬!</i>' : '';
$log = "<p class='txt-skill-info'><strong>일반 공격</strong>을 사용했습니다.</p>";
if (!empty($attack['message'])) $log .= "<p class='txt-skill-info ty2'>{$attack['message']}</p>";
$log .= "<p class='txt-skill-result'>대미지 <em>{$damage}!</em> {$critical_text}</p>";

/* insert_dungeon_log()의 기존 스킬 로그 형식을 사용하되, 장착 슬롯·MP·쿨다운은 소비하지 않는다. */
$basic_action = array(
    'sk_id' => 0, 'sh_id' => 0, 'sh_level' => 0,
    'sk_name' => '일반 공격', 'sh_name' => '일반 공격', 'sk_function' => '공격',
    'sk_keep_limit' => 0, 'sk_mod_st_id' => 0, 'sk_mod_code' => $attack_code, 'sk_mod_enermy' => ''
);
insert_dungeon_log('스킬', $ds, $dm, $basic_action, $damage, $critical, $log);
$result_state = set_dungeon_mon_damage($ds_id, $ds, $damage, !empty($attack['is_weak']));

if ($result_state === 'G') {
    $ds['ds_weak_turn'] = (int)$ds['dg_weak_effect_turn'];
    insert_dungeon_log('몬스터', $ds, null, null, null, null, "「{$ds['dg_mon_name']}」이(가) 그로기 상태에 빠집니다!&&&&{$ds['dg_weak_effect_turn']}회 공격 무효");
}

include(G5_PATH.'/dungeon/proc/inc/_active.cmm.php');
goto_url($return_url);
?>
