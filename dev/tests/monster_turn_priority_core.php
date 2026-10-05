<?php
/** Skill priority, forecasts and old runtime intents in the common scheduler. */
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);require dirname(__DIR__,2).'/lib/monster_shared.lib.php';
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
function priority_skill($id,$turn,$type='DAMAGE',$upper=100){return array('id'=>$id,'name'=>'Skill '.$id,'type'=>$type,'target'=>$type==='HEAL'?'self':'enemy','mode'=>'SINGLE','count'=>1,'min'=>10,'max'=>10,'stat_id'=>0,'factor'=>0,'turn'=>$turn,'hp_below'=>$upper);}
$definition=array('name'=>'Priority monster','max_hp'=>100,'stats'=>array(),'skills'=>array(priority_skill(1,1),priority_skill(2,4,'HEAL')),'pattern'=>'INTERVAL','default_attack'=>true,'attack_min'=>1,'attack_max'=>1);
$self=array('id'=>9,'hp'=>40,'max_hp'=>100,'name'=>'Monster');$enemy=array(array('id'=>1,'hp'=>100,'max_hp'=>100,'name'=>'Player'));
foreach(array(false,true) as $enmity){
    $definition['enmity_enabled']=$enmity;$runtime=array('definition'=>$definition,'guard'=>0,'guard_until'=>0,'mp'=>0,'cooldowns'=>array(1=>999,2=>999));
    $seen=array();$healing_targets=array();
    for($turn=1;$turn<=12;$turn++)monster_shared_turn($runtime,$turn,$self,$enemy,array($self),function($skill,$targets)use(&$seen,&$healing_targets,$turn){$seen[$turn][]=$skill['id'];if($skill['type']==='HEAL')$healing_targets[]=array_column($targets,'id');return true;});
    check($seen===array(1=>array(1),2=>array(1),3=>array(1),4=>array(2),5=>array(1),6=>array(1),7=>array(1),8=>array(2),9=>array(1),10=>array(1),11=>array(1),12=>array(2)),'one skill per turn, regeneration replaces the every-turn attack with enmity '.(int)$enmity);
    check($healing_targets===array(array(9),array(9),array(9)),'prioritized healing targets only the monster');
}
$definition['enmity_enabled']=true;$definition['skills']=array(priority_skill(2,4,'HEAL'),priority_skill(1,1));
$runtime=array('definition'=>$definition,'guard'=>0,'guard_until'=>0);
check(monster_shared_schedule($runtime,4,$self)[0]['id']===2,'longer interval wins in either registration order');
$runtime['definition']['skills']=array(priority_skill(1,1),priority_skill(2,4,'HEAL'),priority_skill(3,6));
check(monster_shared_schedule($runtime,12,$self)[0]['id']===3,'six-turn skill wins when one, four and six-turn rules overlap');
$runtime['definition']['skills'][2]['hp_below']=35;
check(monster_shared_schedule($runtime,12,$self)[0]['id']===2,'HP-ineligible longer interval cannot suppress an eligible four-turn skill');
$runtime['definition']['skills'][1]['hp_below']=35;
check(monster_shared_schedule($runtime,12,$self)[0]['id']===1,'every-turn skill runs when special skills fail their HP condition');
$runtime['definition']['skills']=array(priority_skill(1,1),priority_skill(2,4,'HEAL'),priority_skill(3,4));
check(monster_shared_schedule($runtime,4,$self)[0]['id']===2,'equal intervals use the first registered eligible skill');
$runtime['definition']['skills'][1]['hp_below']=35;
check(monster_shared_schedule($runtime,4,$self)[0]['id']===3,'ineligible first tie allows the next eligible registered skill');
$runtime['definition']['pattern']='CYCLE';
check(count(monster_shared_schedule($runtime,4,$self))===1&&monster_shared_schedule($runtime,4,$self)[0]['id']===3&&monster_shared_schedule($runtime,5,$self)[0]['id']===1,'cycle mode still uses its turn slots and executes one skill');
$runtime=array('definition'=>$definition,'guard'=>0,'guard_until'=>0,'intent'=>array('turn'=>4,'slots'=>array(0,1)));
$forecast=monster_shared_forecast($runtime,4,$self,$enemy);$used=array();
monster_shared_turn($runtime,4,$self,$enemy,array($self),function($skill)use(&$used){$used[]=$skill['id'];return true;});
check(array_column($forecast['actions'],'name')===array('Skill 2')&&$used===array(2),'legacy multi-skill intent is replaced in both forecast and execution');
$runtime=array('definition'=>$definition,'guard'=>0,'guard_until'=>0);$runtime['definition']['skills'][0]['hp_below']=35;$runtime['definition']['skills'][0]['hp_above']=0;
monster_shared_prepare($runtime,4,array_replace($self,array('hp'=>35)));
$runtime['definition']['skills'][1]['hp_below']=35;$forecast=monster_shared_forecast($runtime,4,$self,$enemy);
check(array_column($forecast['actions'],'name')===array('일반 공격'),'explicit HP ranges recheck healed HP before applying priority');
