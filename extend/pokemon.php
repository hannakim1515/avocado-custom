<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['pokemon_table'] = G5_TABLE_PREFIX.'pokemon';
$g5['pokemon_config_table'] = G5_TABLE_PREFIX.'pokemon_config';
$g5['pokemon_has_table'] = G5_TABLE_PREFIX.'pokemon_has';
$g5['pokemon_map_table'] = G5_TABLE_PREFIX.'pokemon_map';
$g5['pokemon_log_table'] = G5_TABLE_PREFIX.'pokemon_log';
$g5['pokemon_encount_table'] = G5_TABLE_PREFIX.'pokemon_encount';
$g5['pokemon_type_table'] = G5_TABLE_PREFIX.'pokemon_type';

if(!strstr($config['cf_item_category'], '알영양제')) {
	$config['cf_item_category'] .= "||알영양제||기술머신||리본||볼캡||리본제작||볼캡제작";
}

$pkm_cf=sql_fetch (" SELECT * from {$g5['pokemon_config_table']} limit 1");
$poke_pers=array('노력하는','외로움타는','고집스러운','개구쟁이','용감한','대담한','온순한','장난꾸러기','촐랑대는','무사태평한','조심스러운','의젓한','수줍은','덜럼대는','냉정한','차분한','얌전한','신중한','변덕스러운','건방진','겁쟁이','성급한','명랑한','천진난만한','성실한');
$poke_pers2=array('먹는 것을 제일 좋아함','힘자랑이 특기','몸이 튼튼함','호기심이 강함','기가 센 성격','달리기를 좋아함','낮잠을 잘잠','난동부리기를 좋아함','맷집이 강함','장난을 좋아함','조금 겉치레를 좋아함','주위소리에 민감함','말뚝잠이 많음','약간 화를 잘 내는 성미','끈질김','빈틈이 많음','오기가 센 성격','촐랑대는 성격','물건을 잘 어지름','싸움을 좋아함','인내심이 강함','걱정거리가 많음','지기 싫어함','약간 우쭐쟁이','유유자적을 좋아함','혈기가 왕성함','잘 참음','매우 꼼꼼함','조금 고집통이','도망에는 선수');
$po_it_id=array('partner'=>$pkm_cf['partner_it_id'],'evo'=>$pkm_cf['evo_it_id'],'release'=>$pkm_cf['release_it_id'],'encount'=>$pkm_cf['encount_it_id']);
$po_st_id=array('1'=>$pkm_cf['st_1'],'2'=>$pkm_cf['st_2'],'3'=>$pkm_cf['st_3'],'4'=>$pkm_cf['st_4'],'5'=>$pkm_cf['st_5']);

