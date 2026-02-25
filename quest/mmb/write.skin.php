<?php
if ((!isset($write['qh_id']) || !$write['qh_id'])&& $character['ch_state'] === '승인') {
    $qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
    $qh_list=get_has_quest($character['ch_id']);

    // 아이템 제출 퀘스트 정보 준비 (JavaScript에서 사용)
    $quest_item_data = array();
    for ($i=0; $i < count($qh_list); $i++) {
        if ($qh_list[$i]['qu_complete_type'] == 'item' && $qh_list[$i]['qu_request_item']) {
            $req_item = get_item($qh_list[$i]['qu_request_item']);
            // 유저 보유 수량 조회
            $owned_cnt = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['inventory_table']} 
                                    WHERE ch_id = '{$character['ch_id']}' 
                                    AND it_id = '{$qh_list[$i]['qu_request_item']}'");
            $quest_item_data[$qh_list[$i]['qh_id']] = array(
                'type' => 'item',
                'item_id' => $qh_list[$i]['qu_request_item'],
                'item_name' => $req_item['it_name'],
                'item_count' => (int)$qh_list[$i]['qu_request_item_count'],
                'owned_count' => (int)$owned_cnt['cnt']
            );
        } else {
            $quest_item_data[$qh_list[$i]['qh_id']] = array(
                'type' => 'log'
            );
        }
    }

    if(count($qh_list)>0){?>
        <div class="inner">
            <dl>
                <dt>
                    <label for="use_item"><i class="icon item"></i><?php echo h($qc_title); ?></label>
                </dt>
                <dd>
                    <select name="qh_id" id="quest_select" onchange="onQuestSelect(this)">
                        <option value="">수행할 <?php echo h($qc_title); ?> 선택</option>
                        <?php for ($i=0; $i < count($qh_list); $i++) { ?>
                            <option value="<?php echo $qh_list[$i]['qh_id']; ?>" 
                                    data-type="<?php echo $qh_list[$i]['qu_complete_type'] == 'item' ? 'item' : 'log'; ?>">
                                <?php echo h($qh_list[$i]['qu_title']); ?>
                                <?php if ($qh_list[$i]['qu_complete_type'] == 'item') { echo '[아이템 제출]'; } ?>
                            </option>
                        <?php } ?>
                    </select>
                    
                    <!-- 로그 타입 안내 -->
                    <p id="quest_log_notice">※<?php echo h($qc_title); ?> 수행 시 등록하는 로그는 <?php echo h($qc_title); ?> 내용과 관련이 있어야 합니다.</p>
                    
                    <!-- 아이템 제출 타입 안내 -->
                    <div id="quest_item_notice" style="display:none; margin-top:10px; padding:10px; background:#f5f5f5cc; border-radius:5px;">
                        <p style="color:#333; font-weight:bold;">※ 이 <?php echo h($qc_title); ?>는 아이템 제출이 필요합니다.</p>
                        <p id="quest_item_info" style="margin-top:5px; color:#333; "></p>
                        <p style="margin-top:5px; font-size:12px; color:#666;">로그 등록 시 인벤토리에서 해당 아이템이 자동으로 차감됩니다.</p>
                    </div>
                </dd>
            </dl>
        </div>
        
        <script>
        var questItemData = <?php echo json_encode($quest_item_data); ?>;
        
        function onQuestSelect(select) {
            var qhId = select.value;
            var logNotice = document.getElementById('quest_log_notice');
            var itemNotice = document.getElementById('quest_item_notice');
            var itemInfo = document.getElementById('quest_item_info');
            
            if (!qhId || !questItemData[qhId] || questItemData[qhId].type == 'log') {
                // 로그 타입 또는 미선택
                logNotice.style.display = 'block';
                itemNotice.style.display = 'none';
            } else {
                // 아이템 타입
                var data = questItemData[qhId];
                var isEnough = data.owned_count >= data.item_count;
                var statusColor = isEnough ? '#080' : '#c33';
                var statusText = isEnough ? '(제출 가능)' : '(부족)';
                
                logNotice.style.display = 'none';
                itemNotice.style.display = 'block';
                itemInfo.innerHTML = '필요 아이템: <strong>' + data.item_name + '</strong> x ' + data.item_count + '개<br>' +
                                    '보유 수량: <strong style="color:' + statusColor + '">' + data.owned_count + '개 ' + statusText + '</strong>';
            }
        }
        </script>
    <?php } 

}?>
