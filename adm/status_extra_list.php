<?php
$sub_menu = "910100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['status_extra_table']} ";

$sql_search = " where (1) ";

$sql_order = " order by ex_id asc ";

$sql = " select *
			{$sql_common}
			{$sql_search}
			{$sql_order} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '스탯 연동코드 관리';
include_once ('./admin.head.php');

$status_type = explode("||", $config['cf_status_select_type']);
$status_type = array_filter($status_type);
$status_type = array_values($status_type);

$colspan = 9;

$pg_anchor = '<ul class="anchor">
	<li><a href="#anc_001">체력설정</a></li>
	<li><a href="#anc_002">연동코드 목록</a></li>
	<li><a href="#anc_003">연동코드 등록</a></li>
</ul>';
?>

<section id="anc_001">
	<h2 class="h2_frm">체력설정</h2>
	<?php echo $pg_anchor ?>

	<form name="fstatusform" method="post" id="fstatuslist2" action="./status_extra_form_update.php" autocomplete="off" enctype="multipart/form-data">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">
	<input type="hidden" name="add_type" value="hp">

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 130px;">
			<col>
			<col style="width: 130px;">
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">체력스탯</th>
			<td>
				<select name="st_id">
					<option value="">지정하지 않음</option>
					<?
						$stat_sql = "select st_id, st_name, st_use_hp from {$g5['status_config_table']} order by st_order asc";
						$stat_list = sql_query($stat_sql);
						for($i=0; $srow = sql_fetch_array($stat_list); $i++) {
					?>
						<option value="<?=$srow['st_id']?>" <?=$srow['st_use_hp'] ? "selected" : ""?>><?=$srow['st_name']?></option>
					<? } ?>
				</select>
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


