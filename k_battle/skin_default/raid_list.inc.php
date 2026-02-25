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


        // mmbraid - 게시판 정보 조회
        $bo = sql_fetch("SELECT * FROM {$g5['board_table']} WHERE bo_table = '".sql_escape_string($ra_id)."'");
        if (!empty($bo['bo_table'])) {
            $mo = sql_fetch("SELECT mo_thumb FROM {$g5['k_monster_table']} WHERE mo_id='{$bo['bo_2']}' ");
            $join_able=$bo['bo_2_subj']=='true'?'참여 가능':'참여 마감';
            $ra = array(
                'ra_id'       => $bo['bo_table'],
                'ra_title'    => $bo['bo_subject'],
                'ra_list_img' => $mo['mo_thumb'],
                'raid_url'    => G5_BBS_URL.'/board.php?bo_table='.urlencode($bo['bo_table']),
                'join_able'   => $join_able
            );
            $raids[] = $ra;
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
        ?>
        <div class="raid-card" 
            data-raid-id="<?php echo h($ra['ra_id']); ?>"
            <?php if (!empty($ra['ra_list_img'])){echo 'style="background-image:url('.h($ra['ra_list_img']).');';}?>
        >
            <div class="raid-card-link">
                <div class="raid-card-body" style="height:auto;">
                    <div class="raid-card-title"><?php echo h($ra['ra_title']); ?></div>
                    <div class="raid-card-id">
                        <a href="<?php echo $ra['raid_url']?>">
                             레이드 <?php echo $ra['join_able']; ?>
                        </a>
                    </div>
                </div>
            </div>
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
