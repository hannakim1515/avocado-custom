<?
include_once('./_common.php');
header("Content-Type: application/json");

if($type=='ch'){
    $rm=sql_fetch (" SELECT * from {$g5['relation_table']} where rm_id='{$rm_id}' ");
    if($rm['ch_id']==$character['ch_id']){
        sql_query (" UPDATE {$g5['relation_table']} 
        set rm_memo = '{$rm_memo}'
        where rm_id='{$rm_id}' ");
    }
    $data[]=true;
}elseif($type=='po'){
    $po=get_pokemon($rm_id, false);
    if($po['ch_id']==$character['ch_id']){
        sql_query (" UPDATE {$g5['pokemon_has_table']} 
        set po_content = '{$rm_memo}'
        where ph_id='{$rm_id}' ");
    }
}

echo (json_encode($data));?>