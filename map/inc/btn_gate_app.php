<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$dg_url = G5_URL."/dungeon/applicate.php?ds_id={$dg['ds_id']}";
$maze_entry_label = maze_entry_label((int)$dg['ds_id'], (int)$character['ch_id']);
if ($maze_entry_label === '') { echo '<span class="ui-btn">미궁 진행 중</span>'; return; }
?>
<?php if($maze_entry_label==='입장하기') { ?>
<form method="post" action="<?=G5_URL?>/dungeon/maze.php" onsubmit="return confirm('던전에 입장하시겠습니까?');"><input type="hidden" name="token" value="<?=get_text(inventory_boundary_token())?>"><input type="hidden" name="ds_id" value="<?=(int)$dg['ds_id']?>"><input type="hidden" name="operation" value="join"><button type="submit" class="ui-btn app"><span>입장하기</span></button></form>
<?php } else { ?>
<button type='button' onclick='goto_dungeon_application();' class='ui-btn app'><span><?=htmlspecialchars($maze_entry_label, ENT_QUOTES, 'UTF-8')?></span></button>
<script>
function goto_dungeon_application() {
	var url = "<?=$dg_url?>";
	if(confirm('던전에 입장하시겠습니까?')) {
		location.href = url;
	}
}
</script>
<?php } ?>
