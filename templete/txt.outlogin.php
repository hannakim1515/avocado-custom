<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$memo_not_read = 0;
if ($is_member) {
    $memo_row = sql_fetch(" select count(*) as cnt from {$g5['memo_table']} where me_recv_mb_id = '{$member['mb_id']}' and me_read_datetime = '0000-00-00 00:00:00' ");
    $memo_not_read = isset($memo_row['cnt']) ? (int) $memo_row['cnt'] : 0;
} else {
    $logo = get_logo('pc');
    $m_logo = get_logo('mo');

    $logo_data = "";
    if ($logo) {
        $logo_data .= "<img src='".$logo."' ";
    }
    if ($m_logo) {
        $logo_data .= "class='only-pc' /><img src='".$m_logo."' class='not-pc'";
    }
    if ($logo_data) {
        $logo_data .= " />";
    }
}
?>

<h2>MEMBER LOGIN</h2>

<?php if ($is_member) { ?>
    <div class="re-member-box">
        <div class="re-member-profile">
            <?php if (isset($character['ch_id']) && $character['ch_id']) { ?>
                <a class="re-character-thumb" href="<?php echo G5_URL; ?>/mypage/">
                    <?php if ($character['ch_thumb']) { ?>
                        <img src="<?php echo $character['ch_thumb']; ?>" alt="<?php echo get_text($character['ch_name']); ?>">
                    <?php } else { ?>
                        <span>NO IMAGE</span>
                    <?php } ?>
                </a>
                <?php if ($character['ch_name']) { ?>
                    <p class="re-character-name"><?php echo get_text($character['ch_name']); ?></p>
                <?php } ?>
            <?php } else { ?>
                <a class="re-character-empty" href="<?php echo G5_URL; ?>/mypage/character/character_form.php">
                    캐릭터 생성
                </a>
            <?php } ?>
        </div>

        <p class="re-member-name">
            <?php echo get_text($member['mb_nick']); ?> 님
            <?php if ($is_admin) { ?>
                <a class="re-admin-link" href="<?php echo G5_ADMIN_URL; ?>/" target="_blank" rel="noopener noreferrer">관리자 페이지</a>
            <?php } ?>
        </p>
        <ul class="re-member-links">
            <li>
                <a href="<?php echo G5_URL; ?>/mypage/memo/">
                    쪽지
                    <?php if ($memo_not_read) { ?><i><?php echo $memo_not_read; ?></i><?php } ?>
                </a>
            </li>
            <li><a href="<?php echo G5_URL; ?>/mypage/">계정관리</a></li>
            <li><a href="<?php echo G5_BBS_URL; ?>/logout.php">로그아웃</a></li>
        </ul>
    </div>
<?php } else { ?>
    <?php if ($logo_data) { ?>
        <div class="re-login-logo">
            <?php echo $logo_data; ?>
        </div>
    <?php } ?>

    <form method="post" action="<?php echo G5_HTTPS_BBS_URL; ?>/login_check.php" autocomplete="off">
        <input type="hidden" name="url" value="<?php echo G5_URL; ?>">
        <input class="re-input" type="text" name="mb_id" placeholder="아이디" required>
        <input class="re-input" type="password" name="mb_password" placeholder="비밀번호" required>

        <label class="re-save-id">
            <input type="checkbox" name="auto_login" value="1">
            <span>자동 로그인</span>
        </label>

        <button class="re-login-btn" type="submit">로그인</button>
    </form>

    <div class="re-login-links">
        <a href="<?php echo G5_BBS_URL; ?>/register.php">회원가입</a>
        <span>|</span>
        <a href="<?php echo G5_BBS_URL; ?>/password_lost.php">정보 찾기</a>
    </div>
<?php } ?>
