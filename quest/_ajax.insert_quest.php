<?php
include_once('./_common.php');

global $qu_cf;
$qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');
$max_give = (int)$qu_cf['qc_quarter_give_max'];
$max_take = (int)$qu_cf['qc_member_take_max'];

$qc_member_quest_type = ses($qu_cf, 'qc_member_quest_type', 'mmb,direct', 'raw');
$allowed_submit_types = array_map('trim', explode(',', $qc_member_quest_type));

if($character['ch_give_quest'] >= $max_give){
    echo '<p class="quest_alert">이미 '.h($qc_title).'를 충분히 올렸습니다.</p>';
    exit;
}
?>


    <form id="insert_quest_form" class="insert_quest" action="./insert_quest_update.php" method="post">
        <div class="quest_title member">
            멤버 <?php echo h($qc_title); ?> 등록
        </div>
        <div class="quest_content">
            <table>
                <colgroup>
                    <col style="width: 100px;" />
                    <col/>
                </colgroup>
                <tbody>
                    <tr>
                        <td style="text-align:center"><?php echo h($qc_title); ?> 제목</td>
                        <td>
                            <input type="text" name="qu_title" style="width:90%;" required placeholder="<?php echo h($qc_title); ?> 제목을 입력해 주세요">
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center">수행 인원</td>
                        <td>
                            <select name="qu_take_max" id="qu_take_max">
                                <?php for($i=1; $i<=$max_take; $i++){ ?>
                                <option value="<?php echo $i?>"<?php if($i==1){?> selected<?php }?>><?php echo $i?>명</option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center">작성자</td>
                        <td>
                            <select name="qu_blind">
                                <option value="0" selected>기명</option>
                                <option value="1">익명</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center">완료 방식</td>
                        <td>
                            <?php if (count($allowed_submit_types) == 1) { 
                                // 1개만 허용된 경우 hidden으로 전송
                                $only_type = $allowed_submit_types[0];
                            ?>
                                <input type="hidden" name="qu_submit_type" value="<?php echo h($only_type); ?>">
                                <?php if ($only_type == 'mmb') { ?>
                                    자비란 경유 (글 작성)
                                <?php } else { ?>
                                    직접 완료
                                <?php } ?>
                            <?php } else { ?>
                                <select name="qu_submit_type">
                                    <?php if (in_array('mmb', $allowed_submit_types)) { ?>
                                        <option value="mmb" selected>자비란 경유 (글 작성)</option>
                                    <?php } ?>
                                    <?php if (in_array('direct', $allowed_submit_types)) { ?>
                                        <option value="direct">직접 완료</option>
                                    <?php } ?>
                                </select>
                            <?php } ?>
                            <span style="color:#888; font-size:12px;">※ 직접 완료: <?php echo h($qc_title); ?> 페이지에서 바로 완료</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center"><?php echo h($qc_title); ?> 내용</td>
                        <td>
                            <textarea style="height:300px" required name="qu_content" placeholder="<?php echo h($qc_title); ?> 내용을 입력해 주세요
ex)밥을 같이 먹을 사람이 필요해!"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center">요구 사항</td>
                        <td>
                            <textarea required name="qu_content2" placeholder="<?php echo h($qc_title); ?>의 요구사항을 입력해 주세요.
ex)같이 밥을 먹는 로그"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center">완료 메시지</td>
                        <td>
                            <input type="text" name="qu_end_msg" style="width:90%;" required placeholder="완료시 출력될 메시지를 입력해 주세요">
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center"><?php echo h($qc_title); ?> 보상</td>
                        <td>
                            <input type="hidden" name="re_it_id" id="re_it_id" value="" />
                            <input type="text" placeholder="아이템 이름을 입력해 주세요.(*필수 아님)" name="re_it_name" value="" id="re_it_name" onkeyup="get_ajax_quest_item(this, 're_item_list', 're_it_id');" />
                            <div id="re_item_list" class="ajax-list-box"><div class="list"></div></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="quest_control">
            <div class="quest_action member">
                <button type="submit" class="ui-btn">등록하기</button>
            </div>
        </div>
    </form>

<script>
    let min_cnt = 1;
    
    function get_ajax_quest_item(obj, list_id, rel_id) {
        var url = g5_url + "/quest/_mission_item.php";
        ajax_load(url, obj, list_id, rel_id, min_cnt);
        $('#re_it_id').val('');
    }
    
    $("#qu_take_max").on("change", function() {
        var newCount = parseInt($(this).val());
        if(min_cnt < newCount){
            $('#re_item_list .list').empty();
            $('#re_it_id').val('');
            $('#re_it_name').val('');
        }
        min_cnt = newCount;
        // 입력 필드에 새로운 카운트로 다시 검색 트리거
        if($('#re_it_name').val()) {
            get_ajax_quest_item($('#re_it_name')[0], 're_item_list', 're_it_id');
        }
    });
</script>
