<?php
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);require dirname(__DIR__,2).'/lib/monster_shared.lib.php';
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label."\n";}
$sk=array('id'=>1,'name'=>'강격','cue'=>'팔을 들어 올린다.','type'=>'DAMAGE','target'=>'enemy','mode'=>'SINGLE','count'=>1,'min'=>20,'max'=>20,'stat_id'=>0,'factor'=>0,'turn'=>1,'hp_below'=>100,'mp'=>4,'cooldown'=>1);
$d=array('enmity_enabled'=>true,'mp'=>12,'stats'=>array(),'skills'=>array($sk),'pattern'=>'INTERVAL','default_attack'=>true,'attack_min'=>5,'attack_max'=>5);
$r=array('definition'=>$d,'mp'=>12,'cooldowns'=>array(),'guard'=>0,'guard_until'=>0);
$self=array('id'=>9,'name'=>'슬라임','hp'=>100,'max_hp'=>100);$party=array(array('id'=>1,'name'=>'아린','hp'=>100),array('id'=>2,'name'=>'세라','hp'=>100),array('id'=>3,'name'=>'퇴장','hp'=>100,'state'=>'LEFT'),array('id'=>4,'name'=>'사망','hp'=>0));
monster_shared_enmity($r,2,26);check(monster_shared_targets($sk,$party,array(),$self,$r)[0]['id']===2,'actual damage selects highest living threat');
$other=$r;monster_shared_enmity($r,1,0,true);check($r['enmity'][1]==36 && $other['enmity'][2]===26 && !isset($other['enmity'][1]),'provoke top plus margin and independent monster instances');
check(monster_shared_targets($sk,$party,array(),$self,$r)[0]['id']===1,'provoke redirects SINGLE');
monster_shared_enmity($r,2,20);check(monster_shared_targets($sk,$party,array(),$self,$r)[0]['id']===2,'damage can overtake provoke');
$r['enmity_target']=1;$r['enmity'][1]=$r['enmity'][2];check(monster_shared_targets($sk,$party,array(),$self,$r)[0]['id']===1,'ties keep current target');
$r['enmity'][3]=999;$r['enmity'][4]=999;check(monster_shared_targets($sk,$party,array(),$self,$r)[0]['id']===1,'dead and exited actors excluded');
$all=$sk;$all['mode']='ALL';check(count(monster_shared_targets($all,$party,array(),$self,$r))===2,'ALL unaffected by provoke');
$random=$sk;$random['mode']='RANDOM';$random['count']=2;check(count(monster_shared_targets($random,$party,array(),$self,$r))===2,'RANDOM keeps distinct living targets');
$before=$r;$f=monster_shared_forecast($r,1,$self,$party);check($before===$r && $f['actions'][0]['cue']===$sk['cue'] && $f['actions'][0]['scope']==='위협 1위','forecast is pure and exposes cue and scope');
$used=array();monster_shared_turn($r,1,$self,$party,array($self),function($s,$targets,$amount)use(&$used){$used[]=array($s['name'],$targets[0]['id'],$amount);});check($used===array(array('강격',1,20)) && !isset($r['mp']),'forecast and execution share the configured pattern without MP');
check(monster_shared_forecast($r,2,$self,$party)['actions'][0]['name']==='강격','next forecast repeats the skill without cooldown suppression');
check(!monster_shared_forecast($r,2,$self,$party,true)['actions'],'groggy forecast reports no action');
$party[0]['hp']=0;monster_shared_prune($r,$party);$party[0]['hp']=100;check(!isset($r['enmity'][1]),'death removes threat before resurrection');
$legacy=$r;unset($legacy['definition']['enmity_enabled']);$old=$legacy;monster_shared_enmity($legacy,1,100,true);check($old===$legacy && monster_shared_forecast($legacy,1,$self,$party)===array(),'legacy snapshots preserve old behavior');
$r['definition']['skills'][0]['hp_below']=50;$r['definition']['default_attack']=false;check(!monster_shared_forecast($r,3,$self,$party)['actions'],'HP condition suppresses forecast');$self['hp']=40;check(monster_shared_forecast($r,3,$self,$party)['actions'][0]['condition']==='HP 50% 이하','HP conditional forecast labels threshold');

$r['definition']['default_attack']=true;$r['definition']['skills'][0]['hp_below']=50;$self['hp']=100;
monster_shared_prepare($r,5,$self);$self['hp']=40;$prepared=monster_shared_forecast($r,5,$self,$party);$used=array();monster_shared_turn($r,5,$self,$party,array($self),function($sk)use(&$used){$used[]=$sk['name'];});
check($prepared['actions'][0]['name']==='일반 공격' && $used===array('일반 공격'),'HP changes after preparation cannot substitute an unannounced attack');
monster_shared_prepare($r,6,$self);check(monster_shared_forecast($r,6,$self,$party)['actions'][0]['name']==='강격','next preparation evaluates new HP threshold');
