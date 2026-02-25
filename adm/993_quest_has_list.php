<?php
$sub_menu = "993300";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// 파라미터 처리
$sfl = ses($_GET, 'sfl', 'qu_title', 'raw');
$stx = ses($_GET, 'stx', '', 'string');
$sst = ses($_GET, 'sst', 'qh.qh_id', 'raw');
$sod = ses($_GET, 'sod', 'desc', 'raw');
$page = ses($_GET, 'page', 1, 'int');
$cate = ses($_GET, 'cate', '', 'raw');

$sql_common = " FROM {$g5['k_quest_has_table']} qh 
                INNER JOIN {$g5['k_quest_table']} qu ON qu.qu_id = qh.qu_id ";
$sql_search = " WHERE (1) ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " (qu.{$sfl} like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if($cate) { $sql_search .= " and qu.qu_type = '{$cate}' "; }

if (!$sst) {
	$sst  = "qh.qh_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '퀘스트 현황 관리';
include_once('./admin.head.php');

$colspan = 7;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	수주된 퀘스트 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<select name="cate" id="cate">
	<option value="">종류</option>
	<option value="main" <?php echo $cate == "main" ? "selected" : ""?>>메인 퀘스트</option>
	<option value="sub" <?php echo $cate == "sub" ? "selected" : ""?>>서브 퀘스트</option>
	<option value="member" <?php echo $cate == "member" ? "selected" : ""?>>멤버 퀘스트</option>
</select>

<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="qu_title"<?php echo get_selected($_GET['sfl'], "qu_title", true); ?>>퀘스트 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>



<form name="fitemlist" id="fitemlist" action="./993_quest_has_list_update.php" onsubmit="return fitemlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="cate" value="<?php echo $cate ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
	<table>
		<caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col style="width: 40px;" />
			<col style="width: 100px;" />
			<col/>
			<col style="width: 120px;"/>
			<col style="width: 140px;"/>
			<col style="width: 70px;"/>
		</colgroup>
		<thead>
			<tr>
				<th scope="col" class="bo-right">
					<label for="chkall" class="sound_only">퀘스트 전체</label>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">캐릭터</th>
				<th scope="col">퀘스트내용</th>
				<th scope="col">상태</th>
				<th scope="col">시간</th>
				<th scope="col">로그</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i=0; $quest=sql_fetch_array($result); $i++) {
				
				$bg = 'bg'.($i%2);
			?>

			<tr class="<?php echo $bg; ?>">
				<td>
					<label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($quest['qu_title']) ?></label>
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					<input type="hidden" name="qh_id[<?php echo $i ?>]" value="<?php echo $quest['qh_id'] ?>" />
				</td>
				<td>
					<?php if($quest['ch_id']){ echo get_character_name($quest['ch_id']);}?>
				</td>

				<td class="txt-left">
					<p><strong><?php echo get_text($quest['qu_title']) ?></strong></p>
					<p style="color:#666;font-size:12px;"><?php echo get_text($quest['qu_content']) ?></p>
				</td>

				<td>
					<select name="qh_state[<?php echo $i ?>]" id="qh_state_<?php echo $i ?>">
						<option value="수행중" <?php echo $quest['qh_state'] == '수행중' ? 'selected' : ''; ?>>수행중</option>
						<option value="완료" <?php echo $quest['qh_state'] == '완료' ? 'selected' : ''; ?>>완료</option>
						<option value="실패" <?php echo $quest['qh_state'] == '실패' ? 'selected' : ''; ?>>실패</option>
					</select>
				</td>
				<td style="font-size:11px;">
					<?php if($quest['qh_starttime']) { ?>
					<div>시작: <?php echo substr($quest['qh_starttime'], 0, 10); ?></div>
					<?php } ?>
					<?php if($quest['qh_endtime']) { ?>
					<div>종료: <?php echo substr($quest['qh_endtime'], 0, 10); ?></div>
					<?php } ?>
				</td>
				<td class="td_mngsmall">
					<?php if($quest['qh_log']) { ?>
					<a href="<?php echo $quest['qh_log']; ?>" target="_blank">로그</a>
					<?php } else { echo '-'; } ?>
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
<p>이 페이지에서 완료처리시 보상 지급되지 않습니다.</p>
<div class="btn_list01 btn_list">
	<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
	<?php if ($is_admin == 'super') { ?>
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
	<?php } ?>
</div>

</form>
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='); ?>

<script>
function fitemlist_submit(f)
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
include_once('./admin.tail.php');
?>
