<?php
$sub_menu = "710100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$token = get_token();

$sql_common = " from {$g5['map_table']} ";
$sql_order = " order by ma_parent asc, ma_id asc ";

$sql = " select count(*) as cnt {$sql_common} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 50;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$listall = '<a href="'.$_SERVER['PHP_SELF'].'" class="ov_listall">전체목록</a>';

$g5['title'] = '지역 관리';
include_once ('./admin.head.php');
include_once('./990_unified_menu_bootstrap.php');
$colspan = 13;
?>

<div class="groupWrap" style="min-width:1400px;">
	<div class="form-area">
		<form name="fmapconfiglist" method="post" id="fmapconfiglist" action="./map_update.php" autocomplete="off">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<input type="hidden" name="type" value="CONFIG">

			<div class="tbl_frm01 tbl_wrap">
				<table>
				<colgroup>
					<col style="width: 100px;">
					<col style="width: 250px;">
					<col>
				</colgroup>
				<tbody>
				<tr>
					<th scope="row"><label for="cf_use_map">지역 기능</label></th>
					<td colspan="2">
						<input type="checkbox" name="cf_use_map" value="1" id="cf_use_map" <? if($config['cf_use_map']) { ?>checked<? } ?>>
						<label for="cf_use_map">사용</label>
					</td>
				</tr>
				<tr>
					<th <?=$config['cf_use_map_all'] ? "rowspan='2'" :""?> scope="row"><label for="cf_use_map_all">전체지역</label></th>
					<td colspan="2">
						<input type="checkbox" name="cf_use_map_all" value="1" id="cf_use_map_all" <? if($config['cf_use_map_all']) { ?>checked<? } ?>>
						<label for="cf_use_map_all">전체지도사용</label><br />
						<p style="padding-top:5px;">
							<?php echo help("전체지도를 사용시, 최초 지역 진입 때 전체 지도가 출력됩니다. 이후 상위지역을 선택할 경우 상위 지역으로 이동됩니다.") ?>
						</p>
					</td>
				</tr>
				<? if($config['cf_use_map_all']) { ?>
				<tr>
					<td>
						<div style="display:block; position:relative; height:100px; border:1px solid #ddd;">
							<? if($config['cf_map_all_img']) { ?>
								<img src="<?=$config['cf_map_all_img']?>" style="display:block; width:100%; height:100%; object-fit:cover;" onerror="this.remove();"/>
							<? } ?>
						</div>
						<input type="text" name="cf_map_all_img" value="<?php echo get_text($config['cf_map_all_img']) ?>" class="frm_input full" placeholder="지도 이미지 URL">
					</td>
					<td>
						W : <input type="text" name="cf_map_all_w" value="<?php echo get_text($config['cf_map_all_w']) ?>" class="frm_input" style="width:50px;"> px
						&nbsp;&nbsp;
						H : <input type="text" name="cf_map_all_h" value="<?php echo get_text($config['cf_map_all_h']) ?>" class="frm_input" style="width:50px;"> px

					</td>
				</tr>
				<? } else { ?>
				<tr>
					<td colspan="2" style="padding:0; border:none;">
						<input type="hidden" name="cf_map_all_img" value="<?php echo get_text($config['cf_map_all_img']) ?>" />
						<input type="hidden" name="cf_map_all_w" value="<?php echo get_text($config['cf_map_all_w']) ?>" />
						<input type="hidden" name="cf_map_all_h" value="<?php echo get_text($config['cf_map_all_h']) ?>" />
					</td>
				</tr>
				<? } ?>
				</tbody>
				</table>
				<br />
				<div class="btn_confirm01 btn_confirm">
					<input type="submit" value="확인" class="btn_submit">
				</div>
			</div>
		</form>
		<br /><br />
		<form name="fpointlist2" method="post" id="fpointlist2" action="./map_update.php" autocomplete="off">
			<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
			<input type="hidden" name="stx" value="<?php echo $stx ?>">
			<input type="hidden" name="sst" value="<?php echo $sst ?>">
			<input type="hidden" name="sod" value="<?php echo $sod ?>">
			<input type="hidden" name="page" value="<?php echo $page ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">

			<div class="tbl_frm01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width:100px;">
						<col>
					</colgroup>
					<tbody>
					<tr>
						<th scope="row"><label for="ma_parent">지역 정보</label></th>
						<td>
							<select id="ma_parent" name="ma_parent">
								<option value="">상위지역</option>
							<?
								$pa_sql = "select ma_id, ma_name from {$g5['map_table']} where ma_parent = ma_id order by ma_id asc";
								$pa_result = sql_query($pa_sql);

								for($i=0; $row = sql_fetch_array($pa_result); $i++) { 
							?>
								<option value="<?=$row['ma_id']?>"  <?=get_cookie("co_ma_parent") == $row['ma_id'] ? "selected" : ""?>><?=$row['ma_name']?></option>
							<? } ?>
							</select>
							<input type="text" name="ma_name" value="" id="ma_name" class="required frm_input" required placeholder=" 지역명 입력">
							&nbsp;&nbsp;
							<input type="checkbox" name="ma_use" value="1" id="ma_use" checked>
							<label for="ma_use">사용여부</label>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<br />
			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="확인" class="btn_submit">
			</div>

		</form>
	</div>
	<div>
		<section id="anc_001">
			<div class="local_ov01 local_ov" style="margin-top: 0;">
				<?php echo $listall ?>
				전체 <?php echo number_format($total_count) ?> 건
			</div>
			<?php echo help("- X, Y 좌표는 px 단위로 작성해 주시길 바랍니다.") ?>
			<?php echo help("- X, Y 의 값이 모두 0, 0인 경우에는 지도에 마커가 출력되지 않습니다.") ?>
			<form name="fpointlist" id="fpointlist" method="post" action="./map_list_update.php" onsubmit="return fpointlist_submit(this);">
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
					<col style="width: 45px" />
					<col style="width: 50px" />
					<col style="width: 50px" />

					<col style="width: 40px" />
					<col />

					<col style="width: 50px;" />
					<col style="width: 50px;" />
					<col style="width: 50px;" />
					
					<col style="width: 70px;" />
					<col style="width: 70px;" />
					<col style="width: 80px;" />
					<col style="width: 80px;"/>
					
					<col style="width: 70px;"/>
				</colgroup>
				<thead>
				<tr>
					<th scope="col">
						<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
					</th>
					<th>IDX</th>
					<th>상세</th>
					<th scope="col" colspan="2">지역명</th>
					<th scope="col">사용</th>
					<th scope="col" title="캐릭터 생성 시 최초로 시작할 지역을 설정합니다." style="font-weight:800;">시작</th>
					<th scope="col" title="던전이 열릴 수 있는 지역을 체크합니다." style="font-weight:800;">던전</th>
					<th>X</th>
					<th>Y</th>
					<th>W</th>
					<th>H</th>
					<th>이벤트</th>
				</tr>
				</thead>
				<tbody>
				<?php
				for ($i=0; $row=sql_fetch_array($result); $i++) {
					$bg = 'bg'.($i%2);
					$is_parent = true;
					if($row['ma_parent'] != $row['ma_id']) $is_parent = false;
				?>

				<tr class="<?php echo $bg; ?>">
					<td class="td_chk">
						<input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
					</td>
					<td class="td_chk">
						<input type="text" name="ma_id[<?php echo $i ?>]" value="<?php echo $row['ma_id'] ?>" id="ma_id_<?php echo $i ?>" readonly class="full">
					</td>
					<td>
						<a href="javascript:;" onclick="$(this).closest('tr').next().toggle();" style="display:block; background:#29c7c9; color:#fff; width:30px; line-height:28px;">+</a>
					</td>

					<? if(!$is_parent) { ?>
					<td>
						┗…
					</td>
					<td>
					<? } else { ?>
					<td colspan="2">
					<? } ?>
						<input type="text" name="ma_name[<?php echo $i ?>]" value="<?php echo get_text($row['ma_name']) ?>" id="ma_name_<?php echo $i ?>" required class="required frm_input full" size="20">
					</td>

					<td style="Text-align: center;">
						<input type="checkbox" name="ma_use[<?php echo $i ?>]" value="1" id="ma_use_<?php echo $i ?>" <?php echo $row['ma_use']?"checked":"" ?>>
					</td>

					<? if(!$is_parent) { ?>
					<td style="Text-align: center;">
						<input type="checkbox" name="ma_start[<?php echo $i ?>]" value="1" id="ma_start_<?php echo $i ?>" <?php echo $row['ma_start']?"checked":"" ?>>
					</td>
					<td style="Text-align: center;">
						<input type="checkbox" name="ma_use_dungeon[<?php echo $i ?>]" value="1" id="ma_use_dungeon_<?php echo $i ?>" <?php echo $row['ma_use_dungeon']?"checked":"" ?>>
					</td>
					<? } else { ?>
					<td colspan="2"></td>
					<? } ?>

					<? if(!$is_parent || ($is_parent && $config['cf_use_map_all'])) { ?>
					<td>
						<input type="text" name="ma_left[<?php echo $i ?>]" value="<?php echo get_text($row['ma_left']) ?>" class="frm_input full">
					</td>
					<td>
						<input type="text" name="ma_top[<?php echo $i ?>]" value="<?php echo get_text($row['ma_top']) ?>" class="frm_input full">
					</td>
					<? } else { ?>
						<td colspan="2">
							<input type="hidden" name="ma_left[<?php echo $i ?>]" value="<?php echo get_text($row['ma_left']) ?>" />
							<input type="hidden" name="ma_top[<?php echo $i ?>]" value="<?php echo get_text($row['ma_top']) ?>" />
						</td>
					<? } ?>

					<? if($is_parent) { ?>
					<td>
						<input type="text" name="ma_width[<?php echo $i ?>]" value="<?php echo get_text($row['ma_width']) ?>" class="frm_input full">
					</td>
					<td>
						<input type="text" name="ma_height[<?php echo $i ?>]" value="<?php echo get_text($row['ma_height']) ?>" class="frm_input full">
					</td>
					<? } else { ?>
						<td colspan="2">
							<input type="hidden" name="ma_width[<?php echo $i ?>]" value="<?php echo get_text($row['ma_width']) ?>" />
							<input type="hidden" name="ma_height[<?php echo $i ?>]" value="<?php echo get_text($row['ma_height']) ?>" />
						</td>
					<? } ?>

					<td>
						<?
							if(!$is_parent) { 
							// 이벤트 카운터 검색
								$me_cnt = sql_fetch("select count(me_id) as cnt from {$g5['map_event_table']} where ma_id = '{$row['ma_id']}'");
								$me_cnt = $me_cnt['cnt'];
						?>
							<a href="./map_event_list.php?ma_id=<?=$row['ma_id']?>"><?=$me_cnt?>건</a>
						<? } ?>
					</td>
				</tr>
				<tr class="<?php echo $bg; ?>" style="display:none;">
					<td style="background:#efeff1;"></td>

					<? if($is_parent) { ?>
					<td colspan="4">
						<div style="display:block; position:relative; height:100px; border:1px solid #ddd;">
							<? if($row['ma_img']) { ?>
								<img src="<?=$row['ma_img']?>" style="display:block; width:100%; height:100%; object-fit:cover;" />
							<? } ?>
						</div>
						<input type="text" name="ma_img[<?php echo $i ?>]" value="<?php echo get_text($row['ma_img']) ?>" class="frm_input full" placeholder="지도 이미지 URL">
					</td>
					<td colspan="8">
					<? } else { ?>
						<td style="background:#efeff1;"></td>
						<td style="background:#efeff1;"></td>
						<td colspan="10">
							<input type="hidden" name="ma_img[<?php echo $i ?>]" value="<?php echo get_text($row['ma_img']) ?>" />
					<? } ?>
						<textarea name="ma_content[<?php echo $i ?>]" class="frm_input full" style="height:120px;"><?php echo get_text($row['ma_content']) ?></textarea>
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

			<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>
		</section>
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
function fpointlist_submit(f)
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
