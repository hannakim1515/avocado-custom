<?php
include_once('./_common.php');

global $qu_cf;
$qc_title = ses($qu_cf, 'qc_title', '퀘스트', 'raw');

$g5['title'] = $qc_title;
include_once('./_head.sub.php');

// 파라미터
$type = ses($_GET, 'type', 'now', 'raw');
$q_id = ses($_GET, 'q_id', 0, 'int');

// 일일 자동 처리: n일 경과한 멤버 퀘스트 실패 처리
$today = G5_TIME_YMD;
$reset_days = (int)$qu_cf['qc_reset_days'];

if ($qu_cf['qc_last_reset_date'] != $today) {
    sql_query("UPDATE {$g5['k_quest_config_table']} SET qc_last_reset_date = '{$today}'");
    
    // n일 경과한 멤버 퀘스트의 qu_take_now 감소 (실패 처리 전에 카운트)
    sql_query("
        UPDATE {$g5['k_quest_table']} qu
        SET qu.qu_take_now = GREATEST(0, qu.qu_take_now - (
            SELECT COUNT(*) FROM {$g5['k_quest_has_table']} qh
            WHERE qh.qu_id = qu.qu_id
              AND DATEDIFF(NOW(), qh.qh_starttime) >= {$reset_days}
              AND qh.qh_state = '수행중'
        ))
        WHERE qu.qu_type = 'member'
          AND EXISTS (
            SELECT 1 FROM {$g5['k_quest_has_table']} qh
            WHERE qh.qu_id = qu.qu_id
              AND DATEDIFF(NOW(), qh.qh_starttime) >= {$reset_days}
              AND qh.qh_state = '수행중'
          )
    ");
    
    // n일 경과한 멤버 퀘스트 실패 처리
    sql_query("
        UPDATE {$g5['k_quest_has_table']} qh
        INNER JOIN {$g5['k_quest_table']} qu ON qu.qu_id = qh.qu_id
        SET qh.qh_state = '실패'
        WHERE DATEDIFF(NOW(), qh.qh_starttime) >= {$reset_days}
          AND qh.qh_state = '수행중'
          AND qu.qu_type = 'member'
    ");
    
    $qu_cf['qc_last_reset_date'] = $today;
}

// 퀘스트 목록 조회
$qu_list = array();
if ($character['ch_id']) {
    $qu_list = get_quest_list($character['ch_id'], $type);
}

?>
<link rel="stylesheet" href="./quest.css">
<style>
:root {
    --quest-main-color: <?php echo h($qu_cf['qc_main_color']); ?>;
    --quest-sub-color: <?php echo h($qu_cf['qc_sub_color']); ?>;
    --quest-member-color: <?php echo h($qu_cf['qc_member_color']); ?>;
    --quest-main-text-color: <?php echo h($qu_cf['qc_main_text_color']); ?>;
    --quest-sub-text-color: <?php echo h($qu_cf['qc_sub_text_color']); ?>;
    --quest-member-text-color: <?php echo h($qu_cf['qc_member_text_color']); ?>;
}
</style>

<div class="quest_wrapper">
    <div class="quest-inner">
        <div class="list_area">
            <button class="list_toggle" onclick="toggleListArea()" title="목록 접기/펼치기">
                <span class="toggle_icon">◀</span>
            </button>
            <ul class="quest_category">
                <li <?php if($type=='now'){echo 'class="on"';}?>><a href="./index.php?type=now">모집중인 <?php echo h($qc_title); ?></a></li>
                <li <?php if($type=='past'){echo 'class="on"';}?>><a href="./index.php?type=past">지나간 <?php echo h($qc_title); ?></a></li>
            </ul>
            <ul id="quest_list" class="quest_list">
                <?php foreach ($qu_list as $quest) { ?>
                        <li class="<?php echo $quest['qu_type']; ?> <?php if($quest['qh_id']){ echo 'has'; } ?>" data-idx="<?php echo $quest['q_id']; ?>">
                            <?php echo $quest['qu_title']; ?>
                            <?php if($quest['qh_state']){ echo "<span>{$quest['qh_state']}</span>"; } ?>
                        </li>
                <?php } ?>
            </ul>
            <?php if($qu_cf['qc_member_quest_enabled']) { ?>
                <div class="quest_member_btn">
                    <ul class="quest_category">
                        <li><a href="javascript:void(0);" onclick="loadInsertQuest()">멤버 <?php echo h($qc_title); ?> 등록 <?php echo !isset($character['ch_give_quest'])||empty($character['ch_give_quest']) ? 0 : $character['ch_give_quest']?>/<?php echo $qu_cf['qc_quarter_give_max']?></a></li>
                        <li <?php if($type=='member'){echo 'class="on"';}?>><a href="./index.php?type=member">멤버 <?php echo h($qc_title); ?> 수행  <?php echo !isset($character['ch_receive_quest'])||empty($character['ch_receive_quest']) ? 0 : $character['ch_receive_quest']?>/<?php echo $qu_cf['qc_quarter_receive_max']?></a></li>
                    </ul>
                </div>
            <?}?>
        </div>
        <div class="info_area">
            <div id="quest_info">
                <p class="quest_alert">이 곳에 <?php echo h($qc_title); ?> 내용이 표시됩니다.</p>
            </div>
        </div>
    </div>
   
</div>

<script>
var questCache = {};
var initialQuestId = <?php echo $q_id ? $q_id : 'null'; ?>;

// 퀘스트 로드 함수
function loadQuest(questId) {
    if (!questId) return;
    
    // 목록에서 선택 상태 변경
    $("#quest_list li").removeClass('on');
    $("#quest_list li[data-idx='" + questId + "']").addClass('on');
    
    // 캐시 확인
    if (questCache[questId]) {
        $('#quest_info').html(questCache[questId]);
        return;
    }
    
    // AJAX로 로드
    var h_link = "./_ajax.quest_info.php?qu_id=" + questId;
    $.ajax({
        url: h_link,
        success: function(data) {
            questCache[questId] = data;
            $('#quest_info').html(data);
        },
        error: function() {
            $('#quest_info').html('<p class="quest_alert">퀘스트 정보를 불러올 수 없습니다.</p>');
        }
    });
}

// 페이지 로드 시 초기 퀘스트 로드
$(document).ready(function() {
    if (initialQuestId) {
        loadQuest(initialQuestId);
    }
});

// 퀘스트 목록 클릭 이벤트
$("#quest_list li").on("click", function(){
    var idx = $(this).data('idx');
    loadQuest(idx);
    
    // URL 업데이트 (페이지 새로고침 없이)
    if (history.pushState) {
        var newUrl = window.location.pathname + '?type=<?php echo h($type); ?>&q_id=' + idx;
        history.pushState(null, '', newUrl);
    }
});

// 멤버 퀘스트 등록 폼 로드
function loadInsertQuest() {
    $("#quest_list li").removeClass('on');
    
    $.ajax({
        url: './_ajax.insert_quest.php',
        success: function(data) {
            $('#quest_info').html(data);
        },
        error: function() {
            $('#quest_info').html('<p class="quest_alert">폼을 불러올 수 없습니다.</p>');
        }
    });
}

// 퀘스트 목록으로 돌아가기
function loadQuestList() {
    $("#quest_list li").removeClass('on');
    $('#quest_info').html('<p class="quest_alert">이 곳에 <?php echo h($qc_title); ?> 내용이 표시됩니다.</p>');
}

// 리스트 영역 토글
function toggleListArea() {
    var listArea = $('.list_area');
    var toggleIcon = $('.toggle_icon');
    
    listArea.toggleClass('collapsed');
    
    if (listArea.hasClass('collapsed')) {
        toggleIcon.text('▶');
        localStorage.setItem('questListCollapsed', 'true');
    } else {
        toggleIcon.text('◀');
        localStorage.setItem('questListCollapsed', 'false');
    }
}

// 페이지 로드 시 이전 상태 복원
$(document).ready(function() {
    var isCollapsed = localStorage.getItem('questListCollapsed') === 'true';
    if (isCollapsed) {
        $('.list_area').addClass('collapsed');
        $('.toggle_icon').text('▶');
    }
});

// 직접 완료 (direct 타입)
function completeQuestDirect(qu_id, log_url) {
    if (!confirm('<?php echo h($qc_title); ?>을(를) 완료하시겠습니까?')) {
        return;
    }
    
    $.ajax({
        url: './_ajax.complete_direct.php',
        type: 'POST',
        dataType: 'json',
        data: {
            qu_id: qu_id,
            log_url: log_url
        },
        success: function(res) {
            if (res.success) {
                alert(res.message);
                // 캐시 초기화 후 페이지 새로고침
                questCache = {};
                location.reload();
            } else {
                alert(res.message);
            }
        },
        error: function() {
            alert('오류가 발생했습니다.');
        }
    });
}
</script>
<?php
include_once('./_tail.sub.php');
?>

