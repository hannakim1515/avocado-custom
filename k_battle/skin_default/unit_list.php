<?php
// 방어
$list_type = isset($list_type) ? (string)$list_type : '';
$unit_list = isset($unit_list) && is_array($unit_list) ? $unit_list : array();
$ra = isset($ra) && is_array($ra) ? $ra : array();
$now_turn = ses($ra, 'now_turn', 0, 'int');

?>
<ul class="unit-list <?php echo h($list_type) ?>">
<?php foreach ($unit_list as $u):
    $rm_id   = ses($u, 'rm_id', 0, 'int');
    $thumb   = h(ses($u, 'unit_thumb', ''));
    $name    = h(ses($u, 'unit_name', ''));

    $hp_now  = ses($u, 'hp_now', 0, 'int');
    $hp_max  = ses($u, 'hp_max', 0, 'int');
    $hp_pct  = clamp_pct($hp_now, $hp_max);

    $mp_now  = ses($u, 'mp_now', 0, 'int');
    $mp_max  = ses($u, 'mp_max', 0, 'int');
    $mp_pct  = clamp_pct($mp_now, $mp_max);
    $is_aggr = ses($u, 'is_aggr', 0, 'int');
    $is_stun = ses($u, 'is_stun', 0, 'int');
    $tt_done = ses($u, 'tt_done', 0, 'int');

    // 버프/디버프 목록
    $buff_list = ses($u, 'buff_list', array(), 'array');
    $buffs = ses($buff_list, 'buff', array(), 'array');
    $debuffs = ses($buff_list, 'debuff', array(), 'array');
    $buff_cnt = count($buffs);
    $debuff_cnt = count($debuffs);
    
    $buff_html = '';
    foreach ($buffs as $bf) {
        $buff_html .= '<span>' . h($bf['name']) . ' +' . $bf['value'] . ' | 남은 턴 ' . $bf['turn'] . '</span>';
    }
    $debuff_html = '';
    foreach ($debuffs as $df) {
        $debuff_html .= '<span>' . h($df['name']) . ' ' . $df['value'] . ' | 남은 턴 ' . $df['turn'] . '</span>';
    }
?>
    <li data-id="<?php echo $rm_id ?>" <?php if($now_turn == $rm_id) echo 'class="nowturn"'; ?>>
        <p class="unit-thumb"><img src="<?php echo $thumb ?>" alt=""></p>
        <div class="ar-default">
            <p class="unit-name"><?php echo $name ?></p>
        </div>
        <div class="ar-info" data-id="<?php echo $rm_id ?>">
            <p class="unit-hp">
                <span style="width:<?php echo $hp_pct ?>%"></span>
                <i><?php echo $hp_now ?>/<?php echo max(1, $hp_max) ?></i>
            </p>
            <?php if (!empty($kb_cf['mp'])): ?>
            <p class="unit-mp">
                <span style="width:<?php echo $mp_pct ?>%"></span>
                <i><?php echo $mp_now ?>/<?php echo max(1, $mp_max) ?></i>
            </p>
            <?php endif; ?>
            <div class="unit-status rm-buff">
                <div class="aggr <?php if($is_aggr>0) echo 'done'; ?>" data-cnt="<?php echo $is_aggr ?>"><?php echo $is_aggr ?><span class="bf-inner"><span>도발 | 남은 턴 <?php echo $is_aggr ?></span></span></div>
                <div class="stun <?php if($is_stun>0) echo 'done'; ?>" data-cnt="<?php echo $is_stun ?>"><?php echo $is_stun ?><span class="bf-inner"><span>기절 | 남은 턴 <?php echo $is_stun ?></span></span></div>
                <div class="buff <?php if($buff_cnt>0) echo 'done'; ?>" data-cnt="<?php echo $buff_cnt ?>"><?php echo $buff_cnt ?><span class="bf-inner"><?php echo $buff_html ?></span></div>
                <div class="debuff <?php if($debuff_cnt>0) echo 'done'; ?>" data-cnt="<?php echo $debuff_cnt ?>"><?php echo $debuff_cnt ?><span class="bf-inner"><?php echo $debuff_html ?></span></div>
                <div class="turn <?php if($tt_done>0) echo 'done'; ?>"></span>
            </div>
        </div>
    </li>
<?php endforeach; ?>
</ul>
