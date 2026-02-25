<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가


/*------------------------------------------
	Monster State 
------------------------------------------ */

$now_mon_hp = ($ds['ds_hp'] - $ds['ds_hurt']) < 0 ? 0 : $ds['ds_hp'] - $ds['ds_hurt'];
$now_mon_per = $now_mon_hp == 0 ? 0 : $now_mon_hp/$ds['ds_hp'] * 100;
$mon_log = sql_fetch("select * from {$g5['dungeon_log_table']} where dl_cate = '몬스터' and ds_id = '{$ds_id}' order by dl_id desc limit 0, 1");

// 몬스터 버프 확인
//$mon_hp_buff = get_status_buffer_enermy($ds_id, "체력");
$mon_attack_buff = get_status_buffer_enermy($ds_id, "공격");
$mon_def_buff = get_status_buffer_enermy($ds_id, "방어");
$mon_is_grrogy = $ds['ds_weak_turn'] > 0 ? true : false;

?>
<div class="allTurn">
	<div class="mid">
		<div>
			<span>전체 행동횟수</span>
			<strong><?=$total_turn_count['cnt']?></strong>
		</div>
	</div>
</div>

<div class="img">
	<img src="<?=$ds['dg_mon_img']?>" alt="" />
</div>

<?
if($mon_log['dl_id'] && $_COOKIE['viewMonPopup'] != $mon_log['dl_id']) {
	$mon_pop_content = explode("&&&&",$mon_log['dl_log']);
	$mon_pop_content[0] = $mon_pop_content[0] ? $mon_pop_content[0] : "움직임이 감지되었습니다.";
?>
<div class="pop none-trans" onclick="set_cookie('viewMonPopup', '<?=$mon_log['dl_id']?>', 360, g5_cookie_domain); $(this).fadeOut(100);">
	<div class="tit">
		<div class="in">
			<strong><?=$mon_pop_content[0]?></strong>
		</div>
	</div>
	<div class="state">
		<div class="in">
			<?=$mon_pop_content[1]?>
		</div>
	</div>
</div>
<? } ?>

<div class="status">
	<div class="name">
		<strong><?=$ds['dg_mon_name']?></strong>
	</div>
	<dl>
		<dt>HP</dt>
		<dd>
			<span style="width:<?=$now_mon_per?>%;"></span>
		</dd>
	</dl>

	<? if($mon_hp_buff || $mon_def_buff || $ds['ds_weak_turn']) { ?>
	<div class="buff">
		<? if($mon_hp_buff) { ?>
		<span>
			대미지디버프 : <?=$mon_hp_buff?>
		</span>
		<? } ?>
		<? if($mon_def_buff) { ?>
		<span>
			방어디버프 : <?=$mon_def_buff?>
		</span>
		<? } ?>
		<? if($ds['ds_weak_turn']) { ?>
		<span>
			그로기상태 : <?=$ds['ds_weak_turn']?>회 남음
		</span>
		<? } ?>
	</div>
	<? } ?>
	<div class="count"><?=$now_mon_hp?>/<?=$ds['ds_hp']?></div>
</div>