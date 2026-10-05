<?php
$sub_menu = "730100";
include_once('./_common.php');
check_demo();

if (!count($_POST['chk'])) {
	alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

if ($_POST['act_button'] == "선택수정") {
	auth_check($auth[$sub_menu], 'w');
	check_token();
    foreach($_POST['chk'] as $k) {
        $id=(int)$_POST['dg_id'][$k];
        $dg=sql_fetch('SELECT * FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$id);
        $settings=!empty($dg['dg_settings'])?maze_data($dg['dg_settings']):array('min_players'=>1);
        $maximum=filter_var($_POST['dg_count'][$k],FILTER_VALIDATE_INT);
        if($maximum===false || $maximum<($settings['min_players']??1) || $maximum>999)alert('최대 참가 인원은 최소 출발 인원 이상이어야 합니다.');
    }
	for ($i=0; $i<count($_POST['chk']); $i++) {

		// 실제 번호를 넘김
		$k = $_POST['chk'][$i];

		$ma_ids = $_POST['ma_ids'][$k] = "||".implode("||", $_POST['ma_ids'][$k]??array())."||";
		$_POST['dg_use'][$k]=empty($_POST['dg_use'][$k])?0:1;

		$sql = " update {$g5['dungeon_table']}
					set dg_title         = '{$_POST['dg_title'][$k]}',
						ma_ids			= '{$ma_ids}',
						dg_rank         = '{$_POST['dg_rank'][$k]}',
						dg_per_s         = '{$_POST['dg_per_s'][$k]}',
						dg_per_e         = '{$_POST['dg_per_e'][$k]}',
						dg_count         = '{$_POST['dg_count'][$k]}',
						dg_use         = '{$_POST['dg_use'][$k]}'
				  where dg_id               = '{$_POST['dg_id'][$k]}' ";
		sql_query($sql);
		$id=(int)$_POST['dg_id'][$k];
	}

} else if ($_POST['act_button'] == "선택삭제") {
	auth_check($auth[$sub_menu], 'd');
	check_token();
	if(maze_readiness()['ready']) foreach($_POST['chk'] as $k) {
		$id=(int)$_POST['dg_id'][$k];
		if(maze_one('SELECT ds_id FROM `'.maze_table('session').'` WHERE dg_id='.$id.' AND snapshot IS NOT NULL LIMIT 1')) alert('탐험 이력이 있는 던전은 삭제할 수 없습니다. 사용 여부를 해제해 주세요.');
	}

	for ($i=0; $i<count($_POST['chk']); $i++) {
		$k = $_POST['chk'][$i];
		$temp_dg_id = trim($_POST['dg_id'][$k]);
		if (!$temp_dg_id) { return; }

		$temp_dg_id=(int)$temp_dg_id;
        sql_query('DELETE FROM `'.$g5['dungeon_item_table'].'` WHERE dg_id='.$temp_dg_id);
        sql_query('DELETE FROM `'.monster_table('dungeon_monster_pool').'` WHERE dg_id='.$temp_dg_id);
        sql_query('DELETE FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$temp_dg_id);
	}
}

goto_url('./dungeon_list.php?'.$qstr);
?>
