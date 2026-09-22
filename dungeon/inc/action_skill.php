<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<form name="frmSkillActive" id="frmSkillActive" method="post" action="<?=G5_URL?>/dungeon/proc/skill_use.php">
	<input type="hidden" name="list_type" value="<?=$list_type?>" />
	<input type="hidden" name="ds_id" value="<?=$ds_id?>" />
	<input type="hidden" name="token" value="<?=get_token()?>" />
	<?php $sh_id = 0; $basic_action = ''; ?>

	<div data-ajax="skill_setting_area">
		<div class="in">
			<div class="left">
				<div class="skill-list">
					<? @include(G5_PATH."/dungeon/inc/action_skill.list.php"); ?>
				</div>
			</div>
			<div class="right">
				<div class="skill-target-area"><p class="target ready">행동을 선택하세요</p></div>
				<div class="skill-descript"></div>
				<div class="skill-control">
					<div><span>행동하기</span></div>
				</div>
			</div>
		</div>
	</div>
</form>
<script>

function skill_setting(obj) {
	let idx = $(obj).attr('data-idx');
	let basicAction = $(obj).attr('data-basic-action');
	let ds_idx = <?=$ds_id?>;
	let dm_idx = <?=$dm['dm_id']?>;

	var sendData = {sh_id:idx, basic_action:basicAction, ds_id:ds_idx, dm_id:dm_idx};
	var url = g5_url + "/dungeon/proc/skill_setting.php";
	$.ajax({
		type: 'post'
		, url : url
		, data: sendData
		, success : function(data) {
			if(data != 'F') {
				$('#frmSkillActive').attr('action', g5_url + '/dungeon/proc/skill_use.php');
				let response = $(data).find('*[data-ajax="skill_setting_area"]').html();
				response = response.replace("{적}", "<?=$ds['dg_mon_name']?>");
				$('*[data-ajax="skill_setting_area"]').empty().append(response);
			} else {
				alert("오류가 발생했습니다.");
			}
		}
	});
}

function fn_use_skill(obj, f) {
	$(obj).attr('disabled', 'disabled');
	f.submit();
}

function fn_use_basic_action(obj, f) {
	$(obj).attr('disabled', 'disabled');
	$(f).attr('action', g5_url + '/dungeon/proc/basic_action.php');
	f.submit();
}


</script>
