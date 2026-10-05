<?php
$sub_menu = '981420';
include_once('./_common.php');

check_token();
require_once G5_PATH.'/lib/monster_shared.lib.php';
if (monster_shared_ready()) {
    $legacy_id=(int)($_REQUEST['mo_id']??0);
    $linked=$legacy_id?sql_fetch('SELECT monster_id FROM `'.monster_table('monster_master').'` WHERE raid_id='.$legacy_id):null;
    alert('몬스터 행동은 공용 몬스터 관리 화면에서 설정해 주세요.', './'.($linked?'monster_form.php?monster_id='.(int)$linked['monster_id']:'monster.php'));
}

$mo_id = ses($_REQUEST, 'mo_id', 0, 'int');
$type = ses($_REQUEST, 'type', '', 'string');

if ($mo_id <= 0) {
    alert('몬스터가 지정되지 않았습니다.');
}

$return_url = './982_k_mo_pattern.php?mo_id='.$mo_id;

// 패턴 타입 및 기본 행동 설정 (mo_pattern, mo_default_act 컬럼)
if ($type === 'set_pattern_type') {
    $mo_pattern = ses($_POST, 'mo_pattern', 1, 'int');
    if ($mo_pattern < 1 || $mo_pattern > 2) {
        $mo_pattern = 1;
    }
    
    $mo_default_act = ses($_POST, 'mo_default_act', 1, 'int');
    if ($mo_default_act < 1 || $mo_default_act > 2) {
        $mo_default_act = 1;
    }
    
    require_once G5_PATH.'/lib/monster.lib.php';
    if(!monster_raid_catalog_ready())alert('몬스터 관리 DB를 1.5.0으로 업데이트하세요.');
    $master=sql_fetch('SELECT monster_id,profile FROM `'.monster_table('monster_master').'` WHERE raid_id='.$mo_id);
    if(!$master)alert('몬스터를 찾을 수 없습니다.');
    $profile=json_decode($master['profile']??'{}',true);
    if(!is_array($profile))alert('몬스터 프로필을 확인하세요.');
    $profile['k_pattern']=$mo_pattern;$profile['k_default_act']=$mo_default_act;
    if(isset($profile['shared'])){$profile['shared']['pattern']=$mo_pattern===2?'CYCLE':'INTERVAL';$profile['shared']['default_attack']=$mo_default_act===2;}
    sql_query('UPDATE `'.monster_table('monster_master').'` SET profile='.monster_quote(json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).' WHERE monster_id='.(int)$master['monster_id']);
    
    goto_url($return_url);
}

// 패턴 등록
if ($type === 'insert') {
    $pt_turn = ses($_POST, 'pt_turn', 0, 'int');
    $pt_skill_arr = ses($_POST, 'pt_skill', array(), 'array');
    $pt_skill_cnt = ses($_POST, 'pt_skill_cnt', 1, 'int');
    
    // 스킬 후보 CSV 생성
    $valid_skills = array();
    foreach ($pt_skill_arr as $sk) {
        $sk_id = (int)$sk;
        if ($sk_id > 0) {
            $valid_skills[] = $sk_id;
        }
    }
    $pt_skill = implode(',', $valid_skills);
    
    // 스킬 갯수 조정
    $skill_count = count($valid_skills);
    if ($pt_skill_cnt < 1) {
        $pt_skill_cnt = 1;
    }
    if ($skill_count > 0 && $pt_skill_cnt > $skill_count) {
        $pt_skill_cnt = $skill_count;
    }
    
    if (empty($pt_skill)) {
        alert('스킬 후보를 하나 이상 선택해 주세요.');
    }
    
    sql_query("
        INSERT INTO {$g5['k_mo_pattern_table']} 
        SET mo_id = '{$mo_id}',
            pt_turn = '{$pt_turn}',
            pt_skill = '".sql_escape_string($pt_skill)."',
            pt_skill_cnt = '{$pt_skill_cnt}'
    ");
    
    goto_url($return_url);
}

// 선택 수정/삭제
$act_button = ses($_POST, 'act_button', '', 'raw');
$chk = ses($_POST, 'chk', array(), 'array');

if ($act_button === '선택삭제') {
    foreach ($chk as $idx) {
        $idx = (int)$idx;
        $pt_id = ses($_POST['pt_id'], $idx, 0, 'int');
        if ($pt_id > 0) {
            sql_query("DELETE FROM {$g5['k_mo_pattern_table']} WHERE pt_id = '{$pt_id}' AND mo_id = '{$mo_id}'");
        }
    }
    goto_url($return_url);
}

if ($act_button === '선택수정') {
    foreach ($chk as $idx) {
        $idx = (int)$idx;
        $pt_id = ses($_POST['pt_id'], $idx, 0, 'int');
        if ($pt_id <= 0) continue;
        
        $pt_turn = ses($_POST['pt_turn'], $idx, 0, 'int');
        $pt_skill_all = ses($_POST, 'pt_skill', array(), 'array');
        $pt_skill_arr = isset($pt_skill_all[$idx]) ? $pt_skill_all[$idx] : array();
        $pt_skill_cnt = ses($_POST['pt_skill_cnt'], $idx, 1, 'int');
        
        // 스킬 후보 CSV 생성
        $valid_skills = array();
        if (is_array($pt_skill_arr)) {
            foreach ($pt_skill_arr as $sk) {
                $sk_id = (int)$sk;
                if ($sk_id > 0) {
                    $valid_skills[] = $sk_id;
                }
            }
        }
        $pt_skill = implode(',', $valid_skills);
        
        // 스킬 갯수 조정
        $skill_count = count($valid_skills);
        if ($pt_skill_cnt < 1) {
            $pt_skill_cnt = 1;
        }
        if ($skill_count > 0 && $pt_skill_cnt > $skill_count) {
            $pt_skill_cnt = $skill_count;
        }
        
        if (empty($pt_skill)) {
            // 스킬이 모두 해제되면 해당 패턴 삭제
            sql_query("DELETE FROM {$g5['k_mo_pattern_table']} WHERE pt_id = '{$pt_id}' AND mo_id = '{$mo_id}'");
        } else {
            sql_query("
                UPDATE {$g5['k_mo_pattern_table']} 
                SET pt_turn = '{$pt_turn}',
                    pt_skill = '".sql_escape_string($pt_skill)."',
                    pt_skill_cnt = '{$pt_skill_cnt}'
                WHERE pt_id = '{$pt_id}' AND mo_id = '{$mo_id}'
            ");
        }
    }
    goto_url($return_url);
}

alert('잘못된 접근입니다.');
