<?php
$sub_menu = "400900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

// NPC Form Setting Data
$ns = sql_fetch("select * from {$g5['character_table']} where ch_id = '{$ns_id}'");
if(!$ns['ch_id']) {
	alert("NPC 정보가 확인되지 않습니다.");
}

$sql_common = " from {$g5['npc_item_table']} ";
$sql_search = " where ch_id ='{$ns_id}' ";

if (!$sst) {
	$sst  = "ni_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 15;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
			{$sql_common}
			{$sql_search}
			{$sql_order}
			limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['SCRIPT_NAME'].'" class="ov_listall">전체목록</a>';

$g5['title'] = 'NPC '.$ns['ch_name'].' 호감도 아이템 관리';
include_once ('./admin.head.php');
$colspan = 8;


$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">호감도 아이템 등록</a></li>
	<li><a href="#anc_002">호감도 아이템 설정 목록</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
</div>';

?>

<form name="fpointlist2" method="post" id="fpointlist2" action="./npc_item_update.php" autocomplete="off">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<input type="hidden" name="ch_id" value="<?php echo $ns_id ?>">
	
	<section id="anc_001">
		<h2 class="h2_frm">호감도 아이템 등록</h2>
		<?php echo $pg_anchor ?>

		<div class="tbl_frm01 tbl_wrap">
			<table>
				<colgroup>
					<col style="width: 100px;">
					<col>
				</colgroup>
				<tbody>
					<tr>
						<th scope="row">아이템</th>
						<td>
							<input type="hidden" name="it_id" id="it_id" value="" />
							<input type="text" name="it_name" value="" id="it_name" onkeyup="get_ajax_item(this, 'item_list', 'it_id');" placeholder="아이템 이름" />
							<div style="display:inline-block; vertical-align:middle; margin-left:10px;">
								<input type="number" name="ni_value" value="0" style="font-family:'Dotum'; width:60px; height:20px; font-size:12px; text-align:center; border:none; border-bottom:1px solid #ddd; outline:0; padding:0;"/> P 획득 &nbsp;&nbsp;
								<span style="display:inline-block; vertical-align:middle;">
									최대
									<input type="number" name="ni_max_count" value="0" style="font-family:'Dotum'; width:50px; font-size:12px; height:20px; text-align:center; border:none; border-bottom:1px solid #ddd; outline:0; padding:0;"/> 회 까지
								</span>
								<span style="margin-left:10px; display:inline-block; vertical-align:middle;">
									<input type="checkbox" name="ni_max_is_total" value="1" id="ni_max_is_total" style="display:inline-block; vertical-align:middle;" />
									<label for="ni_max_is_total" style="display:inline-block; vertical-align:middle;"> 1일초기화</label>
								</span>
							</div>
							<div id="item_list" class="ajax-list-box"><div class="list"></div></div>
						</td>
					</tr>
					<tr>
						<th scope="row">기본대사</th>
						<td>
							<?php echo help("[이름] : 캐릭터 이름을 출력합니다.") ?>
							
							<input type="text" name="ni_comment" value="" style="width:100%;" />
						</td>
					</tr>
					<tr>
						<th scope="row">최대 횟수 <br />이후 대사</th>
						<td>
							<?php echo help("[이름] : 캐릭터 이름을 출력합니다.") ?>
							
							<input type="text" name="ni_max_comment" value="" style="width:100%;"/>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="btn_confirm01 btn_confirm">
			<input type="submit" value="확인" class="btn_submit">
		</div>
	</section>
</form>


<section id="anc_002">
	<h2 class="h2_frm">호감도 아이템 설정 목록</h2>
	<?php echo $pg_anchor ?>

	<div class="local_ov01 local_ov">
		<?php echo $listall ?>
		전체 <?php echo number_format($total_count) ?> 건
	</div>

	<form name="fpointlist" id="fpointlist" method="post" action="./npc_item_list_update.php" onsubmit="return fpointlist_submit(this);">
		<input type="hidden" name="sst" value="<?php echo $sst ?>">
		<input type="hidden" name="sod" value="<?php echo $sod ?>">
		<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
		<input type="hidden" name="stx" value="<?php echo $stx ?>">
		<input type="hidden" name="page" value="<?php echo $page ?>">
		<input type="hidden" name="token" value="">
		<input type="hidden" name="ch_id" value="<?php echo $ns_id ?>">

		<div class="tbl_head01 tbl_wrap">
			<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<thead>
			<tr>
				<th scope="col" style="width:30px;">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col" style="width:30px;"></th>
				<th scope="col" style="width:150px;">아이템 이름</th>
				<th scope="col" style="width:60px;">획득P</th>
				<th scope="col" style="width:60px;">최대</th>
				<th scope="col" style="width:60px;">1일초기화</th>
				<th scope="col" >기본 대사</th>
				<th scope="col" >최대횟수 이후 대사</th>
			</tr>
			</thead>
			<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
				$it_img = get_item_img($row['it_id']);
			?>

			<tr class="<?php echo $bg; ?>">
				<td>
					<input type="hidden" name="ni_id[<?php echo $i ?>]" value="<?php echo $row['ni_id'] ?>">
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
				</td>
				<td>
					<img src="<?=$it_img?>" style="max-width:30px;" alt="" />
				</td>
				<td>
					<input type="text" name="it_name[<?php echo $i ?>]" value="<?php echo $row['it_name'] ?>" class="frm_input" style="width: 98%;">
				</td>
				<td>
					<input type="text" name="ni_value[<?php echo $i ?>]" value="<?php echo $row['ni_value'] ?>" class="frm_input" style="width: 98%; text-align:center;">
				</td>
				<td>
					<input type="text" name="ni_max_count[<?php echo $i ?>]" value="<?php echo $row['ni_max_count'] ?>" class="frm_input" style="width: 98%; text-align:center;">
				</td>
				<td>
					<input type="checkbox" name="ni_max_is_total[<?php echo $i ?>]" value="1" <?php echo $row['ni_max_is_total']?"checked":"" ?>>
				</td>
				<td>
					<input type="text" name="ni_comment[<?php echo $i ?>]" value="<?php echo $row['ni_comment'] ?>" class="frm_input" style="width: 98%;">
				</td>
				<td>
					<input type="text" name="ni_max_comment[<?php echo $i ?>]" value="<?php echo $row['ni_max_comment'] ?>" class="frm_input" style="width: 98%;">
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

	<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;ns_id={$ns_id}&amp;page="); ?>
</section>

<script>
function fpointlist_submit(f)
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
