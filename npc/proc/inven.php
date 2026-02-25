<?
include_once("./_common.php");

// NPC에게 줄 아이템을 선택한다.
// 잠금 아이템은 제외.
$ch_id = $character['ch_id'];
$pe_inven_sql = "select * from {$g5['inventory_table']} inven, {$g5['item_table']} item where inven.ch_id = '{$ch_id}' and item.it_id = inven.it_id and inven.in_use = ''";
$pe_inven_sql .= " order by item.it_name asc";
$pe_inven_result = sql_query($pe_inven_sql);
for($i=0; $row=sql_fetch_array($pe_inven_result); $i++) {
	$pin[$i] = $row;
	$p_count++;
}
?>
<div>
	<? include(G5_PATH."/npc/inc/npc_inven.inc.php"); ?>
</div>