<?php
$sub_menu = "720100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['skill_table']} ";
$sql_search = " where (1) ";
$sql_order = " order by sk_id asc ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$skill = sql_fetch($sql);
$total_count = $skill['cnt'];

$rows = 20;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '스킬 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

// 연동코드 목록 가져오기
$extra = array();
if(isset($g5['status_extra_table'])) {
	// 연동코드 기능을 사용할 시
	$extra_result = sql_query("select * from {$g5['status_extra_table']}");
	for($i=0; $ex = sql_fetch_array($extra_result); $i++) {
		$extra[$i]['id'] = $ex['ex_name'];
		$extra[$i]['name'] = $ex['ex_name'];
	}
} else {
	// 연동코드 기능을 사용하지 않을 시
	$extra_result = sql_query("select * from {$g5['status_config_table']}");
	for($i=0; $ex = sql_fetch_array($extra_result); $i++) {
		$extra[$i]['id'] = $ex['st_id'];
		$extra[$i]['name'] = $ex['st_name'];
	}
}


// 스탯 정보 가져오기
$status = array();
$status_id_list = array();
$status_result = sql_query("select * from {$g5['status_config_table']}");
for($i=0; $st = sql_fetch_array($status_result); $i++) {
	$status[$i]['id'] = $st['st_id'];
	$status[$i]['name'] = $st['st_name'];
	$status_id_list[$st['st_id']] = $st;
}

$colspan = 11;

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">스킬목록</a></li>
	<li><a href="./990_unified_skill_map.php">통합 전투 설정</a></li>
</ul>';
?>

<section id="anc_001">
	<h2 class="h2_frm">공통 스킬 목록</h2>
	<?php echo $pg_anchor ?>
	<p class="local_desc01 local_desc">스킬 장착 슬롯 수는 <a href="./990_unified_skill_map.php">통합 전투 설정</a>에서만 변경합니다.</p>

	<?php if ($is_admin == 'super') { ?>
	<div class="btn_add01 btn_add">
		<a href="./skill_form.php" id="bo_add">스킬 추가</a>
	</div>
	<?php } ?>

	<form name="fstatuslist" id="fstatuslist" method="post" action="./skill_list_update.php" onsubmit="return fstatuslist_submit(this);"  enctype="multipart/form-data">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
		<caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col style="width: 45px;" />
			<col style="width: 80px;" />
			<col style="width: 100px;" />
			<col style="width: 50px;" />
			<col style="width: 150px;" />
			<col />
			<col />
			<col style="width: 80px;" />
			<col style="width: 80px;" />
			<col style="width: 80px;" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col">
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col" colspan="2">타입</th>
				<th scope="col" colspan="2">스킬명</th>
				<th scope="col">스킬설명</th>
				<th scope="col">레벨별</th>
				<th scope="col">소모</th>
				<th scope="col">필요턴</th>
				<th scope="col">유지턴</th>
				<th scope="col">관리</th>
			</tr>
		</thead>
		<tbody>
		<?php
		for ($i=0; $skill=sql_fetch_array($result); $i++) {
			$bg = 'bg'.($i%2);
		?>
			<tr class="<?php echo $bg; ?>">
				<td style="text-align: center">
					<input type="hidden" name="sk_id[<?php echo $i ?>]" value="<?php echo $skill['sk_id'] ?>" >
					<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
				</td>
				<td>
					<?=$skill['sk_type']?>
				</td>
				<td>
					<?=$skill['sk_function']?>
				</td>
				<td>
					<? if($skill['sk_img']) { ?>
						<img src="<?=$skill['sk_img']?>" style="max-width: 40px; max-height:30px;"/>
					<? } else { ?>
						-
					<? } ?>
				</td>
				<td>
					<input type="text" name="sk_name[<?php echo $i ?>]" value="<?php echo $skill['sk_name'] ?>" style="width: 100%;">
				</td>
				<td style="text-align:left;">
					<P><?php echo $skill['sk_descript'] ?></P>
					<p style="color:#e66149;">
						<?
							if($skill['sk_mod_st_id'] || $skill['sk_mod_code'] || $skill['sk_mod_enermy']) {
								echo "【{$skill['sk_target']}】의 【{$status_id_list[$skill['sk_mod_st_id']]['st_name']}{$skill['sk_mod_code']}{$skill['sk_mod_enermy']}】{$skill['sk_mod_type']}";
								echo "(【{$skill['sk_status_code']}】{$skill['sk_value_type']}【레벨별 변동수치】";
								if($skill['sk_def_code'] || $skill['sk_def_enermy']) {
									echo "{$skill['sk_def_type']}【{$skill['sk_target']}】의 【{$skill['sk_def_code']}{$skill['sk_def_enermy']}】";
								}
								echo ")";

							}
						?>

					</p>
				</td>
				<td style="text-align:left;">
					<?
						$skill_level_result = sql_query("select * from {$g5['skill_level_table']} where sk_id = '{$skill['sk_id']}' order by sl_level asc");
						for($j=0; $sl = sql_fetch_array($skill_level_result); $j++) {
							echo "<p>【{$sl['sl_level']}】";
							if($sl['sl_name'] && $sl['sl_name'] != "-") echo $sl['sl_name'];
							echo " : ";
							if($sl['sl_set_value']) echo "<span style='color:#0f7c32;'>변동 {$sl['sl_set_value']}</span>	";
							if($sl['sl_use_value']) echo "<span style='color:#03a9f4;'>소모 {$sl['sl_use_value']}</span>";
							echo "</p>";
						}
					?>
				</td>
				<td>
					<?=$status_id_list[$skill['sk_use_st_id']]['st_name']?>
				</td>
				<td>
					<input type="text" name="sk_limit[<?php echo $i ?>]" value="<?php echo $skill['sk_limit'] ?>" style="width: 100%; text-align:center;">
				</td>
				<td>
					<input type="text" name="sk_keep_limit[<?php echo $i ?>]" value="<?php echo $skill['sk_keep_limit'] ?>" style="width: 100%; text-align:center;">
				</td>
				<td>
					<a href="./skill_form.php?w=u&sk_id=<?=$skill['sk_id']?>">수정</a>
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

	<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['PHP_SELF'].'?'.$qstr.'&amp;'); ?>
</section>


<script>
function fstatuslist_submit(f)
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
