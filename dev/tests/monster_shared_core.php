<?php
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);require dirname(__DIR__,2).'/lib/monster_shared.lib.php';
function ok($value,$label){if(!$value)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label."\n";}
$skill=array('id'=>1,'name'=>'강격','type'=>'DAMAGE','min'=>20,'max'=>20,'stat_id'=>3,'factor'=>2,'target'=>'enemy','mode'=>'SINGLE','count'=>1,'mp'=>4,'cooldown'=>1,'turn'=>1,'hp_below'=>100);
$d=array('mp'=>12,'stats'=>array(3=>3),'attack_min'=>7,'attack_max'=>7,'default_attack'=>true,'pattern'=>'INTERVAL','skills'=>array($skill));
$runtime=array('definition'=>$d,'mp'=>12,'cooldowns'=>array(),'guard'=>0,'guard_until'=>0);
$self=array('id'=>0,'hp'=>120,'max_hp'=>120);$targets=array(array('id'=>1,'hp'=>100),array('id'=>2,'hp'=>100),array('id'=>3,'hp'=>0));$seen=array();
for($turn=1;$turn<=7;$turn++)monster_shared_turn($runtime,$turn,$self,$targets,array($self),function($s,$t,$amount)use(&$seen){$seen[]=array($s['id'],$amount,count($t));});
ok($seen===array_fill(0,7,array(1,26,1)) && !isset($runtime['mp'],$runtime['cooldowns'],$runtime['definition']['skills'][0]['mp']),'every scheduled monster skill executes without MP or cooldown limits');
$skill['mode']='ALL';ok(count(monster_shared_targets($skill,$targets,array(),$self))===2,'ALL excludes dead targets');
$skill['mode']='RANDOM';$skill['count']=2;ok(count(monster_shared_targets($skill,$targets,array(),$self))===2,'RANDOM chooses distinct living targets');
$skill['target']='self';ok(monster_shared_targets($skill,$targets,array(),$self)[0]['id']===0,'self target');
$skill['target']='ally';ok(count(monster_shared_targets($skill,$targets,array($self),$self))===1,'ally target');
$d['default_attack']=false;$d['skills'][0]['hp_below']=50;$runtime=array('definition'=>$d,'mp'=>12,'cooldowns'=>array(),'guard'=>50,'guard_until'=>1);
ok(!monster_shared_turn($runtime,2,$self,$targets,array($self),function(){throw new RuntimeException('Condition ignored');}) && $runtime['guard']===0,'HP condition, idle and guard expiry');
$d['skills'][0]['hp_below']=100;$d['skills'][0]['turn']=2;$d['pattern']='CYCLE';$runtime=array('definition'=>$d,'mp'=>12,'cooldowns'=>array(),'guard'=>0,'guard_until'=>0);$turns=array();
for($turn=1;$turn<=4;$turn++)if(monster_shared_turn($runtime,$turn,$self,$targets,array($self),function(){}))$turns[]=$turn;
ok($turns===array(2,4),'cycle scheduling');
$runtime['definition']['skills']=array($skill,$skill);$runtime['definition']['skills'][0]['target']='self';$runtime['definition']['skills'][0]['turn']=1;$runtime['definition']['pattern']='INTERVAL';$runtime['cooldowns']=array();$runtime['mp']=12;$calls=0;
monster_shared_turn($runtime,5,$self,$targets,array($self),function()use(&$calls){$calls++;return false;});ok($calls===1,'dead actor stops remaining skills');
