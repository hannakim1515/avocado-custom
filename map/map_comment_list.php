<?php
include_once('./_common.php');

$sql_common = " from {$g5['map_comment_table']} where ma_id = '{$ma_id}' ";
$sql_order = " order by mc_datetime desc ";

$sql = " select count(*) as cnt {$sql_common} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 5;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " select * {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$write_paging = get_paging(5, $page, $total_page, "#");
?>
<ul>
	<? for($i=0; $row = sql_fetch_array($result); $i++) {
		$ch = sql_fetch("select ch_name, ch_thumb from {$g5['character_table']} where ch_id = '{$row['ch_id']}'");
	?>
		<li>
			<div class="message">
				<div class="thumb">
					<div style="background-image:url(<?=$ch['ch_thumb']?>);"></div>
				</div>
				<div class="comment">
					<p class="name">
						<?=$ch['ch_name']?>
						<? if($row['ch_id'] == $character['ch_id'] || $is_admin) { ?>
							<a href="javascript:comment_delete('<?=$row['mc_id']?>');" class="del">삭제</a>
						<? } ?>
					</p>
					<div class="txt">
						<?=$row['mc_content']?>
					</div>
				</div>
			</div>
		</li>
	<? } ?>
</ul>
<div id="comment_paging">
	<?=$write_paging?>
</div>
<script>
$('#comment_paging a').on('click', function(e) {
	e.preventDefault();
	var paging = $(this).attr('href');
	paging = paging.replace("#&page=","");
	var ma_id = <?=$ma_id?>;
	var h_link = "./map_comment_list.php?page=" + paging + "&ma_id=" + ma_id;
	$.ajax({
		async: true
		, url: h_link
		, beforeSend: function() {}
		, success: function(data) {
			var response = data;
			$('#area_message').empty().append(response);
		}
		, error: function(data, status, err) {
			$('#area_message').empty();
		}
	});
	return false;
});

function comment_delete(idx) {
	if(confirm("정말 삭제하시겠습니까?")) {
		var paging = <?=$page?>;
		var ma_id = <?=$ma_id?>;
		var h_link = g5_url + "/map/proc/map_comment_delete.php?page=" + paging + "&mc_id=" + idx;
		$.ajax({
			async: true
			, url: h_link
			, beforeSend: function() {}
			, success: function(data) {
				var response = data;
				$('#area_message').empty().append(response);
			}
			, error: function(data, status, err) {
				$('#area_message').empty();
			}
		});
	}
}
</script>