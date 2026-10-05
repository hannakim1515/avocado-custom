<?php
/** Explicit QA-only reset. Default is read-only; back up before --apply. */
require __DIR__.'/qa_bootstrap.php';
function qa_json($a){return json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
function qa_name($suffix,$pk,$name,$values){return qa_upsert($suffix,$pk,'`'.($suffix==='monster_master'?'name':($suffix==='dungeon'?'dg_title':($suffix==='dungeon_maze_status'?'name':'sk_name'))).'`='.qa_quote($name),$values);}
function qa_reset_sessions($dgIds,$chIds){
    if(!$dgIds)return;
    $sessions=qa_rows('SELECT * FROM '.qa_table('dungeon_state').' WHERE dg_id IN ('.implode(',',$dgIds).')');
    foreach($sessions as $session){
        $id=(int)$session['ds_id'];
        foreach(maze_party($id,false) as $p)qa_assert(in_array((int)$p['ch_id'],$chIds,true),'Non-QA participant in QA dungeon');
        if($session['snapshot']===null)continue;
        if(!in_array($session['phase'],array('CLEAR','FAILED','CLOSED'),true))maze_admin_end($id,'fail');
        maze_transaction($id,function($locked){$party=maze_party($locked['ds_id'],false);inventory_boundary_lock_owners(array_column($party,'ch_id'));foreach($party as $p)if(!$p['settled'])maze_settle_member($locked,$p,'QA_RESET',false);});
        maze_pay_rewards($id);
    }
    $ids=array_map('intval',array_column($sessions,'ds_id'));if(!$ids)return;
    $list=implode(',',$ids);
    qa_assert(!(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_maze_inventory').' WHERE ds_id IN ('.$list.') AND restored=0')['n'],'Unsettled QA inventory');
    foreach(qa_rows('SELECT battle_id FROM '.qa_table('dungeon_maze_battle').' WHERE ds_id IN ('.$list.')') as $b)$GLOBALS['db']->query('DELETE FROM '.qa_table('monster_runtime').' WHERE battle_key='.qa_quote('maze:'.$b['battle_id']));
    $GLOBALS['db']->query('DELETE a FROM '.qa_table('dungeon_maze_action').' a JOIN '.qa_table('dungeon_maze_battle').' b ON b.battle_id=a.battle_id WHERE b.ds_id IN ('.$list.')');
    foreach(array('dungeon_combat_event','dungeon_maze_inventory','dungeon_maze_reward','dungeon_maze_vote','dungeon_maze_log','dungeon_maze_battle','dungeon_maze_room','dungeon_maze_member','dungeon_state') as $suffix)$GLOBALS['db']->query('DELETE FROM '.qa_table($suffix).' WHERE ds_id IN ('.$list.')');
}
function qa_raid_clear($suffix,$id,$chIds){
    foreach(qa_rows('SELECT unit_id FROM '.qa_table($suffix.'_unit')." WHERE unit_type='ch' AND ra_id=".qa_quote($id)) as $p)qa_assert(in_array((int)$p['unit_id'],$chIds,true),'Non-QA raid participant');
    foreach(array('skill','buff','log','unit') as $part)$GLOBALS['db']->query('DELETE FROM '.qa_table($suffix.'_'.$part).' WHERE ra_id='.qa_quote($id));
    $GLOBALS['db']->query('DELETE FROM '.qa_table('monster_runtime').' WHERE battle_key='.qa_quote('raid:'.G5_TABLE_PREFIX.$suffix.':'.$id));
}
function qa_monster($name,$hp,$defense,$skills,$description,$phases=array(),$default=true,$pattern='INTERVAL'){
    $definitions=qa_rows('SELECT st_id,st_use_hp FROM '.qa_table('status').' ORDER BY st_id');$stats=array();$slots=array();
    foreach($definitions as $stat){$value=$stat['st_use_hp']?$hp:0;if((int)$stat['st_id']!==(int)$GLOBALS['kb_cf']['mp'])$stats[(int)$stat['st_id']]=$value;$slots[]=$value;}
    $shared=array('stats'=>$stats,'attack_min'=>6,'attack_max'=>10,'pattern'=>$pattern,'default_attack'=>$default,'skills'=>$skills);if($phases)$shared['phases']=$phases;
    $profile=array('dg_mon_hp'=>$hp,'dg_defence'=>$defense,'dg_d_attack_min'=>6,'dg_d_attack_max'=>10,'dg_d_attack_turn'=>1,'dg_d_attack_count'=>1,'dg_strong_code'=>'','dg_strong_value'=>1,'dg_weak_code'=>'','dg_weak_value'=>1,'dg_weak_effect_count'=>0,'dg_weak_effect_turn'=>0,'shared'=>$shared,'k_raid_type'=>'realtime,mmbraid','k_pattern'=>$pattern==='CYCLE'?2:1,'k_default_act'=>$default?2:1,'k_description'=>'[QADATA] '.$description,'k_stats'=>array_pad($slots,10,0));
    $id=qa_name('monster_master','monster_id',$name,array('name'=>$name,'image'=>'','description'=>'[QADATA] '.$description,'enabled'=>1,'profile'=>qa_json($profile)));
    $row=sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE monster_id='.(int)$id);if(!$row['raid_id']){ $next=1+(int)sql_fetch('SELECT COALESCE(MAX(raid_id),0) n FROM '.qa_table('monster_master'))['n'];qa_update('monster_master',array('raid_id'=>$next),'monster_id='.(int)$id);}
    monster_profile($id);return (int)$id;
}
function qa_rule($id,$turn=1,$upper=100,$lower=0){return array('skill_id'=>(int)$id,'turn'=>$turn,'hp_below'=>$upper,'hp_above'=>$lower);}
function qa_check_data(){
    $out=array('database'=>$GLOBALS['qaDatabase'],'characters'=>qa_rows("SELECT ch_id,ch_name,mb_id,ma_id,ch_skill_slot FROM ".qa_table('character')." WHERE ch_name LIKE '[QA]%'"),'dungeons'=>array(),'raids'=>qa_rows("SELECT ra_id,ra_title,ra_state,ra_limit FROM ".qa_table('k_battle_realtime')." WHERE ra_content LIKE '[QADATA]%'"));
    foreach(qa_rows("SELECT * FROM ".qa_table('dungeon')." WHERE dg_description LIKE '[QADATA]%' ORDER BY dg_id") as $dg){$settings=maze_config($dg);$open=qa_rows('SELECT ds_id,ds_ma_id,phase,snapshot FROM '.qa_table('dungeon_state').' WHERE dg_id='.(int)$dg['dg_id']." AND ds_state='S'");qa_assert(count($open)===1 && $open[0]['phase']==='WAITING' && $open[0]['snapshot']===null,'Exactly one pristine waiting room per QA dungeon');$poolIds=array_column($settings['monsters'],'monster_id');foreach(maze_generate($settings) as $room){if($room['payload']['monster'])qa_assert(in_array($room['payload']['monster']['monster_id'],$poolIds,true),'Spawn outside dungeon pool');} $out['dungeons'][]=array('id'=>$dg['dg_id'],'title'=>$dg['dg_title'],'state_id'=>$open[0]['ds_id'],'region'=>$open[0]['ds_ma_id'],'rooms'=>array($settings['rooms_min'],$settings['rooms_max']),'pool'=>$poolIds);}
    foreach(qa_rows("SELECT monster_id FROM ".qa_table('monster_master')." WHERE description LIKE '[QADATA]%'") as $m){$p=monster_profile($m['monster_id']);qa_assert(isset($p['shared'])&&!isset($p['shared']['mp']),'Every QA monster uses MP-free shared patterns');}
    foreach(array('monster_master','k_battle_skill','skill','item','dungeon_maze_status') as $suffix)$out['counts'][$suffix]=(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table($suffix))['n'];
    qa_assert(!(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('dungeon_maze_inventory').' WHERE active_original_id IS NOT NULL')['n'],'No inventory remains isolated');
    qa_assert(maze_readiness()['ready'],'Dungeon storage readiness');
    foreach(array('k_battle_monster','dungeon_monster_read') as $suffix)qa_assert(sql_fetch('SELECT TABLE_TYPE t FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.qa_quote(G5_TABLE_PREFIX.$suffix))['t']==='VIEW','Shared monster view preserved');
    $out['ok']=true;return $out;
}
if(isset($options['check'])){echo qa_json(qa_check_data()),PHP_EOL;exit;}
$qaChars=qa_rows("SELECT * FROM ".qa_table('character')." WHERE ch_name LIKE '[QA]%' AND ch_type<>'npc' ORDER BY ch_id");
$qaChIds=array_map('intval',array_column($qaChars,'ch_id'));qa_assert(count($qaChars)===3,'Expected existing three QA player characters');
foreach($qaChars as $ch)qa_assert($ch['mb_id']===$config['cf_admin'],'QA characters must remain with existing administrator');
$qaDgIds=array_map('intval',array_column(qa_rows("SELECT dg_id FROM ".qa_table('dungeon')." WHERE dg_title LIKE '[QA]%' OR dg_description LIKE '[QADATA]%'"),'dg_id'));
if(!isset($options['apply'])){echo qa_json(array('plan'=>'Reuse three administrator characters; settle and reset QA runs; seed 8 open dungeon courses, 3 realtime raids, MMB and 1:1 boards. No new members.','existing_characters'=>$qaChIds,'existing_qa_dungeons'=>$qaDgIds)),PHP_EOL;exit;}
if(!$qaFixture)qa_assert(!empty($options['backup'])&&is_file($options['backup'])&&filesize($options['backup'])>10000,'A complete main database backup is required');
qa_assert((int)sql_fetch('SELECT GET_LOCK('.qa_quote(G5_TABLE_PREFIX.'qa_server_seed').',0) acquired')['acquired']===1,'Another QA seeder is running');
try{
    $adminBefore=sql_fetch('SELECT mb_id,mb_password,mb_level FROM '.$g5['member_table'].' WHERE mb_id='.qa_quote($config['cf_admin']));
    $memberCountBefore=(int)sql_fetch('SELECT COUNT(*) n FROM '.$g5['member_table'])['n'];
    qa_assert(maze_readiness()['ready'],'Install current dungeon storage first');
    qa_assert(!(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('inventory_journal')." WHERE state NOT IN ('DONE','CANCELLED') AND ch_id IN (".implode(',',$qaChIds).')')['n'],'Unfinished QA inventory journal requires review');
    qa_reset_sessions($qaDgIds,$qaChIds);
    qa_update('config',array('cf_open'=>1,'cf_dungeon_open'=>1,'cf_dungeon_map'=>1,'cf_use_map'=>1,'cf_dungeon_count'=>0,'cf_dungeon_time'=>87600,'cf_dungeon_reset'=>date('Y-m-d H:00:00'),'cf_dungeon_enter'=>1000,'cf_skill_count_max'=>12,'cf_use_status_battle'=>1),'1=1');
    $config=sql_fetch('SELECT * FROM '.$g5['config_table']);
    $plaza=sql_fetch('SELECT ma_id FROM '.qa_table('newmap_config').' WHERE ma_name='.qa_quote('[QA] 중앙 광장'))['ma_id'];
    $forest=sql_fetch('SELECT ma_id FROM '.qa_table('newmap_config').' WHERE ma_name='.qa_quote('[QA] 안개 숲'))['ma_id'];qa_assert($plaza&&$forest,'QA map references');
    $qaStatValues=array(array(240,22,8,60,18,10),array(320,16,12,40,8,6),array(200,12,6,100,14,14));
    foreach($qaChars as $index=>$ch){qa_update('character',array('ma_id'=>$plaza,'ch_skill_slot'=>12,'ch_state'=>'승인','ch_search'=>0,'ch_search_date'=>date('Y-m-d')),'ch_id='.(int)$ch['ch_id']);$values=$qaStatValues[$index];foreach(qa_rows('SELECT st_id FROM '.qa_table('status').' ORDER BY st_id') as $i=>$st)qa_update('status_character',array('sc_value'=>0,'sc_max'=>$values[$i]??10),'ch_id='.(int)$ch['ch_id'].' AND st_id='.(int)$st['st_id']);}
    qa_update('member',array('ch_id'=>$qaChIds[0]),'mb_id='.qa_quote($config['cf_admin']));
    $hp=(int)sql_fetch('SELECT st_id FROM '.qa_table('status').' WHERE st_use_hp=1 LIMIT 1')['st_id'];$mp=(int)$kb_cf['mp'];
    $baseAttack=sql_fetch('SELECT * FROM '.qa_table('skill').' WHERE sk_cate='.qa_quote('QADATA').' AND sk_name='.qa_quote('[QA] 강격'));
    $baseHeal=sql_fetch('SELECT * FROM '.qa_table('skill').' WHERE sk_cate='.qa_quote('QADATA').' AND sk_name='.qa_quote('[QA] 치유'));qa_assert($baseAttack&&$baseHeal,'Canonical QA player skills');
    foreach(array(array('[QA] 휩쓸기','ALL','적','공격',0,0,3,2),array('[QA] 난사','RANDOM','적','공격',0,0,4,2),array('[QA] 파티 치유','SINGLE','아군전체','스탯회복',$hp,0,8,2),array('[QA] 지속 타격','SINGLE','적','공격',0,3,0,2),array('[QA] 약화 타격','SINGLE','적','스탯강화',0,2,5,2)) as $s){$v=$s[3]==='스탯회복'?$baseHeal:$baseAttack;unset($v['sk_id']);$v=array_replace($v,array('sk_name'=>$s[0],'sk_dungeon_target_mode'=>$s[1],'sk_target'=>$s[2],'sk_function'=>$s[3],'sk_mod_st_id'=>$s[4],'sk_keep_limit'=>$s[5],'sk_limit'=>$s[7],'sk_descript'=>'[QADATA] '.$s[0].' 기능 확인'));if($s[3]==='스탯강화'){$v['sk_mod_enermy']='방어';$v['sk_status_code']='';$v['sk_mod_type']='-';}$sid=qa_name('skill','sk_id',$s[0],$v);foreach(array(1,2) as $level)qa_upsert('skill_level','sl_id','sk_id='.(int)$sid.' AND sl_level='.$level,array('sk_id'=>$sid,'sl_level'=>$level,'sl_name'=>'Lv.'.$level,'sl_set_value'=>$s[6]+($level-1)*3,'sl_use_value'=>$level===1?4:6));}
    foreach(qa_rows('SELECT * FROM '.qa_table('skill')." WHERE sk_cate='QADATA'") as $sk){unified_skill_compile_definition($sk['sk_id'],$sk);foreach($qaChars as $ch){qa_upsert('skill_has','sh_id','sk_id='.(int)$sk['sk_id'].' AND ch_id='.(int)$ch['ch_id'],array('sk_id'=>$sk['sk_id'],'ch_id'=>$ch['ch_id'],'sh_level'=>1,'sh_limit'=>0,'sh_use'=>1,'sh_name'=>'','sh_descript'=>'','sh_datetime'=>date('Y-m-d H:i:s')));}}
    foreach($qaChIds as $id)unified_skill_sync_character($id,true);
    $itemIds=array();foreach(qa_rows("SELECT * FROM ".qa_table('item')." WHERE it_5 LIKE 'QADATA:%'") as $it)$itemIds[substr($it['it_5'],7)]=(int)$it['it_id'];
    foreach(array(array('HEAL_BIG','[QA] 대형 회복약','스탯회복',80,$hp),array('MP_BIG','[QA] 대형 정신 회복약','스탯회복',40,$mp),array('CLEAR_GUARANTEED','[QA] 확정 보상 표식','',0,0),array('CLEAR_CHANCE','[QA] 확률 보상 표식','',0,0),array('CLEAR_NEVER','[QA] 미지급 보상 표식','',0,0)) as $s){$id=qa_upsert('item','it_id','it_5='.qa_quote('QADATA:'.$s[0]),array('it_5'=>'QADATA:'.$s[0],'it_name'=>$s[1],'it_category'=>'QADATA','it_content'=>'[QADATA] 서버 테스트용 '.$s[1],'it_type'=>$s[2],'it_value'=>$s[3],'st_id'=>$s[4],'it_use_battle_able'=>$s[2]?1:0,'it_use_able'=>$s[2]?1:0,'it_use'=>1,'it_use_sell'=>1,'it_sell'=>10,'it_img'=>''));$itemIds[$s[0]]=(int)$id;}
    // Top up after real settlement; preserve existing inventory IDs and equipped gear.
    foreach($qaChars as $ch){inventory_boundary_query('START TRANSACTION');try{inventory_boundary_lock_owners(array($ch['ch_id']));foreach(array('HEAL'=>12,'MP'=>10,'REVIVE'=>5,'CLEANSE'=>8,'HEAL_BIG'=>8,'MP_BIG'=>8,'KEY'=>3,'MAT_A'=>12,'MAT_B'=>12,'SELL'=>3,'SWORD'=>1,'ARMOR'=>1) as $role=>$count){$it=sql_fetch('SELECT * FROM '.qa_table('item').' WHERE it_id='.$itemIds[$role]);$n=(int)sql_fetch('SELECT COUNT(*) n FROM '.qa_table('inventory').' WHERE ch_id='.(int)$ch['ch_id'].' AND it_id='.$itemIds[$role]." AND in_use=''")['n'];for($i=$n;$i<$count;$i++)qa_insert('inventory',array('ch_id'=>$ch['ch_id'],'ch_name'=>$ch['ch_name'],'it_id'=>$it['it_id'],'it_name'=>$it['it_name'],'in_5'=>'QADATA:SEED','in_sdatetime'=>date('Y-m-d H:i:s')));}inventory_boundary_query('COMMIT');}catch(Throwable $e){sql_query('ROLLBACK',false);throw $e;}}
    $ms=array();foreach(array(array('bash','[QA] 몸통박치기','DAMAGE','enemy','SINGLE',1,12,16),array('aoe','[QA] 광역 진동','DAMAGE','enemy','ALL',1,8,12),array('regen','[QA] 재생','HEAL','self','SINGLE',1,24,24),array('random','[QA] 산개 탄환','DAMAGE','enemy','RANDOM',2,10,14),array('guard','[QA] 돌갑옷','GUARD','self','SINGLE',1,25,25),array('strong','[QA] 격돌','DAMAGE','enemy','SINGLE',1,24,30),array('group_heal','[QA] 무리 재생','HEAL','ally','ALL',1,12,12)) as $s){$shared=array('type'=>$s[2],'target'=>$s[3],'mode'=>$s[4],'count'=>$s[5],'min'=>$s[6],'max'=>$s[7],'stat_id'=>0,'factor'=>0,'duration'=>1,'cue'=>$s[1].' 준비');$ms[$s[0]]=(int)qa_name('k_battle_skill','sk_id',$s[1],array('sk_name'=>$s[1],'sk_content'=>'[QADATA] MP·재사용 대기 없이 설정 패턴대로 실행','sk_info'=>qa_json(array('monster_shared'=>$shared)),'sk_target'=>$s[3],'sk_target_cnt'=>strtolower($s[4]),'unit_type'=>'mo','raid_type'=>'realtime,mmbraid','sk_mp'=>0,'sk_cool'=>0));}
    $mon=array();
    $mon['slime']=qa_monster('[QA] 훈련 슬라임',70,0,array(qa_rule($ms['bash'])),'동시 출현 2~4마리, 매 턴 몸통박치기');
    $mon['keeper']=qa_monster('[QA] 미궁 파수꾼',160,2,array(qa_rule($ms['bash']),qa_rule($ms['aoe'],3)),'이벤트 시험장에만 등록된 파수꾼');
    $mon['golem']=qa_monster('[QA] 시계탑 골렘',480,2,array(qa_rule($ms['bash']),qa_rule($ms['regen'],4,35)),'HP 35% 이하에서 4턴마다 재생 우선, 한 턴 한 기술');
    $phases=array(array('below'=>100,'actions'=>array($ms['bash'],0,$ms['aoe'],$ms['guard'])),array('below'=>50,'actions'=>array($ms['random'],$ms['aoe'])),array('below'=>30,'actions'=>array($ms['regen'],$ms['strong'])));
    $mon['phase']=qa_monster('[QA] 삼단계 수호자',480,2,array(),'100→50→30% 행동 단계, 회복해도 이전 단계로 돌아가지 않음',$phases);
    $mon['range']=qa_monster('[QA] 구간 감시자',420,1,array(qa_rule($ms['bash'],1,100,50),qa_rule($ms['aoe'],1,50,30),qa_rule($ms['strong'],1,30,0)),'100~50 / 50~30 / 30~0% 현재 HP 구간만 실행. 경계는 다음 구간');
    $mon['idle']=qa_monster('[QA] 느린 석상',240,0,array(qa_rule($ms['strong'],2)),'일반 공격 꺼짐, 홀수 턴 대기·짝수 턴 격돌',array(),false);
    $mon['reward']=qa_monster('[QA] 보상 시험 인형',40,0,array(qa_rule($ms['bash'])),'짧은 클리어·보상 확인용');
    $mon['cycle']=qa_monster('[QA] 순환 정령',300,1,array(qa_rule($ms['bash'],1),qa_rule($ms['aoe'],2),qa_rule($ms['regen'],3)),'몸통박치기→광역 진동→재생 3턴 순환',array(),false,'CYCLE');
    $mon['healer']=qa_monster('[QA] 치유 슬라임',90,0,array(qa_rule($ms['bash']),qa_rule($ms['group_heal'],3)),'다수 몬스터 중 아군 전체 회복을 3턴마다 우선');
    $status=array();foreach(array(array('중독',3,array(array('type'=>'DOT_HP','value'=>5))),array('둔화',2,array(array('type'=>'STAT_MODIFIER','stat_id'=>5,'mode'=>'percent','value'=>-30))),array('기절',1,array(array('type'=>'ACTION_DISABLE','value'=>1))),array('회복 저하',3,array(array('type'=>'HEAL_MODIFIER','value'=>-50)))) as $s){$status[$s[0]]=(int)qa_name('dungeon_maze_status','status_id','[QA] '.$s[0],array('name'=>'[QA] '.$s[0],'description'=>'[QADATA] 함정과 해독제 테스트','duration'=>$s[1],'enabled'=>1,'components'=>qa_json($s[2])));}
    $base=array('background'=>'','min_players'=>1,'rooms_min'=>5,'rooms_max'=>5,'branches_min'=>0,'branches_max'=>0,'weights'=>array('EMPTY'=>1,'SEARCH'=>0,'TREASURE'=>0,'TRAP'=>0),'dead_end_percent'=>0,'encounter_percent'=>0,'escape_percent'=>100,'trap_damage'=>8,'boss'=>true,'events'=>array());
    $courses=array(
        array('[QA] 안개 미궁','복합 탐험: 분기·다수 출현·회복·함정·보스',$forest,array('rooms_min'=>9,'rooms_max'=>12,'branches_min'=>1,'branches_max'=>2,'weights'=>array('EMPTY'=>2,'SEARCH'=>3,'TREASURE'=>2,'TRAP'=>2),'encounter_percent'=>40,'escape_percent'=>60),array(array('slime',8,0,2,4),array('healer',2,0,1,2),array('phase',1,1,1,1)),'mixed'),
        array('[QA] 01 동시 출현 시험','슬라임 2~4마리 또는 치유 슬라임 2마리 동시 출현',$plaza,array('encounter_percent'=>100,'boss'=>false),array(array('slime',8,0,2,4),array('healer',2,0,2,2)),'none'),
        array('[QA] 02 턴 기술 우선순위','골렘: 매 턴 몸통박치기, HP 35% 이하 4·8·12턴 재생 우선',$plaza,array('rooms_min'=>3,'rooms_max'=>3),array(array('golem',1,1,1,1)),'none'),
        array('[QA] 03 HP 단계 패턴','수호자: 100→50→30% 순서 초기화, 회복 후 단계 유지',$plaza,array('rooms_min'=>3,'rooms_max'=>3),array(array('phase',1,1,1,1)),'none'),
        array('[QA] 04 HP 구간 제한','감시자: 정확히 50%,30%에서 다음 구간 기술만 사용',$plaza,array('rooms_min'=>3,'rooms_max'=>3),array(array('range',1,1,1,1)),'none'),
        array('[QA] 05 함정과 상태이상','중독·둔화·기절·회복 저하 함정, 해독제·회복·부활 시험',$forest,array('rooms_min'=>10,'rooms_max'=>10,'weights'=>array('EMPTY'=>0,'SEARCH'=>0,'TREASURE'=>0,'TRAP'=>1),'encounter_percent'=>35,'boss'=>false),array(array('idle',1,0,1,1)),'status'),
        array('[QA] 06 이벤트 선택과 출현','선택지: 아이템·회복·등록 몬스터 전투, 미등록 출현 없음',$forest,array('rooms_min'=>8,'rooms_max'=>8,'weights'=>array('EMPTY'=>0,'SEARCH'=>1,'TREASURE'=>0,'TRAP'=>0),'boss'=>false),array(array('keeper',1,0,2,2)),'event'),
        array('[QA] 07 보상과 순환 행동','인형 보스·순환 정령, 확정 2개·50% 1개·0% 보상',$plaza,array('rooms_min'=>4,'rooms_max'=>4,'encounter_percent'=>100),array(array('cycle',1,0,1,1),array('reward',1,1,1,1)),'reward')
    );
    foreach($courses as $index=>$course){list($title,$desc,$region,$overrides,$pool,$kind)=$course;$settings=array_replace($base,$overrides);$events=array();
        if($kind==='mixed'||$kind==='event')$events[]=array('enabled'=>true,'room_kind'=>'SEARCH','weight'=>10,'prompt'=>'[QA] 낡은 선반 앞에서 선택하세요.','type'=>'NONE','choices'=>array(array('label'=>'약병 얻기','type'=>'ITEM','it_id'=>$itemIds['HEAL'],'count'=>2,'text'=>'회복약 2개 획득'),array('label'=>'잠시 쉬기','type'=>'HEAL','target'=>'ALL','value'=>30,'text'=>'파티 체력 회복'),array('label'=>'몬스터와 싸우기','type'=>'MONSTER','monster_id'=>$kind==='event'?$mon['keeper']:$mon['slime'],'text'=>'이 던전 출현 목록의 몬스터가 나타났다.')));
        if($kind==='mixed'){$events[]=array('enabled'=>true,'room_kind'=>'TREASURE','weight'=>1,'prompt'=>'결정 상자를 발견했다.','type'=>'ITEM','it_id'=>$itemIds['MAZE_REWARD'],'count'=>1,'text'=>'미궁 결정 획득');$events[]=array('enabled'=>true,'room_kind'=>'TRAP','weight'=>1,'prompt'=>'독안개 함정','type'=>'TRAP','target'=>'ALL','value'=>8,'status_id'=>$status['중독'],'text'=>'중독 함정 작동');}
        if($kind==='status')foreach($status as $label=>$sid)$events[]=array('enabled'=>true,'room_kind'=>'TRAP','weight'=>1,'prompt'=>'[QA] '.$label.' 함정','type'=>'TRAP','target'=>'ALL','value'=>6,'status_id'=>$sid,'text'=>$label.' 적용');
        $settings['events']=$events;$id=(int)qa_name('dungeon','dg_id',$title,array('dg_title'=>$title,'dg_use'=>1,'dg_count'=>3,'dg_per_s'=>0,'dg_per_e'=>100,'dg_point'=>$kind==='reward'?200:50,'ma_ids'=>'||'.$region.'||','dg_description'=>'[QADATA] '.$desc,'dg_settings'=>qa_json($settings)));
        $db->query('DELETE FROM '.qa_table('dungeon_monster_pool').' WHERE dg_id='.$id);foreach($pool as $entry)qa_insert('dungeon_monster_pool',array('dg_id'=>$id,'monster_id'=>$mon[$entry[0]],'weight'=>$entry[1],'enabled'=>1,'is_boss'=>$entry[2],'min_count'=>$entry[3],'max_count'=>$entry[4]));
        $db->query('DELETE FROM '.qa_table('dungeon_item').' WHERE dg_id='.$id);foreach(array(array('CLEAR_GUARANTEED',2,100),array('CLEAR_CHANCE',1,50),array('CLEAR_NEVER',1,0)) as $reward)qa_insert('dungeon_item',array('dg_id'=>$id,'it_id'=>$itemIds[$reward[0]],'di_count'=>$reward[1],'di_percent'=>$reward[2]));
        maze_config(sql_fetch('SELECT * FROM '.qa_table('dungeon').' WHERE dg_id='.$id));
        qa_insert('dungeon_state',array('dg_id'=>$id,'dg_title'=>$title,'ds_state'=>'S','ds_ma_id'=>$region,'ds_datetime'=>date('Y-m-d H:i:s'),'phase'=>'WAITING','snapshot'=>null,'version'=>0,'expires_at'=>date('Y-m-d H:i:s',time()+87600*3600),'created_at'=>date('Y-m-d H:i:s')));
    }
    // These are the existing feature's shared runtime tables, plus board post tables.
    // No separate monster definitions or extra dungeon configuration tables are created.
    foreach(array('unit','skill','buff','log') as $part)if(!system_install_schema(G5_TABLE_PREFIX.'board_'.$part))$db->query('CREATE TABLE '.qa_table('board_'.$part).' LIKE '.qa_table('k_battle_realtime_'.$part));
    foreach(array('bo_11','bo_12') as $column)if(!isset(system_install_schema(G5_TABLE_PREFIX.'board')['columns'][$column]))$db->query('ALTER TABLE '.qa_table('board').' ADD `'.$column.'` text NOT NULL');
    $group=qa_upsert('group','gr_id','gr_id=\'qa_test\'',array('gr_id'=>'qa_test','gr_subject'=>'[QA] 전투 테스트','gr_admin'=>$config['cf_admin'],'gr_device'=>'both','gr_use_access'=>0));
    foreach(array('qa_mmb'=>'k_mmbraid','qa_battle'=>'mmb_battle') as $boardId=>$skin){$values=array('bo_table'=>$boardId,'gr_id'=>$group,'bo_subject'=>$boardId==='qa_mmb'?'[QA] MMB 단계 패턴 레이드':'[QA] 1:1 배틀','bo_skin'=>$skin,'bo_mobile_skin'=>$skin,'bo_device'=>'both','bo_list_level'=>1,'bo_read_level'=>1,'bo_write_level'=>2,'bo_comment_level'=>2,'bo_upload_level'=>2,'bo_download_level'=>1,'bo_html_level'=>2,'bo_page_rows'=>20,'bo_mobile_page_rows'=>20,'bo_table_width'=>100,'bo_image_width'=>900,'bo_subject_len'=>100,'bo_mobile_subject_len'=>100,'bo_use_search'=>1,'bo_upload_count'=>1,'bo_upload_size'=>10485760,'bo_content_head'=>'<p>[QADATA] 기존 관리자 캐릭터를 전환하며 시험하세요.</p>');if($boardId==='qa_mmb')$values=array_replace($values,array('bo_1'=>'true','bo_1_subj'=>'mmbraid','bo_2'=>sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE monster_id='.$mon['phase'])['raid_id'],'bo_2_subj'=>'true','bo_3'=>'true','bo_4'=>'true','bo_5'=>'true','bo_6'=>'true','bo_7'=>'','bo_10_subj'=>'all','bo_11'=>'true'));
        qa_upsert('board','bo_table','bo_table='.qa_quote($boardId),$values);
        $write=G5_TABLE_PREFIX.'write_'.$boardId;if(!system_install_schema($write))$db->query(str_replace('__TABLE_NAME__',$write,file_get_contents($root.'/adm/sql_write.sql')));
        if($boardId==='qa_mmb')foreach(array('wr_k_raid_log','wr_k_raid_admin') as $column)if(!isset(system_install_schema($write)['columns'][$column]))$db->query('ALTER TABLE `'.$write.'` ADD `'.$column.'` text NOT NULL');
        if($boardId==='qa_battle')foreach(array('wr_battle_ch_value','wr_battle_re_ch_value','wr_battle_re_ch') as $column)if(!isset(system_install_schema($write)['columns'][$column]))$db->query('ALTER TABLE `'.$write.'` ADD `'.$column.'` int(11) NOT NULL DEFAULT 0');
    }
    qa_update('battle_config',array('bc_proc'=>'자동','bc_before'=>'공격력','bc_after'=>'공격력','bc_damage_proc'=>'공통대미지'),'bc_id=1');
    require_once $root.'/extend/00_functions.php';require_once $root.'/k_battle/extend/raid/0_default.php';require_once $root.'/k_battle/extend/raid/2_turn.php';require_once $root.'/lib/monster_adapters.lib.php';
    $k_stat=array_map('intval',array_column(qa_rows('SELECT k_sc_id FROM '.qa_table('unified_combat_stat_map').' WHERE is_active=1 ORDER BY map_id'),'k_sc_id'));
    foreach(array('qa_realtime_raid'=>array('golem','[QA] 시계탑 훈련 레이드'),'qa_phase_raid'=>array('phase','[QA] HP 단계 실시간 레이드'),'qa_range_raid'=>array('range','[QA] HP 구간 실시간 레이드')) as $rid=>$r){qa_raid_clear('k_battle_realtime',$rid,$qaChIds);qa_upsert('k_battle_realtime','ra_id','ra_id='.qa_quote($rid),array('ra_id'=>$rid,'ra_title'=>$r[1],'ra_content'=>'[QADATA] '.$r[1].' / 던전과 같은 공용 몬스터 정의','ra_list_img'=>'','ra_bg_img'=>'','ra_state'=>0,'ra_limit'=>3,'ra_limit_now'=>0,'ra_reload'=>'turn','ra_reload_time'=>5,'ra_turn_type'=>'speed','ra_mo_auto'=>'auto','ra_type'=>'pve','ra_turn'=>1,'ra_count'=>0,'now_turn'=>0,'ra_system'=>'normal','ra_time_limit'=>300,'ra_time_start'=>0,'ra_reward_money'=>200,'ra_reward_exp'=>50,'ra_reward_title'=>0,'ra_reward_item'=>$itemIds['MAZE_REWARD'],'ra_system_msg'=>'[QADATA] 입장 후 레이드를 시작하세요.'));$battle_table=G5_TABLE_PREFIX.'k_battle_realtime';$raidId=sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE monster_id='.$mon[$r[0]])['raid_id'];qa_assert(insert_k_battle_unit($raidId,'mo','realtime',$rid)!==false,'Realtime monster insertion');qa_upsert('k_raid_list','li_id','ra_ids='.qa_quote($rid),array('li_title'=>$r[1],'ra_ids'=>$rid,'raid_type'=>'realtime','li_use'=>1));}
    qa_raid_clear('board','qa_mmb',$qaChIds);$battle_table=G5_TABLE_PREFIX.'board';$raidId=sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE monster_id='.$mon['phase'])['raid_id'];qa_assert(insert_k_battle_unit($raidId,'mo','mmbraid','qa_mmb')!==false,'MMB shared monster insertion');
    $battle_table=G5_TABLE_PREFIX.'k_battle_realtime';$raidId=sql_fetch('SELECT raid_id FROM '.qa_table('monster_master').' WHERE monster_id='.$mon['slime'])['raid_id'];insert_k_battle_unit($raidId,'mo','realtime','qa_range_raid');
    qa_upsert('k_raid_list','li_id','ra_ids=\'qa_mmb\'',array('li_title'=>'[QA] MMB 단계 패턴 레이드','ra_ids'=>'qa_mmb','raid_type'=>'mmbraid','li_use'=>1));
    qa_assert($adminBefore===sql_fetch('SELECT mb_id,mb_password,mb_level FROM '.$g5['member_table'].' WHERE mb_id='.qa_quote($config['cf_admin'])),'Administrator login identity changed');
    qa_assert($memberCountBefore===(int)sql_fetch('SELECT COUNT(*) n FROM '.$g5['member_table'])['n'],'Member count changed');
    echo qa_json(qa_check_data()),PHP_EOL;
}finally{$db->query('SELECT RELEASE_LOCK('.qa_quote(G5_TABLE_PREFIX.'qa_server_seed').')');}
