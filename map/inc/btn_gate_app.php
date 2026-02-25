<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$dg_url = G5_URL."/dungeon/applicate.php?ds_id={$dg['ds_id']}";
?>
<button type='button' onclick='goto_dungeon_application();' class='ui-btn app'><span>입장하기</span></button>
<script>
function goto_dungeon_application() {
	var url = "<?=$dg_url?>";
	if(confirm('던전에 입장하시겠습니까?')) {
		location.href = url;
	}
}
</script>
