<?php
$sub_menu = "710100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
$token = get_token();

$ma_id = isset($_REQUEST['ma_id']) ? (int)$_REQUEST['ma_id'] : 0;
$s_action_type = isset($_REQUEST['s_action_type']) ? $_REQUEST['s_action_type'] : '';
$action_types = map_get_action_types();

$ma_list = array();
$ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
for($i=0; $map = sql_fetch_array($ma_result); $i++) {
	$ma_list[$i]['name'] = $map['ma_name'];
	$ma_list[$i]['id'] = $map['ma_id'];
	$ma_list[$i]['sub'] = array();

	$sub_ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) {
		$ma_list[$i]['sub'][$j]['name'] = $sub_map['ma_name'];
		$ma_list[$i]['sub'][$j]['id'] = $sub_map['ma_id'];
	}
}

$sql_search = " where 1=1 ";
if($ma_id) $sql_search .= " and a.ma_id = '{$ma_id}' ";
if($s_action_type) $sql_search .= " and a.action_type = '{$s_action_type}' ";

$sql_common = " from {$g5['map_action_table']} a left join {$g5['map_table']} m on a.ma_id = m.ma_id {$sql_search} ";
$sql_order = " order by m.ma_parent asc, m.ma_id asc, a.action_order asc, a.action_id asc ";

$sql = " select count(*) as cnt {$sql_common} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select a.*, m.ma_name {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$g5['title'] = 'MAP 행동 관리';
include_once('./admin.head.php');
$colspan = 10;
?>

