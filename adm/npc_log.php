<?php
$sub_menu = "400900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// NPC Form Setting Data
$ns = sql_fetch("select * from {$g5['character_table']} where ch_id = '{$ns_id}'");
if(!$ns['ch_id']) {
	alert("NPC 정보가 확인되지 않습니다.");
}


$sql_common = " from {$g5['npc_log_table']} nl, {$g5['character_table']} ch ";

$sql_search = "  where nl.ch_id = ch.ch_id and nl.ns_id = '{$ns_id}' ";
if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ({$sfl} like '{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if (!$sst) {
	$sst = "nl.nl_id";
	$sod = "desc";
}

$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 20;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$listall = '<a href="'.$_SERVER['PHP_SELF'].'?ns_id='.$ns_id.'" class="ov_listall">전체목록</a>';

$g5['title'] = "NPC {$ns['ch_name']} 관련 호감도 로그 관리";
include_once('./admin.head.php');

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$colspan = 8;

$max_character = sql_query("select *, SUM(nl.nl_value) as total from {$g5['npc_log_table']} nl, {$g5['character_table']} ch where nl.ns_id = '{$ns_id}' and ch.ch_id = nl.ch_id group by nl.ch_id order by total desc limit 0, 7");
$min_character = sql_query("select *, SUM(nl.nl_value) as total from {$g5['npc_log_table']} nl, {$g5['character_table']} ch where nl.ns_id = '{$ns_id}' and ch.ch_id = nl.ch_id group by nl.ch_id order by total asc limit 0, 7");


?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	총 대화 건수 <?php echo number_format($total_count) ?>건
</div>

<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 120px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">최소호감도</th>
			<td>
				<? for($i=0; $row=sql_fetch_array($min_character); $i++) { ?>
					<span style="display:inline-block; min-width:150px;">
						<i style="display:inline-block; width:20px; height:20px; vertical-align:middle; border:1px solid #000; overflow:hidden;"><img src="<?=$row['ch_thumb']?>" alt="" style="width:100%;" /></i>
						<?=$row['ch_name']?> (<?=$row['total']?>)
					</span>
				<? } ?>
			</td>
		</tr>
		<tr>
			<th scope="row">최대호감도</th>
			<td>
				<? for($i=0; $row=sql_fetch_array($max_character); $i++) { ?>
					<span style="display:inline-block; min-width:150px;">
						<i style="display:inline-block; width:20px; height:20px; vertical-align:middle; border:1px solid #000; overflow:hidden;"><img src="<?=$row['ch_thumb']?>" alt="" style="width:100%;" /></i>
						<?=$row['ch_name']?> (<?=$row['total']?>)
					</span>
				<? } ?>
			</td>
		</tr>
		
		</tbody>
		</table>
	</div>
	<br />

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
	<input type="hidden" name="ns_id" value="<?php echo $ns_id ?>">
	<label for="sfl" class="sound_only">검색대상</label>
	<select name="sfl" id="sfl">
		<option value="ch.ch_name"<?php echo get_selected($_GET['sfl'], "ch.ch_name"); ?>>캐릭터 이름</option>
		<option value="nl.ns_state"<?php echo get_selected($_GET['sfl'], "nl.ns_state"); ?>>호감도 상태</option>
	</select>
	<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
	<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
	<input type="submit" class="btn_submit" value="검색">
</form>

<br />

<form name="fmemberlist" id="fmemberlist" action="./npc_log_update.php" onsubmit="return fmemberlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="ns_id" value="<?php echo $ns_id ?>">

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col style="width: 45px;" />
		<col style="width: 90px;" />
		<col style="width: 120px;" />
		<col style="width: 150px;" />
		<col />
		<col style="width: 150px;" />
		<col style="width: 100px;" />
		<col style="width: 100px;" />
	</colgroup>
	<thead>
	<tr>
		<th scope="col" style="width:45px;">
			<label for="chkall" class="sound_only"><?=$config['cf_money']?> 내역 전체</label>
			<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
		</th>
		<th>일시</th>
		<th>캐릭터</th>
		<th>선물</th>
		<th>반응대사/로그</th>
		<th>호감도 선물</th>
		<th>상태</th>
		<th>호감도 변동</th>
	</tr>
	</thead>
	<tbody>
	<?php
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$ch_id = $row['ch_id'];
		$bg = 'bg'.($i%2);
	?>

	<tr class="<?php echo $bg; ?>">
		<td>
			<input type="hidden" name="nl_id[<?php echo $i ?>]" value="<?php echo $row['nl_id'] ?>">
			<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
		</td>
		<td>
			<?=$row['nl_date']?>
		</td>
		<td class="txt-left">
			<a style="color:#000;" href="./npc_log.php?sfl=ch.ch_name&amp;stx=<?=$row['ch_name']?>&amp;ns_id=<?=$ns_id?>"><? if($row['ch_icon']) { ?><img src="<?=$row['ch_icon']?>" alt="" />&nbsp;<? } ?><?=$row['ch_name']?></a>
		</td>
		<td>
			<? if($row['nl_item']) { $nl_item = get_item($row['nl_item']);?>
				<img src="<?=$nl_item['it_img']?>" alt="" style="max-width:20px; max-height:20px;"/> <?=$nl_item['it_name']?>
			<? } ?>
		</td>
		<td class="txt-left"><?php echo get_text($row['nl_log']); ?></td>
		<td>
			<? if($row['it_id']) { $item = get_item($row['it_id']);?>
				<img src="<?=$item['it_img']?>" alt="" style="max-width:20px; max-height:20px;"/> <?=$item['it_name']?>
			<? } ?>
		</td>
		<td><?=$row['ns_state']?></td>
		<td><?=$row['nl_value']?></td>
	</tr>
  
	<?php
		}
		if ($i == 0) echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
	?>
	</tbody>
	<?
	if($sfl == 'ch.ch_name') { 
	?>
	<tfoot>
		<th class="txt-right" colspan="7">총 호감도 합계</th>
		<td class="txt-center">
			<span style="font-size:1.2em; color:#000; font-weight:900;">
				<?=get_npc_point($ns_id, $ch_id)?>
			</span>
		</td>
	</tfoot>
	<? } ?>
	
	</table>
</div>


<div class="btn_list01 btn_list">
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;ns_id={$ns_id}&amp;page="); ?>


</form>



<?php
include_once ('./admin.tail.php');
?>
