<?php
$ug_list = sql_query("select * from {$g5['k_upgrade_table']} ");
for($i = 0; $row = sql_fetch_array($ug_list); $i++) {
    if($row['ug_bg_color']){?>
        .equip-img:has(.eq_lv.lv_<?php echo $row['ug_id']?>):before{
            background:linear-gradient(to top, <?php echo $row['ug_bg_color']?>, #7b7b7b00);
        }
    <?php }?>
    
    .eq_lv.lv_<?php echo $row['ug_id']?>{
        <?php 
        if($row['ug_color']){ echo "color:{$row['ug_color']};";}
        if($row['ug_tag_color']){ echo "background-color:{$row['ug_tag_color']};";}
        ?>
    }
<?php };?>