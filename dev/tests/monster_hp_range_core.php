<?php
/** Range boundaries, turn schedules and intent refresh in the shared engine. */
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);require dirname(__DIR__,2).'/lib/monster_shared.lib.php';
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
function ranged_skill($id,$upper,$lower,$turn=1){return array('id'=>$id,'name'=>'기술 '.$id,'type'=>'DAMAGE','target'=>'enemy','mode'=>'SINGLE','min'=>$id,'max'=>$id,'turn'=>$turn,'hp_below'=>$upper,'hp_above'=>$lower);}
$skills=array(ranged_skill(1,100,50),ranged_skill(2,50,30),ranged_skill(3,30,0));
$definition=array('name'=>'구간 검증','max_hp'=>1000,'stats'=>array(),'skills'=>$skills,'pattern'=>'INTERVAL','default_attack'=>false,'attack_min'=>7,'attack_max'=>7,'enmity_enabled'=>true);
$runtime=array('definition'=>$definition,'guard'=>0,'guard_until'=>0,'mp'=>0,'cooldowns'=>array(1=>999,2=>999,3=>999));
$self=array('id'=>9,'hp'=>1000,'max_hp'=>1000,'name'=>'몬스터');$enemy=array(array('id'=>1,'hp'=>100,'name'=>'모험가'));
$used=array();foreach(array(1000,750,501,500,499,301,300,299,1) as $index=>$hp){
 $self['hp']=$hp;monster_shared_turn($runtime,$index+1,$self,$enemy,array($self),function($skill)use(&$used){$used[]=$skill['id'];});
}
check($used===array(1,1,1,2,2,2,3,3,3),'100~50, 50~30 and 30~0 stop previous skills at exact boundaries');
check(!isset($runtime['mp'],$runtime['cooldowns']),'range selection keeps the resource-free monster contract');
$self['max_hp']=201;
$self['hp']=101;check(monster_shared_hp_matches($skills[0],$self),'HP ratio above 50% is not rounded down');
$self['hp']=100;check(!monster_shared_hp_matches($skills[0],$self)&&monster_shared_hp_matches($skills[1],$self),'fractional maximum HP selects the correct 50% side');
$self['hp']=61;check(monster_shared_hp_matches($skills[1],$self),'HP ratio above 30% remains in middle range');
$self['hp']=60;check(monster_shared_hp_matches($skills[2],$self),'HP ratio below 30% selects final range');
$self['max_hp']=1000;$self['hp']=600;monster_shared_prepare($runtime,10,$self);$before=$runtime;
$self['hp']=500;$forecast=monster_shared_forecast($runtime,10,$self,$enemy);$used=array();
monster_shared_turn($runtime,10,$self,$enemy,array($self),function($skill)use(&$used){$used[]=$skill['id'];});
check($forecast['actions'][0]['name']==='기술 2'&&$forecast['actions'][0]['condition']==='HP 30% 초과 · 50% 이하'&&$used===array(2),'HP drop after preview replaces the expired range at execution');
check($before['intent']['slots']===array(0),'forecast does not mutate the persisted intent');
$runtime['definition']['skills']=array($skills[0]);$self['hp']=500;unset($runtime['intent']);$calls=0;
check(!monster_shared_turn($runtime,11,$self,$enemy,array($self),function()use(&$calls){$calls++;})&&$calls===0,'single 100~50 skill stops completely at 50%');
$runtime['definition']['default_attack']=true;unset($runtime['intent']);$used=array();
monster_shared_turn($runtime,12,$self,$enemy,array($self),function($skill)use(&$used){$used[]=$skill['id'];});
check($used===array(0),'no matching skill follows the configured ordinary attack fallback');
$runtime['definition']=$definition;$runtime['definition']['skills']=array(ranged_skill(2,50,30,4));$self['hp']=450;unset($runtime['intent']);
check(monster_shared_schedule($runtime,4,$self)[0]['id']===2&&!monster_shared_schedule($runtime,5,$self),'interval turn and bounded HP conditions both apply');
$runtime['definition']['pattern']='CYCLE';$runtime['definition']['skills']=array(ranged_skill(1,100,50,1),ranged_skill(2,50,30,2));
check(monster_shared_schedule($runtime,2,$self)[0]['id']===2&&!monster_shared_schedule($runtime,3,$self),'cycle turn and bounded HP conditions both apply');
$runtime['definition']=$definition;$self['hp']=750;unset($runtime['intent']);
check(monster_shared_schedule($runtime,13,$self)[0]['id']===1,'turn conditions evaluate current HP after healing');
check(monster_shared_validate_hp_range(array('hp_below'=>35))===array('hp_below'=>35,'hp_above'=>0),'old upper-only conditions remain valid with lower default zero');
foreach(array(array(50,50),array(30,50),array(101,0),array(100,-1),array(100,30.5),array(0,0)) as $range){
 $rejected=false;try{monster_shared_validate_hp_range(array('hp_below'=>$range[0],'hp_above'=>$range[1]));}catch(RuntimeException $e){$rejected=true;}
 check($rejected,'invalid upper/lower range rejected: '.implode('/',$range));
}
