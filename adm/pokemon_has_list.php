<?php
$sub_menu = "091110";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['pokemon_table']} po, {$g5['pokemon_has_table']} ph ";
$sql_search = " where po.po_id=ph.po_id ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " (po.po_species like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if($cate) { $sql_search .= " and ph.ch_id = {$cate} "; }


if (!$sst) {
	$sst  = "ph.ph_type";
	$sod = "desc";
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
	<option value="po_species"<?php echo get_selected($_GET['sfl'], "po_species", true); ?>>포켓몬 이름</option>
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
				<col style="width: 80px;" />
				<col style="width: 50px;" />
				<col style="min-width:100px;"/>
				<col style="min-width:60px;"/>
				<col style="min-width:100px;"/>
				<col style="min-width:100px;"/>
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
					<th scope="col">구분</th>
					<th scope="col">도트</th>
					<th scope="col">이름</th>
					<th scope="col">성별</th>
					<th scope="col">성격</th>
					<th scope="col">만난장소</th>
					<th scope="col"><?=$pkm_cf['st_1']?></th>
					<th scope="col"><?=$pkm_cf['st_2']?></th>
					<th scope="col"><?=$pkm_cf['st_3']?></th>
					<th scope="col"><?=$pkm_cf['st_4']?></th>
					<th scope="col"><?=$pkm_cf['st_5']?></th>
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
						<select name="ph_type[<?php echo $i ?>]">
							<option value="partner_buddy" <?if($po['ph_type']=="partner_buddy"){echo 'selected';}?>>메인파트너</option>
							<option value="partner" <?if($po['ph_type']=="partner"){echo 'selected';}?>>파트너</option>
							<option value="friend" <?if($po['ph_type']=="friend"){echo 'selected';}?>>만난포켓몬</option>
						</select>
					</td>
					<td>
						<img src="<?=G5_URL?>/pokemon/img/<?=$po['po_id']?>.png" style="width:30px;">
					</td>
					<td>
						<?if($po['po_form']){$po['po_species']=$po['po_species']."/".$po['po_form'];}?>
						<p><?php echo $po['po_name']?></p>
						<p>(<?=$po['po_species']?>)</p>
						<p>알 부화도 <input type="text" name="ph_egg[<?php echo $i ?>]" value="<?=$po['ph_egg']?>"></p>
					</td>
					<td>
						<select name="ph_sex[<?php echo $i ?>]">
							<option value="0" <?if($po['ph_sex']=="0"){echo 'selected';}?>>무성</option>
							<option value="1" <?if($po['ph_sex']=="1"){echo 'selected';}?>>암</option>
							<option value="2" <?if($po['ph_sex']=="2"){echo 'selected';}?>>수</option>
						</select>
					</td>
					<td>
						<p>
							<select name="po_pers1[<?php echo $i ?>]">
								<?for ($h=0; $h < count($poke_pers); $h++) {
									$per_check=$poke_pers[$h]." 성격";
								?>
									<option value="<?=$poke_pers[$h]?> 성격"<?if($per_check==$po['po_pers1']){echo 'selected';}?>><?=$poke_pers[$h]?> 성격</option>
								<?}?>
						
							</select>
						</p>
						<p>
							<select name="po_pers2[<?php echo $i ?>]">
								<?for ($h=0; $h < count($poke_pers2); $h++) {?>
									<option value="<?=$poke_pers2[$h]?>"<?if($poke_pers2[$h]==$po['po_pers2']){echo 'selected';}?>><?=$poke_pers2[$h]?></option>
								<?}?>
							</select>
						</p>
					</td>
					<td>
						<p>
							<input type="text" name="po_date[<?php echo $i ?>]" value="<?=$po['po_date']?>">
						</p>
						<p>
							<input type="text" name="ma_name[<?php echo $i ?>]" value="<?=$po['ma_name']?>">
						</p>
					</td>
					<td>
						<input type="text" name="po_st1[<?php echo $i ?>]" value="<?=$po['po_st1']?>" style="width:50px">
					</td>
					<td>
						<input type="text" name="po_st2[<?php echo $i ?>]" value="<?=$po['po_st2']?>" style="width:50px">
					</td>
					<td>
						<input type="text" name="po_st3[<?php echo $i ?>]" value="<?=$po['po_st3']?>" style="width:50px">
					</td>
					<td>
						<input type="text" name="po_st4[<?php echo $i ?>]" value="<?=$po['po_st4']?>" style="width:50px">
					</td>
					<td>
						<input type="text" name="po_st5[<?php echo $i ?>]" value="<?=$po['po_st5']?>" style="width:50px">
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
				<col style="min-width: 200px;" />
				<col style="width: 100px;"/>
				<col style="min-width: 200px;" />
				<col style="width: 80px;" />
				<col style="width: 80px;" />
				<col style="min-width:80px;"/>
				<col style="min-width:80px;"/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col">트레이너</th>
					<th scope="col">구분</th>
					<th scope="col">포켓몬</th>
					<th scope="col">알</th>
					<th scope="col">성별</th>
					<th scope="col">성격</th>
					<th scope="col">만난장소</th>
				</tr>
			</thead>
			<tbody>
				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="hidden" name="ch_id" id="ch_id" value="" />
						<input type="text" name="ch_name" value="" id="ch_name" onkeyup="get_ajax_character(this, 'character_list', 'ch_id');" />
						<div id="character_list" class="ajax-list-box"><div class="list"></div></div>
					</td>
					<td>
						<select name="ph_type">
							<option value="friend" <?if($po['ph_type']=="friend"){echo 'selected';}?>>만난포켓몬</option>
							<option value="partner" <?if($po['ph_type']=="partner"){echo 'selected';}?>>파트너</option>
						</select>
					</td>
					<td>
						<input type="hidden" name="po_id" id="po_id" value="" />
						<input type="text" name="po_species" value="" id="po_species" onkeyup="get_ajax_pokemon(this, 'po_list', 'po_id');" />
						<div id="po_list" class="ajax-list-box theme-box"><div class="list"></div></div>
					</td>
					<td>
						<p>알 부화도 <input type="text" name="ph_egg" value="<?=$po['ph_egg']?>"></p>
					</td>
					<td>
						<select name="ph_sex">
							<option value="0" <?if($po['ph_sex']=="0"){echo 'selected';}?>>무성</option>
							<option value="1" <?if($po['ph_sex']=="1"){echo 'selected';}?>>암</option>
							<option value="2" <?if($po['ph_sex']=="2"){echo 'selected';}?>>수</option>
						</select>
					</td>
					<td>
						<p>
							<select name="po_pers1">
								<?for ($i=0; $i < count($poke_pers); $i++) {
									$per_check=$poke_pers[$i]." 성격";
								?>
									<option value="<?=$poke_pers[$i]?> 성격"><?=$poke_pers[$i]?> 성격</option>
								<?}?>
						
							</select>
						</p>
						<p>
							<select name="po_pers2">
								<?for ($i=0; $i < count($poke_pers2); $i++) {?>
									<option value="<?=$poke_pers2[$i]?>"><?=$poke_pers2[$i]?></option>
								<?}?>
							</select>
						</p>
					</td>
					<td>
						<p>
							<input type="text" name="po_date" value="<?=$po['po_date']?>" placeholder="mm월 nn일">
						</p>
						<p>
							<input type="text" name="ma_name" value="<?=$po['ma_name']?>"  placeholder="만난장소">
						</p>
					</td>
				</tr>
			
			</tbody>
		</table>
	</div>

	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="지급" onclick="document.pressed=this.value">
	</div>

</form>

<script>
function get_ajax_pokemon(obj, list_id, rel_id, etc_value) {
	var url = g5_url + "/pokemon/_search_pokemon.php";
	ajax_load(url, obj, list_id, rel_id, etc_value);
}

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
