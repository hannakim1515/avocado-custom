<?include_once('./_common.php');
if($type=='main'){
    $po=get_pokemon($ph_id);
    if(!$po['ph_id']||$character['ch_id']!=$po['ch_id']){
        alert("잘못된 접근입니다.");
    }
    sql_query (" UPDATE {$g5['character_table']} 
                    set ph_id = '{$ph_id}'
                    where ch_id = '{$po['ch_id']}' ");
                    
    alert("메인 포켓몬을 변경하였습니다.");
}elseif($type=='rev'){
    $result=rev_pokemon($ph_id,$character['ch_id'],$member['mb_id']);
    if($result){
        alert("포켓몬이 각성하였습니다!");
    }else{
        alert("잘못된 접근입니다.");
    }

}else{
    $po=get_pokemon($ph_id);
    if(!$po['ph_id']||$po['ch_id']!=$character['ch_id']){alert("잘못된 접근입니다.");}
    $mb_id=$member['mb_id'];

    $pokemon_image_path = G5_DATA_PATH."/pokemon/".$mb_id;
    $pokemon_image_url = G5_DATA_URL."/pokemon/".$mb_id;

    @mkdir($pokemon_image_path, G5_DIR_PERMISSION);
    @chmod($pokemon_image_path, G5_DIR_PERMISSION);

    if($_FILES['po_dot_file']['name']) {
        // 확장자 따기
        $exp = explode(".", $_FILES['po_dot_file']['name']);
        $exp = $exp[count($exp)-1];
        $image_name = "dot_".time().".".$exp;
        upload_file($_FILES['po_dot_file']['tmp_name'], $image_name, $pokemon_image_path);
        $po_dot = $pokemon_image_url."/".$image_name;
    }

    if(!$po['dot_default']&&$po['po_dot'] != $po_dot) { 
        // 해당 서버에 업로드 한 파일일 경우
        $prev_file_path = str_replace(G5_URL, G5_PATH, $po['po_dot']);
        @unlink($prev_file_path);
    }
    sql_query (" UPDATE {$g5['pokemon_has_table']} 
                set po_name     ='{$po_name}',
                    po_content  ='{$po_content}',
                    po_dot		= '{$po_dot}'
                where ph_id = '{$ph_id}' ");
    alert("변경되었습니다.");
}


?>