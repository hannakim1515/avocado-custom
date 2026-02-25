<?php
include_once('./_common.php');

$npc = sql_fetch("select * from {$g5['character_table']} ch, {$g5['npc_table']} ns where ch.ch_id = ns.ns_id and ns.ns_id = '{$ns_id}'");
if(!$npc['ch_id']) { alert("NPC 정보가 올바르지 않습니다."); }

$npc_id = $npc['ch_id'];
$ch_id = $character['ch_id'];

$g5['title'] = "{$npc['ch_name']} 대화";
include_once('./_head.php');
?>


<div class="npcPageWrap">
	<div class="__npcVisual">
		<div class="vis" style="background-image:url(<?=$npc['ch_body']?>);"></div>
	</div>
	<div class="__npcDescript theme-box">
		<? include_once('./npc.inc.php'); ?>
	</div>
</div>


<?
include_once('./_tail.php');
?>

