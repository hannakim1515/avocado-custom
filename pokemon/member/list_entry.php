<ul class="partner-list">
    <?
    $po_list=get_pokemon_list($ch['ch_id'],false);
    for ($h=0; $h < $pkm_cf['po_max'] ; $h++) { 
        if($po_list[$h]['po_dot']){
            echo "<li><img src='{$po_list[$h]['po_dot']}'></li>";
        }else{
            echo "<li><img src='".G5_URL."/pokemon/img/objects/ball_grey.png'></li>";
        }
    }?>
</ul>