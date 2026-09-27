<?php
$sub_menu = "710100";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;

function map_admin_condition_targets()
{
	global $g5;
	static $targets = null;
	if($targets !== null) return $targets;

	$targets = array('stat' => array(), 'favor' => array(), 'item' => array());

	$result = sql_query(" select st_name from {$g5['status_config_table']} ", false);
	if($result) for($i=0; $row = sql_fetch_array($result); $i++) $targets['stat'][(string)$row['st_name']] = true;

	$result = sql_query(" select ch_id from {$g5['character_table']} where ch_type = 'npc' ", false);
	if($result) for($i=0; $row = sql_fetch_array($result); $i++) $targets['favor'][(int)$row['ch_id']] = true;

	$result = sql_query(" select it_id from {$g5['item_table']} ", false);
	if($result) for($i=0; $row = sql_fetch_array($result); $i++) $targets['item'][(int)$row['it_id']] = true;

	return $targets;
}

function map_admin_item_id($value)
{
	$value = (int)$value;
	if($value <= 0) return 0;
	$targets = map_admin_condition_targets();
	if(!isset($targets['item'][$value])) alert('등록된 아이템을 선택하세요.');
	return $value;
}

function map_admin_weight($value, $default=1)
{
	if($value === null || $value === '') return $default;
	$value = (int)$value;
	if($value < 0) return 0;
	return $value;
}

function map_admin_condition($prefix, $index=null, $enabled=true)
{
	if(!$enabled) return array('type' => 'NULL', 'target' => 'NULL', 'operator' => 'NULL', 'value' => 'NULL');

	$type = $index === null ? (isset($_POST[$prefix.'type']) ? trim($_POST[$prefix.'type']) : '') : (isset($_POST[$prefix.'type'][$index]) ? trim($_POST[$prefix.'type'][$index]) : '');
	$target = $index === null ? (isset($_POST[$prefix.'target']) ? trim($_POST[$prefix.'target']) : '') : (isset($_POST[$prefix.'target'][$index]) ? trim($_POST[$prefix.'target'][$index]) : '');
	$operator = $index === null ? (isset($_POST[$prefix.'operator']) ? trim($_POST[$prefix.'operator']) : '') : (isset($_POST[$prefix.'operator'][$index]) ? trim($_POST[$prefix.'operator'][$index]) : '');
	$value = $index === null ? (isset($_POST[$prefix.'value']) ? trim($_POST[$prefix.'value']) : '') : (isset($_POST[$prefix.'value'][$index]) ? trim($_POST[$prefix.'value'][$index]) : '');

	if($type === '') alert('성공조건을 사용할 때는 조건 종류를 선택하세요.');
	if(!in_array($type, array('stat', 'favor', 'money', 'item'), true)) alert('올바른 선택지 조건 종류를 선택하세요.');
	if(!in_array($operator, array('>=', '>', '<=', '<', '=', '!='), true)) alert('올바른 비교 연산자를 선택하세요.');
	if($value === '' || !preg_match('/^-?\d+$/', $value)) alert('선택지 조건 기준값은 정수로 입력하세요.');

	$targets = map_admin_condition_targets();
	if($type == 'money') {
		$target = '';
	} else if($type == 'stat') {
		if($target === '' || !isset($targets['stat'][$target])) alert('등록된 스탯을 선택하세요.');
	} else if($type == 'favor') {
		$target_id = (int)$target;
		if($target_id <= 0 || !isset($targets['favor'][$target_id])) alert('등록된 NPC를 선택하세요.');
		$target = (string)$target_id;
	} else if($type == 'item') {
		$target_id = (int)$target;
		if($target_id <= 0 || !isset($targets['item'][$target_id])) alert('등록된 아이템을 선택하세요.');
		$target = (string)$target_id;
	}

	return array(
		'type' => "'".sql_real_escape_string($type)."'",
		'target' => $target === '' ? 'NULL' : "'".sql_real_escape_string($target)."'",
		'operator' => "'".sql_real_escape_string($operator)."'",
		'value' => (int)$value
	);
}

function map_admin_redirect($ma_id, $open_choice=0)
{
	$url = './map_event_list.php?ma_id='.(int)$ma_id;
	if($open_choice) $url .= '&open_choice='.(int)$open_choice;
	goto_url($url, false);
}

if(!$ma_id || !get_map($ma_id)) {
	alert("지역정보를 확인할 수 없습니다.");
}

