<?php
if (!defined('_GNUBOARD_')) exit;
$rows=inventory_boundary_rows('SELECT d.dg_id,d.dg_title,d.dg_count,d.dg_use,d.dg_settings AS settings FROM `'.$g5['dungeon_table'].'` d ORDER BY d.dg_id DESC');
?>
<div class="local_ov01 local_ov"><span class="ov_listall">전체목록</span> 미궁 <strong><?=count($rows)?></strong>개</div>
<div class="btn_add01 btn_add btn_list"><a href="dungeon_form.php?mode=maze" id="bo_add">미궁 추가</a></div>
<div class="tbl_head01 tbl_wrap"><table><caption>미궁 목록</caption><thead><tr><th scope="col">미궁명</th><th scope="col">최대 인원</th><th scope="col">방 수</th><th scope="col">갈림길 수</th><th scope="col">보스</th><th scope="col">설정 상태</th><th scope="col">관리</th></tr></thead><tbody>
<?php foreach($rows as $i=>$row):$s=$row['settings']?maze_data($row['settings']):array(); ?>
<tr class="bg<?=$i%2?>"><td><?=da_h($row['dg_title'])?></td><td><?=(int)$row['dg_count']?></td><td><?=$s?(int)$s['rooms_min'].' ~ '.(int)$s['rooms_max']:'—'?></td><td><?=$s?(int)$s['branches_min'].' ~ '.(int)$s['branches_max']:'—'?></td><td><?=!empty($s['boss'])?'사용':'없음'?></td><td><?=$s?'설정됨':'미설정'?></td><td><a class="btn btn_03" href="dungeon_form.php?w=u&amp;mode=maze&amp;dg_id=<?=(int)$row['dg_id']?>">수정</a></td></tr>
<?php endforeach;if(!$rows): ?><tr><td colspan="7" class="empty_table">등록된 미궁이 없습니다.</td></tr><?php endif; ?>
</tbody></table></div>
