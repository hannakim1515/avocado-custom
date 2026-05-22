<?php
include_once('./_common.php');

$menu='item';

include_once('./_head.php');

?>


<h2 class="page-title inner-title">
	<strong>포켓몬 성장</strong>
</h2>

<?
@include(G5_PATH.'/pokemon/info/stat.inc.php');
include_once('./_tail.php');?>