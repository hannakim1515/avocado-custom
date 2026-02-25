<?php
include_once('./_common.php');

$dm = get_dungeon_character($ds_id, $character['ch_id']);
$sh = get_skill_has_by_has_id($character['ch_id'], $sh_id);
$error_message = "";
$is_error = false;


if(!$dm['dm_id']) {
	$error_message = "던전 참여 정보를 확인할 수 없습니다.";
	$is_error = true;
	echo "F";
	exit;
}
if($dm['dm_state'] == 'E') {
	$error_message = "스킬을 사용할 수 없는 상태입니다.";
	$is_error = true;
	echo "F";
	exit;
}
if(!$sh['ch_id']) {
	$error_message = "스킬정보를 확인할 수 없습니다.";
	$is_error = true;
	echo "F";
	exit;
}
if($sh['ch_id'] != $character['ch_id']) {
	$error_message = "권한이 없습니다.";
	$is_error = true;
	echo "F";
	exit;
}

if($sh['sk_use_st_id']) {
	$max_use_state = $dm['st_id_'.$sh['sk_use_st_id']] + $dm['st_id_'.$sh['sk_use_st_id'].'_mod'];
	$now_use_state = $max_use_state - $dm['st_id_'.$sh['sk_use_st_id'].'_use'];
	$skill_use_state = $sh['sl_use_value'];

	if($now_use_state < $skill_use_state) {
		$error_message = "스킬 사용에 필요한 자원이 부족합니다.";
		$is_error = true;
	}
}

if($sh['sh_limit'] > 0) {
	$error_message = "사용 가능까지 {$sh['sh_limit']}턴 남았습니다.";
	$is_error = true;
}


if(!$is_error) { 
	$dm_list = get_dungeon_member($ds_id);
	$re_ch_target = "";
	switch($sh['sk_target']) {
		case "자신" : 
			$re_ch_target = "<p class='target'>자신</p>";
		break;
		case "아군" : 
			$re_ch_target = "<select name='re_ch'>";
			for($i=0; $i < count($dm_list); $i++) {
				$re_ch_target .= "<option value='{$dm_list[$i]['ch_id']}'>{$dm_list[$i]['ch_name']}</option>";
			}
			$re_ch_target .= "</select>";
		break;
		case "아군전체" : 
			$re_ch_target = "<p class='target'>아군전체</p>";
		break;
		case "타인" : 
			$re_ch_target = "<select name='re_ch'>";
			for($i=0; $i < count($dm_list); $i++) {
				if($dm_list['ch_id'] == $character['ch_id']) continue;
				$re_ch_target .= "<option value='{$dm_list[$i]['ch_id']}'>{$dm_list[$i]['ch_name']}</option>";
			}
			$re_ch_target .= "</select>";
		break;
		case "타인전체" : 
			$re_ch_target = "<p class='target'>타인전체</p>";
		break;
		case "적" : 
			$re_ch_target = "<p class='target enermy'>{적}</p>";
		break;
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
						<input type="hidden" name="sh_id" value="<?=$sh['sh_id']?>" />
						<?=$re_ch_target?>
					<? } ?>
				</div>
				<div class="skill-descript"><?=$select_skill_data?></div>
				<div class="skill-control">
					<? if($is_error) { ?>
						<div><span>스킬 사용하기</span></div>
					<? } else { ?>
						<button type="button" onclick="fn_use_skill(this, this.form);"><span>스킬 사용하기</span></button>
						<script>
							function fn_use_skill(obj, f) {
								$(obj).attr('disabled', 'disabled');
								f.submit();
							}
						</script>
					<? } ?>
				</div>
			</div>
		</div>
	</div>
</div>