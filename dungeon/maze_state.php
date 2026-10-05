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
    $state=maze_client_state((int)($_GET['ds_id']??0),(int)($_GET['last_log_id']??0),(int)($_GET['last_chat_id']??0));
    // Hash excludes deltas, so unchanged polls only carry new messages.
    $stable=$state;unset($stable['chat'],$stable['logs']);$version=hash('sha256',json_encode($stable));
    if (($_GET['known_version']??'')===$version) $state=array('chat'=>$state['chat'],'logs'=>$state['logs']);
    $state['known_version']=$version;echo json_encode($state,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
} catch(Throwable $error) { http_response_code(403);echo json_encode(array('error'=>$error->getMessage()),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); }
