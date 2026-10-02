<?php
include_once('./_common.php');
$ds_id = isset($_REQUEST['ds_id']) ? (int)$_REQUEST['ds_id'] : 0;
$maze_error = ''; $owner_verified = false;
try {
    maze_require_ready();
    maze_expire_waiting();
    inventory_boundary_owner(isset($character['ch_id']) ? (int)$character['ch_id'] : 0);
    $owner_verified = true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        inventory_boundary_check_token();
        foreach ($_POST as $value) if (!is_string($value)) throw new RuntimeException('요청 형식이 올바르지 않습니다.');
        $operation = isset($_POST['operation']) ? $_POST['operation'] : '';
        if ($operation === 'join') maze_join($ds_id);
        else maze_action($ds_id, $operation, $_POST);
        maze_sync_legacy_state($ds_id);
        goto_url('./maze.php?ds_id='.$ds_id);
    }
} catch (Throwable $error) { $maze_error = $error->getMessage(); }
$g5['title'] = '미궁';
include_once('./_head.sub.php');
function maze_h($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }
$token = inventory_boundary_token();
function maze_form($operation, $label, $hidden = array(), $disabled = false) {
    global $token, $ds_id, $session;
    echo '<form method="post" class="maze-inline"><input type="hidden" name="token" value="'.maze_h($token).'"><input type="hidden" name="ds_id" value="'.$ds_id.'"><input type="hidden" name="operation" value="'.maze_h($operation).'">';
    $defaults = array('version' => $session ? $session['version'] : 0, 'room_id' => $session ? $session['room_id'] : 0);
    foreach (array_merge($defaults, $hidden) as $key => $value) echo '<input type="hidden" name="'.maze_h($key).'" value="'.maze_h($value).'">';
    echo '<button class="ui-btn" type="submit"'.($disabled ? ' disabled' : '').'>'.maze_h($label).'</button></form>';
}
?>
<style>
.maze-wrap{max-width:1000px;margin:24px auto;padding:16px}.maze-panel{padding:16px;margin-bottom:16px;border:1px solid currentColor;border-radius:4px}.maze-inline{display:inline-block;margin:4px}.maze-wrap label{display:block;margin:10px 0}.maze-wrap input,.maze-wrap select,.maze-wrap textarea{max-width:100%;padding:7px}.maze-wrap button:disabled{opacity:.5;cursor:not-allowed}.maze-party{display:flex;flex-wrap:wrap;gap:16px;padding:0;list-style:none}.maze-log{max-height:380px;overflow:auto;overflow-wrap:anywhere}.maze-error{border:2px solid #bd5050;padding:12px}.maze-map{display:flex;flex-wrap:wrap;gap:12px;list-style:none;padding:0}.maze-map li{padding:8px;border:1px solid currentColor}.maze-wrap form[aria-busy=true]{opacity:.6}
</style>
<main class="maze-wrap">
<h1>미궁</h1>
<?php if ($maze_error !== '') echo '<p class="maze-error" role="alert">'.maze_h($maze_error).'</p>';
if (!$owner_verified || !maze_readiness()['ready'] || empty($member['mb_id']) || empty($character['ch_id'])) { echo '</main>'; include_once('./_tail.sub.php'); return; }
$session = maze_session($ds_id);
$person = $session ? maze_one('SELECT * FROM `'.maze_table('member').'` WHERE ds_id='.$ds_id.' AND ch_id='.(int)$character['ch_id'].' AND mb_id='.inventory_boundary_quote($member['mb_id'])) : null;
$ds = get_dungeon_state($ds_id);
$display_settings = $session ? maze_data($session['snapshot']) : array();
echo '<h2>'.maze_h($display_settings['title'] ?? $ds['dg_title'] ?? '던전').'</h2>';
if (!$person || ($session['phase'] === 'WAITING' && $person['state'] !== 'ACTIVE')) {
    echo '<p>입장하면 대기실에서 참가자들과 출발 준비를 할 수 있습니다. 아이템은 출발이 확정될 때 보관됩니다.</p>';
    maze_form('join', '대기실 입장');
    echo '</main>'; include_once('./_tail.sub.php'); return;
}
$active = $person['state'] === 'ACTIVE';
$party = maze_party($ds_id, $active);
$settings = maze_data($session['snapshot']);
echo '<p>시작: '.maze_h($session['started_at']).' · 종료: '.maze_h($session['finished_at']).'</p>';
if ($person['settled']) {
    echo '<section class="maze-panel"><h2>나의 정산</h2><p>'.maze_h($person['state']).' · 아이템 정산 완료</p>';
    foreach (inventory_boundary_rows('SELECT message FROM `'.maze_table('log').'` WHERE ds_id='.$ds_id.' AND dm_id='.(int)$person['dm_id']." AND kind='SETTLEMENT' ORDER BY log_id DESC LIMIT 1") as $summary) echo '<p>'.maze_h($summary['message']).'</p>';
    foreach (inventory_boundary_rows('SELECT amount,state FROM `'.maze_table('reward').'` WHERE ds_id='.$ds_id.' AND dm_id='.(int)$person['dm_id']) as $reward) echo '<p>포인트 '.(int)$reward['amount'].' · '.maze_h($reward['state']).'</p>';
    echo '</section>';
}
echo '<p>상태: '.maze_h($session['phase']).' · <a href="?ds_id='.$ds_id.'">새로고침</a></p>';
echo '<section class="maze-panel"><h2>참가자</h2><ul class="maze-party">';
$status_names=array(); foreach ($settings['status_registry'] ?? array() as $status) $status_names['status:'.$status['status_id']]=$status['name'];
foreach ($party as $mate) {
    echo '<li>'.maze_h($mate['name']).' · '.($session['phase'] === 'WAITING' ? ($mate['ready'] ? 'READY' : '준비 중') : ((int)$mate['hp'] > 0 ? 'HP '.(int)$mate['hp'].' / '.(int)$mate['max_hp'] : '전투불능'));
    foreach(maze_data($mate['effects']) as $key=>$effect) if(isset($status_names[$key])) echo '<br>'.maze_h($status_names[$key]).' · '.(int)$effect['remaining'].'턴';
    echo '</li>';
}
echo '</ul>';
if ($active && in_array($session['phase'], array('WAITING','EXPLORE','BATTLE'), true)) {
    maze_form('leave', '미궁 나가기');
    foreach ($party as $mate) if ($mate['dm_id'] !== $person['dm_id']) maze_form('vote', $mate['name'].' 강퇴 찬성', array('target_id' => $mate['dm_id']));
    $votes = inventory_boundary_rows('SELECT target_id,COUNT(*) AS votes FROM `'.maze_table('vote').'` WHERE ds_id='.$ds_id.' GROUP BY target_id');
    foreach ($votes as $vote) {
        foreach ($party as $mate) if ($mate['dm_id'] === $vote['target_id']) echo '<p>'.maze_h($mate['name']).' 강퇴 찬성 '.(int)$vote['votes'].' / '.max(0,count($party)-1).'</p>';
        maze_form('vote', '내 찬성 취소', array('target_id' => $vote['target_id'], 'cancel' => 1));
    }
}
echo '</section>';
if ($active && $session['phase'] === 'WAITING') {
    echo '<section class="maze-panel"><h2>출발 준비</h2><p>최소 '.$settings['min_players'].'명 · 최대 '.$settings['max_players'].'명</p>';
    maze_form('ready', $person['ready'] ? 'READY 취소' : 'READY', array('ready' => $person['ready'] ? 0 : 1));
    $all_ready = count($party) >= $settings['min_players'];
    foreach ($party as $mate) if (!$mate['ready']) $all_ready = false;
    maze_form('start', '미궁 출발', array(), !$all_ready || !$person['ready']); echo '</section>';
}
$play = $active && $person['hp'] > 0;
if ($session['phase'] === 'EXPLORE') {
    $room = maze_room($session); $payload = maze_data($room['payload']);
    echo '<section class="maze-panel"><h2>방 '.(int)$room['room_id'].' · '.maze_h($room['kind']).'</h2>';
    if ($play) {
        $paths = inventory_boundary_rows('SELECT direction FROM `'.maze_table('room').'` WHERE ds_id='.$ds_id.' AND parent_id='.(int)$room['room_id']);
        $names = array('forward' => '앞으로', 'left' => '왼쪽', 'right' => '오른쪽');
        foreach ($paths as $path) maze_form('move', $names[$path['direction']], array('direction' => $path['direction']));
        if ($room['parent_id']) maze_form('move', '뒤로', array('direction' => 'back'));
        if (!$room['resolved']) {
            $event = $payload['event'];
            if (!empty($event['choices'])) {
                echo '<p>'.maze_h(isset($event['prompt']) ? $event['prompt'] : '어떻게 하시겠습니까?').'</p>';
                foreach ($event['choices'] as $key => $choice) maze_form('event', $choice['label'], array('choice' => $key));
            } else maze_form('event', $room['kind'] === 'TREASURE' ? '상자를 연다' : '조사한다');
        }
    }
    if ($room['resolved']) echo '<p>'.nl2br(maze_h($room['result_text'])).'</p>';
    echo '</section>';
}
if ($session['phase'] === 'BOSS_RESULT') {
    echo '<section class="maze-panel"><h2>보스를 처치했습니다</h2><p>탈출하면 현재 생존한 참가자에게 클리어 보상이 지급됩니다.</p>';
    if ($play) maze_form('escape_final', '탈출'); echo '</section>';
}
$inventory = $active ? inventory_boundary_rows('SELECT inventory_id,item_snapshot FROM `'.maze_table('inventory').'` WHERE ds_id='.$ds_id.' AND dm_id='.(int)$person['dm_id'].' AND consumed=0 AND restored=0 ORDER BY inventory_id') : array();
if ($session['phase'] === 'BATTLE') {
    $battle = maze_battle_current($session); $monster = maze_data($battle['monster']); $actions = maze_actions($battle);
    if (!empty($monster['dg_mon_img'])) echo '<img src="'.maze_h($monster['dg_mon_img']).'" alt="'.maze_h($monster['dg_mon_name']).'" style="max-width:100%;max-height:240px">';
    echo '<section class="maze-panel"><h2>'.maze_h($monster['dg_mon_name']).' · HP '.(int)$battle['hp'].'</h2><p>턴 '.(int)$battle['turn_no'].'</p>';
    if ($battle['deadline_at']) echo '<p id="maze-deadline" data-seconds="'.max(0,strtotime($battle['deadline_at'])-time()).'">제한시간 확인 중</p>';
    foreach ($party as $mate) if ($mate['hp'] > 0) echo '<p>'.maze_h($mate['name']).': '.(!empty($actions[$mate['dm_id']]['action']) ? '제출 완료' : '행동 대기').'</p>';
    if ($play) {
        $mine = isset($actions[$person['dm_id']]) ? $actions[$person['dm_id']] : array();
?>
<form method="post">
<input type="hidden" name="token" value="<?php echo maze_h($token); ?>"><input type="hidden" name="ds_id" value="<?php echo $ds_id; ?>"><input type="hidden" name="operation" value="submit">
<input type="hidden" name="battle_id" value="<?php echo (int)$battle['battle_id']; ?>"><input type="hidden" name="turn_no" value="<?php echo (int)$battle['turn_no']; ?>">
<label>행동 <select name="action"><?php foreach (array('ATTACK'=>'공격','GUARD'=>'방어','HEAL'=>'회복','SKILL'=>'스킬','ITEM'=>'아이템','SKIP'=>'대기') as $key=>$label) echo '<option value="'.$key.'"'.(isset($mine['action']) && $mine['action']===$key ? ' selected':'').'>'.$label.'</option>'; ?></select></label>
<label>대상 <select name="target_id"><?php foreach ($party as $mate) echo '<option value="'.(int)$mate['dm_id'].'"'.((int)($mine['target_id'] ?? $person['dm_id'])===(int)$mate['dm_id']?' selected':'').'>'.maze_h($mate['name']).($mate['hp']<=0?' (전투불능)':'').'</option>'; ?></select></label>
<label>스킬 또는 아이템 <select name="reference_id"><option value="0">선택하지 않음</option><optgroup label="스킬"><?php foreach (maze_data($person['skills']) as $skill) echo '<option value="'.(int)$skill['sh_id'].'"'.(($mine['action'] ?? '')==='SKILL' && (int)($mine['reference_id'] ?? 0)===(int)$skill['sh_id']?' selected':'').'>'.maze_h($skill['sk_name']).' (대기 '.(int)$skill['sh_limit'].'턴)</option>'; ?></optgroup><optgroup label="아이템"><?php foreach ($inventory as $stored) { $item=inventory_boundary_decode($stored['item_snapshot']); echo '<option value="'.(int)$stored['inventory_id'].'"'.(($mine['action'] ?? '')==='ITEM' && (int)($mine['reference_id'] ?? 0)===(int)$stored['inventory_id']?' selected':'').'>'.maze_h($item['it_name']).'</option>'; } ?></optgroup></select></label>
<label><input type="checkbox" name="escape_vote" value="1" <?php echo !empty($mine['escape_vote'])?'checked':''; ?>> 도주 찬성 — 생존자 전원 찬성 시 일반 행동보다 먼저 판정</label>
<button type="submit" class="ui-btn">행동 제출 / 수정</button>
</form>
<?php
        echo '<div id="maze-resolve">'; maze_form('resolve', '남은 시간 스킵 / 종료된 턴 처리', array('battle_id'=>$battle['battle_id'],'turn_no'=>$battle['turn_no']), !maze_all_submitted($party,$actions) && (!$battle['deadline_at'] || strtotime($battle['deadline_at'])>time())); echo '</div>';
    }
    echo '</section>';
}
if ($play && $session['phase'] === 'EXPLORE' && $inventory) { ?>
<section class="maze-panel"><h2>미궁 아이템</h2><form method="post">
<input type="hidden" name="token" value="<?php echo maze_h($token); ?>"><input type="hidden" name="ds_id" value="<?php echo $ds_id; ?>"><input type="hidden" name="operation" value="item"><input type="hidden" name="version" value="<?php echo (int)$session['version']; ?>"><input type="hidden" name="room_id" value="<?php echo (int)$session['room_id']; ?>">
<label>아이템 <select name="inventory_id"><?php foreach ($inventory as $stored) { $item=inventory_boundary_decode($stored['item_snapshot']); echo '<option value="'.(int)$stored['inventory_id'].'"'.(($mine['action'] ?? '')==='ITEM' && (int)($mine['reference_id'] ?? 0)===(int)$stored['inventory_id']?' selected':'').'>'.maze_h($item['it_name']).'</option>'; } ?></select></label>
<label>대상 <select name="target_id"><?php foreach ($party as $mate) echo '<option value="'.(int)$mate['dm_id'].'">'.maze_h($mate['name']).'</option>'; ?></select></label><button class="ui-btn" type="submit">사용</button></form></section>
<?php }
if ($session['started_at']) {
    $visited = inventory_boundary_rows('SELECT room_id,parent_id,kind,is_exit,is_boss FROM `'.maze_table('room').'` WHERE ds_id='.$ds_id.' AND visited=1 ORDER BY room_id');
    echo '<section class="maze-panel"><h2>방문 지도</h2><ul class="maze-map">';
    foreach ($visited as $room) echo '<li>'.($room['room_id']===$session['room_id']?'현재 · ':'').'방 '.(int)$room['room_id'].' (이전 '.(int)$room['parent_id'].')<br>'.maze_h($room['kind']).($room['is_exit']?' · 출구':'').($room['is_boss']?' · 보스':'').'</li>';
    echo '</ul></section>';
}
echo '<section class="maze-panel"><h2>기록과 대화</h2><div class="maze-log">';
$before = isset($_GET['before']) ? max(0,(int)$_GET['before']) : 0;
$logs = inventory_boundary_rows('SELECT l.*,m.name FROM `'.maze_table('log').'` l LEFT JOIN `'.maze_table('member').'` m ON m.dm_id=l.dm_id WHERE l.ds_id='.$ds_id.($before?' AND l.log_id<'.$before:'').' ORDER BY l.log_id DESC LIMIT 50');
foreach (array_reverse($logs) as $log) echo '<p>'.maze_h($log['created_at'].' · '.$log['name'].' · '.$log['message']).'</p>';
echo '</div>';
if (count($logs)===50) echo '<a href="?ds_id='.$ds_id.'&amp;before='.(int)$logs[49]['log_id'].'">이전 기록</a>';
if ($active) { ?>
<form method="post"><input type="hidden" name="token" value="<?php echo maze_h($token); ?>"><input type="hidden" name="ds_id" value="<?php echo $ds_id; ?>"><input type="hidden" name="operation" value="chat"><label>대화 <textarea name="message" required maxlength="1000" rows="2"></textarea></label><button class="ui-btn" type="submit">보내기</button></form>
<?php } ?>
</section></main>
<script>
document.querySelectorAll('.maze-wrap form').forEach(function(form){form.addEventListener('submit',function(event){if(form.dataset.pending){event.preventDefault();return;}form.dataset.pending='1';form.setAttribute('aria-busy','true');form.querySelectorAll('button').forEach(function(button){button.disabled=true;});});});
(function(){var timer=document.getElementById('maze-deadline');if(!timer)return;var end=Date.now()+Number(timer.dataset.seconds)*1000;function tick(){var remaining=Math.max(0,Math.ceil((end-Date.now())/1000));timer.textContent='남은 시간 '+remaining+'초';if(remaining>0){setTimeout(tick,1000);return;}var form=document.querySelector('#maze-resolve form');if(form){form.querySelector('button').disabled=false;form.requestSubmit();}}tick();})();
</script>
<?php include_once('./_tail.sub.php');
