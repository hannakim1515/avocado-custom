<?include_once('./_common.php');

$po=get_pokemon($ph_id, false);
if($po['ch_id']==$character['ch_id']){
    sql_query (" UPDATE {$g5['character_table']} set egg_id='{$po['ph_id']}' where ch_id='{$character['ch_id']}' ");
    alert("알을 부화기에 넣었습니다.");
}else{
    alert("잘못된 접근입니다.");
}?>