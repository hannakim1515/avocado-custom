<?php
include_once('./_common.php');
include_once('./_head.php');

$memo_row = sql_fetch(" select count(*) as cnt from {$g5['memo_table']} where me_recv_mb_id = '{$member['mb_id']}' and me_read_datetime = '0000-00-00 00:00:00' ");
$memo_count = isset($memo_row['cnt']) ? (int) $memo_row['cnt'] : 0;

$character_row = sql_fetch(" select count(*) as cnt from {$g5['character_table']} where mb_id = '{$member['mb_id']}' and ch_state != '삭제' ");
$character_count = isset($character_row['cnt']) ? (int) $character_row['cnt'] : 0;
?>

<h2 class="page-title">
    <strong>마이페이지</strong>
    <span>My Page</span>
</h2>

<section class="mypage-home">
    <a class="mypage-home-card" href="<?php echo G5_URL; ?>/mypage/character/">
        <span>CHARACTER</span>
        <strong>캐릭터</strong>
        <em><?php echo number_format($character_count); ?>명 보유</em>
    </a>

    <?php if ($ad['ad_use_money']) { ?>
        <a class="mypage-home-card" href="<?php echo G5_URL; ?>/mypage/money/">
            <span>MONEY</span>
            <strong><?php echo get_text($config['cf_money']); ?></strong>
            <em><?php echo number_format($member['mb_point']); ?><?php echo get_text($config['cf_money_pice']); ?></em>
        </a>
    <?php } ?>

    <a class="mypage-home-card" href="<?php echo G5_URL; ?>/mypage/memo/">
        <span>MAIL</span>
        <strong>우편함</strong>
        <em><?php echo $memo_count ? '읽지 않은 우편 '.$memo_count.'건' : '새 우편 없음'; ?></em>
    </a>
</section>

<?php
include_once('./_tail.php');
?>
