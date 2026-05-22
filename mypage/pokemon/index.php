<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once('./_head.php');

?>

<section>
	<div class="partner-area" id="partner">
		<ul class="partner_tab" id="partner_tab">
			<?for ($i=0; $i < count($po_list); $i++) {?>
				<li <?if($ph_id==$po_list[$i]['ph_id']){echo "class=\"on\"";}?>>
					<a href="./index.php?ph_id=<?=$po_list[$i]['ph_id']?>" >
						<img src="<?=$po_list[$i]['po_dot']?>">
					</a>
					<span><?=$po_list[$i]['po_name']?></span>
				</li>
			<?}?>
		</ul>
	</div>
	<?@include(G5_PATH."/pokemon/info/pokemon_inc.php");?>
</section>


<?php
include_once('./_tail.php');
?>
