<?php
$sub_menu = "710100";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');
$token = get_token();

$ma_id = $_REQUEST['ma_id'];
$open_choice = isset($_REQUEST['open_choice']) ? (int)$_REQUEST['open_choice'] : 0;
$ma = sql_fetch("select * from {$g5['map_table']} where ma_id = '{$ma_id}'");
$action_types = map_get_action_types();
$action_list = get_map_actions($ma_id, false);
$place_npc_list = map_get_place_npcs($ma_id, false);
$place_npc_script_map = map_get_npc_scripts_by_place_ids(array_column($place_npc_list, 'mn_id'));
$map_script_ids = array();
foreach($place_npc_script_map as $scripts) foreach($scripts as $script) $map_script_ids[] = $script['script_id'];
$map_choice_map = map_get_npc_choices_by_script_ids($map_script_ids);
$map_script_require_groups = array();
$script_require_result = sql_query("\n\tselect sc.script_id, sc.script_title, sc.script_use, mn.mn_id, mn.mn_use, ma.ma_id, ma.ma_name, npc.ch_name\n\t\tfrom {$g5['map_npc_script_table']} sc\n\t\tjoin {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id\n\t\tjoin {$g5['map_table']} ma on mn.ma_id = ma.ma_id\n\t\tleft join {$g5['character_table']} npc on mn.npc_id = npc.ch_id\n\t\torder by (mn.ma_id = '".(int)$ma_id."') desc, ma.ma_name asc, npc.ch_name asc, sc.script_order asc, sc.script_id asc\n", false);
if($script_require_result) {
	for($i=0; $require_script = sql_fetch_array($script_require_result); $i++) {
		$group_key = (int)$require_script['ma_id'].'_'.(int)$require_script['mn_id'];
		if(!isset($map_script_require_groups[$group_key])) {
			$group_label = ((int)$require_script['ma_id'] == (int)$ma_id ? '현재 장소 · ' : '');
			$group_label .= $require_script['ma_name'].' · '.($require_script['ch_name'] ? $require_script['ch_name'] : 'NPC 정보 없음');
			$map_script_require_groups[$group_key] = array(
				'label' => $group_label,
				'scripts' => array()
			);
		}
		$map_script_require_groups[$group_key]['scripts'][] = $require_script;
	}
}

function map_script_require_options($groups, $selected_id=0, $exclude_id=0)
{
	$html = '';
	$selected_id = (int)$selected_id;
	$exclude_id = (int)$exclude_id;
	foreach($groups as $group) {
		$option_html = '';
		foreach($group['scripts'] as $script) {
			$script_id = (int)$script['script_id'];
			if($script_id == $exclude_id) continue;
			$state = (!$script['script_use'] || !$script['mn_use']) ? ' (미사용)' : '';
			$option_html .= '<option value="'.$script_id.'"'.($selected_id == $script_id ? ' selected' : '').'>['.$script_id.'] '.get_text($script['script_title']).$state.'</option>';
		}
		if($option_html) $html .= '<optgroup label="'.get_text($group['label']).'">'.$option_html.'</optgroup>';
	}
	return $html;
}
$npc_name_options = array();
$map_npc_ids = array();
for($i=0; $i < count($place_npc_list); $i++) $map_npc_ids[(int)$place_npc_list[$i]['npc_id']] = true;
$npc_result = sql_query("select ch_id, ch_name from {$g5['character_table']} where ch_type = 'npc' order by ch_name asc, ch_id asc");
for($i=0; $npc_row = sql_fetch_array($npc_result); $i++) {
	$npc_name_options[] = $npc_row;
}
usort($npc_name_options, function($a, $b) use ($map_npc_ids) {
	$a_place = isset($map_npc_ids[(int)$a['ch_id']]) ? 0 : 1;
	$b_place = isset($map_npc_ids[(int)$b['ch_id']]) ? 0 : 1;
	if($a_place != $b_place) return $a_place - $b_place;
	return strcmp($a['ch_name'], $b['ch_name']);
});
$map_stat_options = array();
$stat_result = sql_query("select st_name from {$g5['status_config_table']} order by st_order asc, st_id asc", false);
if($stat_result) {
	for($i=0; $stat_row = sql_fetch_array($stat_result); $i++) {
		$map_stat_options[] = $stat_row['st_name'];
	}
}
$map_item_options = array();
$item_result = sql_query("select it_id, it_name from {$g5['item_table']} order by it_name asc, it_id asc", false);
if($item_result) for($i=0; $item_row = sql_fetch_array($item_result); $i++) $map_item_options[] = $item_row;
$s_me_type = isset($_REQUEST['s_me_type']) ? $_REQUEST['s_me_type'] : '';
$s_action_id = isset($_REQUEST['s_action_id']) ? (int)$_REQUEST['s_action_id'] : 0;
$s_me_use = isset($_REQUEST['s_me_use']) ? $_REQUEST['s_me_use'] : '';

if(!$ma['ma_id']) { 
	alert("지역정보를 확인할 수 없습니다.");
}

$ma_parent_info = array();
$ma_effective_img = trim(isset($ma['ma_img']) ? $ma['ma_img'] : '');
if((int)$ma['ma_parent'] > 0 && (int)$ma['ma_parent'] !== (int)$ma['ma_id']) {
	$ma_parent_info = sql_fetch("select ma_id, ma_name, ma_img from {$g5['map_table']} where ma_id = '".(int)$ma['ma_parent']."'");
	if($ma_effective_img === '' && isset($ma_parent_info['ma_img'])) {
		$ma_effective_img = trim($ma_parent_info['ma_img']);
	}
}

$sql_search = "";
if($s_me_type == 'search') {
	$sql_search .= " and (me_type = '' or me_type = 'search') and action_id = '0' ";
} else if($s_me_type == 'parttime') {
	$sql_search .= " and (me_type = 'parttime' or (me_type != '' and me_type != 'search') or action_id > 0) ";
}
if($s_action_id) $sql_search .= " and action_id = '{$s_action_id}' ";
if($s_me_use !== '') $sql_search .= " and me_use = '".(int)$s_me_use."' ";

$sql_common = " from {$g5['map_event_table']} where ma_id = '{$ma_id}' {$sql_search} ";
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

$listall = '<a href="'.$_SERVER['PHP_SELF'].'?ma_id='.$ma_id.'" class="ov_listall">전체목록</a>';
$action_return_url = './map_event_list.php?ma_id='.$ma_id;

/** 지역 정보 **/
$g5['title'] = "[ ".$ma['ma_name']." ] 지역 이벤트 관리";
include_once ('./admin.head.php');

$colspan = 10;
?>


