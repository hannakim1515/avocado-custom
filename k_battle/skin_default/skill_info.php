<?php
include_once '../../common.php';

/* 입력 */
$id = ses($_REQUEST, 'id', 0, 'int');

/* 환경 기본값 */
$show_skill_img = ses($kb_cf, 'skill_img', 0, 'int');
$skill_max = ses($kb_cf, 'skill_max', 0, 'int');

$character = isset($character) && is_array($character) ? $character : array();

/* 스킬 조회 */
$sk = get_k_skill($id, 'cs');
if (!is_array($sk)) $sk = array();

/* 유효성 검사 */
if (empty($sk['sk_id'])) {
    echo '스킬 정보를 찾을 수 없습니다.';
    return;
}

/* 소유자 여부 및 버튼 노출 조건 */
$is_owner = isset($sk['ch_id'], $character['ch_id']) && (int)$sk['ch_id'] === (int)$character['ch_id'];
$has_skillcut = !empty($sk['cs_img']) && $show_skill_img === 1;
$show_mine_box = $is_owner || $has_skillcut;

/* 장착 가능 여부 계산 */
$skillequip = false;
if ($is_owner) {
    $row = sql_fetch("SELECT COUNT(cs_id) AS cnt FROM {$g5['k_ch_skill_table']} WHERE cs_use=1 AND ch_id = '" . (int)$character['ch_id'] . "'");
    $owned_cnt = ses($row, 'cnt', 0, 'int');
    if ($owned_cnt < $skill_max) {
        $skillequip = true;
    }
}

/* 출력 값 준비 */
$sk_icon    = h(ses($sk, 'sk_icon', '', 'raw'));
$sk_name    = h(ses($sk, 'sk_name', '', 'raw'));
$sk_content = h(ses($sk, 'sk_content', '', 'raw'));
$sk_info    = h(ses($sk, 'sk_info', '', 'raw'));
$mp_name    = h(ses($kb_cf, 'mp_name', '', 'raw'));
$sk_mp      = ses($sk, 'sk_mp', 0, 'int');
$sk_cool    = ses($sk, 'sk_cool', 0, 'int');
$sk_turn    = ses($sk, 'sk_turn', 0, 'int');
$cs_use     = !empty($sk['cs_use']);
$allow_custom = $is_owner && isset($sk['sk_use']) && strpos((string)$sk['sk_use'], 'custom') !== false;
$cs_img_js  = json_encode(ses($sk, 'cs_img', '', 'raw')); // onclick 안전
?>
<div class="info-header">
    <?php if ($show_mine_box): ?>
        <div class="asset-mine">
            <?php if ($has_skillcut): ?>
                <div class="ui-btn" onclick="viewImg(<?php echo $cs_img_js ?>)" id="skillcut">스킬컷</div>
            <?php endif; ?>

            <?php if ($allow_custom): ?>
                <div class="sk-custom ui-btn" data-id="<?php echo (int)$id ?>" id="custom">커스텀</div>
            <?php endif; ?>

            <?php if ($is_owner): ?>
                <?php if ($cs_use): ?>
                    <div class="asset-use ui-btn" data-id="<?php echo (int)$id ?>" id="skill_equip">해제</div>
                <?php elseif ($skillequip): ?>
                    <div class="asset-use ui-btn" data-id="<?php echo (int)$id ?>" id="skill_equip">장착</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="asset-icon"><img src="<?php echo $sk_icon ?>"></p>
    <p class="asset-name"><?php echo $sk_name ?></p>
</div>

<div class="asset-content"><?php echo $sk_content ?></div>

<div class="info-footer">
    <p class="asset-content2"><?php echo $sk_info ?></p>
    <p class="sk-mp"><?php echo $mp_name ?> <?php echo $sk_mp ?> | 쿨타임 <?php echo $sk_cool ?> | 지속 <?php echo $sk_turn ?>턴</p>
</div>

<script>
$("#custom").on("click", function(){
    $.ajax({
        async: true,
        url: g5_url + "/k_battle/skin_default/skill_custom.php?cs_id=" + $(this).attr("data-id"),
        success: function(data){ $('#skill_info').empty().append(data); },
        error: function(){ $('#skill_info').empty(); }
    });
});

$("#skill_equip").on("click", function(){
    var formData = new FormData();
    var cs_id = $(this).attr("data-id");
    var text  = $(this).text();

    $.ajax({
        url: g5_url + "/k_battle/ajax/skill_update.php?type=equip&cs_id=" + cs_id,
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        type: "POST",
        success: function(data){
            if(!data || data.result !== 'S') return;

            if(text === '장착'){
                $("#skill_equip").text('해제');
                $("#skill_has_list li[data-id='"+cs_id+"']").addClass("selected");
                $("#skill_list li.none:first").before(data.in);
                $("#skill_list li.none:first").remove();
            }else{
                $("#skill_equip").text('장착');
                $("#skill_has_list li[data-id='"+cs_id+"']").removeClass("selected");
                $("#skill_list li[data-id='"+cs_id+"']").remove();
                $("#skill_list").append("<li class='none'></li>");
            }
        }
    });
});
</script>
