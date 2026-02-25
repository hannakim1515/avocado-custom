<?php
include_once('../../../../common.php');
include_once(G5_PATH.'/head.sub.php');

if ((isset($board['bo_1_subj']) && $board['bo_1_subj'] === 'mmbraid')) {
    $raid_type='mmbraid';
    include G5_PATH . '/k_battle/_raid_common.php';
}


/** 스탯 목록 */
$sc_list = [];
if (count($k_stat) > 1) {
    for ($si = 0; $si < count($k_stat); $si++) {
        $sid = (int)$k_stat[$si];
        if ($sid > 0) {
            $sc_list[] = sql_fetch("SELECT * FROM {$g5['k_stat_table']} WHERE sc_id='{$sid}'");
        }
    }
}
?>
<style>@import url('<?php echo G5_URL; ?>/k_battle/css/admin.css');</style>

<?php if ($is_admin === 'super' && $bo_table !== ''): ?>
<div class="btns">
    <a class="ui-btn point" href="./admin.php?bo_table=<?php echo h($bo_table); ?>">기본설정</a>
    <a class="ui-btn" href="./admin.css.php?bo_table=<?php echo h($bo_table); ?>">스타일설정</a>
    <a class="ui-btn" href="./admin.text.php?bo_table=<?php echo h($bo_table); ?>">텍스트설정</a>
    <a class="ui-btn" href="./admin.member.php?bo_table=<?php echo h($bo_table); ?>">참여자관리</a>
</div>

