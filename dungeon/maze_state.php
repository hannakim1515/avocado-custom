<?php
// Authenticate without theme/bootstrap/reset/visit writes.
define('G5_GAME_STATE_READ_ONLY',true);
include_once '../common.php';
require_once G5_PATH.'/extend/inventory_boundary.lib.php';
require_once G5_PATH.'/extend/dungeon_maze.lib.php';
require_once G5_PATH.'/lib/monster.lib.php';
require_once G5_PATH.'/lib/maze_client.lib.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
try {
    if (empty($member['mb_id'])) throw new RuntimeException('로그인 후 이용하세요.');
    $ds_id=(int)($_GET['ds_id']??0);$last_log=(int)($_GET['last_log_id']??0);$last_chat=(int)($_GET['last_chat_id']??0);$last_event=(int)($_GET['last_event_id']??0);
    $current=maze_one('SELECT s.version, EXISTS(SELECT 1 FROM `'.maze_table('log').'` l WHERE l.ds_id=s.ds_id AND l.kind=\'CHAT\' AND l.log_id>'.$last_chat.') AS new_chat, EXISTS(SELECT 1 FROM `'.maze_table('log').'` l WHERE l.ds_id=s.ds_id AND l.kind <> \'CHAT\' AND l.log_id>'.$last_log.') AS new_log, EXISTS(SELECT 1 FROM `'.monster_table('dungeon_combat_event').'` e WHERE e.source=\'maze\' AND e.ds_id=s.ds_id AND e.event_id>'.$last_event.') AS new_event FROM `'.maze_table('session').'` s JOIN `'.maze_table('member').'` m ON m.ds_id=s.ds_id WHERE s.ds_id='.$ds_id.' AND m.ch_id='.(int)$member['ch_id'].' AND m.mb_id='.inventory_boundary_quote($member['mb_id']).' LIMIT 1');
    if (!$current) throw new RuntimeException('미궁 참가자가 아닙니다.');
    if ((string)($_GET['known_version']??'')===(string)$current['version'] && !$current['new_chat'] && !$current['new_log'] && !$current['new_event'])
        $state=array('chat'=>array(),'logs'=>array(),'events'=>array());
    else $state=maze_client_state($ds_id,$last_log,$last_chat,$last_event);
    $state['known_version']=isset($state['version']) ? (int)$state['version'] : (int)$current['version'];echo json_encode($state,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
} catch(Throwable $error) { http_response_code(403);echo json_encode(array('error'=>$error->getMessage()),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); }
