<?php
$sub_menu = '920200';
include_once('./_common.php');
check_demo();

$sql = " update {$g5['config_table']}
			set cf_skill_count = '{$_POST['cf_skill_count']}',
				cf_skill_count_max = '{$_POST['cf_skill_count_max']}'";
sql_query($sql);

goto_url('./skill_list.php?'.$qstr);
?>