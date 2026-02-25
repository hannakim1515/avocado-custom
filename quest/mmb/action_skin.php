<?php
$qh_id = (int)$log_comment['qh_id'];
$qh = sql_fetch("SELECT qu.qu_end_msg 
                  FROM {$g5['k_quest_table']} qu 
                  INNER JOIN {$g5['k_quest_has_table']} qh ON qh.qu_id = qu.qu_id 
                  WHERE qh.qh_id = '{$qh_id}'");
?>

<div class="quest-box">
    <p style="text-align:center;"><strong><?php echo h($qu_cf['qc_title']); ?> 완료!</strong></p>
    <p style="text-align:center;"><?php echo h($qh['qu_end_msg'])?></p>
</div>