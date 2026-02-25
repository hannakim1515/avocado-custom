<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$set_list = get_skill_set_list($character['ch_id']);
$max_slot = $character['ch_skill_slot'];
$maxium_slot = get_skill_maxium_count();
$select_skill_data = "";
?>

<ul class="skill-set-list <?=$sh_id ? "selected" : ""?>">
	<?
		for($i=0; $i < count($set_list); $i++) {
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
		for($i; $i < $max_slot; $i++) { 
			echo "<li>";
	?>
			<div class="item none">
				<div class="thumb blank"></div>
			</div>
	<? }
		for($i; $i < $maxium_slot; $i++) { 
			echo "<li>";
	?>
			<div class="item none">
				<div class="thumb lock"></div>
			</div>
	<?		echo "</li>";
		}
	?>
</ul>
