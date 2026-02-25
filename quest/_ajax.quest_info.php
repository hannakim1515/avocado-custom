<?php
include_once("./_common.php");

$reset_days = (int)$qu_cf['qc_reset_days'];
$qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');

$qu_id = ses($_GET, 'qu_id', 0, 'int');
if (!$qu_id) {
    echo '<p class="quest_alert">잘못된 접근입니다.</p>';
    exit;
}

$qu = get_quest($qu_id, $character['ch_id']);
if (!$qu['q_id']) {
    echo '<p class="quest_alert">'.h($qc_title).'를 찾을 수 없습니다.</p>';
    exit;
}

?>
<div class="quest_title <?php echo $qu['qu_type']?>">
    <?php echo $qu['qu_title']?>
</div>

<div class="quest_content">
    <p class='content1'><?php echo nl2br($qu['qu_content'])?>
    <?php if($qu['qu_type']=='member'&&!$qu['qu_blind']){?>
       <span style="display:inline-block; width:100%; text-align:right; padding-top:3px;">-<?php echo get_character_name($qu['qu_ch_id']);?></span>
    <?php }?>
    </p>

</div>

<div class="quest_control">

    <?php if ($qu['qu_submit_type'] != 'direct'||$qu['qu_complete_type'] == 'log') {?>
        <div class="quest_member">
            <?php
            $mb_done_list=array();
            $mb_sql = sql_query("SELECT * from {$g5['k_quest_has_table']} qh, {$g5['character_table']} ch where ch.ch_id=qh.ch_id and qh.qu_id='{$qu_id}' and ch.ch_state='승인' and qh.qh_state='완료'");
            for($i = 0; $row = sql_fetch_array($mb_sql); $i++) {
                $mb_done_list[] = $row;
            };?>
            <p class="quest_done">
                <?php for ($i=0; $i < count($mb_done_list); $i++) { 
                echo "<a target='_blank' href='{$mb_done_list[$i]['qh_log']}'>{$mb_done_list[$i]['ch_name']}</a>";
                }?>
            </p>
        </div>
    <?php }?>

    <?php if($qu['qu_content2']){?>
        <p class='content2'><?php echo nl2br($qu['qu_content2'])?></p>
    <?php }?>

    <div class="quest_reward">
        <?php if($qu['qu_money']){?>
            <p class="money"><?php echo $qu['qu_money']?></p>
        <?php }
        if($qu['qu_exp']){?>
            <p class="exp"><?php echo $qu['qu_exp']?></p>
        <?php }
        if($qu['it_id']){
            $it=get_item($qu['it_id']);?>
            <p class="item"><img src="<?php echo $it['it_img']?>" title="<?php echo $it['it_name']?>"></p>
        <?php }
        if($qu['ti_id']){
            $ti_img=get_title_image($qu['ti_id']);
        ?>
            <p class="title"><?php echo $ti_img?></p>
        <?php }?>
    </div>

    <div class="quest_action <?php echo $qu['qu_type']?>">
        <?php
        if($qu['qh_state']=='수행중'){
            echo h($qc_title)." 수행중입니다.";
            if($qu['qu_type']=='member'){
                $days_left = $reset_days - 1;
                $future_timestamp = strtotime("+{$days_left} days", strtotime($qu['qh_starttime']));
                $future_date = date('m/d', $future_timestamp);
                echo " ({$future_date}까지 수행 가능)";
            }
            
            // direct 타입인 경우 직접 완료 버튼 표시
            if ($qu['qu_submit_type'] == 'direct') {
                echo '<div class="quest_direct_complete" style="margin-top:15px;">';
                
                // 아이템 제출 타입인 경우
                if ($qu['qu_complete_type'] == 'item' && $qu['qu_request_item']) {
                    $req_item = get_item($qu['qu_request_item']);
                    $owned_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['inventory_table']} 
                                            WHERE ch_id = '{$character['ch_id']}' 
                                            AND it_id = '{$qu['qu_request_item']}'");
                    $is_enough = ($owned_cnt['cnt'] >= $qu['qu_request_item_count']);
                    $status_color = $is_enough ? '#080' : '#c33';
                    
                    echo '<p style="margin-bottom:10px; text-align:center;">필요 아이템: <strong>'.h($req_item['it_name']).'</strong> x '.$qu['qu_request_item_count'].'개';
                    echo ' (보유: <span style="color:'.$status_color.'">'.$owned_cnt['cnt'].'개</span>)</p>';
                    
                    if ($is_enough) {
                        echo '<button type="button" class="ui-btn" onclick="completeQuestDirect('.$qu_id.', \'\')">';
                        echo '아이템 제출하고 '.h($qc_title).' 완료</button>';
                    } else {
                        echo '<button type="button" class="ui-btn" disabled style="opacity:0.5;cursor:not-allowed;">아이템 부족</button>';
                    }
                } else {
                    // 로그 타입인 경우 URL 입력
                    echo '<div style="margin-bottom:10px;">';
                    echo '<input type="text" id="quest_log_url_'.$qu_id.'" style="width:100%;padding:5px;margin-top:5px;" placeholder="로그 URL을 입력하세요">';
                    echo '</div>';
                    echo '<button type="button" class="ui-btn" onclick="completeQuestDirect('.$qu_id.', document.getElementById(\'quest_log_url_'.$qu_id.'\').value)">';
                    echo h($qc_title).' 완료</button>';
                }
                
                echo '</div>';
            }
        }elseif($qu['qh_state']=='완료'){
            echo "완료한 ".h($qc_title)."입니다.";
        }elseif($qu['qh_state']=='실패'){
            echo "기한이 지나 실패한 ".h($qc_title)."입니다.";
        }elseif($qu['qu_state']=='done'){
            echo "종료된 ".h($qc_title)."입니다.";
        }else{
            // 내가 등록한 멤버 퀘스트인 경우
            if($qu['qu_type']=='member' && $qu['qu_ch_id'] == $character['ch_id']){
                echo "내가 등록한 ".h($qc_title)."입니다.";
            }else{
                if($qu['qu_take_max']){
                    $qu_take=sql_fetch (" SELECT count(*) as cnt from {$g5['k_quest_has_table']} 
                    where qu_id='{$qu_id}' and qh_state!='실패' ");
                    if($qu_take['cnt']>=$qu['qu_take_max']){
                        echo h($qc_title)." 수행 인원이 가득 찼습니다.";
                    }else{
                        echo "<a href='".G5_URL."/quest/receive_quest.php?qu_id={$qu_id}' class='ui-btn'>".h($qc_title)." 받기 ({$qu_take['cnt']}/{$qu['qu_take_max']})</a>";
                    }
                }else{
                    echo "<a href='".G5_URL."/quest/receive_quest.php?qu_id={$qu_id}' class='ui-btn'>".h($qc_title)." 받기</a>";
                }
            }
        }
        ?>
    </div>
</div>
