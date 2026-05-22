<?
include_once("./_common.php");


if(!$is_member) { 
	echo "<ul><li class='no-data'>회원만 검색 가능합니다.</li></ul>";
} else {
	if($keyword == "") { 
		echo "<ul><li class='no-data'>키워드를 입력해 주시길 바랍니다.</li></ul>";
	} else {
		echo "<ul>";
		$sql = " select po_id, po_species, po_form from {$g5['pokemon_table']} where po_species like '%{$keyword}%'";
		$sql .= "order by po_species asc";
		$result = sql_query($sql);
		for($i=0; $row = sql_fetch_array($result); $i++) {
			if($row['po_form']&&$row['po_species']!='마휘핑'){$row['po_species'].=" ({$row['po_form']})";}
	?>
				<li>
					<a href="#" onclick="select_item('<?=$list_obj?>', '<?=$input_obj?>', '<?=$row['po_species']?>', '<?=$output_obj?>', '<?=$row['po_id']?>'); return false;">
						<div class="ui-thumb">
							<img src="<?=G5_URL?>/pokemon/img/<?=$row['po_id']?>.png">
						</div>
						<div class="ui-info">
							<p class="point"><?=$row['po_species']?></p>
						</div>
					</a>
				</li>
	<?
		}
		if($i==0) { 
			echo "<li class='no-data'>[ ".$keyword." ]에 대한 검색결과가 존재하지 않습니다.</li>";
		}
		echo "</ul>";
	}
}
?>