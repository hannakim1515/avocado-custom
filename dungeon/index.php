<?php
include_once('./_common.php');
$g5['title'] = "던전목록";
include_once('./_head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_DUNGEON_URL.'/css/intro.css">', 0);

if(!$config['cf_dungeon_open']) {
	alert("오픈되지 않은 기능입니다.");
}

if($config['cf_dungeon_map']) {
	goto_url(G5_URL."/map");
}

$config['cf_dungeon_reset'] = set_reset_dungeon();

$dungeon_list = array();
$dungeon_sql = sql_query("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_table']} dg where ds.ds_state = 'S' and ds.dg_id = dg.dg_id");
?>

<div class="dungeonListWrap">
	<ul>
		<? for($i=0; $dg = sql_fetch_array($dungeon_sql); $i++) { 
			$mon_hp = $dg['ds_hp'];
			$mon_hp_now = ($dg['ds_hp'] - $dg['ds_hurt']) <= 0 ? 0 : $dg['ds_hp'] - $dg['ds_hurt'];
			$mon_hp_per = $mon_hp_now == 0 ? 0 : ($mon_hp_now/$mon_hp) * 100;

			$close_date = date('m/d H:i', strtotime($config['cf_dungeon_reset']." +{$config['cf_dungeon_time']} Hour"));
			$ds_mem = get_dungeon_member($dg['ds_id'], "");

			$dg_url = "";
			if($character['ch_state'] == '승인') { 
				$dg_url = G5_URL."/dungeon/applicate.php?ds_id={$dg['ds_id']}";
			}
		?>
		<li>
			<div class="dg-item" style="background-image:url(<?=$dg['dg_mon_img']?>);">

				<div class="info-monster">

					<div class="in">
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
					
						<div class="bar">
							<span style="width:<?=$mon_hp_per?>%;"></span>
							<div class="txt">
								<strong><?=$mon_hp_now?></strong>
								<span><?=$mon_hp?></span>
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
					</div>
				</div>

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

				<? if($dg_url) { ?>
				<div class="control"><button type='button' onclick="goto_dungeon_application('<?=$dg_url?>');"><span>입장하기</span></button></div>
				<? } ?>

			</div>

		</li>

		<? } ?>
	</ul>
</div>




<script>
function goto_dungeon_application(url) {
	if(confirm('던전에 입장하시겠습니까?')) {
		location.href = url;
	}
}
</script>

<?
include_once('./_tail.php');
?>