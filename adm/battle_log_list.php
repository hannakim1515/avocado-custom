<?php
$sub_menu = "910210";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['battle_log_table']} ";

$sql_search = " where (1) ";

if ($stx) {
	$sql_search .= " and {$sfl} like '%{$stx}%' ";
}

if (!$sst) {
	$sst  = "bl_datetime";
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

$ch = array();
if ($sfl == 'ch_id' && $stx)
	$ch = get_member($stx);

$g5['title'] = '1:1 배틀 로그 기록';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan = 10;

?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	전체 <?php echo number_format($total_count) ?> 건
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
	<label for="sfl" class="sound_only">검색대상</label>
	<select name="sfl" id="sfl">
		<option value="ch_name"<?php echo get_selected($_GET['sfl'], "ch_name"); ?>>캐릭터 이름</option>
		<option value="re_ch_name"<?php echo get_selected($_GET['sfl'], "re_ch_name"); ?>>상대 캐릭터 이름</option>
	</select>
	<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
	<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
	<input type="submit" class="btn_submit" value="검색">
</form>
<br />
<form name="forderlist" id="forderlist" method="post" action="./battle_log_list_delete.php" onsubmit="return forderlist_submit(this);">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 150px;" />
				<col style="width: 80px;" />
				<col style="width: 80px;" />

				<col style="width: 150px;" />
				<col style="width: 80px;" />
				<col style="width: 80px;" />

				<col style="width: 150px;" />

				<col />

				<col style="width: 120px;" />
			</colgroup>
			<thead>
				<tr>
					<th scope="col" rowspan="2">
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th scope="col" colspan="3">캐릭터</th>
					<th scope="col" colspan="3">상대캐릭터</th>
					<th scope="col" rowspan="2">승자</th>
					<th scope="col" rowspan="2">보상</th>
					<th scope="col" rowspan="2">일시</th>
				</tr>
				<tr>
					<th>이름</th>
					<th>판정수치</th>
					<th>대미지</th>
					<th>이름</th>
					<th>판정수치</th>
					<th>대미지</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i=0; $row=sql_fetch_array($result); $i++) {
					$bg = 'bg'.($i%2);
				?>

				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
						<input type="hidden" name="bl_id[<?php echo $i ?>]" value="<?php echo $row['bl_id'] ?>" />
					</td>

					<td><?=$row['ch_name']?></td>
					<td><?=$row['ch_point']?></td>
					<td><?=$row['ch_damage']?></td>

					<td><?=$row['re_ch_name']?></td>
					<td><?=$row['re_ch_point']?></td>
					<td><?=$row['re_ch_damage']?></td>

					<td>
						<?
							if($row['bl_both']) echo "-";
							else if($row['bl_win'] == $row['ch_id']) echo $row['ch_name'];
							else echo $row['re_ch_name'];
						?>
					</td>
					<td class="txt-left">
						<?	
							if($row['bl_log']) {
								$temp = str_replace("@@", "<br />[{$row['re_ch_name']}] 획득 ▶", $row['bl_log']);
								$temp = str_replace("||", "&nbsp;&nbsp;&nbsp;&nbsp;", $temp);
								$temp = str_replace("+", " : ", $temp);
								echo "[{$row['ch_name']}] 획득 ▶".$temp;
							}
						?>
					</td>
					<td><?=$row['bl_datetime']?></td>
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

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>



<script>
function forderlist_submit(f)
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
