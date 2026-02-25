<?php
include_once('../../../../common.php');

function insert_k_battle_table($battle_table){
	global $g5;
	
	sql_query(" CREATE TABLE IF NOT EXISTS `{$battle_table}_skill` (
		`bs_id` int(11) NOT NULL AUTO_INCREMENT,
		`rm_id`  int(11) NOT NULL DEFAULT '0',
		`unit_id`  int(11) NOT NULL DEFAULT '0',
		`unit_type`  VARCHAR(10) NOT NULL DEFAULT '',
		`cs_id`  int(11) NOT NULL DEFAULT '0',
		`sk_cool_now` int(11) NOT NULL DEFAULT '0',
		`ra_id` VARCHAR(55) NOT NULL DEFAULT '0',
		PRIMARY KEY (`bs_id`),
		KEY `ra_id` (`ra_id`),
		KEY `rm_id` (`rm_id`),
		KEY `cs_id` (`cs_id`)
	) ", false);
	
	sql_query(" CREATE TABLE IF NOT EXISTS `{$battle_table}_buff` (
		`bf_id` int(11) NOT NULL AUTO_INCREMENT,
		`rm_id`  int(11) NOT NULL DEFAULT '0',
		`si_code`	varchar(50) NOT NULL DEFAULT '0',
		`bf_value`	int(11) NOT NULL DEFAULT '0',
		`sc_id`	varchar(50) NOT NULL DEFAULT '0',
		`cs_id`	int(11) NOT NULL DEFAULT '0',
		`turn_left` int(11) NOT NULL DEFAULT '0',
		`ra_id` VARCHAR(55) NOT NULL DEFAULT '0',
		PRIMARY KEY (`bf_id`),
		KEY `ra_id` (`ra_id`),
		KEY `rm_id` (`rm_id`)
	) ", false);

	sql_query(" CREATE TABLE IF NOT EXISTS `{$battle_table}_unit` (
		`rm_id` int(11) NOT NULL AUTO_INCREMENT,
		`unit_type` varchar(255) NOT NULL,
		`unit_id` int(11) NOT NULL,
		`hp_now` int(11) NOT NULL DEFAULT '0',
		`hp_max` int(11) NOT NULL DEFAULT '0',
		`mp_now` int(11) NOT NULL DEFAULT '0',
		`mp_max` int(11) NOT NULL DEFAULT '0',
		`st_1` int(11) NOT NULL DEFAULT '0',
		`st_2` int(11) NOT NULL DEFAULT '0',
		`st_3` int(11) NOT NULL DEFAULT '0',
		`st_4` int(11) NOT NULL DEFAULT '0',
		`st_5` int(11) NOT NULL DEFAULT '0',
		`st_6` int(11) NOT NULL DEFAULT '0',
		`st_7` int(11) NOT NULL DEFAULT '0',
		`st_8` int(11) NOT NULL DEFAULT '0',
		`st_9` int(11) NOT NULL DEFAULT '0',
		`st_10` int(11) NOT NULL DEFAULT '0',
		`is_stun` int(11) NOT NULL DEFAULT '0',
		`is_aggr` int(11) NOT NULL DEFAULT '0',
		`tt_done` int(11) NOT NULL DEFAULT '0',
		`ra_id` VARCHAR(55) NOT NULL DEFAULT '0',
		PRIMARY KEY (`rm_id`),
		KEY `ra_id` (`ra_id`),
		KEY `unit_id` (`unit_id`)
	) ", false);

	sql_query(" CREATE TABLE IF NOT EXISTS `{$battle_table}_log` (
		`lo_id` int(11) NOT NULL AUTO_INCREMENT,
		`ra_id` VARCHAR(55) NOT NULL DEFAULT '0',
		`lo_content` TEXT NOT NULL DEFAULT '',
		`lo_1` VARCHAR(255) NOT NULL DEFAULT '',
		`lo_2` VARCHAR(255) NOT NULL DEFAULT '',
		`lo_3` VARCHAR(255) NOT NULL DEFAULT '',
		`lo_4` VARCHAR(255) NOT NULL DEFAULT '',
		`lo_5` VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (`lo_id`),
		KEY `ra_id` (`ra_id`)
	) ", false);

	return true;
}

if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}


if ($is_admin !== 'super') {
    alert('권한이 없습니다.');
}

/* ---------- 입력 정리 ---------- */
$act_button = ses($_POST, 'act_button', '', 'raw');
$create     = !empty($_POST['create']);

