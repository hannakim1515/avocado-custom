<?php
$sub_menu = "730100";
include_once('./_common.php');
include_once(G5_EDITOR_LIB);

auth_check($auth[$sub_menu], 'w');

$html_title = '던전';
$required = "";
$readonly = "";
if ($w == '') {

	$html_title .= ' 생성';
	$required = 'required';
	$sound_only = '<strong class="sound_only">필수</strong>';
	$dungeon['dg_use'] = '1';


} else if ($w == 'u') {

	$html_title .= ' 수정';
	$dungeon = sql_fetch("select * from {$g5['dungeon_table']} where dg_id = '{$dg_id}'");
	if (!$dungeon['dg_id'])
		alert('존재하지 않는 아이템 입니다.');
	$readonly = 'readonly';
}

$g5['title'] = $html_title;
include_once ('./admin.head.php');

$pg_anchor = '<ul class="anchor">
				<li><a href="#anc_001">던전기본설정</a></li>
				<li><a href="#anc_002">몬스터설정</a></li>
				<li><a href="#anc_003">보상설정</a></li>
				</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
	<input type="submit" value="확인" class="btn_submit" accesskey="s">
	<a href="./dungeon_list.php?'.$qstr.'">목록</a>'.PHP_EOL;
$frm_submit .= '</div>';

/** 지역 정보 **/
$ma = array();
$ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
$index = 0;
for($i=0; $map = sql_fetch_array($ma_result); $i++) { 
	$sub_ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} and ma_use_dungeon = 1 order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) { 
		$ma[$index]['name'] = $sub_map['ma_name'];
		$ma[$index]['id'] = $sub_map['ma_id'];
		$index++;
	}
}

// 스탯 정보 가져오기
$status = array();
$status_result = sql_query("select * from {$g5['status_config_table']}");
for($i=0; $st = sql_fetch_array($status_result); $i++) {
	$status[$i]['id'] = $st['st_id'];
	$status[$i]['name'] = $st['st_name'];
}

/** 레벨 정보 **/
$lv_sql = "select * from {$g5['level_table']} order by lv_exp asc";
$lv_result = sql_query($lv_sql);
for($i=0; $level = sql_fetch_array($lv_result); $i++) { 
	$lv[$i]['name'] = $level['lv_name'];
	$lv[$i]['id'] = $level['lv_id'];
}

/** 아이템 정보 **/
$item_list = array();
$item_result = sql_query("select * from {$g5['item_table']} where it_maker = '' and it_use = 'Y' order by it_category asc, it_type asc, it_name asc");
for($i = 0; $row = sql_fetch_array($item_result); $i++) {
	$item_list[] = $row;
}


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

?>

<datalist id ="it_name_list">
	<? for($i = 0; $i < count($item_list); $i++) { echo "<option value='{$item_list[$i]['it_name']}' />"; }?>
</datalist>

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

<form name="fdungeonform" id="fdungeonform" action="./dungeon_form_update.php" onsubmit="return fdungeonform_submit(this)" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w ?>">
<input type="hidden" name="dg_id" value="<?php echo $dg_id ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">

