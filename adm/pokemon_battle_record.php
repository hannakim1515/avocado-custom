<?php
$sub_menu = "091220";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');


$sql_common = " from {$g5['pokemon_battle_log_table']} lo 
                LEFT JOIN {$g5['pokemon_battle_table']} atk ON lo.ph_id=atk.ph_id 
                LEFT JOIN {$g5['pokemon_battle_table']} def ON lo.re_ph_id=def.ph_id ";
$sql_search = " where lo.lo_order=4 ";

if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ({$sfl} like '%{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if (!$sst) {
	$sst  = "lo.lo_datetime";
	$sod = "asc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = "select lo.*,atk.ch_id AS atk_ch_id,atk.po_name AS atk_name,def.po_name AS def_name, def.ch_id AS def_ch_id {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);


$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '기술 관리';
include_once('./admin.head.php');

$colspan = 11;
?>

<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	총 로그 수 <?php echo number_format($total_count) ?>개
</div>

<div class="tbl_head01 tbl_wrap">
	<table>
		<caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col/>
			<col/>
			<col/>
			<col/>
			<col/>
		</colgroup>
		<thead>
			<tr>
				<th scope="col">날짜</th>
				<th scope="col">P1</th>
				<th scope="col">P2</th>
				<th scope="col">승자</th>
				<th scope="col">로그</th>
			</tr>
		</thead>
		<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
			
                if(strstr($row['lo_msg'], '무승부')){
                    $battle_result="무승부";
                }else{
					$battle_result=$row['atk_name'];
				}
			?>

			<tr class="<?php echo $bg; ?>">
				<td style="text-align:center;"><?=$row['lo_datetime']?></td>
				<td style="text-align:center;"><?=$row['atk_name']?></td>
				<td style="text-align:center;"><?=$row['def_name']?></td>
				<td style="text-align:center;"><?php echo $battle_result; ?></td>
				<td style="text-align:center;"><a href="<?=$board_link?>" target="_blank">로그</a></td>
			</tr>
			<?php
			}
			if ($i == 0)
				echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
			?>
		</tbody>
	</table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;type='.$type.'&amp;cate='.$cate.'&amp;cate2='.$cate2.'&amp;map_id='.$map_id.'&amp;page='); ?>


<?php
include_once('./admin.tail.php');
?>
