<?php
if (!defined('_GNUBOARD_')) exit;

$basic_attack_code = function_exists('unified_combat_action_code') ? unified_combat_action_code('atk') : '';
?>
<form class="dungeon-basic-attack" method="post" action="<?=G5_URL?>/dungeon/proc/basic_attack.php" onsubmit="var button=this.querySelector('button'); if(button){button.disabled=true; button.innerHTML='<span>공격 중...</span>';} return true;">
	<input type="hidden" name="ds_id" value="<?=intval($ds_id)?>">
	<input type="hidden" name="list_type" value="<?=get_text($list_type)?>">
	<input type="hidden" name="token" value="<?=get_token()?>">
	<?php if ($basic_attack_code !== '') { ?>
		<p>A/K 공통 공격 연동 코드: <strong><?=get_text($basic_attack_code)?></strong></p>
		<button type="submit"><span>일반 공격</span></button>
	<?php } else { ?>
		<p>통합 전투 설정에서 일반 공격 연동 코드를 먼저 선택하세요.</p>
		<button type="button" disabled><span>일반 공격</span></button>
	<?php } ?>
</form>
