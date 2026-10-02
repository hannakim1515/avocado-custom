<?php
include_once('../../common.php');
if (isset($_REQUEST['ds_id']) && maze_session((int)$_REQUEST['ds_id'])) {
    http_response_code(409);
    exit('새 미궁은 전용 행동 화면에서 진행해 주세요.');
}
?>
