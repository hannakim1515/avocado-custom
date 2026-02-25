<?php
$sub_menu = "400900";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$sql_common = " from {$g5['character_table']} ch LEFT JOIN {$g5['npc_table']} ns on ch.ch_id = ns.ns_id ";
$sql_search = " where ch.ch_type = 'npc' ";
if ($stx) {
	$sql_search .= " and ( ";
	switch ($sfl) {
		default :
			$sql_search .= " ({$sfl} like '{$stx}%') ";
			break;
	}
	$sql_search .= " ) ";
}

if (!$sst) {
	$sst = "ch.ch_id asc";
	$sod = "";
}

$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = 'NPC 관리';
include_once('./admin.head.php');

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$colspan = 6;

$npc_link = G5_URL."/npc/index.php?ns_id=";
?>

<style>
.heart-progress,
.heart-progress li {margin:0; padding:0;}
.heart-progress {display:flex; flex-wrap:nowrap; justify-content:space-between; margin-top:0; height:70px;}
.heart-progress li {display:block; position:relative; flex-grow:1; z-index:0;}
.heart-progress li .bar {display:block; position:absolute; top:50%; left:1px; right:1px; margin-top:-7px; height:4px; border-radius:0; z-index:-1;}
.heart-progress li:after {content:""; display:block; position:absolute; width:2px; background:#000; top:10px; bottom:10px; left:0; margin-left:-1px; z-index:1;}
.heart-progress li:first-child:after {display:none;}
.heart-progress li.lv0:before {background:#999;}
.heart-progress li.lv0 .point {visibility:hidden;}
.heart-progress li.lv0 .point,
.heart-progress li.lv0 .name {margin-left:0;}
.heart-progress .point,
.heart-progress .name {position:absolute; width:90px; left:0; margin-left:-45px; text-align:center; white-space:nowrap; z-index:2;}
.heart-progress .point {top:0; background:#000; color:#fff; border-radius:9em;}
.heart-progress .point input {width:60px; margin-right:5px; background:transparent; height:20px; border:none; outline:none; color:#fff; text-align:center; padding:0;}
.heart-progress .name {bottom:0; padding:4px; background:#fcfcfc; border:1px solid #ddd; border-radius:9em;}
.heart-progress .name input {text-align:center; width:100%;}
</style>


<section id="anc_001">
	<h2 class="h2_frm">상점 NPC 설정</h2>
	<?php echo $pg_anchor ?>
	<form name="fstatusform" method="post" id="fstatuslist2" action="./npc_shop_update.php">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 80px;">
			<col>
			<col style="width: 130px;">
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">상점</th>
			<td>
				<input type="text" name="shop_npc" value="<?=get_character_name($config['cf_shop_npc'])?>" placeholder="이름 입력" />
			</td>
			<td>
				<div class="btn_confirm01 btn_confirm" style="padding:0;">
					<input type="submit" value="확인" class="btn_submit">
				</div>
			</td>
		</tr>
		</tbody>
		</table>
	</div>
	</form>
</section>


<div class="local_ov01 local_ov">
	<?php echo $listall ?>
	총캐릭터수 <?php echo number_format($total_count) ?>명
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>


<select name="sfl" id="sfl">
	<option value="ch_name"<?php echo get_selected($_GET['sfl'], "ch_name"); ?>>캐릭터 이름</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
</form>

<br />

<form name="fmemberlist" id="fmemberlist" action="./character_list_update.php" onsubmit="return fmemberlist_submit(this);" method="post">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption><?php echo $g5['title']; ?> 목록</caption>
	<colgroup>
		<col style="width: 60px;" />
		<col style="width: 140px;" />
		<col style="width: 100px;" />
		<col style="width: 100px;" />
		<col style="width: 100px;" />
		<col />
	</colgroup>
	<thead>
	<tr>
		<th colspan="2">이름</th>
		<th>기본관리</th>
		<th>선물</th>
		<th>로그</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$ns = array();
	for ($i=0; $row=sql_fetch_array($result); $i++) {
		$ns = $row;
		$ch_id = $row['ch_id'];
		$bg = 'bg'.($i%2);
		$no_link = false;

		if(!$row['ns_id']) {
			$no_link = true;
			$row['ns_id'] = $row['ch_id'];
		} else {
			$item_cnt = sql_fetch("select count(*) as cnt from {$g5['npc_item_table']} where ch_id = '{$row['ns_id']}'"); 
			$item_cnt = $item_cnt['cnt'];
			$npc_item = '<a href="./npc_item_list.php?'.$qstr.'&amp;w=u&amp;ns_id='.$row['ns_id'].'">등록 ('.$item_cnt.')</a>';
			$npc_log = '<a href="./npc_log.php?'.$qstr.'&amp;w=u&amp;ns_id='.$row['ns_id'].'">로그</a>';
		}
		$npc_setting = '<a href="./npc_form.php?'.$qstr.'&amp;w=u&amp;ns_id='.$row['ns_id'].'">관리</a>';
	?>

	<tr class="<?php echo $bg; ?>">
		<td style="background:url(<?=$row['ch_thumb']?>) no-repeat 50% 0%; background-size:cover;"></td>
		<td>
			<? if($no_link) { ?>
				<?php echo get_text($row['ch_name']); ?>
			<? } else {?>
				<a href="<?=$npc_link.$row['ns_id']?>" target="_blank" title="개인대화 바로가기"><?php echo get_text($row['ch_name']); ?></a>
			<? } ?>
		</td>
		<td><?=$npc_setting?></td>
		<td><?=$npc_item?></td>
		<td><?=$npc_log?></td>
		<td style="padding:5px;">
			<ul class="heart-progress">
				<?
					$is_data = false;
					for($j=0; $j <= 4; $j++) {
						if($j == 0) {
							$ns['ns_lv'.$j.'_point'] = -200000000;
						}
						if($ns['ns_lv'.$j.'_name']) {
							echo "<li class='lv{$j}'>";
							echo "	<div class='bar' style='background:{$ns['ns_lv'.$j.'_color']}'></div>";
							echo "	<div class='point'><span>".$ns['ns_lv'.$j.'_point']."p</span></div> ";
							echo "	<div class='name'>".$ns['ns_lv'.$j.'_name']."</div> ";
							echo "</li>";
							$is_data = true;
						}
					}
				?>
			</ul>
		</td>
	</tr>
  
	<?php
	}
	if ($i == 0)
		echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
	?>
	</tbody>
	</table>
</div>
<br />

</form>

<style>
.tbl_head01 .sub-table {background:#fff; border:1px solid #ddd; border-collapse: collapse;}
.tbl_head01 .sub-table th,
.tbl_head01 .sub-table td {font-size:11px !important; padding:2px !important; color:#888; font-family:'Dotum'; border:1px solid #ddd;}
.tbl_head01 .sub-table thead th {background:#f3f3f3;}
.tbl_head01 .sub-table tbody td {color:#afafaf; font-weight:300; height:3em;}
.tbl_head01 .sub-table tbody td strong {color:#888; font-weight:300;}
</style>


<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>


<?php
include_once ('./admin.tail.php');
?>
