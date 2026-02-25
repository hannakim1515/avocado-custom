<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/skill/css/style.css">', 0);

// $has_list : 현재 보유중인 스킬 목록
// $set_list : 현재 장착중인 스킬 목록

$is_item_upgrade = false;
$is_item_skill_remove = false;

if($is_mine) {
	//$is_item_upgrade = is_hasItem_func($ch_id, '스킬레벨업');
	//$is_item_skill_remove = is_hasItem_func($ch_id, '스킬해제');
}

?>

<div>
	<div class="skill-list-box" data-ajax="skill_list_box">
		<ul class="skill-set-list">
			<?
				for($i=0; $i < count($set_list); $i++) {
					$set = $set_list[$i];

					$skill_name = $set['sh_name'] ? $set['sh_name'] : $set['sk_name'];

					$max_level = sql_fetch("select MAX(sl_level) as sl_level from {$g5['skill_level_table']} where sk_id = '{$set['sk_id']}'");
					$max_level = $max_level['sl_level'];
					$next_level = $set['sh_level'] +1;

					echo "<li>";
			?>
					<div class="item">
						<button type="button" onclick="$('#skill_detail_<?=$i?>').toggle();" class="thumb">
							<em style="background-image:url(<?=$set['sk_img']?>);"></em>
						</button>
					</div>
					<div class="detail-layer" id="skill_detail_<?=$i?>">
						<div class="pop">
							<div class="tit">
								<? if($skill_name) { ?><strong><? if($set['sl_name']) { ?><em><?=$set['sl_name']?></em> <? } ?><?=$skill_name?></strong><? } ?>
							</div>
							<div class="desc">
								<? if($set['sk_descript']) { ?><div class="txt"><?=nl2br($set['sk_descript'])?></div><? } ?>
								<div class="spec">
									<? if($set['sk_type']) { ?><em><?=$set['sk_type']?></em><? } ?>
									<? if($set['sk_limit']) { ?><em><?=$set['sk_limit']?>턴 이후 사용</em><? } ?>
									<? if($set['sk_keep_limit']) { ?><em><?=$set['sk_keep_limit']?>턴 유지</em><? } ?>
									<? if($set['sk_target']) { ?><em>대상 : <?=$set['sk_target']?></em><? } ?>
									<? if($set['sk_use_st_id']) { ?><em><?=$set['st_name']?> <?=$set['sl_use_value']?>소모</em><? } ?>
								</div>
							</div>
							<div class="control">
								<? if($is_mine) { ?>
									<button type="button" onclick="skill_remove(<?=$set['sh_id']?>);" class="ui-btn">해제</button>
									<? if($max_level >= $next_level) { ?>
										<button type="button" onclick="skill_upgrade(<?=$set['sh_id']?>);" class="ui-btn">레벨업</button>
									<? } ?>
								<? } ?>
								<button type="button" onclick="$(this).closest('.detail-layer').toggle();" class="ui-btn">닫기</button>
							</div>
						</div>
					</div>
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
		<? if($is_mine) { ?>
		<div class="setting-control">
			<button type="button" onclick="$('.skill-conf-list').toggle();" class="ui-btn">설정</button>
		</div>
		<? } ?>
	</div>

	<? if($is_mine) { ?>
		<div class="skill-conf-list" data-ajax="skill_conf_box">
			<div class="pop">
				<ul>
					<? for($i=0; $i < count($has_list); $i++) {
						$has = $has_list[$i];
						$skill_name = $has['sh_name'] ? $has['sh_name'] : $has['sk_name'];

						$max_level = sql_fetch("select MAX(sl_level) as sl_level from {$g5['skill_level_table']} where sk_id = '{$has['sk_id']}'");
						$max_level = $max_level['sl_level'];
						$next_level = $has['sh_level'] +1;

					?>
						<li>
							<div class="item <? if($is_mine && ($has['sh_use'] || count($set_list) < $max_slot)) { ?>has-control<? } ?>">
								<div class="thumb"><em style="background-image:url(<?=$has['sk_img']?>);"></em></div>
								<div class="desc">
									<div class="name">
										<? if($has['sh_use']) { ?><em data-cate="장착중">장착중</em> <? } ?>
										<? if($skill_name) { ?><strong><?=$skill_name?></strong><? } ?>
									</div>
									<? if($has['sk_descript']) { ?>
										<div class="txt"><?=nl2br($has['sk_descript'])?></div>
									<? } ?>
									<div class="spec">
										<em data-cate="<?=$has['sk_type']?>"><?=$has['sk_type']?></em>
										<em><?=$has['sl_name']?></em>
										<? if($has['sk_limit']) { ?><em><?=$has['sk_limit']?>턴 이후 사용</em><? } ?>
										<? if($has['sk_keep_limit']) { ?><em><?=$has['sk_keep_limit']?>턴 유지</em><? } ?>
										<? if($has['sk_target']) { ?><em>대상 : <?=$has['sk_target']?></em><? } ?>
										<? if($has['sk_use_st_id']) { ?><em><?=$has['st_name']?> <?=$has['sl_use_value']?>소모</em><? } ?>
									</div>
								</div>
								<div class="control">
									<? if($has['sh_use']) { ?>
										<button type="button" onclick="skill_remove(<?=$has['sh_id']?>);" class="del">해제</button>
									<?	} else if(count($set_list) < $max_slot) { ?>
										<button type="button" onclick="skill_add(<?=$has['sh_id']?>);" class="add">장착</button>
									<? } ?>
									<? if($max_level >= $next_level) { ?>
										<button type="button" onclick="skill_upgrade(<?=$has['sh_id']?>);" class="upgrade">레벨업</button>
									<? } ?>
								</div>
							</div>
						</li>
					<? } ?>
				</ul>
				<div class="setting-control">
					<button type="button" onclick="$('.skill-conf-list').toggle();" class="ui-btn">닫기</button>
				</div>
			</div>
		</div>
		<script>
			function skill_add(idx) {
				//if(confirm("해당 스킬을 장착하시겠습니까?")) {
					var sendData = {sh_id:idx, ch_id:<?=$ch_id?>};
					var url = g5_url + "/skill/skill.add.php";
					$.ajax({
						type: 'post'
						, url : url
						, data: sendData
						, success : function(data) {
							if(data != 'F') {
								var response = $(data).find('*[data-ajax="skill_list_box"]').html();
								$('*[data-ajax="skill_list_box"]').empty().append(response);

								var response2 = $(data).find('*[data-ajax="skill_conf_box"]').html();
								$('*[data-ajax="skill_conf_box"]').empty().append(response2);
							} else {
								alert("스킬 장착에 실패하였습니다.");
							}
						}
					});
				//}
			}
			function skill_remove(idx) {
				//if(confirm("해당 스킬의 장착을 해제하시겠습니까?")) {
					var sendData = {sh_id:idx, ch_id:<?=$ch_id?>};
					var url = g5_url + "/skill/skill.del.php";
					$.ajax({
						type: 'post'
						, url : url
						, data: sendData
						, success : function(data) {
							console.log(data);
							if(data != 'F') {
								var response = $(data).find('*[data-ajax="skill_list_box"]').html();
								$('*[data-ajax="skill_list_box"]').empty().append(response);

								var response2 = $(data).find('*[data-ajax="skill_conf_box"]').html();
								$('*[data-ajax="skill_conf_box"]').empty().append(response2);
							} else {
								alert("스킬 해제에 실패하였습니다.");
							}
						}
					});
				//}
			}
			function skill_upgrade(idx) {
				if(confirm("해당 스킬의 레벨을 올리시겠습니까? (스킬 레벨을 올릴 수 있는 아이템을 보유하고 있어야 합니다.)")) {
					var sendData = {sh_id:idx, ch_id:<?=$ch_id?>};
					var url = g5_url + "/skill/skill.upgrade.php";
					$.ajax({
						type: 'post'
						, url : url
						, data: sendData
						, success : function(data) {
							if(data != 'F') {
								var response = $(data).find('*[data-ajax="skill_list_box"]').html();
								$('*[data-ajax="skill_list_box"]').empty().append(response);

								var response2 = $(data).find('*[data-ajax="skill_conf_box"]').html();
								$('*[data-ajax="skill_conf_box"]').empty().append(response2);
							} else {
								alert("스킬 레벨업에 실패하였습니다.");
							}
						}
					});
				}
			}
		</script>
	<? } ?>
</div>