<section id="anc_002">
	<h2 class="h2_frm">연동코드 목록</h2>
	<?php echo $pg_anchor ?>

	<div class="local_ov01 local_ov" style="border:1px solid #efeff1; padding:10px;">
		<p>
			<strong style="background:#000; color:#fff; padding:0 10px;">프로그램 코드</strong>&nbsp;
			$변수명 = get_status_extra("코드명", 캐릭터 IDX <span style="opacity:.6;">,(생략가능) 변동발동확률 전의 스탯에 더할 수치, (생략가능) 전체배율 곱한 뒤의 스탯에 더할 수치</span>);
		</p>
		<p style="padding-top:5px;">
			<strong style="background:#000; color:#fff; padding:0 10px;">반환값</strong>&nbsp;
			$변수명['default'] : 기본 수치값 / $변수명['is_cri'] : 변동수치 발동 여부 / $변수명['cri_value'] : 변동수치 발동으로 추가되는 값 / $변수명['value'] : 최종 결과값
		</p>
	</div>

	<form name="fstatuslist" id="fstatuslist" method="post" action="./status_extra_list_update.php" onsubmit="return fstatuslist_submit(this);"  enctype="multipart/form-data">
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
			<col style="width: 50px;" />
			<col />

			<col style="width: 140px;" />
			<col style="width: 190px;"/>

			<col style="width: 100px;" />
			<col style="width: 190px;"/>

			<col style="width: 100px;" />
			<col style="width: 190px;"/>

			<col style="width: 100px;" />
		</colgroup>
		<thead>
			<tr>
				<th scope="col" rowspan="2">
					<label for="chkall" class="sound_only">스탯설정 내역 전체</label>
					<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
				</th>
				<th scope="col" rowspan="2">코드명</th>
				<th scope="col" colspan="2">기본수치</th>

				<th scope="col" colspan="2">변동 : 발동확률</th>

				<th scope="col" colspan="2">변동 : 추가수치</th>

				<th scope="col" rowspan="2">전체배율</th>
			</tr>
			<tr>
				<th>상수</th>
				<th>스탯</th>

				<th>상수</th>
				<th>스탯</th>

				<th>상수</th>
				<th>스탯</th>

			</tr>
		</thead>
		<tbody>
		<?php
		for ($i=0; $row=sql_fetch_array($result); $i++) {
			$bg = 'bg'.($i%2);
		?>

		<tr class="<?php echo $bg; ?>">
			<td style="text-align: center">
				<input type="hidden" name="ex_id[<?php echo $i ?>]" value="<?php echo $row['ex_id'] ?>" >
				<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
			</td>
			<td>
				<input type="text" name="ex_name[<?php echo $i ?>]" value="<?php echo $row['ex_name'] ?>" style="width: 100%;">
			</td>

			<td>
				<input type="text" name="ex_main_min[<?php echo $i ?>]" value="<?php echo $row['ex_main_min'] ?>" style="width: 50px;">
				~
				<input type="text" name="ex_main_max[<?php echo $i ?>]" value="<?php echo $row['ex_main_max'] ?>" style="width: 50px;">
			</td>
			<td>
				<input type="checkbox" name="ex_is_main_status[<?php echo $i ?>]" value="1" <?=$row['ex_is_main_status'] ? "checked" : ""?>/>
				<select name="ex_main_status_type[<?php echo $i ?>]" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>" <?=($row['ex_main_status_type'] == $status_type[$k] ? "selected" : "")?>><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_main_status_per[<?php echo $i ?>]" value="<?php echo $row['ex_main_status_per'] ?>" style="width:40px;"/>
			</td>
			
			<td>
				<input type="text" name="ex_cri[<?php echo $i ?>]" value="<?php echo $row['ex_cri'] ?>" style="width: 70%;">%
			</td>
			<td>
				<input type="checkbox" name="ex_is_cri_status[<?php echo $i ?>]" value="1" <?=$row['ex_is_cri_status'] ? "checked" : ""?>/>
				<select name="ex_cri_status_type[<?php echo $i ?>]" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>" <?=($row['ex_cri_status_type'] == $status_type[$k] ? "selected" : "")?>><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_cri_status_per[<?php echo $i ?>]" value="<?php echo $row['ex_cri_status_per'] ?>" style="width:40px;"/>
			</td>


			<td>
				<input type="text" name="ex_cri_add_per[<?php echo $i ?>]" value="<?php echo $row['ex_cri_add_per'] ?>" style="width: 70%;">%
			</td>
			<td>
				<input type="checkbox" name="ex_is_cri_add_status[<?php echo $i ?>]" value="1" <?=$row['ex_is_cri_add_status'] ? "checked" : ""?>/>
				<select name="ex_cri_add_status_type[<?php echo $i ?>]" style="width:90px;">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>" <?=($row['ex_cri_add_status_type'] == $status_type[$k] ? "selected" : "")?>><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_cri_add_status_per[<?php echo $i ?>]" value="<?php echo $row['ex_cri_add_status_per'] ?>" style="width:40px;"/>
			</td>


			<td>
				x <input type="text" name="ex_all_per[<?php echo $i ?>]" value="<?php echo $row['ex_all_per'] ?>" style="width: 70%;">
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

		<button type="button" onclick="location.href='./status_list.php';" style="float:right;">스탯 설정 바로가기</button>
	</div>

	</form>

</section>


<section id="anc_003">
	<h2 class="h2_frm">연동코드 등록</h2>
	<?php echo $pg_anchor ?>

	<form name="fstatuslist3" method="post" id="fstatuslist3" action="./status_extra_form_update.php" autocomplete="off" enctype="multipart/form-data">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_frm01 tbl_wrap">
		<table>
		<colgroup>
			<col style="width: 130px;">
			<col style="width: 90px;">
			<col>
		</colgroup>
		<tbody>
		<tr>
			<th scope="row">연동코드명</th>
			<td colspan="2"><input type="text" name="ex_name" id="ex_name" class="required" required></td>
		</tr>
		<tr>
			<th scope="row" rowspan="3">기본 수치</th>
			<td class="bo-right">
				상수
			</td>
			<td>최소 <input type="text" name="ex_main_min" style="width:80px;"/> ~ 최대 <input type="text" name="ex_main_max" style="width:80px;"/></td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯사용
			</td>
			<td><input type="checkbox" name="ex_is_main_status" value="1" id="ex_is_main_status" /><label for="ex_is_main_status">스탯수치를 사용합니다.</label></td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯설정
			</td>
			<td>
				<?php echo help("※ 선택된 타입의 스탯을 모두 더한 후 배율을 곱하게 됩니다.");?>
				<select name="ex_main_status_type">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>"><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_main_status_per" style="width:80px;"/>
			</td>
		</tr>
		
		<tr>
			<th scope="row" rowspan="8">변동 수치<br />(크리티컬)</th>
			<td colspan="2" style="background:#fafafa; padding-left:20px;">
				발동 확률 설정
			</td>
		</tr>
		<tr>
			<td class="bo-right">
				기본확률
			</td>
			<td>
				<?php echo help("※ 수치가 더해질 확률을 결정합니다. 스탯이 연동되지 않은 기본 수치입니다.");?>
				<input type="text" name="ex_cri" style="width:80px;"/>%
			</td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯사용
			</td>
			<td><input type="checkbox" name="ex_is_cri_status" value="1" id="ex_is_cri_status" /><label for="ex_is_cri_status">스탯수치를 사용합니다.</label></td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯설정
			</td>
			<td>
				<?php echo help("※ 선택된 타입의 스탯을 모두 더한 후 배율을 곱하여 확률수치에 더하게 됩니다.");?>
				<select name="ex_cri_status_type">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>"><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_cri_status_per" style="width:80px;"/>
			</td>
		</tr>

		<tr>
			<td colspan="2" style="background:#fafafa; padding-left:20px;">
				발동 성공 시 기본 수치에 더해질 값
			</td>
		</tr>
		<tr>
			<td class="bo-right">
				추가수치
			</td>
			<td>
				<?php echo help("※ 기본 수치에서 더해질 값을 입력합니다. 기본 수치에서 일정 비율을 가져옵니다.");?>
				<?php echo help("※ 기본수치 + (기본수치 x (추가수치/100)) 으로 계산됩니다.");?>
				<input type="text" name="ex_cri_add_per" style="width:80px;"/>%
			</td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯사용
			</td>
			<td><input type="checkbox" name="ex_is_cri_add_status" value="1" id="ex_is_cri_add_status" /><label for="ex_is_cri_add_status">스탯수치를 사용합니다.</label></td>
		</tr>
		<tr>
			<td class="bo-right">
				스탯설정
			</td>
			<td>
				<?php echo help("※ 선택된 타입의 스탯을 모두 더한 후 배율을 곱하여 나온 수를 추가수치에 더하게 됩니다.");?>
				<?php echo help("※ 기본수치 + (기본수치 x ((추가수치 + (스탯설정x배율))/100)) 로 계산됩니다.");?>
				<select name="ex_cri_add_status_type">
					<option value="">-</option>
					<? for($k=0; $k < count($status_type); $k++) { ?>
						<option value="<?=$status_type[$k]?>"><?=$status_type[$k]?></option>
					<? } ?>
				</select>
				x
				<input type="text" name="ex_cri_add_status_per" style="width:80px;"/>
			</td>
		</tr>
		<tr>
			<th scope="row">전체배율</th>
			<td colspan="2">
				<?php echo help("※ 최종적으로 나온 값에 대한 배율을 설정합니다.");?>
				x <input type="text" name="ex_all_per" style="width:80px;"/>
			</td>
		</tr>
		</tbody>
		</table>
	</div>

	<div class="btn_confirm01 btn_confirm">
		<input type="submit" value="확인" class="btn_submit">
	</div>

	</form>

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
