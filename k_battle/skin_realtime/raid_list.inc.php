<?php

// li_id 파라미터로 리스트 조회
$li_id = ses($_GET, 'li_id', 0, 'int');

if ($li_id <= 0) {
    // li_id가 없으면 사용중인 첫번째 리스트 표시
    $list_row = sql_fetch("SELECT * FROM {$g5['k_raid_list']} WHERE li_use = 1 ORDER BY li_id ASC LIMIT 1");
    if (empty($list_row['li_id'])) {
        include_once('./_head.sub.php');
        echo '<div class="raid-list-wrap"><p class="empty">등록된 레이드 리스트가 없습니다.</p></div>';
        include_once('./_tail.sub.php');
        exit;
    }
} else {
    $list_row = sql_fetch("SELECT * FROM {$g5['k_raid_list']} WHERE li_id = '{$li_id}' AND li_use = 1");
    if (empty($list_row['li_id'])) {
        include_once('./_head.sub.php');
        echo '<div class="raid-list-wrap"><p class="empty">존재하지 않거나 비활성화된 리스트입니다.</p></div>';
        include_once('./_tail.sub.php');
        exit;
    }
}

$li_title  = $list_row['li_title'];
$raid_type = $list_row['raid_type'];
$ra_ids    = !empty($list_row['ra_ids']) ? explode(',', $list_row['ra_ids']) : array();

// 현재 캐릭터 ID
$my_ch_id = ses($character, 'ch_id', 0, 'int');

