<?php
$sub_menu = "091003";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['pokemon_map_table']} ";
$sql_search = " where ma_type='default' ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ({$sfl} like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}


if (!$sst) {
	$sst  = ", ma_id";
	$sod = "asc";
}
$sql_order = " order by ma_type asc {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '맵 관리';
include_once('./admin.head.php');

$colspan = 12;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	추가된 맵 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="ma_name"<?php echo get_selected($_GET['sfl'], "ma_name", true); ?>>맵 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>


<form action="./pokemon_map_list_update.php" onsubmit="return f_submit(this);" method="post">
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
			<col style="width: 100px;" />
			<col style="width: 80px;" />
			<col/>
			<col style="width: 100px;" />
			<col style="width: 100px;"/>
			<col style="width: 50px;"/>
			<col style="width: 300px;"/>
			<col style="width: 50px;"/>
		
		</colgroup>
		<thead>
			<tr>
				<th scope="col" class="bo-right">
					<label for="chkall" class="sound_only">맵 전체</label>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">구분</th>
				<th scope="col">이름</th>
				<th scope="col">설명</th>
				<th scope="col">배경(선택화면)</th>
				<th scope="col">배경(자비액션)</th>
				<th scope="col">알</th>
				<th scope="col">등장포켓몬</th>
				<th scope="col">사용</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i=0; $po=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
			?>

			<tr class="<?php echo $bg; ?>">
				<td>
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					<input type="hidden" name="ma_id[<?php echo $i ?>]" value="<?php echo $po['ma_id'] ?>" />
				</td>
				<td>
					<input type="hidden" name="ma_type[<?php echo $i ?>]" value="<?php echo $po['ma_type'] ?>" />
					<?if($po['ma_type']=='default'){echo "일반";}elseif($po['ma_type']=='command'){echo "커맨드";}else{echo "이벤트";}?>
				</td>
				<td>
					<?if($po['ma_type']!='command'){?>
						<input type="text" name="ma_name[<?php echo $i ?>]" value="<?=$po['ma_name']?>">		

					<?}else{ echo $po['ma_name'];?>
						<input type="hidden" name="ma_name[<?php echo $i ?>]" value="<?php echo $po['ma_name'] ?>" />	
					<?}?>
				</td>
				<td>
					<textarea name="ma_content[<?php echo $i ?>]"><?=$po['ma_content']?></textarea>
				</td>

				<td>
					<img src="<?=$po['ma_img']?>" style="max-width:100px;">
					<input type="text" name="ma_img[<?php echo $i ?>]" value="<?=$po['ma_img']?>">
				</td>
				<td >
					<img src="<?=$po['ma_img_action']?>" style="max-width:100px;">
					<input type="text" name="ma_img_action[<?php echo $i ?>]" value="<?=$po['ma_img_action']?>">
				</td>
				<td>
					<?if($po['ma_type']=='default'){?>
						<input type="number" min="0" max="10" value="<?=$po['ma_egg']?>" name="ma_egg[<?php echo $i ?>]" placeholder="0~10">
					<?}?>
				</td>
				<td>
					<?if($po['ma_type']!='command'){?>
					<details>
						<summary>보기</summary>
						<?$p_list=array();
						$p_sql = sql_query("SELECT po_id from {$g5['pokemon_table']} where FIND_IN_SET('{$po['ma_id']}', ma_id)");
						for($h = 0; $row2 = sql_fetch_array($p_sql); $h++) {?>
							<img src="<?=G5_URL?>/pokemon/img/<?=$row2['po_id']?>.png" style="width:30px;"> 
						<?}?>
					</details>
					<?}?>
				</td>
				<td>
					<?if($po['ma_type']=='default'){?>
					<input type="checkbox" name="ma_use[<?php echo $i ?>]" value="1" <?if($po['ma_use']){echo 'checked';}?>>
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

<form action="./pokemon_map_list_update.php" onsubmit="return f_submit(this);" method="post">
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
				<col style="width: 80px;" />
				<col/>
				<col style="width: 100px;" />
				<col style="width: 100px;"/>
				<col style="width: 50px;"/>
				<col style="width: 50px;"/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col">이름</th>
					<th scope="col">설명</th>
					<th scope="col">배경(선택화면)</th>
					<th scope="col">배경(자비액션)</th>
					<th scope="col">알</th>
					<th scope="col">사용</th>
				</tr>
			</thead>
			<tbody>
				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="text" name="ma_name" value="">
					</td>
					<td>
						<textarea name="ma_content"></textarea>
					</td>
					<td>
						<input type="text" name="ma_img" value="">
					</td>
					<td >
						<input type="text" name="ma_img_action" value="">
					</td>
					<td>
						<input type="number" min="0" max="10" value="1" name="ma_egg" placeholder="0~10">
					</td>
					<td>
						<input type="checkbox" name="ma_use" value="">
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
function f_submit(f){
	
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
