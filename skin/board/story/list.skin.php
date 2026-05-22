<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<div class="story-board-container">
    <div class="story-sidebar">
        <div class="sidebar-header">등록된 키워드 목록</div>
        <div class="keyword-list">
            <?php 
            for ($i=0; $i<count($list); $i++) { 
                $active_class = ($i === 0) ? 'active' : '';
            ?>
                <button type="button" class="keyword-item <?php echo $active_class; ?>" onclick="openStory(<?php echo $i; ?>, this)">
                    <?php echo $list[$i]['subject']; ?>
                </button>
            <?php } ?>
            <?php if (count($list) == 0) echo "<p class='empty-msg'>등록된 키워드가 없습니다.</p>"; ?>
        </div>
        <?php if ($write_href) { ?>
            <a href="<?php echo $write_href ?>" class="btn-write">키워드 등록하기</a>
        <?php } ?>
    </div>

    <div class="story-content-wrapper">
        <?php for ($i=0; $i<count($list); $i++) { 
            $display = ($i === 0) ? 'block' : 'none';
        ?>
            <div id="story-detail-<?php echo $i; ?>" class="story-detail-box" style="display:<?php echo $display; ?>;">
                <div class="detail-header">
                    <div class="title-row">
                        <h2 class="keyword-title"><?php echo $list[$i]['subject']; ?></h2>
                        
                        <div class="btn-group">
                            <?php if ($list[$i]['is_note'] || $is_admin) { ?>
                                <a href="<?php echo G5_BBS_URL; ?>/write.php?w=u&bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $list[$i]['wr_id']; ?>" class="btn-edit">수정</a>
                                
                                <a href="<?php echo G5_BBS_URL; ?>/delete.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $list[$i]['wr_id']; ?>&token=<?php echo get_token(); ?>" class="btn-del" onclick="if(confirm('정말 삭제하시겠습니까?')) { location.href=this.href; return false; } else { return false; }">삭제</a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="chapter-info">
                        <?php echo $list[$i]['wr_1'] ? $list[$i]['wr_1'] : 'PROLOGUE'; ?>
                    </div>
                </div>

                <div class="detail-body">
                    <?php echo $list[$i]['wr_content']; ?>
                </div>

                <?php if ($list[$i]['wr_2']) { ?>
                <div class="detail-hint">
                    <span class="hint-label">HINT</span>
                    <span class="hint-text"><?php echo $list[$i]['wr_2']; ?></span>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<script>
function document_delete(href) {
    if(confirm("정말로 이 키워드를 삭제하시겠습니까?\n삭제 후에는 복구가 불가능합니다.")) {
        location.href = href;
    }
}

function openStory(idx, obj) {
    var boxes = document.querySelectorAll('.story-detail-box');
    boxes.forEach(function(box) { box.style.display = 'none'; });
    
    var btns = document.querySelectorAll('.keyword-item');
    btns.forEach(function(btn) { btn.classList.remove('active'); });
    
    document.getElementById('story-detail-' + idx).style.display = 'block';
    obj.classList.add('active');
}

</script>