<section id="anc_001">
	<h2 class="h2_frm">던전기본설정</h2>
	<?php echo $pg_anchor ?>
	<div class="tbl_frm01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 130px;">
				<col style="width: 100px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row">사용여부</th>
					<td colspan="2">
						<input type="checkbox" name="dg_use" value="1" <?=$dungeon['dg_use'] == '1'? "checked" : ""?>/>
					</td>
				</tr>
				<tr>
					<th scope="row">랭크</th>
					<td colspan="2">
						<input type="text" name="dg_rank" value="<?php echo get_text($dungeon['dg_rank']) ?>" required class="required" size="5" maxlength="120">&nbsp;RANK&nbsp;&nbsp;
					</td>
				</tr>
				<tr>
					<th scope="row">던전명</th>
					<td colspan="2">
						<input type="text" name="dg_title" value="<?php echo get_text($dungeon['dg_title']) ?>" required class="required" size="50" maxlength="120">
					</td>
				</tr>
				<tr>
					<th scope="row">입장최대인원</th>
					<td colspan="2">
						<input type="text" name="dg_count" value="<?=$dungeon['dg_count']?>" size="5"/> 명
					</td>
				</tr>
				<tr>
					<th scope="row" rowspan="3">지역설정</th>
					<td class="bo-right">
						발생지역
					</td>
					<td>
						<? for($k=0; $k < count($ma); $k++) { ?>
							<span style="display: inline-block; vertical-align: middle; min-width: 150px;">
								<input type="checkbox" name="ma_ids[]" id="ma_ids_<?=$k?>" value="<?=$ma[$k]['id']?>" <?=strstr($dungeon['ma_ids'], "||".$ma[$k]['id']."||") ? "checked" : "" ?> />
								<label for="ma_ids_<?=$k?>"><?=$ma[$k]['name']?></label>
							</span>
						<? } ?>
					</td></tr><tr>
					<td class="bo-right">
						발생구간
					</td>
					<td>
						<input type="text" name="dg_per_s" value="<?=$dungeon['dg_per_s']?>" size="5"/> ~ <input type="text" name="dg_per_e" value="<?=$dungeon['dg_per_e']?>" size="5"/>
					</td></tr><tr>
					<td class="bo-right">
						지역버프
					</td>
					<td>
						<select name="dg_status">
							<option value="">-</option>
							<?
								for($i=0; $i < count($status); $i++) { 
									if($status[$i] != "") { 
										$is_checked = $dungeon['dg_status'] == $status[$i]['id'] ? "selected" : "";
										echo "<option value='{$status[$i]['id']}' {$is_checked}>{$status[$i]['name']}</option>";
									}
								}
							?>
						</select>
						<select name="dg_status_type" class="inline-select center" style="min-width:30px;">
							<option value="+" <?=$dungeon['dg_status_type'] == "+" ? "selected" : ""?>>+</option>
							<option value="x" <?=$dungeon['dg_status_type'] == "x" ? "selected" : ""?>>x</option>
						</select>
						<input type="text" name="dg_status_value" value="<?=$dungeon['dg_status_value']?>" size="5" style="text-align:center;"/>
					</td>
				</tr>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_002">
	<h2 class="h2_frm">몬스터 설정</h2>
	<?php echo $pg_anchor ?>
	<div class="tbl_frm01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 130px;">
				<col style="width: 100px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row">몬스터 이름</th>
					<td colspan="2">
						<input type="text" name="dg_mon_name" value="<?=$dungeon['dg_mon_name']?>" />
					</td>
				</tr>
				<tr>
					<th scope="row" rowspan="2">이미지</th>
					<td rowspan="2" class="bo-right" style="text-align:center; vertical-align:middle;">
						<? if($dungeon['dg_mon_img']) { ?>
							<img src="<?=$dungeon['dg_mon_img']?>" style="max-height:50px; max-width:80px;">
						<? } else { ?>
						이미지 없음
						<? } ?>
					</td>
					<td>
						직접등록&nbsp;&nbsp; <input type="file" name="dg_mon_img_file" value="" size="50">
					</td>
				</tr>
				<tr>
					<td>
						외부경로&nbsp;&nbsp; <input type="text" name="dg_mon_img" value="<?=$dungeon['dg_mon_img']?>" size="50"/>
					</td>
				</tr>
				<tr>
					<th scope="row">설명</th>
					<td colspan="2">
						<textarea name="dg_mon_descript" style="height:100px;"><?=$dungeon['dg_mon_descript']?></textarea>
					</td>
				</tr>

				<tr>
					<th scope="row">스탯</th>
					<td colspan="2">
						생명력
						<input type="text" name="dg_mon_hp" value="<?=$dungeon['dg_mon_hp']?>" size="8"/>
						&nbsp;&nbsp;
						방어력
						<input type="text" name="dg_defence" value="<?=$dungeon['dg_defence']?>" size="8"/>
					</td>
				</tr>
				<tr>
					<th scope="row">특수속성</th>
					<td colspan="2">
						<?php echo help("※ 취약과 강점의 경우, 대미지를 입혔을 시 나오는 문구가 달라집니다. 똑같이 xN을 하는 수식이지만 코멘트가 달라지기 때문에 구분하여 입력해 주길 바랍니다.") ?>
						<span class="box-txt ty5">강점</span>
						멤버의
						<select name="dg_strong_code">
							<option value="">-</option>
							<?
								for($i=0; $i < count($extra); $i++) { 
									if($extra[$i] != "") { 
										$is_checked = $dungeon['dg_strong_code'] == $extra[$i]['id'] ? "selected" : "";
										echo "<option value='{$extra[$i]['id']}' {$is_checked}>{$extra[$i]['name']}</option>";
									}
								}
							?>
						</select>
						x <input type="text" name="dg_strong_value" value="<?=$dungeon['dg_strong_value']?>" size="8"/> 대미지 <span style="color:red; font-size:11px;">(해당 연동코드를 대미지로 하는 공격에 대해 1/N의 대미지를 입습니다.)</span>

						<div style="border-bottom:1px solid #ddd; margin:10px 0;"></div>

						<span class="box-txt ty2">취약</span>
						멤버의
						<select name="dg_weak_code">
							<option value="">-</option>
							<?
								for($i=0; $i < count($extra); $i++) { 
									if($extra[$i] != "") { 
										$is_checked = $dungeon['dg_weak_code'] == $extra[$i]['id'] ? "selected" : "";
										echo "<option value='{$extra[$i]['id']}' {$is_checked}>{$extra[$i]['name']}</option>";
									}
								}
							?>
						</select>
						x <input type="text" name="dg_weak_value" value="<?=$dungeon['dg_weak_value']?>" size="8"/> 대미지 <span style="color:red; font-size:11px;">(해당 연동코드를 대미지로 하는 공격에 대해 N배의 대미지를 입습니다.)</span>

						<div style="margin:10px 0;"></div>
						<span class="box-txt ty2">취약공격</span> <input type="text" name="dg_weak_effect_count" value="<?=$dungeon['dg_weak_effect_count']?>" size="5"/> 회 공격 시
						<span class="box-txt ty1">몬스터 공격</span> <input type="text" name="dg_weak_effect_turn" value="<?=$dungeon['dg_weak_effect_turn']?>" size="5"/> 회 공격하지 않음
						<br /><br />
						<?php echo help("ex) 취약공격 10회 공격 마다 몬스터 공격 2회 그로기 상태일 경우 : 멤버들이 취약공격을 10회 동안 쌓았을 시, 몬스터는 자신의 공격 턴이 2번째가 지나갈 때 까지 공격을 하지 않는다. (평타/광역/특수공격 전부 포함)") ?>
					</td>
				</tr>
				<tr>
					<th scope="row" rowspan="2">평타</th>
					<td class="bo-right">
						공격패턴
					</td>
					<td>
						<input type="text" name="dg_d_attack_turn" value="<?=$dungeon['dg_d_attack_turn']?>" size="5"/>
						턴 마다
						&nbsp;&nbsp;
						공격력
						<input type="text" name="dg_d_attack_min" value="<?=$dungeon['dg_d_attack_min']?>" size="10"/> ~ <input type="text" name="dg_d_attack_max" value="<?=$dungeon['dg_d_attack_max']?>" size="10"/>
						&nbsp;&nbsp;
						<input type="text" name="dg_d_attack_count" value="<?=$dungeon['dg_d_attack_count']?>" size="5"/>명
					</td></tr><tr>
					<td class="bo-right">
						코멘트
					</td>
					<td>
						<input type="text" name="dg_d_attack_comment" value="<?=$dungeon['dg_d_attack_comment']?>" style="width:100%;" />
					</td>
				</tr>

				<tr>
					<th scope="row" rowspan="2">광역기</th>
					<td class="bo-right">
						공격패턴
					</td>
					<td>
						<input type="text" name="dg_w_attack_turn" value="<?=$dungeon['dg_w_attack_turn']?>" size="5"/>
						턴 마다
						&nbsp;&nbsp;
						공격력
						<input type="text" name="dg_w_attack_min" value="<?=$dungeon['dg_w_attack_min']?>" size="10"/> ~ <input type="text" name="dg_w_attack_max" value="<?=$dungeon['dg_w_attack_max']?>" size="10"/>
					</td></tr><tr>
					<td class="bo-right">
						코멘트
					</td>
					<td>
						<input type="text" name="dg_w_attack_comment" value="<?=$dungeon['dg_w_attack_comment']?>" style="width:100%;" />
					</td>
				</tr>

				<tr>
					<th scope="row" rowspan="2">특수공격1</th>
					<td class="bo-right">
						공격패턴
					</td>
					<td>
						<input type="text" name="dg_s1_attack_turn" value="<?=$dungeon['dg_s1_attack_turn']?>" size="5"/>
						턴 마다
						<span class="box-txt">목표 : 멤버</span>
						<select name="dg_s1_attack_st_id" style="min-width:80px;">
							<option value="">-</option>
							<?
								for($i=0; $i < count($status); $i++) { 
									if($status[$i] != "") { 
										$is_checked = $dungeon['dg_s1_attack_st_id'] == $status[$i]['id'] ? "selected" : "";
										echo "<option value='{$status[$i]['id']}' {$is_checked}>{$status[$i]['name']}</option>";
									}
								}
							?>
						</select>
						<input type="text" name="dg_s1_attack_st_value" value="<?=$dungeon['dg_s1_attack_st_value']?>" size="5"/>
						<select name="dg_s1_attack_type" class="inline-select center" style="min-width:40px;">
							<option value="이상" <?=$dungeon['dg_s1_attack_type'] == "이상" ? "selected" : ""?>>이상</option>
							<option value="이하" <?=$dungeon['dg_s1_attack_type'] == "이하" ? "selected" : ""?>>이하</option>
						</select>
						에게

						공격력
						<input type="text" name="dg_s1_attack_min" value="<?=$dungeon['dg_s1_attack_min']?>" size="10"/> ~ <input type="text" name="dg_s1_attack_max" value="<?=$dungeon['dg_s1_attack_max']?>" size="10"/>
					</td></tr><tr>
					<td class="bo-right">
						코멘트
					</td>
					<td>
						<input type="text" name="dg_s1_attack_comment" value="<?=$dungeon['dg_s1_attack_comment']?>" style="width:100%;" />
					</td>
				</tr>

				<tr>
					<th scope="row" rowspan="2">특수공격2</th>
					<td class="bo-right">
						공격패턴
					</td>
					<td>
						<input type="text" name="dg_s2_attack_turn" value="<?=$dungeon['dg_s2_attack_turn']?>" size="5"/>
						턴 마다
						<span class="box-txt">목표 : 멤버</span>
						<select name="dg_s2_attack_st_id" style="min-width:80px;">
							<option value="">-</option>
							<?
								for($i=0; $i < count($status); $i++) { 
									if($status[$i] != "") { 
										$is_checked = $dungeon['dg_s2_attack_st_id'] == $status[$i]['id'] ? "selected" : "";
										echo "<option value='{$status[$i]['id']}' {$is_checked}>{$status[$i]['name']}</option>";
									}
								}
							?>
						</select>
						<input type="text" name="dg_s2_attack_st_value" value="<?=$dungeon['dg_s2_attack_st_value']?>" size="5"/>
						<select name="dg_s2_attack_type" class="inline-select center" style="min-width:40px;">
							<option value="이상" <?=$dungeon['dg_s2_attack_type'] == "이상" ? "selected" : ""?>>이상</option>
							<option value="이하" <?=$dungeon['dg_s2_attack_type'] == "이하" ? "selected" : ""?>>이하</option>
						</select>
						에게

						공격력
						<input type="text" name="dg_s2_attack_min" value="<?=$dungeon['dg_s2_attack_min']?>" size="10"/> ~ <input type="text" name="dg_s2_attack_max" value="<?=$dungeon['dg_s2_attack_max']?>" size="10"/>
					</td></tr><tr>
					<td class="bo-right">
						코멘트
					</td>
					<td>
						<input type="text" name="dg_s2_attack_comment" value="<?=$dungeon['dg_s2_attack_comment']?>" style="width:100%;" />
					</td>
				</tr>

			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_003">
	<h2 class="h2_frm">보상 설정</h2>
	<?php echo $pg_anchor ?>
	<div class="tbl_frm01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 130px;">
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th scope="row">기본보상</th>
					<td>
						<input type="text" name="dg_point" value="<?php echo get_text($dungeon['dg_point']) ?>" size="7" maxlength="120"> <?=$config['cf_money_pice']?>
					</td>
				</tr>
				<tr>
					<th scope="row">아이템 보상</th>
					<td>
						<div class="btn_confirm" style="padding:0; text-align:right; width:600px;">
							<input type="button" onclick="fn_add_itemFrom();" value="추가" class="btn_submit btn-item-add" style="padding:7px; font-size:12px;"/>
						</div>
						<table style="width:600px;" id="itemAddTable">
							<colgroup>
								<col />
								<col style="width:90px;" />
								<col style="width:170px;" />
								<col style="width:110px;" />
							</colgroup>
							<thead>
								<th>아이템이름</th>
								<th>획득갯수</th>
								<th>획득구간</th>
								<th>컨트롤</th>
							</thead>
							<tbody>
								<tr class="original">
									<td>
										<input type="text" name="it_name[]" value="" style="width:100%;" list="it_name_list"/>
									</td>
									<td>
										<input type="text" name="di_count[]" value="" style="width:80px;" />
									</td>
									<td style="text-align:center;">
										<input type="text" name="di_per_s[]" value="" style="width:50px;" /> ~ <input type="text" name="di_per_e[]" value="" style="width:50px;" />
									</td>
									<td class="btn_confirm" style="padding:0;">
										<input type="button" onclick="fn_del_itemFrom(this);" value="삭제" class="btn-item-dell" style="padding:7px; font-size:12px;"/>
									</td>
								</tr>
								<?
									$item_award_result = sql_query("select * from {$g5['dungeon_item_table']} where dg_id = '{$dg_id}' order by it_id asc");
									for($i=0; $di = sql_fetch_array($item_award_result); $i++) { 
								?>
									<tr>
										<td>
											<input type="text" name="it_name[]" value="<?=get_item_name($di['it_id'])?>" style="width:100%;" />
										</td>
										<td>
											<input type="text" name="di_count[]" value="<?=$di['di_count']?>" style="width:80px;" />
										</td>
										<td style="text-align:center;">
											<input type="text" name="di_per_s[]" value="<?=$di['di_per_s']?>" style="width:50px;" /> ~ <input type="text" name="di_per_e[]" value="<?=$di['di_per_e']?>" style="width:50px;" />
										</td>
										<td class="btn_confirm" style="padding:0;">
											<input type="button" onclick="fn_del_itemFrom(this);" value="삭제" class="btn-item-dell" style="padding:7px; font-size:12px;"/>
										</td>
									</tr>
								<? } ?>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</section>

<?php echo $frm_submit; ?>

</form>

<style>
#itemAddTable .original {display:none !important;}
</style>
<script>

function fn_add_itemFrom() {
	var item_form = $('#itemAddTable .original').clone();
	item_form.removeClass('original');
	$('#itemAddTable tbody').append(item_form);
}
function fn_del_itemFrom(obj) {
	$(obj).closest('tr').remove();
}
function fdungeonform_submit(f)
{
	return true;
}

</script>

<?php
include_once ('./admin.tail.php');
?>
