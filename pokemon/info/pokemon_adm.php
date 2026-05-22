
<div class="pokemon-adm">
    <?if($ch['ph_id']!=$po['ph_id']){?>
        <a href="<?=G5_URL?>/pokemon/info/pokemon_update.php?type=main&ph_id=<?=$po['ph_id']?>" class="ui-btn">
            이 포켓몬을 메인파트너로
        </a>
    <?}?>
   
    <form action="<?=G5_URL?>/pokemon/info/pokemon_update.php" enctype="multipart/form-data" method="post">
        <input type="hidden" name="ph_id" value="<?=$po['ph_id']?>">
        <input type="hidden" name="type" value="update">
        
        <p>
            <span>닉네임<br>(5자 이하)</span>
            <input type="text" name="po_name" maxlength="5" value="<?=$po['po_name']?>">
        </p>
        <p>
            <span>포켓몬 설명<br>(140자 이하)</span>
            <textarea name="po_content" maxlength="140"><?=$po['po_content']?></textarea>
        </p>
        <p>	
            <span>포켓몬 도트</span>
            <input type="file" name="po_dot_file" accept="image/*"/>
            <input type="hidden" name="po_dot" value="<?php echo $po['po_dot'] ?>" />
            <img src="<?=$po['po_dot']?>">
        </p>
        <button type="submit" class="ui-btn point">변경</button>
    </form>
</div>

<div class="pokemon_item">
    <?$ribbon_list=array();
    if($po['po_ribbon']['in_id']){$ribbon_list[]=$po['po_ribbon'];}
    $ribbon_sql = sql_query("SELECT * from {$g5['inventory_table']} inven 
                            left join {$g5['pokemon_has_table']} ph on inven.in_id = ph.po_ribbon
                            right join {$g5['item_table']} it on inven.it_id = it.it_id
                            where inven.ch_id='{$character['ch_id']}'
                            and it.it_type='리본'
                            and ph.ph_id is null");
    for($i = 0; $row = sql_fetch_array($ribbon_sql); $i++){
        $ribbon_list[] = $row;
    };

    $ball_list=array();

    if($po['po_ball']['in_id']){$ball_list[]=$po['po_ball'];}
    $ball_sql = sql_query("SELECT * from {$g5['inventory_table']} inven 
                            left join {$g5['pokemon_has_table']} ph on inven.in_id = ph.po_ball
                            right join {$g5['item_table']} it on inven.it_id = it.it_id
                            where inven.ch_id='{$character['ch_id']}'
                            and it.it_type='볼캡'
                            and ph.ph_id is null");
    for($i = 0; $row = sql_fetch_array($ball_sql); $i++){
        $ball_list[] = $row;
    };

    ?>
    <div class="item_list_area">
        <ul class="inventory-list" id="po_ribbon" data-type="po_ribbon">
            <? for ($i=0; $i < count($ribbon_list); $i++) { 
                $it=$ribbon_list[$i];?>
                <li class="<?if($i==0&&$it['in_id']==$po['po_ribbon']['in_id']){echo "on";}?>" id="in_<?=$it['in_id']?>" data-idx="<?=$it['in_id']?>">
                    <img src="<?=$it['it_img']?>" />
                </li>
            <?}?>
        </ul>
        <ul class="inventory-list" id="po_ball" data-type="po_ball">
            <? for ($i=0; $i < count($ball_list); $i++) { 
                $it=$ball_list[$i];?>
                <li class="<?if($i==0&&$it['in_id']==$po['po_ball']['in_id']){echo "on";}?>" id="in_<?=$it['in_id']?>" data-idx="<?=$it['in_id']?>">
                    <img src="<?=$it['it_img']?>" />
                </li>
            <?}?>
        </ul>
    </div>
    <div class="item_info_area">이 곳에 아이템 정보가 표시됩니다.</div>
</div>

<script>
    var ph_id = '<?=$ph_id?>';
    $(".pokemon_item .inventory-list li").on("click",function(){
        var type=$(this).parent().data('type');
        var in_id=$(this).data('idx');

        var formData = new FormData();
        formData.append('type', type);
        formData.append('in_id', in_id);
        formData.append('ph_id', ph_id);
        $(this).addClass('selected');
        $(this).siblings('li').removeClass('selected');
        $.ajax({
            type: 'post'
            , url : g5_url + '/pokemon/info/item_info.php'
            , data: formData
            , dataType:'json'
            , contentType: false
            , processData: false
            , success : function(data) {
                $(".item_info_area").empty();
                $(".item_info_area").html(data.html);
            }
        });

    });
    function pokemon_equip(e){
        var type=$(e).data('type');
        var in_id=$(e).data('idx');
        var formData = new FormData();
        formData.append('equip', true);
        formData.append('type', type);
        formData.append('in_id', in_id);
        formData.append('ph_id', ph_id);
        $.ajax({
            type: 'post'
            , url : g5_url + '/pokemon/info/item_info.php'
            , data: formData
            , dataType:'json'
            , contentType: false
            , processData: false
            , success : function(data) {
                $(".item_info_area").empty();
                $(".item_info_area").html(data.html);
                if(data.result=='on'){
                    $('#in_'+in_id).addClass('on');
                    $('#in_'+in_id).siblings('li').removeClass('on');
                }else if(data.result=='off'){
                    $('#in_'+in_id).removeClass('on');
                }
                console.log(data.img,data.title)
                if(data.img){
                    $('.'+type+'_img').html("<img src='"+data.img+"'>")
                }else{
                    $('.'+type+'_img').empty();
                }
                if(data.title){
                    $('.'+type+'_title').html("["+data.title+"]")
                }else{
                    $('.'+type+'_title').empty();
                }
            }
        });
    }
</script>

