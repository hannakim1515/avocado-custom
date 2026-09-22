<?php
include_once('./_common.php');

$ds_id = isset($_POST['ds_id']) ? (int)$_POST['ds_id'] : 0;
$basic_action = isset($_POST['basic_action']) && in_array($_POST['basic_action'], array('atk', 'heal', 'guard'), true) ? $_POST['basic_action'] : '';
$sh_id = isset($_POST['sh_id']) ? (int)$_POST['sh_id'] : 0;
$ds = get_dungeon_state($ds_id);
$dm = get_dungeon_character($ds_id, $character['ch_id']);
$sh = array();
$error_message = "";
$is_error = false;
$re_ch_target = '';
$select_skill_data = '';

if(!$ds['ds_id'] || !$dm['dm_id']) {
	$error_message = "던전 참여 정보를 확인할 수 없습니다.";
	$is_error = true;
}
if(!$is_error && ($ds['ds_state'] == 'E' || $dm['dm_state'] == 'E')) {
	$error_message = "스킬을 사용할 수 없는 상태입니다.";
	$is_error = true;
}

if(!$is_error && $basic_action) {
	$basic_data = array(
		'atk' => array('name' => '일반 공격', 'code_name' => '일반 공격', 'description' => '장착 스킬 없이 적을 공격합니다.', 'target' => "<p class='target enermy'>{적}</p>"),
		'heal' => array('name' => '일반 치유', 'code_name' => '일반 치유', 'description' => '장착 스킬 없이 자신 또는 아군 한 명을 치유합니다.', 'target' => ''),
		'guard' => array('name' => '일반 방어', 'code_name' => '일반 방어', 'description' => '다음 행동 전까지 자신의 받는 피해를 줄입니다.', 'target' => "<p class='target'>자신</p>")
	);
	$basic = $basic_data[$basic_action];
	$code = function_exists('unified_combat_action_code') ? unified_combat_action_code($basic_action) : '';
	if($code === '') {
		$error_message = "통합 전투 설정에서 {$basic['code_name']} 연동 코드를 선택하세요.";
		$is_error = true;
	} elseif($basic_action === 'heal') {
		$members = get_dungeon_member($ds_id);
		$re_ch_target = "<select name='re_ch'>";
		foreach($members as $member_row) $re_ch_target .= "<option value='".(int)$member_row['ch_id']."'>".get_text($member_row['ch_name'])."</option>";
		$re_ch_target .= "</select>";
	} else {
		$re_ch_target = $basic['target'];
	}
} elseif(!$is_error) {
	$sh = get_skill_has_by_has_id($character['ch_id'], $sh_id);
	if(!$sh['ch_id']) {
		$error_message = "스킬정보를 확인할 수 없습니다.";
		$is_error = true;
	} elseif($sh['ch_id'] != $character['ch_id']) {
		$error_message = "권한이 없습니다.";
		$is_error = true;
	} elseif($sh['sk_use_st_id']) {
		$max_use_state = $dm['st_id_'.$sh['sk_use_st_id']] + $dm['st_id_'.$sh['sk_use_st_id'].'_mod'];
		$now_use_state = $max_use_state - $dm['st_id_'.$sh['sk_use_st_id'].'_use'];
		if($now_use_state < $sh['sl_use_value']) { $error_message = "스킬 사용에 필요한 자원이 부족합니다."; $is_error = true; }
	}
	if(!$is_error && $sh['sh_limit'] > 0) { $error_message = "사용 가능까지 {$sh['sh_limit']}턴 남았습니다."; $is_error = true; }
	if(!$is_error) {
		$dm_list = get_dungeon_member($ds_id);
		switch($sh['sk_target']) {
			case "자신": $re_ch_target = "<p class='target'>자신</p>"; break;
			case "아군":
				$re_ch_target = "<select name='re_ch'>"; foreach($dm_list as $member_row) $re_ch_target .= "<option value='".(int)$member_row['ch_id']."'>".get_text($member_row['ch_name'])."</option>"; $re_ch_target .= "</select>"; break;
			case "아군전체": $re_ch_target = "<p class='target'>아군전체</p>"; break;
			case "타인":
				$re_ch_target = "<select name='re_ch'>"; foreach($dm_list as $member_row) if($member_row['ch_id'] != $character['ch_id']) $re_ch_target .= "<option value='".(int)$member_row['ch_id']."'>".get_text($member_row['ch_name'])."</option>"; $re_ch_target .= "</select>"; break;
			case "타인전체": $re_ch_target = "<p class='target'>타인전체</p>"; break;
			case "적": $re_ch_target = "<p class='target enermy'>{적}</p>"; break;
		}
	}
}

?>
<div>
	<div data-ajax="skill_setting_area">
		<div class="in">
			<div class="left">
				<div class="skill-list">
					<? @include(G5_PATH."/dungeon/inc/action_skill.list.php"); ?>
				</div>
			</div>
			<div class="right">
				<div class="skill-target-area">
					<? if($error_message) { ?>
						<p class="target error"><?=$error_message?></p>
					<? } else { ?>
						<?php if($basic_action) { ?>
							<input type="hidden" name="basic_action" value="<?=$basic_action?>" />
						<?php } else { ?>
							<input type="hidden" name="sh_id" value="<?=$sh['sh_id']?>" />
						<?php } ?>
						<?=$re_ch_target?>
					<? } ?>
				</div>
				<div class="skill-descript"><?=$select_skill_data?></div>
				<div class="skill-control">
					<? if($is_error) { ?>
						<div><span><?= $basic_action ? '행동하기' : '스킬 사용하기' ?></span></div>
					<? } else { ?>
						<button type="button" onclick="<?= $basic_action ? 'fn_use_basic_action' : 'fn_use_skill' ?>(this, this.form);"><span><?= $basic_action ? '행동하기' : '스킬 사용하기' ?></span></button>
					<? } ?>
				</div>
			</div>
		</div>
	</div>
</div>
