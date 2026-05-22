<?php
if (!defined('_GNUBOARD_')) {
    include_once('./_common.php');
}

if (defined('G5_THEME_PATH')) {
    include_once(G5_THEME_PATH . '/head.php');
} else {
    include_once(G5_PATH . '/head.php');
}
$feature_cards = [
    ['title' => '마이룸', 'desc' => '나만의 공간을 꾸며보세요', 'icon' => '✦', 'class' => 'cyan', 'href' => G5_URL . '/room/index.php'],
    ['title' => '조합 제조', 'desc' => '아이템을 제작하고 조합합니다', 'icon' => '⚗', 'class' => 'green', 'href' => G5_URL . '/craft.php'],
    ['title' => '장비강화', 'desc' => '장비의 잠재력을 끌어냅니다', 'icon' => '⚔', 'class' => 'blue', 'href' => G5_URL . '/enhance.php'],
    ['title' => '스킬강화', 'desc' => '스킬의 한계를 돌파합니다', 'icon' => '✧', 'class' => 'violet', 'href' => G5_URL . '/skill.php'],
    ['title' => '던전입장', 'desc' => '다양한 던전에 도전하세요', 'icon' => '▰', 'class' => 'gold', 'href' => G5_URL . '/dungeon.php'],
    ['title' => '레이드 입장', 'desc' => '강력한 레이드에 도전하세요', 'icon' => '♜', 'class' => 'red', 'href' => G5_URL . '/raid.php'],
];


add_stylesheet('<link rel="stylesheet" href="' . G5_URL . '/css/gpt_main.css">', 0);
?>

<div class="re-page">
    <div class="re-bg" aria-hidden="true"></div>

    <main class="re-main">
        <section class="re-hero">
            <aside class="re-panel re-login">
                <div class="re-panel-inner">
                    <?php include(G5_PATH."/templete/txt.outlogin.php"); ?>
                </div>
            </aside>

            <section class="re-title-area">
                <h1>페어제 자캐커뮤</h1>
                <p class="re-sub-en">AVOCADO EDITION</p>
                <div class="re-title-line"></div>
                <p class="re-sub-ko">당신의 이야기가, 이 세계의 역사가 된다.</p>
                <a class="re-enter-btn" href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=world">세계 입장 ✦</a>
            </section>
        </section>

        <section class="re-features">
            <?php foreach ($feature_cards as $card) { ?>
                <a class="re-feature re-feature-<?php echo $card['class']; ?>" href="<?php echo $card['href']; ?>">
                    <span class="re-feature-light"></span>
                    <span class="re-feature-icon"><?php echo get_text($card['icon']); ?></span>
                    <strong><?php echo get_text($card['title']); ?></strong>
                    <em><?php echo get_text($card['desc']); ?></em>
                </a>
            <?php } ?>
        </section>

        <section class="re-bottom">
            <section class="re-panel re-notice">
                <div class="re-panel-inner">
                    <div class="re-section-head">
                        <h2>공지</h2>
                        <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=notice">더보기 +</a>
                    </div>

                    <ul>
                        <?php if (count($notices) > 0) { ?>
                            <?php foreach ($notices as $notice) { ?>
                            <li>
                                <span><?php echo get_text($notice['date']); ?></span>
                                <a href="<?php echo $notice['href']; ?>"><?php echo get_text($notice['title']); ?></a>
                                <?php if ($notice['new']) { ?><b>N</b><?php } ?>
                            </li>
                            <?php } ?>
                        <?php } else { ?>
                            <li>
                                <span>-</span>
                                <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=notice">등록된 공지가 없습니다.</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </section>

            <section class="re-panel re-event">
                <div class="re-panel-inner">
                    <div class="re-section-head">
                        <h2>진행중 이벤트</h2>
                        <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=event">더보기 +</a>
                    </div>

                    <div class="re-event-banner">
                        <p>혈빛 성운을 넘어, 새로운 문이 열린다</p>
                        <strong>출석 이벤트</strong>
                        <span>매일 접속하고 특별한 보상을 받아가세요.</span>
                        <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=event">이벤트 참여하기 →</a>
                    </div>
                </div>
            </section>
        </section>
    </main>

</div>

<?php
if (defined('G5_THEME_PATH')) {
    include_once(G5_THEME_PATH . '/tail.php');
} else {
    include_once(G5_PATH . '/tail.php');
}
?>
