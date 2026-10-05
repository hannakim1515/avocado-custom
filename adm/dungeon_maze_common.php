<?php
$sub_menu='730100';
include_once './_common.php';
require_once G5_PATH.'/extend/dungeon_maze.lib.php';
auth_check($auth[$sub_menu], 'w');
maze_require_ready();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_demo();
    check_token();
    $raw=trim((string)($_POST['action_timeout']??''));
    if ($raw!=='' && (filter_var($raw,FILTER_VALIDATE_INT)===false || (int)$raw<1)) alert('제한시간은 빈 값 또는 1 이상의 초 단위 정수입니다.');
    maze_update('meta',array('action_timeout'=>$raw===''?null:(int)$raw),'singleton=1');
    goto_url('./dungeon_maze_common.php?saved=1');
}
$meta=maze_one('SELECT action_timeout FROM `'.maze_table('meta').'` WHERE singleton=1');
$g5['title']='던전 탐험 공통 설정';
include_once './admin.head.php';
echo '<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/combat_editor_shell.css"><script src="'.G5_ADMIN_URL.'/js/combat_editor_shell.js"></script>';
?>
<h2 class="h2_frm">모든 던전에 적용하는 설정</h2>
<?php if(!empty($_GET['saved'])): ?><div class="local_desc01 local_desc" role="status">저장했습니다.</div><?php endif; ?>
<form method="post" action="./dungeon_maze_common.php">
<input type="hidden" name="token" value="<?=htmlspecialchars(get_token(),ENT_QUOTES,'UTF-8')?>">
<div class="tbl_frm01 tbl_wrap"><table><caption>던전 탐험 공통 설정</caption><colgroup><col class="grid_4"><col></colgroup><tbody>
<tr><th scope="row"><label for="action_timeout">행동 제한시간</label></th><td><input id="action_timeout" name="action_timeout" type="number" class="frm_input" min="1" value="<?=htmlspecialchars((string)($meta['action_timeout']??''),ENT_QUOTES,'UTF-8')?>"> 초 <p class="frm_info">빈 값은 제한 없음입니다. 모든 던전의 다음 턴부터 적용됩니다.</p></td></tr>
</tbody></table></div>
<div class="btn_confirm01 btn_confirm"><button type="submit" class="btn_submit">저장</button> <a href="./dungeon_list.php">던전 목록</a></div>
</form>
<?php include_once './admin.tail.php'; ?>
