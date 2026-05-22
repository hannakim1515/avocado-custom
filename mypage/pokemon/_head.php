<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
include_once('../_head.php');


if(!$ph_id){$ph_id=$character['ph_id'];}
$po=get_pokemon($ph_id);
$po_list=get_pokemon_list($character['ch_id']);
$ch=$character;

$cm_list=array();
$command_sql = sql_query("SELECT * from {$g5['pokemon_map_table']} where ma_type='command'");
for($i = 0; $row = sql_fetch_array($command_sql); $i++) {
	$cm_list[$row['ma_name']] = $row;
};

?>
<style>
	@import url(<?=G5_URL?>/pokemon/member/mypage.css);
</style>
<h2 class="page-title">
	<strong>포켓몬 관리</strong>
	<span>Pokemon</span>
</h2>

<nav id="tab_list">
	<ul class="tab_list">
		<li <?if(!$menu){echo "class=\"on\"";}?>>
			<a href="./index.php?ph_id=<?=$po_list[0]['ph_id']?>" >
				엔트리
			</a>
		</li>
		<li <?if($menu=='egg'){echo "class=\"on\"";}?>>
			<a href="./egg.php" >
				부화
			</a>
		</li>

		<li <?if($menu=='rel'){echo "class=\"on\"";}?>>
			<a href="./relation_list.php">
				만난 포켓몬
			</a>
		</li>
		<li <?if($menu=='item'){echo "class=\"on\"";}?>>
			<a href="./item.php">
			   성장
			</a>
		</li>
		<?if($cm_list['battle']['ma_use']){?>
			<li>
				<a href="<?=G5_URL?>/pokemon/battle" target="_blank">
					배틀
				</a>
			</li>
		<?}?>
	</ul>
</nav>

<div class="mypage_subinner">