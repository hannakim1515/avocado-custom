<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/npc/css/style.css">', 0);

if(!$npc['ch_id']) {
	$npc = get_npc_data($ch['ch_id']);
}

$npc_id = $npc['ch_id'];
$npc['data'] = get_npc_state($npc_id, $character['ch_id'], $npc);

$message = $npc['data']['ready_talk'];
$npc_now_state = $npc['data']['state'];
$level = $npc['data']['level'];
$next_level = $npc['data']['next_state']['level'];
$now_color = $npc['data']['color'];
$next_color = $npc['data']['next_state']['color'];

$per = 0;

$full_point = $npc['data']['next_point'] - $npc['data']['now_point'];

if($full_point <= 0 || $level == 0) {
	$per = 100;
} else {
	$fill_point = $npc['data']['point'] - $npc['data']['now_point'];
	$per = $fill_point <= 0 ? 0 : (($fill_point/$full_point)*100);
}


?>
<div data-ajax-npc>
	<? include(G5_PATH."/npc/inc/npc_talk.inc.php"); ?>
</div>
<div data-ajax-inven style="display:none;">

</div>

<script>

function vew_npc_content(cate) {
	switch(cate) {
		case "talk" :
			$("[data-ajax-npc]").show();
			$("[data-ajax-inven]").hide();
		break;
		case "inven" : 
			$("[data-ajax-inven]").show();
			$("[data-ajax-npc]").hide();
		break;
	}
}

function set_npc_event(cate, npc_id) {
	var formData = new FormData();
	var url = g5_url;
	formData.append("npc_id", npc_id);

	$('.tab-con[data-tabs="대화"] .control button').attr('disabled');
	$('.tab-con[data-tabs="대화"] .control button').css('opacity','0.4');

	switch(cate) {
		case "talk" : url = url + '/npc/proc/talk.php'; break;
		case "inven" : url = url + '/npc/proc/inven.php'; break;
	}
	$.ajax({
		url:url
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(data) {
				switch(cate) {
					case "talk" : $("[data-ajax-npc]").empty().html(data); vew_npc_content('talk'); break;
					case "inven" : $("[data-ajax-inven]").empty().html(data); vew_npc_content('inven'); break;
				}
			}
			$('.tab-con[data-tabs="대화"] .control button').removeAttr('disabled').removeAttr('style');
		}
	});
}

function set_filter_inven(obj) {
	var txt = $(obj).val();
	if(txt != "") {
		$('#npc_my_inven_list li').hide();
		$('#npc_my_inven_list li[data-name*="'+txt+'"]').show();
	} else {
		$('#npc_my_inven_list li').show();
	}
}
function send_npc_item() {
	var params = $("#frm_npc_present").serialize();
	$.ajax({
		url:g5_url + '/npc/proc/present.php'
		, type: 'POST'
		, data:params
		, success: function (data) {
			console.log(data);
			if (data){
				$("[data-ajax-npc]").empty().html(data);
				$("[data-ajax-inven]").empty();
				vew_npc_content('talk');
			}
		}
	});
}

</script>
