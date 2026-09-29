<?php
if (!defined('_GNUBOARD_')) {
    include_once('./_common.php');
}

$system_modules = array(
    array('index' => '01', 'label' => 'MY ROOM', 'title' => '개인 기록', 'href' => G5_URL.'/room/'),
    array('index' => '02', 'label' => 'CHARACTER', 'title' => '캐릭터', 'href' => G5_URL.'/member/'),
    array('index' => '03', 'label' => 'RELATION', 'title' => '관계', 'href' => G5_URL.'/couple/'),
    array('index' => '04', 'label' => 'MARKET', 'title' => '상점', 'href' => G5_URL.'/shop/'),
    array('index' => '05', 'label' => 'MISSION', 'title' => '던전', 'href' => G5_URL.'/dungeon/'),
    array('index' => '06', 'label' => 'WORLD MAP', 'title' => '지도', 'href' => G5_URL.'/map/'),
);

$selected_name = 'ACTIVE RECORD';
if ($is_member && isset($character['ch_name']) && $character['ch_name']) {
    $selected_name = get_text($character['ch_name']);
}
?>

<div id="system-intro" class="client-boot" role="dialog" aria-modal="true" aria-label="시스템 접속 준비 중">
    <div class="client-boot__world" aria-hidden="true"></div>
    <div class="client-boot__scan" aria-hidden="true"></div>
    <div class="client-boot__topline"><span>ARCHIVE CLIENT / NODE 01</span><span>INITIALIZING</span></div>
    <div class="client-boot__assembly" aria-hidden="true">
        <span class="client-boot__ring client-boot__ring--outer"></span>
        <span class="client-boot__ring client-boot__ring--inner"></span>
        <span class="client-boot__axis client-boot__axis--x"></span>
        <span class="client-boot__axis client-boot__axis--y"></span>
        <img src="<?php echo G5_IMG_URL; ?>/system/faction-emblem.png" alt="">
    </div>
    <div class="client-boot__copy">
        <small>FACTION LINK / AUTHORIZED</small>
        <strong><?php echo get_text($config['cf_title']); ?></strong>
        <span>CONNECTION ESTABLISHED</span>
    </div>
    <div class="client-boot__progress"><span>BOOT SEQUENCE</span><b><i></i></b><em>100%</em></div>
    <button type="button" class="client-boot__skip">SKIP <span aria-hidden="true">↗</span></button>
</div>

<script>document.body.classList.add('game-client-active', 'client-boot-active');</script>

