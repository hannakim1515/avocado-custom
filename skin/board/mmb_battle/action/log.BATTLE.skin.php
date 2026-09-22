<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

// 1. S : 탐색 (성공여부, 획득아이템 ID, 획득아이템이름, 인벤 ID)


// 전투 진행 사항 처리 : N : 진행중 / E : 엔드
/*
$battle_log .= $action.
$battle_log .= "||E||";
$battle_log .= "{$ch['ch_id']}&&{$ch['ch_name']}&&{$ch_hp['has']}&&{$ch_hp['now']}&&{$ch_battle_info['default']}&&{$ch_battle_info['is_cri']}&&{$ch_battle_info['cri_value']}&&{$ch_battle_info['value']}&&{$battle_result['ch_damage']}&&{$battle_result['ch_reward']}";
$battle_log .= "||";
$battle_log .= "{$re_ch['ch_id']}&&{$re_ch['ch_name']}&&{$re_ch_hp['has']}&&{$re_ch_hp['now']}&&{$re_ch_battle_info['default']}&&{$re_ch_battle_info['is_cri']}&&{$re_ch_battle_info['cri_value']}&&{$re_ch_battle_info['value']}&&{$battle_result['re_ch_damage']}&&{$battle_result['re_ch_reward']}";
$battle_log .= "||";
$battle_log .= "{$result['win']}||{$result['lose']}||{$result['both']}";

print_r($data_log);

*/

$bat_before = explode("&&", $data_log[2]);
$bat_after = explode("&&", $data_log[3]);

if($data_log[1] == 'E') {
	// 자동 대응일 경우 이미 결과가 모두 나오는 상태
	$bat_win = $data_log[4];
	$bat_lose = $data_log[5];
	$bat_both = $data_log[6];

	$bat_before_reward = array_filter(explode("^^", $bat_before[9]));
	$bat_before_reward = implode(", ", $bat_before_reward);
	$bat_before_reward = str_replace("+", " : ", $bat_before_reward);

	$bat_after_reward = array_filter(explode("^^", $bat_after[9]));
	$bat_after_reward = implode(", ", $bat_after_reward);
	$bat_after_reward = str_replace("+", " : ", $bat_after_reward);
}


?>

<div class="log-BATTLE-data">
	<div class="log-tit theme-box">전투를 시작합니다.</div>

	<? if($data_log[1] == 'E') { // 자동 대응 설정이 되어 있을 경우 ?>

		<div class="log-battle-member">
			<div class="left <?=$bat_win == $bat_before[0] ? "win" : ""?> <?=$bat_lose == $bat_before[0] ? "lose" : ""?> <?=$bat_both ? "both" : ""?>">
				<div class="thumb">
					<img src="<?=get_character_head($bat_before[0])?>" alt="" onerror="$(this).remove();"/>
					<div class="name theme-box"><?=$bat_before[1]?></div>
				</div>

				<div class="status-bar">
					<dl>
						<dd>
							<p>
								<i><?=$bat_before[3]?>/<?=$bat_before[2]?></i>	
								<span style="width:<?=($bat_before[3] == 0 ? 0 : ($bat_before[3]/$bat_before[2])*100)?>%;"></span>
							</p>
						</dd>
					</dl>
				</div>
				
				<div class="attack <?=($bat_before[5] ? "cri" : "")?>">
					<?=$bat_before[7]?>
				</div>

				<div class="damage theme-box">
					DAMAGE : <?=$bat_before[8]?>
				</div>
			</div>
			<div class="mid">
				VS
			</div>
			<div class="right <?=$bat_win == $bat_after[0] ? "win" : ""?> <?=$bat_lose == $bat_after[0] ? "lose" : ""?> <?=$bat_both ? "both" : ""?>">
				<div class="thumb">
					<img src="<?=get_character_head($bat_after[0])?>" alt="" onerror="$(this).remove();"/>
					<div class="name theme-box"><?=$bat_after[1]?></div>
				</div>

				<div class="status-bar">
					<dl>
						<dd>
							<p>
								<i><?=$bat_after[3]?>/<?=$bat_after[2]?></i>	
								<span style="width:<?=($bat_after[3] == 0 ? 0 : ($bat_after[3]/$bat_after[2])*100)?>%;"></span>
							</p>
						</dd>
					</dl>
				</div>
				
				<div class="attack <?=($bat_after[5] ? "cri" : "")?>">
					<?=$bat_after[7]?>
				</div>
				<div class="damage theme-box">
					DAMAGE : <?=$bat_after[8]?>
				</div>
			</div>
		</div>

		<div class="result theme-box">
			<? if($bat_both) { ?>
				[비김]
			<? } else if($bat_win == $bat_before[0]) { ?>
				[승자] <?=$bat_before[1]?>
			<? } else { ?>
				[승자] <?=$bat_after[1]?>
			<? } ?>
		</div>

		<div class="reward">
			<? if($bat_before_reward) { ?>
			<dl>
				<dt><?=$bat_before[1]?></dt>
				<dd><?=$bat_before_reward?></dd>
			</dl>
			<? } ?>
			<? if($bat_after_reward) { ?>
			<dl>
				<dt><?=$bat_after[1]?></dt>
				<dd><?=$bat_after_reward?></dd>
			</dl>
			<? } ?>
		</div>
	<? } else { // 수동 대응으로 설정되어 있을 경우?>
		<div class="log-battle-member">
			<div class="left">
				<div class="thumb">
					<img src="<?=get_character_head($bat_before[0])?>" alt="" onerror="$(this).remove();"/>
					<div class="name theme-box"><?=$bat_before[1]?></div>
				</div>

				<div class="status-bar">
					<dl>
						<dd>
							<p>
								<i><?=$bat_before[3]?>/<?=$bat_before[2]?></i>	
								<span style="width:<?=($bat_before[3] == 0 ? 0 : ($bat_before[3]/$bat_before[2])*100)?>%;"></span>
							</p>
						</dd>
					</dl>
				</div>
			</div>
			<div class="mid">
				VS
			</div>
			<div class="right">
				<div class="thumb">
					<img src="<?=get_character_head($bat_after[0])?>" alt="" onerror="$(this).remove();"/>
					<div class="name theme-box"><?=$bat_after[1]?></div>
				</div>

				<div class="status-bar">
					<dl>
						<dd>
							<p>
								<i><?=$bat_after[3]?>/<?=$bat_after[2]?></i>	
								<span style="width:<?=($bat_after[3] == 0 ? 0 : ($bat_after[3]/$bat_after[2])*100)?>%;"></span>
							</p>
						</dd>
					</dl>
				</div>
			</div>
		</div>

		<? if($character['ch_id'] == $bat_after[0] && !$list_item['wr_battle_re_ch_value']) { ?>
			<script>
				// 대상자의 공격 대응 커맨드 리플란에 추가 하여 만들기
				$(function() {
					var prev_obj = $('#bo_vc_w_<?=$list_item['wr_id']?>').closest('.ui-comment').find('.action-check').find('label[for="action_<?=$list_item['wr_id']?>_"]');
					
					var radio_obj = $('<input type="radio" name="action" id="action_<?=$list_item['wr_id']?>_BATTLE_ANSWER" value="BATTLE_ANSWER" />');
					var radio_obj_label = $('<label for="action_<?=$list_item['wr_id']?>_BATTLE_ANSWER">&nbsp;전투대응&nbsp;&nbsp;&nbsp;&nbsp;</label>');
					prev_obj.after(radio_obj_label);
					prev_obj.after(radio_obj);
				});
			</script>
		<? } ?>
	<? } ?>
</div>