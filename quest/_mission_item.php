<?php
include_once("./_common.php");

// 파라미터 처리
$keyword = ses($_GET, 'keyword', '', 'string');
$option = ses($_GET, 'option', 1, 'int');
$list_obj = ses($_GET, 'list_obj', '', 'raw');
$input_obj = ses($_GET, 'input_obj', '', 'raw');
$output_obj = ses($_GET, 'output_obj', '', 'raw');

if(!$is_member) { 
	echo "<ul><li class='no-data'>회원만 검색 가능합니다.</li></ul>";
} else {
	if($keyword == "") { 
		echo "<ul><li class='no-data'>키워드를 입력해 주시길 바랍니다.</li></ul>";
	} else {
		echo "<ul>";
		$result = sql_query("SELECT * FROM {$g5['inventory_table']} ai JOIN {$g5['item_table']} item ON ai.it_id = item.it_id
					WHERE ai.ch_id = '{$character['ch_id']}'
					and item.it_name like '%{$keyword}%'
					and item.it_has != '1'
					AND ai.it_id IN (
						SELECT it_id
						FROM {$g5['inventory_table']}
						WHERE ch_id = '{$character['ch_id']}'
						and se_ch_id = ''
						GROUP BY it_id
						HAVING COUNT(*) >= {$option})
					GROUP BY item.it_id
					order by item.it_name asc");
		for($i=0; $row = sql_fetch_array($result); $i++) {
			$list_obj_safe = h($list_obj);
			$input_obj_safe = h($input_obj);
			$output_obj_safe = h($output_obj);
			$it_name_safe = h($row['it_name']);
			$it_content_safe = h($row['it_content']);
	?>
				<li>
					<a href="#" onclick="select_item('<?php echo $list_obj_safe?>', '<?php echo $input_obj_safe?>', '<?php echo $it_name_safe?>', '<?php echo $output_obj_safe?>', '<?php echo $row['it_id']?>'); return false;">
						<div class="ui-thumb">
							<img src="<?php echo h($row['it_img'])?>">
						</div>
						<div class="ui-info">
							<p class="point"><strong>아이템</strong>: <?php echo $it_name_safe?></p>
							<p><strong>설명</strong>: <?php echo $it_content_safe?></p>
						</div>
					</a>
				</li>
	<?php
		}
		if($i==0) { 
			echo "<li class='no-data'>[ ".$keyword." ]에 대한 검색결과가 존재하지 않습니다.</li>";
		}
		echo "</ul>";
	}
}
?>