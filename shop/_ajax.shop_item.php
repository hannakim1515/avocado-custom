<?
include_once("./_common.php");

$item = sql_fetch("select * from {$g5['shop_table']} shop, {$g5['item_table']} item where shop.it_id = item.it_id and shop.sh_id = '{$sh_id}'");
$discount = get_npc_cost($config['cf_shop_npc'], $character['ch_id']);

if($item['sh_id']) {

	$money = "";
	$add_str = "";

	if($item['sh_money'] && $item['sh_use_money']) {
		// 상점 NPC 정보 확인
		$item['sh_money'] = floor($item['sh_money'] * $discount);
		$money .= $add_str.$item['sh_money'].' '.$config['cf_money_pice'];
		$add_str = ", ";
	}

	if($item['sh_exp'] && $item['sh_use_exp']) { 
		$money .= $add_str.$item['sh_exp'].' '.$config['cf_exp_pice'];
		$add_str = ", ";
	}

	if($item['sh_has_item'] && $item['sh_use_has_item']) { 
		$item['sh_has_item_count'] = ($item['sh_has_item_count'] == '' || $item['sh_has_item_count'] == '0') ? 1 : $item['sh_has_item_count'];
		$money .= $add_str.get_item_name($item['sh_has_item']).' '.$item['sh_has_item_count'].'개';
		$add_str = ", ";
	}

	if($item['it_content2']) { 
		$item['it_content'] .= "<br />(".$item['it_content2'].")";
	}
}

if(defined('G5_THEME_PATH') && is_file(G5_THEME_PATH."/shop/shop.item.skin.php")) {
	include(G5_THEME_PATH."/shop/shop.item.skin.php");
} else {
	include(G5_PATH."/shop/skin/shop.item.skin.php");
}

?>
