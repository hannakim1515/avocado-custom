<?php
include_once('./_common.php');

if (function_exists('check_token')) check_token();

$ds_id = isset($_POST['ds_id']) ? (int)$_POST['ds_id'] : 0;
$list_type = isset($_POST['list_type']) && $_POST['list_type'] === 'log' ? 'log' : '';
$action = isset($_POST['basic_action']) && in_array($_POST['basic_action'], array('atk', 'heal', 'guard'), true) ? $_POST['basic_action'] : '';
$return_url = G5_URL.'/dungeon/ground.php?ds_id='.$ds_id.($list_type ? '&list_type='.$list_type : '');

if ($ds_id <= 0 || $action === '') alert('올바른 일반행동 요청이 아닙니다.');
if (!function_exists('unified_combat_action_code')) alert('통합 전투 확장 파일을 확인하세요.');

$ds = get_dungeon_state($ds_id);
$dm = get_dungeon_character($ds_id, $character['ch_id']);
if (empty($ds['ds_id']) || $ds['ds_state'] === 'E') alert('토벌이 종료되었거나 정보를 찾을 수 없습니다.');
if (empty($dm['dm_id']) || $dm['dm_state'] === 'E') alert('행동 가능한 던전 참가자 정보를 찾을 수 없습니다.');

$code = unified_combat_action_code($action);
$labels = array('atk' => '일반 공격', 'heal' => '일반 치유', 'guard' => '일반 방어');
$label = $labels[$action];
if ($code === '') alert('통합 전투 설정에서 '.$label.' 연동 코드를 선택하세요.');

$basic_skill = array(
    'sk_id' => 0, 'sh_id' => 0, 'sh_level' => 0,
    'sk_name' => $label, 'sh_name' => $label, 'sk_keep_limit' => 0,
    'sk_mod_st_id' => 0, 'sk_mod_code' => $code, 'sk_mod_enermy' => ''
);
$result_state = $ds['ds_state'];

if ($action === 'atk') {
    if (!function_exists('unified_dungeon_basic_attack_result')) alert('통합 전투 확장 파일을 확인하세요.');
    $attack = unified_dungeon_basic_attack_result($ds_id, $ds, $character['ch_id'], $dm, $code);
    $damage = (int)$attack['value'];
    $critical = !empty($attack['is_critical']) ? 1 : 0;
    $critical_text = $critical ? '<i>크리티컬!</i>' : '';
    $log = "<p class='txt-skill-info'><strong>{$label}</strong>을 사용했습니다.</p>";
    if (!empty($attack['message'])) $log .= "<p class='txt-skill-info ty2'>{$attack['message']}</p>";
    $log .= "<p class='txt-skill-result'>대미지 <em>{$damage}!</em> {$critical_text}</p>";
    $basic_skill['sk_function'] = '공격';
    insert_dungeon_log('스킬', $ds, $dm, $basic_skill, $damage, $critical, $log);
    $result_state = set_dungeon_mon_damage($ds_id, $ds, $damage, !empty($attack['is_weak']));
    if ($result_state === 'G') {
        $ds['ds_weak_turn'] = (int)$ds['dg_weak_effect_turn'];
        insert_dungeon_log('몬스터', $ds, null, null, null, null, "「{$ds['dg_mon_name']}」이(가) 그로기 상태에 빠집니다!&&&&{$ds['dg_weak_effect_turn']}회 공격 무효");
    }
} elseif ($action === 'heal') {
    $target_ch_id = isset($_POST['re_ch']) ? (int)$_POST['re_ch'] : (int)$character['ch_id'];
    $target_dm = get_dungeon_character($ds_id, $target_ch_id);
    if (empty($target_dm['dm_id']) || $target_dm['dm_state'] === 'E') alert('치유할 던전 참가자를 확인할 수 없습니다.');
    $hp_st_id = function_exists('unified_stat_hp_id') ? (int)unified_stat_hp_id() : 0;
    if ($hp_st_id <= 0) alert('통합 전투 설정에서 체력 원본을 선택하세요.');
    $heal_data = get_status_dungeon($code, $ds_id, $character['ch_id'], $dm);
    $heal = max(0, (int)$heal_data['value']);
    $critical = !empty($heal_data['is_cri']) ? 1 : 0;
    $before_use = (int)$target_dm['st_id_'.$hp_st_id.'_use'];
    $after_use = max(0, $before_use - $heal);
    sql_query("UPDATE {$g5['dungeon_member_table']} SET st_id_{$hp_st_id}_use = '{$after_use}' WHERE dm_id = '".(int)$target_dm['dm_id']."'");
    $critical_text = $critical ? '<i>크리티컬!</i>' : '';
    $log = "<p class='txt-skill-info'><strong>{$label}</strong>을 사용했습니다.</p>";
    $log .= "<p class='txt-skill-result'><strong>".get_text($target_dm['ch_name'])."</strong>의 체력 <em>+{$heal}</em> {$critical_text}</p>";
    $basic_skill['sk_function'] = '스탯회복';
    $basic_skill['sk_mod_st_id'] = $hp_st_id;
    insert_dungeon_log('스킬', $ds, $dm, $basic_skill, $heal, $critical, $log);
} else {
    $guard_data = get_status_dungeon($code, $ds_id, $character['ch_id'], $dm);
    $rate = min(90, max(0, (int)$guard_data['value']));
    $log = "<p class='txt-skill-info'><strong>{$label}</strong>을 사용했습니다.</p>";
    $log .= "<p class='txt-skill-result'>다음 행동 전까지 받는 피해 <em>{$rate}% 감소</em></p>";
    $basic_skill['sk_function'] = '방어';
    insert_dungeon_log('스킬', $ds, $dm, $basic_skill, $rate, 0, $log);
    $basic_skill['sk_keep_limit'] = 1;
    insert_dungeon_log('효과', $ds, $dm, $basic_skill, $rate, 0, '');
    sql_query("UPDATE {$g5['dungeon_member_table']} SET dm_comment = '방어: {$rate}% 피해 감소' WHERE dm_id = '".(int)$dm['dm_id']."'");
}

include(G5_PATH.'/dungeon/proc/inc/_active.cmm.php');
goto_url($return_url);
?>
