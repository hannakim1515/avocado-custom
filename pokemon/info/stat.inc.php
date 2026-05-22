<?if(!$po['ph_id']||!$character['ch_id']){
    echo "포켓몬 정보를 찾을 수 없습니다.";
}else{
    if($po['ch_id']==$character['ch_id']){$ismine=true;}

    $berry_list=$stat_max=array();
    $berry_order=array($pkm_cf['st_1_it_id']=>1,$pkm_cf['st_2_it_id']=>2,$pkm_cf['st_3_it_id']=>3,$pkm_cf['st_4_it_id']=>4,$pkm_cf['st_5_it_id']=>5);
    $berry_sql = sql_query("SELECT it_id, it_name, it_img from {$g5['item_table']} where it_id in ({$pkm_cf['st_1_it_id']},{$pkm_cf['st_2_it_id']},{$pkm_cf['st_3_it_id']},{$pkm_cf['st_4_it_id']},{$pkm_cf['st_5_it_id']})");
    for($i = 0; $row = sql_fetch_array($berry_sql); $i++) {
        $cnt=sql_fetch (" SELECT count(in_id) as cnt from {$g5['inventory_table']} where ch_id= '{$character['ch_id']}' and it_id = '{$row['it_id']}' and is_del!='1'");
        $row['cnt']=$cnt['cnt'];
        $berry_list[$berry_order[$row['it_id']]]=$row;
    }
    		
    ?>
    
<style>
    @import url(<?=G5_URL?>/pokemon/info/stat.css);
</style>

<div class="notie">
    <p><span>성장</span> 아이템을 사용하여 포켓몬의 스탯을 올릴 수 있습니다.</p>
</div>

<ul class="po_list">
    <?for ($i=0; $i < count($po_list); $i++) {?>
        <li <?if($po_list[$i]['ph_id']==$ph_id){ echo "class='on'";}?>>
            <a href="<?=G5_URL?>/mypage/pokemon/item.php?ph_id=<?=$po_list[$i]['ph_id']?>">
                <img src="<?=$po_list[$i]['po_dot']?>"></p>
                <span><?=$po_list[$i]['po_name']?></span>
            </a>
        </li>
    <?}?>
</ul>



<div class="po_stat">

    
    <form action="<?=G5_URL?>/pokemon/info/pokemon_stat_update.php" method="post"  onsubmit="return form_submit(this);">
        <input type="hidden" name="ph_id" value="<?=$po['ph_id']?>">
        <ul class="st_list">
            <?for ($i=1; $i < 6; $i++) {
                $st_name="po_st".$i;
                if(!$berry_list[$i]['cnt']){$berry_list[$i]['cnt']=0;}
                $stat_max[$i]=20-$po[$st_name];
                ?>
                <li>
                    <p class="st_name"><?=$po_st_id[$i]?></p>
                    <div class="st_info">
                        <p class="st_point">
                            <span class="stat_has" id="stat_has_<?=$i?>"><?=$po[$st_name]?></span>/20
                        </p>
                    </div>
                    <div class="berry_info">
                        <?if($stat_max[$i]>$berry_list[$i]['cnt']){$stat_max[$i]=$berry_list[$i]['cnt'];}?>
                        <div class="cnt_area">
                            <span class="cnt_up <?if(!$stat_max[$i]){echo "disabled";}?>" id="cnt_up_<?=$i?>" onclick="stat_action('up',<?=$i?>)">+</span>
                            <span class="cnt_down disabled" id="cnt_down_<?=$i?>" onclick="stat_action('down',<?=$i?>)">-</span>
                        </div>
                        <div class="give_cnt" id="give_cnt_<?=$i?>" data-max="<?=$stat_max[$i]?>">
                            <span>0</span>
                            <input type="hidden" name="berry_cnt[<?=$i?>]" value="0" >
                        </div>
                        <div class="info">
                            <p class="berry_name"><img src="<?=$berry_list[$i]['it_img']?>"><?=$berry_list[$i]['it_name']?></p>
                            <div class="berry_has_cnt" id="berry_has_cnt"><?=$berry_list[$i]['cnt']?></div>
                        </div>
                    </div>
                </li>
            <?}?>
            <li><input type="submit" value="저장"></li>
        </ul>
    </form>
</div>

<script>
    var max_cnt=100;
    var origin_all_cnt=100;
    var all_cnt=100;

    function stat_action(type, id){
        var berry_cnt = $("#give_cnt_"+id+" input").val();
        var berry_cnt = Number(berry_cnt);
        var berry_max = $("#give_cnt_"+id).data('max');
        var stat_has= $("#stat_has_"+id).text();
        var stat_has=  Number(stat_has);

        if(type=='down'&&berry_cnt>0&&origin_all_cnt>all_cnt){
            all_cnt++;
            berry_cnt--;
            stat_has--;
            if(origin_all_cnt<=all_cnt){
                all_cnt=origin_all_cnt;
                $(".cnt_down").addClass('disabled');
            }else{
                if(berry_cnt<=0){
                    $("#cnt_down_"+id).addClass('disabled');
                }
                if(stat_has<20){
                    $("#cnt_up_"+id).removeClass('disabled');
                }
            }
            if(all_cnt==1){
                for (let index = 0; index < 6; index++) {
                    if(Number($("#give_cnt_"+index+" input").val())<Number($("#give_cnt_"+index).data('max'))&&Number($("#stat_has_"+index).text())<20){
                        $("#cnt_up_"+index).removeClass('disabled');
                    }
                }
            }
        }else if(type=='up'&&all_cnt>0&&berry_cnt<berry_max){
            all_cnt--;
            berry_cnt++;
            stat_has++;
            $("#cnt_down_"+id).removeClass('disabled');
            if(all_cnt<=0){
                all_cnt=0;
                $(".cnt_up").addClass('disabled');
            }else{
                if(berry_cnt>=berry_max){
                    $("#cnt_up_"+id).addClass('disabled');
                }else{
                    $("#cnt_up_"+id).removeClass('disabled');
                }
                if(stat_has>=20){
                    $("#cnt_up_"+id).addClass('disabled');
                }
            }
        }

        $("#give_cnt_"+id+" input").val(berry_cnt);
        $("#give_cnt_"+id+" span").text(berry_cnt);
        $("#stat_has_"+id).text(stat_has);
        $("#all_cnt").text(all_cnt);

    }

    function form_submit(f){

        if(all_cnt==origin_all_cnt){
            alert("줄 아이템을 선택해 주세요.");
            return false; 
        }
        if (!confirm("포켓몬에게 아이템을 주겠습니까?")){
           return false; 
        }

        return true;
    }
</script>

<?}?>