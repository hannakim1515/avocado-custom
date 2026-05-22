<?php
include_once('./_common.php');

$menu='rel';

include_once('./_head.php');

$relation=array();
$relation_sql = sql_query("SELECT ph_id from {$g5['pokemon_has_table']} where ch_id='{$ch['ch_id']}' order by ph_type desc");
for($i = 0; $row = sql_fetch_array($relation_sql); $i++) {
	$relation[] = $row;
};
?>


<h2 class="page-title inner-title">
	<strong>만난 포켓몬</strong>
</h2>

<ul class="relation-member-list">
<?
	 for($i=0; $i < count($relation); $i++) { 
		$re_ch = get_pokemon($relation[$i]['ph_id']);
?>
		<li id="rm_<?=$re_ch['ph_id']?>">
			<div class="ui-thumb">
				<img src="<?=$re_ch['po_dot']?>">
			</div>
			<div class="info">
				<div class="rm-name">
					<?if($re_ch['ph_egg']>0){?>
                        포켓몬 알
                    <?}else{?>
                        <?=$re_ch['po_name']?>/<?=$re_ch['po_species']?>
                    <?}?>
					<? if($re_ch['ch_id'] == $character['ch_id']) { 
						if(!$re_ch['ph_egg']){?>
							<a href="#rm_<?=$re_ch['ph_id']?>" class="btn-modify ui-btn small">수정</a>
						<?}elseif($character['egg_id']!=$re_ch['ph_id']){?>
							<a class="ui-btn" href="<?=G5_URL?>/pokemon/egg/hatch_update.php?ph_id=<?=$re_ch['ph_id']?>">부화</a>
						<?}else{?>
							<span class="ui-btn point">부화중</span>
						<?}
					} ?>
				</div>
			</div>
			<div class="memo">
				<div class="ori-content"><?if(!$re_ch['po_content']){$re_ch['po_content']='트레이너 메모가 없습니다.';} echo nl2br($re_ch['po_content']);?></div>
				<div class="poke_info">
                    <?if(!$re_ch['ph_egg']){?> <p><?=$re_ch['po_pers1']?></p><?}?>
                    <p><?=$re_ch['po_date']?>에 <?=$re_ch['ma_name']?>에서 만났다.</p>
                    <?if(!$re_ch['ph_egg']){?> <p><?=$re_ch['po_pers2']?></p><?}?>
                </div>
			<? if($re_ch['ch_id'] == $character['ch_id']&&!$re_ch['ph_egg']) { ?>
				<div class="modify-box">
					<input type="hidden" id="re_ch_id_<?=$re_ch['ph_id']?>" value="<?=$re_ch['ph_id']?>" />
					<textarea id="memo_<?=$re_ch['ph_id']?>" class="full" rows="8"><?=$re_ch['po_content']?></textarea>
					<button type="button" class="ui-btn full point" onclick="fn_relation_modify(<?=$re_ch['ph_id']?>);" style="margin-top: 5px;">UPDATE</button>
				</div>
			<? } ?>
				
			</div>
			<div class="link_area">
				<?
				$link_list=array();
				$link_sql = sql_query("SELECT * from {$g5['pokemon_log_table']} where ph_id = '{$re_ch['ph_id']}' and lo_type!='알' and lo_type!='각성' order by lo_id asc");
				for($k = 0; $row2 = sql_fetch_array($link_sql); $k++) {
					echo "<a href='{$row2['lo_link']}'>{$row2['lo_type']}</a>";
				}?>
				
			</div>
		</li>


<? }?>
</ul>

<form name="frmRemember_modify" id="frmRemember_modify" method="post" action="./relation_update.php"  onsubmit="return fwrite_submit(this);">
	<input type="hidden" name="ch_id" value="<?=$ch_id?>" />
	<input type="hidden" name="w" value="u" />
	<input type="hidden" name="ph_id" />
	<input type="hidden" name="po_content"/>
</form>

<script>
$('.btn-modify').on('click', function() {
	$(this).closest('li').toggleClass('state-modify');
	return false;
});
function fwrite_submit(f) {
	return true;
}

function fn_relation_modify(idx) { 
	var f = document.frmRemember_modify;
	
	var ph_id = idx;
	var po_content = document.getElementById('memo_' + idx).value;
	
	f.ph_id.value = ph_id;
	f.po_content.value = po_content;

	f.submit();
}



</script>


<?php
include_once('./_tail.php');
?>