$origin_bo_2= ses($_POST, 'origin_bo_2', '', 'string');
$bo_2       = ses($_POST, 'bo_2', '', 'string');
$bo_3       = ses($_POST, 'bo_3', '', 'string');
$bo_4       = ses($_POST, 'bo_4', '', 'string');
$bo_5       = ses($_POST, 'bo_5', '', 'string');
$bo_6       = ses($_POST, 'bo_6', '', 'string');
$bo_7       = ses($_POST, 'bo_7', '', 'string');
$bo_8       = ses($_POST, 'bo_8', '', 'string');
$bo_1       = ses($_POST, 'bo_1', '', 'string');
$bo_2_subj  = ses($_POST, 'bo_2_subj', '', 'string');
$bo_3_subj  = ses($_POST, 'bo_3_subj', '', 'string');
$bo_4_subj  = ses($_POST, 'bo_4_subj', '', 'string');
$bo_5_subj  = ses($_POST, 'bo_5_subj', '', 'string');
$bo_6_subj  = ses($_POST, 'bo_6_subj', '', 'string');
$bo_7_subj  = ses($_POST, 'bo_7_subj', '', 'string');
$bo_8_subj  = ses($_POST, 'bo_8_subj', '', 'string');
$bo_9       = ses($_POST, 'bo_9', '', 'string');
$bo_9_subj  = ses($_POST, 'bo_9_subj', '', 'string');
$bo_10      = ses($_POST, 'bo_10', '', 'string');
$bo_10_subj = ses($_POST, 'bo_10_subj', '', 'string');
$bo_11      = ses($_POST, 'bo_11', '', 'string');
$bo_12      = ses($_POST, 'bo_12', '', 'string');

$raid_css     = ses($_POST, 'raid_css', array(), 'array');
$comment_css  = ses($_POST, 'comment_css', array(), 'array');
$system_css   = ses($_POST, 'system_css', array(), 'array');
if(!isset($battle_table)||!$battle_table){$battle_table=$g5['board_table'];}
$unit_id      = ses($_POST, 'unit_id', 0, 'int');
$url = './admin.php?bo_table='.$bo_table;

/* ---------- 헬퍼: 컬럼 존재 확인 ---------- */
function column_type_of($table, $column) {
    $t = sql_escape_string($table);
    $c = sql_escape_string($column);
    $row = sql_fetch("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME='{$t}' AND COLUMN_NAME='{$c}'");
    return is_array($row) ? (string)$row['COLUMN_TYPE'] : '';
}
function column_exists($table, $column) {
    return column_type_of($table, $column) !== '';
}

insert_k_battle_table($g5['board_table']);

