<?php
$sub_menu = "710300";
include_once('./_common.php');
$ma_id = $_REQUEST['ma_id'];

auth_check($auth[$sub_menu], 'r');

$g5['title'] = '캐릭터 위치 관리';
include_once('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

// 지역 목록
$ma_config = array();
$ma_list = array();
$ma_sql = "select ma_id, ma_name, ma_parent from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
$now_map = null;
$now_parent = "";
for($i=0; $map = sql_fetch_array($ma_result); $i++) { 
	$ma_list[$i]['name'] = $map['ma_name'];
	$ma_list[$i]['id'] = $map['ma_id'];
	$ma_list[$i]['sub'] = array();

	$ma_config[$map['ma_id']] = $map;

	$sub_ma_sql = "select ma_id, ma_name, ma_parent, ma_move from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) { 
		$ma_list[$i]['sub'][$j]['name'] = $sub_map['ma_name'];
		$ma_list[$i]['sub'][$j]['id'] = $sub_map['ma_id'];
		$ma_list[$i]['sub'][$j]['move'] = $sub_map['ma_move'];

		$ma_config[$sub_map['ma_id']] = $sub_map;

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
			// 현재 선택된 지역에 존재하는 캐릭터 목록, 존재하지 않는 캐릭터 목록
			$ch_list = array();
			$ch_not_list = array();
			$ch_result = sql_query("select * from {$g5['character_table']} where ch_state='승인' order by ch_name asc");
			for($i=0; $ch = sql_fetch_array($ch_result); $i++) {
				if($ch['ma_id'] != $ma_id) {
					$ch_not_list[] = $ch;
				} else {
					$ch_list[] = $ch;
				}
			}
		?>
		<form name="fconfigform" id="fconfigform" method="post" onsubmit="return fconfigform_submit(this);">
			<input type="hidden" name="token" value="<?php echo $token ?>" id="token">
			<input type="hidden" name="ma_id" value="<?=$ma_id?>" />
			<h2><?=$now_parent?> : <?=$now_map['ma_name']?></h2>
			<div class="map-setting-wrap">
				<div class="ch-list-box map-left-box">
					<div class="scroll-box">
						<div class="data-filter">
							<div class="input-search">
								<select class="sch-cate">
									<option value="data-name" selected>캐릭터명</option>
								</select>
								<input type="text" class="sch-text" value="" placeholder="키워드" />
								<button type="button">검색</button>
							</div>
							<button type="button" class="chk-all"><span>현재 목록 전체 선택</span></button>
						</div>

						<ul class="data-filter-list">
							<? for($i=0; $i < count($ch_list); $i++) { ?>
								<li data-name="<?=$ch_list[$i]['ch_name']?>">
									<input type="checkbox" name="ch_expert[]" value="<?=$ch_list[$i]['ch_id']?>" id="ch_expert_<?=$i?>" class="chk-filter" />
									<label for="ch_expert_<?=$i?>">
										<strong><?=$ch_list[$i]['ch_name']?></strong>
									</label>
								</li>
							<? } ?>
						</ul>
					</div>
				</div>
				<div class="control">
					<div class="btn_list01 btn_list">
						<input type="submit" name="act_button" value="《 지역이동" onclick="document.pressed=this.value">
						<br /><br />
						<input type="submit" name="act_button" value="지역이탈 》" onclick="document.pressed=this.value" style="background:#d99898;">
					</div>
				</div>
				<div class="ch-list-box map-right-box">
					<div class="scroll-box">
						<div class="data-filter">
							<div class="input-search">
								<select class="sch-cate">
									<option value="data-name" selected>캐릭터명</option>
									<option value="data-pamap">상위지역명</option>
									<option value="data-map">하위지역명</option>
								</select>
								<input type="text" class="sch-text" value="" placeholder="키워드" />
								<button type="button">검색</button>
							</div>
							<button type="button" class="chk-all"><span>현재 목록 전체 선택</span></button>
						</div>
						<ul class="data-filter-list">
							<? for($i=0; $i < count($ch_not_list); $i++) {
								$ch_map_name = "미지정";
								$ch_map_pa_name = ""; 

								if($ma_config[$ch_not_list[$i]['ma_id']]['ma_name']) {
									$ch_map_pa_name = $ma_config[$ma_config[$ch_not_list[$i]['ma_id']]['ma_parent']]['ma_name'];
									$ch_map_name = $ma_config[$ch_not_list[$i]['ma_id']]['ma_name'];
								}
							?>
								<li data-name="<?=$ch_not_list[$i]['ch_name']?>" data-pamap="<?=$ch_map_pa_name?>" data-map="<?=$ch_map_name?>">
									<input type="checkbox" name="ch_insert[]" value="<?=$ch_not_list[$i]['ch_id']?>" id="ch_insert_<?=$i?>" class="chk-filter" />
									<label for="ch_insert_<?=$i?>">
										<strong><?=$ch_not_list[$i]['ch_name']?></strong><span><?=$ch_map_pa_name?><?=$ch_map_pa_name ? " : " : ""?><?=$ch_map_name?></span>
									</label>
								</li>
							<? } ?>
						</ul>
					</div>
				</div>
			</div>
		</form>
		<? } ?>
	</div>
</div>


<script>
$(function() {
	$('.data-filter .input-search button').on('click', function() {
		let $filterList = $(this).closest('.ch-list-box');
		let $filterBox = $filterList.find('.data-filter');
		let $filterDataList = $filterList.find('.data-filter-list');

		$filterList.find('.chk-filter').prop('checked',false);
		let _data_attr = $filterBox.find('select').val();
		let _data_txt = $filterBox.find('input[type="text"]').val();

		if(_data_txt != "") {
			$filterDataList.find('li').hide();
			$filterDataList.find('li['+_data_attr+'*="'+_data_txt+'"]').show();
		} else {
			$filterDataList.find('li').show();
		}
	});

	$('.data-filter .chk-all').on('click', function() {
		let $filterList = $(this).closest('.ch-list-box');
		let $filterDataList = $filterList.find('.data-filter-list');

		$filterDataList.find('li').each(function() {
			if($(this).is(':hidden')) {
				$(this).find('.chk-filter').prop('checked',false);
			} else {
				$(this).find('.chk-filter').prop('checked',true);
			}
		});
	});
});

function fconfigform_submit(f) {
	f.action = "./map_member_list_update.php";
	return true;
}
</script>


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

.map-setting-wrap {display:table; width:100%; max-width:800px; table-layout:fixed;}
.map-setting-wrap > * {display:table-cell; position:relative; vertical-align:top;}
.map-setting-wrap .control {width:120px; text-align:center; vertical-align:middle; padding:10px;}
.map-setting-wrap .scroll-box {display:block; position:relative; border:1px solid #dadada; border-radius:10px; background:#fafafa; padding:5px; box-sizing:border-box;}
.map-setting-wrap .scroll-box ul {display:block; position:relative; padding:5px; height:calc(100vh - 190px); overflow:auto;}
.map-setting-wrap .scroll-box .data-filter {display:block; position:relative; margin-bottom:5px; border:1px solid #dadada; background:#fff; padding:5px; border-radius:5px;}
.map-setting-wrap .scroll-box .data-filter .input-search {overflow:hidden; margin-bottom:5px;}
.map-setting-wrap .scroll-box .data-filter .input-search > * {display:block; position:relative; float:left; box-sizing:border-box; height:30px;}
.map-setting-wrap .scroll-box .data-filter .input-search select {width:30%;}
.map-setting-wrap .scroll-box .data-filter .input-search input {width:50%; border-left-width:0;}
.map-setting-wrap .scroll-box .data-filter .input-search button {width:20%; background:#666; color:#fff; border:none;}
.map-setting-wrap .scroll-box .data-filter .chk-all {display:block; position:relative; width:100%; height:30px; background:#333; color:#fff; border:none;}
.map-setting-wrap .scroll-box .data-filter + ul {height:calc(100vh - 270px);}

.map-setting-wrap li {display:block; position:relative; white-space:nowrap; z-index:0;}
.map-setting-wrap li + li {border-top:1px solid #eaeaea;}
.map-setting-wrap li input {display:block; position:absolute; left:10px; top:50%; transform:translateY(-50%); -webkit-transform:translateY(-50%);}
.map-setting-wrap li label {display:block; vertical-align:middle; padding:10px 10px 10px 25px;}
.map-setting-wrap li label:before {content:""; display:block; position:absolute; top:0; left:0; right:0; bottom:0; border-radius:5px; z-index:-1;}
.map-setting-wrap li label > * {display:inline-block; vertical-align:middle; padding:3px 5px;}
.map-setting-wrap li label span {border-radius:3px; background:#333; color:#fff; float:right;}

</style>


<?php
include_once ('./admin.tail.php');
?>
