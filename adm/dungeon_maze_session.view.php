<?php if (!defined('_GNUBOARD_')) exit; if($ds_id && ($session=maze_session($ds_id))) { ?>
<section id="session-detail"><h2 class="h2_frm">세션 #<?php echo $ds_id; ?> — <?php echo maze_admin_h($session['phase']); ?></h2><div class="tbl_head01 tbl_wrap"><table><caption>세션 참가자 현황</caption><thead><tr><th scope="col">참가자</th><th scope="col">HP</th><th scope="col">상태</th><th scope="col">정산</th><th scope="col">처리</th></tr></thead><tbody>
<?php foreach(maze_party($ds_id,false) as $person) { echo '<tr><td>'.maze_admin_h($person['name']).'</td><td>'.(int)$person['hp'].'</td><td>'.maze_admin_h($person['state']).($person['ready']?' · READY':'').($person['hp']<=0?' · 전투불능':'').'</td><td>'.($person['settled']?'완료':'대기').'</td><td>'; if($person['state']==='ACTIVE'){ echo '<form method="post" action="./dungeon_state_list.php">'; maze_admin_hidden('kick'); echo '<input type="hidden" name="target_id" value="'.(int)$person['dm_id'].'"><button class="btn btn_02">강제 퇴장</button></form>'; } echo '</td></tr>'; } ?></tbody></table></div>
<?php foreach(array('clear'=>'클리어 종료','fail'=>'실패 종료','reward_retry'=>'대기 포인트 지급 재시도') as $op=>$label){echo '<form method="post" action="./dungeon_state_list.php">';maze_admin_hidden($op);echo '<button class="btn btn_02">'.$label.'</button></form>';} ?>
<?php echo '<p>던전 #'.(int)$session['dg_id'].' · 방 #'.(int)$session['room_id'].' · 시작 '.maze_admin_h($session['started_at']).' · 종료 '.maze_admin_h($session['finished_at']).'</p>';
if($battle=maze_admin_battle($session)) { echo '<p>전투 #'.(int)$battle['battle_id'].' · 턴 '.(int)$battle['turn_no'].' · '.maze_admin_h($battle['state']).'</p>'; }
?>
</section>
<?php } ?>