/* ---------- 로직 ---------- */
if ($act_button === '설정변경') {

    if ($create) {
        // 기본값 초기 세팅
        $bo_1 = 'false';
        $bo_2_subj = 'false';
        $bo_2 = $bo_7 = $bo_10 = '';
        $bo_3 = $bo_4 = $bo_5 = $bo_6 = $bo_11 = 'true';
        $bo_9 = "600px|||||550px|300px|||5px|16px|14px|20px|crimson|crimson||black";
        $bo_9_subj = "600px||||||||||||||||";
        $bo_12 = "WARNING|gold|18px|none|black|10px|white|14px||||10px|white|14px|300px||transparent|||";
        $bo_10_subj = 'no';

        // 스키마 보정
        $ct = column_type_of($g5['board_table'], 'bo_10');
        if (strtoupper($ct) !== 'TEXT') {
            sql_query("ALTER TABLE `{$g5['board_table']}`
                MODIFY COLUMN `bo_3_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_4_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_5_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_6_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_7_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_8`       TEXT NOT NULL,
                MODIFY COLUMN `bo_8_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_9`       TEXT NOT NULL,
                MODIFY COLUMN `bo_9_subj`  TEXT NOT NULL,
                MODIFY COLUMN `bo_10`      TEXT NOT NULL
            ", true);
        }
        if (!column_exists($g5['board_table'], 'bo_11')) {
            sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_11` TEXT NOT NULL");
        }
        if (!column_exists($g5['board_table'], 'bo_12')) {
            sql_query("ALTER TABLE `{$g5['board_table']}` ADD `bo_12` TEXT NOT NULL");
        }

        delete_k_battle_data($g5['board_table'], $board['bo_table']);

    } else {
        // 보스 교체 시 몬스터 유닛 초기화 후 재삽입
       
        if ($origin_bo_2 !== $bo_2) {
            sql_query("DELETE FROM {$battle_table}_unit WHERE unit_type='mo' and ra_id = '{$ra_id}'");
            if ($bo_2 !== '') {
                $insert_result = insert_k_battle_unit($bo_2, 'mo', 'mmbraid', $ra_id);
                
                // 에러 로그 출력
                if (!empty($error_messages)) {
                    $err_text = implode("\\n", $error_messages);
                    alert("[몬스터 등록 에러]\\n" . $err_text);
                }
                
                // insert 결과 확인
                if (!$insert_result) {
                    alert("[몬스터 등록 실패]\\nbo_2={$bo_2}, ra_id={$ra_id}, battle_table={$battle_table}\\n결과: " . var_export($insert_result, true));
                }
            }
        }
        // 기존 스타일 유지
        $bo_9      = $board['bo_9'];
        $bo_9_subj = $board['bo_9_subj'];
        $bo_12     = $board['bo_12'];
    }

    sql_query("UPDATE {$g5['board_table']}
        SET bo_1='{$bo_1}',
            bo_2='{$bo_2}',
            bo_3='{$bo_3}',
            bo_4='{$bo_4}',
            bo_5='{$bo_5}',
            bo_6='{$bo_6}',
            bo_7='{$bo_7}',
            bo_10='{$bo_10}',
            bo_11='{$bo_11}',
            bo_1_subj='mmbraid',
            bo_2_subj='{$bo_2_subj}',
            bo_10_subj='{$bo_10_subj}',
            bo_12='{$bo_12}',
            bo_9='{$bo_9}',
            bo_9_subj='{$bo_9_subj}'
        WHERE bo_table='{$bo_table}'
        LIMIT 1
    ");

    $url = './admin.php?bo_table='.$bo_table;

} elseif ($act_button === '스타일변경' || $act_button === '스타일초기화') {

    if ($act_button === '스타일초기화') {
        $bo_9 = "600px|||||550px|300px|||5px|16px|14px|20px|crimson|crimson||black";
        $bo_9_subj = "600px||||||||||||||||";
        $bo_12 = "WARNING|gold|18px|none|black|10px|white|14px||||10px|white|14px|300px||transparent|||";
    } else {
        // 배열 → 파이프 결합. 내부 개행은 배열 요소 내에만 유지됨.
        $bo_9      = sql_escape_string(implode('|', $raid_css));
        $bo_9_subj = sql_escape_string(implode('|', $comment_css));
        $bo_12     = sql_escape_string(implode('|', $system_css));
    }

    sql_query("UPDATE {$g5['board_table']}
        SET bo_12='{$bo_12}', bo_9='{$bo_9}', bo_9_subj='{$bo_9_subj}'
        WHERE bo_table='{$bo_table}'
        LIMIT 1
    ");

    $url = './admin.css.php?bo_table='.$bo_table;

} elseif ($act_button === '텍스트변경') {

    sql_query("UPDATE {$g5['board_table']}
        SET bo_8='{$bo_8}',
            bo_3_subj='{$bo_3_subj}',
            bo_4_subj='{$bo_4_subj}',
            bo_5_subj='{$bo_5_subj}',
            bo_6_subj='{$bo_6_subj}',
            bo_7_subj='{$bo_7_subj}',
            bo_8_subj='{$bo_8_subj}'
        WHERE bo_table='{$bo_table}'
        LIMIT 1
    ");

    $url = './admin.text.php?bo_table='.$bo_table;

} elseif ($act_button === '전체초기화') {
    delete_k_battle_data($g5['board_table'], $board['bo_table']);

    sql_query("UPDATE {$g5['board_table']} SET
        bo_1='', bo_2='', bo_3='', bo_4='', bo_5='', bo_6='', bo_7='', bo_8='', bo_9='', bo_10='',
        bo_1_subj='', bo_2_subj='', bo_3_subj='', bo_4_subj='', bo_5_subj='', bo_6_subj='', bo_7_subj='', bo_8_subj='', bo_9_subj='', bo_10_subj='',
        bo_11='', bo_12=''
        WHERE bo_table='{$bo_table}'
        LIMIT 1
    ");

    $url = './admin.php?bo_table='.$bo_table;

} elseif ($act_button === '전투초기화') {

    sql_query("UPDATE {$battle_table}_unit
        SET hp_now=hp_max,
            mp_now=mp_max,
            tt_done='',
            is_aggr='',
            is_stun=''
        WHERE ra_id='{$ra_id}'
    ");

    sql_query("UPDATE {$battle_table}_skill
        SET sk_cool_now=0
        WHERE ra_id='{$ra_id}'
    ");

    sql_query("DELETE FROM {$battle_table}_log  WHERE ra_id='{$ra_id}'");
    sql_query("DELETE FROM {$battle_table}_buff WHERE ra_id='{$ra_id}'");

    $url = './admin.php?bo_table='.$bo_table;
    alert('초기화되었습니다.', $url);

} elseif ($act_button === '선택삭제') {

    $chk = ses($_POST, 'chk', array(), 'array');
    $rm_id = ses($_POST, 'rm_id', array(), 'array');

    for ($i = 0, $n = count($chk); $i < $n; $i++) {
        $k = (int)$chk[$i];
        if (!isset($rm_id[$k])) continue;
        delete_k_battle_unit((int)$rm_id[$k]);
    }
    alert('삭제되었습니다.', $url);

} elseif ($act_button === '참가등록') {

    if ($unit_id < 1) alert('대상 유닛이 없습니다.', $url);

    $check = sql_fetch("SELECT unit_id FROM {$battle_table}_unit WHERE unit_id='{$unit_id}' AND unit_type='ch' AND ra_id='{$ra_id}' LIMIT 1");
    if (empty($check['unit_id'])) {
        insert_k_battle_unit($unit_id, 'ch', 'mmbraid', $ra_id);
    } else {
        alert('이미 참가등록이 되어 있습니다.', $url);
    }

} else {
    // 미지정 동작
    alert('잘못된 요청입니다.', $url);
}

goto_url($url);
