<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
?>
<form name="frmSkillActive" id="frmSkillActive" method="post" action="<?=G5_URL?>/dungeon/proc/skill_use.php">
	<input type="hidden" name="list_type" value="<?=$list_type?>" />
	<input type="hidden" name="ds_id" value="<?=$ds_id?>" />

	<div data-ajax="skill_setting_area">
		<div class="in">
			<div class="left">
				<div class="skill-list">
					<? @include(G5_PATH."/dungeon/inc/action_skill.list.php"); ?>
				</div>
			</div>
			<div class="right">
				<div class="skill-target-area"><p class="target ready">스킬 대상</p></div>
				<div class="skill-descript"></div>
				<div class="skill-control">
					<div><span>스킬 사용하기</span></div>
				</div>
			</div>
		</div>
	</div>
</form>
<script>

function skill_setting(obj) {
	let idx = $(obj).attr('data-idx');
	let ds_idx = <?=$ds_id?>;
	let dm_idx = <?=$dm['dm_id']?>;

	var sendData = {sh_id:idx, ds_id:ds_idx, dm_id:dm_idx};
	var url = g5_url + "/dungeon/proc/skill_setting.php";
	$.ajax({
		type: 'post'
		, url : url
		, data: sendData
		, success : function(data) {
			if(data != 'F') {
				let response = $(data).find('*[data-ajax="skill_setting_area"]').html();
				response = response.replace("{적}", "<?=$ds['dg_mon_name']?>");
				$('*[data-ajax="skill_setting_area"]').empty().append(response);
			} else {
				alert("오류가 발생했습니다.");
			}
		}
	});
}



</script>