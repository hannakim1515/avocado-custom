<?php
$sub_menu = '980200';
include_once('./_common.php');

if(!sql_query(" DESC {$g5['k_skill_table']} ")) {

	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['k_skill_table']}` (
		`sk_id` int(11) NOT NULL AUTO_INCREMENT,
		`si_id` text NOT NULL,
        `sk_name` varchar(255) NOT NULL,
		`sk_content` text NOT NULL,
		`sk_info` text NOT NULL,
		`sk_value` float(10,2) NOT NULL,
		`default_calc` varchar(255) NOT NULL,
		`bonus_calc` varchar(255) NOT NULL,
		`sk_turn` int(11) NOT NULL,
		`sk_cool` int(11) NOT NULL,
		`sk_target` varchar(255) NOT NULL,
		`sk_target_cnt` varchar(255) NOT NULL,
		`sk_icon` varchar(255) NOT NULL,
		`sk_mp` int(11) NOT NULL,
		`sc_id` int(11) NOT NULL,
		`target_sc` int(11) NOT NULL,
		`sk_use` varchar(255) NOT NULL,
		PRIMARY KEY (`sk_id`),
		KEY (`si_id`)
	) ", false);
}


if(!sql_query(" DESC {$g5['k_skill_info_table']} ")) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['k_skill_info_table']}` (
		`si_id` int(11) NOT NULL AUTO_INCREMENT,
		`si_type` varchar(255) NOT NULL,
		`si_code` varchar(255) NOT NULL,
		`si_passive` int(11) NOT NULL default 0,
        `si_category` int(11) NOT NULL default 0,
		`si_default` int(11) NOT NULL default 0,
		`si_use` int(11) NOT NULL default 1,
		`si_1` varchar(255) NOT NULL,
		`si_info` TEXT NOT NULL,
		PRIMARY KEY (`si_id`)
	) ", false);

    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='공격', si_code='atk', si_category=980, si_1='default', si_info='적을 공격할 때 추가 대미지를 입힙니다.<br>지속턴 설정시 출혈 대미지가 들어갑니다.<br>적 단일/전체를 대상으로 선택하여야 합니다.'");
    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='치유', si_code='heal', si_category=980, si_1='default', si_info='hp가 0 이상인 아군 또는 자신을 회복합니다.<br>지속턴 설정시 지속회복이 들어갑니다.<br>아군 단일/전체를 대상으로 선택하여야 합니다.'");
    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='부활', si_code='rev', si_category=980, si_1='default', si_info='hp가 0인 아군을 회복합니다.<br>지속턴 설정시 지속회복이 들어갑니다.<br>아군 단일/전체를 대상으로 선택하여야 합니다.'");
    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='능력증감', si_code='buff', si_category=980, si_passive=1, si_1='target_sc', si_info='지정한 턴 동안 아군과 적군의 적용대상스탯에 적용값만큼 수치를 더하거나 빼 줍니다.<br>패시브의 경우 영구히 지속됩니다.<br>본인/아군/적 단일/전체를 대상으로 선택할 수 있습니다.'");
    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='도발', si_code='aggr', si_category=980, si_1='percent', si_info='지정한 턴 동안 적의 공격대상을 자기 자신에게로 돌립니다. 전체 대상 공격에는 효과가 없습니다. 무작위 대상 공격의 경우 확정적으로 공격 대상이 됩니다.<br>발동 성공 확률을 지정할 수 있습니다.<br>본인/아군 단일을 대상으로 선택하여야 합니다.'");
    sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='기절', si_code='stun', si_category=980, si_1='percent', si_info='지정한 턴 동안 적을 공격불능으로 만듭니다.<br>발동 성공 확률을 지정할 수 있습니다.<br>적 단일/전체를 대상으로 선택하여야 합니다.'");
    /*sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='밀어내기', si_code='push', si_use=0, si_info='(마스레이드)적이나 아군을 해당 방향으로 밀어냅니다. 장애물이 있을 경우 이동을 멈춥니다.'");
	sql_query (" INSERT into {$g5['k_skill_info_table']} set si_type='환경무시', si_code='env', si_passive=1, si_use=0, si_1='env', si_info='(마스레이드)지정한 턴 동안 지정한 환경에 따른 모든 영향을 무시합니다. 패시브의 경우 영구히 지속됩니다.'");*/
}

