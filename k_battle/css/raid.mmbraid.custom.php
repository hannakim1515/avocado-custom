<style>
    .monster-area{
        <?php
        if($raid_css[0]){ echo "max-width:{$raid_css[0]};";}
        if($raid_css[1]){ echo "max-height:{$raid_css[1]};";}
        if($raid_css[2]){ echo "border:{$raid_css[2]};";}
        if($raid_css[3]){ echo "background-color:{$raid_css[3]};";}
        if($raid_css[4]){ echo "border-radius:{$raid_css[4]};";}
        ?>
    }

    .monster-area .monster-image{
        <?php
        if($raid_css[5]){ echo "max-width:{$raid_css[5]};";}
        if($raid_css[6]){ echo "max-height:{$raid_css[6]};";}
        if($raid_css[7]){ echo "border:{$raid_css[7]};";}
        if($raid_css[8]){ echo "background-color:{$raid_css[8]};";}
        if($raid_css[9]){ echo "border-radius:{$raid_css[9]};";}
        ?>
    }

    .monster-info{
        <?php
        if($raid_css[5]){ echo "max-width:{$raid_css[5]};";}
        ?>
    }

    .monster-info .mo-name{
        <?php
        if($raid_css[10]){ echo "font-size:{$raid_css[10]};";}
        if($raid_css[11]){ echo "color:{$raid_css[11]};";}
        ?>
    }

    .system-msg{
        <?php
        if($raid_css[12]){ echo "font-size:{$raid_css[12]};";}
        if($raid_css[13]){ echo "color:{$raid_css[13]};";}
        ?>
    }

    .monster-info .hp-bar{
        <?php
        if($raid_css[16]){ echo "background-color:{$raid_css[16]};";}

        ?>
    }

    .monster-info .hp-bar .bar-inner{
        <?php
        if($raid_css[14]){ echo "background-color:{$raid_css[14]};";}
        ?>
    }

    .rm-area{
        <?php
        if($comment_css[0]){ echo "max-width:{$comment_css[0]};";}
        if($comment_css[1]){ echo "max-height:{$comment_css[1]};";}
        if($comment_css[2]){ echo "border:{$comment_css[2]};";}
        if($comment_css[3]){ echo "background-color:{$comment_css[3]};";}
        if($comment_css[4]){ echo "border-radius:{$comment_css[4]};";}
        ?>
    }

    .rm-area .ui-thumb{
        <?php
        if($comment_css[5]){ echo "max-width:{$comment_css[5]};";}
        if($comment_css[6]){ echo "max-height:{$comment_css[6]};";}
        if($comment_css[7]){ echo "border:{$comment_css[7]};";}
        if($comment_css[8]){ echo "background-color:{$comment_css[8]};";}
        if($comment_css[9]){ echo "border-radius:{$comment_css[9]};";}
        ?>
    }

    .rm-area .rm-name{
        <?php
        if($comment_css[10]){ echo "font-size:{$comment_css[10]};";}
        if($comment_css[11]){ echo "color:{$comment_css[11]};";}
        ?>
    }

    .rm-msg{
        <?php
        if($comment_css[12]){ echo "font-size:{$comment_css[12]};";}
        if($comment_css[13]){ echo "color:{$comment_css[13]};";}
        ?>
    }

    .rm-area .hp-bar .bar-inner{
        <?php
        if($comment_css[14]){ echo "background-color:{$comment_css[14]};";}

        ?>
    }
    
    .rm-area .mp-bar .bar-inner{
        <?php
        if($comment_css[15]){ echo "background-color:{$comment_css[15]};";}
        ?>
    }

    .rm-area .hp-bar, .rm-area .mp-bar{
        <?php
        if($comment_css[16]){ echo "background-color:{$comment_css[16]};";}
        ?>
    }

    <?php if($board['bo_11']=='false'){?>
        #log_list .item.raid_admin .item-comment-form-box,
        #log_list .item.raid_admin .ui-comment .item-comment:not(:first-child){
            display:none!important;
        }
    <?php }?>

    #log_list .item.raid_admin .pic-header:before{
        <?php
        if($log_css[0]){ echo "content:'{$log_css[0]}';";}
        if($log_css[1]){ echo "color:{$log_css[1]};";}
        if($log_css[2]){ echo "font-size:{$log_css[2]};";}
        ?>
    }

    #log_list .item.raid_admin{
        <?php
        if($log_css[3]){ echo "border:{$log_css[3]};";}
        if($log_css[4]){ echo "background:{$log_css[4]};";}
        if($log_css[5]){ echo "border-radius:{$log_css[5]};";}
        if($log_css[6]){ echo "color:{$log_css[6]};";}
        if($log_css[7]){ echo "font-size:{$log_css[7]};";}
        ?>
    }
    .raid_admin .log-area{
        <?php
        if($log_css[8]){ echo "max-width:{$log_css[8]};";}
        if($log_css[9]){ echo "border:{$log_css[9]};";}
        if($log_css[10]){ echo "background:{$log_css[10]};";}
        if($log_css[11]){ echo "border-radius:{$log_css[11]};";}
        if($log_css[12]){ echo "color:{$log_css[12]};";}
        if($log_css[13]){ echo "font-size:{$log_css[13]};";}
        ?>
    }

    #log_list .item.raid_admin .pic-header .del,
    #log_list .item.raid_admin .pic-header .mod{
        <?php if($log_css[6]){ echo "color:{$log_css[6]};";}?>
    }

    #log_list .item.raid_admin .ui-comment .item-comment:first-child{
        <?php  
        if($log_css[4]){ echo "background:{$log_css[4]};";}
        if($log_css[6]){ echo "color:{$log_css[6]};";}
        ?>
    }

    .log-area:not(.raid_admin .log-area){
        <?php
        if($log_css[14]){ echo "max-width:{$log_css[14]};";}
        if($log_css[15]){ echo "border:{$log_css[15]};";}
        if($log_css[16]){ echo "background:{$log_css[16]};";}
        if($log_css[17]){ echo "border-radius:{$log_css[17]};";}
        if($log_css[18]){ echo "color:{$log_css[18]};";}
        if($log_css[19]){ echo "font-size:{$log_css[19]};";}
        ?>
    }
    .log-area li:not(.raid_admin .log-area li){
        <?php if($log_css[14]){ echo "max-width:{$log_css[14]};";}?>
    }
    .log-area li.mmbsystem{
        <?php if($log_css[1]){ echo "color:{$log_css[1]};";}?>
    }
</style>
