<?php
$sub_menu = "091100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['pokemon_table']} ";
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

if($cate) { $sql_search .= " and ma_id like '%{$cate}%' "; }


if (!$sst) {
	$sst  = "po_id";
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

$map_list=array();
$map_sql = sql_query("SELECT ma_id, ma_name from {$g5['pokemon_map_table']} where ma_type='default'");
for($i = 0; $row = sql_fetch_array($map_sql); $i++) {
	$map_list[] = $row;
};
$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '포켓몬 관리';
include_once('./admin.head.php');

$colspan = 11;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	추가된 포켓몬 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<select name="cate" id="cate">
	<option value="">맵</option>
	<?foreach ($map_list as $ma) {?>
		<option value="<?=$ma['ma_id']?>" <?=$cate == $ma['ma_id'] ? "selected" : ""?>><?=$ma['ma_name']?></option>
	<?}?>
</select>


<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="po_species"<?php echo get_selected($_GET['sfl'], "po_species", true); ?>>포켓몬 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>


<form action="./pokemon_list_update.php" onsubmit="return f_submit(this);" method="post">
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
				<col style="width: 30px;" />
				<col style="width: 80px;" />
				<col style="width: 100px;" />
				<col style="width: 20px;" />
				<col style="width: 60px;"/>
				<col style="width: 60px;"/>
				<col style="width: 40px;"/>
				<col style="width: 80px;"/>
						<col style="width: 40px;"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col" class="bo-right">
						<label for="chkall" class="sound_only">포켓몬 전체</label>
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th scope="col">ID</th>
					<th scope="col">도감번호</th>
					<th scope="col">도트</th>
					<th scope="col">이름</th>
					<th scope="col">폼</th>
					<th scope="col" colspan="2">타입</th>
					<th scope="col">성별</th>
					<th scope="col" colspan="2">진화전</th>
					<th scope="col">알</th>
					<th scope="col">등장맵</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i=0; $po=sql_fetch_array($result); $i++) {
					$bg = 'bg'.($i%2);
				?>

				<tr class="<?php echo $bg; ?>">
					<td>
						<label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($item['it_name']) ?></label>
						<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
						<input type="hidden" name="po_id[<?php echo $i ?>]" value="<?php echo $po['po_id'] ?>" />
					</td>
					<td>
						<?=$po['po_id']?>
					</td>
					<td>
						<input type="text" name="po_num[<?php echo $i ?>]" style="width:50px;" value="<?php echo $po['po_num']?>">
					</td>
					<td>
						<img src="<?=G5_URL?>/pokemon/img/<?=$po['po_id']?>.png" style="width:30px;">
					</td>
					<td>
						<input type="text" name="po_species[<?php echo $i ?>]" value="<?php echo $po['po_species']?>">
					</td>
					<td>
						<input type="text" name="po_form[<?php echo $i ?>]" value="<?php echo $po['po_form']?>">
					</td>
					<td>
						<select name="po_type1[<?php echo $i ?>]">
							<option value="" <?if($po['po_type1']==''){echo 'selected';}?>>없음</option>
							<?foreach ($type_list as $type) {?>
								<option value="<?=$type?>" <?if($po['po_type1']==$type){echo 'selected';}?>><?=$type?></option>
							<?}?>
						</select>
					</td>
					
					<td>
						<select name="po_type2[<?php echo $i ?>]">
							<option value="" <?if($po['po_type2']==''){echo 'selected';}?>>없음</option>
							<?foreach ($type_list as $type) {?>
								<option value="<?=$type?>" <?if($po['po_type2']==$type){echo 'selected';}?>><?=$type?></option>
							<?}?>
						</select>
					</td>
					<td>
						<select name="po_sex[<?php echo $i ?>]">
							<option value="0" <?if($po['po_sex']=="0"){echo 'selected';}?>>무성</option>
							<option value="1|2" <?if($po['po_sex']=="1|2"){echo 'selected';}?>>암/수</option>
							<option value="1" <?if($po['po_sex']=="1"){echo 'selected';}?>>암</option>
							<option value="2" <?if($po['po_sex']=="2"){echo 'selected';}?>>수</option>
						</select>
					</td>
					<td>
						<input type="text" name="po_ex[<?php echo $i ?>]"style="width:50px;" value="<?php echo $po['po_ex']?>">
					</td>
					<td>
						<?if($po['po_ex']){
							$po_ex=sql_fetch (" SELECT po_species, po_form from {$g5['pokemon_table']} where po_id = '{$po['po_ex']}' ");
							if($po_ex['po_form']){$po_ex['po_species'].="({$po_ex['po_form']})";}
							echo $po_ex['po_species'];
						}?>
					</td>
					<td>
						<input type="checkbox" name="po_egg[<?php echo $i ?>]" value="1" <?php if ($po['po_egg']==1) echo 'checked'; ?>>
					</td>
					<td>
						<?
						$ma_ids = array_map('trim', explode(',', $po['ma_id']));
						foreach ($map_list as $ma) {?>
							<label><input type="checkbox" name="ma_id[<?php echo $i ?>][]" value="<?=$ma['ma_id']?>" <?php if (in_array((string)$ma['ma_id'], $ma_ids, true)) echo 'checked'; ?>><?=$ma['ma_name']?></label>
						<?}?>
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

<form action="./pokemon_list_update.php" onsubmit="return f_submit(this);" method="post">
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
			<caption><?php echo $g5['title']; ?> 등록</caption>
			<colgroup>
				<col style="width: 60px;" />
				<col style="width: 80px;" />
				<col style="width: 80px;" />
				<col style="width: 60px;"/>
				<col style="width: 60px;"/>
				<col style="width: 60px;"/>
				<col style="width: 60px;"/>
				<col style="width: 60px;"/>
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col">도감번호</th>
					<th scope="col">이름</th>
					<th scope="col">폼</th>
					<th scope="col" colspan="2">타입</th>
					<th scope="col">성별</th>
					<th scope="col">진화전</th>
					<th scope="col">알</th>
					<th scope="col">등장맵</th>
				</tr>
			</thead>
			<tbody>
				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="text" name="po_num" style="width:50px;" value="">
					</td>
					<td>
						<input type="text" name="po_species" value="">
					</td>
					<td>
						<input type="text" name="po_form" value="">
					</td>
					<td>
						<select name="po_type1">
							<option value="" <?if($po['po_type1']==''){echo 'selected';}?>>없음</option>
							<?foreach ($type_list as $type) {?>
								<option value="<?=$type?>"><?=$type?></option>
							<?}?>
						</select>
					</td>
					
					<td>
						<select name="po_type2">
							<option value="" <?if($po['po_type2']==''){echo 'selected';}?>>없음</option>
							<?foreach ($type_list as $type) {?>
								<option value="<?=$type?>"><?=$type?></option>
							<?}?>
						</select>
					</td>
					<td>
						<select name="po_sex">
							<option value="0">무성</option>
							<option value="1|2">암/수</option>
							<option value="1">암</option>
							<option value="2">수</option>
						</select>
					</td>
					<td>
						<input type="text" name="po_ex" style="width:50px;" value="">
					</td>
					<td>
						<input type="checkbox" name="po_egg" value="1">
					</td>
					<td>
						<?foreach ($map_list as $ma) {?>
							<label><input type="checkbox" name="ma_id[]" value="<?=$ma['ma_id']?>" ><?=$ma['ma_name']?></label>
						<?}?>
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
