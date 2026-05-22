<?$egg_log=explode("|", $log_comment['po_egg']);
if($egg_log[0]){?>
    <div class="pokemon-action egg">
        <div class="egg_area">
            <div class="pokemon-action-msg border_box border_2">
                <img src="<?=G5_URL?>/pokemon/img/egg/egg_center.png"><p><?=$egg_log[3]?></p>
            </div>
        </div>
    </div>
<?}else{?>
    <div class="pokemon-action hatch border_box border_3"  style="background-image:url(<?=$cm_list['hatch']['ma_img_action']?>)">
        <img src="<?=$egg_log[2]?>">
        <div class="pokemon-action-msg border_box border_2">
            <p>어라?</p>
            <p><?=$egg_log[3]?></p>
        </div>
    </div>
<?}?>

