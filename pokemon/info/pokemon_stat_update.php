<?include_once('./_common.php');

    $po=get_pokemon($ph_id);
    if(!$po['ph_id']||$po['ch_id']!=$character['ch_id']){alert("잘못된 접근입니다.");}
    $mb_id=$member['mb_id'];
    $berry_id=array('1'=>$pkm_cf['st_1_it_id'],'2'=>$pkm_cf['st_2_it_id'],'3'=>$pkm_cf['st_3_it_id'],'4'=>$pkm_cf['st_4_it_id'],'5'=>$pkm_cf['st_5_it_id']);
    
    $cnt_check=true;
    $sql_set="";
    for ($i=1; $i < 6; $i++) { 
        if($_POST['berry_cnt'][$i]>0){
            $cnt=sql_fetch (" SELECT count(in_id) as cnt from {$g5['inventory_table']} where ch_id='{$character['ch_id']}' and in_5 != 1 and is_del !=1 and it_id='{$berry_id[$i]}'");
            if($cnt['cnt']<$_POST['berry_cnt'][$i]){
                $cnt_check=false;
                break;
            }
        }
    }
    if(!$cnt_check){ alert("아이템이 부족합니다.");}

    for ($i=1; $i < 6; $i++) { 
        if($_POST['berry_cnt'][$i]>0){
            sql_query (" UPDATE {$g5['inventory_table']} set is_del = 1 where ch_id='{$character['ch_id']}' and in_5 != 1 and is_del !=1 and it_id='{$berry_id[$i]}' limit {$_POST['berry_cnt'][$i]}");
            $sql_set.=", po_st{$i} = po_st{$i} + {$_POST['berry_cnt'][$i]}";
        }
    }

    sql_query (" UPDATE {$g5['pokemon_has_table']} set ph_id = '{$ph_id}' {$sql_set} where ph_id = '{$ph_id}' ");

    alert("포켓몬에게 아이템을 주었습니다!");

?>