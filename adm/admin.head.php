<?php
if (!defined('_GNUBOARD_')) exit;
$begin_time = get_microtime();
include_once(G5_PATH.'/head.sub.php');

function print_menu1($key, $no){
	global $menu;
	$str = print_menu2($key, $no);
	return $str;
}

/*
 * 통합 메뉴의 하위 페이지는 기존 플러그인 권한 번호를 그대로 사용한다.
 * 따라서 번호 앞자리만 비교하면 메뉴가 닫힌다. 현재 URL도 함께 비교해
 * 어느 하위 설정 화면으로 이동해도 해당 상위 메뉴를 열린 상태로 유지한다.
 */
function admin_menu_item_is_current($item) {
	global $sub_menu;
	if (!is_array($item) || empty($item[0])) return false;
	if (isset($sub_menu) && (string)$item[0] === (string)$sub_menu) return true;
	if (empty($item[2]) || empty($_SERVER['REQUEST_URI'])) return false;

	$item_path = parse_url(html_entity_decode((string)$item[2]), PHP_URL_PATH);
	$request_path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
	if (!$item_path || !$request_path) return false;
	return basename($item_path) === basename($request_path);
}

function admin_menu_has_current_item($key) {
	global $menu;
	if (empty($menu[$key]) || !is_array($menu[$key])) return false;
	foreach ($menu[$key] as $index => $item) {
		if ($index === 0 || (isset($item[3]) && $item[3] === 'section')) continue;
		if (admin_menu_item_is_current($item)) return true;
	}
	return false;
}

function print_menu2($key, $no){
	global $menu, $auth_menu, $is_admin, $auth, $g5, $sub_menu;

	$str .= "<div class=\"gnb_2dul\"><ul>";
	$gnb_grp_style = false;
	for($i=1; $i<count($menu[$key]); $i++) {
		if (isset($menu[$key][$i][3]) && $menu[$key][$i][3] === 'section') {
			$str .= '<li class="gnb_section" role="presentation"><span>'.$menu[$key][$i][1].'</span></li>';
			continue;
		}
		if ($is_admin != 'super' && (!array_key_exists($menu[$key][$i][0],$auth) || !strstr($auth[$menu[$key][$i][0]], 'r')))
			continue;

		if (($menu[$key][$i][4] == 1 && $gnb_grp_style == false) || ($menu[$key][$i][4] != 1 && $gnb_grp_style == true)) $gnb_grp_div = 'gnb_grp_div';
		else $gnb_grp_div = '';

		if ($menu[$key][$i][4] == 1) $gnb_grp_style = 'on';
		else $gnb_grp_style = '';

		$check_gnb_grp_style = "";
		if (admin_menu_item_is_current($menu[$key][$i])) {
			$check_gnb_grp_style = "check";
		}

		$str .= '<li class="gnb_2dli '.$check_gnb_grp_style.'"><a href="'.$menu[$key][$i][2].'" class="gnb_2da '.$gnb_grp_style.' '.$gnb_grp_div.'" data-text="'.$menu[$key][$i][1].'">'.$menu[$key][$i][1].'</a></li>';

		$auth_menu[$menu[$key][$i][0]] = $menu[$key][$i][1];
	}
	$str .= "</ul></div>";

	return $str;
}
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
<style>
/* A/K 통합 전투 메뉴의 소제목은 링크가 아니라 읽기 전용 구분선이다. */
.adminGnbArea .gnb_2dul .gnb_section { display:block; padding:14px 18px 5px; color:#8496a7; font-size:11px; font-weight:700; letter-spacing:.08em; line-height:1.2; text-transform:uppercase; pointer-events:none; }
.adminGnbArea .gnb_2dul .gnb_section:not(:first-child) { margin-top:5px; border-top:1px solid #edf1f4; }
</style>

<div class="adminWrap">

<header class="adminHeader">
	<div class="inner">
		<h1>
			<a href="<?php echo G5_ADMIN_URL ?>"><strong><?=$config['cf_title']?> Management</strong></a>
			<i><?=G5_GNUBOARD_VER?></i>
		</h1>
		<aside>
			<a href="<?php echo G5_BBS_URL ?>/logout.php" ><span class="material-symbols-outlined">logout</span></a>
			<a href="<?=G5_URL?>" target="_blank"><span class="material-symbols-outlined">home</span></a>
			<a href="https://avocado-edition-rout.postype.com/" target="_blank"><span class="material-symbols-outlined">developer_guide</span></a>
		</aside>
	</div>
</header>
<nav class="adminGnbArea auto-horiz">
	<div id="gnb" class="inner">
		<?php
		$gnb_str = "<ul>";
		foreach($amenu as $key=>$value) {
			$href1 = $href2 = '';
			if ($menu['menu'.$key][0][2]) {
				$href1 = '<a href="'.$menu['menu'.$key][0][2].'" class="gnb_1da" data-text="'. $menu['menu'.$key][0][1].'">';
				$href2 = '</a>';
			} else {
				continue;
			}
			$current_class = "";
			if ((isset($sub_menu) && (substr($sub_menu, 0, 3) == substr($menu['menu'.$key][0][0], 0, 3))) || admin_menu_has_current_item('menu'.$key))
				$current_class = " on";
			$gnb_str .= '<li class="gnb_1dli'.$current_class.'">'.PHP_EOL;
			$gnb_str .=  $href1 . $menu['menu'.$key][0][1] . $href2;
			$gnb_str .=  print_menu1('menu'.$key, 1);
			$gnb_str .=  "</li>";
		}
		$gnb_str .= "</ul>";
		echo $gnb_str;
		?>
	</div>
</nav>
<section class="adminBody">
	<? if($g5['title'] != "관리자메인") { ?>
	<div class="pageTitle">
		<h2><?php echo $g5['title'] ?></h2>
	</div>
	<? } ?>
	<div class="container">