$type_list= $type_si1 = $si_info =  $sc_list=array();

$type_sql = sql_query("select * from {$g5['k_skill_info_table']} where si_use=1");

for($i = 0; $row = sql_fetch_array($type_sql); $i++) {
	$type_list[] = $row;
	$type_si1[$row['si_id']]=$row['si_1'];
	$si_info[$row['si_id']]=$row['si_info'];
};

$sc_sql = sql_query("select * from {$g5['k_stat_table']} where sc_category='stat'");
for($i = 0; $row = sql_fetch_array($sc_sql); $i++) {
    $sc_list[] = $row;
}

$sql_common = " from {$g5['k_skill_table']}";

$sql_search = "";

if ($stx) {
	$sql_search .= " and ";
	$sql_search .= "{$sfl} like '{$stx}%'";
}

if (!$sst) {
	$sst = "sk_id asc";
	$sod = "";
}

$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result=array();
$result = sql_query($sql);

$g5['title'] = '스킬 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
?>

<h2 class="h2_frm">스킬 설정</h2>
<form method="post" action="./980_k_skill_update.php">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 100px;" />
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col" colspan='2'>
						스킬 설정
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						스킬 사용
					</td>
					<td style="text-align:left;">
						<select name="limit_skill">
							<option value="">제한 없음</option>
							<option value="999" <?if($kb_cf['limit_skill']==999){echo 'selected';}?>>스킬 사용 안함</option>
						</select> 
					</td>
				</tr>
				<tr>
					<td>
						최대 갯수
					</td>
					<td style="text-align:left;">
						<input type="text" name="skill_max" value="<?php echo $kb_cf['skill_max']?>">개
					</td>
				</tr>
				<tr>
					<td>
						스킬컷 사용
					</td>
					<td style="text-align:left;">
						<input type="checkbox" name="skill_img" value='1' <?if($kb_cf['skill_img']){echo 'checked';}?>>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="업데이트" onclick="document.pressed=this.value">
	</div>

</form>

