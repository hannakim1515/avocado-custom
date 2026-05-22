<style>@import url(<?=G5_URL?>/pokemon/egg/egg.css);</style>
<div class="egg_area">
    <div class="hatch_area" style="background-image:url(<?=$cm_list['hatch']['ma_img']?>)">
        <?if($character['egg_id']){?>
            <div class="egg_img">
            </div>
        <?}?>
    </div>

    <div class="action_area">
        <?if($character['egg_id']){
            $egg=get_pokemon($character['egg_id'], false);
            echo $egg['po_content'];
        }else{
            $egg_list=array();
            $egg_sql = sql_query("SELECT ph_id,po_date,ma_name from {$g5['pokemon_has_table']} where ch_id = '{$character['ch_id']}' and ph_egg>0 order by ph_id asc");
            for($i = 0; $row = sql_fetch_array($egg_sql); $i++) {
                $egg_list[] = $row;
            };
            if(count($egg_list)>0){?>
                <select class="egg_select">
                    <?for ($i=0; $i < count($egg_list); $i++) {?>
                        <option value="<?=$egg_list[$i]['ph_id']?>">알 [<?=$egg_list[$i]['po_date']?>/<?=$egg_list[$i]['ma_name']?>]</option>
                    <?}?>
                </select>
                <span class="egg_btn">부화</span>

            <?}else{echo "부화할 수 있는 알이 없습니다.";}
        }?>
    </div>
</div>
<script>
    $('.egg_btn').on('click', function(){
		var ph_id=$(this).siblings('.egg_select').val();
		if(ph_id){location.href="<?=G5_URL?>/pokemon/egg/hatch_update.php?ph_id="+ph_id;}
	});
</script>