<?php
include_once('./_common.php');

if($url) { 
	$return_url = urldecode($url);
} else {
	$return_url = "./viewer.php?ch_id=".$ch_id;
}

$in = sql_fetch("select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.in_id = '{$in_id}' and inven.it_id = item.it_id");
if(!$in['in_id']) { 
	echo "<p>아이템 보유 정보를 확인할 수 없습니다.</p>";
} else {
	$claim = inventory_boundary_begin_or_alert(array($in['in_id']), 'inventory.sell', array(), 'remove');
	$in = array_merge($claim['items'][(int)$claim['rows'][0]['it_id']], $claim['rows'][0]);
	$money = $in['it_sell'];
	$paid = insert_point($member['mb_id'], $money, '아이템 : '.$in['it_name'].' 판매보상 ( '.$money.$config['cf_money_pice'].' 획득 )', '@inventory', (string)$claim['journal_id'], 'sell');
	if ($money > 0 && $paid !== 1 && $paid !== -1) alert('판매 포인트 지급을 확인할 수 없습니다. 관리자에게 처리 기록을 문의해 주세요.');
	inventory_boundary_done($claim);
	echo "LOCATIONURL||||".$return_url;
} ?>