if($mode == 'npc_add') {
	$npc_name = sql_real_escape_string(trim($_POST['npc_name']));
	$npc = sql_fetch(" select ch_id from {$g5['character_table']} where ch_type = 'npc' and ch_name = '{$npc_name}' ");
	if(!$npc['ch_id']) alert("등록된 NPC 이름을 확인할 수 없습니다.");

	$mn_weight = map_admin_weight(isset($_POST['mn_weight']) ? $_POST['mn_weight'] : null, 1);
	$mn_order = isset($_POST['mn_order']) ? (int)$_POST['mn_order'] : 0;
	$mn_use = isset($_POST['mn_use']) ? 1 : 0;

	sql_query("
		insert into {$g5['map_npc_place_table']}
			set ma_id = '{$ma_id}',
				npc_id = '{$npc['ch_id']}',
				mn_weight = '{$mn_weight}',
				mn_order = '{$mn_order}',
				mn_use = '{$mn_use}'
	");
	map_admin_redirect($ma_id);
}

if($mode == 'npc_list') {
	if (!isset($_POST['chk']) || !count($_POST['chk'])) alert($_POST['act_button']." 하실 NPC를 하나 이상 선택하세요.");
	$count = count($_POST['chk']);
	if($_POST['act_button'] == '선택수정') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$mn_id = (int)$_POST['mn_id'][$k];
			$mn_weight = map_admin_weight(isset($_POST['mn_weight'][$k]) ? $_POST['mn_weight'][$k] : null, 1);
			$mn_order = isset($_POST['mn_order'][$k]) ? (int)$_POST['mn_order'][$k] : 0;
			$mn_use = isset($_POST['mn_use'][$k]) ? 1 : 0;
			sql_query("
				update {$g5['map_npc_place_table']}
					set mn_weight = '{$mn_weight}',
						mn_order = '{$mn_order}',
						mn_use = '{$mn_use}'
					where mn_id = '{$mn_id}'
						and ma_id = '{$ma_id}'
			");
		}
	} else if($_POST['act_button'] == '선택삭제') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$mn_id = (int)$_POST['mn_id'][$k];
			$mn = sql_fetch(" select mn_id from {$g5['map_npc_place_table']} where mn_id = '{$mn_id}' and ma_id = '{$ma_id}' " );
			if(!$mn['mn_id']) continue;

			$script_ids = array();
			$script_result = sql_query(" select script_id from {$g5['map_npc_script_table']} where mn_id = '{$mn_id}' " );
			for($j=0; $script = sql_fetch_array($script_result); $j++) $script_ids[] = (int)$script['script_id'];
			if(count($script_ids)) {
				$id_sql = implode(',', $script_ids);
				sql_query(" update {$g5['map_npc_script_table']} set script_require_script_id = NULL where script_require_script_id in ({$id_sql}) " );
				sql_query(" delete from {$g5['map_npc_choice_table']} where script_id in ({$id_sql}) " );
			}
			sql_query(" delete from {$g5['map_npc_script_table']} where mn_id = '{$mn_id}' " );
			sql_query(" delete from {$g5['map_npc_place_table']} where mn_id = '{$mn_id}' and ma_id = '{$ma_id}' " );
		}
	}
	map_admin_redirect($ma_id);
}

