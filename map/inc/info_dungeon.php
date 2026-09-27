<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$mon_hp = $dg['ds_hp'];
$mon_hp_now = ($dg['ds_hp'] - $dg['ds_hurt']) <= 0 ? 0 : $dg['ds_hp'] - $dg['ds_hurt'];
$mon_hp_per = $mon_hp_now == 0 ? 0 : ($mon_hp_now/$mon_hp) * 100;

$close_date = date('m/d H:i', strtotime($config['cf_dungeon_reset']." +{$config['cf_dungeon_time']} Hour"));

$ds_mem = get_dungeon_member($dg['ds_id'], "");
?>

<div class="map-pannel type-dungeon">
	<div class="map-simple-info">
		<div class="in">
			<div class="area">
				<strong><?=$ma['ma_name']?></strong>
			</div>
			<div class="control">
				<?
					if($character['ch_state'] == '승인' && $character['ma_id'] == $ma_id) {
						include(G5_PATH."/map/inc/btn_gate_app.php");
					} else {
						include(G5_PATH."/map/inc/btn_move.php");
					}
				?>
			</div>
		</div>
	</div>
	<div class="map-detail">
		<div class="info-monster">
			<div class="img">
				<? if($dg['dg_mon_img']) { ?>
					<img src="<?=$dg['dg_mon_img']?>" alt="" />
				<? } ?>
			</div>
			<div class="rank">
				<strong><?=$dg['dg_rank']?></strong>
			</div>
			<div class="mon-descript">
				<p><?=$dg['dg_mon_name']?></p>
				<? if($dg['dg_mon_descript']) { ?>
					<div>
						<?=nl2br($dg['dg_mon_descript'])?>
					</div>
				<? } ?>
			</div>
		</div>
		<div class="info-dungeon">
			<div class="bar">
				<span style="width:<?=$mon_hp_per?>%;"></span>
				<div class="txt">
					<strong><?=$mon_hp_now?></strong>
					<span><?=$mon_hp?></span>
				</div>
			</div>
		</div>

		<? if($dg['dg_status']) {
			$st_name = sql_fetch("select st_name from {$g5['status_config_table']} where st_id = '{$dg['dg_status']}'"); $st_name = $st_name['st_name'];
			$dg['dg_status_value'] = $dg['dg_status_value'] < 0 ? "({$dg['dg_status_value']})" : $dg['dg_status_value'];
		?>
			<div class="buffer-info">
				<p>
					<strong>스탯보정효과</strong>
					<span><?=$st_name?> <?=$dg['dg_status_type']?> <?=$dg['dg_status_value']?></span>
				</p>
			</div>
		<? } ?>

		<div class="gate-member-list">
			<div class="tit">
				<p>
					<strong>참가인원</strong>
					<span>
						<?=count($ds_mem)?>/<?=$dg['dg_count']?>
					</span>
				</p>
			</div>
			<ul>
				<? for($i=0; $i < count($ds_mem); $i++) {
					$mem_state = $ds_mem[$i]['dm_state'] =='E' ? "이탈" : "공략 중...";
				?>
				<li>
					<div class="thumb">
						<span>
							<img src="<?=$ds_mem[$i]['ch_thumb']?>" alt="" />
						</span>
					</div>
					<div class="name">
						<span class="state" data-state="<?=$ds_mem[$i]['dm_state']?>"><?=$mem_state?></span>
						<strong><?=$ds_mem[$i]['ch_name']?></strong>
					</div>
				</li>
				<? } ?>
			</ul>
		</div>
	</div>
</div>