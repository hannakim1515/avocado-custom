<?php
$sub_menu = "100100";
include_once('./_common.php');

check_demo();

auth_check($auth[$sub_menu], 'w');

check_admin_token();

$sql = " update {$g5['pokemon_config_table']}
            set po_max = '{$_POST['po_max']}',
                st_1 = '{$_POST['st_1']}',
                st_2 = '{$_POST['st_2']}',
                st_3 = '{$_POST['st_3']}',
                st_4 = '{$_POST['st_4']}',
                st_5 = '{$_POST['st_5']}',
                st_1_icon = '{$_POST['st_1_icon']}',
                st_2_icon = '{$_POST['st_2_icon']}',
                st_3_icon = '{$_POST['st_3_icon']}',
                st_4_icon = '{$_POST['st_4_icon']}',
                st_5_icon = '{$_POST['st_5_icon']}',
                st_1_it_id = '{$_POST['st_1_it_id']}',
                st_2_it_id = '{$_POST['st_2_it_id']}',
                st_3_it_id = '{$_POST['st_3_it_id']}',
                st_4_it_id = '{$_POST['st_4_it_id']}',
                st_5_it_id = '{$_POST['st_5_it_id']}',
                encount_it_id = '{$_POST['encount_it_id']}',
                partner_it_id = '{$_POST['partner_it_id']}',
                release_it_id = '{$_POST['release_it_id']}',
                evo_it_id = '{$_POST['evo_it_id']}'
            where cf_id = 1";
sql_query($sql);

goto_url('./pokemon_config.php', false);
?>