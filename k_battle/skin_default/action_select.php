<ul id="action-select">
<?php
// 기본 방어
$rm    = isset($rm)    && is_array($rm)    ? $rm    : array();
$rm_side = ses($rm, 'ch_side', null);

// 공격
if (empty($kb_cf['limit_atk']) || ($rm_side !== null && (string)$kb_cf['limit_atk'] === (string)$rm_side)) : ?>
    <li class="raid-action <?php echo isset($a_false) ? h($a_false) : '' ?>" onclick="actInfo('atk')">공격</li>
<?php endif;

// 치유
if (empty($kb_cf['limit_heal']) || ($rm_side !== null && (string)$kb_cf['limit_heal'] === (string)$rm_side)) : ?>
    <li class="raid-action <?php echo isset($h_false) ? h($h_false) : '' ?>" onclick="actInfo('heal')">치유</li>
<?php endif;

// 아이템
if (empty($kb_cf['limit_item'])) : ?>
    <li class="raid-action <?php echo isset($i_false) ? h($i_false) : '' ?>" onclick="actInfo('item')">아이템</li>
<?php endif;

// 스킬 아이콘들
if (empty($kb_cf['limit_skill'])) :
    $rm_id   = ses($rm, 'rm_id', 0, 'int');
    if($rm['unit_type']=='ch'){
        $sk_list = get_k_skill_list('battle', $rm_id, "", "bs.bs_id, cs.cs_icon, sk.sk_icon");
    }else{
        $sk_list = get_k_mo_skill_list($rm['unit_id'], $raid_type, "sk.sk_id as bs_id, ms.cs_icon, sk.sk_icon");
    }
    if (is_array($sk_list)) :
        foreach ($sk_list as $sk) :
            $bs_id = ses($sk, 'bs_id', 0, 'int');
            $icon  = h(ses($sk, 'sk_icon', ''));
?>
    <li class="raid-action <?php echo isset($s_false) ? h($s_false) : '' ?> <?php echo isset($act_class) ? h($act_class) : '' ?>"
        onclick="actInfo('skill', <?php echo $bs_id ?>)"><img src="<?php echo $icon ?>"></li>
<?php
        endforeach;
    endif;
endif;

// 취소: $board가 배열이면 표시
if (!empty($board)) : ?>
    <li class="raid-action" onclick="actInfo('')">취소</li>
<?php endif; ?>
</ul>

<div id="action-info">
    <p class="info"></p>
    <p class="warning">
        <?php 
        if(isset($not_start)&&$not_start) {echo h($not_start);}
        elseif(isset($not_myturn)&&$not_myturn) {echo h($not_myturn);}
        else{echo '행동을 선택해 주세요.';} ?>
    </p>
    <p class="target"></p>
</div>
