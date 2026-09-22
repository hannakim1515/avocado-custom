<?php
$sub_menu = "710200";
include_once('./_common.php');
$ma_id = $_REQUEST['ma_id'];

auth_check($auth[$sub_menu], 'w');
$token = get_token();

$end_result = sql_query("select * from {$g5['map_table']} where ma_id != '{$ma_id}' and ma_id != ma_parent order by ma_parent asc, ma_id asc");
$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="저장" class="btn_submit" accesskey="s">
</div>';

$g5['title'] = "지역별 통행 관리";
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

// 지역 목록
$ma_list = array();
$ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
$now_map = null;
$now_parent = "";
for($i=0; $map = sql_fetch_array($ma_result); $i++) { 
	$ma_list[$i]['name'] = $map['ma_name'];
	$ma_list[$i]['id'] = $map['ma_id'];
	$ma_list[$i]['sub'] = array();

	$sub_ma_sql = "select ma_id, ma_name, ma_move from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) { 
		$ma_list[$i]['sub'][$j]['name'] = $sub_map['ma_name'];
		$ma_list[$i]['sub'][$j]['id'] = $sub_map['ma_id'];
		$ma_list[$i]['sub'][$j]['move'] = $sub_map['ma_move'];

		if($sub_map['ma_id'] == $ma_id) {
			$now_map = $sub_map;
			$now_parent = $map['ma_name'];
		}
	}
}

?>

<div class="mapMoveLayout">
	<div class="mapList">
		<ul>
			<? for($i=0; $i < count($ma_list); $i++) { ?>
				<li>
					<p><?=$ma_list[$i]['name']?></p>
					<? if(count($ma_list[$i]['sub']) > 0) { 
						echo "<ul>";
						for($j=0; $j < count($ma_list[$i]['sub']); $j++) {
							$_ma = $ma_list[$i]['sub'][$j];
					?>
						<li>
							<a href="?ma_id=<?=$_ma['id']?>" class="<?=($_ma['id'] == $ma_id ? "selected" : "")?>"><?=$_ma['name']?></a>
						</li>
					<?	}
						echo "</ul>";
					} ?>
				</li>
			<? } ?>
		</ul>
	</div>
	<div class="mapMoveList">
		<? if(!$ma_id) { ?>
		<div class="none">
			<p>이동 설정을 할 지역을 선택해 주세요</p>
		</div>
		<? } else {
			// 현재 선택 된 지역을 제외한 나머지 지역 선택
		?>
		<h2><?=$now_parent?> : <?=$now_map['ma_name']?></h2>
		<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);">
			<input type="hidden" name="token" value="<?php echo $token ?>" id="token">
			<input type="hidden" name="ma_id" value="<?=$ma_id?>" />
			<ul class="map-move-setting">
				<? for($i=0; $i < count($ma_list); $i++) { echo "<li class='map-group'>"; ?>
					<div>
						<p><input type="checkbox" id="chk_all_<?=$i?>" class="allChk" /><label for="chk_all_<?=$i?>"><?=$ma_list[$i]['name']?></label></p>
						<?
							if(count($ma_list[$i]['sub']) > 0) { 
								echo "<ul>";
								for($j=0; $j < count($ma_list[$i]['sub']); $j++) {
									$_ma = $ma_list[$i]['sub'][$j];
									if($_ma['id'] == $ma_id) { 
										echo "<li><input type='checkbox' id='chk_mf_end_{$i}_{$j}' disabled /><label for='chk_mf_end_{$i}_{$j}' class='disabled'>{$_ma['name']}</label></li>";
									} else {
										echo "<li><input type='checkbox' id='ma_move_{$i}_{$j}' class='chk-map' name='ma_move[]' ".(strstr($now_map['ma_move'], "||".$_ma['id']."||") ? "checked" : "")." value='{$_ma['id']}'/><label for='ma_move_{$i}_{$j}'>{$_ma['name']}</label></li>";
									}
								}
								echo "</ul>";
							}
						?>
					</div>
				<? echo "</li>"; } ?>
			</ul>

			<?php echo $frm_submit; ?>
		</form>
		<script>
			$(function() {
				$('.map-move-setting > li').each(function() {
					let is_checked = true;
					let $pannel = $(this);
					$pannel.find('.chk-map').each(function() {
						if(!$(this).is(':checked')) {
							is_checked = false;
						}
					});
					if(is_checked) {
						$pannel.find('.allChk').prop('checked',true);
					} else {
						$pannel.find('.allChk').prop('checked',false);
					}
				});
			});

			$('.allChk').click(function(){
				var checked = $(this).is(':checked');
				if(checked) {
					$(this).closest('.map-group').find('.chk-map').prop('checked',true);
				} else {
					$(this).closest('.map-group').find('.chk-map').prop('checked',false);
				}
			});
			$('.chk-map').click(function(){
				let is_checked = true;
				let $pannel = $(this).closest('.map-group');
				$pannel.find('.chk-map').each(function() {
					if(!$(this).is(':checked')) {
						is_checked = false;
					}
				});
				if(is_checked) {
					$pannel.find('.allChk').prop('checked',true);
				} else {
					$pannel.find('.allChk').prop('checked',false);
				}
			});

			function fconfigform_submit(f){
				f.action = "./map_move_update.php";
				return true;
			}
		</script>
		<? } ?>

	</div>