// 레이드 정보 조회
$raids = array();
if (!empty($ra_ids)) {
    foreach ($ra_ids as $ra_id) {
        $ra_id = trim($ra_id);
        if ($ra_id === '') continue;

        if ($raid_type === 'realtime') {
            $ra = sql_fetch("SELECT * FROM {$g5['k_realtime_table']} WHERE ra_id = '".sql_escape_string($ra_id)."'");
            if (!empty($ra['ra_id'])) {
                $ra['raid_url'] = G5_URL.'/k_battle/raid.php?ra_id='.urlencode($ra['ra_id']).'&raid_type=realtime';
                $ra['raid_type'] = 'realtime';
                
                // 참가 정보 - 실제 유닛 테이블에서 카운트
                $ra['ra_limit'] = ses($ra, 'ra_limit', 0, 'int');
                $count_row = sql_fetch("
                    SELECT COUNT(*) as cnt FROM {$battle_table}_unit 
                    WHERE ra_id = '".sql_escape_string($ra['ra_id'])."' 
                      AND unit_type = 'ch'
                ");
                $ra['ra_limit_now'] = ses($count_row, 'cnt', 0, 'int');
                $ra['ra_state'] = ses($ra, 'ra_state', 0, 'int'); // 0:준비중, 1:진행중, 2:종료
                
                // 현재 캐릭터 참가 여부 확인
                $ra['is_joined'] = false;
                $ra['my_rm_id'] = 0;
                if ($my_ch_id > 0) {
                    $joined_row = sql_fetch("
                        SELECT rm_id FROM {$battle_table}_unit 
                        WHERE ra_id = '".sql_escape_string($ra['ra_id'])."' 
                          AND unit_id = '{$my_ch_id}' 
                          AND unit_type = 'ch'
                    ");
                    if (!empty($joined_row['rm_id'])) {
                        $ra['is_joined'] = true;
                        $ra['my_rm_id'] = (int)$joined_row['rm_id'];
                    }
                }
                
                $raids[] = $ra;
            }
        } else {
            // mmbraid - 게시판 정보 조회
            $bo = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '".sql_escape_string($ra_id)."'");
            if (!empty($bo['bo_table'])) {
                $ra = array(
                    'ra_id'       => $bo['bo_table'],
                    'ra_title'    => $bo['bo_subject'],
                    'ra_content'  => '',
                    'ra_list_img' => '',
                    'ra_bg_img'   => '',
                    'raid_url'    => G5_BBS_URL.'/board.php?bo_table='.urlencode($bo['bo_table']),
                    'raid_type'   => 'mmbraid',
                    'ra_limit'    => 0,
                    'ra_limit_now' => 0,
                    'ra_state'    => 0,
                    'is_joined'   => false,
                    'my_rm_id'    => 0,
                );
                $raids[] = $ra;
            }
        }
    }
}

// 사용 가능한 리스트 목록 (탭용)
$list_tabs = array();
$list_result = sql_query("SELECT li_id, li_title, raid_type FROM {$g5['k_raid_list']} WHERE li_use = 1 ORDER BY li_id ASC");
while ($tab_row = sql_fetch_array($list_result)) {
    $list_tabs[] = $tab_row;
}

?>

<style>
    @import url(<?php echo G5_URL?>/k_battle/css/raid_list.css);
</style>

<div class="raid-list-wrap">

    <?php if (empty($raids)): ?>
    <p class="empty">이 리스트에 등록된 레이드가 없습니다.</p>
    <?php else: ?>
    <div class="raid-cards">
        <?php foreach ($raids as $ra): 
            $is_realtime = ($ra['raid_type'] === 'realtime');
            $ra_limit = (int)$ra['ra_limit'];
            $ra_limit_now = (int)$ra['ra_limit_now'];
            $ra_state = (int)$ra['ra_state'];
            $is_joined = $ra['is_joined'];
            $my_rm_id = (int)$ra['my_rm_id'];
            $is_full = ($ra_limit > 0 && $ra_limit_now >= $ra_limit);
            
            // 상태 텍스트
            $state_labels = array(0 => '준비중', 1 => '진행중', 2 => '종료');
            $state_label = ses($state_labels, $ra_state, '준비중');
        ?>
        <div class="raid-card" 
            data-raid-id="<?php echo h($ra['ra_id']); ?>"
            <?php if (!empty($ra['ra_list_img'])){echo 'style="background-image:url('.h($ra['ra_list_img']).');';}?>
        >
            <a href="<?php echo h($ra['raid_url']); ?>" class="raid-card-link">
                <div class="raid-top">
                    <span class="top-badge">
                        <?php if ($is_joined){?>
                            참가중
                        <?php }elseif($is_full){?>
                            마감
                        <?php }else{?>
                            미참가
                        <?php }?>
                    </span>
                </div>
                <div class="raid-card-body">
                    <div class="raid-card-title"><?php echo h($ra['ra_title']); ?></div>
                    <div class="raid-card-id">
                      레이드 <?php echo $state_label; ?>
                    </div>
                    <?php if ($is_realtime): ?>
                    <div class="raid-card-meta">
                        참가자  
                        <span class="count <?php echo $is_full ? 'full' : ''; ?>">
                            <?php echo $ra_limit_now; ?><?php if ($ra_limit > 0): ?> / <?php echo $ra_limit; ?><?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ra['ra_content'])): ?>
                    <div class="raid-card-desc"><?php echo h($ra['ra_content']); ?></div>
                    <?php endif; ?>
                </div>
            </a>
            
            <?php if ($is_realtime): ?>
            <div class="raid-card-actions">
                <?php if ($my_ch_id > 0): // 캐릭터가 있을 때만 버튼 표시 ?>
                    <?php if ($is_joined): ?>
                        <button type="button" class="btn btn-leave" 
                                onclick="raidLeave('<?php echo h($ra['ra_id']); ?>', <?php echo $my_rm_id; ?>)"
                                <?php echo ($ra_state >= 1) ? 'disabled title="진행 중에는 취소할 수 없습니다"' : ''; ?>>
                            참가 취소
                        </button>
                        <a href="<?php echo h($ra['raid_url']); ?>" class="btn btn-enter">입장</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-join" 
                                onclick="raidJoin('<?php echo h($ra['ra_id']); ?>')"
                                <?php echo ($is_full || $ra_state >= 1) ? 'disabled' : ''; ?>
                                <?php if ($is_full): ?>title="참가 인원이 가득 찼습니다"<?php endif; ?>
                                <?php if ($ra_state >= 1): ?>title="이미 진행 중인 레이드입니다"<?php endif; ?>>
                            참가하기
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo G5_URL; ?>/member/login.php" class="btn btn-join">로그인 필요</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
function raidJoin(raId) {
    if (!confirm('이 레이드에 참가하시겠습니까?')) {
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo G5_URL; ?>/k_battle/skin_realtime/_ajax_join.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            } catch (e) {
                alert('처리 중 오류가 발생했습니다.');
            }
        }
    };
    xhr.send('ra_id=' + encodeURIComponent(raId));
}

function raidLeave(raId, rmId) {
    if (!confirm('정말로 참가를 취소하시겠습니까?')) {
        return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo G5_URL; ?>/k_battle/skin_realtime/_ajax_leave.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message);
                }
            } catch (e) {
                alert('처리 중 오류가 발생했습니다.');
            }
        }
    };
    xhr.send('ra_id=' + encodeURIComponent(raId) + '&rm_id=' + encodeURIComponent(rmId));
}
</script>
