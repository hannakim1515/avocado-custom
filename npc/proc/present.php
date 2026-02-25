<?
include_once("./_common.php");

$npc = get_npc_data($npc_id);
$npc['data'] = get_npc_state($npc_id, $character['ch_id'], $npc);

$talk_list = $npc['ns_talk_item'];
$talk_value = 0;
$inven = get_inventory_item($in_id);

if($inven['ch_id'] != $character['ch_id']) {
	exit;
}

// 해당 아이템이 값에 설정되어 있는지 확인
$npc_item = sql_fetch("select * from {$g5['npc_item_table']} where ch_id = '{$npc_id}' and it_id = '{$inven['it_id']}'");

if($npc_item['ni_id']) { 

	if($npc_item['ni_max_count'] > 0) {
		// 최대 횟수가 존재 할 때
		// 로그를 확인한다.

		if($npc_item['ni_max_is_total'] > 0) {
			$ni_count = sql_fetch("select count(*) as cnt from {$g5['npc_log_table']} where ni_id = '{$npc_item['ni_id']}' and ch_id = '{$character['ch_id']}' and  nl_date = '".date('Y-m-d')."'");
		} else {
			$ni_count = sql_fetch("select count(*) as cnt from {$g5['npc_log_table']} where ni_id = '{$npc_item['ni_id']}' and ch_id = '{$character['ch_id']}'");
		}
		$ni_count = $ni_count['cnt'];

		if($ni_count >= $npc_item['ni_max_count']) {
			// 초과 하였다.
			$talk = $npc_item['ni_max_comment'];
			$talk_value = 0;
		} else {
			$talk = $npc_item['ni_comment'];
			$talk_value = $npc_item['ni_value'];
		}
		
	} else {
		$talk = $npc_item['ni_comment'];
		$talk_value = $npc_item['ni_value'];
	}
} else {
	$talk_list = trim($talk_list);
	$talk = explode("<br />", nl2br($talk_list));
	$talk_index = rand(0, count($talk)-1);
	$talk = trim($talk[$talk_index]);
}
delete_inventory($in_id);

$talk = str_replace("[이름]은", $character['ch_name'].j($character['ch_name'],'은'), $talk);
$talk = str_replace("[이름]는", $character['ch_name'].j($character['ch_name'],'는'), $talk);

$talk = str_replace("[이름]을", $character['ch_name'].j($character['ch_name'],'을'), $talk);
$talk = str_replace("[이름]를", $character['ch_name'].j($character['ch_name'],'를'), $talk);

$talk = str_replace("[이름]이", $character['ch_name'].j($character['ch_name'],'이'), $talk);
$talk = str_replace("[이름]가", $character['ch_name'].j($character['ch_name'],'이'), $talk);

$talk = str_replace("[이름]과", $character['ch_name'].j($character['ch_name'],'과'), $talk);
$talk = str_replace("[이름]와", $character['ch_name'].j($character['ch_name'],'과'), $talk);
$talk = str_replace("[이름]", $character['ch_name'], $talk);

$npc['new_data'] = get_npc_state($npc_id, $character['ch_id']);

$log_data = array(
	"get_item" => $npc_item['ni_id'],
	"inven_item" => $inven['it_id'],
	"npc" => $npc_id,
	"ch_id" => $character['ch_id'],
	"value" => $talk_value,
	"state" => $npc['new_data']['state'],
	"log" => $talk
);
insert_npc_log($log_data);

$message = $talk;
$npc_now_state = $npc['new_data']['state'];
$level = $npc['new_data']['level'];
$next_level = $npc['new_data']['next_state']['level'];
$now_color = $npc['new_data']['color'];
$next_color = $npc['new_data']['next_state']['color'];

$per = 0;
$full_point = $npc['new_data']['next_point'] - $npc['new_data']['now_point'];
if($full_point <= 0) {
	$per = 100;
} else {
	$fill_point = $npc['new_data']['point'] - $npc['new_data']['now_point'];
	$per = $fill_point <= 0 ? 0 : (($fill_point/$full_point)*100);
}
?>
<div>
	<? include(G5_PATH."/npc/inc/npc_talk.inc.php"); ?>
</div>