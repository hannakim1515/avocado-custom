<?php
$sub_menu='730110';include_once './_common.php';auth_check($auth[$sub_menu],'r');
require_once G5_PATH.'/lib/monster_shared.lib.php';require_once __DIR__.'/dungeon_admin.lib.php';
$id=(int)($_REQUEST['skill_id']??0);$message=!empty($_GET['saved'])?'스킬을 저장했습니다. 몬스터 관리에서 사용할 몬스터에 추가하세요.':'';
$row=$id?sql_fetch('SELECT * FROM `'.$g5['k_skill_table'].'` WHERE sk_id='.$id):array();
$skill=$row?monster_shared_skill($row):array();$options=da_options();
$types=array('DAMAGE'=>'HP 피해','HEAL'=>'HP 회복','MP_HEAL'=>'MP 회복','GUARD'=>'받는 피해 감소 (%)');
if(monster_shared_ready() && $_SERVER['REQUEST_METHOD']==='POST') {
    auth_check($auth[$sub_menu],'w');check_token();
    try {
        if($id && !$skill)throw new RuntimeException('수정할 공용 스킬이 없습니다.');
        $name=trim(stripslashes($_POST['name']??''));if($name==='')throw new RuntimeException('스킬 이름을 입력하세요.');
        $type=$_POST['type']??'';$target=$_POST['target']??'';$mode=$_POST['mode']??'';
        if(!isset($types[$type]) || !in_array($target,array('enemy','ally','self'),true) || !in_array($mode,array('SINGLE','ALL','RANDOM'),true))throw new RuntimeException('효과와 대상을 선택하세요.');
        $min=(int)($_POST['min']??0);$max=(int)($_POST['max']??0);$factor=$_POST['factor']??0;$stat=(int)($_POST['stat_id']??0);
        if($min<0 || $max<$min || !is_numeric($factor) || $factor<0 || ($stat && !isset($options['stats'][$stat])))throw new RuntimeException('스킬 수치와 기준 스탯을 확인하세요.');
        if($type==='GUARD' && ($max>90 || $factor!=0))throw new RuntimeException('피해 감소는 0~90%, 스탯 계수는 0으로 설정하세요.');
        $count=(int)($_POST['count']??1);$duration=(int)($_POST['duration']??1);
        if($count<1||$count>12||$duration<1)throw new RuntimeException('대상 수·지속 턴을 확인하세요.');
        $skill=array_replace($skill?:array(),array('cue'=>trim(stripslashes($_POST['cue']??'')),'type'=>$type,'target'=>$target,'mode'=>$mode,'count'=>$count,'min'=>$min,'max'=>$max,'stat_id'=>$stat,'factor'=>(float)$factor,'duration'=>$duration));
        unset($skill['mp'],$skill['cooldown']);
        $info=json_decode($row['sk_info']??'{}',true)?:array();$info['monster_shared']=$skill;
        $sets='sk_name='.monster_quote($name).',sk_content='.monster_quote(stripslashes($_POST['description']??'')).',sk_info='.monster_quote(json_encode($info,JSON_UNESCAPED_UNICODE)).",unit_type='mo',raid_type='realtime,mmbraid',sk_use='1',si_id=0,sk_value=".$min.",default_calc='',bonus_calc='',sk_turn=".$duration.',sk_cool=0,sk_target='.monster_quote($target).',sk_target_cnt='.monster_quote($mode==='ALL'?'all':'single').",sk_icon='',sk_mp=0,sc_id=0,target_sc=0";
        if($id)sql_query('UPDATE `'.$g5['k_skill_table'].'` SET '.$sets.' WHERE sk_id='.$id);
        else {sql_query('INSERT INTO `'.$g5['k_skill_table'].'` SET '.$sets);$id=(int)sql_insert_id();}
        goto_url('./monster_skill_form.php?skill_id='.$id.'&saved=1');
    }catch(Throwable $e){$message=$e->getMessage();$row['sk_name']=stripslashes($_POST['name']??'');$row['sk_content']=stripslashes($_POST['description']??'');foreach(array('cue','type','target','mode','min','max','stat_id','factor','count','duration') as $key)if(isset($_POST[$key]))$skill[$key]=stripslashes($_POST[$key]);}
}
$g5['title']=$id?'몬스터 스킬 정보 수정':'몬스터 스킬 정보 등록';include './admin.head.php';
if(!monster_shared_ready()){echo '<div class="local_desc01 local_desc">몬스터 관리 DB 업데이트가 필요합니다. <a href="system_install.php">DB 업데이트</a></div>';include './admin.tail.php';return;}
?>
<div class="local_desc01 local_desc">스킬 하나를 만든 뒤 여러 몬스터의 행동 패턴에서 선택할 수 있습니다. 피해·회복량은 ‘최소~최대 수치 + 기준 스탯 × 계수’로 계산합니다. 몬스터는 MP·재사용 대기 제한 없이 설정한 순서나 턴 조건에 따라 스킬을 실행합니다.</div>
<?php if($message)echo '<div class="local_desc01 local_desc" role="status">'.da_h($message).'</div>'; ?>
<form id="fmonsterskillform" name="fmonsterskillform" action="monster_skill_form.php" method="post" enctype="multipart/form-data" autocomplete="off"><input type="hidden" name="token" value="<?=da_h(get_token())?>"><input type="hidden" name="skill_id" value="<?=$id?>">
<section id="anc_001"><div class="tbl_frm01 tbl_wrap">
<table><caption>몬스터 스킬 기본 정보</caption><colgroup><col style="width:130px"><col style="width:130px"><col></colgroup><tbody>
<tr><th scope="row"><label for="name">스킬명</label></th><td colspan="2"><input type="text" class="frm_input required" id="name" name="name" value="<?=da_h($row['sk_name']??'')?>" required size="40"></td></tr>
<tr><th scope="row"><label for="description">설명</label></th><td colspan="2"><textarea id="description" name="description" rows="3"><?=da_h($row['sk_content']??'')?></textarea></td></tr>
<tr><th scope="row"><label for="cue">행동 예고</label></th><td colspan="2"><input type="text" id="cue" name="cue" class="frm_input" size="70" value="<?=da_h($skill['cue']??'')?>"><span class="frm_info">대사 / 모션 설명. 예: 거대한 팔을 들어 올린다. 다음 몬스터 행동 전에 표시합니다.</span></td></tr>
</tbody></table><br>
<table><caption>몬스터 스킬 기능과 사용 설정</caption><colgroup><col style="width:130px"><col style="width:130px"><col></colgroup><tbody>
<tr><th scope="rowgroup" rowspan="5">기능</th><td colspan="2"><label for="type" class="sound_only">효과</label><?php da_select('type',$types,$skill['type']??'DAMAGE',null); ?></td></tr>
<tr><th scope="row" class="bo-left bo-right txt-center">① 기본수식</th><td>
<label for="min">최소</label> <input id="min" name="min" type="number" class="frm_input" min="0" value="<?=da_h($skill['min']??10)?>" style="width:80px"> ~
<label for="max">최대</label> <input id="max" name="max" type="number" class="frm_input" min="0" value="<?=da_h($skill['max']??10)?>" style="width:80px">
</td></tr>
<tr><th scope="row" class="bo-left bo-right txt-center">② 스탯 보정</th><td>
<label for="stat_id" class="sound_only">기준 스탯</label><?php da_select('stat_id',$options['stats'],$skill['stat_id']??0,'고정 수치만 사용'); ?> ×
<label for="factor" class="sound_only">기준 스탯 계수</label><input id="factor" name="factor" type="number" class="frm_input" min="0" step="any" value="<?=da_h($skill['factor']??0)?>" style="width:80px">
<span class="frm_info">기본수식 결과에 스탯 × 계수를 더합니다. 피해 감소는 최대 90%, 계수 0으로 설정합니다.</span></td></tr>
<tr><th scope="row" class="bo-left bo-right txt-center">③ 적용 대상</th><td>
<label for="target" class="sound_only">대상 진영</label><?php da_select('target',array('enemy'=>'상대 파티','ally'=>'몬스터 아군','self'=>'자신'),$skill['target']??'enemy',null); ?>
<label for="mode" class="sound_only">대상 선택</label><?php da_select('mode',array('SINGLE'=>'위협 1위 한 명','ALL'=>'전체 생존자','RANDOM'=>'지정 수만큼 무작위'),$skill['mode']??'SINGLE',null); ?>
<label for="count">무작위 대상 수</label> <input id="count" name="count" type="number" class="frm_input" min="1" max="12" value="<?=da_h($skill['count']??1)?>" style="width:60px"></td></tr>
<tr><th scope="row" class="bo-left bo-right txt-center">④ 지속 효과</th><td><label for="duration">피해 감소 지속</label> <input id="duration" name="duration" type="number" class="frm_input" min="1" value="<?=da_h($skill['duration']??1)?>" style="width:60px"> 턴</td></tr>
</tbody></table></div></section>
<div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit" accesskey="s">확인</button> <a href="monster_skill.php">목록</a></div></form>
<?php include './admin.tail.php'; ?>
