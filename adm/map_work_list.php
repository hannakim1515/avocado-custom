<?php
$sub_menu = "710500";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
$token = get_token();

$s_claimed = isset($_REQUEST['s_claimed']) ? $_REQUEST['s_claimed'] : 'active';

$sql_search = " where 1=1 ";
if($s_claimed == 'active') {
	$sql_search .= " and w.reward_claimed = '0' ";
} else if($s_claimed == 'claimed') {
	$sql_search .= " and w.reward_claimed = '1' ";
}

$sql_common = "
	from {$g5['map_work_table']} w
	left join {$g5['map_action_table']} a on w.action_id = a.action_id
	left join {$g5['map_table']} m on w.ma_id = m.ma_id
	left join {$g5['character_table']} ch on w.ch_id = ch.ch_id
	left join {$g5['map_event_table']} me on w.result_event_id = me.me_id
	{$sql_search}
";
$sql_order = " order by w.work_id desc ";

$row = sql_fetch(" select count(*) as cnt {$sql_common} ");
$total_count = $row['cnt'];

$rows = 50;
$total_page = ceil($total_count / $rows);
if($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select w.*, a.action_name, a.action_type, m.ma_name, ch.ch_name, me.me_title {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$g5['title'] = 'MAP 작업 현황';
include_once('./admin.head.php');
$colspan = 10;
?>

<form method="get" class="local_sch01 local_sch">
	<label for="s_claimed">상태</label>
	<select name="s_claimed" id="s_claimed">
		<option value="active" <?=$s_claimed == 'active' ? 'selected' : ''?>>진행/완료대기</option>
		<option value="claimed" <?=$s_claimed == 'claimed' ? 'selected' : ''?>>수령/초기화 완료</option>
		<option value="all" <?=$s_claimed == 'all' ? 'selected' : ''?>>전체</option>
	</select>
	<input type="submit" value="검색" class="btn_submit">
</form>

<div class="local_ov01 local_ov">전체 <?php echo number_format($total_count) ?> 건</div>

<form name="fmapworklist" method="post" action="./map_work_list_update.php" onsubmit="return fmapworklist_submit(this);">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<input type="hidden" name="s_claimed" value="<?php echo $s_claimed ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<div class="tbl_head01 tbl_wrap">
		<table>
			<caption><?php echo $g5['title']; ?> 목록</caption>
			<colgroup>
				<col style="width:45px;">
				<col style="width:60px;">
				<col style="width:120px;">
				<col style="width:120px;">
				<col>
				<col style="width:135px;">
				<col style="width:135px;">
				<col style="width:100px;">
				<col style="width:120px;">
				<col style="width:90px;">
			</colgroup>
			<thead>
				<tr>
					<th><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
					<th>ID</th>
					<th>캐릭터</th>
					<th>장소</th>
					<th>작업</th>
					<th>시작</th>
					<th>완료 예정</th>
					<th>상태</th>
					<th>결과 이벤트</th>
					<th>수령</th>
				</tr>
			</thead>
			<tbody>
			<? for($i=0; $row=sql_fetch_array($result); $i++) {
				$row = map_refresh_work_status($row);
				$bg = 'bg'.($i%2);
				$status_label = $row['work_status'];
				if($row['work_status'] == 'WORKING') $status_label = '진행 중';
				if($row['work_status'] == 'COMPLETE') $status_label = '완료';
				if($row['work_status'] == 'CANCELLED') $status_label = '초기화';
			?>
				<tr class="<?=$bg?>">
					<td>
						<input type="hidden" name="work_id[<?=$i?>]" value="<?=$row['work_id']?>">
						<? if($row['reward_claimed'] == '0') { ?>
							<input type="checkbox" name="chk[]" value="<?=$i?>" id="chk_<?=$i?>">
						<? } ?>
					</td>
					<td><?=$row['work_id']?></td>
					<td><?=get_text($row['ch_name'])?></td>
					<td><?=get_text($row['ma_name'])?></td>
					<td>
						<strong><?=get_text($row['action_name'])?></strong>
						<span style="display:block; font-size:11px; color:#777;"><?=map_get_action_type_name($row['action_type'])?></span>
					</td>
					<td><?=$row['started_at']?></td>
					<td><?=$row['complete_at']?></td>
					<td><?=$status_label?></td>
					<td><?=$row['me_title'] ? get_text($row['me_title']) : '-'?></td>
					<td>
						<? if($row['reward_claimed'] == '0') { ?>
							대기
						<? } else if($row['work_status'] == 'CANCELLED') { ?>
							초기화
						<? } else { ?>
							완료
						<? } ?>
					</td>
				</tr>
			<? }
			if($i == 0) echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
			?>
			</tbody>
		</table>
	</div>
	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="선택초기화" onclick="document.pressed=this.value">
	</div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?s_claimed='.$s_claimed.'&amp;page='); ?>

<script>
function fmapworklist_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}
	if(document.pressed == "선택초기화") {
		if(!confirm("선택한 작업을 보상 없이 초기화하시겠습니까?")) {
			return false;
		}
	}
	return true;
}
</script>

<?php
include_once('./admin.tail.php');
?>
