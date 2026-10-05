<?php
$sub_menu='730150';include_once './_common.php';require_once __DIR__.'/dungeon_admin.lib.php';da_monster_auth('r');
require_once G5_PATH.'/lib/monster_shared.lib.php';
// Existing bookmarks and already-open forms continue to reach the editor.
if ($_SERVER['REQUEST_METHOD']==='POST') { require __DIR__.'/monster_form.php'; return; }
if (!empty($_GET['monster_id'])) goto_url('./monster_form.php?monster_id='.(int)$_GET['monster_id']);
$g5['title']='몬스터 목록';include_once './admin.head.php';
if(!monster_shared_ready()){echo '<div class="local_desc01 local_desc">몬스터 관리를 먼저 설치하세요. <a href="system_install.php">시스템 설치 / 업데이트</a></div>';include './admin.tail.php';return;}
$monsters=monster_rows('SELECT * FROM `'.monster_table('monster_master').'` ORDER BY monster_id DESC');
$catalog=monster_shared_skills();$search=trim(stripslashes($_GET['stx']??''));
if($search!=='')$monsters=array_values(array_filter($monsters,function($m)use($search){return mb_stripos($m['name'],$search)!==false;}));
$total=count($monsters);$page=max(1,(int)($_GET['page']??1));$pages=(int)ceil($total/20);$monsters=array_slice($monsters,($page-1)*20,20);
?>
<?php if(!empty($_GET['saved'])): ?><div class="local_desc01 local_desc" role="status">저장했습니다.</div><?php endif; ?>
<div class="local_ov01 local_ov"><a href="monster.php" class="ov_listall">전체목록</a> 검색된 몬스터 <strong><?=$total?></strong>종</div>
<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get"><label for="stx">몬스터명</label> <input id="stx" name="stx" class="frm_input" value="<?=da_h($search)?>"> <button type="submit" class="btn_submit">검색</button></form>
<div class="btn_add01 btn_add btn_list"><a href="monster_form.php" id="bo_add">몬스터 추가</a></div>
<div class="tbl_head01 tbl_wrap"><table><caption>몬스터 목록</caption><thead><tr><th scope="col">이름</th><th scope="col">사용</th><th scope="col">최대 HP</th><th scope="col">행동 패턴 / 스킬</th><th scope="col">관리</th></tr></thead><tbody>
<?php foreach($monsters as $i=>$m):
    $profile=json_decode($m['profile']??'',true)?:array();$skill_names=array();$phases=$profile['shared']['phases']??array();
    if($phases){foreach($phases as $phase)foreach($phase['actions']??array() as $sid)if((int)$sid>0)$skill_names[(int)$sid]=$catalog[(int)$sid]['name']??'목록에 없는 스킬';$pattern_label='HP 구간 '.count($phases).'단계';}
    elseif(isset($profile['shared'])){foreach($profile['shared']['skills']??array() as $entry){$sid=(int)($entry['skill_id']??$entry['id']??0);$skill_names[$sid]=$catalog[$sid]['name']??'목록에 없는 스킬';}$pattern_label='턴 조건 반복';}
    else $pattern_label='기존 K 패턴';
    $pattern_summary=$pattern_label.($skill_names?' · '.implode(', ',$skill_names):($phases?' · 일반 공격':''));
?>
<tr class="bg<?=$i%2?>"><td><a href="monster_form.php?monster_id=<?=(int)$m['monster_id']?>"><?=da_h($m['name'])?></a></td><td><?=$m['enabled']?'사용':'중지'?></td><td><?=(int)($profile['dg_mon_hp']??0)?></td><td><?=da_h($pattern_summary)?></td><td><a class="btn btn_03" href="monster_form.php?monster_id=<?=(int)$m['monster_id']?>">관리</a></td></tr>
<?php endforeach; if(!$monsters): ?><tr><td colspan="5" class="empty_table">등록된 몬스터가 없습니다.</td></tr><?php endif; ?>
</tbody></table></div>
<?=get_paging(G5_IS_MOBILE?$config['cf_mobile_pages']:$config['cf_write_pages'],$page,$pages,'?stx='.urlencode($search).'&amp;page=')?>
<?php include './admin.tail.php'; ?>
