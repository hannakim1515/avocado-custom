<style>
    @import url(<?php echo G5_URL ?>/quest/quest.css);
    :root {
        --quest-main-color: <?php echo h($qu_cf['qc_main_color']); ?>;
        --quest-sub-color: <?php echo h($qu_cf['qc_sub_color']); ?>;
        --quest-member-color: <?php echo h($qu_cf['qc_member_color']); ?>;
        --quest-main-text-color: <?php echo h($qu_cf['qc_main_text_color']); ?>;
        --quest-sub-text-color: <?php echo h($qu_cf['qc_sub_text_color']); ?>;
        --quest-member-text-color: <?php echo h($qu_cf['qc_member_text_color']); ?>;
    }
</style>
<div class="quest_board">
    <?php
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
    $qu_list = array();
    $cnt = 0;
    if($character['ch_id']){
        $display_count = (int)$qu_cf['qc_main_display_count'];
        $qu_list = get_quest_list($character['ch_id'], 'now', '', $display_count);
        $cnt = count($qu_list);
    }?>
    <ul class="quest_list">
        <?php for ($i=0; $i < $cnt; $i++) {?>
            <li class="<?php echo $qu_list[$i]['qu_type']?> <?php if($qu_list[$i]['ch_id']){echo "has";}?>" <?php if($qu_list[$i]['qh_state']=='완료'){ echo "style='filter:grayscale(1);'";}?> onclick="window.open('<?php echo G5_URL?>/quest/?q_id=<?php echo $qu_list[$i]['q_id']?>', '_blank')">
                <?php echo $qu_list[$i]['qu_title']?>
                <?php if($qu_list[$i]['qh_state']){echo "<span>{$qu_list[$i]['qh_state']}</span>";}?>
            </li>
        <?php }
       if(!$i){?>
            <li class="none" onclick="window.open('<?php echo G5_URL?>/quest/', '_blank')">
                게시판에 <?php echo h($qc_title); ?>가 없습니다.
            </li>
        <?php }?>
    </ul>
</div>