<form action="./admin_update.php" method="post" onsubmit="return f_submit(this);">
    <input type="hidden" name="bo_table" value="<?php echo h($bo_table); ?>">
    <table class="theme-form">
        <colgroup><col style="width:100px"><col></colgroup>
        <thead style="background-color:#000000cc"><tr></tr><tr></tr></thead>
        <tbody>
        <?php if (ses($board, 'bo_1_subj', '') === 'mmbraid'): ?>
            <?php
            $mo_row = sql_fetch("SELECT rm_id FROM {$battle_table}_unit WHERE unit_type='mo' and ra_id='{$ra_id}' ");
            $mo     = ses($mo_row, 'rm_id', 0, 'int') ? get_k_unit($mo_row['rm_id'], 'mo') : array();
            $mo_id  = ses($mo, 'mo_id', 0, 'int');
            $mo_list = get_k_monster_list('*', 'mmbraid');
            ?>
            <tr>
                <td>레이드 사용</td>
                <td>
                    <select name="bo_1">
                        <option value="false" <?php echo (ses($board, 'bo_1', '')==='false' ? 'selected' : ''); ?>>미사용</option>
                        <option value="true"  <?php echo (ses($board, 'bo_1', '')==='true'  ? 'selected' : ''); ?>>사용</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>레이드 참여</td>
                <td>
                    <select name="bo_2_subj">
                        <option value="true"  <?php echo (ses($board, 'bo_2_subj', '')==='true'  ? 'selected' : ''); ?>>가능</option>
                        <option value="false" <?php echo (ses($board, 'bo_2_subj', '')==='false' ? 'selected' : ''); ?>>마감</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>시스템 로그 리플</td>
                <td>
                    <select name="bo_11">
                        <option value="true"  <?php echo (ses($board, 'bo_11', '')==='true'  ? 'selected' : ''); ?>>허용</option>
                        <option value="false" <?php echo (ses($board, 'bo_11', '')==='false' ? 'selected' : ''); ?>>비허용</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>레이드 방식</td>
                <td>
                    <?php $mode = ses($board, 'bo_10_subj', 'all'); ?>
                    <select name="bo_10_subj" onchange="infochange(this.value);">
                        <option value="all"   <?php echo ($mode==='all'   ? 'selected' : ''); ?>>모든 행동 제한</option>
                        <option value="skill" <?php echo ($mode==='skill' ? 'selected' : ''); ?>>스킬만 제한</option>
                        <option value="no"    <?php echo ($mode==='no'    ? 'selected' : ''); ?>>제한없음</option>
                    </select>
                    <p class="info" id="all"   <?php echo ($mode==='all'   ? 'style="display:block;"' : ''); ?>>턴 변경 전까지 임의 행동(아이템/공격/회복/스킬) 1회만 가능</p>
                    <p class="info" id="skill" <?php echo ($mode==='skill' ? 'style="display:block;"' : ''); ?>>턴 변경 전까지 스킬 사용 1회만 가능, 나머지 행동 제한 없음</p>
                    <p class="info" id="no"    <?php echo ($mode==='no'    ? 'style="display:block;"' : ''); ?>>턴 변경 전까지 모든 행동 횟수제한 없이 가능</p>
                </td>
            </tr>
            <tr>
                <td>몬스터</td>
                <td>
                    <?php if ($mo_id > 0): ?>
                        <table class="theme-form member">
                            <colgroup>
                                <col>
                                <?php for ($si = 0, $sn = count($sc_list); $si < $sn; $si++): ?><col><?php endfor; ?>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th><?php echo h(ses($kb_cf, 'hp_name', 'HP')); ?></th>
                                    <?php
                                    for ($si = 0, $sn = count($sc_list); $si < $sn; $si++) {
                                        echo '<th>'.h(ses($sc_list[$si], 'sc_name', '')).'</th>';
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo h(ses($mo, 'hp_now', 0, 'int')).'/'.h(ses($mo, 'hp_max', 0, 'int')); ?></td>
                                    <?php
                                    for ($si = 0, $sn = count($sc_list); $si < $sn; $si++) {
                                        $st_tag    = 'st_'.($si+1);
                                        $bonus_tag = $st_tag.'_buff';
                                        $base  = ses($mo, $st_tag, '');
                                        $bonus = ses($mo, $bonus_tag, '');
                                        echo '<td>'.h($base).($bonus!=='' ? '('.h($bonus).')' : '').'</td>';
                                    }
                                    ?>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif;?>

                    <p>
                        <input type="hidden" name="origin_bo_2" value="<?php echo h($mo_id); ?>">
                        <select name="bo_2">
                            <option value="">몬스터 지정</option>
                            <?php
                            if (is_array($mo_list)) {
                                for ($mi = 0, $mn = count($mo_list); $mi < $mn; $mi++) {
                                    $mid = ses($mo_list[$mi], 'mo_id', 0, 'int');
                                    $mname = ses($mo_list[$mi], 'mo_name', '');
                                    $sel = ($mid == $mo_id) ? ' selected' : '';
                                    echo '<option value="'.h($mid).'"'.$sel.'>'.h($mname).'</option>';
                                }
                            }
                            ?>
                        </select>
                    </p>
                    <p>
                        <span>몬스터 반격</span>
                        <?php $bo7 = ses($board, 'bo_7', 'true'); ?>
                        <select name="bo_7" style="display:inline-block;">
                            <option value="true"  <?php echo ($bo7==='true'  ? 'selected' : ''); ?>>사용</option>
                            <option value="false" <?php echo ($bo7==='false' ? 'selected' : ''); ?>>미사용</option>
                        </select>
                    </p>
                    <p>
                        <span>몬스터 이미지</span>
                        <input type="text" name="bo_10" value="<?php echo h(ses($board, 'bo_10', '')); ?>">
                    </p>
                </td>
            </tr>
            <tr>
                <td>공격</td>
                <td>
                    <?php $bo3 = ses($board, 'bo_3', 'true'); ?>
                    <select name="bo_3">
                        <option value="true"  <?php echo ($bo3==='true'  ? 'selected' : ''); ?>>사용</option>
                        <option value="false" <?php echo ($bo3==='false' ? 'selected' : ''); ?>>미사용</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>회복</td>
                <td>
                    <?php $bo4 = ses($board, 'bo_4', 'true'); ?>
                    <select name="bo_4">
                        <option value="true"  <?php echo ($bo4==='true'  ? 'selected' : ''); ?>>사용</option>
                        <option value="false" <?php echo ($bo4==='false' ? 'selected' : ''); ?>>미사용</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>스킬</td>
                <td>
                    <?php $bo5 = ses($board, 'bo_5', 'true'); ?>
                    <select name="bo_5">
                        <option value="true"  <?php echo ($bo5==='true'  ? 'selected' : ''); ?>>사용</option>
                        <option value="false" <?php echo ($bo5==='false' ? 'selected' : ''); ?>>미사용</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>레이드아이템</td>
                <td>
                    <?php $bo6 = ses($board, 'bo_6', 'true'); ?>
                    <select name="bo_6">
                        <option value="true"  <?php echo ($bo6==='true'  ? 'selected' : ''); ?>>사용</option>
                        <option value="false" <?php echo ($bo6==='false' ? 'selected' : ''); ?>>미사용</option>
                    </select>
                </td>
            </tr>
        <?php else: ?>
            <tr>
                <td>초기설정</td>
                <td>
                    <input type="hidden" name="create" value="1">
                    이 게시판을 레이드에 사용하시려면 '설정변경' 버튼을 눌러 주세요.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div style="text-align:right;">
        <input type="submit" class="ui-btn" name="act_button" value="설정변경" onclick="document.pressed=this.value">
        <?php if (ses($board, 'bo_1_subj', '') === 'mmbraid'): ?>
            <input type="submit" class="ui-btn admin" name="act_button" value="전투초기화" onclick="document.pressed=this.value">
            <input type="submit" class="ui-btn admin" name="act_button" value="전체초기화" onclick="document.pressed=this.value">
        <?php endif; ?>
    </div>
</form>

<script>
function f_submit(f){
    if(document.pressed==="전투초기화"){
        if(!confirm("버프, 로그, 쿨타임, 캐릭터/몬스터 상태가 초기화됩니다. 초기화한 데이터는 복구할 수 없습니다.")){ return false; }
    }else if(document.pressed==="전체초기화"){
        if(!confirm("모든 데이터가 초기화됩니다. 초기화한 데이터는 복구할 수 없습니다.")){ return false; }
    }
    return true;
}
function infochange(val){
    if (window.jQuery){ 
        $('.info#'+val).show(); $('.info').not('#'+val).hide();
    } else {
        var infos=document.querySelectorAll('.info');
        for(var i=0;i<infos.length;i++){ infos[i].style.display='none'; }
        var t=document.getElementById(val); if(t){ t.style.display='block'; }
    }
}
</script>

<?php else: ?>
<script>window.close();</script>
<?php endif; ?>

<?php include_once(G5_PATH.'/tail.sub.php'); ?>
