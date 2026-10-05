<?php
if(!defined('_GNUBOARD_'))exit;
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/monster_phase.css">';
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/combat_editor_shell.css"><script src="'.G5_ADMIN_URL.'/js/combat_editor_shell.js"></script>';
$options=da_options();$catalog=monster_shared_skills();$options['monster_skills']=array_column($catalog,'name','id');$shared=$profile['shared']??array();
if(!$shared && $id){
    $shared=array('stats'=>array(),'attack_min'=>$profile['dg_d_attack_min']??0,'attack_max'=>$profile['dg_d_attack_max']??0,'pattern'=>(int)($profile['k_pattern']??1)===2?'CYCLE':'INTERVAL','default_attack'=>(int)($profile['k_default_act']??1)===2,'skills'=>array());
    foreach($definitions as $index=>$stat)if($index<10)$shared['stats'][(int)$stat['st_id']]=(int)($profile['k_stats'][$index]??0);
}
foreach($shared['skills']??array() as $index=>$entry) $shared['skills'][$index]=array_replace(array('turn'=>1,'hp_below'=>100,'hp_above'=>0),$entry,array('skill_id'=>$entry['skill_id']??$entry['id']??0));
$pattern_mode=$_POST['pattern_mode']??(!empty($profile['shared']['phases'])?'phases':($initial_shared?'turns':($id?'legacy_k':'phases')));
$phase_rows=$profile['shared']['phases']??array(array('below'=>100,'actions'=>array(0)));
function monster_input_row($key,$label,$value,$min=0){echo '<tr><th scope="row"><label for="'.mh($key).'">'.mh($label).'</label></th><td><input class="frm_input" id="'.mh($key).'" name="'.mh($key).'" type="number" step="'.(substr($key,-6)==='_value'?'any':'1').'" min="'.$min.'" value="'.mh($value).'" size="8"></td></tr>';}
function monster_table_open($title){echo '<section><h2 class="h2_frm">'.mh($title).'</h2><div class="tbl_frm01 tbl_wrap editor-fields"><table><caption>'.mh($title).'</caption><colgroup><col class="grid_4"><col></colgroup><tbody>';}
function monster_table_close(){echo '</tbody></table></div></section>';}
?>
<div class="local_desc01 local_desc">몬스터를 한 번 만들고 던전·레이드에서 선택하세요. 공용 스킬은 여러 몬스터에게 각각 추가할 수 있습니다.</div>
<?php if($message)echo '<div class="local_desc01 local_desc" role="status">'.mh($message).'</div>'; ?>
<form id="fmonsterform" name="fmonsterform" method="post" action="monster_form.php" autocomplete="off" enctype="multipart/form-data"><input type="hidden" name="token" value="<?=mh(get_token())?>"><input type="hidden" name="monster_id" value="<?=$id?>">
<nav class="monster-form-tabs" role="tablist" aria-label="몬스터 편집 항목"><button type="button" data-monster-tab="monster-basic" aria-selected="true">기본 정보</button><button type="button" data-monster-tab="monster-actions" aria-selected="false">행동 패턴</button><button type="button" data-monster-tab="monster-stats" aria-selected="false">능력치</button><button type="button" data-monster-tab="monster-pattern" aria-selected="false">전투 특성</button></nav>
<p class="frm_info">탭에서 설정을 바꾼 뒤 화면 아래 저장 버튼을 누르세요.</p>
<div id="monster-basic">
<?php monster_table_open($id?'몬스터 수정':'몬스터 등록'); ?>
<tr><th scope="row"><label for="name">이름</label></th><td><input type="text" id="name" name="name" class="frm_input required" required value="<?=mh($row['name']??'')?>" size="40"></td></tr>
<tr><th scope="row"><label for="image">이미지</label></th><td><input id="image" name="image" type="text" inputmode="url" class="frm_input" value="<?=mh($row['image']??'')?>" size="60"><p class="frm_info">이미지 주소를 붙여넣거나 파일을 올리세요. 없어도 등록할 수 있습니다.</p><input name="image_file" aria-label="몬스터 이미지 업로드" type="file" accept="image/png,image/jpeg,image/gif,image/webp"></td></tr>
<?php monster_input_row('hp','최대 HP',$profile['dg_mon_hp']??100,1);monster_input_row('attack_min','일반 공격 최소 피해',$shared['attack_min']??$profile['dg_d_attack_min']??10);monster_input_row('attack_max','일반 공격 최대 피해',$shared['attack_max']??$profile['dg_d_attack_max']??10); ?>
<tr><th scope="row">기본 행동</th><td><label><input type="checkbox" name="default_attack" value="1" <?=!isset($shared['default_attack'])||$shared['default_attack']?'checked':''?>> 기존 턴 조건 패턴에서 해당 턴에 등록된 스킬이 없을 때 일반 공격</label><p class="frm_info">몬스터는 MP·재사용 대기 제한 없이 설정한 패턴을 실행합니다.</p></td></tr>
<tr><th scope="row">사용 여부</th><td><label><input type="checkbox" name="enabled" value="1" <?=!isset($row['enabled'])||$row['enabled']?'checked':''?>> 새 전투에 사용</label></td></tr>
<tr><th scope="row">레이드 사용</th><td><?php $raid_types=explode(',',$profile['k_raid_type']??'realtime,mmbraid');foreach(array('realtime'=>'실시간 레이드','mmbraid'=>'MMB 레이드') as $type=>$label): ?><label><input type="checkbox" name="raid_types[]" value="<?=mh($type)?>" <?=in_array($type,$raid_types,true)?'checked':''?>> <?=mh($label)?></label> <?php endforeach; ?><p class="frm_info">던전 출현은 던전 편집의 몬스터 출현 탭에서 선택합니다.</p></td></tr>
<tr><th scope="row"><label for="pattern_mode">행동 패턴 방식</label></th><td><select id="pattern_mode" name="pattern_mode" class="frm_input"><option value="phases" <?=$pattern_mode==='phases'?'selected':''?>>HP 구간별 행동 순서</option><option value="turns" <?=$pattern_mode==='turns'?'selected':''?>>기존 턴 조건 반복</option><?php if($id && !$initial_shared): ?><option value="legacy_k" <?=$pattern_mode==='legacy_k'?'selected':''?>>기존 K 레이드 패턴 유지</option><?php endif; ?></select><p class="frm_info">HP 구간별 행동 순서는 던전·실시간·MMB 레이드에 적용됩니다. 기존 K 패턴은 새 방식을 저장하기 전까지 유지됩니다.</p></td></tr>
<?php monster_table_close(); ?></div>
<div id="monster-actions" hidden><section id="monster-phases"><h2 class="h2_frm">HP 구간별 행동 순서</h2><p class="frm_info">100% 단계부터 시작합니다. 다음 단계의 HP 기준에 도달하면 이전 순서를 중단하고 새 단계의 첫 행동부터 실행합니다. 예: 100·50·30%로 설정하면 100~50%, 50~30%, 30~0%로 구간이 나뉩니다. 정확히 50%면 두 번째, 30%면 세 번째 단계입니다. 한 단계의 마지막 행동 뒤에는 첫 행동으로 돌아가며, 회복해도 이전 단계로 돌아가지 않습니다.</p>
<input type="hidden" name="phases" id="monster-phases-value" value="<?=mh(json_encode($phase_rows,JSON_UNESCAPED_UNICODE))?>"><div id="monster-phase-list"></div><button type="button" class="btn btn_02" id="monster-phase-add">HP 단계 추가</button><p class="frm_info">행동에 사용할 스킬은 <a href="monster_skill.php">몬스터 스킬 관리</a>에서 만듭니다. 일반 공격도 순서에 넣을 수 있습니다.</p></section>
<section id="monster-abilities"><h2 class="h2_frm">기존 턴 조건 스킬</h2>
<p class="frm_info">스킬을 추가하거나 각 행의 수정·삭제로 편집한 뒤, 아래 저장을 누르세요. <a href="monster_skill.php">몬스터 스킬 목록</a>에서 공용 스킬 정의를 관리합니다.</p>
<p class="frm_info">각 스킬에 HP 상한·하한을 입력하세요. 상한 100·하한 50은 HP 100% 이하~50% 초과에서만 사용하며, 50%가 되면 중단합니다. 상한 50·하한 30은 50% 이하~30% 초과에서 사용합니다. 하한 0은 쓰러질 때까지 사용할 수 있습니다. 몬스터 행동 시점의 HP로 판정하므로 회복하면 다시 해당 구간의 조건을 따릅니다.</p>
<p class="frm_info">‘설정한 턴마다 반복’은 전체 전투의 턴 번호가 기준입니다. 1은 매턴, 4는 4·8·12턴이며 HP 구간 조건도 만족해야 합니다. 해당 턴에 사용 가능한 스킬이 없으면 기본 행동 설정을 따릅니다.</p>
<p class="frm_info">조건을 만족하는 스킬이 겹치면 사용 턴 값이 큰 스킬 하나만 사용합니다. 같은 값이면 위에 등록한 스킬이 우선입니다. 예: 몸통박치기 1 · 회복 4 → 1~3턴 몸통박치기, 4턴 회복만 사용, 5~7턴 몸통박치기, 8턴 회복만 사용합니다.</p>
<?php da_json_editor('abilities','abilities',$shared['skills']??array()); ?></section>
<p id="monster-legacy-note" class="local_desc01 local_desc" hidden>기존 K 레이드 패턴을 유지합니다. HP 구간별 행동 순서를 만들려면 기본 정보에서 행동 패턴 방식을 변경하세요.</p></div>
<div id="monster-stats" hidden>
<?php monster_table_open('설명과 능력치'); ?>
<tr><th scope="row"><label for="description">설명</label></th><td><textarea name="description" id="description" rows="3"><?=mh($row['description']??'')?></textarea></td></tr>
<?php monster_input_row('defense','방어력',$profile['dg_defence']??0);
foreach($definitions as $stat){$sid=(int)$stat['st_id'];if($sid===$hp_id||$sid===$mp_id)continue;monster_input_row('stats['.$sid.']',$stat['st_name'],$shared['stats'][$sid]??0);}monster_table_close(); ?></div><div id="monster-pattern" hidden><?php monster_table_open('행동 패턴과 특성'); ?>
<tr class="legacy-pattern-rule"><th scope="row"><label for="pattern">기존 스킬 발동 방식</label></th><td><?php da_select('pattern',array('INTERVAL'=>'설정한 턴마다 반복','CYCLE'=>'턴 순서대로 반복'),$shared['pattern']??'INTERVAL',null); ?><p class="frm_info">기존 턴 조건 반복 방식을 선택한 경우에만 적용됩니다.</p></td></tr>
<?php foreach(array('dg_strong_code'=>'강점 연동 특성','dg_weak_code'=>'약점 연동 특성') as $key=>$label){echo '<tr><th scope="row"><label for="'.$key.'">'.$label.'</label></th><td>';da_select($key,$options['codes'],$profile[$key]??'');echo '</td></tr>';}foreach(array('dg_strong_value'=>'강점 피해 배율','dg_weak_value'=>'약점 피해 배율','dg_weak_effect_count'=>'그로기 발동 적중 수','dg_weak_effect_turn'=>'그로기 지속 턴') as $key=>$label)monster_input_row($key,$label,$profile[$key]??(strpos($key,'value')!==false?1:0));monster_table_close(); ?>
</div>
<div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit">저장</button> <a href="monster.php">목록</a></div></form>
<script type="application/json" id="dungeon-editor-options"><?=json_encode($options,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script><script src="<?=G5_ADMIN_URL?>/js/dungeon_admin.js"></script>
<script src="<?=G5_ADMIN_URL?>/js/monster_phase_editor.js"></script>
