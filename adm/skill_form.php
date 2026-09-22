<?php
$sub_menu = "720100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$html_title = '스킬 정보';
$required = "";
$readonly = "";

$skill = array();

if ($w == '') {
	$html_title .= ' 등록';
	$sound_only = '<strong class="sound_only">필수</strong>';
} else if ($w == 'u') {
	$html_title .= ' 수정';
	$skill = sql_fetch("select * from {$g5['skill_table']} where sk_id = '{$sk_id}'");
	if (!$skill['sk_id'])
		alert('존재하지 않는 스킬 정보 입니다.');
	$readonly = 'readonly';
}

$g5['title'] = $html_title;
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');


$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./skill_list.php?'.$qstr.'">목록</a>'.PHP_EOL;
$frm_submit .= '</div>';


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
$status_result = sql_query("select * from {$g5['status_config_table']}");
for($i=0; $st = sql_fetch_array($status_result); $i++) {
	$status[$i]['id'] = $st['st_id'];
	$status[$i]['name'] = $st['st_name'];
}

$skill_level = array();
if($skill['sk_id']) {
	$skill_level_result = sql_query("select * from {$g5['skill_level_table']} where sk_id = '{$sk_id}' order by sl_level asc");
	for($i=0; $sl = sql_fetch_array($skill_level_result); $i++) {
		$skill_level[]= $sl;
	}
}

// 스킬 기능 사전 설정하기
$skill_function = array("공격", "도발", "회피", "방어", "스탯강화", "연동코드강화", "스탯회복");

?>

<style>

.tbl_frm01 .tbl_head01 table {border-top-width:0;}
.tbl_frm01 .tbl_head01 table th,
.tbl_frm01 .tbl_head01 table td {border-bottom-width:0; border-left-width:0;}
.tbl_frm01 .tbl_head01 table tbody th,
.tbl_frm01 .tbl_head01 table tbody td {border-top-width:1px; border-right-width:1px;}

