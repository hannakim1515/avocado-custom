<?php
$sub_menu = "091210";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['pokemon_waza_table']} ";
$sql_search = " where (1) ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ({$sfl} like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if($cate) { $sql_search .= " and wa_type2 like '%{$cate}%' "; }


if (!$sst) {
	$sst  = "wa_category";
	$sod = "asc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$type_list=array();
$type_sql = sql_query("SELECT atk_type from {$g5['pokemon_type_table']} group by atk_type order by atk_type asc");
for($i = 0; $row = sql_fetch_array($type_sql); $i++) {
	$type_list[] = $row['atk_type'];
};


$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '기술 관리';
include_once('./admin.head.php');

$colspan = 11;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	추가된 기술 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<select name="cate" id="cate">
	<option value="">기술타입</option>
	<?foreach ($type_list as $type) {?>
		<option value="<?=$type?>" <?=$cate == $type ? "selected" : ""?>><?=$type?></option>
	<?}?>
</select>


<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="wa_name"<?php echo get_selected($_GET['sfl'], "wa_name", true); ?>>기술 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>


<form action="./pokemon_waza_list_update.php" onsubmit="return f_submit(this);" method="post">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">

	<input type="hidden" name="cate" value="<?php echo $cate ?>">
	<input type="hidden" name="map_id" value="<?php echo $map_id ?>">

	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 40px;" />
				<col style="width: 40px;" />
				<col style="width: 60px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col" class="bo-right">
						<label for="chkall" class="sound_only">기술 전체</label>
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th scope="col">ID</th>
					<th scope="col">이름</th>
					<th scope="col" colspan="2">타입</th>
					<th scope="col">위력</th>
					<th scope="col">명중</th>
					<th scope="col">pp</th>
					<th scope="col">설명</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i=0; $wa=sql_fetch_array($result); $i++) {
					$bg = 'bg'.($i%2);
				?>

				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
						<input type="hidden" name="wa_id[<?php echo $i ?>]" value="<?php echo $wa['wa_id'] ?>" />
						<input type="hidden" name="wa_category[<?php echo $i ?>]" value="<?php echo $wa['wa_category'] ?>" />
					</td>
					<td>
						<?=$wa['wa_id']?>
					</td>
					<td>
						<input type="text" name="wa_name[<?php echo $i ?>]" value="<?php echo $wa['wa_name']?>">
					</td>
					<td>
						<?if($wa['wa_category']=='공격'){?>
							<select name="wa_type[<?php echo $i ?>]">
								<option value="물리" <?if($wa['wa_type']=='물리'){echo 'selected';}?>>물리</option>
								<option value="특수" <?if($wa['wa_type']=='특수'){echo 'selected';}?>>특수</option>
							</select>
						<?}else{
							echo $wa['wa_type'];
						?>
							<input type="hidden" name="wa_type[<?php echo $i ?>]" value="<?php echo $wa['wa_type'] ?>" />
						<?}?>
					</td>
					<td>
						<?if($wa['wa_category']=='공격'){?>
							<select name="wa_type2[<?php echo $i ?>]">
								<option value="무" <?if($wa['wa_type2']=='무'){echo 'selected';}?>>무</option>
								<?foreach ($type_list as $type) {?>
									<option value="<?=$type?>" <?if($wa['wa_type2']==$type){echo 'selected';}?>><?=$type?></option>
								<?}?>
							</select>
						<?}else{
							echo $wa['wa_type2'];
							?>
							<input type="hidden" name="wa_type2[<?php echo $i ?>]" value="<?php echo $wa['wa_type2'] ?>" />
						<?}?>
					</td>
					<td>
						<?if($wa['wa_category']=='공격'){?>
							<input type="text" name="wa_power[<?php echo $i ?>]" style="width:50px;"value="<?php echo $wa['wa_power']?>">
						<?}else{?>
							<input type="hidden" name="wa_power[<?php echo $i ?>]" value="<?php echo $wa['wa_power'] ?>" />
						<?}?>
					</td>
					<td>
						<?if($wa['wa_category']=='공격'){?>
							<input type="text" name="wa_hit[<?php echo $i ?>]"style="width:50px;" value="<?php echo $wa['wa_hit']?>">
						<?}else{?>
							<input type="hidden" name="wa_hit[<?php echo $i ?>]" value="<?php echo $wa['wa_hit'] ?>" />
						<?}?>
					</td>
					<td>
						<input type="text" name="wa_pp_max[<?php echo $i ?>]"style="width:50px;" value="<?php echo $wa['wa_pp_max']?>">
					</td>
					<td>
						<input type="text" name="wa_content[<?php echo $i ?>]"style="width:100%" value="<?php echo $wa['wa_content']?>">
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

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;type='.$type.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='); ?>


<form action="./pokemon_waza_list_update.php" onsubmit="return f_submit(this);" method="post">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">

	<input type="hidden" name="cate" value="<?php echo $cate ?>">
	<input type="hidden" name="map_id" value="<?php echo $map_id ?>">

	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 60px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 50px;"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col">이름</th>
					<th scope="col" colspan="2">타입</th>
					<th scope="col">위력</th>
					<th scope="col">명중</th>
					<th scope="col">pp</th>
					<th scope="col">설명</th>
				</tr>
			</thead>
			<tbody>
				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="text" name="wa_name" value="">
					</td>
					<td>
						<select name="wa_type">
							<option value="물리">물리</option>
							<option value="특수">특수</option>
						</select>
					</td>
					<td>
						<select name="wa_type2">
							<option value="무">무</option>
							<?foreach ($type_list as $type) {?>
								<option value="<?=$type?>"><?=$type?></option>
							<?}?>
						</select>
					</td>
					<td>
						<input type="text" name="wa_power" style="width:50px;"value="60">
					</td>
					<td>
						<input type="text" name="wa_hit"style="width:50px;" value="100">
					</td>
					<td>
						<input type="text" name="wa_pp_max" style="width:50px;" value="20">
					</td>
					<td>
						<input type="text" name="wa_content"style="width:100%" value="">
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
	</div>

</form>


<script>
function f_submit(f)
{
	if (!is_checked("chk[]")&&document.pressed != "등록") {
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
include_once('./admin.tail.php');
?>
