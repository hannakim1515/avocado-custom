<?
include_once('./_common.php');
header("Content-Type: application/json");

$ph_id=$ma_id=$val;
$data['inner']="";
$data['msg']="";

if($val){
    if($type=='encount'){
        $ma=sql_fetch (" SELECT * from {$g5['pokemon_map_table']} where ma_id='{$ma_id}' ");
        if($ma['ma_img']){$ma_img="style=\"background-image:url({$ma['ma_img']})\"";}
        $ma_po_list="";
        $ma_po_sql = sql_query("SELECT po_id from {$g5['pokemon_table']} where FIND_IN_SET('{$ma_id}', ma_id) group by po_species order by po_id asc");
        for($i = 0; $row = sql_fetch_array($ma_po_sql); $i++) {
            $ma_po_list.="<li style=\"background-image:url(".G5_URL."/pokemon/img/{$row['po_id']}.png);\"></li>";
        };
        $data['inner']="<div class=\"map_detail\" {$ma_img}><ul>{$ma_po_list}</ul></div>";
        $data['msg']=$ma['ma_content'];
    }elseif($type=='release'){
        $ph_id=$val;
        $po=sql_fetch (" SELECT po.po_id, ph.po_name, po.po_species from {$g5['pokemon_has_table']} ph, {$g5['pokemon_table']} po where po.po_id=ph.po_id and ph.ph_id='{$ph_id}' ");
        if(!$po['po_name']){$po['po_name']=$po['po_species'];};
        $data['msg']="{$po['po_name']}을(를) 놓아줄까?<BR>이별한 포켓몬은 만난 포켓몬 목록으로 돌아갑니다.";
    }elseif($type=='partner'){
        $ph_id=$val;
        $po=get_pokemon($ph_id);
        if(!$po['po_name']){$po['po_name']=$po['po_species'];};
        if($po['ph_sex']==1){$po['ph_sex2']='♀';}elseif($po['ph_sex']==2){$po['ph_sex2']='♂';}else{$po['ph_sex2']='무성';}
        $data['inner']="<div class=\"po_detail\"><img src=\"{$po['po_dot']}\"><p class=\"po_name\">{$po['po_name']}<span>{$po['ph_sex2']}</span></p><p class=\"po_info\"><span>{$po['po_pers1']}</span><span>{$po['po_pers2']}</span></p><p class=\"po_content\">{$po['po_content']}<span>{$po['po_date']}에 {$po['ma_name']}에서 만났다.</span></p></div>";
        $data['msg']="{$po['po_name']}와(과) 파트너가 될까?";
    }elseif($type=='evolution'){
        $ph_id=$val;
        $evo_list='';
        $evo_count=0;

        $po=sql_fetch (" SELECT po.po_id, ph.po_name, po.po_species from {$g5['pokemon_has_table']} ph, {$g5['pokemon_table']} po where po.po_id=ph.po_id and ph.ph_id='{$ph_id}' ");
        if(!$po['po_name']){$po['po_name']=$po['po_species'];};

        $evo_cnt=sql_fetch (" SELECT count(po_id) as cnt from {$g5['pokemon_table']} where po_ex='{$po['po_id']}'");

        if($po['po_species']=='마빌크'){//마빌크 예외처리
            $color_list = $sugar_list = "";
            $colors=array("","밀키바닐라","밀키솔트","밀키루비","루비믹스","밀키말차","캐러맬믹스","밀키민트","트리플믹스","밀키레몬");
            $sugars=array("","딸기사탕공예","꽃사탕공예","별사탕공예","네잎사탕공예","베리사탕공예","리본사탕공예","하트사탕공예");

            for ($i=1; $i <= 7; $i++) { 
                $sugar_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"{$sugars[$i]}\" data-type=\"m\"><input type=\"radio\" name=\"sugar_select\" value=\"{$i}\"><img src=\"".G5_URL."/pokemon/img/s{$i}.png\"></label>";
            }
            for ($i=1; $i <= 9; $i++) { 
                $color_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"{$colors[$i]}\" data-type=\"m\"><input type=\"radio\" name=\"color_select\" value=\"{$i}\"><img src=\"".G5_URL."/pokemon/img/e{$i}.png\"></label>";
            }
            $color_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"랜덤 색상\" data-type=\"m\"><input type=\"radio\" name=\"color_select\" value=\"\" checked><img src=\"".G5_URL."/pokemon/img/rand.png\"></label>";
            $sugar_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"랜덤 사탕공예\" data-type=\"m\"><input type=\"radio\" name=\"sugar_select\" value=\"\" checked><img src=\"".G5_URL."/pokemon/img/rand.png\"></label>";
            
            $color_list="<p class=\"color_list\">{$color_list}</p>";
            $sugar_list="<p class=\"sugar_list\">{$sugar_list}</p>";
            $evo_list=$color_list.$sugar_list;
            
            $data['msg']="어라, {$po['po_name']}의 모습이…?<br>{$po['po_name']}을(를) 어떤 모습으로 진화시킬까?";
        }elseif($evo_cnt['cnt']>1){
            $evo_sql = sql_query("SELECT * from {$g5['pokemon_table']} where po_ex='{$po['po_id']}'");
            for($i = 0; $row = sql_fetch_array($evo_sql); $i++) {
                if($row['po_form']){$row['po_species']=$row['po_species']."({$row['po_form']})";}
                $evo_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"{$row['po_species']}\"><input type=\"radio\" name=\"evo_select\" value=\"{$row['po_id']}\"><img src=\"".G5_URL."/pokemon/img/{$row['po_id']}.png\"></label>";
            };
            $evo_list.= "<label class=\"evo_select\" onclick=\"evo_select(this)\" data-name=\"랜덤 포켓몬\" ><input type=\"radio\" name=\"evo_select\" value=\"\"><img src=\"".G5_URL."/pokemon/img/rand.png\"></label>";
            $data['msg']="어라, {$po['po_name']}의 모습이…?<br>{$po['po_name']}을(를) 어떤 포켓몬으로 진화시킬까?";
        }else{
            $evo=sql_fetch (" SELECT * from {$g5['pokemon_table']} where po_ex='{$po['po_id']}'");
            if($evo['po_form']){$evo['po_species']=$evo['po_species']."({$evo['po_form']})";}
            $evo_list.= "<label class=\"evo_select\"><input type=\"hidden\" name=\"evo_select\" value=\"{$evo['po_id']}\"><img src=\"".G5_URL."/pokemon/img/{$evo['po_id']}.png\"></label>";
            $data['msg']="어라, {$po['po_name']}의 모습이…?<br>{$po['po_name']}을(를) {$evo['po_species']}(으)로 진화시킬까?";
        } 
        $data['inner']="<div class=\"evo_detail\">{$evo_list}</div>";
    }
}



echo (json_encode($data));
?>