<?php
if(!defined('_GNUBOARD_'))exit;
$source='maze';
$ds_id=(int)($_GET['ds_id']??0);$page=max(1,(int)($_GET['page']??1));$offset=($page-1)*50;
$tab=($_GET['tab']??'')==='history'?'history':'combat';
if($ds_id && function_exists('maze_session') && maze_session($ds_id))$source='maze';
$g5['title']='던전 전투 기록';include './admin.head.php';
require_once __DIR__.'/dungeon_admin.lib.php';
require_once __DIR__.'/dungeon_maze.admin.lib.php';
da_nav();
function lh($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<?php if(!empty($_GET['saved']))echo '<div class="local_desc01 local_desc" role="status">처리했습니다.</div>';
if($source==='maze' && $ds_id) { $token=inventory_boundary_token();$dg_id=0;require __DIR__.'/dungeon_maze_session.view.php'; } ?>
<?php $sessions=array();foreach(monster_rows('SELECT s.ds_id,s.dg_title,s.ds_datetime FROM `'.monster_table('dungeon_state').'` s ORDER BY s.ds_id DESC LIMIT 100') as $session)$sessions[$session['ds_id']]=$session['dg_title'].' · '.$session['ds_datetime'];if($ds_id&&!isset($sessions[$ds_id]))$sessions[$ds_id]='선택한 세션'; ?>
<form class="local_sch01 local_sch" method="get"><label for="ds_id">진행 세션</label> <?php da_select('ds_id',$sessions,$ds_id,'세션 선택'); ?> <button class="btn_submit">조회</button></form>
<style>#log-panel-combat[hidden],#log-panel-history[hidden]{display:none!important}</style>
<div class="btn_list01 btn_list" role="tablist" aria-label="기록 종류"><button type="button" id="log-tab-combat" role="tab" aria-controls="log-panel-combat" aria-selected="<?=$tab==='combat'?'true':'false'?>" class="<?=$tab==='combat'?'btn_submit':'btn btn_02'?>">전투기록</button> <button type="button" id="log-tab-history" role="tab" aria-controls="log-panel-history" aria-selected="<?=$tab==='history'?'true':'false'?>" class="<?=$tab==='history'?'btn_submit':'btn btn_02'?>">진행 / 대화 기록</button></div>
<section id="log-panel-combat" role="tabpanel" aria-labelledby="log-tab-combat" <?=$tab==='combat'?'':'hidden'?>>
<h2 class="h2_frm">전투 기록</h2>
<?php if($ds_id && monster_ready()) { $rows=monster_rows('SELECT * FROM `'.monster_table('dungeon_combat_event').'` WHERE source='.monster_quote($source).' AND ds_id='.$ds_id.' ORDER BY event_id DESC LIMIT '.$offset.',51'); ?>
<div class="tbl_head01 tbl_wrap"><table><caption>구조화된 전투 기록</caption><thead><tr><th scope="col">행동자</th><th scope="col">대상</th><th scope="col">행동 / 스킬 / 아이템</th><th scope="col">수치</th><th scope="col">결과</th><th scope="col">턴</th><th scope="col">시간</th></tr></thead><tbody><?php foreach(array_slice($rows,0,50) as $row)echo '<tr><td>'.lh($row['actor']).'</td><td>'.lh($row['target']).'</td><td>'.lh($row['action']).'</td><td>'.(int)$row['amount'].'</td><td>'.lh(array('DAMAGE'=>'피해','HEAL'=>'회복','DEAD'=>'전투불능','STATUS'=>'효과','EVADE'=>'회피')[$row['result']]??$row['result']).'</td><td>'.(int)$row['turn_no'].'</td><td>'.lh($row['created_at']).'</td></tr>';if(!$rows)echo '<tr><td colspan="7" class="empty_table">구조화 기록이 없습니다. 진행 / 대화 기록 탭을 확인하세요.</td></tr>'; ?></tbody></table></div>
<?php if($page>1)echo '<a href="?source='.$source.'&amp;ds_id='.$ds_id.'&amp;tab=combat&amp;page='.($page-1).'">이전</a> ';if(count($rows)>50)echo '<a href="?source='.$source.'&amp;ds_id='.$ds_id.'&amp;tab=combat&amp;page='.($page+1).'">다음</a>'; } ?>
</section><section id="log-panel-history" role="tabpanel" aria-labelledby="log-tab-history" <?=$tab==='history'?'':'hidden'?>>
<?php if($ds_id) { ?><h2 class="h2_frm">진행 / 대화 기록</h2><div class="tbl_head01 tbl_wrap"><table><thead><tr><th scope="col">행동자</th><th scope="col">종류</th><th scope="col">기록</th><th scope="col">시간</th></tr></thead><tbody><?php
if($source==='maze')$legacy=monster_rows('SELECT m.name actor,l.kind kind,l.message message,l.created_at time FROM `'.maze_table('log').'` l LEFT JOIN `'.maze_table('member').'` m ON m.dm_id=l.dm_id WHERE l.ds_id='.$ds_id.' ORDER BY l.log_id DESC LIMIT '.$offset.',51');
foreach(array_slice($legacy,0,50) as $entry)echo '<tr><td>'.lh($entry['actor']).'</td><td>'.lh($entry['kind']).'</td><td>'.nl2br(lh(html_entity_decode(strip_tags(str_replace('&&&&',' / ',$entry['message'])),ENT_QUOTES,'UTF-8'))).'</td><td>'.lh($entry['time']).'</td></tr>';
if(!$legacy)echo '<tr><td colspan="4" class="empty_table">기록이 없습니다.</td></tr>';
?></tbody></table></div><?php if($page>1)echo '<a href="?source='.$source.'&amp;ds_id='.$ds_id.'&amp;tab=history&amp;page='.($page-1).'">이전</a> ';if(count($legacy)>50)echo '<a href="?source='.$source.'&amp;ds_id='.$ds_id.'&amp;tab=history&amp;page='.($page+1).'">다음</a>'; } ?>
</section>
<script>
(() => {
 const tabs=[document.getElementById('log-tab-combat'),document.getElementById('log-tab-history')];
 const select=tab=>tabs.forEach(t=>{const active=t===tab;t.setAttribute('aria-selected',String(active));t.className=active?'btn_submit':'btn btn_02';document.getElementById(t.getAttribute('aria-controls')).hidden=!active;});
 tabs.forEach((tab,i)=>{
  tab.addEventListener('click',()=>select(tab));
  tab.addEventListener('keydown',e=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(e.key)){e.preventDefault();const next=tabs[e.key==='Home'?0:e.key==='End'?1:1-i];next.focus();select(next);}});
 });
})();
</script>
<?php include './admin.tail.php';
