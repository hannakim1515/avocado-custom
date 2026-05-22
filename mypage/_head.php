<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('_MYPAGE_', true);
$g5['title'] = $member['mb_name']." 마이페이지";
include_once(G5_PATH.'/_head.php');

// 기본 항목 설정 데이터
$ad = $article;

$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$is_mypage_character = strpos($request_uri, '/mypage/character') !== false;
$is_mypage_money = strpos($request_uri, '/mypage/money') !== false;
$is_mypage_memo = strpos($request_uri, '/mypage/memo') !== false;
$is_mypage_home = !$is_mypage_character && !$is_mypage_money && !$is_mypage_memo;
?>

<div class="mypageWrap">
	<div class="mypageInside">
		<nav id="submenu">
			<ul>
				<li><a href="<? echo G5_URL; ?>/mypage/character" class="<?php echo ($is_mypage_character || $is_mypage_home) ? 'point' : ''; ?>"><span>캐릭터</span></a></li>
			<? if($ad['ad_use_money']) { ?>
				<li><a href="<? echo G5_URL; ?>/mypage/money" class="<?php echo $is_mypage_money ? 'point' : ''; ?>"><span><?=$config['cf_money']?>관리</span></a></li>
			<? } ?>
				<li><a href="<? echo G5_URL; ?>/mypage/memo" class="<?php echo $is_mypage_memo ? 'point' : ''; ?>"><span>우편함</span></a></li>
			</ul>
		</nav>
		<div id="subpage">
