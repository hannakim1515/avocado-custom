<?php
include_once('../../../../common.php');
include_once(G5_PATH.'/head.sub.php');

if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}


?>



<style>@import url('<?php echo G5_URL; ?>/k_battle/css/admin.css');</style>

<?php if ($is_admin === 'super' && $bo_table !== ''): ?>
<div class="btns">
    <a class="ui-btn" href="./admin.php?bo_table=<?php echo h($bo_table); ?>">기본설정</a>
    <a class="ui-btn" href="./admin.css.php?bo_table=<?php echo h($bo_table); ?>">스타일설정</a>
    <a class="ui-btn point" href="./admin.text.php?bo_table=<?php echo h($bo_table); ?>">텍스트설정</a>
    <a class="ui-btn" href="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">참여자관리</a>
</div>

<form action="./admin_update.php" method="post" onsubmit="return f_submit(this);">
    <input type="hidden" name="bo_table" value="<?php echo h($bo_table); ?>">
    <table class="theme-form">
        <colgroup><col style="width:100px"><col></colgroup>
        <thead style="background-color:#000000cc"><tr></tr><tr></tr></thead>
        <tbody>
        <?php if (ses($board, 'bo_1_subj', '') === 'mmbraid'): ?>
            <tr>
                <td>자비 상단 메시지</td>
                <td>
                    <textarea name="bo_8" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_8', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>코멘트 상단</td>
                <td>
                    <textarea name="bo_8_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_8_subj', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>캐릭터 공격 메시지</td>
                <td>
                    <textarea name="bo_3_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_3_subj', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>캐릭터 회복 메시지</td>
                <td>
                    <textarea name="bo_4_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_4_subj', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>캐릭터 스킬 메시지</td>
                <td>
                    <textarea name="bo_5_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_5_subj', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>캐릭터 아이템 메시지</td>
                <td>
                    <textarea name="bo_6_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_6_subj', '')); ?></textarea>
                </td>
            </tr>
            <tr>
                <td>몬스터 반격 메시지</td>
                <td>
                    <textarea name="bo_7_subj" rows="10" placeholder="랜덤 출력, 줄바꿈으로 구분"><?php echo h(ses($board, 'bo_7_subj', '')); ?></textarea>
                </td>
            </tr>
        <?php else: ?>
            <tr>
                <td>초기설정</td>
                <td>
                    <input type="hidden" name="create" value="1">
                    레이드 초기설정을 완료해 주세요.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:right;">
        <input type="submit" class="ui-btn" name="act_button" value="텍스트변경" onclick="document.pressed=this.value">
    </div>
</form>

<script>
function f_submit(f){
    if(document.pressed==="초기화"){
        if(!confirm("초기화한 데이터는 복구할 수 없습니다.")){ return false; }
    }
    return true;
}
</script>

<?php else: ?>
<script>window.close();</script>
<?php endif; ?>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>