<div class="map-admin-place">
	<nav class="map-admin-quickmenu" aria-label="MAP 관리 빠른 이동">
		<a href="#map-admin-place-info">기본정보</a>
		<a href="#map-admin-events">MAP 이벤트</a>
		<a href="#map-admin-npc-events">NPC 이벤트</a>
	</nav>
	<details class="map-admin-accordion" id="map-admin-place-info" open>
		<summary>기본 정보</summary>
		<form name="fmapplaceform" id="fmapplaceform" action="./map_place_update.php" method="post">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width:120px;">
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th scope="row">장소명</th>
							<td>
								<input type="text" name="ma_name" value="<?=get_text($ma['ma_name'])?>" class="frm_input required" required>
								<label><input type="checkbox" name="ma_use" value="1" <?=$ma['ma_use'] ? 'checked' : ''?>> 사용</label>
								<label><input type="checkbox" name="ma_start" value="1" <?=$ma['ma_start'] ? 'checked' : ''?>> 시작</label>
								<label><input type="checkbox" name="ma_use_dungeon" value="1" <?=$ma['ma_use_dungeon'] ? 'checked' : ''?>> 던전</label>
								<input type="hidden" name="ma_left" value="<?=get_text($ma['ma_left'])?>">
								<input type="hidden" name="ma_top" value="<?=get_text($ma['ma_top'])?>">
								<input type="hidden" name="ma_width" value="<?=get_text($ma['ma_width'])?>">
								<input type="hidden" name="ma_height" value="<?=get_text($ma['ma_height'])?>">
							</td>
						</tr>
						<tr>
							<th scope="row">지역 이미지</th>
							<td>
								<div style="display:block; position:relative; width:360px; max-width:100%; height:140px; margin-bottom:8px; border:1px solid #ddd; background:#f7f7f7; overflow:hidden;">
									<? if($ma_effective_img) { ?>
										<img src="<?=get_text($ma_effective_img)?>" alt="" style="display:block; width:100%; height:100%; object-fit:cover;" onerror="this.remove();">
									<? } else { ?>
										<span style="display:flex; align-items:center; justify-content:center; height:100%; color:#888;">등록된 이미지 없음</span>
									<? } ?>
								</div>
								<input type="text" name="ma_img" value="<?=get_text($ma['ma_img'])?>" class="frm_input full" placeholder="지역 이미지 URL">
								<? if((int)$ma['ma_parent'] !== (int)$ma['ma_id']) { ?>
									<?php echo help("하위지역 이미지를 비워두면 상위지역".(isset($ma_parent_info['ma_name']) && $ma_parent_info['ma_name'] ? " [".get_text($ma_parent_info['ma_name'])."]" : "")." 이미지를 자동으로 사용합니다.") ?>
								<? } else { ?>
									<?php echo help("이 상위지역에 속한 하위지역에 개별 이미지가 없으면 이 이미지가 대신 출력됩니다.") ?>
								<? } ?>
							</td>
						</tr>
						<tr>
							<th scope="row">NPC 조우확률</th>
							<td>
								<input type="number" name="ma_npc_chance" value="<?=isset($ma['ma_npc_chance']) ? (int)$ma['ma_npc_chance'] : 15?>" min="0" max="100" step="1" class="frm_input" style="width:90px;"> %
								<?php echo help("이 지역에서 조사할 때 NPC 이벤트를 시도할 확률입니다. 0%면 NPC 조우를 끄고, NPC/스크립트 후보가 없으면 일반 MAP 이벤트 판정으로 넘어갑니다. NPC끼리의 비율은 아래 등장 가중치로 조절합니다.") ?>
							</td>
						</tr>
						<tr>
							<th scope="row">지역 설명</th>
							<td>
								<textarea name="ma_content" class="frm_input full" style="height:90px;"><?=get_text($ma['ma_content'])?></textarea>
								<?php echo help("하위지역 이동 후 이 내용을 줄바꿈 기준으로 한 줄씩 출력합니다. {이름} 변수를 사용할 수 있습니다.") ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="기본 정보 저장" class="btn_submit">
			</div>
		</form>
	</details>

	<details class="map-admin-accordion" id="map-admin-events" open>
		<summary>행동 / 아르바이트 + 맵 발생 이벤트</summary>
