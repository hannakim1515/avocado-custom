<ul class="relation-member-list">
    <?
        for($i=0; $i < count($relation); $i++) { 
            $re_ch = get_pokemon($relation[$i]['ph_id']);
            if($re_ch['po_name']!=$re_ch['po_species']){
                $re_ch['po_species']=$re_ch['po_name']."/".$re_ch['po_species'];
            }
            if($re_ch['ph_sex']==1){
                $re_ch['po_species'].=" ♀";
            }elseif($re_ch['ph_sex']==2){
                $re_ch['po_species'].=" ♂";
            }
    ?>
        <li class="<?if($re_ch['ph_type']!='friend'){echo "entry";}?>">
            <div class="ui-thumb">
                <img src="<?=$re_ch['po_dot']?>">
            </div>
            <div class="info">
                <div class="rm-name">
                    <?if($re_ch['ph_egg']>0){?>
                        포켓몬 알
                    <?}else{?>
                        <?=$re_ch['po_species']?>
                    <?}?>
                   
                </div>
            </div>
            <div class="memo theme-box">
                <p id="memo_po_<?=$re_ch['ph_id']?>"><?if(!$re_ch['po_content']){$re_ch['po_content']='트레이너 메모가 없습니다.';} echo nl2br($re_ch['po_content']);?></p>
                <div class="poke_info">
                    <?if(!$re_ch['ph_egg']){?> <p><?=$re_ch['po_pers1']?></p><?}?>
                    <p><?=$re_ch['po_date']?>에 <?=$re_ch['ma_name']?>에서 만났다.</p>
                    <?if(!$re_ch['ph_egg']){?> <p><?=$re_ch['po_pers2']?></p><?}?>
                </div>
                <div class="link_area">
                    <?
                    $link_list=array();
                    $link_sql = sql_query("SELECT * from {$g5['pokemon_log_table']} where ph_id = '{$re_ch['ph_id']}' and lo_type!='알' and lo_type!='각성' order by lo_id asc");
                    for($k = 0; $row2 = sql_fetch_array($link_sql); $k++) {
                        echo "<a href='{$row2['lo_link']}'>{$row2['lo_type']}</a>";
                    }?>
			    </div>
            </div>
            <?if($ch_id==$character['ch_id']){?>
                 <div class="memo-modify">
                    <?if(!$re_ch['ph_egg']){?>
                        <span class="ui-btn relation_modify" data-type="po" data-idx='<?=$re_ch['ph_id']?>'>수정</span>
                        <span class="ui-btn relation_save" style="display:none;" data-type="po" data-idx='<?=$re_ch['ph_id']?>'>저장</span>
                    <?}elseif($ch['egg_id']!=$re_ch['ph_id']){?>
                        <a class="ui-btn" href="<?=G5_URL?>/pokemon/egg/hatch_update.php?ph_id=<?=$re_ch['ph_id']?>">부화</a>
                    <?}else{?>
                        <span class="ui-btn point">부화중</span>
                    <?}?>
                </div>
            <?}?>
        </li>
    <? }?>
</ul>


<script>
    $(".relation_modify").on('click',function(){
        var rm_id=$(this).data('idx');
        var type=$(this).data('type');
        var rm_memo='';
        rm_memo=$("#memo_"+type+"_"+rm_id).text();
        $("#memo_"+type+"_"+rm_id).empty().html("<textarea>"+rm_memo+"</textarea>");
        $(this).hide();
        $(this).siblings('.relation_save').show();
    });
    $(".relation_save").on('click',function(){
        var rm_id=$(this).data('idx');
        var type=$(this).data('type');
        var rm_memo='';
        rm_memo=$("#memo_"+type+"_"+rm_id+" textarea").val();

        var formData = new FormData();
        formData.append('rm_id', rm_id);
        formData.append('type', type);
        formData.append('rm_memo', rm_memo);

        rm_memo=rm_memo.replace(/(?:\r\n|\r|\n)/g, '<br>');
        $.ajax({
            type: 'post'
            , url : g5_url + '/member/relation_update.php'
            , data: formData
            , dataType:'json'
            , contentType: false
            , processData: false
            , success : function(data) {
                $("#memo_"+type+"_"+rm_id).empty().html(rm_memo);
            }
        });
        $(this).hide();
        $(this).siblings('.relation_modify').show();
    });
</script>
