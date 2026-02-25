<?php
include_once('./_common.php');

if($category=='stat'){
	$sub_menu = "980110";
	$title="커스텀 스탯";
}elseif($category=='battle'){
	$sub_menu = "980120";
	$title="전투 함수";
}
if(!sql_query(" DESC {$g5['k_stat_table']} ", false)) {
	sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['k_stat_table']}` (
				  `sc_id` int(11) NOT NULL AUTO_INCREMENT,
				  `sc_name` varchar(255) NOT NULL,
				  `sc_value` varchar(255) NOT NULL,
				  `sc_type` varchar(255) NOT NULL,
				  `sc_category` varchar(255) NOT NULL,
				  `sc_1` varchar(255) NOT NULL,
				  `sc_2` varchar(255) NOT NULL,
				  `sc_3` varchar(255) NOT NULL,
				  `sc_4` varchar(255) NOT NULL,
				  `sc_5` varchar(255) NOT NULL,
				  `sc_6` varchar(255) NOT NULL,
				  `sc_7` varchar(255) NOT NULL,
				  `sc_8` varchar(255) NOT NULL,
				  `sc_9` varchar(255) NOT NULL,
				  `sc_10` varchar(255) NOT NULL,
				  PRIMARY KEY (`sc_id`)
				) ", false);
}

$token = get_token();

$sql_common = " from {$g5['k_stat_table']}";

$sql_search = " where sc_category='{$category}' ";


$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_search}";
$row = sql_fetch($sql);

$total_count = $row['cnt'];

$sql = " select *
			{$sql_common}
			{$sql_search}";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$ch = array();
if ($sfl == 'ch_id' && $stx)
	$ch = get_member($stx);

$g5['title'] = $title.' 설정';
include_once ('./admin.head.php');
include_once ('./admin.stat.php');

if($kb_cf['sample_unit']){
	$sample_unit=explode("|",$kb_cf['sample_unit']);
}
if($kb_cf['sample_target']){
	$sample_target=explode("|",$kb_cf['sample_target']);
}

$colspan = 5;
$status = $default_stat = array();
$st_name = $is = array();

$st_result = sql_query("select * from {$g5['status_config_table']} order by st_order asc");
for($i = 0; $row = sql_fetch_array($st_result); $i++) {
	$default_stat[]=$row;
	$st_tag="st_".($i+1);
	if($sample_unit[$i]){
		$default_unit[$st_tag]=$sample_unit[$i];
	}
	if($sample_target[$i]){
		$default_target[$st_tag]=$sample_target[$i];
	}
}
if($category=='stat'){
	$sc_list=array();
	$sc_sql=sql_query($sql);
	for($i = 0; $row = sql_fetch_array($sc_sql); $i++) {
		$sc_list[] = $row;
	}
	$st_result = sql_query("select * from {$g5['status_config_table']} order by st_order asc");
	for($i = 0; $row = sql_fetch_array($st_result); $i++) {
		$status[] = $row;
		$st_name[$row['st_id']]=$row['st_name'];
	}
}else{
	$st_result = sql_query("select * from {$g5['k_stat_table']}");
	$stat=array();
	$h=1;
	for($i = 0; $row = sql_fetch_array($st_result); $i++) {
		if($row['sc_category']=='stat'){
			$row['st_id'] = $row['sc_id'];
			$row['st_name'] = $row['sc_name'];
			$status[] = $row;
			$st_name[$row['st_id']]=$row['st_name'];
			$tag="st_".$h;
			if($config['k_sample_unit']){
				$unit[$tag]=get_k_status($default_unit,$row['sc_id'],'test');
			}
			if($config['k_sample_target']){
				$target[$tag]=get_k_status($default_target,$row['sc_id'],'test');
			}
			$k_stat_name[$tag]=$row['sc_name'];
			$h++;
		}else{
			$is[$row['sc_name']]['sc_id']=$row['sc_id'];
		}
	}
}
?>
<style>
	.f_in{
		display:flex;
		padding:0;
		align-items:center;
		flex-wrap:wrap;
	}

	.f_in .ui-btn{
		list-style:none;
		padding:0 10px;
		margin: 0 3px;
		background-color:lightblue;
		min-width:40px;
		font-weight:900;
		border:1px solid skyblue;
		cursor:pointer;
		height:20px;
	}
	.f_in .ui-btn.stat_r{
		background-color:navy;
		color:white;
		font-weight:300;
	}
	<?if($category=='battle'){?>
		.f_in .ui-btn:before{
			content:'내 ';
		}
	<?}?>
	.f_in .ui-btn.stat_r:before,
	.f_in .ui-btn.func_r:before{
		content:'상대 ';
	}

	.f_in .ui-btn.cons:before,
	.f_in .ui-btn.math:before{
		content:"";
	}
	.f_in .ui-btn.math{
		min-width:0px;
		background-color:lightpink;
		border:1px solid pink;
	}
	.f_in .ui-btn.func_m,
	.f_in .ui-btn.func_r{
		background-color:lightgreen;
		border:1px solid green;
	}
	.f_in .ui-btn.func_r{
		background-color:darkgreen;
		color:white;
		font-weight:300;
	}
	.f_in .ui-btn.math.open,
	.f_in .ui-btn.math.close{
		background-color:transparent;
		border-color:transparent;
	}
	.f_in .ui-btn.cons{
		background-color:palegoldenrod;
		border:1px solid gold;
	}
	.depth{
		display:flex;
		align-items:center;
		padding: 3px;
		background-color:#00000022;
	}

	.disabled *{
		filter:grayscale(1);
		pointer-events:none!important;
	}

</style>

<?if($category=='stat'){?>

<h2 class="h2_frm">hp/mp 설정</h2>
<form method="post" action="./980_k_stat_func_update.php?category=<?php echo $category?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 100px;" />
				<col/>
			</colgroup>
			<thead>
				<tr>
					<th scope="col" colspan='2'>
						hp/mp 등록
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<input type="text" name='hp_name' value='<?php echo $kb_cf['hp_name']?>'>
					</td>
					<td style="text-align:left;">
						<select name="hp">
							<option value="">선택</option>
							<?for ($i=0; $i < count($status); $i++) {?>
								<option value="<?php echo $status[$i]['st_id']?>"<?	if($status[$i]['st_id']==$kb_cf['hp']){echo 'selected';}?>><?php echo $status[$i]['st_name']?></option>
							<?}?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<input type="text" name='mp_name' value='<?php echo $kb_cf['mp_name']?>'>
					</td>
					<td style="text-align:left;">
						<select name="mp">
							<option value="">선택</option>
							<?for ($i=0; $i < count($status); $i++) {?>
								<option value="<?php echo $status[$i]['st_id']?>"<?	if($status[$i]['st_id']==$kb_cf['mp']){echo 'selected';}?>><?php echo $status[$i]['st_name']?></option>
							<?}?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						속도
					</td>
					<td style="text-align:left;">
						<select name="speed">
							<option value="">선택</option>
							<?for ($i=0; $i<count($sc_list); $i++) {?>
								<option value="<?php echo $sc_list[$i]['sc_id']?>"<?if($sc_list[$i]['sc_id']==$kb_cf['speed']){echo 'selected';}?>><?php echo $sc_list[$i]['sc_name']?></option>
							<?}?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="업데이트" onclick="document.pressed=this.value">
	</div>

</form>

<?}?>
<h2 class="h2_frm"><?php echo $title?> 등록</h2>

<?if(count($is)<count($func_list)){?>
	<form method="post" action="./980_k_stat_func_update.php?category=<?php echo $category?>" onsubmit="return f_submit(this);">

		<div class="tbl_head01 tbl_wrap <?if($total_count>9&&$category=='stat'){echo 'disabled';}?>">
			<table>
				<colgroup>
					<col style="width: 150px;" />
					<col style="" />
				</colgroup>
				<tbody>
					<thead>
						<tr>
							<th scope="col" colspan='2'>
								등록
							</th>
						</tr>
					</thead>
					<tr>
						<td>
							명칭	
						</td>
						<td class='txt-left'>
							<?if($category=='stat'){?>
								<input type="text" name="sc_name">
								<input type="hidden" value="normal" id="result_type">
							<?}elseif($category=='battle'){?>
								<select name="sc_name" onchange="change_type(this.value);";>
									<option value="">선택</option>
									<?
									for ($i=0; $i < count($func_list); $i++) { 
										if(!$is[$func_list[$i]['code']]['sc_id']){?>
											<option value="<?php echo $func_list[$i]['code']?>"><?php echo $func_list[$i]['name']?></option>
										<?}
									}?>
								</select>
							<?}?>
						</td>
					</tr>
					<tr>
						<td>
							<?if($category=='battle'){ echo"커스텀 ";}?>스탯	
						</td>
						<td>
							<ul class='f_in'>
							<?for ($i=0; $i < count($status); $i++) {?>
								<li class="ui-btn" onclick="f_in('stat',<?php echo $status[$i]['st_id']?>,'<?php echo $status[$i]['st_name']?>')"><?php echo $status[$i]['st_name']?></li>
								<?if($category=='battle'){ ?>
									<li class="ui-btn stat_r" onclick="f_in('stat_r',<?php echo $status[$i]['st_id']?>,'<?php echo $status[$i]['st_name']?>')"><?php echo $status[$i]['st_name']?></li>
								<?}
							}?>
							</ul>
						</td>
					</tr>
					<tr>
						<td>
							연산자	
						</td>
						<td>
							<ul class='f_in'>
								<li class="ui-btn math" onclick="f_in('math','+');">+</li>
								<li class="ui-btn math" onclick="f_in('math','-');">-</li>
								<li class="ui-btn math" onclick="f_in('math','*');">*</li>
								<li class="ui-btn math" onclick="f_in('math','/');">/</li>
								<li class="ui-btn math open" onclick="f_in('math','(');">(</li>
								<li class="ui-btn math close" onclick="f_in('math',')');">)</li>
							</ul>

						</td>
					</tr>
					<tr>
						<td>
							상수	
						</td>
						<td class='f_in'>
							<input type="text" value="" id="cons"> <span class="ui-btn cons" onclick="f_in('cons');">입력</span>
						</td>
					</tr>
					<tr>
						<td>
							결과
						</td>
						<td>
							<ul class='f_in result' id="normal">

							</ul>
						</td>
					</tr>
					<tr>
						<td>
							보정
						</td>
						<td style="text-align:left;">
						    <p><span>최소값</span><input type="text" name="min_value" placeholder="정수">~최대값<input type="text" name="max_value"  placeholder="정수"></p>
						    <p><span>소수점</span>
								<select name="round">
										<option value="round">반올림</option>
										<option value="ceil">올림</option>
										<option value="floor">내림</option>
								</select>
							</p>
							<?if($category=='battle'){?>
								<p id="cri">
									<span>치명타사용</span>
										<select name="use_cri">
											<option value="0">미사용</option>
											<option value="1">사용</option>
									</select>
								</p>
								<p>
									<span>랜덤보정</span> 최종값 +-<input type="text" name="rand"  placeholder="정수">
								</p>
							<?}?>
						</td>
					</tr>

				</tbody>
			</table>
		</div>

		<div class="btn_list01 btn_list">
			<input type="submit" name="act_button" value="등록" onclick="document.pressed=this.value">
		</div>
	</form>
<?}?>
<h2 class="h2_frm"><?php echo $title?> 목록</h2>
<form method="post" action="./980_k_stat_func_update.php?category=<?php echo $category?>">
<div class="tbl_head01 tbl_wrap">
	<table>
		<colgroup>
			<col style="width: 100px;" />
			<?for ($i=0; $i < count($default_stat); $i++) { 
				echo '<col>';
			}
			if($category=='battle'){
				for ($i=0; $i < count($unit) ; $i++) { 
					echo '<col>';
				}
			}?>
			
		</colgroup>
		<thead>
			<tr>
				<th scope="col">
				</th>
				<?for ($i=0; $i < count($default_stat); $i++) { 
					echo '<th scope="col">'.$default_stat[$i]['st_name'].'</th>';
				}
				if($category=='battle'){
					foreach($unit as $key => $value){
						echo '<th scope="col">'.$k_stat_name[$key].'</th>';
					}
				}?>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					유닛 샘플
				</td>
				<?for ($i=0; $i < count($default_stat); $i++) {
					$st_tag="st_".($i+1);?>
					<td><input type="text" value="<?php echo $default_unit[$st_tag]?>" name="sample_unit[]"></td>
				<?}
				if($category=='battle'){
					foreach($unit as $value){?>
						<td><?php echo $value?></td>
					<?}
				}?>
			</tr>
			<?if($category=='battle'){?>
				<td>
					타겟 샘플
				</td>
				<?for ($i=0; $i < count($default_stat); $i++) {
					$st_tag="st_".($i+1);
				?>
					<td><input type="text" value="<?php echo $default_target[$st_tag]?>" name="sample_target[]"></td>
				<?}
				foreach($target as $value){
				?>
					<td><?php echo $value?></td>
				<?}
			}?>
		</tbody>
	</table>
</div>

<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="샘플등록" onclick="document.pressed=this.value">
</div>
</form>

<br>
<form name="forderlist" id="forderlist" method="post" action="./980_k_stat_func_update.php?category=<?php echo $category?>" onsubmit="return forderlist_submit(this);">
	<input type="hidden" name="page" value="<?php echo $page ?>">
	<input type="hidden" name="token" value="<?php echo $token ?>">

	<div class="tbl_head01 tbl_wrap">
		<table>
			<colgroup>
				<col style="width: 50px;" />
				<col style="width: 50px;" />
				<col style="width: 150px;" />
				<col />
				<col style="width: 150px;" />
				<col style="width: 100px;" />
			</colgroup>
			<thead>
				<tr>
					<th scope="col">
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th scope="col">ID</th>
					<th scope="col">명칭</th>
					<th scope="col">내용</th>
					<th scope="col">보정</th>
					<th scope="col">샘플</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i=0; $row=sql_fetch_array($result); $i++) {
					$bg = 'bg'.($i%2);
					$val = $type = array();
					$val=explode("|", $row['sc_value']);
					$type=explode("|", $row['sc_type']);
				?>

				<tr class="<?php echo $bg; ?>">
					<td>
						<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
						<input type="hidden" name="sc_id[<?php echo $i ?>]" value="<?php echo $row['sc_id'] ?>" />
					</td>
					<td><?php echo get_text($row['sc_id']); ?></td>
					<td><?php echo get_text($row['sc_name']); ?></td>
					<td>
						<ul class="f_in">
							<?for ($h=0; $h < count($val); $h++) {
								$type2=$div1=$div2='';
								if($type[$h]=='stat'||$type[$h]=='stat_r'){
									$inner=$st_name[$val[$h]];
								}else{
									$inner=$val[$h];
								}
								if($val[$h]=="("){
									$div1="<div class='depth'>";
									$type2='open';
								}elseif($val[$h]==")"){
									$type2='close';
									$div2="</div>";
								}
								echo "{$div1}<li class='ui-btn {$type[$h]} {$type2}'>{$inner}</li>{$div2}";
							}?>
						</ul>
					</td>
					<td style="text-align:left;">
						<p>최소값 <?php echo $row['sc_2']?> ~ 최대값 <?php echo $row['sc_3']?></p>
						<p><span>소수점</span>
						<?
							if($row['sc_1']=='round'){echo "반올림";
							}elseif($row['sc_1']=='ceil'){echo "올림";
							}elseif($row['sc_1']=='floor'){echo "내림";
							}?>
						</p>
						<?if($category=='battle'){?>
							<p>
								<span>치명타</span>
								<?if($row['sc_4']){echo "사용";}else{echo "미사용";}?>
							</p>
							<p>
								<span>랜덤보정</span> 최종값 +- <?php echo $row['sc_5']?>
							</p>
							
						<?}?>
					</td>
					<td>
						<?if($category=='stat'){
							echo get_k_status($default_unit,$row['sc_id'],'test');
						}else{
							$value=get_k_battle_func($row['sc_name'],$unit,$target);
							if($value['cri']){echo '크리티컬<br>';}
							echo $value['value'];
						}?>
					</td>
				</tr>
						
				<?}
			
				if ($i == 0)
					echo '<tr><td colspan="'.$colspan.'" class="empty_table">자료가 없습니다.</td></tr>';
				?>
			</tbody>
		</table>
	</div>

	<div class="btn_list01 btn_list">
		<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value">
	</div>

</form>
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['PHP_SELF']}?$qstr&amp;page="); ?>



<script>
function f_submit(f)
{
	var msg ='';
	var li=$(".result#normal li").length;
	if(li==0){
		msg='수식을 등록해 주세요.';
	}else{
		var last=$(".result#normal li").last();
		if($(last).hasClass("math")&&!$(last).hasClass("close")){
			msg='수식은 연산자로 종료될 수 없습니다.';
		}else{
			var open=$(".result#normal li.open").length;
			var close=$(".result#normal li.close").length;
			if(open!=close){
				msg='괄호가 잘못되었습니다. 체크해 주세요.';
			}
		}
	}
	

	if(msg){
		alert(msg);
		return false;
	}
	return true;
}

function f_in(type,value='',html=''){
	var blank='';
	var result='normal';

    if(type=='cons'){
        value = $("#cons").val();
    }
    
    if(!value){
        return false;
    }
	if(!html){
		html=value;
	}
	var incheck=in_check(type,value,html,result);
	if(!incheck){
		return false;
	}
	if(html=='('){
		blank= 'open';
	}else if(html==')'){
		blank= 'close';
	}
    var thisli=`<li class="ui-btn ${type} ${blank}" onclick="$(this).remove()"><input type="hidden" value="${value}" name="value_${result}[]"><input type="hidden" value="${type}" name="type_${result}[]">${html}</li>`;
    $('.result#'+result).append(thisli);
}

function in_check(type,value,html,result){
	var msg='';
	var number=false;
	var last=$(".result#"+result+" li").last();
	if(type!='math'){
		number = true;
	}

	if(!$(last).text()){
		if(type=='math'&&html!='('){
			msg='스탯 또는 상수로 시작해야 합니다.'
		}
	}else{
		if(number||html=="("){
			if(!$(last).hasClass('math')||$(last).hasClass('close')){
				msg='연산자가 들어갈 자리입니다.';
			}
		}else if(!number&&html!="("){
			if(html==")"){
				if($(last).hasClass('open')){
					msg='괄호를 바로 닫을 수 없습니다.';
				}else{
					var open=$(".result#"+result+" li.open").length;
					var close=$(".resul#"+result+" li.close").length;
					if(open<=close){
						msg='괄호가 잘못되었습니다. 체크해 주세요.';
					}
				}

			}else if($(last).hasClass('math')&&!$(last).hasClass('close')){
				msg='상수 또는 스탯치가 들어갈 자리입니다.';
			}
		}
	}
	if(msg){
		alert(msg);
		return false;
	}else{
		return true;
	}
}

function change_type(val){
	$(".result#normal").empty();
	
	if(val=='crival'||val=='criper'){
		$("#cri").addClass('disabled');
		$("#cri select").val('0');
	}else{
		$("#cri").removeClass('disabled');
	}
}
</script>

<?php
include_once ('./admin.tail.php');
?>
