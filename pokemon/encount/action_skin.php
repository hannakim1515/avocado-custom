
<?
$po_data_log = explode("||", $log_comment['wr_pokemon_log']);
if($po_data_log[1]=='battle'){
    @include(G5_PATH."/pokemon/battle/action_skin.php");
}else{
    if($po_data_log[1]=='encount'){
        $encount_id=$po_data_log[2];
        $lo=sql_fetch (" SELECT * from {$g5['pokemon_encount_table']} lo, {$g5['pokemon_map_table']} ma where ma.ma_id=lo.ma_id and lo.lo_id='{$encount_id}'");
        if($lo['ma_img_action']){$action_bg=$lo['ma_img_action'];}else{$action_bg=$cm_list[$po_data_log[1]]['ma_img_action'];}
    }else{
        $action_bg=$cm_list[$po_data_log[1]]['ma_img_action'];
    }?>
    <div class="pokemon-action <?=$po_data_log[1]?>" style="background-image:url(<?=$action_bg?>)">
        <?if($po_data_log[1]=='encount'){?>
            <div class="pokemon_encount <?if(!$encount_id){?>fail<?}?>">
                <?if(!$encount_id){?>
                    <div class="pokemon-action-msg">
                        <p><?if($po_data_log[3]){echo $po_data_log[3];}else{ echo "근처에 포켓몬이 보이지 않는다... 포켓몬 스낵을 다시 챙겼다.";}?></p>
                    </div>
                <?}else{
                    if($lo['lo_catch']){
                        if(!$lo['po_egg']){
                            $en_po=get_wild_pokemon($lo['lo_catch']);?>
                            <img src="<?=G5_URL?>/pokemon/img/<?=$en_po['po_id']?>.png">
                            <div class="pokemon-action-msg">
                                <p>숨어있던 포켓몬은... <?=$en_po['po_species']?>(이)다!</p>
                                <p>만난 포켓몬 리스트에 <?=$en_po['po_species']?>이(가) 등록되었습니다.</p>
                            </div>
                        <?}else{?>
                            <img src="<?=G5_URL?>/pokemon/img/9999.png">
                            <div class="pokemon-action-msg">
                                <p>숨어있던 포켓몬은... 이건 포켓몬 알이다!</p>
                                <p>포켓몬 알을 주웠다.</p>
                            </div>
                        <?}
                    }else{
                        $encount_list=explode("|",$lo['po_id']);
                        $egg_list=explode("|",$lo['po_egg']);
                        $pokemon_list=array();?>

                        <div class="pokemon-action-msg">
                            <p>
                                <?if($lo['it_name']){echo "{$lo['it_name']}에 {$lo['it_value']} 포켓몬들이 관심을 보인다.";}else{echo "근처에서 부스럭거리는 소리가 들려온다...";}?>
                            </p>
                        </div>
                        
                        <?if($lo['ch_id']==$character['ch_id']){?>
                            <form action="<?=G5_URL?>/pokemon/encount/update.php" method="post">
                                <input type="hidden" value="<?=$lo['lo_id']?>" name="lo_id">
                                <input type="hidden" value="<?=$log_comment['wr_id']?>" name="wr_id">
                                <input type="hidden" value="<?=$bo_table?>" name="bo_table">
                        <?}?>
                            <ul class="encount_list">
                                <?for ($en=0; $en < count($encount_list); $en++) { 
                                    $en_po=get_wild_pokemon($encount_list[$en]);?>
                                    <li>
                                        <label for="encount_<?=$log_comment['wr_id']?>_<?=$en?>">
                                            <input type="radio" name="encount_<?=$log_comment['wr_id']?>" value="<?=($en+1)?>" id="encount_<?=$log_comment['wr_id']?>_<?=$en?>">
                                        <p class="encount_img">
                                            <?if($egg_list[$en]){?>
                                                <img src="<?=G5_URL?>/pokemon/img/9999.png">
                                            <?}else{?>
                                                <img src="<?=G5_URL?>/pokemon/img/<?=$en_po['po_id']?>.png">
                                            <?}?>
                                    
                                        </p>
                                        <p class="encount_type">
                                            <?if($en_po['po_type1']){echo "<span>{$en_po['po_type1']}</span>";}
                                            if($en_po['po_type2']){echo "<span>{$en_po['po_type2']}</span>";}?>
                                        </p>
                                        </label>
                                    </li>
                                <?}?>
                            </ul>
                        <?if($lo['ch_id']==$character['ch_id']){?>
                            <button type="submit" value="y" class="ui-btn">아이템을 준다</button>
                        </form><?}?>
                    <?}
                }?>
            </div>
        <?}elseif($po_data_log[1]=='evolution'){?>
            <img src="<?=G5_URL?>/pokemon/img/<?=$po_data_log[4]?>.png">
            <div class="pokemon-action-msg">
                <p>축하합니다!</p>
                <p><?=$po_data_log[2]?>은(는) <?=$po_data_log[3]?>(으)로 진화했습니다!</p>
            </div>
        <?}elseif($po_data_log[1]=='partner'){?>
            <img src="<?=$po_data_log[3]?>">
            <div class="pokemon-action-msg">
                <p><?=$po_data_log[2]?>이(가) 파트너가 되었습니다.</p>
                <p><?=$po_data_log[2]?>, 잘 부탁해!</p>
            </div>
        <?}elseif($po_data_log[1]=='release'){?>
            <img src="<?=$po_data_log[3]?>">
            <div class="pokemon-action-msg">
                <p><?=$po_data_log[2]?>을(를) 떠나보냈습니다.</p>
                <p>바이바이, <?=$po_data_log[2]?>!</p>
            </div>
            
        <?}?>
    </div>
<?}?>