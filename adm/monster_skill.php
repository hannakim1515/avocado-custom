<?php
$sub_menu='730110';include_once './_common.php';auth_check($auth[$sub_menu],'r');
require_once G5_PATH.'/lib/monster_shared.lib.php';require_once __DIR__.'/dungeon_admin.lib.php';
if($_SERVER['REQUEST_METHOD']==='POST'){require __DIR__.'/monster_skill_form.php';return;}
if(!empty($_GET['skill_id']))goto_url('./monster_skill_form.php?skill_id='.(int)$_GET['skill_id']);
$g5['title']='몬스터 스킬 관리';include './admin.head.php';
if(!monster_shared_ready()){echo '<div class="local_desc01 local_desc">시스템 설치 / 업데이트에서 몬스터 관리를 업데이트하세요.</div>';include './admin.tail.php';return;}
$types=array('DAMAGE'=>'HP 피해','HEAL'=>'HP 회복','MP_HEAL'=>'MP 회복','GUARD'=>'피해 감소');
$targets=array('enemy'=>'상대 파티','ally'=>'몬스터 아군','self'=>'자신');$modes=array('SINGLE'=>'한 명','ALL'=>'전체','RANDOM'=>'무작위');
$search=trim(stripslashes($_GET['stx']??''));$list=array_values(monster_shared_skills());
if($search!=='')$list=array_values(array_filter($list,function($skill)use($search){return mb_stripos($skill['name'],$search)!==false;}));
$total=count($list);$page=max(1,(int)($_GET['page']??1));$pages=(int)ceil($total/20);$list=array_slice($list,($page-1)*20,20);
?>
<section id="anc_001"><h2 class="h2_frm">몬스터 스킬 목록</h2>
<ul class="anchor"><li><a href="monster.php">몬스터 목록</a></li><li><a href="#anc_001">몬스터 스킬 목록</a></li></ul>
<?php if(!empty($_GET['saved'])): ?><div class="local_desc01 local_desc" role="status">스킬을 저장했습니다.</div><?php endif; ?>
<div class="local_ov01 local_ov"><a href="monster_skill.php" class="ov_listall">전체목록</a> 검색된 스킬 <strong><?=$total?></strong>개</div>
<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get"><label for="stx">스킬명</label> <input type="text" class="frm_input" id="stx" name="stx" value="<?=da_h($search)?>"> <button class="btn_submit" type="submit">검색</button></form>
<p class="local_desc01 local_desc">스킬을 만든 뒤 몬스터 작성폼의 보유 스킬에서 추가하세요. 하나의 스킬을 여러 몬스터가 함께 사용할 수 있습니다.</p>
<div class="btn_add01 btn_add btn_list"><a href="monster_skill_form.php" id="bo_add">스킬 추가</a></div>
<div class="tbl_head01 tbl_wrap"><table><caption>몬스터 스킬 목록</caption><colgroup><col class="grid_4"><col><col><col class="grid_3"><col class="grid_2"><col class="grid_2"></colgroup><thead><tr><th scope="col">기능</th><th scope="col">스킬명</th><th scope="col">행동 예고</th><th scope="col">대상</th><th scope="col">수치</th><th scope="col">관리</th></tr></thead><tbody>
<?php foreach($list as $i=>$skill): ?><tr class="bg<?=$i%2?>"><td><?=da_h($types[$skill['type']]??$skill['type'])?></td><td><a href="monster_skill_form.php?skill_id=<?=(int)$skill['id']?>"><?=da_h($skill['name'])?></a></td><td><?=da_h($skill['cue']??'')?></td><td><?=da_h(($targets[$skill['target']]??'').' · '.($modes[$skill['mode']]??''))?></td><td><?=(int)$skill['min']?>~<?=(int)$skill['max']?></td><td><a class="btn btn_03" href="monster_skill_form.php?skill_id=<?=(int)$skill['id']?>">수정</a></td></tr><?php endforeach; ?>
<?php if(!$list): ?><tr><td class="empty_table" colspan="6">등록된 스킬이 없습니다.</td></tr><?php endif; ?></tbody></table></div>
<?=get_paging(G5_IS_MOBILE?$config['cf_mobile_pages']:$config['cf_write_pages'],$page,$pages,'?stx='.urlencode($search).'&amp;page=')?>
</section><?php include './admin.tail.php'; ?>