.inline-select {border-width:0px !important; border-bottom-width:1px !important; padding:0 5px !important; -webkit-appearance:none; -moz-appearance:none; -o-appearance:none; appearance:none; cursor:pointer; color:#d85b5b; font-family: 'Noto Sans KR', sans-serif; font-weight:700; text-align:center;}
.inline-select::ms-expand {display:none;}
.inline-select.center {text-align:center;}

.box-txt {display:inline-block; vertical-align:middle; padding:0 10px; background:#6fb591; line-height:25px; color:#fff; font-weight:400; border-radius:9em; margin:0 2px;}
.box-txt.ty2 {background:#d85b5b;}
.box-txt.ty3 {background:#96a59d;}
.box-txt.ty4 {background:#a52b2b;}
.box-txt.ty5 {background:#8e85ff;}
</style>

<form name="fstatuslist3" method="post" id="fstatuslist3" action="./skill_form_update.php" autocomplete="off" enctype="multipart/form-data">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="<?php echo $token ?>">
<input type="hidden" name="sk_id" value="<?php echo $sk_id ?>">
<input type="hidden" name="w" value="<?php echo $w ?>">

<section id="anc_001">
	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 130px;">
			<col style="width: 130px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th>스킬명</th>
			<td colspan="2"><input type="text" name="sk_name" value="<?=$skill['sk_name']?>" class="required" required></td>
		</tr>
		<tr>
			<th scope="row" rowspan="2">이미지</th>
			<td rowspan="2" class="bo-left bo-right txt-center">
				<? if($skill['sk_img']) { ?>
					<img src="<?=$skill['sk_img']?>" style="max-height:80px; max-width:80px;">
				<? } else { ?>
				이미지 미등록
				<? } ?>
			</td>
			<td>
				직접등록&nbsp;&nbsp; <input type="file" name="sk_img_file" value="" size="50">
			</td>
		</tr>
		<tr>
			<td>
				외부경로&nbsp;&nbsp; <input type="text" name="sk_img" value="<?=$skill['sk_img']?>" size="50"/>
			</td>
		</tr>
		</tbody>
	</table>
	<br />
	<table>
		<colgroup>
			<col style="width: 130px;">
			<col style="width: 130px;">
			<col>
		</colgroup>
		<tbody>	
		<tr>
			<th rowspan="4">기능</th>
			<td colspan="2">
				<select name="sk_type" onchange="fn_function_setting(this);" style="min-width:100px;">
					<option value="액티브" <?=$skill['sk_type'] == "액티브" ? "selected" : ""?>>액티브</option>
					<option value="패시브" <?=$skill['sk_type'] == "패시브" ? "selected" : ""?>>패시브</option>
				</select>

				<select name="sk_function" onchange="fn_function_setting(this);">
					<? for($i=0; $i < count($skill_function); $i++) {?>
						<option value="<?=$skill_function[$i]?>" <?= $skill['sk_function'] == $skill_function[$i] ? "selected" : ""?>><?=$skill_function[$i]?></option>
					<? } ?>
				</select>
			</td></tr><tr>
			<td class="bo-left bo-right txt-center">① 기본수식</td>
			<td>
				<div class="none" data-type="단독기능">
					<select name="sk_status_code" class="inline-select" style="min-width:100px;" onchange="fn_function_setting(this);">
						<option value="">-</option>
						<?
							for($i=0; $i < count($extra); $i++) { 
								if($extra[$i] != "") { 
									$is_checked = $skill['sk_status_code'] == $extra[$i]['id'] ? "selected" : "";
									echo "<option value='{$extra[$i]['id']}' {$is_checked}>{$extra[$i]['name']}</option>";
								}
							}
						?>
					</select>
					
					<select name="sk_value_type" class="inline-select center" style="min-width:30px;" onchange="fn_function_setting(this);">
						<option value="+" <?=$skill['sk_value_type'] == "+" ? "selected" : ""?>>+</option>
						<option value="x" <?=$skill['sk_value_type'] == "x" ? "selected" : ""?>>x</option>
					</select>
					&nbsp;<a href="#skill_level_list" class="box-txt">레벨별 변동 수치</a>
					= <strong class="box-txt ty2">결과값</strong>
				</div>
			</td></tr><tr class="none" data-type="회복기능제외">
			
			<td class="bo-left bo-right txt-center">② 결과수정</td>
			<td>
				<div class="none" data-type="단독기능">
					<strong class="box-txt ty2">결과값</strong>

					<select name="sk_def_type"  class="inline-select center" style="min-width:30px;">
						<option value="+" <?=$skill['sk_def_type'] == "+" ? "selected" : ""?>>+</option>
						<option value="-" <?=$skill['sk_def_type'] == "-" ? "selected" : ""?>>-</option>
					</select>
					대상의&nbsp;&nbsp;

					<select name="sk_def_code" class="inline-select" style=" min-width:120px;">
						<option value="">-</option>
						<?
							for($i=0; $i < count($extra); $i++) { 
								if($extra[$i] != "") { 
									$is_checked = $skill['sk_def_code'] == $extra[$i]['id'] ? "selected" : "";
									echo "<option value='{$extra[$i]['id']}' {$is_checked}>{$extra[$i]['name']}</option>";
								}
							}
						?>
					</select>
					<select name="sk_def_enermy" class="inline-select" style="display:none; min-width:120px;">
						<option value="">-</option>
						<option value="체력" <?=$skill['sk_def_enermy'] == "체력" ? "selected" : ""?>>적 체력</option>
						<option value="공격" <?=$skill['sk_def_enermy'] == "공격" ? "selected" : ""?>>적 공격</option>
						<option value="방어" <?=$skill['sk_def_enermy'] == "방어" ? "selected" : ""?>>적 방어</option>
					</select>

					

					= <strong class="box-txt ty4">수정 결과값</strong>
				</div>
			</td></tr><tr>
			<td class="bo-left bo-right txt-center">③ 결과적용</td>
			<td>
				<div class="none" data-type="단독기능">
					대상의&nbsp;&nbsp;
					<select name="sk_mod_st_id" class="inline-select" style="min-width:120px;">
						<option value="">사용안함</option>
						<?
							for($i=0; $i < count($status); $i++) { 
								if($status[$i] != "") { 
									$is_checked = $skill['sk_mod_st_id'] == $status[$i]['id'] ? "selected" : "";
									echo "<option value='{$status[$i]['id']}' {$is_checked}>{$status[$i]['name']}</option>";
								}
							}
						?>
					</select>

					<select name="sk_mod_code" class="inline-select" style="min-width:120px; display:none;">
						<option value="">사용안함</option>
						<?
							for($i=0; $i < count($extra); $i++) { 
								if($extra[$i] != "") { 
									$is_checked = $skill['sk_mod_code'] == $extra[$i]['id'] ? "selected" : "";
									echo "<option value='{$extra[$i]['id']}' {$is_checked}>{$extra[$i]['name']}</option>";
								}
							}
						?>
					</select>

					<select name="sk_mod_enermy" class="inline-select" style="min-width:120px; display:none;" onchange="fn_function_setting(this);">
						<option value="">사용안함</option>
						<option value="체력" <?=$skill['sk_mod_enermy'] == "체력" ? "selected" : ""?>>적 체력</option>
						<option value="공격" <?=$skill['sk_mod_enermy'] == "공격" ? "selected" : ""?>>적 공격</option>
						<option value="방어" <?=$skill['sk_mod_enermy'] == "방어" ? "selected" : ""?>>적 방어</option>
					</select>

					<select name="sk_mod_type"  class="inline-select center" style="min-width:30px;" onchange="fn_function_setting(this);">
						<option value="+" <?=$skill['sk_mod_type'] == "+" ? "selected" : ""?>>+</option>
						<option value="-" <?=$skill['sk_mod_type'] == "-" ? "selected" : ""?>>-</option>
						<option value="x" <?=$skill['sk_mod_type'] == "x" ? "selected" : ""?>>x</option>
					</select>
					<strong class="box-txt ty4">수정 결과값</strong>
					= 
					<strong class="box-txt ty5">최종 결과값</strong>
					 으로 적용됩니다.
				</div>
			</td>
		</tr>
		</tbody>
	</table>
	<br />
	<table>
		<colgroup>
			<col style="width: 130px;">
			<col style="width: 130px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th rowspan="2">제한</th>
			<td class="bo-left bo-right txt-center">사용대상</td>
			<td>
				<select name="sk_target" style="min-width:100px;" onchange="fn_function_setting(this);">
					<option value="자신" <?=$skill['sk_target'] == "자신" ? "selected" : ""?>>자신</option>
					<option value="아군" <?=$skill['sk_target'] == "아군" ? "selected" : ""?>>아군</option>
					<option value="아군전체" <?=$skill['sk_target'] == "아군전체" ? "selected" : ""?>>아군전체</option>
					<option value="타인" <?=$skill['sk_target'] == "타인" ? "selected" : ""?>>타인</option>
					<option value="타인전체" <?=$skill['sk_target'] == "타인전체" ? "selected" : ""?>>타인전체</option>
					<option value="적" <?=$skill['sk_target'] == "적" ? "selected" : ""?>>적</option>
				</select>
			</td></tr><tr>
			<td class="bo-left bo-right txt-center">턴 설정</td>
			<td>
				<div class="none" data-type="패시브">
					<input type="text" class="inline-select" name="sk_limit" value="<?=$skill['sk_limit']?>" size="5"> 턴 이후 사용 가능
					<span class="none" data-type="회복기능제외">
						&nbsp;&nbsp;/&nbsp;&nbsp;
						<input type="text" class="inline-select" name="sk_keep_limit" value="<?=$skill['sk_keep_limit']?>" size="5"> 턴 동안 유지
					</span>
				</div>
			</td>
		</tr>
		<tr class="none" data-type="패시브">
			<th>소모</th>
			<td colspan="2">
				<?php echo help("※ 패시브 타입의 경우, 소모스탯이 적용되지 않습니다.") ?>
				스탯 : 
				<select name="sk_use_st_id" class="inline-select" style="min-width:100px;">
					<option value="">사용안함</option>
					<?
						for($i=0; $i < count($status); $i++) { 
							if($status[$i] != "") { 
								$is_checked = $skill['sk_use_st_id'] == $status[$i]['id'] ? "selected" : "";
								echo "<option value='{$status[$i]['id']}' {$is_checked}>{$status[$i]['name']}</option>";
							}
						}
					?>
				</select>
				&nbsp;을 <a href="#skill_level_list" class="box-txt ty3">레벨별 변동 소모수치</a>만큼 소모합니다.
			</td>
		</tr>
		<tr>
			<th>설명</th>
			<td colspan="2">
				<input type="text" name="sk_descript" value="<?=$skill['sk_descript']?>" style="width:100%;" />
			</td>
		</tr>

		<tr>
			<th>레벨설정</th>
			<td colspan="2" style="padding:0;">
				<div class="tbl_head01 tbl_wrap" style="max-width:600px;">
					<table>
					<caption><?php echo $g5['title']; ?> 목록</caption>
					<colgroup>
						<col style="width: 100px;" />
						<col />
						<col style="width: 120px;" />
						<col style="width: 120px;" />
						<col style="width: 50px;" />
					</colgroup>
					<thead>
						<tr>
							<th scope="col">순서</th>
							<th scope="col">등급명</th>
							<th scope="col">변동수치</th>
							<th scope="col">소모수치</th>
							<th scope="col">
								<button type="button" onclick="fn_add_skill_level();" style="border:none; background:#29c7c9 ; color:#fff;">+</button>

							</th>
						</tr>
					</thead>
					<tbody id="skill_level_list">
						<? for($i=0; $i < count($skill_level); $i++) { ?>
							<tr>
								<td>
									<input type="text" name="sl_level[]" value="<?=$skill_level[$i]['sl_level']?>" style="width:100%; text-align:center;">
								</td>
								<td>
									<input type="text" name="sl_name[]" value="<?=$skill_level[$i]['sl_name']?>" style="width:100%;">
								</td>
								<td>
									<input type="text" name="sl_set_value[]" value="<?=$skill_level[$i]['sl_set_value']?>" style="width:100%; text-align:center;">
								</td>
								<td>
									<input type="text" name="sl_use_value[]" value="<?=$skill_level[$i]['sl_use_value']?>" style="width:100%; text-align:center;">
								</td>
								<td>
									<button type="button" onclick="fn_level_del(this);" style="border:none; background:#d85b5b; color:#fff;">-</button>
								</td>
							</tr>
						<? } ?>
					</tbody>
					</table>

				</div>
			</td>
		</tr>
		</tbody>
		</table>
	</div>

	
	<? echo $frm_submit?>
</section>

</form>

<script>
function fn_function_setting(obj) {

	let sk_type = $('[name="sk_type"]').val();
	let sk_function = $('[name="sk_function"]').val();
	let sk_target = $('[name="sk_target"]').val();
	let sk_mod_type = $('[name="sk_mod_type"]').val();

	$('[name="sk_def_enermy"]').show();
	$('[name="sk_def_code"]').show();

	// ---- Type 에 따른 전체적인 설정
	if(sk_type == '패시브') {
		fn_hide_type('패시브');
	} else {
		fn_show_type('패시브');
	}
	if(sk_type == '패시브' && sk_function != "스탯강화") {
		$('[name="sk_function"]').val("스탯강화");
		sk_function = "스탯강화";
	}
	if(sk_type == '패시브' && sk_target != "자신") {
		$('[name="sk_target"]').val("자신");
		sk_target = "자신";
	}

	// ---- Function 에 따른 설정
	if(sk_function == "공격") {
		$('[name="sk_target"]').val("적");
		$('[name="sk_mod_enermy"]').val("체력");
		$('[name="sk_mod_type"]').val("-");
		sk_mod_type = "-";
		sk_target = "적";
	}

	if(sk_function == "도발" || sk_function == "회피") {
		fn_hide_type('단독기능');

		if((sk_function == "회피" || sk_function == "방어") && sk_target == "적") {
			$('[name="sk_target"]').val("자신");
			sk_target = "자신";
		} else if (sk_function == "도발") {
			$('[name="sk_target"]').val("자신");
			sk_target = "자신";
		}
	} else {
		fn_show_type('단독기능');
	}

	if(sk_function == "방어" && sk_target == "적") {
		$('[name="sk_target"]').val("자신");
		sk_target = "자신";
	}

	if(sk_function == "스탯회복") {
		fn_hide_type('회복기능제외');

		if(sk_function == "스탯회복" && sk_target == "적") {
			$('[name="sk_target"]').val("자신");
			sk_target = "자신";
		}
		$('[name="sk_mod_type"]').val("+");
		sk_mod_type = "+";
	} else {
		fn_show_type('회복기능제외');
	}

	if(sk_type == "패시브" && sk_function == "스탯강화") {
		$('[name="sk_status_code"]').val("");
		$('[name="sk_value_type"]').val("+");
		$('[name="sk_def_enermy"]').val("");
		$('[name="sk_def_enermy"]').hide();
		$('[name="sk_def_code"]').val("");
		$('[name="sk_def_code"]').hide();
	}

	if(sk_function == "연동코드강화" && sk_target == "적") {
		$('[name="sk_target"]').val("자신");
		sk_target = "자신";
	}
	if(sk_function == "연동코드강화") {
		$('[name="sk_mod_st_id"]').val("");
		$('[name="sk_mod_st_id"]').hide();
		$('[name="sk_mod_enermy"]').val("");
		$('[name="sk_mod_enermy"]').hide();
		$('[name="sk_mod_code"]').show();
	} else if(sk_target != '적') {
		$('[name="sk_mod_code"]').val("");
		$('[name="sk_mod_code"]').hide();
		$('[name="sk_mod_enermy"]').val("");
		$('[name="sk_mod_enermy"]').hide();
		$('[name="sk_def_enermy"]').val("");
		$('[name="sk_def_enermy"]').hide();
		$('[name="sk_mod_st_id"]').show();
	} else if(sk_target == '적') {
		$('[name="sk_mod_st_id"]').val("");
		$('[name="sk_mod_st_id"]').hide();
		$('[name="sk_mod_code"]').val("");
		$('[name="sk_mod_code"]').hide();
		$('[name="sk_mod_enermy"]').show();
		$('[name="sk_def_code"]').val("");
		$('[name="sk_def_code"]').hide();
		$('[name="sk_def_enermy"]').show();
	}

	if((sk_type != "패시브" || sk_function != "스탯강화") && sk_mod_type == "x") {
		$('[name="sk_mod_type"]').val("+");
		sk_mod_type = "+";
	}

}
fn_function_setting();

function fn_hide_type(type) {
	$('.none[data-type *= "'+type+'"]').hide();
	$('.none[data-type *= "'+type+'"]').val("");
}
function fn_show_type(type) {
	$('.none[data-type *= "'+type+'"]').removeAttr('style');
}

function fn_add_skill_level() {
	var item =	'	<tr>' +
				'		<td>' +
				'			<input type="text" name="sl_level[]" value="" style="width:100%; text-align:center;">' +
				'	</td>' +
				'	<td>' +
				'		<input type="text" name="sl_name[]" value="" style="width:100%; text-align:center;">' +
				'	</td>' +
				'	<td>' +
				'		<input type="text" name="sl_set_value[]" value="" style="width:100%; text-align:center;">' +
				'	</td>' +
				'	<td>' +
				'		<input type="text" name="sl_use_value[]" value="" style="width:100%; text-align:center;">' +
				'	</td>' +
				'	<td>' +
				'		<button type="button" onclick="fn_level_del(this);" style="border:none; background:#d85b5b; color:#fff;">-</button>' +
				'	</td>' +
				'</tr>';

	$('#skill_level_list').append(item);
}
function fn_level_del(obj) {
	$(obj).closest('tr').remove();
}


</script>


<?php
include_once ('./admin.tail.php');
?>
