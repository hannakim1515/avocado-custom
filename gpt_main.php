<?php
if (!defined('_GNUBOARD_')) {
    include_once('./_common.php');
}

$player_credit = 0;

if ($is_member) {
    $player_credit = isset($member['mb_point']) ? (int) $member['mb_point'] : 0;
}

$quick_menus = array(
    array('class' => 'map', 'label' => '맵 탐색', 'file' => '퀵메뉴_맵.png', 'href' => G5_URL.'/map/'),
    array('class' => 'dungeon', 'label' => '던전', 'file' => '퀵메뉴_던전.png', 'href' => G5_URL.'/dungeon/'),
    array('class' => 'craft', 'label' => '조합대', 'file' => '퀵메뉴_조합대.png', 'href' => G5_URL.'/bbs/board.php?bo_table=world'),
    array('class' => 'shop', 'label' => '상점', 'file' => '퀵메뉴_상점.png', 'href' => G5_URL.'/shop/'),
    array('class' => 'room', 'label' => '마이룸', 'file' => '퀵메뉴_마이룸.png', 'href' => G5_URL.'/room/'),
    array('class' => 'quest', 'label' => '퀘스트', 'file' => '퀵메뉴_퀘스트.png', 'href' => G5_URL.'/quest/'),
    array('class' => 'community', 'label' => '커뮤니티', 'file' => '퀵메뉴_커뮤니티.png', 'href' => G5_URL.'/bbs/board.php?bo_table=community'),
    array('class' => 'archive', 'label' => '자료실', 'file' => '퀵메뉴_자료.png', 'href' => G5_URL.'/bbs/board.php?bo_table=guide'),
);
?>

<script>
(function () {
    var activateLobby = function () { document.body.classList.add('game-client-active', 'client-is-ready'); };
    if (document.body) activateLobby();
    else document.addEventListener('DOMContentLoaded', activateLobby, { once: true });
})();
</script>

<main class="client-shell lobby-shell">
    <section class="lobby-stage" aria-label="<?php echo get_text($config['cf_title']); ?> 게임 로비">
        <div class="lobby-grid" aria-hidden="true"></div>
        <img class="lobby-bg lobby-bg--composite" src="<?php echo G5_IMG_URL; ?>/system/배경합본.png" alt="">
        <img class="lobby-bg lobby-bg--city" src="<?php echo G5_IMG_URL; ?>/system/배경요소3.png" alt="">
        <img class="lobby-decoration lobby-decoration--top" src="<?php echo G5_IMG_URL; ?>/system/꾸밈요소1.png" alt="">
        <img class="lobby-decoration lobby-decoration--bottom" src="<?php echo G5_IMG_URL; ?>/system/꾸밈요소2.png" alt="">

        <header class="lobby-brand">
            <img src="<?php echo G5_IMG_URL; ?>/system/심볼1.png" alt="">
            <div>
                <h1>NEXUS</h1>
                <p>PARALLEL COMMUNITY</p>
            </div>
        </header>

        <section class="lobby-login" aria-label="멤버 로그인">
            <img class="lobby-login__frame" src="<?php echo G5_IMG_URL; ?>/system/loginbox.png" alt="">
            <div class="lobby-login__content">
                <?php include(G5_PATH.'/templete/txt.outlogin.php'); ?>
            </div>
        </section>

        <nav class="lobby-support" aria-label="안내 바로가기">
            <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=notice"><b>NOTICE</b><span>시스템 공지</span><i aria-hidden="true">+</i></a>
            <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=event"><b>EVENT</b><span>진행 중인 이벤트</span><i aria-hidden="true">↗</i></a>
        </nav>

        <section class="lobby-status" aria-label="플레이어 상태">
            <img class="lobby-status__frame" src="<?php echo G5_IMG_URL; ?>/system/top_status_reference.png" alt="">
            <div class="lobby-status__player">
                <small>CREDIT</small>
                <strong><?php echo number_format($player_credit); ?></strong>
            </div>
            <div class="lobby-status__resource lobby-status__resource--credit">
                <small>탐사 가능 횟수</small>
                <strong>5 / 5</strong>
            </div>
            <a class="lobby-status__add" href="<?php echo G5_URL; ?>/shop/" aria-label="탐사 횟수 보충">
                <img src="<?php echo G5_IMG_URL; ?>/system/버튼_추가.png" alt="">
            </a>
        </section>

        <img class="lobby-character" src="<?php echo G5_IMG_URL; ?>/system/캐릭터전신.png" alt="은빛 머리와 검은 의상의 게임 캐릭터">

        <nav class="quick-menu" aria-label="게임 메뉴">
            <?php foreach ($quick_menus as $menu) { ?>
                <a class="quick-menu__item quick-menu__item--<?php echo $menu['class']; ?>" href="<?php echo $menu['href']; ?>" aria-label="<?php echo $menu['label']; ?>">
                    <img src="<?php echo G5_IMG_URL; ?>/system/<?php echo $menu['file']; ?>" alt="">
                </a>
            <?php } ?>
        </nav>

        <div class="lobby-telemetry" aria-hidden="true">
            <span>01</span>
            <p>THERE ARE STILL<br>MANY WORLDS<br>WE HAVE YET TO SEE.</p>
        </div>
        <div class="lobby-coordinate" aria-hidden="true"><span>N 37.5665</span><span>E 126.9780</span></div>
        <div class="lobby-baseline" aria-hidden="true"><span>TO A FURTHER UNKNOWN</span><i></i></div>
    </section>
</main>