</div>

<style>
ul,li {margin:0; padding:0; list-style:none;}

.mapMoveLayout {display:block; position:relative;}
.mapMoveLayout .mapList {display:block; position:sticky; top:0; left:0; width:200px; float:left; border:1px solid #dadada; background:#fafafa;}
.mapMoveLayout .mapList ul,
.mapMoveLayout .mapList li {display:block; position:relative;}
.mapMoveLayout .mapList > ul {min-height:400px; max-height:600px; overflow:auto; margin:0; padding:10px;}
.mapMoveLayout .mapList > ul > li {padding:5px 0;}
.mapMoveLayout .mapList > ul > li p {background:#333; color:#fff; font-size:14px; padding:10px; border-radius:5px;}

.mapMoveLayout .mapList > ul ul > li + li {border-top:1px solid #ddd;}
.mapMoveLayout .mapList > ul ul > li {padding:10px 5px;}
.mapMoveLayout .mapList > ul ul > li a {display:block; position:relative; padding:0 5px;}
.mapMoveLayout .mapList > ul ul > li a.selected {border-left:5px solid #29c7ca; color:#29c7ca; font-weight:800;}

.mapMoveList {margin-left:250px;}
.mapMoveList .none {display:table; width:100%; table-layout:fixed; height:400px;}
.mapMoveList .none > * {display:table-cell; vertical-align:middle; text-align:center; border:1px solid #ddd; border-radius:10px; font-size:14px; color:#ccc;}

.map-move-setting {display:block; position:relative; margin:-5px;}
.map-move-setting:after {content:""; display:block; clear:both;}
.map-move-setting > li {display:inline-block; position:relative; min-width:240px; vertical-align:top; padding:5px;}
.map-move-setting > li * {white-space:nowrap;}
.map-move-setting > li > div {display:block; position:relative; border:1px solid #dadada; border-radius:10px; padding:10px;}
.map-move-setting > li > div p {display:block; position:relative; text-align:center; padding:10px 5px; border-radius:5px; z-index:0; margin-bottom:10px;}
.map-move-setting > li > div p label {color:#fff; margin-left:5px; font-size:14px;}
.map-move-setting > li > div p label:before {content:""; display:block; position:absolute; top:0; left:0; right:0; bottom:0; z-index:-1; background:#666; border-radius:5px;}
.map-move-setting > li > div p input:checked + label:before {background:#29c7ca;}

.map-move-setting .map-group ul li {display:block; position:relative; padding:5px;}
.map-move-setting .map-group ul li + li {border-top:1px solid #eaeaea;}
.map-move-setting .map-group ul li label {display:inline-block; position:relative; vertical-align:middle; padding:4px;}
.map-move-setting .map-group ul li label.disabled {opacity:.4;}
</style>


<?php
include_once ('./admin.tail.php');
?>
