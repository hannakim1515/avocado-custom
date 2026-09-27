<?php
$sub_menu = "710100";
include_once('./_common.php');

check_demo();
auth_check($auth[$sub_menu], 'w');
check_token();

$ma_id = isset($_POST['ma_id']) ? (int)$_POST['ma_id'] : 0;
$ma = get_map($ma_id);
if(!$ma['ma_id']) {
	alert("지역정보를 확인할 수 없습니다.");
}

$ma_name = sql_real_escape_string(trim($_POST['ma_name']));
$ma_img = sql_real_escape_string(trim(isset($_POST['ma_img']) ? $_POST['ma_img'] : $ma['ma_img']));
$ma_content = sql_real_escape_string($_POST['ma_content']);
$ma_use = isset($_POST['ma_use']) ? 1 : 0;
$ma_start = isset($_POST['ma_start']) ? 1 : 0;
$ma_use_dungeon = isset($_POST['ma_use_dungeon']) ? 1 : 0;
$ma_left = isset($_POST['ma_left']) ? (int)$_POST['ma_left'] : (int)$ma['ma_left'];
$ma_top = isset($_POST['ma_top']) ? (int)$_POST['ma_top'] : (int)$ma['ma_top'];
$ma_width = isset($_POST['ma_width']) ? (int)$_POST['ma_width'] : (int)$ma['ma_width'];
$ma_height = isset($_POST['ma_height']) ? (int)$_POST['ma_height'] : (int)$ma['ma_height'];
$ma_npc_chance = isset($_POST['ma_npc_chance']) ? (int)$_POST['ma_npc_chance'] : (isset($ma['ma_npc_chance']) ? (int)$ma['ma_npc_chance'] : 15);
if($ma_npc_chance < 0) $ma_npc_chance = 0;
if($ma_npc_chance > 100) $ma_npc_chance = 100;

if(!$ma_name) {
	alert("장소명을 입력하세요.");
}

$npc_chance_column = sql_fetch(" SHOW COLUMNS FROM `{$g5['map_table']}` LIKE 'ma_npc_chance' ", false);
if(!isset($npc_chance_column['Field']) || !$npc_chance_column['Field']) {
	alert("NPC 조우확률 DB 컬럼이 없습니다. 동봉된 NPC_UI_PATCH_MIGRATION.sql을 먼저 1회 실행하세요.");
}

sql_query("
	update {$g5['map_table']}
		set ma_name = '{$ma_name}',
			ma_img = '{$ma_img}',
			ma_use = '{$ma_use}',
			ma_start = '{$ma_start}',
			ma_use_dungeon = '{$ma_use_dungeon}',
			ma_left = '{$ma_left}',
			ma_top = '{$ma_top}',
			ma_width = '{$ma_width}',
			ma_height = '{$ma_height}',
			ma_npc_chance = '{$ma_npc_chance}',
			ma_content = '{$ma_content}'
		where ma_id = '{$ma_id}'
");

goto_url('./map_event_list.php?ma_id='.$ma_id, false);
?>
