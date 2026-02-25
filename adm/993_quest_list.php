<?php
$sub_menu = "993200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// 파라미터 처리
$sfl = ses($_GET, 'sfl', 'qu_title', 'raw');
$stx = ses($_GET, 'stx', '', 'string');
$sst = ses($_GET, 'sst', 'qu_id', 'raw');
$sod = ses($_GET, 'sod', 'desc', 'raw');
$page = ses($_GET, 'page', 1, 'int');
$cate = ses($_GET, 'cate', '', 'raw');

$sql_common = " FROM {$g5['k_quest_table']} ";
$sql_search = " WHERE (1) ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ($sfl like '%$stx%') ";
			break;
	}
	$sql_search .= " ) ";
}

if($cate) { $sql_search .= " and qu_type = '{$cate}' "; }

$state = ses($_GET, 'state', '', 'raw');
if($state) { $sql_search .= " and qu_state = '{$state}' "; }

if (!$sst) {
	$sst  = "qu_id";
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

$g5['title'] = '퀘스트 관리';
include_once('./admin.head.php');

$colspan = 7;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	추가된 퀘스트 수 <?php echo number_format($total_count) ?>개
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

<select name="cate" id="cate">
	<option value="">종류</option>
	<option value="main" <?php echo $cate == "main" ? "selected" : ""?>>메인 퀘스트</option>
	<option value="sub" <?php echo $cate == "sub" ? "selected" : ""?>>서브 퀘스트</option>
	<option value="member" <?php echo $cate == "member" ? "selected" : ""?>>멤버 퀘스트</option>
</select>

<?php 
$state = ses($_GET, 'state', '', 'raw');
?>
<select name="state" id="state">
	<option value="">상태</option>
	<option value="hidden" <?php echo $state == "hidden" ? "selected" : ""?>>미표시</option>
	<option value="active" <?php echo $state == "active" ? "selected" : ""?>>진행중</option>
	<option value="done" <?php echo $state == "done" ? "selected" : ""?>>완료</option>
</select>

<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
	<option value="qu_title"<?php echo get_selected($_GET['sfl'], "qu_title", true); ?>>퀘스트 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx">
<input type="submit" value="검색" class="btn_submit">

</form>

<?php if ($is_admin == 'super') { ?>
<div class="btn_add01 btn_add">
	<a href="./993_quest_form.php" id="bo_add">퀘스트 추가</a>
</div>
<?php } ?>

<form name="fitemlist" id="fitemlist" action="./993_quest_list_update.php" onsubmit="return fitemlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">

<input type="hidden" name="cate" value="<?php echo $cate ?>">
<input type="hidden" name="state" value="<?php echo $state ?>">
<input type="hidden" name="map_id" value="<?php echo $map_id ?>">

<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">

<div class="tbl_head01 tbl_wrap">
	<table>
		<caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col style="width:  40px;" />
			<col style="width: 100px;" />
			<col/>
			<col style="width: 130px;" />
			<col style="width: 70px;"/>
			<col style="width: 80px;"/>
			<col style="width: 80px;"/>
		</colgroup>
		<thead>
			<tr>
				<th scope="col" class="bo-right">
					<label for="chkall" class="sound_only">퀘스트 전체</label>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col">분류</th>
				<th scope="col">퀘스트내용</th>
				<th scope="col">보상</th>
				<th scope="col">인원</th>
				<th scope="col">상태</th>
				<th scope="col">관리</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i=0; $quest=sql_fetch_array($result); $i++) {
				$one_update = '<a href="./993_quest_form.php?w=u&amp;qu_id='.$quest['qu_id'].'&amp;'.$qstr.'">수정</a>';
				$bg = 'bg'.($i%2);
			?>

			<tr class="<?php echo $bg; ?>">
				<td>
					<label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($quest['qu_title']) ?></label>
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					<input type="hidden" name="qu_id[<?php echo $i ?>]" value="<?php echo $quest['qu_id'] ?>" />
				</td>

				<td>
					<select name="qu_type[<?php echo $i ?>]" id="qu_type_<?php echo $i ?>" style="display: block;">
						<option value="main" <?php echo $quest['qu_type'] == "main" ? "selected" : ""?>>메인 퀘스트</option>
						<option value="sub" <?php echo $quest['qu_type'] == "sub" ? "selected" : ""?>>서브 퀘스트</option>
						<option value="member" <?php echo $quest['qu_type'] == "member" ? "selected" : ""?>>멤버 퀘스트</option>
					</select>
				<?php if($quest['qu_ch_id']){ echo get_character_name($quest['qu_ch_id']);} ?>
				<label><input type="checkbox"  name="qu_blind[<?php echo $i ?>]" value='1' <?php if($quest['qu_blind']){echo 'checked';} ?>> 익명</label>
				</td>

				<td class="txt-left">
					<input type="text" style="width:100%;" name="qu_title[<?php echo $i ?>]" value="<?php echo get_text($quest['qu_title']) ?>" id="qu_title_<?php echo $i ?>" size="20" placeholder="타이틀">
					<input type="text" style="width:100%;" name="qu_content[<?php echo $i ?>]" value="<?php echo get_text($quest['qu_content']) ?>" id="qu_content_<?php echo $i ?>" size="20" placeholder="콘텐츠">
					<input type="text" style="width:100%;" name="qu_content2[<?php echo $i ?>]" value="<?php echo get_text($quest['qu_content2']) ?>" id="qu_content2_<?php echo $i ?>" size="20" placeholder="콘텐츠">
					<input type="text" style="width:100%;" name="qu_end_msg[<?php echo $i ?>]" value="<?php echo get_text($quest['qu_end_msg']) ?>" id="qu_end_msg_<?php echo $i ?>" size="20" placeholder="완수메시지">
				</td>

				<td class="txt-left">
					<input type="text" name="qu_money[<?php echo $i ?>]" value="<?php echo $quest['qu_money']?>" placeholder="화폐"size="10"><br>
					<input type="text" name="qu_exp[<?php echo $i ?>]" value="<?php echo $quest['qu_exp']?>" placeholder="경험치"size="10"><br>
					<input type="text" name="it_id[<?php echo $i ?>]" value="<?php echo $quest['it_id']?>" placeholder="아이템(ID)"size="10"><br>
					<input type="text" name="ti_id[<?php echo $i ?>]" value="<?php echo $quest['ti_id']?>" placeholder="타이틀(ID)"size="10">
				</td>

				<td class="txt-center">
					<input type="text" name="qu_take_now[<?php echo $i ?>]" value="<?php echo $quest['qu_take_now']; ?>" size="2" style="width:30px;text-align:center;" title="현재 수행 인원"> / 
					<input type="text" name="qu_take_max[<?php echo $i ?>]" value="<?php echo $quest['qu_take_max']; ?>" size="2" style="width:30px;text-align:center;" title="최대 수행 인원">명
				</td>

				<td class="txt-center">
					<select name="qu_state[<?php echo $i ?>]" id="qu_state_<?php echo $i ?>">
						<option value="hidden" <?php echo ses($quest, 'qu_state', 'active') == "hidden" ? "selected" : ""?>>미표시</option>
						<option value="active" <?php echo ses($quest, 'qu_state', 'active') == "active" ? "selected" : ""?>>진행중</option>
						<option value="done" <?php echo ses($quest, 'qu_state', 'active') == "done" ? "selected" : ""?>>완료</option>
					</select>
				</td>
			
				<td class="td_mngsmall">
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
	<?php if ($is_admin == 'super') { ?>
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
	<input type="submit" name="act_button" value="일괄종료" onclick="document.pressed=this.value" class="btn_frmline">
	<input type="submit" name="act_button" value="일괄완료" onclick="document.pressed=this.value" class="btn_frmline">
	<?php } ?>
</div>

</form>
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;cate='.$cate.'&amp;state='.$state.'&amp;page='); ?>

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

	if(document.pressed == "일괄종료") {
		if(!confirm("선택한 퀘스트를 종료하시겠습니까?\n수행중인 경우 실패 처리되며, 멤버 퀘스트의 경우 남은 보상 아이템이 등록자에게 반환됩니다.")) {
			return false;
		}
	}

	if(document.pressed == "일괄완료") {
		if(!confirm("선택한 퀘스트를 완료 처리하시겠습니까?\n수행중인 경우 보상이 지급되며, 멤버 퀘스트의 경우 남은 보상 아이템이 등록자에게 반환됩니다.")) {
			return false;
		}
	}

	return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
