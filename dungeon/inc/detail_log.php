<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// insert_dungeon_log($category, $dungeon_state, $user, $skill, $value, $is_cri = "", $log = "")
//$temp_log = "밍밍이가 괴성을 내지릅니다&&&&<p><span>뫄뫄</span>에게 <strong>999</strong>대미지 <i>!</i></p><p><span>뫄뫄</span>에게 <strong>999</strong>대미지 <i>!</i></p><p><span>뫄뫄</span>에게 <strong>999</strong>대미지 <i>!</i></p>";
//insert_dungeon_log("몬스터", $ds, null, null, 999, 0, $temp_log);

if($ds['ds_state'] != 'E') {
	$log_result = sql_query("select * from {$g5['dungeon_log_table']} where dl_cate != '효과' and ds_id = '{$ds_id}' order by dl_id desc limit 0, 50");
} else {
	$log_result = sql_query("select * from {$g5['dungeon_log_table']} where dl_cate != '효과' and ds_id = '{$ds_id}' order by dl_id desc");
}

$log_character_list = array();
?>

<ul class="log-list">
	<? for($i=0; $lo = sql_fetch_array($log_result); $i++) {
		$lo_ch = null;

		if($lo['ch_id'] && $ds['ds_state'] != "E") { 
			$lo_key = array_search($lo['ch_id'], array_column($dm_list_All, 'ch_id'));
			$lo_ch = $dm_list_All[$lo_key];
		} else {
			if(!$log_character_list[$lo['ch_id']]['ch_id']) {
				$log_character_list[$lo['ch_id']] = get_character($lo['ch_id']);
			}
			$lo_ch = $log_character_list[$lo['ch_id']];
		}

		$mon_log = "";
		if($lo['dl_cate'] == "몬스터") {
			$mon_log = explode("&&&&",$lo['dl_log']);
			$lo['dl_log'] = "<p class='msg'>{$mon_log[0]}</p><div class='result-log'>{$mon_log[1]}</div>";
		}
	?>
		<li>
			<div class="item" data-cate="<?=$lo['dl_cate']?>">
				<? if($lo_ch['ch_thumb']) { ?>
				<div class="thumb <?=$lo_ch['dm_aggro'] == "Y" ? "aggro" : ""?>">
					<em><img src="<?=$lo_ch['ch_thumb']?>" alt="" /></em>
				</div>
				<? } ?>
				<div class="comment">
					<div><?=$lo['dl_log']?></div>
				</div>
			</div>
		</li>
	<? } ?>
</ul>