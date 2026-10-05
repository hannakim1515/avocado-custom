<?php
$sub_menu='730160';include_once './_common.php';auth_check($auth[$sub_menu],'r');
require_once __DIR__.'/dungeon_admin.lib.php';require_once __DIR__.'/monster_status.lib.php';
$g5['title']='상태이상 목록';include_once './admin.head.php';
if(!item_recovery_table_exists('status')){echo '<div class="local_desc01 local_desc">상태이상 저장소를 먼저 설치하세요. <a href="system_install.php">시스템 설치 / 업데이트</a></div>';include './admin.tail.php';return;}
$statuses=monster_rows('SELECT * FROM `'.monster_status_table().'` ORDER BY status_id DESC');
$search=trim(stripslashes($_GET['stx']??''));if($search!=='')$statuses=array_values(array_filter($statuses,function($s)use($search){return mb_stripos($s['name'],$search)!==false;}));
$total=count($statuses);$page=max(1,(int)($_GET['page']??1));$pages=(int)ceil($total/20);$statuses=array_slice($statuses,($page-1)*20,20);
?>
<?php if(!empty($_GET['saved'])): ?><div class="local_desc01 local_desc" role="status">상태이상을 저장했습니다.</div><?php endif; ?>
<div class="local_ov01 local_ov"><a href="monster_status.php" class="ov_listall">전체목록</a> 검색된 상태이상 <strong><?=$total?></strong>개</div>
<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get"><label for="stx">상태이상명</label> <input id="stx" name="stx" class="frm_input" value="<?=da_h($search)?>"> <button type="submit" class="btn_submit">검색</button></form>
<div class="btn_add01 btn_add btn_list"><a href="monster_status_form.php" id="bo_add">상태이상 추가</a></div>
<div class="tbl_head01 tbl_wrap"><table><caption>상태이상 목록</caption><thead><tr><th scope="col">이름</th><th scope="col">설명</th><th scope="col">지속 턴</th><th scope="col">사용</th><th scope="col">관리</th></tr></thead><tbody>
<?php foreach($statuses as $i=>$s): ?><tr class="bg<?=$i%2?>"><td><a href="monster_status_form.php?status_id=<?=(int)$s['status_id']?>"><?=da_h($s['name'])?></a></td><td><?=da_h($s['description'])?></td><td><?=(int)$s['duration']?></td><td><?=$s['enabled']?'사용':'중지'?></td><td><a class="btn btn_03" href="monster_status_form.php?status_id=<?=(int)$s['status_id']?>">수정</a></td></tr><?php endforeach; ?>
<?php if(!$statuses): ?><tr><td colspan="5" class="empty_table">등록된 상태이상이 없습니다.</td></tr><?php endif; ?>
</tbody></table></div>
<?=get_paging(G5_IS_MOBILE?$config['cf_mobile_pages']:$config['cf_write_pages'],$page,$pages,'?stx='.urlencode($search).'&amp;page=')?>
<?php include './admin.tail.php'; ?>