if($mode == 'script_add') {
	$mn_id = isset($_POST['mn_id']) ? (int)$_POST['mn_id'] : 0;
	$mn = sql_fetch(" select mn_id from {$g5['map_npc_place_table']} where mn_id = '{$mn_id}' and ma_id = '{$ma_id}' ");
	if(!$mn['mn_id']) alert("NPC 연결 정보를 확인할 수 없습니다.");

	$script_type = $_POST['script_type'] == 'event' ? 'event' : 'dialogue';
	$script_title = sql_real_escape_string(trim($_POST['script_title']));
	$script_content = sql_real_escape_string($_POST['script_content']);
	$script_weight = map_admin_weight(isset($_POST['script_weight']) ? $_POST['script_weight'] : null, 1);
	$script_favor_s = isset($_POST['script_favor_s']) && $_POST['script_favor_s'] !== '' ? (int)$_POST['script_favor_s'] : 'NULL';
	$script_favor_e = isset($_POST['script_favor_e']) && $_POST['script_favor_e'] !== '' ? (int)$_POST['script_favor_e'] : 'NULL';
	$script_favor_value = isset($_POST['script_favor_value']) ? (int)$_POST['script_favor_value'] : 0;
	$script_repeat_type = in_array($_POST['script_repeat_type'], array('always','once','daily','cooldown'), true) ? $_POST['script_repeat_type'] : 'always';
	$script_cooldown = $script_repeat_type == 'cooldown' ? max(0, (int)$_POST['script_cooldown']) : 0;
	$script_require_script_id = isset($_POST['script_require_script_id']) && (int)$_POST['script_require_script_id'] ? (int)$_POST['script_require_script_id'] : 'NULL';
	$script_order = isset($_POST['script_order']) ? (int)$_POST['script_order'] : 0;
	$script_use = isset($_POST['script_use']) ? 1 : 0;

	if(!$script_title) $script_title = $script_type == 'event' ? '선택지 이벤트' : '일반 대사';
	if(!$script_content) alert("내용을 입력하세요.");

	sql_query("
		insert into {$g5['map_npc_script_table']}
			set mn_id = '{$mn_id}',
				script_type = '{$script_type}',
				script_title = '{$script_title}',
				script_content = '{$script_content}',
				script_weight = '{$script_weight}',
				script_favor_s = {$script_favor_s},
				script_favor_e = {$script_favor_e},
				script_favor_value = '{$script_favor_value}',
				script_repeat_type = '{$script_repeat_type}',
				script_cooldown = '{$script_cooldown}',
				script_require_script_id = {$script_require_script_id},
				script_order = '{$script_order}',
				script_use = '{$script_use}'
	");
	map_admin_redirect($ma_id);
}

if($mode == 'script_list') {
	$mn_id = isset($_POST['mn_id']) ? (int)$_POST['mn_id'] : 0;
	$open_choice = isset($_POST['open_choice']) ? (int)$_POST['open_choice'] : 0;
	if (!isset($_POST['chk']) || !count($_POST['chk'])) alert($_POST['act_button']." 하실 대사/이벤트를 하나 이상 선택하세요.");
	$count = count($_POST['chk']);
	if($_POST['act_button'] == '선택수정') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$script_id = (int)$_POST['script_id'][$k];
			$script_type = $_POST['script_type'][$k] == 'event' ? 'event' : 'dialogue';
			$script_title = sql_real_escape_string(trim($_POST['script_title'][$k]));
			$script_content = sql_real_escape_string($_POST['script_content'][$k]);
			$script_weight = map_admin_weight(isset($_POST['script_weight'][$k]) ? $_POST['script_weight'][$k] : null, 1);
			$script_favor_s = isset($_POST['script_favor_s'][$k]) && $_POST['script_favor_s'][$k] !== '' ? (int)$_POST['script_favor_s'][$k] : 'NULL';
			$script_favor_e = isset($_POST['script_favor_e'][$k]) && $_POST['script_favor_e'][$k] !== '' ? (int)$_POST['script_favor_e'][$k] : 'NULL';
			$script_favor_value = isset($_POST['script_favor_value'][$k]) ? (int)$_POST['script_favor_value'][$k] : 0;
			$script_repeat_type = isset($_POST['script_repeat_type'][$k]) && in_array($_POST['script_repeat_type'][$k], array('always','once','daily','cooldown'), true) ? $_POST['script_repeat_type'][$k] : 'always';
			$script_cooldown = $script_repeat_type == 'cooldown' ? max(0, (int)$_POST['script_cooldown'][$k]) : 0;
			$script_require_script_id = isset($_POST['script_require_script_id'][$k]) && (int)$_POST['script_require_script_id'][$k] ? (int)$_POST['script_require_script_id'][$k] : 'NULL';
			$script_order = isset($_POST['script_order'][$k]) ? (int)$_POST['script_order'][$k] : 0;
			$script_use = isset($_POST['script_use'][$k]) ? 1 : 0;
			sql_query("
				update {$g5['map_npc_script_table']} sc
				join {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id
					set sc.script_type = '{$script_type}',
						sc.script_title = '{$script_title}',
						sc.script_content = '{$script_content}',
						sc.script_weight = '{$script_weight}',
						sc.script_favor_s = {$script_favor_s},
						sc.script_favor_e = {$script_favor_e},
						sc.script_favor_value = '{$script_favor_value}',
						sc.script_repeat_type = '{$script_repeat_type}',
						sc.script_cooldown = '{$script_cooldown}',
						sc.script_require_script_id = {$script_require_script_id},
						sc.script_order = '{$script_order}',
						sc.script_use = '{$script_use}'
					where sc.script_id = '{$script_id}'
						and sc.mn_id = '{$mn_id}'
						and mn.ma_id = '{$ma_id}'
			");
		}
	} else if($_POST['act_button'] == '선택삭제') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$script_id = (int)$_POST['script_id'][$k];
			$script = sql_fetch("
				select sc.script_id
					from {$g5['map_npc_script_table']} sc
					join {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id
					where sc.script_id = '{$script_id}'
						and sc.mn_id = '{$mn_id}'
						and mn.ma_id = '{$ma_id}'
			");
			if($script['script_id']) {
				sql_query(" update {$g5['map_npc_script_table']} set script_require_script_id = NULL where script_require_script_id = '{$script_id}' ");
					sql_query(" delete from {$g5['map_npc_choice_table']} where script_id = '{$script_id}' ");
				sql_query(" delete from {$g5['map_npc_script_table']} where script_id = '{$script_id}' ");
			}
		}
	}
	map_admin_redirect($ma_id, $open_choice);
}

if($mode == 'choice_add') {
	$script_id = isset($_POST['script_id']) ? (int)$_POST['script_id'] : 0;
	$script = sql_fetch("
		select sc.script_id
			from {$g5['map_npc_script_table']} sc
			join {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id
			where sc.script_id = '{$script_id}'
				and mn.ma_id = '{$ma_id}'
	");
	if(!$script['script_id']) alert("선택지 이벤트 정보를 확인할 수 없습니다.");

	$choice_text = sql_real_escape_string(trim($_POST['choice_text']));
	$choice_result = sql_real_escape_string($_POST['choice_result']);
	$condition_use = isset($_POST['choice_condition_use']) ? 1 : 0;
	$choice_fail_result_raw = $condition_use && isset($_POST['choice_fail_result']) ? $_POST['choice_fail_result'] : '';
	if($condition_use && trim($choice_fail_result_raw) === '') alert('성공조건을 사용할 때는 실패 결과 스크립트를 입력하세요.');
	$choice_fail_result = sql_real_escape_string($choice_fail_result_raw);
	$condition = map_admin_condition('choice_check_', null, $condition_use);
	$choice_get_money = isset($_POST['choice_get_money']) ? (int)$_POST['choice_get_money'] : 0;
	$choice_fail_get_money = $condition_use && isset($_POST['choice_fail_get_money']) ? (int)$_POST['choice_fail_get_money'] : 0;
	$choice_get_item = map_admin_item_id(isset($_POST['choice_get_item']) ? $_POST['choice_get_item'] : 0);
	$choice_fail_get_item = $condition_use ? map_admin_item_id(isset($_POST['choice_fail_get_item']) ? $_POST['choice_fail_get_item'] : 0) : 0;
	$choice_favor_value = isset($_POST['choice_favor_value']) ? (int)$_POST['choice_favor_value'] : 0;
	$choice_fail_favor_value = $condition_use && isset($_POST['choice_fail_favor_value']) ? (int)$_POST['choice_fail_favor_value'] : 0;
	$choice_order = isset($_POST['choice_order']) ? (int)$_POST['choice_order'] : 0;
	$choice_use = isset($_POST['choice_use']) ? 1 : 0;
	if(!$choice_text) alert("선택지 문구를 입력하세요.");

	sql_query("
		insert into {$g5['map_npc_choice_table']}
			set script_id = '{$script_id}',
				choice_text = '{$choice_text}',
				choice_result = '{$choice_result}',
				choice_fail_result = '{$choice_fail_result}',
				choice_check_type = {$condition['type']},
				choice_check_target = {$condition['target']},
				choice_check_operator = {$condition['operator']},
				choice_check_value = {$condition['value']},
				choice_get_item = '{$choice_get_item}',
				choice_get_money = '{$choice_get_money}',
				choice_fail_get_item = '{$choice_fail_get_item}',
				choice_fail_get_money = '{$choice_fail_get_money}',
				choice_favor_value = '{$choice_favor_value}',
				choice_fail_favor_value = '{$choice_fail_favor_value}',
				choice_order = '{$choice_order}',
				choice_use = '{$choice_use}'
	");
	map_admin_redirect($ma_id, $script_id);
}

if($mode == 'choice_list') {
	$script_id = isset($_POST['script_id']) ? (int)$_POST['script_id'] : 0;
	if (!isset($_POST['chk']) || !count($_POST['chk'])) alert($_POST['act_button']." 하실 선택지를 하나 이상 선택하세요.");
	$count = count($_POST['chk']);
	if($_POST['act_button'] == '선택수정') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$choice_id = (int)$_POST['choice_id'][$k];
			$choice_text = sql_real_escape_string(trim($_POST['choice_text'][$k]));
			$choice_result = sql_real_escape_string($_POST['choice_result'][$k]);
			$condition_use = isset($_POST['choice_condition_use'][$k]) ? 1 : 0;
			$choice_fail_result_raw = $condition_use && isset($_POST['choice_fail_result'][$k]) ? $_POST['choice_fail_result'][$k] : '';
			if($condition_use && trim($choice_fail_result_raw) === '') alert('성공조건을 사용할 때는 실패 결과 스크립트를 입력하세요.');
			$choice_fail_result = sql_real_escape_string($choice_fail_result_raw);
			$condition = map_admin_condition('choice_check_', $k, $condition_use);
			$choice_get_money = (int)$_POST['choice_get_money'][$k];
			$choice_fail_get_money = $condition_use && isset($_POST['choice_fail_get_money'][$k]) ? (int)$_POST['choice_fail_get_money'][$k] : 0;
			$choice_get_item = map_admin_item_id(isset($_POST['choice_get_item'][$k]) ? $_POST['choice_get_item'][$k] : 0);
			$choice_fail_get_item = $condition_use ? map_admin_item_id(isset($_POST['choice_fail_get_item'][$k]) ? $_POST['choice_fail_get_item'][$k] : 0) : 0;
			$choice_favor_value = isset($_POST['choice_favor_value'][$k]) ? (int)$_POST['choice_favor_value'][$k] : 0;
			$choice_fail_favor_value = $condition_use && isset($_POST['choice_fail_favor_value'][$k]) ? (int)$_POST['choice_fail_favor_value'][$k] : 0;
			$choice_order = isset($_POST['choice_order'][$k]) ? (int)$_POST['choice_order'][$k] : 0;
			$choice_use = isset($_POST['choice_use'][$k]) ? 1 : 0;
			sql_query("
				update {$g5['map_npc_choice_table']} ch
				join {$g5['map_npc_script_table']} sc on ch.script_id = sc.script_id
				join {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id
					set ch.choice_text = '{$choice_text}',
						ch.choice_result = '{$choice_result}',
						ch.choice_fail_result = '{$choice_fail_result}',
						ch.choice_check_type = {$condition['type']},
						ch.choice_check_target = {$condition['target']},
						ch.choice_check_operator = {$condition['operator']},
						ch.choice_check_value = {$condition['value']},
						ch.choice_get_item = '{$choice_get_item}',
						ch.choice_get_money = '{$choice_get_money}',
						ch.choice_fail_get_item = '{$choice_fail_get_item}',
						ch.choice_fail_get_money = '{$choice_fail_get_money}',
						ch.choice_favor_value = '{$choice_favor_value}',
						ch.choice_fail_favor_value = '{$choice_fail_favor_value}',
						ch.choice_order = '{$choice_order}',
						ch.choice_use = '{$choice_use}'
					where ch.choice_id = '{$choice_id}'
						and ch.script_id = '{$script_id}'
						and mn.ma_id = '{$ma_id}'
			");
		}
	} else if($_POST['act_button'] == '선택삭제') {
		for($i=0; $i<$count; $i++) {
			$k = $_POST['chk'][$i];
			$choice_id = (int)$_POST['choice_id'][$k];
			$choice = sql_fetch("
				select ch.choice_id
					from {$g5['map_npc_choice_table']} ch
					join {$g5['map_npc_script_table']} sc on ch.script_id = sc.script_id
					join {$g5['map_npc_place_table']} mn on sc.mn_id = mn.mn_id
					where ch.choice_id = '{$choice_id}'
						and ch.script_id = '{$script_id}'
						and mn.ma_id = '{$ma_id}'
			");
			if($choice['choice_id']) {
				sql_query(" delete from {$g5['map_npc_choice_table']} where choice_id = '{$choice_id}' ");
			}
		}
	}
	map_admin_redirect($ma_id, $script_id);
}

alert("처리할 수 없는 요청입니다.");
?>
