<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가

$bat_before = explode("&&", $data_log[2]);
$bat_after = explode("&&", $data_log[3]);

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

?>

<div class="log-BATTLE-data">
	<div class="log-tit theme-box">전투가 종료되었습니다.</div>

	<div class="log-battle-member">
		<div class="left <?=$bat_win == $bat_after[0] ? "win" : ""?> <?=$bat_lose == $bat_after[0] ? "lose" : ""?> <?=$bat_both ? "both" : ""?>">
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
		<div class="mid">
			VS
		</div>
		<div class="right <?=$bat_win == $bat_before[0] ? "win" : ""?> <?=$bat_lose == $bat_before[0] ? "lose" : ""?> <?=$bat_both ? "both" : ""?>">
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
</div>