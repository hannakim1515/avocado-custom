<hr class="padding" />
<h3>POKEMON</h3>
<?
    $relation=array();
    $relation_sql = sql_query("SELECT ph_id from {$g5['pokemon_has_table']} where ch_id='{$ch['ch_id']}' order by ph_type desc");
    for($i = 0; $row = sql_fetch_array($relation_sql); $i++) {
        $relation[] = $row;
    };
    include(G5_PATH."/pokemon/info/relation_pokemon_list.php");
?>