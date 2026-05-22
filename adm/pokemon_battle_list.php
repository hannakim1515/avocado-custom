<?php
$sub_menu = "091200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['pokemon_table']} po, {$g5['pokemon_battle_table']} ph ";
$sql_search = " where po.po_id=ph.po_id ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " (po.po_name like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if($cate) { $sql_search .= " and ph.ch_id = {$cate} "; }


if (!$sst) {
	$sst  = "ph.ph_id";
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

$ch_list=array();
$ch_sql = sql_query("SELECT ch_id, ch_name from {$g5['character_table']} order by ch_name asc");
for($i = 0; $row = sql_fetch_array($ch_sql); $i++) {
	$ch_list[] = $row;
};

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '보유 포켓몬 관리';
include_once('./admin.head.php');

$colspan = 13;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	보유 포켓몬 수 <?php echo number_format($total_count) ?>마리
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<select name="cate" id="cate">
	<option value="">캐릭터</option>
	<?foreach ($ch_list as $ch) {?>
		<option value="<?=$ch['ch_id'] ?>" <?=$cate == $ch['ch_id'] ? "selected" : ""?>><?=$ch['ch_name']?></option>
	<?}?>
</select>


<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="po_name"<?php echo get_selected($_GET['sfl'], "po_name", true); ?>>포켓몬 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>


<form action="./pokemon_has_list_update.php" onsubmit="return f_submit(this);" method="post">
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
				<col style="width: 100px;"/>
				<col style="width: 50px;" />
				<col style="min-width:100px;"/>
				<col style="min-width:80px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
				<col style="min-width: 30px;"/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col" class="bo-right">
						<label for="chkall" class="sound_only">포켓몬 전체</label>
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th scope="col">트레이너</th>
					<th scope="col">도트</th>
					<th scope="col">이름</th>
					<th scope="col">체</th>
					<th scope="col">공</th>
					<th scope="col">방</th>
					<th scope="col">특공</th>
					<th scope="col">특방</th>
					<th scope="col">속도</th>
					<th scope="col">회피</th>
					<th scope="col">명중</th>
					<th scope="col">상태이상</th>
					<th scope="col">전투</th>
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
						<input type="hidden" name="ph_id[<?php echo $i ?>]" value="<?php echo $po['ph_id'] ?>" />
					</td>
					<td>
						<? echo get_character_name($po['ch_id']);?>
					</td>
					<td>
						<img src="<?=G5_URL?>/pokemon/img/<?=$po['po_id']?>.png" style="width:30px;">
					</td>
					<td>
						<p><?php echo $po['po_name']?></p>
					</td>
					<td>
						<?php echo $po['hp_now']?>/<?php echo $po['hp_max']?>
					</td>
					<td>
						<?php echo $po['a']?>
					</td>
					<td>
						<?php echo $po['b']?>
					</td>
					<td>
						<?php echo $po['c']?>
					</td>
					<td>
						<?php echo $po['d']?>
					</td>
					<td>
						<?php echo $po['s']?>
					</td>
					<td>
						<?php echo $po['m']?>
					</td>
					<td>
						<?php echo $po['e']?>
					</td>
					<td>
						<?=$po['paral']?'[마비]':''?>
						<?=$po['conf']?'[혼란]':''?>
						<?=$po['conf']?'[맹독]':''?>
						<?=$po['fire']?'[화상]':''?>
					</td>
					<td>
						<?=$po['is_battle']?'전투중':''?>
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
		<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
	</div>

</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;type='.$type.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='); ?>



<script>


function f_submit(f){
	if (!is_checked("chk[]")&&document.pressed != "지급") {
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
