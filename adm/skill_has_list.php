<?php
$sub_menu = "720200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['skill_has_table']} ";

$sql_search = " where (1) ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		case "sk_name" : 
			$sql_connet = "";
			$check_result = sql_query("select sk_id from {$g5['skill_table']} where sk_name like '%{$stx}%' ");
			$sql_search .= "(";
			for($i=0; $row = sql_fetch_array($check_result); $i++) {
				$sql_search .= "{$sql_connet} sk_id = '{$row['sk_id']}' ";
				$sql_connet = " or ";
			}
			$sql_search .= ")";
		break;
		case "ch_name" : 
			$sql_connet = "";
			$check_result = sql_query("select ch_id from {$g5['character_table']} where ch_name = '{$stx}' ");
			$sql_search .= "(";
			for($i=0; $row = sql_fetch_array($check_result); $i++) {
				$sql_search .= "{$sql_connet} ch_id = '{$row['ch_id']}' ";
				$sql_connet = " or ";
			}
			$sql_search .= ")";
		break;

		default :
			$sql_search .= " ({$sfl} like '%{$stx}%') ";
		break;
	}
	$sql_search .= " ) ";
}

if (!$sst) {
	$sst  = "sh_id";
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


$g5['title'] = '스킬 보유현황 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan = 7;

if (strstr($sfl, "ch_id"))
	$ch_id = $stx;
else
	$ch_id = "";


$sk = array();
$skill_list = array();
$temp_result = sql_query("select * from {$g5['skill_table']}");
for($i=0; $row = sql_fetch_array($temp_result); $i++) {
	$sk[$row['sk_id']] = $row;
	$sk[$row['sk_id']]['level'] = array();
	$skill_level_result = sql_query("select sl_name, sl_level from {$g5['skill_level_table']} where sk_id = '{$row['sk_id']}' order by sl_level asc");
	$indexing = 0;
	for($k=0; $skill_level = sql_fetch_array($skill_level_result); $k++) {
		$sk[$row['sk_id']]['level'][$indexing]['id'] = $skill_level['sl_level'];
		$sk[$row['sk_id']]['level'][$indexing]['name'] = $skill_level['sl_name'];
		$indexing++;
	}
	$skill_list[] = $row;
}

$ch = array();
$temp_result = sql_query("select * from {$g5['character_table']}");
for($i=0; $row = sql_fetch_array($temp_result); $i++) {
	$ch[$row['ch_id']] = $row;
}

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';

/** 캐릭터 정보 **/
$character_list = array();
$character_result = sql_query("select * from {$g5['character_table']} order by ch_type asc, ch_name asc");
for($i = 0; $row = sql_fetch_array($character_result); $i++) {
	$character_list[] = $row;
}
?>

<datalist id ="ch_name_list">
	<? for($i = 0; $i < count($character_list); $i++) { echo "<option value='{$character_list[$i]['ch_name']}' />"; }?>
</datalist>
<datalist id ="sk_name_list">
	<? for($i = 0; $i < count($skill_list); $i++) { echo "<option value='{$skill_list[$i]['sk_name']}' />"; }?>
</datalist>

<style>
.groupWrap {display:block; position:relative; overflow:hidden;}
.groupWrap > * {display:block; position:relative; width:40%; box-sizing:border-box; float:left;}
.groupWrap > * + * {float:right; width:58%;}
</style>

<div class="groupWrap">

	<section id="anc_001">
		<h2 class="h2_frm">스킬 지급</h2>
		<form name="ftitlelist2" method="post" id="ftitlelist2" action="./skill_has_update.php" autocomplete="off">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width: 120px;">
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th scope="row">지급유형</th>
							<td>
								<input type="radio" id="take_type_01" name="take_type" value="P" checked onclick="if(document.getElementById('take_type_01').checked) $('#take_member_name').show();"/>
								<label for="take_type_01">개별지급</label>
								&nbsp;&nbsp;
								<input type="radio" id="take_type_02" name="take_type" value="A" onclick="if(document.getElementById('take_type_02').checked) $('#take_member_name').hide();"/>
								<label for="take_type_02">전체지급</label>
							</td>
						</tr>
						<tr id="take_member_name">
							<th scope="row">캐릭터 이름</th>
							<td>
								<?php echo help('개별지급 시 입력') ?>
								<input type="text" name="ch_name" value="" list="ch_name_list" />
							</td>
						</tr>
						<tr>
							<th scope="row">스킬 이름</th>
							<td>
								<input type="text" name="sk_name" value="" list="sk_name_list" />
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="확인" class="btn_submit" id="btn_submit">
			</div>
		</form>
	</section>

	<section id="anc_002">
		<h2 class="h2_frm">스킬 보유 현황</h2>
		<div class="local_ov01 local_ov">
			<?php echo $listall ?>
			전체 <?php echo number_format($total_count) ?> 건
		</div>

		<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
		<label for="sfl" class="sound_only">검색대상</label>
		<select name="sfl" id="sfl">
			<option value="ch_name"<?php echo get_selected($_GET['sfl'], "ch_name"); ?>>캐릭터 이름</option>
			<option value="sk_name"<?php echo get_selected($_GET['sfl'], "sk_name"); ?>>스킬 이름</option>
		</select>
		<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
		<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
		<input type="submit" class="btn_submit" value="검색">
		</form>
		<br />

		<form name="ftitlelist" id="ftitlelist" method="post" action="./skill_has_list_update.php" onsubmit="return ftitlelist_submit(this);">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		<input type="hidden" name="token" value="<?php echo $token ?>">

		<div class="tbl_head01 tbl_wrap">
			<table style="table-layout:fixed;">
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 120px;" />
				<col style="width: 50px;" />
				<col />
				<col style="width: 140px;" />
				<col style="width: 100px;" />
				<col style="width: 50px;" />
			</colgroup>
			<thead>
			<tr>
				<th>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th>소유자</th>
				<th colspan="2">스킬이름</th>
				<th>장착일시</th>
				<th>레벨</th>
				<th>장착</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
				$temp_sk_id = $row['sk_id'];
				$temp_sk = $sk[$temp_sk_id];
				$temp_ch_id = $row['ch_id'];
				$temp_ch = $ch[$temp_ch_id];
			?>

			<tr class="<?php echo $bg; ?>">
				<td>
					<input type="hidden" name="sh_id[<?php echo $i ?>]" value="<?php echo $row['sh_id'] ?>" />
					<input type="hidden" name="old_sh_use[<?php echo $i ?>]" value="<?php echo $row['sh_use'] ?>" />
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
				</td>
				<td class="txt-left">
					<?php echo get_text($temp_ch['ch_name']); ?>
				</td>
				<td>
					<img src="<?=$temp_sk['sk_img']?>" style="max-height:30px; max-width:30px;" />
				</td>
				
				<td class="txt-left">
					<?php echo get_text($temp_sk['sk_name']); ?>
				</td>
				<td>
					<?php echo $row['sh_datetime']; ?>
				</td>
				<td>
					<select name="sh_level[<?=$i?>]" style="width:100%;">
						<option value="">-</option>
						<? for($k=0; $k < count($temp_sk['level']); $k++) { ?>
							<option value="<?=$temp_sk['level'][$k]['id']?>" <?=$temp_sk['level'][$k]['id'] == $row['sh_level'] ? "selected" : ""?>><?=$temp_sk['level'][$k]['name']?></option>
						<? } ?>
					</select>
				</td>
				<td>
					<input type="checkbox" name="sh_use[<?=$i?>]" value="1" <?=$row['sh_use'] == '1' ? "checked" : ""?>>
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

		<script>
			function ftitlelist_submit(f) {
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

	</section>

</div>

<script>

function get_ajax_skill(obj, list_id, rel_id, etc_value) {
	var url = g5_url + "/adm/skill_search.php";
	ajax_load(url, obj, list_id, rel_id, etc_value);
}

</script>

<?php
include_once ('./admin.tail.php');
?>
