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
    ['title' => '마이룸', 'desc' => '나만의 공간을 꾸며보세요', 'icon' => '✦', 'class' => 'cyan', 'href' => G5_URL . '/myroom.php'],
    ['title' => '조합 제조', 'desc' => '아이템을 제작하고 조합합니다', 'icon' => '⚗', 'class' => 'green', 'href' => G5_URL . '/craft.php'],
    ['title' => '장비강화', 'desc' => '장비의 잠재력을 끌어냅니다', 'icon' => '⚔', 'class' => 'blue', 'href' => G5_URL . '/enhance.php'],
    ['title' => '스킬강화', 'desc' => '스킬의 한계를 돌파합니다', 'icon' => '✧', 'class' => 'violet', 'href' => G5_URL . '/skill.php'],
    ['title' => '던전입장', 'desc' => '다양한 던전에 도전하세요', 'icon' => '▰', 'class' => 'gold', 'href' => G5_URL . '/dungeon.php'],
    ['title' => '레이드 입장', 'desc' => '강력한 레이드에 도전하세요', 'icon' => '♜', 'class' => 'red', 'href' => G5_URL . '/raid.php'],
];

$notices = [
    ['date' => '06.14', 'title' => '정기 점검 안내 (6/15)', 'href' => '#', 'new' => true],
    ['date' => '06.12', 'title' => '커뮤니티 가이드라인 개정 안내', 'href' => '#', 'new' => true],
];

add_stylesheet('<link rel="stylesheet" href="' . G5_URL . '/css/gpt_main.css">', 0);
?>

<div class="pearje-page">
    <div class="pearje-bg" aria-hidden="true"></div>

    <main class="pearje-main">
        <section class="pearje-hero">
            <aside class="pearje-panel pearje-login">
                <div class="pearje-panel-inner">
                    <h2>MEMBER LOGIN</h2>

                    <?php if ($is_member) { ?>
                        <div class="pearje-member-box">
                            <p class="pearje-member-name">
                                <?php echo get_text($member['mb_nick']); ?> 님
                                <?php if ($is_admin) { ?>
                                    <a class="pearje-admin-link" href="<?php echo G5_ADMIN_URL; ?>/" target="_blank" rel="noopener noreferrer">관리자 페이지</a>
                                <?php } ?>
                            </p>
                            <a class="pearje-login-btn" href="<?php echo G5_BBS_URL; ?>/logout.php">로그아웃</a>
                            <div class="pearje-login-links">
                                <a href="<?php echo G5_BBS_URL; ?>/member_confirm.php?url=register_form.php">정보수정</a>
                                <span>|</span>
                                <a href="<?php echo G5_BBS_URL; ?>/memo.php" target="_blank">쪽지</a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <form method="post" action="<?php echo G5_HTTPS_BBS_URL; ?>/login_check.php" autocomplete="off">
                            <input type="hidden" name="url" value="<?php echo G5_URL; ?>">
                            <input class="pearje-input" type="text" name="mb_id" placeholder="아이디" required>
                            <input class="pearje-input" type="password" name="mb_password" placeholder="비밀번호" required>

                            <label class="pearje-save-id">
                                <input type="checkbox" name="auto_login" value="1">
                                <span>자동 로그인</span>
                            </label>

                            <button class="pearje-login-btn" type="submit">로그인</button>
                        </form>

                        <div class="pearje-login-links">
                            <a href="<?php echo G5_BBS_URL; ?>/register.php">회원가입</a>
                            <span>|</span>
                            <a href="<?php echo G5_BBS_URL; ?>/password_lost.php">정보 찾기</a>
                        </div>
                    <?php } ?>
                </div>
            </aside>

            <section class="pearje-title-area">
                <div class="pearje-title-badge">✥</div>
                <h1>페어제 자캐커뮤</h1>
                <p class="pearje-sub-en">AVOCADO EDITION</p>
                <div class="pearje-title-line"></div>
                <p class="pearje-sub-ko">당신의 이야기가, 이 세계의 역사가 된다.</p>
                <a class="pearje-enter-btn" href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=world">세계 입장 ✦</a>
            </section>

            <section class="pearje-panel pearje-slide">
                <div class="pearje-slide-bg"></div>
                <div class="pearje-slide-content">
                    <span>NEW STORY</span>
                    <h2>녹빛 회랑의 초대</h2>
                    <p>페어제 교단의 문,<br>새로운 여정이 시작됩니다.</p>
                    <div class="pearje-slide-dots"><b>‹</b><i class="active"></i><i></i><i></i><b>›</b></div>
                </div>
            </section>
        </section>

        <section class="pearje-features">
            <?php foreach ($feature_cards as $card) { ?>
                <a class="pearje-feature pearje-feature-<?php echo $card['class']; ?>" href="<?php echo $card['href']; ?>">
                    <span class="pearje-feature-light"></span>
                    <span class="pearje-feature-icon"><?php echo get_text($card['icon']); ?></span>
                    <strong><?php echo get_text($card['title']); ?></strong>
                    <em><?php echo get_text($card['desc']); ?></em>
                </a>
            <?php } ?>
        </section>

        <section class="pearje-bottom">
            <section class="pearje-panel pearje-notice">
                <div class="pearje-panel-inner">
                    <div class="pearje-section-head">
                        <h2>공지</h2>
                        <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=notice">더보기 +</a>
                    </div>

                    <ul>
                        <?php foreach ($notices as $notice) { ?>
                            <li>
                                <span><?php echo get_text($notice['date']); ?></span>
                                <a href="<?php echo $notice['href']; ?>"><?php echo get_text($notice['title']); ?></a>
                                <?php if ($notice['new']) { ?><b>N</b><?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </section>

            <section class="pearje-panel pearje-event">
                <div class="pearje-panel-inner">
                    <div class="pearje-section-head">
                        <h2>진행중 이벤트</h2>
                        <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=event">더보기 +</a>
                    </div>

                    <div class="pearje-event-banner">
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
