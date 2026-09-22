<?php
$sub_menu = "730100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['dungeon_table']} ";

$sql_search = " where (1) ";

if (!$sst) {
	$sst  = "dg_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
			{$sql_common}
			{$sql_search}
			{$sql_order}
			limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '던전 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan =10;



$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">던전설정</a></li>
	<li><a href="#anc_002">던전목록</a></li>
</ul>';

if($config['cf_dungeon_open']) {
	// 던전변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}


/** 지역 정보 **/
$ma = array();
$ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
$index = 0;
for($i=0; $map = sql_fetch_array($ma_result); $i++) { 
	$sub_ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} and ma_use_dungeon = 1 order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) { 
		$ma[$index]['name'] = $sub_map['ma_name'];
		$ma[$index]['id'] = $sub_map['ma_id'];
		$index++;
	}
}


// 스탯 정보 가져오기
$status = array();
$status_result = sql_query("select * from {$g5['status_config_table']}");
for($i=0; $st = sql_fetch_array($status_result); $i++) {
	$status[$i]['id'] = $st['st_id'];
	$status[$i]['name'] = $st['st_name'];
}

?>


<section id="anc_001" >
	<h2 class="h2_frm">던전설정</h2>
	<?php echo $pg_anchor ?>

	<form name="fdungeonconfig" method="post" id="fdungeonconfig" action="./dungeon_config_update.php" autocomplete="off">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 100px;">
			<col style="width: 80px;">
			<col style="width: 100px;">
			<col style="width: 80px;">
			<col style="width: 100px;">
			<col style="width: 100px;">
			<col style="width: 100px;">
			<col style="width: 100px;">
			<col style="width: 120px;">
			<col style="width: 170px;">
			<col style="width: 120px;">
			<col style="width: 80px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">던전오픈</th>
			<td>
				<input type="checkbox" name="cf_dungeon_open" value="1" id="cf_dungeon_open" <? if($config['cf_dungeon_open']) { ?>checked<? } ?>>
				<label for="cf_dungeon_open">사용</label>
			</td>
			<th scope="row">지역사용</th>
			<td>
				<input type="checkbox" name="cf_dungeon_map" value="1" id="cf_dungeon_map" <? if($config['cf_dungeon_map']) { ?>checked<? } ?>>
				<label for="cf_dungeon_map">사용</label>
			</td>
			<th scope="row" class="bo-left">최대등장갯수</th>
			<td>
				<input type="text" name="cf_dungeon_count" value="<?php echo $config['cf_dungeon_count'] ?>" class="frm_input" style="width: 98%;">
			</td>
			<th scope="row" class="bo-left">변동시간</th>
			<td>
				<select name="cf_dungeon_time" style="display: block; width:100%;">
					<option value="1" <?=$config['cf_dungeon_time'] == "1" ? "selected" : ""?>>1시간</option>
					<option value="3" <?=$config['cf_dungeon_time'] == "3" ? "selected" : ""?>>3시간</option>
					<option value="6" <?=$config['cf_dungeon_time'] == "6" ? "selected" : ""?>>6시간</option>
					<option value="12" <?=$config['cf_dungeon_time'] == "12" ? "selected" : ""?>>12시간</option>
					<option value="24" <?=$config['cf_dungeon_time'] == "24" ? "selected" : ""?>>24시간</option>
				</select>
			</td>
			<th scope="row" class="bo-left">마지막 갱신시간</th>
			<td>
				<input type="text" name="cf_dungeon_reset" value="<?php echo $config['cf_dungeon_reset'] ?>" class="frm_input" style="width: 98%;">
			</td>

			<th scope="row" class="bo-left">하루입장횟수</th>
			<td>
				<input type="text" name="cf_dungeon_enter" value="<?php echo $config['cf_dungeon_enter'] ?>" class="frm_input" style="width: 98%; text-align:center;">
			</td>

			<td style="vertical-align: middle;">
				<fieldset class="btn_add" style="float: none; text-align: left; margin: 0;">
					<input type="submit" value="설정" class="btn_submit" style="height: 25px; padding: 0 20px;">
				</fieldset>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
	<div style="padding-top:10px;">
		<?php echo help("※ 지역을 사용하지 않을 시, 등장지역 설정과 관계 없이 던전이 생성됩니다.");?>
		<?php echo help("※ 지역을 사용하지 않는다면, [ <a href='".G5_URL."/dungeon' target='_blank'>".G5_URL."/dungeon</a> ] 페이지에서 던전 입장이 가능합니다.");?>
		<?php echo help("※ 지역을 사용한다면, [ <a href='".G5_URL."/map' target='_blank'>".G5_URL."/map</a> ] 을 통해 던전이 생성된 지역으로 이동하여 입장이 가능합니다.");?>
	</div>
	</form>
</section>



