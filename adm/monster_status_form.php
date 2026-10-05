<?php
$sub_menu='730160';include_once './_common.php';auth_check($auth[$sub_menu],'w');
require_once __DIR__.'/dungeon_admin.lib.php';require_once __DIR__.'/monster_status.lib.php';
$id=(int)($_REQUEST['status_id']??0);$message='';
if(!item_recovery_table_exists('status'))goto_url('./system_install.php');
$status=$id?sql_fetch('SELECT * FROM `'.monster_status_table().'` WHERE status_id='.$id):array('name'=>'','description'=>'','duration'=>1,'enabled'=>1,'components'=>'[{"type":"DOT_HP","value":5}]');
if(!$status)alert('상태이상이 없습니다.','./monster_status.php');
if($_SERVER['REQUEST_METHOD']==='POST') {
    check_token();
    try {monster_status_save($id,$_POST);goto_url('./monster_status.php?saved=1');}
    catch(Throwable $e){$message=$e->getMessage();$status=array_replace($status,array('name'=>stripslashes($_POST['name']??''),'description'=>stripslashes($_POST['description']??''),'duration'=>$_POST['duration']??1,'enabled'=>!empty($_POST['enabled'])?1:0));
        $posted=json_decode(stripslashes($_POST['components']??''),true);if(is_array($posted))$status['components']=json_encode($posted,JSON_UNESCAPED_UNICODE);}
}
$g5['title']=$id?'상태이상 수정':'상태이상 추가';include_once './admin.head.php';$options=da_options();
?>
<div class="local_desc01 local_desc">같은 상태이상이 다시 적용되면 지속시간이 갱신되며, 전투 턴에서만 줄어듭니다. 아이템 관리에서 이 상태이상을 치료하는 아이템을 만들 수 있습니다.</div>
<?php if($message): ?><div class="local_desc01 local_desc" role="status"><?=da_h($message)?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="monster_status_form.php"><input type="hidden" name="token" value="<?=da_h(get_token())?>"><input type="hidden" name="status_id" value="<?=$id?>">
<h2 class="h2_frm">상태이상 정보</h2><div class="tbl_frm01 tbl_wrap"><table><caption>상태이상 정보</caption><colgroup><col class="grid_4"><col></colgroup><tbody>
<tr><th scope="row"><label for="name">이름</label></th><td><input type="text" id="name" name="name" class="frm_input required" value="<?=da_h($status['name'])?>" required size="40"></td></tr>
<tr><th scope="row"><label for="description">설명</label></th><td><textarea id="description" name="description" rows="3"><?=da_h($status['description'])?></textarea></td></tr>
<tr><th scope="row"><label for="duration">지속 턴</label></th><td><input id="duration" name="duration" class="frm_input" type="number" min="1" value="<?=da_h($status['duration'])?>"></td></tr>
<tr><th scope="row">사용 여부</th><td><label><input type="checkbox" name="enabled" value="1" <?=$status['enabled']?'checked':''?>> 상태이상 사용</label></td></tr>
<tr><th scope="row">효과</th><td><?php da_json_editor('components','components',json_decode($status['components'],true)?:array()); ?></td></tr>
</tbody></table></div><div class="btn_confirm01 btn_confirm"><button class="btn_submit" type="submit">저장</button> <a href="monster_status.php">목록</a></div></form>
<script type="application/json" id="dungeon-editor-options"><?=json_encode($options,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script><script src="<?=G5_ADMIN_URL?>/js/dungeon_admin.js"></script>
<?php include './admin.tail.php'; ?>
