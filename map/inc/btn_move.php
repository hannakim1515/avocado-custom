<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가 

$ch_map = get_map($character['ma_id']);
if(strstr($ch_map['ma_move'], "||".$ma['ma_id']."||")) {
?>

<button type="button" onclick="map_move(<?=$ma['ma_id']?>);" class="ui-btn"><span>이동하기</span></button>
<script>
function map_move(idx) { 
	var formData = new FormData();
	formData.append("idx", idx);
	$.ajax({
		url:g5_url + '/map/proc/map_move.php'
		, data: formData
		, processData: false
		, contentType: false
		, type: 'POST'
		, success: function(data){
			if(data) {
				$('.map-script').empty().append(data);
			}
		}
	});
}
</script>
<? } ?>