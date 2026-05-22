<?include_once('./_common.php');

$lo=sql_fetch (" SELECT * from {$g5['pokemon_encount_table']} where lo_id='{$lo_id}'");
$ma=sql_fetch (" SELECT ma_name from {$g5['pokemon_map_table']} where ma_id='{$lo['ma_id']}' ");

$bo_table=$lo['bo_table'];
$wr_id=$lo['wr_id'];
$it_name='';

$return_url=get_board_link($bo_table,$wr_id);

if(!$lo['lo_id']||$lo['lo_catch']||$character['ch_id']!=$lo['ch_id']){
    alert("잘못된 접근입니다.",$return_url);
}


if($submit=='n'){
    sql_query (" UPDATE {$g5['pokemon_encount_table']} set lo_catch = '9999', lo_2='{$it_name}' where lo_id='{$lo_id}' ");
    alert("포켓몬들은 어디론가 흩어졌습니다...",$return_url);
}else{

    $encount_number="encount_{$wr_id}";
    $en=$_POST[$encount_number];
    if(!$en){ alert("아이템을 줄 포켓몬을 선택해 주세요!",$return_url);}

    
    $en=$en-1;
    $encount_list=explode("|",$lo['po_id']);
    $egg_list=explode("|",$lo['po_egg']);
    $po_id=$encount_list[$en];
    
    if($egg_list[$en]){$is_egg=30;}else{$is_egg=0;}
    
    $return=insert_friend_pokemon($po_id, $character['ch_id'], $ma['ma_name'], $is_egg);    
    if(!$return){alert("잘못된 접근입니다.",$return_url);}

    sql_query (" UPDATE {$g5['pokemon_encount_table']} 
        set lo_catch = '{$po_id}',po_egg = '{$is_egg}', lo_2='{$it_name}'
        where lo_id='{$lo_id}' ");

    alert("포켓몬과 친구가 되었습니다!",$return_url);

}




        


?>