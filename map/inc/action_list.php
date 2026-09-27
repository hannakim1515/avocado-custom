<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$map_actions = get_map_actions($ma['ma_id']);
$active_work = map_get_active_work($character['ch_id']);
$active_action = array();
$active_map = array();

if(isset($active_work['work_id']) && $active_work['work_id']) {
	$active_action = get_map_action($active_work['action_id']);
	$active_map = get_map($active_work['ma_id']);
}

if(isset($map_vn_mode) && $map_vn_mode) {
?>

<div class="map-actions map-actions-vn">
	<? if(isset($active_work['work_id']) && $active_work['work_id']) { ?>
		<? if($active_work['work_status'] == 'COMPLETE') { ?>
			<button type="button" onclick="map_work_complete(<?=$active_work['work_id']?>);" class="map-vn-action-choice map-work-state complete">
				<span>작업 완료 : <?=get_text($active_action['action_name'])?></span>
				<em>결과 확인</em>
			</button>
		<? } else { ?>
			<div class="map-vn-work-state map-work-state working">
				<strong>작업 진행 중 : <?=get_text($active_action['action_name'])?></strong>
				<span class="map-countdown" data-work-id="<?=$active_work['work_id']?>" data-remaining="<?=map_remaining_seconds($active_work)?>">
					남은 시간 <em><?=map_format_seconds(map_remaining_seconds($active_work))?></em>
				</span>
				<button type="button" onclick="map_work_complete(<?=$active_work['work_id']?>);" class="map-vn-action-choice map-countdown-complete" style="display:none;">
					<span>작업 완료 : <?=get_text($active_action['action_name'])?></span>
					<em>결과 확인</em>
				</button>
			</div>
		<? } ?>
	<? } ?>

	<button type="button" onclick="map_search(<?=$ma['ma_id']?>);" class="map-vn-action-choice search-action <?=$is_able_search ? '' : 'disabled'?>" <?=$is_able_search ? '' : 'disabled'?>>
		<span>조사하기</span>
		<em>남은 탐색횟수 <?=($config['cf_search_count']-$character['ch_search'])?>회</em>
	</button>

	<? for($i=0; $i < count($map_actions); $i++) {
		$action = $map_actions[$i];
		$is_available_time = map_is_action_available_time($action);
		$is_locked_work = ($action['is_timed'] && isset($active_work['work_id']) && $active_work['work_id']);
		$is_disabled = (!$is_available_time || $is_locked_work);
		$meta = array();
		if($action['is_timed']) $meta[] = map_format_minutes($action['duration_minutes']).' 소요';
		$meta[] = map_action_time_label($action);
		if(!$is_available_time) $meta[] = '현재 이용 불가';
		if($is_locked_work) $meta[] = '진행 중인 작업 있음';
	?>
		<button type="button" onclick="map_action_start(<?=$action['action_id']?>);" class="map-vn-action-choice <?=$is_disabled ? 'disabled' : ''?>" <?=$is_disabled ? 'disabled' : ''?>>
			<span><?=get_text($action['action_name'])?></span>
			<em><?=get_text(implode(' / ', $meta))?></em>
			<? if($action['action_desc']) { ?><small><?=nl2br(get_text($action['action_desc']))?></small><? } ?>
		</button>
	<? } ?>
</div>

<?
	return;
}
?>

<div class="map-actions">
	<? if(isset($active_work['work_id']) && $active_work['work_id']) { ?>
	<div class="map-work-state <?=$active_work['work_status'] == 'COMPLETE' ? 'complete' : 'working'?>">
		<strong><?=$active_work['work_status'] == 'COMPLETE' ? '작업 완료' : '작업 진행 중'?></strong>
		<p>
			<?=get_text($active_map['ma_name'])?> : <?=get_text($active_action['action_name'])?>
		</p>
		<? if($active_work['work_status'] == 'COMPLETE') { ?>
			<button type="button" onclick="map_work_complete(<?=$active_work['work_id']?>);" class="ui-btn app"><span>결과 확인</span></button>
		<? } else { ?>
			<span class="map-countdown" data-work-id="<?=$active_work['work_id']?>" data-remaining="<?=map_remaining_seconds($active_work)?>">
				남은 시간 <em><?=map_format_seconds(map_remaining_seconds($active_work))?></em>
			</span>
			<button type="button" onclick="map_work_complete(<?=$active_work['work_id']?>);" class="ui-btn app map-countdown-complete" style="display:none;"><span>결과 확인</span></button>
		<? } ?>
	</div>
	<? } ?>

	<h3>무엇을 할까?</h3>

	<div class="map-action-item search-action <?=$is_able_search ? '' : 'disabled'?>">
		<div class="txt">
			<strong>조사하기</strong>
			<span>남은 탐색횟수 <?=($config['cf_search_count']-$character['ch_search'])?>회</span>
		</div>
		<div class="btn">
			<? if($is_able_search) { ?>
				<button type="button" onclick="map_search(<?=$ma['ma_id']?>);" class="ui-btn"><span>시작하기</span></button>
			<? } else { ?>
				<button type="button" class="ui-btn" disabled><span>이용 불가</span></button>
			<? } ?>
		</div>
	</div>

	<? for($i=0; $i < count($map_actions); $i++) {
		$action = $map_actions[$i];
		$is_available_time = map_is_action_available_time($action);
		$is_locked_work = ($action['is_timed'] && isset($active_work['work_id']) && $active_work['work_id']);
		$is_disabled = (!$is_available_time || $is_locked_work);
	?>
	<div class="map-action-item <?=$is_disabled ? 'disabled' : ''?>">
		<div class="txt">
			<strong><?=get_text($action['action_name'])?></strong>
			<span><?=map_get_action_type_name($action['action_type'])?></span>
			<? if($action['is_timed']) { ?><span><?=map_format_minutes($action['duration_minutes'])?> 소요</span><? } ?>
			<span><?=map_action_time_label($action)?></span>
			<? if($action['action_desc']) { ?><p><?=nl2br(get_text($action['action_desc']))?></p><? } ?>
			<? if(!$is_available_time) { ?><em>현재 이용할 수 없습니다.</em><? } ?>
			<? if($is_locked_work) { ?><em>진행 중인 작업이 끝난 뒤 시작할 수 있습니다.</em><? } ?>
		</div>
		<div class="btn">
			<? if($is_disabled) { ?>
				<button type="button" class="ui-btn" disabled><span>이용 불가</span></button>
			<? } else { ?>
				<button type="button" onclick="map_action_start(<?=$action['action_id']?>);" class="ui-btn app"><span><?=$action['is_timed'] ? '시작하기' : '실행하기'?></span></button>
			<? } ?>
		</div>
	</div>
	<? } ?>
</div>
