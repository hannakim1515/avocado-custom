<?php
if (!defined('_GNUBOARD_')) exit;
/*
 * 원본 Avocado 관리자 코어를 고치지 않고도 통합 메뉴를 열어 둔다.
 * 기존 admin.head.php가 하위 페이지의 옛 sub_menu 번호를 인식하지 못하므로,
 * 통합본의 관리 페이지가 이 조각을 포함해 메뉴 표시만 보정한다.
 */
?>
<style id="ak-unified-menu-style">
#gnb .ak-unified-menu-section { display:block; margin:6px 0 0; padding:13px 18px 5px; border-top:1px solid #edf1f4; color:#8496a7; font-size:11px; font-weight:700; letter-spacing:.08em; line-height:1.2; pointer-events:none; }
#gnb .ak-unified-menu-section:first-child { margin-top:0; border-top:0; }
#gnb .ak-unified-menu-section > span { display:block; }
</style>
<script>
(function () {
    if (!window.jQuery) return;
    var $ = window.jQuery;
    function applyUnifiedMenu() {
        var $top = $('#gnb .gnb_1dli').filter(function () {
            return $(this).children('.gnb_1da').attr('data-text') === 'A/K 통합 전투';
        }).first();
        if (!$top.length) return;

        $top.addClass('on').children('.gnb_2dul').show();
        var sectionNames = {'스탯': true, '지역 관리': true, '전투 콘텐츠': true, '레이드': true};
        $top.find('.gnb_2da').each(function () {
            var $link = $(this);
            var name = $link.attr('data-text');
            if (!sectionNames[name] || $link.attr('href') !== 'javascript:void(0)') return;
            $link.closest('li').addClass('ak-unified-menu-section').empty().append($('<span>').text(name));
        });
    }

    $(applyUnifiedMenu);
})();
</script>
