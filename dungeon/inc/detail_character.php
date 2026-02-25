<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$status_list = array();
$status_config = array();
$status_result = sql_query("select * from {$g5['status_config_table']} order by st_order asc");
for($i=0; $rows = sql_fetch_array($status_result); $i++) {
	$status_list[] = $rows;
	$status_config[$rows['st_id']] = $rows;
}


?>

<ul class="character-list">

	<? for($i=0; $i < count($dm_list_All); $i++) { 
		$d = $dm_list_All[$i];

		// 버프 효과 가져오기
		$d_buff = sql_query("select * from {$g5['dungeon_log_table']} where ds_id = '{$ds_id}' and ch_id = '{$d['ch_id']}' and dl_cate = '효과' and dl_keep_limit > 0");
		$code_buff = array();
		$status_buff = array();
		for($j=0; $b = sql_fetch_array($d_buff); $b++) {
			switch($b['dl_function']) {
				case "연동코드강화" :
					if(!$code_buff[$b['st_code']]) $code_buff[$b['st_code']] = 0;
					$code_buff[$b['st_code']]+=$b['dl_value'];
				break;
				case "스탯강화" :
					if(!$status_buff[$b['st_id']]) $status_buff[$b['st_id']] = 0;
					$status_buff[$status_config[$b['st_id']]['st_name']]+=$b['dl_value'];
				break;
			}
		}
	?>
		<li class="state-<?=$d['dm_state']?>">
			<div class="item  <?=$d['dm_aggro'] == "Y" ? "aggro" : ""?>">
				<div class="thumb">
					<em><img src="<?=$d['ch_thumb']?>" alt="" /></em>
				</div>
				<div class="comment">
					<div class="in">
						<div class="descript">
							<div class="name">
								<strong><?=$d['ch_name']?></strong>
							</div>
							<div class="txt">
								<?=$d['dm_comment'] ? $d['dm_comment'] : "<p><i>Loading...</i></p>"?>
							</div>

							<div class="buffs">
									<?
										if($d['dm_aggro'] == 'Y') { echo "<span data-buff='도발' title='{$key}'></span>"; }
										if($d['dm_evasion'] == 'Y') { echo "<span data-buff='회피' title='{$key}'></span>"; }

										foreach($code_buff as $key => $value) {
											if($value > 0) $value = "+".$value;
											if($value < 0) $value = "-".$value;
											echo "<span data-buff='{$key}' title='{$key}'><em>{$key}</em> <i>{$value}</i></span>";
										}
										foreach($status_buff as $key => $value) {
											if($value > 0) $value = "+".$value;
											if($value < 0) $value = "-".$value;
											echo "<span data-buff='{$key}' title='{$key}'><em>{$key}</em> <i>{$value}</i></span>";
										}
									?>
							</div>
						</div>
						
						<div class="status">
							<?
								// 스탯 목록 뽑아오기
								for($j=0; $j < count($status_list); $j++) {
									$_st = $status_list[$j];
									if(!$_st['st_use_max']) {
										// Bar 형태로 스탯을 출력한다.
										$_max = $d['st_id_'.$_st['st_id']] + $d['st_id_'.$_st['st_id'].'_mod'];
										$_use = $d['st_id_'.$_st['st_id'].'_use'];
										$_now = $_max - $_use;
										$_per = $_max == 0 ? 0 : $_now/$_max*100;
										$_class = "bar";
									} else {
										// 텍스트 형태로 스탯을 출력한다
										$_max = $d['st_id_'.$_st['st_id']] + $d['st_id_'.$_st['st_id'].'_mod'];
										$_use = $d['st_id_'.$_st['st_id'].'_use'];
										$_now = $_max - $_use;
										$_per = $_max == 0 ? 0 : $_now/$_max*100;
										$_class = "text";
									}
							?>
									<dl class="<?=$_class?> status-ty<?=$_st['st_id']?>">
										<dt><?=$_st['st_name']?></dt>
										<dd>
											<em><?=$_now?></em><strong><?=$_max?></strong>
											<div class="graph">
												<p>
													<span style="width: <?=$_per?>%;"></span>
												</p>
											</div>
										</dd>
									</dl>
							<? } ?>
						</div>
						
					</div>
				</div>
			</div>
		</li>


	<? } ?>
</ul>