function get_wild_pokemon($po_id){
    global $g5;
    $po=sql_fetch (" SELECT * from {$g5['pokemon_table']} 
    where po_id='{$po_id}' ");
    return $po;
}

function get_pokemon($ph_id, $detail=true){
    global $g5;
    $po=sql_fetch (" SELECT * from {$g5['pokemon_table']} po, {$g5['pokemon_has_table']} ph where po.po_id=ph.po_id and ph_id = '{$ph_id}' ");
    
    if($po['ph_egg']>0){
        $po['po_dot']=G5_URL."/pokemon/img/9999.png";
        if($po['ph_egg']<8){
            $po['po_content']="알이 곧 깨어날 것 같다!";
        }elseif($po['ph_egg']<16){
            $po['po_content']="알이 조금 움직이는 것 같다.";
        }elseif($po['ph_egg']<24){
            $po['po_content']="알은 약간 따듯한 것 같다.";
        }else{
            $po['po_content']="포켓몬 알이다.";
        }
    }
    if(!$po['po_dot']){
        $po['po_dot']=G5_URL."/pokemon/img/{$po['po_id']}.png";
        $po['dot_default']=true;
    }
    if(!$po['po_name']){$po['po_name']=$po['po_species'];}
    if($detail){
        if($po['po_ribbon']){
            $po['po_ribbon']=get_inventory_item($po['po_ribbon']);
        }
        if($po['po_ball']){
            $po['po_ball']=get_inventory_item($po['po_ball']);
        }else{
            $po['po_ball']=array();
            $po['po_ball']['it_img']=G5_URL."/pokemon/img/objects/ball_default.png";
        }
    }

    return $po;
}

function get_pokemon_list($ch_id, $detail=true){
    global $g5;
 
    $po_list=array();
    $po_list[0]=get_partner($ch_id, $detail);
    $po_sql = sql_query("SELECT ph_id from {$g5['pokemon_has_table']} where ph_type!='friend' and ch_id = '{$ch_id}' and ph_id!='{$po_list[0]['ph_id']}' ");
    for($i = 0; $row = sql_fetch_array($po_sql); $i++) {
        $k=$i+1;
        $po_list[$k] = get_pokemon($row['ph_id'], $detail);
    };

    return $po_list;
}

function get_partner($ch_id, $detail=true){
    global $g5;
    $ch=get_character($ch_id);
    $po=get_pokemon($ch['ph_id'],$detail);
    return $po;
}

function pokemon_encount($ma_id,$ch_id,$count=3,$egg_per=0){
    global $g5,$bo_table,$wr_id;

    $time=defined('G5_TIME_YMDHIS')?G5_TIME_YMDHIS:date('Y-m-d H:i:s');
    $ma_id=(int)$ma_id; 
    $ch_id=(int)$ch_id; 
    $count=max(1,(int)$count); 
    $egg_per=max(0,min(10,(int)$egg_per));

    $ma=sql_fetch("SELECT ma_id,ma_egg FROM {$g5['pokemon_map_table']} WHERE ma_id='{$ma_id}'");
    if(empty($ma['ma_id'])) return false;
    if((int)$ma['ma_egg']>0) $egg_per=(int)$ma['ma_egg'];

    $encount=check_pokemon_item($ch_id,'encount');
    if(empty($encount['in_id'])) return false;

    $res=sql_query("SELECT po_id,po_egg FROM {$g5['pokemon_table']} WHERE FIND_IN_SET('{$ma_id}',ma_id) ORDER BY RAND() LIMIT {$count}");
    $ids=array(); $eggs=array();
    while($row=sql_fetch_array($res)){
        $isEgg=(mt_rand(1,10)<=$egg_per && $row['po_egg']>0)?1:0;
        $ids[]=(int)$row['po_id']; 
        $eggs[]=$isEgg;
    }
    if(!count($ids)) return false;

    $id_list=implode('|',$ids); 
    $egg_list=implode('|',$eggs);

    sql_query("INSERT INTO {$g5['pokemon_encount_table']} 
                        SET ch_id='{$ch_id}'
                        ,   po_id='{$id_list}'
                        ,   po_egg='{$egg_list}'
                        ,   ma_id='{$ma_id}'
                        ,   lo_datetime='{$time}'
                        ,   lo_type='default'
                        ,   wr_id='{$wr_id}'
                        ,   bo_table='{$bo_table}'");

    $lo_id=sql_insert_id();

    delete_inventory($encount['in_id']);
    return $lo_id;
}

function insert_friend_pokemon($po_id, $ch_id, $ma_name, $is_egg=0){
    global $g5, $poke_pers, $poke_pers2, $bo_table, $wr_id;

    $po=get_wild_pokemon($po_id);

    if(!$po['po_id']){return false;}

    $time=date("m월 d일");
    $pers1=array_rand($poke_pers);
    $pers1=$poke_pers[$pers1]." 성격";
    $pers2=array_rand($poke_pers2);
    $pers2=$poke_pers2[$pers2];
    $sex_list=explode('|',$po['po_sex']);
    $sex=array_rand($sex_list);
    $sex=$sex_list[$sex];
    $po_name=$po['po_species'];

    $po_st_max=100;
    $ch=get_character($ch_id);
    if(!$ch['ch_id']){return false;}
    sql_query (" INSERT into {$g5['pokemon_has_table']} 
                set ch_id = '{$ch_id}'
                ,po_id = '{$po_id}'
                ,po_name = '{$po_name}'
                ,ma_name = '{$ma_name}'
                ,po_pers1 = '{$pers1}'
                ,po_pers2 = '{$pers2}'
                ,ph_sex = '{$sex}'
                ,ph_type = 'friend'
                ,po_st_max = '{$po_st_max}'
                ,ph_egg = '{$is_egg}'
                ,po_date = '{$time}'" );

    $ph_id=sql_insert_id();
    $link=get_board_link($bo_table,$wr_id);

    insert_pokemon_log($ph_id,$ch_id,"조우",$link);

    
    return $ph_id;
}

function insert_partner_pokemon($ph_id,$ch_id){
    global $g5, $bo_table, $wr_id;

    $po=get_pokemon($ph_id,false);

    if(!$po['ph_id']||$po['ch_id']!=$ch_id||$po['ph_type']!="friend"||$po['ph_egg']){return false;}

    $poke_check=sql_fetch (" SELECT count(ph_id) as cnt from {$g5['pokemon_has_table']} 
                            where ch_id='{$ch_id}' and ph_type!='friend' ");

    if($poke_check['cnt']>3){return false;}
   
    $in=check_pokemon_item($ch_id, 'partner');
    if(!$in['in_id']){return false;}
    
    delete_inventory($in['in_id']);

    sql_query (" UPDATE {$g5['pokemon_has_table']} set ph_type='partner' where ph_id='{$ph_id}' ");
    
    $link=get_board_link($bo_table,$wr_id);
    insert_pokemon_log($ph_id,$ch_id,"포획",$link);

    delete_inventory($in['in_id']);
    return $po['po_species']."||".$po['po_dot'];
}

function evo_pokemon($ph_id, $ch_id, $evo_po_id=0){
    global $g5, $bo_table, $wr_id;

    $po=get_pokemon($ph_id, false);
    if(!$po['ph_id']||$po['ch_id']!=$ch_id||$po['ph_type']=='friend'){
        return false;
    }

    $in=check_pokemon_item($ch_id, 'evo');
    if(!$in['in_id']){return false;}

    $sql_search='';
    if($po['po_id']==683&&$evo_po_id){//마휘핑
        $sql_search="and po_form ='{$evo_po_id}'";
    }elseif($evo_po_id){
        $sql_search="and po_id='{$evo_po_id}'";
    }

    $evo=sql_fetch (" SELECT * from {$g5['pokemon_table']} where po_ex = '{$po['po_id']}' {$sql_search} order by rand() limit 1 ");

    if(!$evo['po_id']){return false;}
    if(!$po['po_name']){$po['po_name']=$po['po_species'];}
    $evo['po_name']=$po['po_name'];
        
    sql_query (" UPDATE {$g5['pokemon_has_table']} 
                    set po_id='{$evo['po_id']}', po_dot='', po_body=''
                    where ph_id = '{$ph_id}' ");

    delete_inventory($in['in_id']);

    $link=get_board_link($bo_table,$wr_id);
    insert_pokemon_log($ph_id,$ch_id,"진화",$link);
    
    return $evo;
}

function plus_stat_pokemon($ph_id, $ch_id, $in_id){
    global $g5;

    $po=get_pokemon($ph_id,false);
    $in=get_inventory_item($in_id);

    $st_ar="po_st{$in['st_id']}";

    if(!$in['in_id']||$in['ch_id']!=$ch_id||$po['ch_id']!=$ch_id||$po[$st_ar]>19||$po['ph_type']=='friend'){
        return false;
    }

    $po[$st_ar]+=$in['it_value'];
    if($po[$st_ar]>20){$po[$st_ar]=20;}

    sql_query (" UPDATE {$g5['pokemon_has_table']} set {$st_ar} = '{$po[$st_ar]}' where ph_id='{$ph_id}' ");
    
    delete_inventory($in_id);
    insert_pokemon_log($ph_id,$ch_id,"스탯","",$in['st_id'],$in['it_value']);
    
    return $po;
}

function release_pokemon($ph_id, $ch_id){

    global $g5, $wr_id, $bo_table;

    $po=get_pokemon($ph_id,false);
    if(!$po['ph_id']||$po['ch_id']!=$ch_id||$po['ph_type']!='partner'){return false;}

    $in=check_pokemon_item($ch_id, 'release');
    if(!$in['in_id']){return false;}

    $ch=get_character($ch_id);
    sql_query (" UPDATE {$g5['pokemon_has_table']} set po_ball='', ph_type='friend' where ph_id='{$ph_id}' ");
    
    if($ch['ph_id']==$ph_id){
        $new=sql_fetch (" SELECT ph_id from {$g5['pokemon_has_table']} where ch_id='{$ch_id}' and ph_type='partner_buddy'");
        sql_query (" UPDATE {$g5['character_table']} set ph_id='{$new['ph_id']}' where ch_id='{$ch_id}' ");
    }

    $link=get_board_link($bo_table,$wr_id);
    insert_pokemon_log($ph_id,$ch_id,"이별",$link);

    return $po;
}

function hatch_pokemon($ph_id, $ch_id, $value){
    global $g5, $bo_table, $wr_id;

    $po=get_pokemon($ph_id, false);
    $ch=get_character($ch_id);
    if(!$po['ph_id']||$ch_id!=$po['ch_id']||$ch['egg_id']!=$po['ph_id']||!$po['ph_egg']){ return false;}

    if($ch['egg_item']){
        $value=$value*2;
        sql_query (" UPDATE {$g5['character_table']} set egg_item = egg_item -1 where ch_id = '{$ch_id}' ");
    }

    $po['ph_egg']=$po['ph_egg']-$value;
    if($po['ph_egg']<=0){
        $po['ph_egg']=0;
        sql_query (" UPDATE {$g5['character_table']} set egg_id = '', egg_item = '' where ch_id = '{$ch_id}' ");
    }

    sql_query (" UPDATE {$g5['pokemon_has_table']} set ph_egg = '{$po['ph_egg']}' where ph_id = '{$ph_id}' ");
    
    if($po['ph_egg']<=0){
        $po=get_pokemon($ph_id, false);
    }
    
    $link=get_board_link($bo_table,$wr_id);
    insert_pokemon_log($ph_id,$ch_id,"알",$link);

    if(!$po['ph_egg']){
        insert_pokemon_log($ph_id,$ch_id,"부화",$link);
    }

    return $po;

}

function insert_pokemon_log($ph_id, $ch_id, $type, $link='', $lo_1='', $lo_2=''){
    global $g5;
    $time=G5_TIME_YMDHIS;
    sql_query (" INSERT into {$g5['pokemon_log_table']} 
                set ph_id='{$ph_id}',
                    ch_id='{$ch_id}',
                    lo_link='{$link}',
                    lo_type='{$type}',
                    lo_1='{$lo_1}',
                    lo_2='{$lo_2}',
                    lo_datetime='{$time}'" );

    $lo_id=sql_insert_id();             
    return $lo_id;
}

function get_board_link($bo_table,$wr_id){
    global $g5;

    $write_table = $g5['write_prefix'].$bo_table;
    $wr=sql_fetch (" SELECT wr_num from {$write_table} where wr_id = '{$wr_id}'");
    $wr['wr_num']=$wr['wr_num']*-1;
    $link=G5_URL."/bbs/board.php?bo_table={$bo_table}&log={$wr['wr_num']}";
    
    return $link;
}

function check_pokemon_item($ch_id, $it_type){
    global $g5, $po_it_id;

    $in=sql_fetch (" SELECT in_id from {$g5['inventory_table']} where it_id ='{$po_it_id[$it_type]}' and ch_id ='{$ch_id}'");
    
    return $in;
}

function count_pokemon_item($ch_id, $it_type){
    global $g5, $po_it_id;

    $in=sql_fetch (" SELECT count(in_id) as cnt from {$g5['inventory_table']} where it_id ='{$po_it_id[$it_type]}' and ch_id ='{$ch_id}'");
    
    return $in['cnt'];
}

?>