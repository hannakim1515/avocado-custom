<?php
// MMB 레이드용 ra_id 설정 (전역 스코프에서 설정되지 않은 경우 대비)
if (empty($ra_id) && !empty($board['bo_table']) && ses($board, 'bo_1_subj', '') === 'mmbraid') {
    $ra_id = $board['bo_table'];
}

if(isset($is_all)&&$is_all){
    $log_sql = sql_query("
        SELECT * FROM {$battle_table}_log 
        WHERE ra_id='{$ra_id}' 
        ORDER BY lo_id DESC
    ");
}elseif(!isset($is_mmb)||!$is_mmb) {
    $log_sql = sql_query("
        SELECT *
        FROM {$battle_table}_log
        WHERE ra_id = '{$ra_id}'
        ORDER BY lo_id DESC
        LIMIT 20
        ");
}else{
    $log_sql = sql_query("
        SELECT *
        FROM {$battle_table}_log
        WHERE ra_id = '{$ra_id}'
        AND lo_2 = '{$wr_id_safe}'
        ORDER BY lo_id ASC
    ");
}
?>
<ul class="log-area" id="log_area">
<?php
    while ($log_row = sql_fetch_array($log_sql)) {
        $cls = h(ses($log_row, 'lo_1', ''));
        $content = ses($log_row, 'lo_content', ''); // 로그 내용은 HTML 포함 가능
        echo "<li class='{$cls}'>{$content}</li>";
    }
?>
</ul>
