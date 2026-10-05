<?php
include_once './_common.php';
require_once G5_PATH.'/lib/maze_client.lib.php';
$ds_id=(int)($_REQUEST['ds_id']??0);$error='';$state=null;
$ajax=$_SERVER['REQUEST_METHOD']==='POST' && ($_POST['ajax']??'')==='1';
try {
    maze_require_ready();inventory_boundary_owner((int)($character['ch_id']??0));
    if($_SERVER['REQUEST_METHOD']==='POST') {
        inventory_boundary_check_token();
        foreach($_POST as $value)if(!is_string($value))throw new RuntimeException('요청 형식이 올바르지 않습니다.');
        $op=$_POST['operation']??'';
        if($op==='join')maze_join($ds_id);else maze_action($ds_id,$op,$_POST);
        if(!$ajax)goto_url('./maze.php?ds_id='.$ds_id);
    }
    $person=maze_one('SELECT dm_id FROM `'.maze_table('member').'` WHERE ds_id='.$ds_id.' AND ch_id='.(int)$character['ch_id'].' AND mb_id='.inventory_boundary_quote($member['mb_id']));
    if($person)$state=maze_client_state($ds_id,(int)($_POST['last_log_id']??0),(int)($_POST['last_chat_id']??0));
    elseif($ajax)throw new RuntimeException('미궁 참가자가 아닙니다.');
} catch(Throwable $e) { $error=$e->getMessage(); }
if($ajax){header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');if($error)http_response_code(400);echo json_encode($error?array('error'=>$error):array('ok'=>true,'state'=>$state),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$g5['title']='미궁';include_once './_head.sub.php';
function maze_h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$token=inventory_boundary_token();
?>
<link rel="stylesheet" href="<?=G5_URL?>/dungeon/maze.css">
<main id="maze-client" class="maze-client">
<header class="maze-top"><a class="maze-brand" href="<?=G5_URL?>/dungeon/">미궁<span>LABYRINTH</span></a><h1 id="maze-title">미궁</h1><div id="maze-phase"></div><div id="maze-turn"></div><div id="maze-room"></div><span id="maze-sync" role="status">연결 중</span><div class="maze-exit-actions"><a href="<?=G5_URL?>/dungeon/">나가기</a><button id="maze-leave" type="button" hidden>미궁 퇴장</button></div></header>
<p id="maze-error" class="maze-error" role="alert" <?=!$error?'hidden':''?>><?=maze_h($error)?></p>
<?php if(!$state){ ?><section class="maze-entry"><h2>미궁 입장</h2><p>파티에 합류한 뒤 준비를 마치면 출발할 수 있습니다.</p><form method="post"><input type="hidden" name="token" value="<?=maze_h($token)?>"><input type="hidden" name="ds_id" value="<?=$ds_id?>"><input type="hidden" name="operation" value="join"><button class="primary">대기실 입장</button></form></section><?php } else { ?>
<aside class="maze-party-panel"><h2>PARTY <span id="party-count"></span></h2><div id="maze-party"></div><details class="maze-map"><summary>방문 지도</summary><div id="maze-map"></div></details></aside>
<section class="maze-stage"><div id="maze-scene" class="maze-scene"><div class="scene-caption"><span>EXPEDITION</span><h2 id="scene-title"></h2><p id="scene-description"></p></div><div id="maze-choices" class="maze-choices"></div><div id="maze-enemies" class="maze-enemies"></div><div id="maze-tokens" class="maze-tokens"></div><p id="maze-result" class="maze-result"></p></div><nav id="maze-nav" class="maze-nav" aria-label="행동 선택"></nav><section id="maze-controls" class="maze-controls" aria-label="현재 행동"></section></section>
<aside class="maze-feed"><div class="maze-tabs" role="tablist" aria-label="기록"><button id="tab-chat" role="tab" aria-selected="true" aria-controls="maze-chat">대화</button><button id="tab-log" role="tab" aria-selected="false" aria-controls="maze-log">전투 기록</button></div><div id="maze-chat" class="maze-messages" role="tabpanel" aria-labelledby="tab-chat"></div><div id="maze-log" class="maze-messages" role="tabpanel" aria-labelledby="tab-log" hidden></div><form id="maze-chat-form"><label for="chat-message">파티 대화</label><textarea id="chat-message" name="message" maxlength="1000" rows="2" required placeholder="메시지를 입력하세요"></textarea><button type="submit">보내기 ↗</button></form></aside>
<?php } ?></main>
<?php if($state){ ?><script id="maze-boot" type="application/json"><?=json_encode(array('state'=>$state,'token'=>$token,'ds_id'=>$ds_id),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?></script><script src="<?=G5_URL?>/dungeon/maze.js" defer></script><?php } include_once './_tail.sub.php';
