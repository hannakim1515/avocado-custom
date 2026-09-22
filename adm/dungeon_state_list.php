<?php
$sub_menu = "730200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['dungeon_table']} dg, {$g5['dungeon_state_table']} ds ";

$sql_search = " where ds.dg_id = dg.dg_id ";

if (!$sst) {
	$sst  = "ds.ds_datetime";
	$sod = "desc";
}
$sql_order = " order by ds.ds_datetime desc, ds.ds_id desc ";

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

$g5['title'] = '던전 발생 현황 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan =9;

if($config['cf_dungeon_open']) {
	// 던전변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

?>



<section id="anc_002">
	<div class="local_ov01 local_ov">
		<?php echo $listall ?>
		전체 <?php echo number_format($total_count) ?> 건
	</div>

	<form name="fsidelist" id="fsidelist" method="post" action="./dungeon_state_list_update.php" onsubmit="return fsidelist_submit(this);"  enctype="multipart/form-data">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		<input type="hidden" name="token" value="<?php echo $token ?>">

		<div class="tbl_head01 tbl_wrap" style="min-width:1400px;">
			<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 80px;" />
				<col style="width: 150px;"/>
				<col style="width: 200px;"/>
				<col style="width: 80px;"/>
				<col />
				<col style="width: 250px;"/>
				<col style="width: 150px;"/>
				<col style="width: 80px;"/>
			</colgroup>
			<thead>
			<tr>
				<th scope="col">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">상태</th>
				<th scope="col">던전명</th>
				<th scope="col">등장지역</th>
				<th scope="col" colspan="2">입장현황</th>
				<th scope="col">몹 HP 현황</th>
				<th scope="col">열린시각</th>
				<th scope="col">관리</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$one_update = '<a href="./dungeon_form.php?w=u&amp;dg_id='.$row['dg_id'].'&amp;'.$qstr.'">수정</a>';
				$bg = 'bg'.($i%2);

				// 던전 상태 가져오기
				$color = "";
				$state_txt = "";
				switch($row['ds_state']) { 
					case "S" : $color = "yellow"; $state_txt = "열림"; break;
					case "E" : $color = "#999"; $state_txt = "닫힘"; break;
				}

				$dg_mem_list = get_dungeon_member($row['ds_id'], "");
			?>

			<tr class="<?php echo $bg; ?>">
				<td style="text-align: center">
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					<input type="hidden" name="ds_id[<?php echo $i ?>]" value="<?php echo $row['ds_id'] ?>" />
				</td>
				<td style="background:<?=$color?>"><?=$state_txt?></td>
				<td style="text-align:left;">
					<a href="./dungeon_form.php?w=u&dg_id=<?=$row['dg_id']?>"><?=$row['dg_title']?></a>
				</td>
				<td><?=get_map_name(($row['ds_ma_id'] ? $row['ds_ma_id'] : $row['ma_id']))?></td>

				<td>
					<?=get_dungeon_member_count($row['ds_id'])?> / <?=$row['dg_count']?>
				</td>
				<td style="text-align:left;">
					<? for($k=0; $k < count($dg_mem_list); $k++) { ?>
						<p style="display:inline-block; vertical-align:middle; margin-right:10px;">
							<em style="background:no-repeat 50% 50% url(<?=$dg_mem_list[$k]['ch_thumb']?>) rgba(0,0,0,.6); background-size:cover; border:1px solid #000; display:inline-block; vertical-align:middle; width:20px; height:20px;"></em>
							<span><?=$dg_mem_list[$k]['dm_state']=='E' ? "[퇴장]" : ""?><a href="./character_form.php?w=u&amp;ch_id=<?=$dg_mem_list[$k]['ch_id']?>"><?=$dg_mem_list[$k]['ch_name']?></a></span>
							<a href="./dungeon_state_member_delete.php?dm_id=<?=$dg_mem_list[$k]['dm_id']?>" onclick="return confirm('해당 멤버의 입장 내역을 삭제하시겠습니까?');">[X]</a>
						</p>
					<? } ?>
				</td>
				<td>
					<input type="text" name="ds_hurt[<?php echo $i ?>]" value="<?php echo $row['ds_hurt'] ?>" style="width:100px; text-align:center;">
					/
					<input type="text" name="ds_hp[<?php echo $i ?>]" value="<?php echo $row['ds_hp'] ?>" style="width:100px; text-align:center;">
				</td>
				<td>
					<? if($row['ds_datetime']) { echo $row['ds_datetime']; } ?>
				</td>
				<td>
					<a href="./dungeon_log.php?ds_id=<?=$row['ds_id']?>">로그확인</a>
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