<h2 class="h2_frm">스킬 등록</h2>
<form action="./980_k_skill_update.php" method="post">
	<input type="hidden" name="type" value="insert">
	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 100px;" />
				<col style="width: 100px;" />
				<col />
				<col style="width: 100px;" />
				<col style="width: 150px;" />
				<col style="width: 50px;" />
				<col style="width: 100px;" />
			</colgroup>
			<thead>
				<tr>
					<th>스킬이름</th>
					<th>스킬종류</th>
					<th>적용값</th>
					<th>사용대상</th>
					<th>지속턴</th>
					<th>소모mp</th>
					<th>사용</th>				
				</tr>
			</thead>
		<tbody>

		<tr>
			<td>
				<input type="text" name="sk_name" class="frm_input" value="">
			</td>

			<td class="txt-left">
				<select name="si_id" class="frm_input" id='si_id' onchange="fn_type_change('si_id','insert','');">
					<?for ($k=0; $k < count($type_list); $k++) {?>
						<option data-passive='<?php echo $type_list[$k]['si_passive']?>' data-attr="<?php echo $type_list[$k]['si_1']?>" data-info="<?php echo $type_list[$k]['si_info']?>" value="<?php echo $type_list[$k]['si_id']?>"><?php echo $type_list[$k]['si_type']?></option>
					<?}?>
				</select>
			
			</td>

			<td class="txt-left">
				<p><span style="background:yellow;">아이콘</span> 
					<input type="text" placeholder="" size="50" name="sk_icon">
				</p>
				<p id="sk_info"><?php echo $type_list[0]['si_info']?></p>
				<p>
					<span style="background:yellow;">스킬설명</span> 
					<input type="text" size="50" name="sk_content" placeholder='커스텀 가능 부분'>
					<input type="text" size="50" name="sk_info" placeholder='커스텀 불가 부분(스킬효과등)'>
				</p>
				<span style="background:yellow;">적용값</span>
					<select name="target_sc" class="sk_changes" id='target_sc' <?if($type_list[0]['si_1']!='target_sc'){?>style="display:none;"<?}?>>
						<option value="">적용대상스탯</option>
						<?for ($k=0; $k < count($sc_list); $k++) {?>
							<option value="<?php echo $sc_list[$k]['sc_id']?>"><?php echo $sc_list[$k]['sc_name']?></option>
						<?}?>
					</select>
					<select name="default_calc" class="sk_changes" id='default' <?if($type_list[0]['si_1']!='default'){?>style="display:none;"<?}?>>
						<option value="">기본수치 제외 </option>
						<option value="p">기본수치 + </option>
						<option value="m">기본수치 * </option>
						<option value="i">기본수치 계산시</option>
					</select>
                	<span>(</span>
					<select name='sc_id'>
					<option value="">관여스탯 없음</option>
					<?for ($k=0; $k < count($sc_list); $k++) {?>
						<option value="<?php echo $sc_list[$k]['sc_id']?>"><?php echo $sc_list[$k]['sc_name']?></option>
					<?}?>
				</select>
                <select name="bonus_calc">
                    <option value="p">+</option>
                    <option value="m">*</option>
                </select>
                <input type="text" placeholder="음수/소수 ok"  size="12" name="sk_value">
				<span>)</span>
				<span id="percent" class="sk_changes" <?if($type_list[0]['si_1']!='percent'){?>style="display:none;"<?}?>>% 확률로 발동</span>
			</td>

			<td class="txt-left">
				<select name="sk_target" class="frm_input" id="sk_target">
					<option value="self">본인</option>
					<option value="ally">아군</option>
					<option value="enemy">적</option>
					<option value="passive" disabled>패시브</option>
				</select>
				<select name="sk_target_cnt" class="frm_input">
					<option value="single">단일</option>
					<option value="all">전체</option>
					<!--<option value="area">범위</option>-->
				</select>
			</td>

			<td>
				발동턴 + <input type="text" name="sk_turn" value="0" size='2'>턴 지속<br>
				쿨타임 <input type="text" name="sk_cool" value="0" size='2'>턴 후 재사용
			</td>

			<td>
				<input type="text" name="sk_mp" size="8" value='0'>
			</td>

			<td>
				<p>커스텀 <input type="checkbox" name="sk_use[]" value="custom"></p>
				<p>자비레이드 <input type="checkbox" name="sk_use[]" value="mmbraid"></p>
			</td>
		</tr>

		</tbody>
		</table>
	</div>
	
	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
	</div>
</form>


