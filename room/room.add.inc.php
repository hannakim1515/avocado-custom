<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

/**
 * [추가] 페어 시스템 연동 로직
 */
$pair = sql_fetch(" select co_id from {$g5['couple_table']} where co_left = '{$character['ch_id']}' or co_right = '{$character['ch_id']}' ");
$room_key = $pair['co_id'] ? "pair_".$pair['co_id'] : "solo_".$character['ch_id'];
?>
<div class="info">
    <div class="ui-thumb">
        <img src="<?=$in['it_img']?>" />
    </div>
</div>
<div class="text">
    <p class="title">
        <?=$in['it_name']?>
        <span><?=number_format($in['it_sell'])?><?=$config['cf_money_pice']?></span>
    </p>
    <div>
        <p><?=$in['it_content']?></p>
    </div>
</div>

<form action="<?=G5_URL?>/room/room_add_update.php" method="post" name="frmItemAdd" enctype="multipart/form-data">
    <input type="hidden" name="type" value="add" />
    <input type="hidden" name="in_id" value="<?=$in['in_id']?>" />
    
    <input type="hidden" name="ch_id" value="<?=$room_key?>" />
    
    <input type="hidden" name="url" value="<?=$url?>" />
    
    <div class="add-item-form">
        <div class="item-info">
            <input type="file" name="ro_img" class="required" required/>
        </div>
    </div>
    <div class="control-box">
        <button type="submit" class="ui-btn simple">등록하기</button>
    </div>
</form>