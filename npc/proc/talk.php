<?
include_once("./_common.php");

$npc = get_npc_data($npc_id);
$npc['data'] = get_npc_state($npc_id, $character['ch_id'], $npc);

// 대화 로그를 저장한다.
// -- 오늘 대화한 횟수를 가져온다.
$today_count = sql_fetch("select count(*) as cnt from {$g5['npc_log_table']} where ns_id = '{$npc_id}' and ch_id = '{$character['ch_id']}' and nl_item = 0 and nl_date = '".date('Y-m-d')."'");
$today_count = $today_count['cnt'];

$talk_list = $npc['data']['talk'];
$talk_value = $npc['ns_talk_point'];

$txt_item = "";

if($npc['ns_talk_max'] > 0 && $today_count >= $npc['ns_talk_max']) {
	// 최대 대화 초과 했을 시
	$talk_list = $npc['ns_talk_max_txt'];
	$talk_value = 0;
}
if($npc['ns_talk_hate'] > 0 && $today_count >= $npc['ns_talk_hate']) {
	// 대화를 너무 많이 하여 호감도 하락 했을 시
	$talk_list = $npc['ns_talk_hate_txt'];
	$talk_value = ($npc['ns_talk_hate_point'] * -1);
}

$talk_list = trim($talk_list);
$talk = explode("<br />", nl2br($talk_list));
$talk_index = rand(0, count($talk)-1);
$talk = trim($talk[$talk_index]);

$talk = str_replace("[이름]은", $character['ch_name'].j($character['ch_name'],'은'), $talk);
$talk = str_replace("[이름]는", $character['ch_name'].j($character['ch_name'],'는'), $talk);

$talk = str_replace("[이름]을", $character['ch_name'].j($character['ch_name'],'을'), $talk);
$talk = str_replace("[이름]를", $character['ch_name'].j($character['ch_name'],'를'), $talk);

$talk = str_replace("[이름]이", $character['ch_name'].j($character['ch_name'],'이'), $talk);
$talk = str_replace("[이름]가", $character['ch_name'].j($character['ch_name'],'이'), $talk);

$talk = str_replace("[이름]과", $character['ch_name'].j($character['ch_name'],'과'), $talk);
$talk = str_replace("[이름]와", $character['ch_name'].j($character['ch_name'],'과'), $talk);

$talk = str_replace("[이름]", $character['ch_name'], $talk);

$txt_item = "";

if($npc['data']['item']) {
	// 획득 아이템이 있을 경우
	// -- 기존에 획득한 내역이 있는지 확인이 필요하다.

	$item_get_count = sql_fetch("select count(*) as cnt from {$g5['npc_log_table']} where ns_id = '{$npc_id}' and ch_id = '{$character['ch_id']}' and it_id = '{$npc['data']['item']}' and ns_state = '{$npc['data']['state']}'");
	$item_get_count = $item_get_count['cnt'];

	if($item_get_count == 0) {
		// 획득한 적이 없을 경우
		// 아이템을 인벤토리에 넣어야 한다.
		$item = get_item($npc['data']['item']);
		
		if($item['it_id']) {
			$sql = " insert into {$g5['inventory_table']}
						set ch_id = '{$character['ch_id']}',
							it_id = '{$item['it_id']}',
							it_name = '{$item['it_name']}',
							ch_name = '{$character['ch_name']}',
							se_ch_id = '{$npc_id}',
							se_ch_name = '{$npc['ch_name']}',
							re_ch_id = '{$character['ch_id']}',
							re_ch_name = '{$character['ch_name']}',
							in_memo = '{$talk}'";
			sql_query($sql);
			$txt_item = "<div class='get-item'>
							<div class='thumb'><em><img src='{$item['it_img']}' alt='' /></em></div>
							<div class='item-con'>
								<div class='title'><strong>{$item['it_name']}</strong>".j($item['it_name'], '을')." 받았습니다.</div>
								<span>{$item['it_content']}</span>
							</div>
						</div>";
			$npc_it_id = $item['it_id'];
		}
	}
}

$npc['new_data'] = get_npc_state($npc_id, $character['ch_id'], $npc);

$log_data = array(
	"get_item" => '',
	"npc" => $npc_id,
	"ch_id" => $character['ch_id'],
	"value" => $talk_value,
	"give" => $npc_it_id,
	"state" => $npc['new_data']['state'],
	"log" => $talk
);
insert_npc_log($log_data);


$message = $txt_item.$talk;
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