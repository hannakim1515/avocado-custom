<?php
$sub_menu = '730100';
include_once('./_common.php');
auth_check($auth[$sub_menu], 'r');
function maze_admin_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function maze_admin_json($value) {
    $data = json_decode($value, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('설정 데이터 형식을 확인해 주세요.');
    return $data;
}
$message = '';
$dg_id = isset($_REQUEST['dg_id']) ? (int)$_REQUEST['dg_id'] : 0;
$ds_id = isset($_REQUEST['ds_id']) ? (int)$_REQUEST['ds_id'] : 0;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        auth_check($auth[$sub_menu], 'w'); inventory_boundary_check_token(); maze_require_ready();
        $operation = isset($_POST['operation']) ? $_POST['operation'] : '';
        if ($operation === 'settings') {
            $dg = sql_fetch('SELECT * FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$dg_id);
            if (!$dg) throw new RuntimeException('던전 정의가 없습니다.');
            $settings = maze_defaults($dg);
            foreach (array('min_players','rooms_min','rooms_max','branches_min','branches_max','dead_end_percent','encounter_percent','escape_percent','trap_damage','points') as $key) $settings[$key] = (int)$_POST[$key];
            $settings['boss'] = !empty($_POST['boss']);
            foreach ($settings['weights'] as $key => $value) $settings['weights'][$key] = (int)$_POST['weights'][$key];
            foreach (array('monsters','events','rewards') as $key) $settings[$key] = maze_admin_json($_POST[$key]);
            foreach ($settings['monsters'] as &$monster) $monster['data']=maze_monster_definition($monster['data'],$dg);
            unset($monster);
            foreach ($settings['events'] as &$event) {
                if(isset($event['monster'])) $event['monster']=maze_monster_definition($event['monster'],$dg);
                if(!empty($event['choices'])) foreach($event['choices'] as &$choice) if(isset($choice['monster']))$choice['monster']=maze_monster_definition($choice['monster'],$dg);
                unset($choice);
            } unset($event);
            maze_validate_config($settings);
            inventory_boundary_query('INSERT INTO `'.maze_table('config').'` (dg_id,settings) VALUES ('.$dg_id.','.inventory_boundary_quote(maze_json($settings)).') ON DUPLICATE KEY UPDATE settings=VALUES(settings)');
            $message = '저장했습니다. 이미 출발한 세션에는 소급 적용되지 않습니다.';
        } elseif ($operation === 'timeout') {
            $timeout = trim($_POST['timeout']);
            if ($timeout !== '' && (filter_var($timeout,FILTER_VALIDATE_INT) === false || (int)$timeout < 1)) throw new RuntimeException('제한시간은 빈 값 또는 1 이상의 초 단위 정수입니다.');
            maze_update('meta', array('action_timeout' => $timeout === '' ? null : (int)$timeout), 'singleton=1');
            $message = '다음 턴부터 제한시간을 적용합니다.';
        } elseif ($operation === 'status') {
            $components = maze_admin_json($_POST['components']);
            if (!$components || trim($_POST['name']) === '' || (int)$_POST['duration'] < 1) throw new RuntimeException('상태 이름·지속 턴·효과가 필요합니다.');
            foreach ($components as $component) {
                if (!isset($component['type'],$component['value']) || !is_numeric($component['value']) || !in_array($component['type'],array('STAT_MODIFIER','DOT_HP','ACTION_DISABLE','HEAL_MODIFIER'),true)) throw new RuntimeException('상태 효과 종류/값을 확인해 주세요.');
                if ($component['type']==='STAT_MODIFIER' && (!isset($component['mode']) || !in_array($component['mode'],array('flat','percent','final'),true))) throw new RuntimeException('스탯 효과 mode는 flat/percent/final이어야 합니다.');
                if ($component['type']==='DOT_HP' && $component['value']<0) throw new RuntimeException('지속 피해는 0 이상이어야 합니다.');
                if ($component['type']==='ACTION_DISABLE' && (int)$component['value']!==1) throw new RuntimeException('행동 제한 값은 1이어야 합니다.');
                if ($component['type']==='STAT_MODIFIER' && (empty($component['stat_id']) || !sql_fetch('SELECT st_id FROM `'.$g5['status_config_table'].'` WHERE st_id='.(int)$component['stat_id']))) throw new RuntimeException('유효한 스탯 ID가 필요합니다.');
            }
            $data = array('name'=>trim($_POST['name']),'description'=>$_POST['description'],'duration'=>(int)$_POST['duration'],'enabled'=>!empty($_POST['enabled'])?1:0,'components'=>maze_json($components));
            $id = (int)$_POST['status_id'];
            if ($id) maze_update('status',$data,'status_id='.$id); else maze_insert('status',$data);
            $message = '상태이상을 저장했습니다.';
        } elseif ($operation === 'item_effect') {
            $id=(int)$_POST['it_id']; $kind=$_POST['kind']; $value=(int)$_POST['value']; $status_id=(int)$_POST['status_id'];
            if (!get_item($id) || !in_array($kind,array('REVIVE','CLEANSE'),true)) throw new RuntimeException('아이템과 효과를 확인해 주세요.');
            if ($kind==='REVIVE' && ($value<1 || $value>100)) throw new RuntimeException('부활 회복률은 1~100%입니다.');
            if ($kind==='CLEANSE' && !maze_one('SELECT status_id FROM `'.maze_table('status').'` WHERE status_id='.$status_id)) throw new RuntimeException('치료할 상태이상이 없습니다.');
            inventory_boundary_query('INSERT INTO `'.maze_table('item_effect').'` (it_id,kind,status_id,value) VALUES ('.$id.','.inventory_boundary_quote($kind).','.$status_id.','.$value.') ON DUPLICATE KEY UPDATE kind=VALUES(kind),status_id=VALUES(status_id),value=VALUES(value)');
            $message='아이템 효과를 저장했습니다.';
        } elseif (in_array($operation,array('clear','fail','kick'),true)) {
            maze_admin_end($ds_id,$operation,isset($_POST['target_id'])?(int)$_POST['target_id']:0); $message='관리자 처리를 완료했습니다.';
        } elseif ($operation==='reward_retry') { maze_pay_rewards($ds_id); $message='대기 중인 포인트 지급을 재시도했습니다.'; }
        else throw new RuntimeException('지원하지 않는 요청입니다.');
    }
} catch (Throwable $error) { $message=$error->getMessage(); }
$g5['title']='미궁 설정과 진행 현황'; include_once('./admin.head.php');
$token=inventory_boundary_token(); $ready=maze_readiness();
function maze_admin_hidden($operation) { global $token,$dg_id,$ds_id; echo '<input type="hidden" name="token" value="'.maze_admin_h($token).'"><input type="hidden" name="operation" value="'.maze_admin_h($operation).'"><input type="hidden" name="dg_id" value="'.$dg_id.'"><input type="hidden" name="ds_id" value="'.$ds_id.'">'; }
if ($message) echo '<div class="local_desc01 local_desc" role="status">'.maze_admin_h($message).'</div>';
if (!$ready['ready']) { echo '<div class="local_desc01 local_desc"><h2>미궁 시스템 DB migration이 적용되지 않았음</h2><pre>'.maze_admin_h(implode("\n",$ready['errors'])).'</pre><p>install/dungeon의 사전 점검과 수동 migration을 확인해 주세요. 이 화면에서는 자동 적용하지 않습니다.</p></div>'; include_once('./admin.tail.php'); return; }
?>
<style>.maze-admin{padding:16px;margin:16px 0;border:1px solid #bbb}.maze-admin label{display:block;margin:12px 0}.maze-admin textarea{width:100%;min-height:130px;font-family:monospace}.maze-admin input{max-width:100%}.maze-admin table{width:100%}.maze-admin th,.maze-admin td{padding:8px;text-align:left}</style>
<section class="maze-admin"><h2>전역 행동 제한시간</h2><form method="post"><?php maze_admin_hidden('timeout'); $meta=maze_one('SELECT action_timeout FROM `'.maze_table('meta').'` WHERE singleton=1'); ?><label>제한시간(초, 빈 값은 제한 없음) <input type="number" name="timeout" min="1" value="<?php echo maze_admin_h($meta['action_timeout']); ?>"></label><button class="btn" type="submit">저장</button></form></section>
<section class="maze-admin"><h2>던전 선택</h2><form method="get"><select name="dg_id"><?php $dungeons=inventory_boundary_rows('SELECT * FROM `'.$g5['dungeon_table'].'` ORDER BY dg_id'); foreach($dungeons as $dg) echo '<option value="'.(int)$dg['dg_id'].'"'.($dg_id===(int)$dg['dg_id']?' selected':'').'>'.maze_admin_h($dg['dg_title']).'</option>'; ?></select><button class="btn">선택</button></form>
<?php if($dg_id) { $dg=sql_fetch('SELECT * FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.$dg_id); if($dg) { $saved=maze_one('SELECT settings FROM `'.maze_table('config').'` WHERE dg_id='.$dg_id); $settings=array_replace(maze_defaults($dg),$saved?maze_data($saved['settings']):array()); ?>
<form method="post"><?php maze_admin_hidden('settings'); ?><p>최대 인원은 기존 던전정보의 dg_count(<?php echo (int)$dg['dg_count']; ?>명)를 사용합니다.</p>
<?php foreach(array('min_players'=>'최소 출발 인원','rooms_min'=>'최소 방 수','rooms_max'=>'최대 방 수','branches_min'=>'최소 갈림길 수','branches_max'=>'최대 갈림길 수','dead_end_percent'=>'Extra dead end chance (%)','encounter_percent'=>'몬스터 배치 확률(%)','escape_percent'=>'도주 성공률(%)','trap_damage'=>'기본 함정 피해','points'=>'클리어 포인트') as $key=>$label) echo '<label>'.maze_admin_h($label).' <input type="number" name="'.$key.'" value="'.(int)$settings[$key].'" min="0" required></label>'; ?>
<label><input type="checkbox" name="boss" value="1" <?php echo $settings['boss']?'checked':''; ?>> 보스 사용</label>
<fieldset><legend>방 종류 가중치 — 합계가 100일 필요는 없습니다</legend><?php foreach($settings['weights'] as $key=>$weight) echo '<label>'.$key.' <input type="number" name="weights['.$key.']" min="0" value="'.(int)$weight.'"></label>'; ?></fieldset>
<?php foreach(array('monsters'=>'몬스터 풀','events'=>'방 이벤트와 한 단계 선택지','rewards'=>'독립 확률 클리어 보상') as $key=>$label) echo '<label>'.$label.' (JSON)<textarea name="'.$key.'" spellcheck="false">'.maze_admin_h(json_encode($settings[$key],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</textarea></label>'; ?>
<p>몬스터: enabled, boss, weight, data(기존 dg_* 전투 수치). 이벤트: enabled, room_kind, weight, type, text. 선택지는 choices 배열에 label과 결과를 지정합니다. 결과 종류는 NONE/TEXT/ITEM/TRAP/MONSTER/HEAL입니다. 아이템 결과는 it_id/count, 함정·회복은 target(ACTOR/ALL/RANDOM)/value, 몬스터 결과는 monster를 지정합니다.</p>
<p>보상 예시: [{"it_id": 1, "count": 1, "percent": 25}]. 각 보상은 참가자별로 독립 추첨됩니다.</p>
<button class="btn" type="submit">던전 설정 저장</button></form>
<?php }} ?></section>
<section class="maze-admin"><h2>공통 상태이상</h2><p>같은 상태는 중첩되지 않고 지속시간이 갱신됩니다. 지속시간은 전투 턴에서만 줄어듭니다.</p>
<?php $statuses=inventory_boundary_rows('SELECT * FROM `'.maze_table('status').'` ORDER BY status_id'); $statuses[]=array('status_id'=>0,'name'=>'','description'=>'','duration'=>1,'enabled'=>1,'components'=>'[{"type":"DOT_HP","value":5}]'); foreach($statuses as $status) { ?>
<form method="post"><?php maze_admin_hidden('status'); ?><input type="hidden" name="status_id" value="<?php echo (int)$status['status_id']; ?>"><h3><?php echo $status['status_id']?'상태 #'.(int)$status['status_id']:'새 상태'; ?></h3><label>이름 <input name="name" value="<?php echo maze_admin_h($status['name']); ?>" required></label><label>설명 <input name="description" value="<?php echo maze_admin_h($status['description']); ?>"></label><label>지속 턴 <input type="number" name="duration" min="1" value="<?php echo (int)$status['duration']; ?>"></label><label><input type="checkbox" name="enabled" value="1" <?php echo $status['enabled']?'checked':''; ?>> 사용</label><label>효과 구성(JSON): STAT_MODIFIER(stat_id, mode, value), DOT_HP(value), ACTION_DISABLE(value=1), HEAL_MODIFIER(value, %)<textarea name="components"><?php echo maze_admin_h($status['components']); ?></textarea></label><button class="btn" type="submit">상태 저장</button></form>
<?php } ?></section>
<section class="maze-admin"><h2>부활·상태 치료 아이템</h2><p>일반 아이템 설정에서 전투 사용 가능도 켜야 출발 시 보관됩니다.</p><form method="post"><?php maze_admin_hidden('item_effect'); ?><label>아이템 ID <input type="number" min="1" name="it_id" required></label><label>효과 <select name="kind"><option value="REVIVE">부활</option><option value="CLEANSE">상태 치료</option></select></label><label>부활 HP 회복률(%) <input type="number" name="value" min="1" max="100" value="20"></label><label>치료할 상태 ID <input type="number" name="status_id" min="0" value="0"></label><button class="btn" type="submit">저장</button></form>
<?php foreach(inventory_boundary_rows('SELECT * FROM `'.maze_table('item_effect').'` ORDER BY it_id') as $effect) echo '<p>아이템 '.(int)$effect['it_id'].' · '.maze_admin_h($effect['kind']).' · 상태 '.(int)$effect['status_id'].' · 수치 '.(int)$effect['value'].'</p>'; ?></section>
<section class="maze-admin"><h2>최근 세션</h2><?php foreach(inventory_boundary_rows('SELECT ds_id,phase,created_at FROM `'.maze_table('session').'` ORDER BY ds_id DESC LIMIT 50') as $row) echo '<p><a href="?ds_id='.(int)$row['ds_id'].'">#'.(int)$row['ds_id'].' '.maze_admin_h($row['phase']).'</a> '.maze_admin_h($row['created_at']).'</p>'; ?></section>
<?php if($ds_id && ($session=maze_session($ds_id))) { ?>
<section class="maze-admin"><h2>세션 #<?php echo $ds_id; ?> — <?php echo maze_admin_h($session['phase']); ?></h2><table><thead><tr><th>참가자</th><th>HP</th><th>상태</th><th>정산</th><th>처리</th></tr></thead><tbody>
<?php foreach(maze_party($ds_id,false) as $person) { echo '<tr><td>'.maze_admin_h($person['name']).'</td><td>'.(int)$person['hp'].'</td><td>'.maze_admin_h($person['state']).($person['ready']?' · READY':'').($person['hp']<=0?' · 전투불능':'').'</td><td>'.($person['settled']?'완료':'대기').'</td><td>'; if($person['state']==='ACTIVE'){ echo '<form method="post">'; maze_admin_hidden('kick'); echo '<input type="hidden" name="target_id" value="'.(int)$person['dm_id'].'"><button class="btn">강제 퇴장</button></form>'; } echo '</td></tr>'; } ?></tbody></table>
<?php foreach(array('clear'=>'클리어 종료','fail'=>'실패 종료','reward_retry'=>'대기 포인트 지급 재시도') as $op=>$label){echo '<form method="post">';maze_admin_hidden($op);echo '<button class="btn">'.$label.'</button></form>';} ?>
<?php echo '<p>던전 #'.(int)$session['dg_id'].' · 방 #'.(int)$session['room_id'].' · 시작 '.maze_admin_h($session['started_at']).' · 종료 '.maze_admin_h($session['finished_at']).'</p>';
if($session['battle_id']) { $battle=maze_battle_current($session); echo '<p>전투 #'.(int)$battle['battle_id'].' · 턴 '.(int)$battle['turn_no'].' · '.maze_admin_h($battle['state']).'</p>'; }
echo '<h3>최근 진행·대화 기록</h3>'; foreach(array_reverse(inventory_boundary_rows('SELECT created_at,dm_id,kind,message FROM `'.maze_table('log').'` WHERE ds_id='.$ds_id.' ORDER BY log_id DESC LIMIT 100')) as $entry) echo '<p>'.maze_admin_h($entry['created_at'].' · #'.$entry['dm_id'].' · '.$entry['kind'].' · '.$entry['message']).'</p>'; ?>
<details><summary>전체 미궁 구조 · 관리자 전용</summary><pre><?php echo maze_admin_h(json_encode(inventory_boundary_rows('SELECT * FROM `'.maze_table('room').'` WHERE ds_id='.$ds_id.' ORDER BY room_id'),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); ?></pre></details></section>
<?php } include_once('./admin.tail.php');
