<?php
$sub_menu='730150';include_once './_common.php';require_once __DIR__.'/dungeon_admin.lib.php';da_monster_auth('r');
require_once G5_PATH.'/lib/monster_shared.lib.php';
function mh($v){return da_h($v);}
$message=!empty($_GET['saved'])?'저장했습니다. 던전·레이드에서 같은 몬스터를 선택할 수 있습니다.':'';$id=(int)($_REQUEST['monster_id']??0);
$row=$id?sql_fetch('SELECT * FROM `'.monster_table('monster_master').'` WHERE monster_id='.$id):array();
$profile=json_decode($row['profile']??'{}',true)?:array();
$initial_shared=isset($profile['shared']);
$definitions=monster_rows('SELECT * FROM `'.$g5['status_config_table'].'` ORDER BY st_order,st_id');
$hp_id=0;$mp_id=(int)($kb_cf['mp']??0);foreach($definitions as $stat)if(!empty($stat['st_use_hp'])){$hp_id=(int)$stat['st_id'];break;}
if(monster_shared_ready() && $_SERVER['REQUEST_METHOD']==='POST') {
    da_monster_auth('w');check_token();
    try {
        if(!monster_raid_catalog_ready())throw new RuntimeException('시스템 설치 / 업데이트에서 몬스터 관리 1.6.0을 적용하세요.');
        if($id && !$row)throw new RuntimeException('수정할 몬스터가 없습니다.');
        $name=trim(stripslashes($_POST['name']??''));if($name==='')throw new RuntimeException('이름을 입력하세요.');
        $hp=(int)($_POST['hp']??0);if($hp<1)throw new RuntimeException('최대 HP는 1 이상이어야 합니다.');
        $min=max(0,(int)($_POST['attack_min']??0));$max=max(0,(int)($_POST['attack_max']??0));if($max<$min)throw new RuntimeException('최대 피해는 최소 피해 이상이어야 합니다.');
        $stats=array();foreach($definitions as $stat)$stats[(int)$stat['st_id']]=max(0,(int)($_POST['stats'][$stat['st_id']]??0));
        if($hp_id)$stats[$hp_id]=$hp;if($mp_id)unset($stats[$mp_id]);
        $catalog=monster_shared_skills();$pattern_mode=$_POST['pattern_mode']??($initial_shared?'turns':($id?'legacy_k':'phases'));
        if(!in_array($pattern_mode,array('phases','turns','legacy_k'),true) || ($pattern_mode==='legacy_k' && $initial_shared))throw new RuntimeException('행동 패턴 방식을 다시 선택하세요.');
        $phase_enabled=$pattern_mode==='phases';
        $phases=$phase_enabled?monster_shared_validate_phases(json_decode(stripslashes($_POST['phases']??'[]'),true),$catalog):array();
        $entries=$phase_enabled||$pattern_mode==='legacy_k'?array():json_decode(stripslashes($_POST['abilities']??'[]'),true);
        if(!is_array($entries))throw new RuntimeException('스킬 설정을 확인하세요.');
        $skills=array();$seen=array();foreach($entries as $entry) {
            $sid=(int)($entry['skill_id']??$entry['id']??0);if(!isset($catalog[$sid]))throw new RuntimeException('몬스터 스킬을 선택하세요.');
            if(isset($seen[$sid]))throw new RuntimeException('같은 스킬은 한 번만 등록하세요.');$seen[$sid]=true;
            $turn=filter_var($entry['turn']??1,FILTER_VALIDATE_INT);
            if($turn===false || $turn<1)throw new RuntimeException('발동 턴은 1 이상의 정수로 입력하세요.');
            $skills[]=array_replace(array('skill_id'=>$sid,'turn'=>$turn),monster_shared_validate_hp_range($entry));
        }
        $image=monster_image_input('image','image_file');$desc=stripslashes($_POST['description']??'');$enabled=!empty($_POST['enabled'])?1:0;
        $pattern=($_POST['pattern']??'INTERVAL')==='CYCLE'?'CYCLE':'INTERVAL';
        $shared_enabled=$pattern_mode!=='legacy_k';
        if($shared_enabled){
            $profile['shared']=array_replace($profile['shared']??array(),array('stats'=>$stats,'attack_min'=>$min,'attack_max'=>$max,'pattern'=>$pattern,'default_attack'=>!empty($_POST['default_attack']),'skills'=>$skills));
            unset($profile['shared']['mp']);
            if($phase_enabled)$profile['shared']['phases']=$phases;else unset($profile['shared']['phases']);
            unset($profile['k_mp']);
        } else {unset($profile['shared'],$profile['k_mp']);}
        $profile['dg_mon_hp']=$hp;$profile['dg_defence']=max(0,(int)($_POST['defense']??0));
        $profile['dg_d_attack_min']=$min;$profile['dg_d_attack_max']=$max;$profile['dg_d_attack_turn']=1;$profile['dg_d_attack_count']=1;
        foreach(array('dg_strong_code','dg_weak_code') as $key){$code=stripslashes($_POST[$key]??'');if($code!=='' && !sql_fetch('SELECT ex_id FROM `'.$g5['status_extra_table'].'` WHERE ex_name='.monster_quote($code)))throw new RuntimeException('연동 특성을 선택하세요.');$profile[$key]=$code;}
        foreach(array('dg_strong_value','dg_weak_value') as $key){$value=$_POST[$key]??1;if(!is_numeric($value)||$value<0)throw new RuntimeException('특성 배율은 0 이상이어야 합니다.');$profile[$key]=(float)$value;}
        foreach(array('dg_weak_effect_count','dg_weak_effect_turn') as $key)$profile[$key]=max(0,(int)($_POST[$key]??0));
        $raid_types=$_POST['raid_types']??array();if(!is_array($raid_types))throw new RuntimeException('레이드 사용 범위를 다시 선택하세요.');
        $raid_types=array_values(array_unique($raid_types));foreach($raid_types as $type)if(!in_array($type,array('realtime','mmbraid'),true))throw new RuntimeException('레이드 사용 범위를 다시 선택하세요.');
        $profile['k_raid_type']=implode(',',$raid_types);
        $profile['k_pattern']=$pattern==='CYCLE'?2:1;
        $profile['k_default_act']=!empty($_POST['default_attack'])?2:1;
        $profile['k_description']=$desc;
        $profile['k_stats']=array();for($i=0;$i<10;$i++)$profile['k_stats'][]=(int)($stats[(int)($definitions[$i]['st_id']??0)]??0);
        unset($profile['dg_mon_name'],$profile['dg_mon_img'],$profile['dg_mon_descript']);
        $raid=(int)($row['raid_id']??0);
        if($id && !$raid)$raid=monster_raid_next_id($id);
        $sets='name='.monster_quote($name).',image='.monster_quote($image).',description='.monster_quote($desc).',enabled='.$enabled.',raid_id='.($raid?:'NULL').',profile='.monster_quote(json_encode($profile,JSON_UNESCAPED_UNICODE));
        if($id)sql_query('UPDATE `'.monster_table('monster_master').'` SET '.$sets.' WHERE monster_id='.$id);
        else {sql_query('INSERT INTO `'.monster_table('monster_master').'` SET '.$sets);$id=(int)sql_insert_id();$raid=monster_raid_next_id($id);sql_query('UPDATE `'.monster_table('monster_master').'` SET raid_id='.$raid.' WHERE monster_id='.$id);}
        goto_url('./monster_form.php?monster_id='.$id.'&saved=1');
    }catch(Throwable $e){
        $message=$e->getMessage();
        foreach(array('name','description','image') as $key)$row[$key]=stripslashes($_POST[$key]??($row[$key]??''));
        $row['enabled']=!empty($_POST['enabled'])?1:0;
        $profile['dg_mon_hp']=$_POST['hp']??($profile['dg_mon_hp']??100);$profile['dg_defence']=$_POST['defense']??($profile['dg_defence']??0);
        $pending=json_decode(stripslashes($_POST['abilities']??'[]'),true);
        $profile['shared']=array_replace($profile['shared']??array(),array('stats'=>$_POST['stats']??array(),'attack_min'=>$_POST['attack_min']??10,'attack_max'=>$_POST['attack_max']??10,'pattern'=>$_POST['pattern']??'INTERVAL','default_attack'=>!empty($_POST['default_attack']),'skills'=>is_array($pending)?$pending:array()));
        unset($profile['shared']['mp']);
        if(isset($_POST['phases']))$profile['shared']['phases']=json_decode(stripslashes($_POST['phases']),true)?:array();
        foreach(array('dg_strong_code','dg_weak_code','dg_strong_value','dg_weak_value','dg_weak_effect_count','dg_weak_effect_turn') as $key)if(isset($_POST[$key]))$profile[$key]=stripslashes($_POST[$key]);
    }
}
$g5['title']=$id?'몬스터 수정':'몬스터 추가';include_once './admin.head.php';
if(!monster_shared_ready()){echo '<div class="local_desc01 local_desc">시스템 설치 / 업데이트에서 몬스터 관리 기능을 설치하거나 업데이트하세요. <a href="system_install.php">DB 업데이트</a></div>';include './admin.tail.php';return;}
require __DIR__.'/monster.view.php';include './admin.tail.php';
