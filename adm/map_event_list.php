<?php
$sub_menu = "710100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
$token = get_token();

$ma_id = $_REQUEST['ma_id'];
$ma = sql_fetch("select * from {$g5['map_table']} where ma_id = '{$ma_id}'");

if(!$ma['ma_id']) { 
	alert("지역정보를 확인할 수 없습니다.");
}

$sql_common = " from {$g5['map_event_table']} where ma_id = '{$ma_id}' ";
$sql_order = " order by me_id asc";

$sql = " select count(*) as cnt
			{$sql_common}
			{$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 20;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select *
			{$sql_common}
			{$sql_order}  limit {$from_record}, {$rows}";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

/** 지역 정보 **/
$ma_list = array();
$ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id = ma_parent order by ma_id asc";
$ma_result = sql_query($ma_sql);
$index = 0;
for($i=0; $map = sql_fetch_array($ma_result); $i++) { 
	$sub_ma_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_use = 1 and ma_id != ma_parent and ma_parent = {$map['ma_id']} order by ma_id asc";
	$sub_ma_result = sql_query($sub_ma_sql);
	for($j=0; $sub_map = sql_fetch_array($sub_ma_result); $j++) { 
		$ma_list[$index]['name'] = $sub_map['ma_name'];
		$ma_list[$index]['id'] = $sub_map['ma_id'];
		$index++;
	}
}

$g5['title'] = "[ ".$ma['ma_name']." ] 지역 이벤트 관리";
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');

$colspan = 10;
?>



<div class="groupWrap" style="min-width:1400px;">
	<div class="form-area">
		
		<form name="fshopform" id="fshopform" action="./map_event_form_update.php" onsubmit="return fshopform_submit(this)" method="post" enctype="multipart/form-data">
			<input type="hidden" name="w" value="<?php echo $w ?>">
			<input type="hidden" name="me_id" value="<?php echo $me_id ?>">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">

			<section id="anc_001">
				<div class="tbl_frm01 tbl_wrap">
					<table>
						<colgroup>
							<col style="width: 100px;">
							<col style="width: 100px;">
							<col>
						</colgroup>
						<tbody>
							<tr>
								<th scope="row">이벤트명</th>
								<td colspan="2">
									<input type="text" name="me_title" value="<?=$me['me_title']?>" />
									<input type="checkbox" name="me_use" id="me_use" value="1" <?=$me['me_use'] == '1'? "checked" : ""?>/>
									<label for="me_use">사용</label>
								</td>
							</tr>
							<tr>
								<th scope="row">내용</th>
								<td colspan="2">
									<textarea name="me_content"><?=get_text($me['me_content'])?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row">
									회득 구간
								</th>
								<td colspan="2">
									<?php echo help("※ 100면체 주사위를 굴렸을 때 나오는 숫자 중 획득 가능 범위를 지정해 주시길 바랍니다. (0 ~ 100)<br />※ 다수의 구간이 겹칠 시,랜덤으로 획득 됩니다.") ?>
									<input type="text" name="me_per_s" value="<?php echo $me['me_per_s']; ?>" id="me_per_s" size="5" maxlength="11">
									~
									<input type="text" name="me_per_e" value="<?php echo $me['me_per_e']; ?>" id="me_per_e" size="5" maxlength="11"> 구간 획득
								</td>
							</tr>
							<tr>
								<th scope="row">획득갯수<br />(현재/최대)</th>
								<td colspan="2">
									<?php echo help("※ 현재 : 현재까지 멤버들이 획득한 갯수를 수정합니다.") ?>
									<?php echo help("※ 최대 : 총 획득 갯수를 제한합니다. 0 입력 시 제한하지 않습니다.") ?>
									<input type="text" name="me_now_cnt" value="<?=$me['me_now_cnt']?>" size="10"/> / <input type="text" name="me_replay_cnt" value="<?=$me['me_replay_cnt']?>" size="10"/>
								</td>
							</tr>
							<tr>
								<th scope="row" rowspan="2">획득</th>
								<td class="bo-right">아이템</td>
								<td>
									<input type="hidden" name="me_get_item" value="<?=$me['me_get_item']?>" />
									<input type="text" name="it_name" value="<?=get_item_name($me['me_get_item'])?>" />
								</td>
							</tr><tr>
								<td class="bo-right"><?=$config['cf_money']?></td>
								<td>
									<input type="text" name="me_get_money" value="<?=get_item_name($me['me_get_money'])?>" style="width:100px;"/> <?=$config['cf_money_pice']?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>

			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="확인" class="btn_submit" accesskey="s">
			</div>
		</form>
	</div>
	<div>
		<div class="local_ov01 local_ov" style="margin-top: 0;">
			<?php echo $listall ?>
			전체 <?php echo number_format($total_count) ?> 건
		</div>

		<form name="fpointlist" id="fpointlist" method="post" action="./map_event_list_update.php" onsubmit="return fpointlist_submit(this);">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_head01 tbl_wrap">
				<table style="table-layout:fixed;">
					<caption><?php echo $g5['title']; ?> 목록</caption>
					<colgroup>
						<col style="width: 45px" />
						<col style="width: 45px" />
						<col />

						<col style="width: 60px;"/>
						<col style="width: 10px" />
						<col style="width: 60px;"/>

						<col style="width: 60px;"/>
						<col style="width: 10px" />
						<col style="width: 60px;"/>
						
						<col style="width: 50px;"/>
					</colgroup>
					<thead>
						<tr>
							<th scope="col"><input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"></th>
							<th>+</th>
							<th scope="col">이벤트명</th>
							<th scope="col" colspan="3">획득구간</th>
							<th scope="col" colspan="3">획득현황</th>
							<th scope="col">사용</th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ($i=0; $me=sql_fetch_array($result); $i++) {
							$bg = 'bg'.($i%2);
						?>

						<tr class="<?php echo $bg; ?>">
							<td class="td_chk">
								<input type="hidden" name="me_id[<?php echo $i ?>]" value="<?php echo $me['me_id'] ?>" id="me_id_<?php echo $i ?>">
								<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
							</td>
							<td>
								<a href="javascript:;" onclick="$(this).closest('tr').next().toggle();" style="display:block; background:#29c7c9; color:#fff; width:30px; line-height:28px;">+</a>
							</td>
							<td>
								<input type="text" name="me_title[<?php echo $i ?>]" value="<?php echo get_text($me['me_title']) ?>" class="frm_input full">
							</td>

							<td style="border-right-width:0;">
								<input type="text" name="me_per_s[<?php echo $i ?>]" value="<?php echo get_text($me['me_per_s']) ?>" class="frm_input txt-center full" style="padding:0;"/>
								</td><td style="border-right-width:0; border-left-width:0; padding:0;">~</td><td style="border-left-width:0;">
								<input type="text" name="me_per_e[<?php echo $i ?>]" value="<?php echo get_text($me['me_per_e']) ?>" class="frm_input txt-center full" style="padding:0;"/>
							</td>

							<td style="border-right-width:0;">
								<input type="text" name="me_now_cnt[<?php echo $i ?>]" value="<?=$me['me_now_cnt']?>" class="frm_input full txt-center" style="padding:0;">
								</td><td style="border-right-width:0; border-left-width:0; padding:0;">/</td><td style="border-left-width:0;">
								<input type="text" name="me_replay_cnt[<?php echo $i ?>]" value="<?=$me['me_replay_cnt']?>" class="frm_input full txt-center" style="padding:0;">
							</td>
							<td>
								<input type="checkbox" name="me_use[<?php echo $i ?>]" value="1" <?=$me['me_use'] == '1'? "checked" : ""?>/>
							</td>
						</tr>
						<tr class="<?php echo $bg; ?>" style="display:none;">
							<td style="background:#efeff1;"></td>
							<td colspan="9">
								<div style="padding:0 5px 10px; text-align:left;">
									<strong style="display:inline-block; vertical-align:middle;">아이템&nbsp;</strong>
									<span style="display:inline-block; vertical-align:middle; width:28px; height:28px; border:1px solid #ddd;">
										<? if($me['me_get_item']) { ?><img src="<?=get_item_img($me['me_get_item'])?>" style="max-width:28px; max-height:28px; vertical-align:middle;" /><? } ?>
									</span>
									<input type="text" name="me_get_item_name[<?php echo $i ?>]" value="<?php echo get_item_name($me['me_get_item']) ?>" class="frm_input" style="width:120px;">
									&nbsp;&nbsp;&nbsp;

									<strong style="display:inline-block; vertical-align:middle;"><?=$config['cf_money']?>&nbsp;</strong>
									<input type="text" name="me_get_money[<?php echo $i ?>]" value="<?=$me['me_get_money']?>" style="width:100px;"/> <?=$config['cf_money_pice']?>
									
								</div>
								<textarea name="me_content[<?php echo $i ?>]" class="frm_input full" style="height:80px;"><?php echo get_text($me['me_content']) ?></textarea>
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
				<a href="./map_list.php" id="bo_add" style="float:right;">지역관리</a>
			</div>
		</form>
		<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;ma_id='.$ma_id.'&amp;page='); ?>
	</div>
</div>

<style>
.groupWrap {display:block; position:relative; overflow:hidden;}
.groupWrap > * {display:block; position:relative; width:40%; box-sizing:border-box; float:left;}
.groupWrap > * + * {float:right; width:58%;}
.groupWrap .form-area {padding:30px 0 0;}
.groupWrap .form-area .btn_confirm {padding:0;}
.groupWrap table {table-layout:fixed;}
</style>

<script>
function fpointlist_submit(f) {
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