<div class="groupWrap" style="min-width:1400px;">
	<div class="form-area">
		<form name="fmapactionform" method="post" action="./map_action_list_update.php" onsubmit="return fmapactionform_submit(this);" autocomplete="off">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<input type="hidden" name="action_type" value="parttime">
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width:120px;">
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th scope="row">장소</th>
							<td>
								<select name="ma_id" required>
									<option value="">선택</option>
									<? for($i=0; $i < count($ma_list); $i++) { ?>
										<? for($j=0; $j < count($ma_list[$i]['sub']); $j++) { $_ma = $ma_list[$i]['sub'][$j]; ?>
											<option value="<?=$_ma['id']?>" <?=$ma_id == $_ma['id'] ? 'selected' : ''?>><?=$ma_list[$i]['name']?> : <?=$_ma['name']?></option>
										<? } ?>
									<? } ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">행동명</th>
							<td><input type="text" name="action_name" value="" class="frm_input full" required></td>
						</tr>
						<tr>
							<th scope="row">설명</th>
							<td><textarea name="action_desc" class="frm_input full" style="height:80px;"></textarea></td>
						</tr>
						<tr>
							<th scope="row">방치형</th>
							<td>
								<label><input type="checkbox" name="is_timed" value="1" checked> 사용</label>
								&nbsp;&nbsp;
								소요시간 <input type="text" name="duration_minutes" value="240" class="frm_input" style="width:70px;"> 분
							</td>
						</tr>
						<tr>
							<th scope="row">시간 제한</th>
							<td>
								<label><input type="checkbox" name="available_time_use" value="1"> 사용</label>
								&nbsp;&nbsp;
								<input type="text" name="available_start" value="00:00" class="frm_input" style="width:70px;"> ~
								<input type="text" name="available_end" value="00:00" class="frm_input" style="width:70px;">
								<?php echo help("시간 제한을 사용하지 않으면 24시간 실행 가능합니다. 자정을 넘는 18:00~06:00 형태도 사용할 수 있습니다.") ?>
							</td>
						</tr>
						<tr>
							<th scope="row">사용</th>
							<td>
								<input type="hidden" name="action_order" value="0">
								<label><input type="checkbox" name="action_use" value="1" checked> 사용</label>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="추가" class="btn_submit">
			</div>
		</form>
	</div>

	<div>
		<form method="get" class="local_sch01 local_sch">
			<label for="ma_id">장소</label>
			<select name="ma_id" id="ma_id">
				<option value="0">전체</option>
				<? for($i=0; $i < count($ma_list); $i++) { ?>
					<? for($j=0; $j < count($ma_list[$i]['sub']); $j++) { $_ma = $ma_list[$i]['sub'][$j]; ?>
						<option value="<?=$_ma['id']?>" <?=$ma_id == $_ma['id'] ? 'selected' : ''?>><?=$ma_list[$i]['name']?> : <?=$_ma['name']?></option>
					<? } ?>
				<? } ?>
			</select>
			<input type="hidden" name="s_action_type" value="">
			<input type="submit" value="검색" class="btn_submit">
		</form>

		<div class="local_ov01 local_ov" style="margin-top:0;">전체 <?php echo number_format($total_count) ?> 건</div>

		<form name="fmapactionlist" method="post" action="./map_action_list_update.php" onsubmit="return fmapactionlist_submit(this);">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="ma_id_filter" value="<?php echo $ma_id ?>">
			<input type="hidden" name="s_action_type" value="<?php echo $s_action_type ?>">
			<div class="tbl_head01 tbl_wrap">
				<table>
					<caption><?php echo $g5['title']; ?> 목록</caption>
					<colgroup>
						<col style="width:45px;">
						<col style="width:55px;">
						<col style="width:120px;">
						<col>
						<col style="width:70px;">
						<col style="width:80px;">
						<col style="width:80px;">
						<col style="width:80px;">
						<col style="width:80px;">
						<col style="width:60px;">
					</colgroup>
					<thead>
						<tr>
							<th><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
							<th>ID</th>
							<th>장소</th>
							<th>행동</th>
							<th>방치</th>
							<th>소요</th>
							<th>시간제한</th>
							<th>시작</th>
							<th>종료</th>
							<th>사용</th>
						</tr>
					</thead>
					<tbody>
					<? for($i=0; $row=sql_fetch_array($result); $i++) { $bg = 'bg'.($i%2); ?>
						<tr class="<?=$bg?>">
							<td>
								<input type="hidden" name="action_id[<?=$i?>]" value="<?=$row['action_id']?>">
								<input type="hidden" name="action_type[<?=$i?>]" value="parttime">
								<input type="hidden" name="action_order[<?=$i?>]" value="<?=$row['action_order']?>">
								<input type="checkbox" name="chk[]" value="<?=$i?>" id="chk_<?=$i?>">
							</td>
							<td><?=$row['action_id']?></td>
							<td><?=get_text($row['ma_name'])?></td>
							<td>
								<input type="text" name="action_name[<?=$i?>]" value="<?=get_text($row['action_name'])?>" class="frm_input full">
								<textarea name="action_desc[<?=$i?>]" class="frm_input full" style="height:50px; margin-top:4px;"><?=get_text($row['action_desc'])?></textarea>
							</td>
							<td><input type="checkbox" name="is_timed[<?=$i?>]" value="1" <?=$row['is_timed'] ? 'checked' : ''?>></td>
							<td><input type="text" name="duration_minutes[<?=$i?>]" value="<?=$row['duration_minutes']?>" class="frm_input full"></td>
							<td><input type="checkbox" name="available_time_use[<?=$i?>]" value="1" <?=$row['available_time_use'] ? 'checked' : ''?>></td>
							<td><input type="text" name="available_start[<?=$i?>]" value="<?=$row['available_start']?>" class="frm_input full"></td>
							<td><input type="text" name="available_end[<?=$i?>]" value="<?=$row['available_end']?>" class="frm_input full"></td>
							<td><input type="checkbox" name="action_use[<?=$i?>]" value="1" <?=$row['action_use'] ? 'checked' : ''?>></td>
						</tr>
					<? }
					if ($i == 0) echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
					?>
					</tbody>
				</table>
			</div>
			<div class="btn_list01 btn_list">
				<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
				<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
			</div>
		</form>
		<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?ma_id='.$ma_id.'&amp;s_action_type='.$s_action_type.'&amp;page='); ?>
	</div>
</div>

<style>
.groupWrap {display:block; position:relative; overflow:hidden;}
.groupWrap > * {display:block; position:relative; width:40%; box-sizing:border-box; float:left;}
.groupWrap > * + * {float:right; width:58%;}
.groupWrap .form-area {padding:30px 0 0;}
.groupWrap .form-area .btn_confirm {padding:0;}
.groupWrap table {table-layout:fixed;}
</style>

<script>
function fmapactionform_submit(f) {
	if(!f.ma_id.value) {
		alert("장소를 선택하세요.");
		return false;
	}
	if(!f.action_name.value) {
		alert("행동명을 입력하세요.");
		return false;
	}
	return true;
}

function fmapactionlist_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}
	if(document.pressed == "선택삭제") {
		if(!confirm("선택한 행동을 정말 삭제하시겠습니까? 연결된 이벤트의 행동 연결은 해제됩니다.")) {
			return false;
		}
	}
	return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
