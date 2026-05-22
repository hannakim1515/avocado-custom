<?
$cm_list=array();
$command_sql = sql_query("SELECT * from {$g5['pokemon_map_table']} where ma_type='command'");
for($i = 0; $row = sql_fetch_array($command_sql); $i++) {
    $cm_list[$row['ma_name']] = $row;
};
if($character['ch_state']=='승인'){
    if($character['egg_id']){$egg=get_pokemon($character['egg_id'],false);}
}
?>
<style>@import url(<?=G5_URL?>/pokemon/encount/action.css);</style>