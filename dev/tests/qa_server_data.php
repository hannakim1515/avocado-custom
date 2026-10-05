<?php
/** Exercise the actual exported QA definitions, exclusively in a guarded clone. */
require dirname(__DIR__).'/tools/qa_bootstrap.php';
qa_assert($qaFixture,'Never exercise gameplay in the main database');
$is_member=false;require $root.'/extend/00_functions.php';require $root.'/extend/character.lib.php';require $root.'/extend/status.lib.php';require $root.'/extend/skill.lib.php';require $root.'/extend/item.lib.php';
require $root.'/k_battle/extend/raid/0_default.php';require $root.'/k_battle/extend/raid/1_skill.php';require $root.'/k_battle/extend/raid/2_turn.php';require $root.'/lib/monster_adapters.lib.php';
require $root.'/extend/dungeon_formula.lib.php';
require $root.'/extend/status_extra.lib.php';
$k_stat=array_map('intval',array_column(qa_rows('SELECT k_sc_id FROM '.qa_table('unified_combat_stat_map').' WHERE is_active=1 ORDER BY map_id'),'k_sc_id'));
$checks=0;
function check($ok,$label){qa_assert($ok,$label);$GLOBALS['checks']++;echo 'PASS '.$label,PHP_EOL;}
function select_character($ch){$GLOBALS['character']=get_character($ch);}
function act($ds,$op,$input=array()){global $character;$s=maze_session($ds);return maze_action($ds,$op,array_replace(array('version'=>$s['version'],'room_id'=>$s['room_id']),$input));}
function submit_round($ds,$actions=array()){
    $s=maze_session($ds);$b=maze_battle_current($s);$party=maze_party($ds);$living=maze_battle_living(maze_battle_group($s,$b));
    foreach($party as $p)if($p['hp']>0){select_character($p['ch_id']);$action=$actions[$p['ch_id']]??array('action'=>'GUARD');act($ds,'submit',array_replace(array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no'],'target_id'=>$p['dm_id'],'monster_id'=>key($living)),$action));}
    act($ds,'resolve',array('battle_id'=>$b['battle_id'],'turn_no'=>$b['turn_no']));
}
$chars=array_map('intval',array_column(qa_rows("SELECT ch_id FROM ".qa_table('character')." WHERE ch_name LIKE '[QA]%' ORDER BY ch_id"),'ch_id'));
$courses=qa_rows("SELECT s.*,d.dg_description FROM ".qa_table('dungeon_state').' s JOIN '.qa_table('dungeon')." d ON d.dg_id=s.dg_id WHERE d.dg_description LIKE '[QADATA]%' ORDER BY s.dg_id");
foreach($courses as $course){
    $ds=(int)$course['ds_id'];
    foreach($chars as $ch){qa_update('character',array('ma_id'=>$course['ds_ma_id']),'ch_id='.$ch);select_character($ch);maze_join($ds);}
    check(maze_session($ds)['phase']==='WAITING' && count(maze_party($ds))===3,$course['dg_title'].' enters waiting room using all existing administrator characters');
    foreach($chars as $ch){select_character($ch);act($ds,'ready',array('ready'=>1));}act($ds,'start');
    check(maze_session($ds)['phase']==='EXPLORE' && (int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_maze_inventory').' WHERE ds_id='.$ds)['n']>0,$course['dg_title'].' starts with real inventory isolation');
    act($ds,'move',array('direction'=>'forward'));
    $s=maze_session($ds);
    if($s['phase']==='BATTLE'){
        $group=maze_battle_group($s,maze_battle_current($s));
        if(strpos($course['dg_title'],'01 ')!==false){check(count($group)>=2&&count($group)<=4,'Generated encounter follows per-monster simultaneous count');
            $party=maze_party($ds);$p=$party[0];qa_update('dungeon_maze_member',array('hp'=>$p['max_hp']-60),'dm_id='.$p['dm_id']);
            $heal=sql_fetch('SELECT sh.sh_id FROM '.qa_table('skill_has').' sh JOIN '.qa_table('skill')." sk ON sk.sk_id=sh.sk_id WHERE sh.ch_id=".$p['ch_id'].' AND sk.sk_name='.qa_quote('[QA] 치유'));
            submit_round($ds,array($p['ch_id']=>array('action'=>'SKILL','reference_id'=>$heal['sh_id'],'target_id'=>$p['dm_id'])));
            $logs=qa_rows('SELECT result FROM '.qa_table('dungeon_combat_event').' WHERE ds_id='.$ds.' AND turn_no=1 AND action='.qa_quote('[QA] 치유'));
            check(count($logs)===1&&$logs[0]['result']==='HEAL','Single-target healing records one HEAL without redundant STATUS');
            $aoe=sql_fetch('SELECT sh.sh_id FROM '.qa_table('skill_has').' sh JOIN '.qa_table('skill')." sk ON sk.sk_id=sh.sk_id WHERE sh.ch_id=".$p['ch_id'].' AND sk.sk_name='.qa_quote('[QA] 휩쓸기'));
            submit_round($ds,array($p['ch_id']=>array('action'=>'SKILL','reference_id'=>$aoe['sh_id'],'target_id'=>$p['dm_id'])));
            check((int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_combat_event').' WHERE ds_id='.$ds.' AND action='.qa_quote('[QA] 휩쓸기'))['n']>=2,'Player area skill reaches simultaneous enemies');
        }else{submit_round($ds);check(count(qa_rows('SELECT * FROM '.qa_table('dungeon_combat_event').' WHERE ds_id='.$ds))>0,$course['dg_title'].' resolves first monster turn');}
    }else{act($ds,'event',array('choice'=>0));check(count(qa_rows('SELECT log_id FROM '.qa_table('dungeon_maze_log').' WHERE ds_id='.$ds." AND kind IN ('EVENT','ITEM_GAIN')"))>0,$course['dg_title'].' executes its room event');}
    if(strpos($course['dg_title'],'07 ')!==false){
        $countBefore=(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('inventory').' i JOIN '.qa_table('item')." it ON it.it_id=i.it_id WHERE i.ch_id=".$chars[0]." AND it.it_5='QADATA:CLEAR_GUARANTEED'")['n'];
        $pointBefore=(int)sql_fetch('SELECT mb_point FROM '.$g5['member_table'].' WHERE mb_id='.qa_quote($member['mb_id']))['mb_point'];
        for($steps=0;$steps<60;$steps++){
            $current=maze_session($ds);if($current['phase']==='CLEAR')break;
            if($current['phase']==='BATTLE'){$attacks=array();foreach($chars as $ch)$attacks[$ch]=array('action'=>'ATTACK');submit_round($ds,$attacks);}
            elseif($current['phase']==='BOSS_RESULT'){act($ds,'escape_final');}
            elseif($current['phase']==='EXPLORE'){act($ds,'move',array('direction'=>'forward'));}
            else throw new RuntimeException('Unexpected reward course phase');
        }
        check(maze_session($ds)['phase']==='CLEAR','Reward course clears through normal combat and boss exit');
        $countAfter=(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('inventory').' i JOIN '.qa_table('item')." it ON it.it_id=i.it_id WHERE i.ch_id=".$chars[0]." AND it.it_5='QADATA:CLEAR_GUARANTEED'")['n'];
        check($countAfter-$countBefore===2,'Guaranteed shared dungeon reward grants exactly two items');
        check(!(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('inventory').' i JOIN '.qa_table('item')." it ON it.it_id=i.it_id WHERE i.ch_id IN (".implode(',',$chars).") AND it.it_5='QADATA:CLEAR_NEVER'")['n'],'Zero-percent reward never grants an item');
        check((int)sql_fetch('SELECT mb_point FROM '.$g5['member_table'].' WHERE mb_id='.qa_quote($member['mb_id']))['mb_point']-$pointBefore===600,'Three surviving characters settle 200 points each to their shared administrator');
        maze_pay_rewards($ds);
        check((int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_maze_reward')." WHERE ds_id=$ds AND state='DONE'")['n']===3,'Point rewards remain settled when retried');
    }else maze_admin_end($ds,'fail');
    check(!(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_maze_inventory').' WHERE ds_id='.$ds.' AND restored=0')['n'],$course['dg_title'].' restores escrow on exit');
}
// Verify exact scheduling from the seeded catalog and patterns, rather than invented test definitions.
$enemy=array(array('id'=>1,'name'=>'QA target','hp'=>10000,'max_hp'=>10000));
foreach(array('[QA] 시계탑 골렘'=>array(array(100,100,100,35,35,35,35,35),array('몸통박치기','몸통박치기','몸통박치기','재생','몸통박치기','몸통박치기','몸통박치기','재생')),'[QA] 삼단계 수호자'=>array(array(100,100,50,70,30,70),array('몸통박치기','일반 공격','산개 탄환','광역 진동','재생','격돌')),'[QA] 구간 감시자'=>array(array(51,50,31,30),array('몸통박치기','광역 진동','광역 진동','격돌')),'[QA] 느린 석상'=>array(array(100,100,100,100),array('대기','격돌','대기','격돌')),'[QA] 순환 정령'=>array(array(100,100,100,100),array('몸통박치기','광역 진동','재생','몸통박치기'))) as $name=>$test){$id=sql_fetch('SELECT monster_id FROM '.qa_table('monster_master').' WHERE name='.qa_quote($name))['monster_id'];$d=monster_profile($id)['shared'];$r=array('definition'=>$d,'guard'=>0,'guard_until'=>0);$actual=array();foreach($test[0] as $i=>$percent){$used=array();$self=array('id'=>0,'name'=>$name,'hp'=>(int)round($d['max_hp']*$percent/100),'max_hp'=>$d['max_hp']);monster_shared_turn($r,$i+1,$self,$enemy,array($self),function($sk)use(&$used){$used[]=$sk['name'];return true;});check(count($used)<=1,$name.' executes at most one skill on turn '.($i+1));$actual[]=$used?str_replace('[QA] ','',$used[0]):'대기';}check($actual===$test[1],$name.' seeded pattern follows expected HP/turn sequence');}
// Both raid adapters instantiate the same canonical phase definition and character skill snapshots.
foreach(array('realtime'=>array('k_battle_realtime','qa_phase_raid'),'mmbraid'=>array('board','qa_mmb')) as $type=>$r){$battle_table=G5_TABLE_PREFIX.$r[0];foreach($chars as $ch)insert_k_battle_unit($ch,'ch',$type,$r[1]);$mo=sql_fetch('SELECT * FROM `'.$battle_table.'_unit` WHERE ra_id='.qa_quote($r[1])." AND unit_type='mo'");check($mo['mp_now']==0&&$mo['mp_max']==0,$type.' monster uses no MP');monster_shared_raid($mo,$r[1],1);check((int)sql_fetch('SELECT COUNT(*) n FROM `'.$battle_table.'_log` WHERE ra_id='.qa_quote($r[1]))['n']>1,$type.' adapter executes canonical monster action');check((int)sql_fetch('SELECT COUNT(*) n FROM `'.$battle_table.'_skill` WHERE ra_id='.qa_quote($r[1]).' AND unified_a_sk_id>0')['n']>0,$type.' character skills compile from canonical definitions');}
foreach(array('realtime'=>array('k_battle_realtime','qa_phase_raid'),'mmbraid'=>array('board','qa_mmb')) as $type=>$r){
    $battle_table=G5_TABLE_PREFIX.$r[0];$raidId=sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE name='.qa_quote('[QA] 훈련 슬라임'))['raid_id'];
    insert_k_battle_unit($raidId,'mo',$type,$r[1]);insert_k_battle_unit($raidId,'mo',$type,$r[1]);
    $dead=(int)sql_fetch('SELECT MAX(rm_id) id FROM `'.$battle_table.'_unit` WHERE ra_id='.qa_quote($r[1]))['id'];sql_query('UPDATE `'.$battle_table.'_unit` SET hp_now=0 WHERE rm_id='.$dead);
    $unit=get_k_unit((int)sql_fetch('SELECT rm_id FROM `'.$battle_table.'_unit` WHERE ra_id='.qa_quote($r[1])." AND unit_type='ch' ORDER BY rm_id LIMIT 1")['rm_id'],'ch');
    $bs=sql_fetch('SELECT bs.bs_id FROM `'.$battle_table.'_skill` bs JOIN '.qa_table('skill')." a ON a.sk_id=bs.unified_a_sk_id WHERE bs.rm_id=".$unit['rm_id'].' AND a.sk_name='.qa_quote('[QA] 휩쓸기'));
    $sk=get_k_battle_skill($bs['bs_id'],'*',true);check($sk['sk_target_cnt']==='all',$type.' preserves ALL target mode when compiling player skills');
    $before=qa_rows('SELECT rm_id,hp_now,unit_type,ra_id FROM `'.$battle_table.'_unit` ORDER BY rm_id');
    $outcome=use_k_skill($sk,$unit,array($unit['rm_id']),'ch',$r[1]);check($outcome===false,$type.' executes player area attack with server-resolved targets');
    $after=array_column(qa_rows('SELECT rm_id,hp_now FROM `'.$battle_table.'_unit`'),null,'rm_id');$hit=0;
    foreach($before as $p){$delta=$p['hp_now']-$after[$p['rm_id']]['hp_now'];if($p['ra_id']===$r[1]&&$p['unit_type']==='mo'&&$p['hp_now']>0){check($delta>0,$type.' area attack damages living enemy '.$p['rm_id']);$hit++;}else check($delta===0,$type.' area attack excludes allies, dead enemies and other encounters '.$p['rm_id']);}
    check($hit===2,$type.' player area attack reaches every living monster');
}
echo 'COMPLETE '.$checks.' QA data checks',PHP_EOL;
