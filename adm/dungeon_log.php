<?php
$sub_menu = "730200";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

if($config['cf_dungeon_open']) {
	// 던전변동 및 업데이트
	$config['cf_dungeon_reset'] = set_reset_dungeon();
}

$ds = get_dungeon_state($ds_id);
if (!$ds['ds_id']) alert('존재하지 않는 던전 입니다.');

$sql_common = " from {$g5['dungeon_log_table']} ";
$sql_search = " where ds_id = '{$ds_id}' and dl_cate != '효과' ";

if (!$sst) {
	$sst  = "dl_id";
	$sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
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

$g5['title'] = "『{$ds['dg_title']}』 로그 관리";
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$total_turn_count = sql_fetch("select count(*) as cnt from {$g5['dungeon_log_table']} where dl_is_turn = 1 and ds_id = '{$ds_id}'");
$total_turn_count = $total_turn_count['cnt'];

$colspan =7;



?>


<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	전체 <?php echo number_format($total_count) ?> 건 (현재 턴수 : <?=$total_turn_count?>)
</div>

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col style="width: 120px;" />
		<col />
		<col style="width: 120px;"/>
	</colgroup>
	<thead>
	<tr>
		<th scope="col">캐릭터 이름</th>
		<th scope="col">로그</th>
		<th scope="col">일시</th>
	</tr>
	</thead>
	<tbody>
	<?php
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$bg = 'bg'.($i%2);

		$re_target = explode("||", $row['re_ch_name']);
		$re_target = array_filter($re_target);
		$re_target = implode(",", $re_target);

		$log = explode("&&&&", $row['dl_log']);
	?>

	<tr class="<?php echo $bg; ?>" style="background:<?=$row['ch_id'] ? "" : "red"?>">
		<td>
			<? if($row['ch_id']) { ?>
				<?=get_character_name($row['ch_id'])?>
			<? } ?>
		</td>
		<td>
			<?=$log[0]?>
			<? if($log[1]) { ?><br /><?=$log[1]?><? } ?>
		</td>
		<td>
			<?=$row['dl_datetime']?>
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
<br />
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;ds_id={$ds_id}&amp;page="); ?>


<?php
include_once ('./admin.tail.php');
?>