<section id="anc_002">
	<h2 class="h2_frm">던전목록</h2>
	<?php echo $pg_anchor ?>

	<div class="local_ov01 local_ov">
		<?php echo $listall ?>
		전체 <?php echo number_format($total_count) ?> 건
	</div>

	<?php if ($is_admin == 'super') { ?>
	<div class="btn_add01 btn_add">
		<a href="./dungeon_form.php" id="bo_add">던전정보 추가</a>
	</div>
	<?php } ?>

	<form name="fsidelist" id="fsidelist" method="post" action="./dungeon_list_update.php" onsubmit="return fsidelist_submit(this);"  enctype="multipart/form-data">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		<input type="hidden" name="token" value="<?php echo $token ?>">

		<div class="tbl_head01 tbl_wrap">
			<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 80px;" />
				<col style="width: 80px;" />
				<col style="width: 300px;" />
				<col/>
				<col style="width: 80px;"/>
				<col style="width: 80px;"/>
				<col style="width: 80px;"/>
				<col style="width: 80px;"/>
				<col style="width: 80px;"/>
			</colgroup>
			<thead>
			<tr>
				<th scope="col">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">상태</th>
				<th scope="col">랭크</th>
				<th scope="col">던전명</th>
				<th scope="col">등장지역</th>
				<th scope="col" colspan="2">등장 구간</th>
				<th scope="col">최대인원</th>
				<th scope="col">사용여부</th>
				<th scope="col">관리</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$one_update = '<a href="./dungeon_form.php?w=u&amp;dg_id='.$row['dg_id'].'&amp;'.$qstr.'">수정</a>';
				$one_copy = '<a href="./dungeon_form_copy.php?dg_id='.$row['dg_id'].'&amp;'.$qstr.'" onclick="return confirm(\'해당 던전 정보를 복사하시겠습니까?\');">복사</a>';
				$bg = 'bg'.($i%2);

				// 던전 상태 가져오기
				$state = sql_fetch("select * from {$g5['dungeon_state_table']} where dg_id = '{$row['dg_id']}' and ds_state != 'E'");
				$color = "";
				switch($state['ds_state']) { 
					case "S" : $color = "yellow"; break;
					case "E" : $color = "#999"; break;
				}
			?>

			<tr class="<?php echo $bg; ?>">
				<td style="text-align: center">
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					<input type="hidden" name="dg_id[<?php echo $i ?>]" value="<?php echo $row['dg_id'] ?>" />
				</td>
				<td style="background:<?=$color?>">
					<?
						switch($state['ds_state']) { 
							case "S" : echo "열림"; break;
							case "E" : echo "닫힘"; break;
						}
					?>
				</td>
				<td>
					<input type="text" name="dg_rank[<?php echo $i ?>]" value="<?php echo $row['dg_rank'] ?>"  style="width:100%; text-align:center;">
				</td>
				<td>
					<input type="text" name="dg_title[<?php echo $i ?>]" value="<?php echo $row['dg_title'] ?>"  style="width:100%;">
				</td>
				<td style="text-align:left;">
					<? for($k=0; $k < count($ma); $k++) { ?>
						<span style="display: inline-block; vertical-align: middle; min-width: 140px;">
							<input type="checkbox" name="ma_ids[<?=$i?>][]" id="ma_ids_<?=$i?>_<?=$k?>" value="<?=$ma[$k]['id']?>" <?=strstr($row['ma_ids'], "||".$ma[$k]['id']."||") ? "checked" : "" ?> />
							<label for="ma_ids_<?=$i?>_<?=$k?>"><?=$ma[$k]['name']?></label>
						</span>
					<? } ?>
				</td>
				<td>
					<input type="text" name="dg_per_s[<?php echo $i ?>]" value="<?php echo $row['dg_per_s'] ?>"  style="width:100%; text-align:center;">
				</td>
				<td>
					<input type="text" name="dg_per_e[<?php echo $i ?>]" value="<?php echo $row['dg_per_e'] ?>"  style="width:100%; text-align:center;">
				</td>
				<td>
					<input type="text" name="dg_count[<?php echo $i ?>]" value="<?php echo $row['dg_count'] ?>"  style="width:100%; text-align:center;">
				</td>
				<td>
					<input type="checkbox" name="dg_use[<?php echo $i ?>]" value="1" <?php echo $row['dg_use']?"checked":"" ?>>
				</td>
				<td>
					<?php echo $one_update ?>
				</td>
			</tr>

			<?php
			}

			if ($i == 0)
				echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
			?>
			</tbody>
			</table>
		</div>

		<div class="btn_list01 btn_list">
			<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
			<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
		</div>

	</form>

	<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>
</section>


<script>
function fsidelist_submit(f)
{
	if (!is_checked("chk[]")) {
		alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}

	if(document.pressed == "선택삭제") {
		if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
			return false;
		}
	}

	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
