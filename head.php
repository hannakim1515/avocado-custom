<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

if (defined('G5_THEME_PATH')) {
    require_once(G5_THEME_PATH.'/head.php');
    return;
}

include_once(G5_PATH.'/head.sub.php');
include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/poll.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');

add_stylesheet('<link rel="stylesheet" href="'.G5_CSS_URL.'/gpt_main.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/theme/basic/css/system-ui.css?ver='.filemtime(G5_PATH.'/theme/basic/css/system-ui.css').'">', 30);

$nav_items = array(
    array('label' => '세계관', 'technical' => 'WORLD', 'href' => G5_URL.'/bbs/board.php?bo_table=world'),
    array('label' => '가이드', 'technical' => 'GUIDE', 'href' => G5_URL.'/bbs/board.php?bo_table=guide'),
    array('label' => '커뮤니티', 'technical' => 'COMMUNITY', 'href' => G5_URL.'/bbs/board.php?bo_table=community'),
    array('label' => '랭킹', 'technical' => 'RANKING', 'href' => G5_URL.'/bbs/board.php?bo_table=ranking'),
);
?>

<header id="header" class="client-header">
    <a class="client-brand" href="<?php echo G5_URL; ?>/main.php" aria-label="메인으로 이동">
        <img src="<?php echo G5_IMG_URL; ?>/system/faction-emblem.png" alt="">
        <span><b><?php echo get_text($config['cf_title']); ?></b><small>CHARACTER ARCHIVE</small></span>
    </a>

    <nav id="gnb" class="client-global" aria-label="주요 메뉴">
        <?php foreach ($nav_items as $index => $item) { ?>
            <a<?php echo defined('_MAIN_') && $index === 0 ? ' class="is-current"' : ''; ?> href="<?php echo $item['href']; ?>">
                <span><?php echo get_text($item['label']); ?></span>
                <small><?php echo $item['technical']; ?></small>
            </a>
        <?php } ?>
    </nav>

    <div class="client-session" aria-label="접속 상태">
        <span><i aria-hidden="true"></i> LIVE CONNECTION</span>
        <b><?php echo $is_member ? 'MEMBER ACCESS' : 'GUEST ACCESS'; ?></b>
    </div>

    <span class="client-audio"><?php include(G5_PATH.'/templete/txt.bgm.php'); ?></span>
    <button id="gnb_control_box" class="client-menu" type="button" aria-label="메뉴 열기" aria-controls="gnb" aria-expanded="false"><span></span><span></span></button>
</header>

<script>
$(function () {
    $('#gnb_control_box').on('click', function () {
        var $button = $(this);
        var isOpen = !$('body').hasClass('open-gnb');
        $('body').toggleClass('open-gnb', isOpen);
        $button.attr('aria-expanded', isOpen).attr('aria-label', isOpen ? '메뉴 닫기' : '메뉴 열기');
    });
});
</script>

<section id="body"><div class="fix-layout">
