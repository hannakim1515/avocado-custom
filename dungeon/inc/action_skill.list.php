<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$set_list = get_skill_set_list($character['ch_id']);
$max_slot = $character['ch_skill_slot'];
$maxium_slot = get_skill_maxium_count();
$select_skill_data = "";
$basic_action = isset($basic_action) && in_array($basic_action, array('atk', 'heal', 'guard'), true) ? $basic_action : '';
$basic_actions = array(
	'atk' => array('name' => '일반 공격', 'short' => 'ATK', 'description' => '장착 스킬 없이 적을 공격합니다. 통합 전투 설정의 일반 공격 연동 코드를 사용합니다.', 'target' => '적'),
	'heal' => array('name' => '일반 치유', 'short' => 'HEAL', 'description' => '장착 스킬 없이 자신 또는 아군 한 명을 치유합니다. 통합 전투 설정의 일반 치유 연동 코드를 사용합니다.', 'target' => '아군'),
	'guard' => array('name' => '일반 방어', 'short' => 'GUARD', 'description' => '장착 스킬 없이 자신의 받는 피해를 다음 행동 전까지 줄입니다. 통합 전투 설정의 일반 방어 연동 코드를 사용합니다.', 'target' => '자신')
);
?>

<ul class="skill-set-list <?=($sh_id || $basic_action) ? "selected" : ""?>">
	<?php foreach ($basic_actions as $action_key => $action) { $active_class = $basic_action === $action_key ? 'active' : ''; ?>
		<li>
			<button type="button" class="item basic-action <?=$active_class?>" onclick="skill_setting(this);" data-basic-action="<?=$action_key?>">
				<div class="thumb"><em class="basic-action-icon action-<?=$action_key?>"><span><?=$action['short']?></span></em></div>
			</button>
			<?php if ($active_class === 'active') { ob_start(); ?>
				<div class="detail-layer basic-action-detail">
					<strong><?=$action['name']?></strong>
					<span><?=$action['description']?></span>
					<div class="spec"><em>일반행동</em><em>대상 : <?=$action['target']?></em></div>
				</div>
			<?php $select_skill_data = ob_get_contents(); ob_end_flush(); } ?>
		</li>
	<?php } ?>
	<?
		$skill_count = count($set_list);
		for($i=0; $i < $skill_count; $i++) {
			$set = $set_list[$i];

			$skill_name = $set['sh_name'] ? $set['sh_name'] : $set['sk_name'];
			$event = "";
			$active_class= $sh_id == $set['sh_id'] ? "active" : "";

			if($set['sk_type'] == '패시브') {
				$event = 'disabled';
			} else {
				$event = 'onclick="skill_setting(this);"';
			}
			echo "<li>";
	?>
			<button type="button" class="item <?=$active_class?>" <?=$event?> data-idx="<?=$set['sh_id']?>">
				<div class="thumb">
					<em style="background-image:url(<?=$set['sk_img']?>);"></em>
				</div>
			</button>
			<? if($active_class == 'active') {
				ob_start();
			?>
				<div class="detail-layer">
					<? if($skill_name) { ?><strong><?=$skill_name?></strong><? } ?>
					<span><?=nl2br($set['sk_descript'])?></span>
					<div class="spec">
						<em><?=$set['sk_type']?></em>
						<? if($set['sk_limit']) { ?><em><?=$set['sk_limit']?>턴 이후 사용</em><? } ?>
						<? if($set['sk_keep_limit']) { ?><em><?=$set['sk_keep_limit']?>턴 유지</em><? } ?>
						<? if($set['sk_target']) { ?><em>대상 : <?=$set['sk_target']?></em><? } ?>
						<? if($set['sk_use_st_id']) { ?><em><?=$set['st_name']?> <?=$set['sl_use_value']?>소모</em><? } ?>
					</div>
				</div>
			<?
				$select_skill_data = ob_get_contents();
				ob_end_flush();
			} ?>
	<?
			echo "</li>";
		}
		for($i = $skill_count; $i < $max_slot; $i++) { 
			echo "<li>";
	?>
			<div class="item none">
				<div class="thumb blank"></div>
			</div>
	<? 		echo "</li>";
		}
		for($i = max($skill_count, $max_slot); $i < $maxium_slot; $i++) { 
			echo "<li>";
	?>
			<div class="item none">
				<div class="thumb lock"></div>
			</div>
	<?		echo "</li>";
		}
	?>
</ul>
