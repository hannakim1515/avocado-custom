<?php
include_once '../../common.php';

/* 입력 */
$cs_id = ses($_REQUEST, 'cs_id', 0, 'int');

/* 스킬 조회 */
$sk = get_k_skill($cs_id, 'cs');
if (!is_array($sk)) {
    $sk = array();
}

/* 검증: 소유와 커스텀 여부 */
$sk_use = ses($sk, 'sk_use', '');
if (empty($sk['sk_id']) || !isset($character['ch_id']) || (int)$sk['ch_id'] !== (int)$character['ch_id'] || strpos($sk_use, 'custom') === false) {
    echo '정상적인 접근이 아닙니다.';
    return;
}


/* 값 준비 */
$sk_icon    = h(ses($sk, 'sk_icon', ''));
$sk_name    = h(ses($sk, 'sk_name', ''));
$sk_content = h(ses($sk, 'sk_content', ''));
$cs_img     = h(ses($sk, 'cs_img', ''));
$show_img   = !empty($kb_cf['skill_img']);
?>
<div class="info-header">
    <div class="asset-mine">
        <div class="sk-custom ui-btn" data-id="<?php echo (int)$cs_id ?>" id="custom_save">저장</div>
    </div>

    <p class="asset-icon"><img src="<?php echo $sk_icon ?>"></p>
    <p class="asset-name">
        <input type="text" id="cs_icon" value="<?php echo $sk_icon ?>">
        <br><input type="text" id="cs_name" value="<?php echo $sk_name ?>">
    </p>
</div>

<div class="asset-content">
    <?php if ($show_img): ?>
        <input type="text" id="cs_img" value="<?php echo $cs_img ?>" placeholder="스킬컷 등록">
    <?php endif; ?>
    <input type="text" id="cs_content" value="<?php echo $sk_content ?>">
</div>

<script>
$("#custom_save").on("click", function () {
    var formData = new FormData();
    var cs_id     = $(this).attr('data-id');
    var cs_content= $("#cs_content").val();
    var cs_name   = $("#cs_name").val();
    var cs_icon   = $("#cs_icon").val();
    var cs_img    = $("#cs_img").length ? $("#cs_img").val() : "";

    formData.append("cs_name", cs_name);
    formData.append("cs_content", cs_content);
    formData.append("cs_icon", cs_icon);
    formData.append("cs_img", cs_img);

    $.ajax({
        url: g5_url + "/k_battle/ajax/skill_update.php?type=custom&cs_id=" + cs_id,
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        type: "POST",
        success: function (data) {
            $.ajax({
                async: true,
                url: g5_url + "/k_battle/skin_default/skill_info.php?id=" + cs_id,
                success: function (html) {
                    $("#skill_info").empty().append(html);
                    var icon = cs_icon && cs_icon.trim() ? cs_icon : (g5_url + "/k_battle/img/default_skill.png");
                    $(".asset-list.skill li[data-id='" + cs_id + "'] img").attr("src", icon);
                },
                error: function () {
                    $("#skill_info").empty();
                }
            });
        }
    });
});
</script>
