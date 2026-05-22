<?
include_once('./_common.php');
header("Content-Type: application/json");

$in=get_inventory_item($in_id);
$po=get_pokemon($ph_id, false);

if(!$in['in_id']){
    $data['html']="<p>아이템 정보를 찾을 수 없습니다.</p>";
}else{
    
    if($equip){
        if($po[$type]==$in_id){
            $data['result']='off';
            $po[$type]='';
            $data['title']=false;
            $data['img']=false;
            if($type=='po_ball'){
                $data['img']=G5_URL.'/pokemon/img/ball/1.png';
            }
        }else{
            $data['result']='on';
            $po[$type]=$in_id;
            $data['title']=$in['it_value'];
            $data['img']=$in['it_img'];
        }
        
        sql_query (" UPDATE {$g5['pokemon_has_table']} 
                     set {$type} = '{$po[$type]}'
                     where ph_id='{$ph_id}' ");
    }

    $data['html']="<p class=\"it_name\">{$in['it_name']}</p>";
    $data['html'].="<p class=\"it_content\">{$in['it_content']}</p>";
    if($in['it_value']&&$in['it_type']=='리본'){
        $data['html'].="<p class=\"it_value\">[{$in['it_value']}] 수식어가 붙는다.</p>";
    }
    if($po['ch_id']==$character['ch_id']){
        if($po[$type]==$in_id){
            $data['html'].="<span class=\"ui-btn equip\" onclick=\"pokemon_equip(this);\" data-type=\"{$type}\" data-idx=\"{$in_id}\">해제</p>";
        }else{
            $data['html'].="<span class=\"ui-btn equip\" onclick=\"pokemon_equip(this);\" data-type=\"{$type}\" data-idx=\"{$in_id}\">장착</p>";
        }
    }
}

echo (json_encode($data));?>