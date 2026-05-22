<?php
if (!defined('_GNUBOARD_')) exit;

if(defined('G5_THEME_PATH')) {
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

/*********** Logo Data ************/
$logo = get_logo('pc');
$m_logo = get_logo('mo');

$logo_data = "";
if($logo)		$logo_data .= "<img src='".$logo."' ";
if($m_logo)		$logo_data .= "class='only-pc' /><img src='".$m_logo."' class='not-pc'";
if($logo_data)	$logo_data.= " />";
/*********************************/

$nav_items = [
    ['label' => '세계관', 'href' => G5_URL . '/bbs/board.php?bo_table=world'],
    ['label' => '가이드', 'href' => G5_URL . '/bbs/board.php?bo_table=guide'],
    ['label' => '커뮤니티', 'href' => G5_URL . '/bbs/board.php?bo_table=community'],
    ['label' => '랭킹', 'href' => G5_URL . '/bbs/board.php?bo_table=ranking'],
];
?>

<header id="header">
    <div class="fix-layout">
        <div class="pearje-header">
            <div class="pearje-brand">
                <span class="pearje-brand-mark">✶</span>
                <span>페어제 자캐커뮤</span>
            </div>

            <nav class="pearje-nav" aria-label="메인 메뉴">
                <?php foreach ($nav_items as $item) { ?>
                    <a href="<?php echo $item['href']; ?>"><?php echo get_text($item['label']); ?></a>
                <?php } ?>
            </nav>

            <div class="pearje-tools">
                <?php include(G5_PATH."/templete/txt.bgm.php"); ?>
            </div>
        </div>
    </div>
</header>

<section id="body">
	<div class="fix-layout">