<h2 class="h2_frm">등록스킬관리</h2>
<form action="./980_k_skill_update.php" onsubmit="return f_submit(this);" method="post">
	<input type="hidden" name="sst" value="<?php echo $sst ?>">
	<input type="hidden" name="sod" value="<?php echo $sod ?>">
	<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
	<input type="hidden" name="stx" value="<?php echo $stx ?>">
	<input type="hidden" name="page" value="<?php echo $page ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 100px;" />
				<col style="width: 100px;" />
				<col />
				<col style="width: 100px;" />
				<col style="width: 150px;" />
				<col style="width: 50px;" />
				<col style="width: 100px;" />
			</colgroup>
			<thead>
				<tr>
					<th scope="col">
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th>스킬이름</th>
					<th>스킬종류</th>
					<th>적용값</th>
					<th>사용대상</th>
					<th>지속턴</th>
					<th>소모mp</th>
					<th>사용</th>				
				</tr>
			</thead>
		<tbody>
			<?php
			for ($i=0; $row=sql_fetch_array($result); $i++) {
				$bg = 'bg'.($i%2);
			?>

		<tr class="<?php echo $bg; ?>">
			<td>
				<input type="hidden" name="sk_id[<?php echo $i?>]" value="<?php echo $row['sk_id'] ?>" id="sk_id_<?php echo $i ?>">
				<p>ID:<?php echo $row['sk_id']?></p>
				<input type="checkbox" name="chk[]" value="<?php echo $i?>" id="chk_<?php echo $i?>">
			</td>
			<td>
				<img src="<?php echo $row['sk_icon']?>">
				<input type="text" name="sk_name[<?php echo $i?>]" class="frm_input" value="<?php echo $row['sk_name']?>">
			</td>
			<td class="txt-left">
				<select name="si_id[<?php echo $i?>]" class="frm_input" id='si_id<?php echo $i?>' onchange="fn_type_change('si_id','insert','<?php echo $i?>');">
						<option>종류 선택</option>
					<?for ($k=0; $k < count($type_list); $k++) {?>
						<option data-passive='<?php echo $type_list[$k]['si_passive']?>' data-attr="<?php echo $type_list[$k]['si_1']?>"  data-info="<?php echo $type_list[$k]['si_info']?>"  value="<?php echo $type_list[$k]['si_id']?>" <?if($row['si_id']==$type_list[$k]['si_id']){echo 'selected';}?>><?php echo $type_list[$k]['si_type']?></option>
					<?}?>
				</select>
			</td>

			<td class="txt-left">
				<p><span style="background:yellow;">아이콘</span> 
					<input type="text" placeholder="" size="50" name="sk_icon[<?php echo $i?>]" value="<?php echo $row['sk_icon']?>">
				</p>
				<p id="sk_info<?php echo $i?>"><?php echo $si_info[$row['si_id']]?></p>
				<p>
					<span style="background:yellow;">스킬설명</span> 
					<input type="text" placeholder="" size="50" name="sk_content[<?php echo $i?>]" value="<?php echo $row['sk_content']?>">
					<input type="text" placeholder="" size="50" name="sk_info[<?php echo $i?>]" value="<?php echo $row['sk_info']?>">
				</p>
				<span style="background:yellow;">적용값</span>
					<select name="target_sc[<?php echo $i?>]" class="sk_changes<?php echo $i?>" id='target_sc<?php echo $i?>' <?if($type_si1[$row['si_id']]!='target_sc'){?>style="display:none;"<?}?>>
						<option value="">적용대상</option>
						<?for ($k=0; $k < count($sc_list); $k++) {?>
							<option value="<?php echo $sc_list[$k]['sc_id']?>"<?if($row['target_sc']==$sc_list[$k]['sc_id']){echo 'selected';}?>><?php echo $sc_list[$k]['sc_name']?></option>
						<?}?>
					</select>
					<select name="default_calc[<?php echo $i?>]" class="sk_changes<?php echo $i?>" id='default<?php echo $i?>' <?if($type_si1[$row['si_id']]!='default'){?>style="display:none;"<?}?>>
						<option value="" <?if($row['default_calc']==''){echo 'selected';}?>>기본수치 제외 </option>
						<option value="p"<?if($row['default_calc']=='p'){echo 'selected';}?>>기본수치 + </option>
						<option value="m"<?if($row['default_calc']=='m'){echo 'selected';}?>>기본수치 * </option>
						<option value="m"<?if($row['default_calc']=='i'){echo 'selected';}?>>기본수치 계산시 </option>
					</select>
					<span>(</span>
                	<select name='sc_id[<?php echo $i?>]'>
					<option value="">관여스탯 없음</option>
					<?for ($k=0; $k < count($sc_list); $k++) {?>
						<option value="<?php echo $sc_list[$k]['sc_id']?>"<?if($row['sc_id']==$sc_list[$k]['sc_id']){echo 'selected';}?>><?php echo $sc_list[$k]['sc_name']?></option>
					<?}?>
				</select>
                <select name="bonus_calc[<?php echo $i?>]">
                    <option value="p"<?if($row['bonus_calc']=='p'){echo 'selected';}?>>+</option>
                    <option value="m"<?if($row['bonus_calc']=='m'){echo 'selected';}?>>*</option>
                </select>
                <input type="text" placeholder="음수/소수 ok" size="8" name="sk_value[<?php echo $i?>]" value="<?php echo $row['sk_value']?>">
				<span>)</span>
				<span id="percent<?php echo $i?>" class="sk_changes<?php echo $i?>" <?if($type_si1[$row['si_id']]!='passive'){?>style="display:none;"<?}?>>% 확률로 발동</span>
			</td>

			<td class="txt-left">
				<select name="sk_target[<?php echo $i?>]" class="frm_input" id="sk_target<?php echo $i?>">
					<option value="self" <?if($row['sk_target']=='self'){echo 'selected';}?>>본인</option>
					<option value="ally" <?if($row['sk_target']=='ally'){echo 'selected';}?>>아군</option>
					<option value="enemy" <?if($row['sk_target']=='enemy'){echo 'selected';}?>>적</option>
					<option value="passive" <?if($row['sk_target']=='passive'){echo 'selected';}?>>패시브</option>
				</select>
				<select name="sk_target_cnt[<?php echo $i?>]" class="frm_input">
					<option value="single" <?if($row['sk_target_cnt']=='single'){echo 'selected';}?>>단일</option>
					<option value="all" <?if($row['sk_target_cnt']=='all'){echo 'selected';}?>>전체</option>
					<!--<option value="area">범위</option>-->
				</select>
			</td>

			<td>
				발동턴 + <input type="text" name="sk_turn[<?php echo $i?>]" value="<?php echo $row['sk_turn']?>" size='2'>턴 지속<br>
				쿨타임 <input type="text" name="sk_cool[<?php echo $i?>]" value="<?php echo $row['sk_cool']?>" size='2'>턴 후 재사용
			</td>

			<td>
				<input type="text" name="sk_mp[<?php echo $i?>]" size="8" value='<?php echo $row['sk_mp']?>'>
			</td>

			<td>
				<p>커스텀 <input type="checkbox" name="sk_use[<?php echo $i?>][]" value="custom"<?if(strpos($row['sk_use'],"custom")!==false){echo 'checked';}?>></p>
				<p>자비레이드 <input type="checkbox" name="sk_use[<?php echo $i?>][]" value="mmbraid"<?if(strpos($row['sk_use'],"mmbraid")!==false){echo 'checked';}?>></p>
			</td>

		</tr>
	
		<?}?>
		</tbody>
		</table>
	</div>


	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="선택수정" onclick="document.pressed=this.value">
		<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
	</div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<script>
function fn_type_change(e,type,num){
	if(type=='insert'){
		var attr=$("#"+e+num+" option:selected").data('attr');
		var info=$("#"+e+num+" option:selected").data('info');
		var passive=$("#"+e+num+" option:selected").data('passive');
		
		if(passive==1){
			$("#sk_target"+num+" option[value='passive']").prop('disabled',false);
		}else{
			var check=$("#sk_target"+num).val();
			if(check=='passive'){$("#sk_target"+num).val('');}
			$("#sk_target"+num+" option[value='passive']").prop('disabled',true);
		}
		if(attr){
			$(".sk_changes"+num+"#"+attr+num).show();
			$(".sk_changes"+num).not("#"+attr+num).hide();
		}else{
			$(".sk_changes"+num).hide();
		}
		
		$(".sk_changes"+num).val('');
		$("#sk_info"+num).text(info);
	}
}
function f_submit(f)
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
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
