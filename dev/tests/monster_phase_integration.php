<?php
/** HP phase sequencing is shared by Dungeon, Maze, realtime and MMB adapters. */
if(PHP_SAPI!=='cli')exit(1);
define('_GNUBOARD_',true);define('G5_TABLE_PREFIX','phase_');
require dirname(__DIR__,2).'/lib/monster_shared.lib.php';
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label."\n";}
$catalog=array(10=>true,11=>true,12=>true);
$phases=monster_shared_validate_phases(array(array('below'=>100,'actions'=>array(10,11,0)),array('below'=>50,'actions'=>array(12,0)),array('below'=>25,'actions'=>array(11))),$catalog);
check(count($phases)===3 && $phases[1]['below']===50,'administrator thresholds and ordered actions accepted');
$many=array();for($threshold=100;$threshold>=1;$threshold--)$many[]=array('below'=>$threshold,'actions'=>array(0));
check(count(monster_shared_validate_phases($many,$catalog))===100,'every distinct integer HP threshold can define a phase');
try{monster_shared_validate_phases(array(array('below'=>100,'actions'=>array(10)),array('below'=>100,'actions'=>array(11))),$catalog);throw new RuntimeException('duplicate threshold accepted');}
catch(RuntimeException $e){check($e->getMessage()!=='duplicate threshold accepted','duplicate HP threshold rejected');}
function skill($id){return array('id'=>$id,'name'=>'기술 '.$id,'type'=>'DAMAGE','target'=>'enemy','mode'=>'SINGLE','count'=>1,'min'=>$id,'max'=>$id,'stat_id'=>0,'factor'=>0,'mp'=>0,'cooldown'=>0,'hp_below'=>100,'turn'=>1);}
$definition=array('name'=>'검증 몬스터','max_hp'=>100,'mp'=>10,'attack_min'=>1,'attack_max'=>1,'default_attack'=>true,'enmity_enabled'=>true,'skills'=>array(skill(10),skill(11),skill(12)),'phases'=>$phases,'stats'=>array());
$runtime=array('definition'=>$definition,'mp'=>10,'cooldowns'=>array(),'guard'=>0,'guard_until'=>0);
$enemy=array(array('id'=>1,'hp'=>100,'max_hp'=>100,'name'=>'모험가'));
$seen=array();$turn=1;
foreach(array(100,90,80,50,75,75,25) as $hp){
    if($turn===6)$runtime['cooldowns'][12]=100;
    $self=array('id'=>9,'hp'=>$hp,'max_hp'=>100,'name'=>'검증 몬스터');
    monster_shared_turn($runtime,$turn,$self,$enemy,array($self),function($chosen)use(&$seen){$seen[]=(int)$chosen['id'];return true;});
    $turn++;
}
check($seen===array(10,11,0,12,0,12,11),'sequence repeats, threshold resets, healing keeps later phase and old cooldown cannot replace a planned skill');
check($runtime['phase_index']===2 && $runtime['phase_step']===0,'phase and next action persist in runtime state');
$preview_runtime=array('definition'=>$definition,'mp'=>10,'cooldowns'=>array(12=>100),'guard'=>0,'guard_until'=>0,'phase_index'=>1,'phase_step'=>0);
$preview=monster_shared_forecast($preview_runtime,8,array('id'=>9,'hp'=>40,'max_hp'=>100,'name'=>'검증 몬스터'),$enemy);
check($preview['actions'][0]['name']==='기술 12','preview shows the configured phase skill despite an old cooldown');
$preview_runtime['mp']=0;$preview_runtime['definition']['skills'][2]['mp']=999;$preview_runtime['definition']['skills'][2]['cooldown']=999;
$used=array();monster_shared_turn($preview_runtime,8,array('id'=>9,'hp'=>35,'max_hp'=>100),$enemy,array(array('id'=>9,'hp'=>35,'max_hp'=>100)),function($chosen)use(&$used){$used[]=$chosen['id'];});
check($used===array(12) && !isset($preview_runtime['mp'],$preview_runtime['cooldowns']),'old zero MP and resource metadata cannot suppress the next phase action');
$definition['phases']=array();$definition['pattern']='INTERVAL';$definition['skills']=array(skill(10),skill(12));$definition['skills'][1]['turn']=4;$definition['skills'][1]['hp_below']=35;
$runtime=array('definition'=>$definition,'mp'=>0,'cooldowns'=>array(10=>999,12=>999),'guard'=>0,'guard_until'=>0);$used=array();
for($turn=1;$turn<=8;$turn++){$hp=$turn<4?36:35;$self=array('id'=>9,'hp'=>$hp,'max_hp'=>100);monster_shared_turn($runtime,$turn,$self,$enemy,array($self),function($chosen)use(&$used,$turn){$used[$turn][]=$chosen['id'];return true;});}
check($used===array(1=>array(10),2=>array(10),3=>array(10),4=>array(12),5=>array(10),6=>array(10),7=>array(10),8=>array(12)),'35 percent regeneration replaces the every-turn attack on global turns 4 and 8');
