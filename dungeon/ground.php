<?php
include_once('./_common.php');
$g5['title'] = "던전진행";
include_once('./_head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_DUNGEON_URL.'/css/style.css">', 0);

$log_url = "./ground.php?ds_id={$ds_id}";
if($list_type != 'log') {
	$log_url = "./ground.php?ds_id={$ds_id}&list_type=log";
}

$ds = get_dungeon_state($ds_id);
if(!$ds['ds_id']) { alert("던전 정보를 확인할 수 없습니다."); }

if($ds['ds_state'] != 'E') {
	$is_d_state = is_able_dungeon($character['ch_id'], $ds_id, true, $ds);
	if(!$is_d_state['is_able']) {
		alert($is_d_state['message']);
	}
}

// 같은 던전에 참여중인 캐릭터 목록
if($ds['ds_state'] == "E") {
	$dm_list_All = get_dungeon_member($ds_id, '');
} else {
	$dm_list = get_dungeon_member($ds_id);
	$dm_list_E = get_dungeon_member($ds_id, 'E');
	$dm_list_All = array_merge($dm_list, $dm_list_E);
}

$dm = get_dungeon_character($ds_id, $character['ch_id']);

if(!$dm['dm_id'] && $ds['ds_state'] != 'E') {
	alert("진행중인 토벌은 관전할 수 없습니다.");
}

//$dm_key = array_search($character['ch_id'], array_column($dm_list_All, 'ch_id'));
//if($dm_key != "") {
//	$dm = $dm_list_All[$dm_key];
//} else {
	//$dm = null;
//}

$is_action = true;
if(!$dm['dm_id'] || $dm['dm_state'] == 'E' || $ds['ds_state'] == 'E') { $is_action = false; }
$turn_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where dl_is_turn = 1 and ds_id = '{$ds_id}' and ch_id = '{$character['ch_id']}'");
$total_turn_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where dl_is_turn = 1 and ds_id = '{$ds_id}'");

?>

<script src="<?php echo G5_DUNGEON_URL ?>/js/script.js"></script>

<div class="dungeon-top-area">
	<div class="inner">
		<div class="turn">
			<p>
				<span>나의 행동횟수</span>
				<strong><?=$turn_count['cnt']?></strong>
			</p>
		</div>

		<div class="control">
			<a href="<?=$log_url?>" class="log <?=$list_type == 'log' ? "off" : ""?>">
				<span><?=$list_type == 'log' ? "현황보기" : "로그보기"?></span>
			</a>
			<a href="javascript:location.reload();" class="reload">
				<span>새로고침</span>
			</a>

			<a href="<?=G5_URL?>/dungeon" class="exit">
				<span>나가기</span>
			</a>
		</div>

	</div>
</div>

<div class="dungeon-content-area">
	<div class="inner">
		<div class="monster-active-pannel">
			<div class="pannel">
				<? include(G5_DUNGEON_PATH."/inc/detail_monster.php"); ?>
			</div>
			<div class="raid-floor">
				<div class="raid-floor-inner"></div>
			</div>
		</div>

		<div class="dungeon-active-pannel">
			<? if($is_action) { ?>
			<div class="write-area" data-active="스킬">
				<div class="tabs">
					<a data-tab="스킬" href="javascript:change_tabs('스킬');"><span>스킬사용</span></a>
					<a data-tab="아이템" href="javascript:change_tabs('아이템');"><span>아이템사용</span></a>
					<a data-tab="대화" href="javascript:change_tabs('대화');"><span>대화하기</span></a>
				</div>
				<div class="tab-con skill-con">
					<? include(G5_DUNGEON_PATH."/inc/action_skill.php"); ?>
				</div>
				<div class="tab-con item-con">
					<? include(G5_DUNGEON_PATH."/inc/action_item.php"); ?>
				</div>
				<div class="tab-con talk-con">
					<? include(G5_DUNGEON_PATH."/inc/action_talk.php"); ?>
				</div>
			</div>
			<? } ?>

			<div class="pannel list-type-<?=$list_type?>">
				<?
					if($list_type == 'log') {
						include(G5_DUNGEON_PATH."/inc/detail_log.php");
					} else {
						// 던전 진행중일 땐 던전 현황 출력
						if($ds['ds_state'] == 'S') {
							include(G5_DUNGEON_PATH."/inc/detail_character.php");
						}
						// 던전 진행이 종료되면 각 캐릭터 보상 획득 처리
						if($ds['ds_state'] == 'E') {
							include(G5_DUNGEON_PATH."/inc/detail_character.result.php");
						}
					}
				?>
			</div>
		</div>

	</div>
</div>


<script>
function change_tabs(cate) {
	$('.dungeon-active-pannel .write-area').attr('data-active', cate);
}

</script>

<?
include_once('./_tail.sub.php');
?>