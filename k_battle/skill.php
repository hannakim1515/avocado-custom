<?php

include_once('./_common.php');
include_once('./_head.sub.php');

/* ---- 입력값/기본값 ---- */
$type  = ses($_REQUEST, 'type', '');
$rm_id = ses($_REQUEST, 'rm_id', 0, 'int');

/* ---- 기본 SELECT 컬럼 ---- */
$select = "cs.cs_icon, cs.cs_content, cs.cs_name, sk.sk_mp, sk.sk_cool, sk.sk_turn, sk.sk_info, bs.sk_cool_now";

/* 외부에서 주입된 선택컬럼이 있을 경우 대체 */
if ($type === 'ch' && isset($ally_select) && is_string($ally_select) && $ally_select !== '') {
    $select = $ally_select;
} elseif ($type === 'mo' && isset($enemy_select) && is_string($enemy_select) && $enemy_select !== '') {
    $select = $enemy_select;
}

/* ---- 데이터 로드 ---- */
$sk_list = array();
$tmp = get_k_skill_list('battle', $rm_id);
if (is_array($tmp)) $sk_list = $tmp;


$rm = array();
$rm = get_k_unit($rm_id, $type, $select);


$mp_label = isset($kb_cf['mp_name']) && $kb_cf['mp_name'] !== '' ? $kb_cf['mp_name'] : 'MP';
?>
<style>
    @import url('./css/raid.default.css');
    @import url('./css/admin.css');
    @import url('./css/asset.css');
    .btn-area{display:none!important;}
    .info-area li{display:block;height:initial;}
    .asset-info{padding:10px;}
    .asset-info .info-footer{bottom:10px;right:10px;}
</style>

<ul class="unit-list-detail">
    <?php include("./skin_default/unit_list.detail.php"); ?>
</ul>

<ul class="unit-skill info-area">
<?php if (!empty($sk_list)) : ?>
    <?php foreach ($sk_list as $idx => $sk) :
        $cs_icon    = ses($sk, 'cs_icon', '', 'raw');
        $cs_name    = ses($sk, 'cs_name', '', 'raw');
        $cs_content = ses($sk, 'cs_content', '', 'raw');
        $sk_info    = ses($sk, 'sk_info', '', 'raw');
        $sk_mp      = ses($sk, 'sk_mp', 0, 'int');
        $sk_cool    = ses($sk, 'sk_cool', 0, 'int');
        $sk_turn    = ses($sk, 'sk_turn', 0, 'int');
        $sk_cool_now= ses($sk, 'sk_cool_now', 0, 'int');

        // id 중복 방지
        $li_id = 'sk_info_' . $idx;
    ?>
    <li class="theme-box asset-info" id="<?php echo $li_id; ?>">
        <div class="info-header">
            <p class="asset-icon">
                <?php if ($cs_icon !== '') : ?>
                    <img src="<?php echo h($cs_icon, ENT_QUOTES); ?>" alt="">
                <?php endif; ?>
            </p>
            <p class="asset-name"><?php echo h($cs_name, ENT_QUOTES); ?></p>
        </div>

        <div class="asset-content">
            <?php
            // cs_content는 관리자가 입력한 설명 HTML일 가능성이 있어 그대로 출력
            echo $cs_content;
            ?>
        </div>

        <div class="info-footer">
            <p class="asset-content2">
                <?php
                // sk_info도 서식 HTML 가능
                echo $sk_info;
                ?>
            </p>
            <p class="sk-mp">
                <?php echo h($mp_label, ENT_QUOTES); ?> <?php echo $sk_mp; ?>
                | 쿨타임 <?php echo $sk_cool_now; ?>/<?php echo $sk_cool; ?>
                | 지속 <?php echo $sk_turn; ?>턴
            </p>
        </div>
    </li>
    <?php endforeach; ?>
<?php else: ?>
    <li class="theme-box asset-info">등록된 스킬이 없습니다.</li>
<?php endif; ?>
</ul>

<?php include_once('./_tail.sub.php'); ?>