<div class="groupWrap">
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
								<th scope="row">타입/행동</th>
								<td colspan="2">
									<select name="me_type" id="me_type">
										<option value="search">조사</option>
										<option value="parttime">아르바이트/외주</option>
									</select>
									<span class="map-action-select-wrap">
									<select name="action_id" id="action_id">
										<? if(!count($action_list)) { ?>
											<option value="0">등록된 행동 없음</option>
										<? } ?>
										<? for($j=0; $j < count($action_list); $j++) { ?>
											<option value="<?=$action_list[$j]['action_id']?>"><?=get_text($action_list[$j]['action_name'])?></option>
										<? } ?>
									</select>
									</span>
									<?php echo help("조사는 이 장소에서 조사했을 때 발생합니다. 아르바이트/외주 결과 이벤트는 연결할 MAP 행동을 선택하세요.") ?>
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
									출현확률
								</th>
								<td colspan="2">
									<?php echo help("1~100 숫자로 입력합니다. 5를 적으면 1d100에서 5 이하가 나올 때만 발생합니다. 100은 항상 발생, 0은 발생하지 않습니다.") ?>
									<input type="hidden" name="me_per_s" value="1">
									<input type="text" name="me_per_e" value="<?php echo isset($me['me_per_e']) && $me['me_per_e'] !== '' ? $me['me_per_e'] : 100; ?>" id="me_per_e" size="5" maxlength="3"> %
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
									<input type="text" name="me_get_money" value="<?=$me['me_get_money']?>" style="width:100px;"/> <?=$config['cf_money_pice']?>
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

		<form name="fmapactionform" id="fmapactionform" action="./map_action_list_update.php" method="post">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="return_url" value="<?php echo $action_return_url ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">

			<section id="anc_action">
				<h2 class="h2_frm">MAP 행동 추가</h2>
				<div class="tbl_frm01 tbl_wrap">
					<table>
						<colgroup>
							<col style="width: 100px;">
							<col>
						</colgroup>
						<tbody>
							<tr>
								<th scope="row">행동명</th>
								<td>
									<input type="hidden" name="action_type" value="parttime">
									<input type="text" name="action_name" value="" class="frm_input" style="width:180px;" />
									<input type="checkbox" name="action_use" id="new_action_use" value="1" checked />
									<label for="new_action_use">사용</label>
								</td>
							</tr>
							<tr>
								<th scope="row">설명</th>
								<td><textarea name="action_desc" class="frm_input full" style="height:55px;"></textarea></td>
							</tr>
							<tr>
								<th scope="row">시간</th>
								<td>
									<input type="checkbox" name="is_timed" id="new_is_timed" value="1" />
									<label for="new_is_timed">방치형</label>
									<input type="text" name="duration_minutes" value="0" class="frm_input" style="width:70px;" /> 분
									&nbsp;
									<input type="checkbox" name="available_time_use" id="new_available_time_use" value="1" />
									<label for="new_available_time_use">시간 제한</label>
									<input type="text" name="available_start" value="00:00" class="frm_input" style="width:65px;" />
									~
									<input type="text" name="available_end" value="24:00" class="frm_input" style="width:65px;" />
									<input type="hidden" name="action_order" value="0">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</section>

			<div class="btn_confirm01 btn_confirm">
				<input type="submit" value="행동 추가" class="btn_submit">
			</div>
		</form>

		<form name="fmapactionlist" id="fmapactionlist" method="post" action="./map_action_list_update.php" onsubmit="return fmapactionlist_submit(this);">
			<input type="hidden" name="ma_id_filter" value="<?php echo $ma_id ?>">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="s_action_type" value="">
			<input type="hidden" name="return_url" value="<?php echo $action_return_url ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<h2 class="h2_frm">MAP 행동 목록</h2>
			<div class="tbl_head01 tbl_wrap">
				<table style="table-layout:fixed;">
					<colgroup>
						<col style="width:35px;">
						<col style="width:120px;">
						<col>
						<col style="width:55px;">
						<col style="width:62px;">
						<col style="width:55px;">
						<col style="width:65px;">
						<col style="width:65px;">
						<col style="width:45px;">
					</colgroup>
					<thead>
						<tr>
							<th scope="col"><input type="checkbox" onclick="check_all(this.form)"></th>
							<th scope="col">행동명</th>
							<th scope="col">설명</th>
							<th scope="col">방치</th>
							<th scope="col">분</th>
							<th scope="col">제한</th>
							<th scope="col">시작</th>
							<th scope="col">종료</th>
							<th scope="col">사용</th>
						</tr>
					</thead>
					<tbody>
						<? for($i=0; $i < count($action_list); $i++) {
							$action = $action_list[$i];
						?>
						<tr>
							<td>
								<input type="hidden" name="action_id[<?=$i?>]" value="<?=$action['action_id']?>">
								<input type="hidden" name="action_type[<?=$i?>]" value="parttime">
								<input type="hidden" name="action_order[<?=$i?>]" value="<?=$action['action_order']?>">
								<input type="checkbox" name="chk[]" value="<?=$i?>">
							</td>
							<td><input type="text" name="action_name[<?=$i?>]" value="<?=get_text($action['action_name'])?>" class="frm_input full"></td>
							<td><input type="text" name="action_desc[<?=$i?>]" value="<?=get_text($action['action_desc'])?>" class="frm_input full"></td>
							<td><input type="checkbox" name="is_timed[<?=$i?>]" value="1" <?=$action['is_timed'] ? 'checked' : ''?>></td>
							<td><input type="text" name="duration_minutes[<?=$i?>]" value="<?=$action['duration_minutes']?>" class="frm_input full"></td>
							<td><input type="checkbox" name="available_time_use[<?=$i?>]" value="1" <?=$action['available_time_use'] ? 'checked' : ''?>></td>
							<td><input type="text" name="available_start[<?=$i?>]" value="<?=$action['available_start']?>" class="frm_input full"></td>
							<td><input type="text" name="available_end[<?=$i?>]" value="<?=$action['available_end']?>" class="frm_input full"></td>
							<td><input type="checkbox" name="action_use[<?=$i?>]" value="1" <?=$action['action_use'] ? 'checked' : ''?>></td>
						</tr>
						<? } ?>
						<? if(!count($action_list)) { ?>
							<tr><td colspan="9" class="empty_table">등록된 MAP 행동이 없습니다.</td></tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="btn_list01 btn_list">
				<input type="submit" name="act_button" value="선택수정" onclick="document.action_pressed=this.value">
				<input type="submit" name="act_button" value="선택삭제" onclick="document.action_pressed=this.value">
			</div>
		</form>
	</div>
	<div>
		<form method="get" class="local_sch01 local_sch">
			<input type="hidden" name="ma_id" value="<?=$ma_id?>">
			<label for="s_me_type">타입</label>
			<select name="s_me_type" id="s_me_type">
				<option value="">전체</option>
				<option value="search" <?=$s_me_type == 'search' ? 'selected' : ''?>>조사</option>
				<? foreach($action_types as $key => $label) { ?>
					<option value="<?=$key?>" <?=$s_me_type == $key ? 'selected' : ''?>><?=$label?></option>
				<? } ?>
			</select>
			<label for="s_action_id">행동</label>
			<select name="s_action_id" id="s_action_id">
				<option value="0">전체</option>
				<? for($j=0; $j < count($action_list); $j++) { ?>
					<option value="<?=$action_list[$j]['action_id']?>" <?=$s_action_id == $action_list[$j]['action_id'] ? 'selected' : ''?>><?=get_text($action_list[$j]['action_name'])?></option>
				<? } ?>
			</select>
			<label for="s_me_use">사용</label>
			<select name="s_me_use" id="s_me_use">
				<option value="" <?=$s_me_use === '' ? 'selected' : ''?>>전체</option>
				<option value="1" <?=$s_me_use === '1' ? 'selected' : ''?>>사용</option>
				<option value="0" <?=$s_me_use === '0' ? 'selected' : ''?>>미사용</option>
			</select>
			<input type="submit" value="검색" class="btn_submit">
		</form>
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
			<input type="hidden" name="s_me_type" value="<?php echo $s_me_type ?>">
			<input type="hidden" name="s_action_id" value="<?php echo $s_action_id ?>">
			<input type="hidden" name="s_me_use" value="<?php echo $s_me_use ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_head01 tbl_wrap">
				<table style="table-layout:fixed;">
					<caption><?php echo $g5['title']; ?> 목록</caption>
					<colgroup>
						<col style="width: 45px" />
						<col style="width: 45px" />
						<col />
						<col style="width: 90px" />
						<col style="width: 130px" />
						<col style="width: 70px;"/>

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
							<th scope="col">타입</th>
							<th scope="col">행동</th>
							<th scope="col">출현확률(%)</th>
							<th scope="col" colspan="3">획득현황</th>
							<th scope="col">사용</th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ($i=0; $me=sql_fetch_array($result); $i++) {
							$bg = 'bg'.($i%2);
							$event_type = ($me['me_type'] == 'search' || ($me['me_type'] == '' && !$me['action_id'])) ? 'search' : 'parttime';
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
							<td>
								<select name="me_type[<?php echo $i ?>]" class="full">
									<option value="search" <?=$event_type == 'search' ? 'selected' : ''?>>조사</option>
									<option value="parttime" <?=$event_type == 'parttime' ? 'selected' : ''?>>아르바이트/외주</option>
								</select>
							</td>
							<td>
								<select name="action_id[<?php echo $i ?>]" class="full">
									<option value="0">-</option>
									<? for($j=0; $j < count($action_list); $j++) { ?>
										<option value="<?=$action_list[$j]['action_id']?>" <?=$me['action_id'] == $action_list[$j]['action_id'] ? 'selected' : ''?>><?=get_text($action_list[$j]['action_name'])?></option>
									<? } ?>
								</select>
							</td>

							<td>
								<input type="hidden" name="me_per_s[<?php echo $i ?>]" value="1">
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
		<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;ma_id='.$ma_id.'&amp;s_me_type='.$s_me_type.'&amp;s_action_id='.$s_action_id.'&amp;s_me_use='.$s_me_use.'&amp;page='); ?>
	</div>
</div>
	</details>

	<details class="map-admin-accordion" id="map-admin-npc-events" open>
		<summary>NPC 등장 / 대사 / 선택지 이벤트</summary>

		<form name="fmapnpcadd" id="fmapnpcadd" action="./map_npc_update.php" method="post">
			<input type="hidden" name="mode" value="npc_add">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_frm01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width:120px;">
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th scope="row">NPC 연결</th>
							<td>
								<input type="text" name="npc_name" value="" class="frm_input" list="map_npc_name_list" placeholder="기존 NPC 이름">
								<datalist id="map_npc_name_list">
									<? for($i=0; $i < count($npc_name_options); $i++) { ?>
										<option value="<?=get_text($npc_name_options[$i]['ch_name'])?>"></option>
									<? } ?>
								</datalist>
								등장 가중치 <input type="number" min="0" name="mn_weight" value="1" class="frm_input" style="width:65px;">
								<input type="hidden" name="mn_order" value="0">
								<label><input type="checkbox" name="mn_use" value="1" checked> 사용</label>
								<input type="submit" value="NPC 연결" class="btn_submit">
								<?php echo help("NPC는 여기서 새로 만들지 않습니다. NPC 관리에서 등록된 기존 NPC 이름을 입력해 이 장소에 연결합니다.") ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</form>

		<form name="fmapnpclist" id="fmapnpclist" action="./map_npc_update.php" method="post" onsubmit="return fmapnpclist_submit(this);">
			<input type="hidden" name="mode" value="npc_list">
			<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
			<input type="hidden" name="token" value="<?php echo $token ?>">
			<div class="tbl_head01 tbl_wrap">
				<table>
					<colgroup>
						<col style="width:45px;">
						<col style="width:70px;">
						<col style="width:70px;">
						<col>
						<col style="width:70px;">
						<col style="width:60px;">
					</colgroup>
					<thead>
						<tr>
							<th><input type="checkbox" onclick="check_all(this.form)"></th>
							<th>이미지</th>
							<th>ID</th>
							<th>NPC</th>
							<th>등장 가중치</th>
							<th>사용</th>
						</tr>
					</thead>
					<tbody>
						<? for($i=0; $i < count($place_npc_list); $i++) { $npc = $place_npc_list[$i]; ?>
						<tr>
							<td>
								<input type="hidden" name="mn_id[<?=$i?>]" value="<?=$npc['mn_id']?>">
								<input type="hidden" name="mn_order[<?=$i?>]" value="<?=$npc['mn_order']?>">
								<input type="checkbox" name="chk[]" value="<?=$i?>">
							</td>
							<td>
								<? if($npc['ch_body']) { ?><img src="<?=$npc['ch_body']?>" alt="" style="display:block; width:48px; height:48px; object-fit:contain;"><? } ?>
							</td>
							<td><?=$npc['npc_id']?></td>
							<td><?=get_text($npc['ch_name'])?></td>
							<td>
								<input type="number" min="0" name="mn_weight[<?=$i?>]" value="<?=$npc['mn_weight']?>" class="frm_input full">
							</td>
							<td><input type="checkbox" name="mn_use[<?=$i?>]" value="1" <?=$npc['mn_use'] ? 'checked' : ''?>></td>
						</tr>
						<? } ?>
						<? if(!count($place_npc_list)) { ?>
							<tr><td colspan="6" class="empty_table">연결된 NPC가 없습니다.</td></tr>
						<? } ?>
					</tbody>
				</table>
			</div>
			<div class="btn_list01 btn_list">
				<input type="submit" name="act_button" value="선택수정" onclick="document.npc_pressed=this.value">
				<input type="submit" name="act_button" value="선택삭제" onclick="document.npc_pressed=this.value">
			</div>
		</form>

		<? for($n=0; $n < count($place_npc_list); $n++) {
		$npc = $place_npc_list[$n];
		$script_list = isset($place_npc_script_map[$npc['mn_id']]) ? $place_npc_script_map[$npc['mn_id']] : array();
		$npc_has_open_choice = false;
		for($o=0; $o < count($script_list); $o++) {
			if($open_choice && $open_choice == $script_list[$o]['script_id']) $npc_has_open_choice = true;
		}
		?>
		<details class="map-admin-npc" <?=$npc_has_open_choice ? 'open' : ''?>>
			<summary><?=get_text($npc['ch_name'])?> MAP 콘텐츠</summary>

			<form action="./map_npc_update.php" method="post">
				<input type="hidden" name="mode" value="script_add">
				<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
				<input type="hidden" name="mn_id" value="<?=$npc['mn_id']?>">
				<input type="hidden" name="token" value="<?php echo $token ?>">
				<div class="tbl_frm01 tbl_wrap">
					<table>
						<colgroup>
							<col style="width:120px;">
							<col>
						</colgroup>
						<tbody>
							<tr>
								<th scope="row">대사/이벤트 추가</th>
								<td>
									<select name="script_type">
										<option value="dialogue">일반 대사</option>
										<option value="event">선택지 이벤트</option>
									</select>
									<input type="text" name="script_title" value="" class="frm_input" style="width:220px;" placeholder="제목">
									가중치 <input type="number" min="0" name="script_weight" value="1" class="frm_input" style="width:55px;">
									호감도 <input type="number" name="script_favor_s" value="" class="frm_input" style="width:55px;" placeholder="최소"> ~
									<input type="number" name="script_favor_e" value="" class="frm_input" style="width:55px;" placeholder="최대">
									변화 <input type="text" name="script_favor_value" value="0" class="frm_input" style="width:55px;">
									반복 <select name="script_repeat_type"><option value="always">항상</option><option value="once">1회</option><option value="daily">매일</option><option value="cooldown">쿨다운</option></select>
									초 <input type="number" min="0" name="script_cooldown" value="0" class="frm_input" style="width:65px;">
									선행 <select name="script_require_script_id"><option value="">없음</option><?=map_script_require_options($map_script_require_groups)?></select>
									<input type="hidden" name="script_order" value="0">
									<label><input type="checkbox" name="script_use" value="1" checked> 사용</label>
								</td>
							</tr>
							<tr>
								<th scope="row">내용</th>
								<td>
									<div class="map-script-editor-wrap">
										<div class="map-script-tools">
											<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
											<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
										</div>
										<textarea name="script_content" class="frm_input full map-script-editor" style="height:90px;"></textarea>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div class="btn_confirm01 btn_confirm">
					<input type="submit" value="대사/이벤트 추가" class="btn_submit">
				</div>
			</form>

			<form action="./map_npc_update.php" method="post" onsubmit="return fmapnpcscript_submit(this);">
				<input type="hidden" name="mode" value="script_list">
				<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
				<input type="hidden" name="mn_id" value="<?=$npc['mn_id']?>">
				<input type="hidden" name="token" value="<?php echo $token ?>">
				<input type="hidden" name="open_choice" value="<?=$open_choice?>">
				<div class="tbl_head01 tbl_wrap">
					<table>
						<colgroup>
							<col style="width:45px;">
							<col style="width:90px;">
							<col style="width:180px;">
							<col>
							<col style="width:70px;">
							<col style="width:60px;">
							<col style="width:60px;">
							<col style="width:60px;">
							<col style="width:50px;">
						</colgroup>
						<thead>
							<tr>
								<th><input type="checkbox" onclick="check_all(this.form)"></th>
								<th>타입</th>
								<th>제목</th>
								<th>내용</th>
								<th>등장 가중치</th>
								<th>호감 시작</th>
								<th>호감 종료</th>
								<th>호감 변화</th>
								<th>사용</th>
							</tr>
						</thead>
						<tbody>
							<? for($s=0; $s < count($script_list); $s++) { $script = $script_list[$s]; ?>
							<tr>
								<td>
									<input type="hidden" name="script_id[<?=$s?>]" value="<?=$script['script_id']?>">
									<input type="hidden" name="script_order[<?=$s?>]" value="<?=$script['script_order']?>">
									<input type="checkbox" name="chk[]" value="<?=$s?>">
								</td>
								<td>
									<select name="script_type[<?=$s?>]" class="full">
										<option value="dialogue" <?=$script['script_type'] == 'dialogue' ? 'selected' : ''?>>일반</option>
										<option value="event" <?=$script['script_type'] == 'event' ? 'selected' : ''?>>선택지</option>
									</select>
								</td>
								<td><input type="text" name="script_title[<?=$s?>]" value="<?=get_text($script['script_title'])?>" class="frm_input full"><select name="script_require_script_id[<?=$s?>]" class="full"><option value="">선행 없음</option><?=map_script_require_options($map_script_require_groups, $script['script_require_script_id'], $script['script_id'])?></select></td>
								<td>
									<div class="map-script-editor-wrap">
										<div class="map-script-tools">
											<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
											<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
										</div>
										<textarea name="script_content[<?=$s?>]" class="frm_input full map-script-editor" style="height:70px;"><?=get_text($script['script_content'])?></textarea>
									</div>
								</td>
								<td>
									<input type="number" min="0" name="script_weight[<?=$s?>]" value="<?=$script['script_weight']?>" class="frm_input full">
									<select name="script_repeat_type[<?=$s?>]" class="full"><option value="always" <?=$script['script_repeat_type']=='always'?'selected':''?>>항상</option><option value="once" <?=$script['script_repeat_type']=='once'?'selected':''?>>1회</option><option value="daily" <?=$script['script_repeat_type']=='daily'?'selected':''?>>매일</option><option value="cooldown" <?=$script['script_repeat_type']=='cooldown'?'selected':''?>>쿨다운</option></select>
									<input type="number" min="0" name="script_cooldown[<?=$s?>]" value="<?=$script['script_cooldown']?>" class="frm_input full" placeholder="초">
								</td>
								<td><input type="text" name="script_favor_s[<?=$s?>]" value="<?=$script['script_favor_s']?>" class="frm_input full"></td>
								<td><input type="text" name="script_favor_e[<?=$s?>]" value="<?=$script['script_favor_e']?>" class="frm_input full"></td>
								<td><input type="text" name="script_favor_value[<?=$s?>]" value="<?=$script['script_favor_value']?>" class="frm_input full"></td>
								<td><input type="checkbox" name="script_use[<?=$s?>]" value="1" <?=$script['script_use'] ? 'checked' : ''?>></td>
							</tr>
							<? } ?>
							<? if(!count($script_list)) { ?><tr><td colspan="9" class="empty_table">등록된 대사/이벤트가 없습니다.</td></tr><? } ?>
						</tbody>
					</table>
				</div>
				<div class="btn_list01 btn_list">
					<input type="submit" name="act_button" value="선택수정" onclick="document.script_pressed=this.value">
					<input type="submit" name="act_button" value="선택삭제" onclick="document.script_pressed=this.value">
				</div>
			</form>
			<? for($s=0; $s < count($script_list); $s++) {
				$script = $script_list[$s];
				if($script['script_type'] != 'event') continue;
				$choice_list = isset($map_choice_map[$script['script_id']]) ? $map_choice_map[$script['script_id']] : array();
			?>
			<details class="map-admin-choice" <?=$open_choice && $open_choice == $script['script_id'] ? 'open' : ''?>>
				<summary><?=get_text($script['script_title'])?> 선택지 관리</summary>
				<form action="./map_npc_update.php" method="post" class="map-choice-editor" onsubmit="return fmapnpcchoice_add_submit(this);">
					<input type="hidden" name="mode" value="choice_add">
					<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
					<input type="hidden" name="script_id" value="<?=$script['script_id']?>">
					<input type="hidden" name="token" value="<?php echo $token ?>">
					<div class="map-choice-main-row">
						<input type="text" name="choice_text" value="" class="frm_input" style="width:220px;" placeholder="플레이어에게 보일 선택지 문구">
						<span class="map-choice-reward-label">결과 보상</span>
						돈 <input type="text" name="choice_get_money" value="0" class="frm_input" style="width:80px;">
						아이템 ID <input type="text" name="choice_get_item" value="0" list="map_item_id_list" class="frm_input" style="width:110px;" placeholder="0=없음">
						호감도 <input type="text" name="choice_favor_value" value="0" class="frm_input" style="width:70px;">
						<input type="hidden" name="choice_order" value="0">
						<label><input type="checkbox" name="choice_use" value="1" checked> 사용</label>
					</div>
					<div class="map-choice-condition-switch">
						<label><input type="checkbox" name="choice_condition_use" value="1" class="map-choice-condition-toggle"> <strong>성공조건 사용</strong></label>
						<span>체크하지 않으면 아래 결과 스크립트 1개만 사용합니다. 체크하면 조건 실패용 스크립트와 보상을 추가로 설정할 수 있습니다.</span>
					</div>
					<div class="map-choice-result-title map-choice-primary-result-title">결과 스크립트</div>
					<div class="map-script-editor-wrap">
						<div class="map-script-tools">
							<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
							<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
							<button type="button" class="map-script-prefix" data-prefix="[NPC:이름]">NPC</button>
						</div>
						<textarea name="choice_result" class="frm_input full map-script-editor" style="height:70px;" placeholder="선택 후 출력할 결과 스크립트"></textarea>
					</div>
					<div class="map-choice-condition-panel" style="display:none;">
						<div class="map-choice-condition-fields">
							<strong>성공 조건</strong>
							<select name="choice_check_type" class="map-choice-condition-type"><option value="">조건 종류 선택</option><option value="stat">스탯</option><option value="favor">호감도</option><option value="money">돈</option><option value="item">아이템</option></select>
							<input type="text" name="choice_check_target" value="" class="frm_input map-choice-condition-target" style="width:130px;" placeholder="조건 종류를 선택">
							<select name="choice_check_operator"><option value=">=">&gt;=</option><option value=">">&gt;</option><option value="<=">&lt;=</option><option value="<">&lt;</option><option value="=">=</option><option value="!=">!=</option></select>
							<input type="number" name="choice_check_value" value="" class="frm_input" style="width:70px;" placeholder="기준값">
						</div>
						<div class="map-choice-result-title">조건 실패 시 스크립트</div>
						<div class="map-script-editor-wrap">
							<div class="map-script-tools">
								<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
								<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
								<button type="button" class="map-script-prefix" data-prefix="[NPC:이름]">NPC</button>
							</div>
							<textarea name="choice_fail_result" class="frm_input full map-script-editor" style="height:70px;" placeholder="조건을 만족하지 못했을 때 출력할 스크립트"></textarea>
						</div>
						<div class="map-choice-fail-rewards">
							<strong>실패 보상</strong>
							돈 <input type="text" name="choice_fail_get_money" value="0" class="frm_input" style="width:80px;">
							아이템 ID <input type="text" name="choice_fail_get_item" value="0" list="map_item_id_list" class="frm_input" style="width:110px;" placeholder="0=없음">
							호감도 <input type="text" name="choice_fail_favor_value" value="0" class="frm_input" style="width:70px;">
						</div>
					</div>
					<div class="btn_confirm01 btn_confirm"><input type="submit" value="선택지 추가" class="btn_submit"></div>
				</form>

				<form action="./map_npc_update.php" method="post" onsubmit="return fmapnpcchoice_submit(this);">
					<input type="hidden" name="mode" value="choice_list">
					<input type="hidden" name="ma_id" value="<?php echo $ma_id ?>">
					<input type="hidden" name="script_id" value="<?=$script['script_id']?>">
					<input type="hidden" name="token" value="<?php echo $token ?>">
					<div class="tbl_head01 tbl_wrap">
						<table>
						<colgroup>
							<col style="width:35px;">
							<col style="width:180px;">
							<col>
							<col style="width:45px;">
						</colgroup>
						<thead>
							<tr>
								<th><input type="checkbox" onclick="check_all(this.form)"></th>
								<th>선택 문구</th>
								<th>결과</th>
								<th>사용</th>
							</tr>
						</thead>
							<tbody>
								<? for($c=0; $c < count($choice_list); $c++) { $choice = $choice_list[$c]; $condition_enabled = !empty($choice['choice_check_type']); ?>
								<tr class="map-choice-row">
									<td>
										<input type="hidden" name="choice_id[<?=$c?>]" value="<?=$choice['choice_id']?>">
										<input type="hidden" name="choice_order[<?=$c?>]" value="<?=$choice['choice_order']?>">
										<input type="checkbox" name="chk[]" value="<?=$c?>">
									</td>
									<td><input type="text" name="choice_text[<?=$c?>]" value="<?=get_text($choice['choice_text'])?>" class="frm_input full"></td>
									<td>
										<div class="map-choice-condition-switch compact">
											<label><input type="checkbox" name="choice_condition_use[<?=$c?>]" value="1" class="map-choice-condition-toggle" <?=$condition_enabled ? 'checked' : ''?>> <strong>성공조건 사용</strong></label>
										</div>
										<div class="map-choice-result-title map-choice-primary-result-title">결과 스크립트</div>
										<div class="map-script-editor-wrap">
											<div class="map-script-tools">
												<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
												<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
												<button type="button" class="map-script-prefix" data-prefix="[NPC:이름]">NPC</button>
											</div>
											<textarea name="choice_result[<?=$c?>]" class="frm_input full map-script-editor" style="height:55px;"><?=get_text($choice['choice_result'])?></textarea>
											<div class="map-choice-success-rewards">
												<strong>보상</strong>
												돈 <input type="text" name="choice_get_money[<?=$c?>]" value="<?=$choice['choice_get_money']?>" class="frm_input" style="width:70px;">
												아이템 ID <input type="text" name="choice_get_item[<?=$c?>]" value="<?=(int)$choice['choice_get_item']?>" list="map_item_id_list" class="frm_input" style="width:100px;">
												호감도 <input type="text" name="choice_favor_value[<?=$c?>]" value="<?=$choice['choice_favor_value']?>" class="frm_input" style="width:70px;">
											</div>
										</div>
										<div class="map-choice-condition-panel" <?=$condition_enabled ? '' : 'style="display:none;"'?>>
											<div class="map-choice-condition-fields">
												<strong>성공 조건</strong>
												<select name="choice_check_type[<?=$c?>]" class="map-choice-condition-type"><option value="" <?=$choice['choice_check_type']?'':'selected'?>>조건 종류 선택</option><option value="stat" <?=$choice['choice_check_type']=='stat'?'selected':''?>>스탯</option><option value="favor" <?=$choice['choice_check_type']=='favor'?'selected':''?>>호감도</option><option value="money" <?=$choice['choice_check_type']=='money'?'selected':''?>>돈</option><option value="item" <?=$choice['choice_check_type']=='item'?'selected':''?>>아이템</option></select>
												<input type="text" name="choice_check_target[<?=$c?>]" value="<?=get_text($choice['choice_check_target'])?>" class="frm_input map-choice-condition-target" style="width:110px;" placeholder="대상">
												<select name="choice_check_operator[<?=$c?>]"><option value=">=" <?=$choice['choice_check_operator']=='>='?'selected':''?>>&gt;=</option><option value=">" <?=$choice['choice_check_operator']=='>'?'selected':''?>>&gt;</option><option value="<=" <?=$choice['choice_check_operator']=='<='?'selected':''?>>&lt;=</option><option value="<" <?=$choice['choice_check_operator']=='<'?'selected':''?>>&lt;</option><option value="=" <?=$choice['choice_check_operator']=='='?'selected':''?>>=</option><option value="!=" <?=$choice['choice_check_operator']=='!='?'selected':''?>>!=</option></select>
												<input type="number" name="choice_check_value[<?=$c?>]" value="<?=$choice['choice_check_value']?>" class="frm_input" style="width:60px;" placeholder="값">
											</div>
											<div class="map-choice-result-title">조건 실패 시 스크립트</div>
											<div class="map-script-editor-wrap">
												<div class="map-script-tools">
													<button type="button" class="map-script-prefix" data-prefix="[대사]">대사</button>
													<button type="button" class="map-script-prefix" data-prefix="[나레이션]">나레이션</button>
													<button type="button" class="map-script-prefix" data-prefix="[NPC:이름]">NPC</button>
												</div>
												<textarea name="choice_fail_result[<?=$c?>]" class="frm_input full map-script-editor" style="height:55px;" placeholder="조건 실패 시 출력할 스크립트"><?=get_text($choice['choice_fail_result'])?></textarea>
											</div>
											<div class="map-choice-fail-rewards">
												<strong>실패 보상</strong>
												돈 <input type="text" name="choice_fail_get_money[<?=$c?>]" value="<?=$choice['choice_fail_get_money']?>" class="frm_input" style="width:70px;">
												아이템 ID <input type="text" name="choice_fail_get_item[<?=$c?>]" value="<?=(int)$choice['choice_fail_get_item']?>" list="map_item_id_list" class="frm_input" style="width:100px;">
												호감도 <input type="text" name="choice_fail_favor_value[<?=$c?>]" value="<?=$choice['choice_fail_favor_value']?>" class="frm_input" style="width:70px;">
											</div>
										</div>
									</td>
									<td><input type="checkbox" name="choice_use[<?=$c?>]" value="1" <?=$choice['choice_use'] ? 'checked' : ''?>></td>
								</tr>
								<? } ?>
								<? if(!count($choice_list)) { ?><tr><td colspan="4" class="empty_table">선택지가 없습니다.</td></tr><? } ?>
							</tbody>
						</table>
					</div>
					<div class="btn_list01 btn_list">
						<input type="submit" name="act_button" value="선택수정" onclick="document.choice_pressed=this.value">
						<input type="submit" name="act_button" value="선택삭제" onclick="document.choice_pressed=this.value">
					</div>
				</form>
			</details>
			<? } ?>
		</details>
		<? } ?>
	</details>
</div>

<datalist id="map_stat_target_list">
	<? for($i=0; $i < count($map_stat_options); $i++) { ?>
		<option value="<?=get_text($map_stat_options[$i])?>"></option>
	<? } ?>
</datalist>
<datalist id="map_npc_id_list">
	<? for($i=0; $i < count($npc_name_options); $i++) { ?>
		<option value="<?=(int)$npc_name_options[$i]['ch_id']?>" label="<?=get_text($npc_name_options[$i]['ch_name'])?>"></option>
	<? } ?>
</datalist>
<datalist id="map_item_id_list">
	<? for($i=0; $i < count($map_item_options); $i++) { ?>
		<option value="<?=(int)$map_item_options[$i]['it_id']?>" label="<?=get_text($map_item_options[$i]['it_name'])?>"></option>
	<? } ?>
</datalist>

<style>
.map-admin-place {display:block; position:relative; max-width:100%;}
.map-admin-quickmenu {position:sticky; top:8px; z-index:10; display:flex; width:max-content; max-width:100%; margin:0 0 8px auto; overflow-x:auto; border:1px solid #d9dfe8; border-radius:5px; background:rgba(255,255,255,.96); box-shadow:0 2px 8px rgba(28,35,52,.08);}
.map-admin-quickmenu a {display:block; flex:0 0 auto; padding:7px 10px; border-left:1px solid #e5e9f0; color:#3b4b63; font-size:12px; font-weight:700; line-height:1.3; text-decoration:none; white-space:nowrap;}
.map-admin-quickmenu a:first-child {border-left:0;}
.map-admin-quickmenu a:hover,
.map-admin-quickmenu a:focus {background:#f1f5fa; color:#1e5c9e; text-decoration:none;}
#map-admin-place-info,
#map-admin-events,
#map-admin-npc-events {scroll-margin-top:52px;}
.map-admin-accordion,
.map-admin-npc,
.map-admin-choice {display:block; margin:16px 0; overflow:hidden; border:1px solid #d9d9df; border-radius:8px; background:#fff; box-shadow:0 3px 12px rgba(28,35,52,.06);}
.map-admin-accordion > summary,
.map-admin-npc > summary,
.map-admin-choice > summary {display:block; padding:14px 16px; background:linear-gradient(135deg, #f8f9fc, #eef2f8); color:#26344a; font-weight:800; cursor:pointer; transition:background .18s ease;}
.map-admin-choice > summary:hover {background:linear-gradient(135deg, #f2f6ff, #e8eef9);}
.map-admin-accordion > summary::-webkit-details-marker,
.map-admin-npc > summary::-webkit-details-marker,
.map-admin-choice > summary::-webkit-details-marker {display:none;}
.map-admin-accordion > summary:before,
.map-admin-npc > summary:before,
.map-admin-choice > summary:before {content:"▶"; display:inline-block; width:18px;}
.map-admin-accordion[open] > summary:before,
.map-admin-npc[open] > summary:before,
.map-admin-choice[open] > summary:before {content:"▼";}
.map-admin-accordion > *:not(summary),
.map-admin-npc > *:not(summary),
.map-admin-choice > *:not(summary) {margin:14px 16px;}
.map-admin-choice > form {padding:14px; border:1px solid #e6eaf0; border-radius:6px; background:#fbfcfe;}
.map-admin-choice > form + form {margin-top:16px; padding-top:16px; border-top:2px solid #dfe6f1;}
.map-admin-choice p {display:flex; flex-wrap:wrap; align-items:center; gap:7px 9px; margin:0 0 10px; color:#536176; font-size:12px;}
.map-admin-choice .map-script-editor-wrap {padding:10px; border:1px solid #dce3ee; border-radius:6px; background:#fff;}
.map-admin-choice textarea {border-color:#d7dfeb; background:#fff;}
.map-choice-main-row,
.map-choice-condition-fields,
.map-choice-success-rewards,
.map-choice-fail-rewards {display:flex; flex-wrap:wrap; align-items:center; gap:7px 9px; margin:0 0 10px; color:#536176; font-size:12px;}
.map-choice-reward-label {font-weight:700; color:#39485f;}
.map-choice-condition-switch {display:flex; align-items:center; gap:10px; margin:10px 0; padding:9px 11px; border:1px solid #d9e1ec; border-radius:6px; background:#f7f9fc; color:#5b687b; font-size:12px;}
.map-choice-condition-switch label {color:#34435a; cursor:pointer;}
.map-choice-condition-switch.compact {display:block; margin:0 0 7px; padding:6px 8px;}
.map-choice-condition-panel {margin-top:10px; padding:11px; border:1px solid #dbe3ee; border-radius:7px; background:#f8fafc;}
.map-choice-condition-panel .map-script-editor-wrap {background:#fff;}
.map-choice-result-title {margin:7px 0 5px; color:#3d4b61; font-size:12px; font-weight:700;}
.map-choice-condition-fields {margin-bottom:10px;}
.map-choice-success-rewards,
.map-choice-fail-rewards {margin:10px 0 0;}
.map-admin-choice .tbl_head01 {overflow:visible; border:1px solid #dce3ee; border-radius:6px; background:#fff;}
.map-admin-choice .tbl_head01 thead th {background:#f1f5fa; color:#43526a;}
.map-admin-choice .tbl_head01 tbody tr:hover td {background:#fafcff;}
.map-admin-choice .btn_confirm,
.map-admin-choice .btn_list {margin:12px 0 0; padding:0; text-align:right;}
.map-admin-choice .btn_submit {min-width:104px; border-radius:4px;}
.groupWrap {display:flow-root; position:relative; min-width:0 !important; overflow:visible;}
.groupWrap > * {display:block; position:relative; width:40%; box-sizing:border-box; float:left;}
.groupWrap > * + * {float:right; width:58%;}
.groupWrap .form-area {padding:30px 0 0;}
.groupWrap .form-area .btn_confirm {padding:0;}
.groupWrap table {table-layout:fixed;}
.map-admin-place .tbl_wrap {max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;}
.map-admin-place .btn_confirm,
.map-admin-place .btn_list {display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:6px; min-height:34px;}
.map-admin-place .btn_list #bo_add {margin-left:auto; float:none !important;}
@media (max-width:1200px) {
	.groupWrap > *,
	.groupWrap > * + * {float:none; width:100%;}
	.groupWrap .form-area {padding-top:0;}
}
@media (max-width:700px) {
	.map-admin-quickmenu {width:100%; margin-right:0;}
	.map-admin-quickmenu a {flex:1 0 auto; text-align:center;}
	.map-admin-accordion > *:not(summary),
	.map-admin-npc > *:not(summary),
	.map-admin-choice > *:not(summary) {margin-left:10px; margin-right:10px;}
}
@media (max-height:600px) {
	.map-admin-quickmenu {display:none;}
}
.map-script-editor-wrap {display:block;}
.map-script-tools {display:block; margin:0 0 4px; text-align:left;}
.map-script-tools button {display:inline-block; min-width:58px; margin:0 3px 3px 0; padding:4px 8px; border:1px solid #bbb; background:#fff; color:#333; font-size:12px; line-height:1.3em; cursor:pointer;}
.map-script-tools button:hover {background:#f1f1f1;}
.map-script-npc-picker {display:inline-block; max-width:220px; height:27px; margin:0 3px 3px 0; vertical-align:top;}
.map-stat-condition-control {display:inline-flex; gap:4px; align-items:center; vertical-align:middle;}
.map-stat-condition-control select {height:27px; max-width:120px;}
.map-stat-condition-control input {width:58px !important; height:27px; box-sizing:border-box;}
</style>

<script>
var mapAdminNpcOptions = <?=json_encode($npc_name_options, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
var mapAdminPlaceNpcIds = <?=json_encode(array_keys($map_npc_ids))?>;

function map_update_choice_condition_target(typeSelect) {
	var $type = $(typeSelect);
	var $scope = $type.closest('p, div');
	var $target = $scope.find('.map-choice-condition-target').first();
	if(!$target.length) return;

	var type = $type.val();
	$target.prop('disabled', false).removeAttr('list');
	if(type == 'stat') {
		$target.attr('list', 'map_stat_target_list').attr('placeholder', '스탯 선택');
	} else if(type == 'favor') {
		$target.attr('list', 'map_npc_id_list').attr('placeholder', 'NPC ID 선택');
	} else if(type == 'item') {
		$target.attr('list', 'map_item_id_list').attr('placeholder', '아이템 ID 선택');
	} else {
		$target.val('').prop('disabled', true).attr('placeholder', type == 'money' ? '대상 없음' : '조건 없음');
	}
}

$(document).on('change', '.map-choice-condition-type', function() {
	map_update_choice_condition_target(this);
});

function map_toggle_choice_condition(toggle) {
	var $toggle = $(toggle);
	var $scope = $toggle.closest('.map-choice-editor, .map-choice-row');
	var $panel = $scope.find('.map-choice-condition-panel').first();
	var enabled = $toggle.is(':checked');
	if(!$panel.length) return;

	$panel.toggle(enabled);
	$panel.find(':input').prop('disabled', !enabled);
	$scope.find('.map-choice-primary-result-title').first().text(enabled ? '조건 성공 시 스크립트' : '결과 스크립트');
	if(enabled) {
		$panel.find('.map-choice-condition-type').each(function() { map_update_choice_condition_target(this); });
	}
}

$(document).on('change', '.map-choice-condition-toggle', function() {
	map_toggle_choice_condition(this);
});

function map_insert_script_prefix(textarea, prefix) {
	var el = textarea;
	var value = el.value;
	var start = el.selectionStart || 0;
	var end = el.selectionEnd || start;
	var before = value.substring(0, start);
	var after = value.substring(end);
	var lineStart = before.lastIndexOf('\n') + 1;
	var lineEndOffset = after.indexOf('\n');
	var lineEnd = lineEndOffset >= 0 ? end + lineEndOffset : value.length;
	var line = value.substring(lineStart, lineEnd);
	var cleanLine = line.replace(/^\[(대사|나레이션|dialogue|narration)\]\s*/i, '').replace(/^\[NPC\s*:\s*[^\]]+\]\s*/i, '');
	var nextLine = prefix + ' ' + cleanLine;

	el.value = value.substring(0, lineStart) + nextLine + value.substring(lineEnd);
	el.focus();
	el.selectionStart = el.selectionEnd = lineStart + nextLine.length;
}

$(document).on('click', '.map-script-prefix', function() {
	var $wrap = $(this).closest('.map-script-editor-wrap');
	var $textarea = $wrap.find('textarea.map-script-editor').first();
	if(!$textarea.length) return;
	map_insert_script_prefix($textarea[0], $(this).data('prefix'));
});

function map_add_npc_pickers() {
	$('.map-script-tools').each(function() {
		var $tools = $(this);
		if($tools.find('.map-script-npc-picker').length) return;
		var $picker = $('<select>', {'class': 'map-script-npc-picker', 'aria-label': 'NPC 선택'});
		$picker.append($('<option>', {value: ''}).text('NPC 선택…'));
		$.each(mapAdminNpcOptions, function(_, npc) {
			var isPlaceNpc = $.inArray(String(npc.ch_id), $.map(mapAdminPlaceNpcIds, String)) !== -1;
			$picker.append($('<option>', {value: npc.ch_id + '|' + npc.ch_name}).text((isPlaceNpc ? '★ ' : '') + npc.ch_name + ' (#' + npc.ch_id + ')'));
		});
		$tools.append($picker);
	});
}

$(document).on('change', '.map-script-npc-picker', function() {
	var value = $(this).val();
	if(!value) return;
	var $textarea = $(this).closest('.map-script-editor-wrap').find('textarea.map-script-editor').first();
	if($textarea.length) map_insert_script_prefix($textarea[0], '[NPC:' + value + ']');
	this.selectedIndex = 0;
});

$(function() {
	map_add_npc_pickers();
	$('.map-choice-condition-toggle').each(function() { map_toggle_choice_condition(this); });
});

function map_toggle_event_action() {
	if($('#me_type').val() == 'parttime') {
		$('.map-action-select-wrap').show();
	} else {
		$('.map-action-select-wrap').hide();
	}
}

$('#me_type').on('change', map_toggle_event_action);
map_toggle_event_action();

function fshopform_submit(f) {
	if(f.me_type.value == 'parttime' && (!f.action_id || !f.action_id.value || f.action_id.value == '0')) {
		alert('아르바이트/외주 이벤트는 연결할 MAP 행동을 선택하세요.');
		return false;
	}
	return true;
}

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

function fmapactionlist_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.action_pressed+" 하실 항목을 하나 이상 선택하세요.");
		return false;
	}
	if(document.action_pressed == "선택삭제") {
		if(!confirm("선택한 MAP 행동을 정말 삭제하시겠습니까? 연결된 이벤트의 행동 연결은 해제됩니다.")) {
			return false;
		}
	}
	return true;
}

function fmapnpclist_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.npc_pressed+" 하실 NPC를 하나 이상 선택하세요.");
		return false;
	}
	if(document.npc_pressed == "선택삭제" && !confirm("선택한 NPC 연결과 MAP 전용 대사/선택지 이벤트를 삭제하시겠습니까?")) {
		return false;
	}
	return true;
}

function fmapnpcscript_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.script_pressed+" 하실 대사/이벤트를 하나 이상 선택하세요.");
		return false;
	}
	if(document.script_pressed == "선택삭제" && !confirm("선택한 대사/이벤트와 연결된 선택지를 삭제하시겠습니까?")) {
		return false;
	}
	return true;
}

function fmapnpcchoice_submit(f) {
	if (!is_checked("chk[]")) {
		alert(document.choice_pressed+" 하실 선택지를 하나 이상 선택하세요.");
		return false;
	}
	if(document.choice_pressed == "선택삭제" && !confirm("선택한 선택지를 삭제하시겠습니까?")) {
		return false;
	}
	if(document.choice_pressed == "선택수정") {
		var valid = true;
		$(f).find('input[name="chk[]"]:checked').each(function() {
			var $row = $(this).closest('.map-choice-row');
			var $toggle = $row.find('.map-choice-condition-toggle');
			if(!$toggle.is(':checked')) return;
			var type = $row.find('.map-choice-condition-type').val();
			var target = $.trim($row.find('.map-choice-condition-target').val() || '');
			var value = $.trim($row.find('input[name^="choice_check_value"]').val() || '');
			var failResult = $.trim($row.find('textarea[name^="choice_fail_result"]').val() || '');
			if(!type) { alert('성공조건 종류를 선택하세요.'); valid = false; return false; }
			if(type != 'money' && !target) { alert('성공조건 대상을 선택하세요.'); valid = false; return false; }
			if(value === '') { alert('성공조건 기준값을 입력하세요.'); valid = false; return false; }
			if(!failResult) { alert('성공조건을 사용할 때는 실패 결과 스크립트를 입력하세요.'); valid = false; return false; }
		});
		if(!valid) return false;
	}
	return true;
}

function fmapnpcchoice_add_submit(f) {
	if(!$.trim($(f).find('[name="choice_text"]').val())) {
		alert('선택지 문구를 입력하세요.');
		return false;
	}
	var $toggle = $(f).find('[name="choice_condition_use"]');
	if($toggle.is(':checked')) {
		var type = $(f).find('[name="choice_check_type"]').val();
		var target = $.trim($(f).find('[name="choice_check_target"]').val() || '');
		var value = $.trim($(f).find('[name="choice_check_value"]').val() || '');
		var failResult = $.trim($(f).find('[name="choice_fail_result"]').val() || '');
		if(!type) {
			alert('성공조건 종류를 선택하세요.');
			return false;
		}
		if(type != 'money' && !target) {
			alert('성공조건 대상을 선택하세요.');
			return false;
		}
		if(value === '') {
			alert('성공조건 기준값을 입력하세요.');
			return false;
		}
		if(!failResult) {
			alert('성공조건을 사용할 때는 실패 결과 스크립트를 입력하세요.');
			return false;
		}
	}
	return true;
}
</script>

<?php
include_once ('./admin.tail.php');
?>