<main class="client-shell">
    <aside class="module-rail" aria-label="게임 시스템">
        <div class="module-rail__title"><b>SYSTEM</b><span>MODULE SELECT</span></div>
        <nav>
            <?php foreach ($system_modules as $index => $module) { ?>
                <a<?php echo $index === 0 ? ' class="is-active"' : ''; ?> href="<?php echo $module['href']; ?>">
                    <span><?php echo $module['index']; ?></span><b><?php echo $module['label']; ?></b><small><?php echo get_text($module['title']); ?></small>
                </a>
            <?php } ?>
        </nav>
        <div class="module-rail__footer"><span>CHANNEL</span><b>00 / PUBLIC</b></div>
    </aside>

    <section class="world-stage" aria-labelledby="stage-title">
        <div class="world-stage__backdrop" aria-hidden="true"></div>
        <div class="world-stage__shade" aria-hidden="true"></div>
        <div class="world-stage__masthead" aria-hidden="true">ARCHIVE</div>
        <img class="world-stage__emblem" src="<?php echo G5_IMG_URL; ?>/system/faction-emblem.png" alt="">
        <img class="world-stage__character" src="<?php echo G5_IMG_URL; ?>/system/character-operative.png" alt="은발과 검은 코트를 입은 기록 관리 요원">

        <div class="world-stage__coordinates" aria-hidden="true"><span>X.0782</span><span>Y.0419</span></div>
        <div class="world-stage__copy">
            <p>CHARACTER COMMUNITY CLIENT</p>
            <h1 id="stage-title">당신의 캐릭터가<br><strong>세계의 기록</strong>이 됩니다.</h1>
            <div class="world-stage__actions">
                <a class="action-primary" href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=world">세계관 입장 <span aria-hidden="true">↗</span></a>
                <a class="action-secondary" href="<?php echo G5_URL; ?>/mypage/character/">캐릭터 관리 <span aria-hidden="true">→</span></a>
            </div>
        </div>

        <div class="character-record">
            <span class="character-record__index">07</span>
            <div><small>SELECTED CHARACTER</small><b><?php echo $selected_name; ?></b><p>캐릭터를 선택하고 커뮤니티의 기록을 이어가세요.</p></div>
        </div>

        <div class="stage-markers" aria-hidden="true"><i></i><span>WORLD NODE / CONNECTED</span></div>
    </section>

    <aside class="intel-panel">
        <section class="access-panel" aria-label="멤버 접속">
            <div class="panel-heading"><span>ACCESS</span><b>MEMBER LINK</b></div>
            <div class="access-panel__module"><?php include(G5_PATH.'/templete/txt.outlogin.php'); ?></div>
        </section>

        <section class="world-brief" aria-label="시스템 상태">
            <div class="panel-heading"><span>WORLD</span><b>LIVE STATUS</b></div>
            <dl>
                <div><dt>ARCHIVE SERVER</dt><dd>ONLINE</dd></div>
                <div><dt>MISSION GATE</dt><dd>OPEN</dd></div>
                <div><dt>CHANNEL</dt><dd>PUBLIC</dd></div>
            </dl>
        </section>

        <section class="dispatch-panel" aria-label="빠른 안내">
            <div class="panel-heading"><span>NOTICE</span><b>LATEST DISPATCH</b></div>
            <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=notice"><time>NOTICE</time><span>새로운 공지와 운영 기록 확인</span></a>
            <a href="<?php echo G5_URL; ?>/bbs/board.php?bo_table=event"><time>EVENT</time><span>진행 중인 이벤트 확인</span></a>
        </section>
    </aside>

    <nav class="system-deck" aria-label="빠른 시스템 이동">
        <a class="system-deck__primary" href="<?php echo G5_URL; ?>/room/"><span>PERSONAL ARCHIVE</span><b>MY ROOM</b><small>캐릭터와 개인 기록을 관리합니다.</small></a>
        <a href="<?php echo G5_URL; ?>/member/"><span>ROSTER</span><b>CHARACTER</b></a>
        <a href="<?php echo G5_URL; ?>/couple/"><span>LINK</span><b>RELATION</b></a>
        <a href="<?php echo G5_URL; ?>/dungeon/"><span>FIELD</span><b>MISSION</b></a>
        <a href="<?php echo G5_URL; ?>/map/"><span>REGION</span><b>WORLD MAP</b></a>
        <div class="system-deck__meter"><span>CLIENT READY</span><b>100</b></div>
    </nav>
</main>

<script>
$(function () {
    $('body').addClass('game-client-active');

    var $intro = $('#system-intro');
    var revealClient = function () {
        $('body').removeClass('client-boot-active').addClass('client-is-ready');
    };
    var finishIntro = function () {
        if (!$intro.length || $intro.hasClass('is-complete')) {
            revealClient();
            return;
        }
        $intro.addClass('is-complete');
        window.setTimeout(function () {
            $intro.remove();
            revealClient();
        }, 420);
        try { window.sessionStorage.setItem('system-intro-seen', '1'); } catch (error) {}
    };

    if (!$intro.length) {
        revealClient();
        return;
    }

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        $intro.remove();
        revealClient();
        return;
    }

    try {
        if (window.sessionStorage.getItem('system-intro-seen') === '1') {
            $intro.remove();
            revealClient();
            return;
        }
    } catch (error) {}

    $intro.find('.client-boot__skip').on('click', finishIntro);
    window.setTimeout(finishIntro, 3200);
});
</script>
