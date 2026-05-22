<?
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$g5['pokemon_battle_table'] = G5_TABLE_PREFIX.'pokemon_battle';
$g5['pokemon_waza_has_table'] = G5_TABLE_PREFIX.'pokemon_waza_has';
$g5['pokemon_waza_table'] = G5_TABLE_PREFIX.'pokemon_waza';
$g5['pokemon_battle_buff_table'] = G5_TABLE_PREFIX.'pokemon_battle_buff';
$g5['pokemon_battle_log_table'] = G5_TABLE_PREFIX.'pokemon_battle_log';
$g5['pokemon_event_log_table'] = G5_TABLE_PREFIX.'pokemon_event_log';
$g5['pokemon_battle_state_table'] = G5_TABLE_PREFIX.'pokemon_battle_state';

/*******************정보 처리부*******************/
function get_waza_list($ph_id, $re_ph_id = false) {//기술 리스트 출력
    global $g5;

    $def = null;
    $atk = get_battle_pokemon($ph_id, false);
    if ($re_ph_id) {
        $def = get_battle_pokemon($re_ph_id, false);
    }

    // 기술 정보 한 번에 미리 가져오기 (중복제거)
    $waza_list = [];
    $atk_types = [];
    $sql = "SELECT * 
            FROM {$g5['pokemon_waza_table']} wa
            JOIN {$g5['pokemon_waza_has_table']} wh ON wa.wa_id = wh.wa_id
            WHERE wh.ph_id = '{$ph_id}' order by wh.wa_order";
    $res = sql_query($sql);
    while ($row = sql_fetch_array($res)) {
        $waza_list[] = $row;
        if ($row['wa_category'] == '공격' || $row['wa_category'] == '상태이상') {
            $atk_types[] = $row['wa_type2'];
        }
    }
    $atk_types = array_unique($atk_types);

    // 상대 타입 목록
    $def_types = [];
    if ($def && isset($def['ph_id'])) {
        $def_types = array_filter([$def['po_type1'], $def['po_type2']]);
        $def_types = array_unique($def_types);
    }

    // 필요한 상성 쿼리만 추출
    $type_map = [];
    if ($atk_types && $def_types) {
        $where = [];
        foreach ($atk_types as $atk_type) {
            foreach ($def_types as $def_type) {
                $where[] = "(atk_type='{$atk_type}' AND def_type='{$def_type}')";
            }
        }
        if ($where) {
            $where_sql = implode(' OR ', $where);
            $sql2 = "SELECT atk_type, def_type, damage_per 
                    FROM {$g5['pokemon_type_table']} 
                    WHERE $where_sql";
            $res2 = sql_query($sql2);
            while ($r = sql_fetch_array($res2)) {
                $type_map[$r['atk_type']][$r['def_type']] = $r['damage_per'];
            }
        }
    }

    // 기술별 효과 메시지 작성
    $candidates = [];
    foreach ($waza_list as $wid => $row) {
        $row['wa_msg'] = '';
        if ($def && isset($def['ph_id'])&&($row['wa_category'] == '공격' || $row['wa_category'] == '상태이상')) {
            $bonus = 1;
            $row['wa_msg'] = "효과가 보통이다.";
            if ($def_types) {
                foreach ($def_types as $t) {
                    if (isset($type_map[$row['wa_type2']][$t])) {
                        $bonus *= $type_map[$row['wa_type2']][$t];
                    }
                }
                if ($bonus <= 0) {
                    $row['wa_msg'] = "효과가 없을 것 같다...";
                } elseif ($bonus < 1) {
                    $row['wa_msg'] = "효과가 별로일 것 같다...";
                } elseif ($bonus > 1) {
                    $row['wa_msg'] = "효과가 굉장하다!";
                }
            }
        }
        $candidates[] = $row;
    }

    return $candidates;
}
function get_battle_pokemon($ph_id,$buff=true){//포켓몬 정보 출력
    global $g5, $wr_id, $bo_table;
    // 필요한 컬럼만 뽑기
    $ph=sql_fetch("SELECT * FROM {$g5['pokemon_battle_table']} ph, {$g5['pokemon_table']} po WHERE po.po_id=ph.po_id and ph.ph_id='{$ph_id}'");

    if($buff){
        $buff=sql_fetch("SELECT SUM(a) a, SUM(b) b, SUM(c) c, SUM(d) d, SUM(m) m, SUM(e) e, SUM(f) f FROM {$g5['pokemon_battle_buff_table']} WHERE bo_table='{$bo_table}' AND ph_id='{$ph_id}' AND wr_id='{$wr_id}'");
        static $rank=[-6=>0.25,-5=>0.29,-4=>0.33,-3=>0.4,-2=>0.5,-1=>0.66,0=>1,1=>1.5,2=>2,3=>2.5,4=>3,5=>3.5,6=>4];
        foreach(['a','b','c','d'] as $k){
            $lv=intval($buff[$k]);
            $lv = max(min($lv, 6), -6);
            if(!isset($rank[$lv])) $lv=0;
            $ph[$k]=round($ph[$k]*$rank[$lv]);
        }
        $ph['e']=isset($buff['e']) ? max(min($buff['e'], 6), -6) : 0;
        $ph['m']=isset($buff['m']) ? max(min($buff['m'], 6), -6) : 0;
        $ph['f']=isset($buff['f']) ? max(min($buff['f'], 3), 0) : 0;
    }

    if($ph['fire']){
        $ph['a']=round($ph['a']/2);
    }
    if($ph['paral']){
        $ph['s']=round($ph['s']/2);
    }

    return $ph;
}
function get_type_bonus($atk, $def, $wa){//타입상성체크

    $point = 0;
    $msg = "";
    $type_bonus = $self_bonus = 1;
    $types = array_filter([$def['po_type1'],$def['po_type2']]);

    if($wa['wa_category']=='상태이상'){
        $check=[];
        if($wa['wa_type2']=="불꽃"){
            $check = ['불꽃'];
        }elseif($wa['wa_type2']=="독"){
            $check = ['독','강철'];
        }elseif($wa['wa_type2']=="전기"){
            $check = ['전기','땅'];
        }
    if (count($check) > 0 && count(array_intersect($types, $check)) > 0) {
            $type_bonus = 0;
        }
    }else{
        $in = implode("','",$types);
        $sql = "SELECT damage_per FROM {$g5['pokemon_type_table']} WHERE atk_type='{$wa['wa_type2']}' AND def_type IN('{$in}')";
        $res = sql_query($sql);
        while($row = sql_fetch_array($res)) $type_bonus *= $row['damage_per'];
    }

    // 효과 메세지 처리
    if($type_bonus<=0){$msg="{$def['po_name']}에게는 효과가 없는 것 같다...";$point--;}
    elseif($type_bonus<1){$msg="효과가 별로인 것 같다...";$point--;}
    elseif($type_bonus>1){$msg="효과는 굉장했다!";$point++;}
    if($wa['wa_type2']==$atk['po_type1']||$wa['wa_type2']==$atk['po_type2']) {
        $self_bonus = 1.2;
    }
    
    return ["type"=>$type_bonus,"self"=>$self_bonus,"point"=>$point,"msg"=>$msg];
}
/****************기술 정보 저장부**************/
function get_battle_waza($atk, $def, $type='auto') {//기술 선택

    $atk_fail_result = $def_fail_result = [
        'msg' => ['damage_log' => '기술 사용에 실패하고 말았다...'],
        'wa' => ['wa_id' => 9999, 'wa_type'=>'', 'wa_name'=>'실패', 'wa_category'=>''],
        'is_successful' => false,
        'log_text' => "기술 사용에 실패하고 말았다...\n",
        'damage' => 0,
        'point' => -1
    ];

    $atk_fail_result['ph']=$atk;
    $def_fail_result['ph']=$def;

    $atk_wa = waza_result($atk['wh_id']);
    $def_wa = waza_result($def['wh_id']);

    $atk_wa = $atk_wa['pp_now'] ? battle_pokemon_atk($atk, $def, $atk_wa, $type) : $atk_fail_result;
    $def_wa = $def_wa['pp_now'] ? battle_pokemon_atk($def, $atk, $def_wa, $type) : $def_fail_result;

    return [$atk,$atk_wa,$def,$def_wa];
}
function waza_result($wh_id) {//기술 정보
    global $g5;
    $wh_id = (int)$wh_id;
    if ($wh_id <= 0) return null;
    if ($wh_id==99999){//발버둥
        $wa=array(
            'wa_id'=>99999,
            'wh_id'=>99999,
            'wa_hit'=>100,
            'wa_power'=>50,
            'pp_now'=>9999,
            'wa_name'=>'발버둥',
            'wa_type'=>'물리',
            'wa_type2'=>'무',
            'wa_category'=>'공격'
        );
        return $wa;
    }else{
        // ★ 충돌 방지: 필요한 컬럼만 명시적으로 선택
        $sql = "
            SELECT
                wa.wa_id         AS wa_id,
                wa.wa_hit        AS wa_hit,
                wa.wa_power      AS wa_power,
                wa.wa_name       AS wa_name,
                wa.wa_type       AS wa_type,
                wa.wa_type2      AS wa_type2,
                wa.wa_category   AS wa_category,
                wh.wh_id         AS wh_id,
                wh.ph_id         AS ph_id,
                wh.pp_now        AS pp_now,
                wh.pp_max        AS pp_max
            FROM {$g5['pokemon_waza_table']} wa
            JOIN {$g5['pokemon_waza_has_table']} wh ON wa.wa_id = wh.wa_id
            WHERE wh.wh_id = '{$wh_id}'
            LIMIT 1
        ";
        return sql_fetch($sql);
    }
}
function battle_pokemon_atk($atk, $def, $wa, $type='auto') {//기술 처리 메인 함수
    global $g5, $wr_id, $bo_table;
    static $rank = [-6=>33,-5=>38,-4=>43,-3=>50,-2=>60,-1=>75,0=>100,1=>133,2=>166,3=>200,4=>233,5=>266,6=>300];
    static $cri_rank = [3=>100, 2=>50, 1=>12, 0=>6];
    static $buff_ar = ['공격'=>'a','방어'=>'b','특수공격'=>'c','특수방어'=>'d','명중률'=>'m','회피율'=>'e','급소율'=>'f'];
    $msg = [
        'waza_name'=>"{$atk['po_name']}의 {$wa['wa_name']}!",
        'waza_effect'=>"",'cri_effect'=>"",'buff_effect'=>"",'damage_log'=>""
    ];
    $log = [];
    $log[] = "[기술 사용] {$atk['po_name']} → {$wa['wa_name']} ({$wa['wa_category']}/{$wa['wa_type']}/{$wa['wa_type2']})";

    $buff = null;
    $damage = 0;
    $point = 0;
    $is_missed = false;
    $is_successful = true;
    $miss_value = 0;
    

    $miss_value = mt_rand(1,100);
    $miss_rank  = max(-6, min(6, (int)$atk['m'] - (int)$def['e']));
    $thr        = $rank[$miss_rank] ?? 100;
    $thr        = max(1, min(100, floor($thr*(int)$wa['wa_hit']/100)));
    $is_missed  = ($miss_value > $thr);

    
    if($wa['wa_category'] === '공격') {
        list($is_missed, $msg2, $damage, $point) = pokemon_skill_atk($atk, $def, $wa, $rank, $cri_rank, $is_missed);
    }
    elseif($wa['wa_category'] === '방어' || $wa['wa_category'] === '보조') {
        list($buff, $msg2, $damage, $is_missed, $point) = pokemon_skill_buff($atk, $def, $wa, $buff_ar, $is_missed);
    }else{//상태이상
        list($msg2, $is_missed, $point) = pokemon_skill_special($atk, $def, $wa, $g5, $wr_id, $bo_table, $is_missed);
    }
    $log[] = "[명중 판정] {$miss_value}, {$miss_rank}/{$thr} → ".($is_missed?"빗나감":"명중");
    $msg = array_merge($msg, $msg2);

    if($wa['wh_id']!=99999){
        //pp감소
        sql_query (" UPDATE {$g5['pokemon_waza_has_table']} set pp_now = GREATEST(0, pp_now - 1) where wh_id = '{$wa['wh_id']}' ");

    }

    return [
        'ph'=>$atk,'wa'=>$wa,'buff'=>$buff,'damage'=>$damage,'point'=>$point,
        'miss_value'=>$miss_value??null,
        'is_missed'=>$is_missed,'is_successful'=>$is_successful,
        'log_text'=>implode("\n", $log),'msg'=>$msg
    ];
}
function pokemon_skill_atk($atk, $def, $wa, $rank, $cri_rank, $is_missed) {//공격스킬
    $msg = [];
    $damage = 0;
    $point = 0;

    if ($is_missed) {
        $msg['damage_log'] = "그러나 {$atk['po_name']}의 공격은 빗나갔다!";
        $point -= 2;
        return [$is_missed, $msg, $damage, $point];
    } else {
        $return=damage_calc($atk, $def, $wa);
    }
    return [$is_missed, $return['msg'], $return['damage'], $return['point']];
}
function pokemon_skill_special($atk, $def, $wa, $g5, $wr_id, $bo_table, $is_missed) {//상태이상관련스킬
    $msg = [];
    $point = 0;
    $is_boss=false;

    if($wa['wa_category'] === '랭크초기화') {
        $is_missed=false;
        if($wa['wa_type'] === '전체') {
            $msg['buff_effect'] = "모든 포켓몬의 능력치가 원래대로 돌아갔다.";
        } elseif($wa['wa_type'] === '아군') {
            $msg['buff_effect'] = "{$atk['po_name']}의 능력치가 원래대로 돌아갔다.";
        }
    }elseif($wa['wa_category'] == '상태이상') {
        if($wa['wa_type'] == '회복') {
            $is_missed=false;
            $msg['buff_effect'] = "{$atk['po_name']}은(는) 상태이상을 회복했다.";
        }else{

            $is_doubled=false;
        
            if($wa['wa_type']=='conf'){$effect_search="and conf<=0";
            }else{$effect_search="and paral<=0 and fire<=0 and pois<=0 and slp<=0";}

            $effect_check=sql_fetch (" SELECT ph_id from {$g5['pokemon_battle_table']} where ph_id='{$def['ph_id']}' {$effect_search} limit 1");
            if(!$effect_check['ph_id']){
                $is_missed=true;
                $is_doubled=true;
            }
        
            if($is_missed){
                    if($is_doubled){
                        $msg['buff_effect'] = "그러나 {$def['po_name']}은(는) 이미 상태이상에 걸려 있다.";
                    }else{
                        $msg['buff_effect'] = "그러나 {$atk['po_name']}의 공격은 빗나갔다!";
                    }
                    $point -= 2; // 페널티
                }elseif(!$is_boss){
                    $t_bonus=get_type_bonus($atk, $def, $wa);
                    if($t_bonus['type']<=0){
                        $point = $t_bonus['point'];
                        $msg['buff_effect'] = $t_bonus['msg'];
                        $is_missed=true;
                    }else{
                        static $wa_effect=["fire"=>"화상을 입었다.", "pois"=>"몸에 독이 퍼졌다.", "paral"=>"마비되어 기술이 나오기 어려워졌다.", "conf"=>"혼란에 빠졌다."];
                        $msg['buff_effect'] = "{$def['po_name']}은(는) {$wa_effect[$wa['wa_type']]}";
                    }
                }
        
        }
    }
    return [$msg, $is_missed, $point];
}
function pokemon_skill_buff($atk, $def, $wa, $buff_ar, $is_missed) {//버프,디버프스킬
    $msg = [];
    $buff = null;
    $damage = 0;
    $point = 0; // 포인트 기본값

    if ($wa['wa_type'] === "버프") {
        $buff = ['val' => 1, 'ph' => $atk['ph_id'], 'type' => $wa['wa_type2'], 'ar' => $buff_ar[$wa['wa_type2']]];
        $buff['sql'] = ",{$buff['ar']}='{$buff['val']}'";
        $msg['buff_effect'] = "{$atk['po_name']}의 {$wa['wa_type2']}(이)가 올랐다!";
    } elseif ($wa['wa_type'] === "회복") {
        $damage = -150;
        $msg['buff_effect'] = "{$atk['po_name']}은(는) 체력을 회복했다.";
    } elseif ($wa['wa_type'] === '디버프') {
        if ($is_missed) {
            $msg['buff_effect'] = "그러나 {$atk['po_name']}의 공격은 빗나갔다!";
            $point -= 2; // 페널티
        } else {
            $buff = [
                'val' => -1,
                'ph' => $def['ph_id'],
                'type' => $wa['wa_type2'],
                'ar' => $buff_ar[$wa['wa_type2']]
            ];
            $buff['sql'] = ",{$buff['ar']}='{$buff['val']}'";
            $msg['buff_effect'] = "{$def['po_name']}의 {$wa['wa_type2']}(이)가 내려갔다!";
        }
    } elseif ($wa['wa_type'] === '반사') {
        $is_missed = false;
        $msg['buff_effect'] = "공격을 반사했다!";
    }

    return [$buff, $msg, $damage, $is_missed, $point];
}
function damage_calc($atk, $def, $wa){//대미지계산
    static $cri_rank = [3=>100, 2=>50, 1=>12, 0=>6];

    $power=$wa['wa_power'];
    if(!$power){$power=80;}
    $damage = 0;
    $point = 0;

    $type_bonus = $self_bonus = $cri_bonus = 1;
    $msg=[];

    if ($wa['wa_type2'] != '무') {
        $t_bonus=get_type_bonus($atk, $def, $wa);
        $type_bonus=$t_bonus['type'];
        $self_bonus=$t_bonus['self'];
        $point=$t_bonus['point'];
        $msg['waza_effect']=$t_bonus['msg'];
    }

    if($type_bonus>0){
        
        $cri_rand = mt_rand(1,100);
        $cri_per = $cri_rank[$atk['f']] ?? 0;

        if($cri_rand <= $cri_per) {
            $cri_bonus = 1.5;
            $msg['cri_effect'] = "급소에 맞았다!";
            $point += 2;
        }

        $atk_val = max(1, ($wa['wa_type']==='특수') ? $atk['c'] : $atk['a']);
        $def_val = max(1, ($wa['wa_type']==='특수') ? $def['d'] : $def['b']);
        $rand = mt_rand(217, 255)/255;

        $damage=((42*$power*$atk_val)/(50*max(1,(float)$def_val))+2)*$cri_bonus*$rand*$self_bonus * $type_bonus;
        $damage = max(1, floor($damage));

        if($damage > 0) $msg['damage_log'] = "{$def['po_name']}에게 {$damage} 피해를 주었다!";
        if($wa['wh_id']==99999){
            $msg['damage_log'].="<br>{$atk['po_name']}은(는) 공격의 반동 대미지를 입었다.";
        }
    }

    return ["msg"=>$msg, "point"=>$point, "damage"=>$damage];
}
/**********************전투 실행부******************/
function determine_turn_order($p1, $p2) {//속도판정
    if ($p1['s'] === $p2['s']) return (rand(1, 2) === 1) ? [$p1, $p2] : [$p2, $p1];
    return ($p1['s'] > $p2['s']) ? [$p1, $p2] : [$p2, $p1];
}
function get_battle_result($atk, $def, $atk_wa, $def_wa, $now_turn, $type='auto', $max_turn=3,$use_savepoint=false){//전투 메인 함수
    global $g5;

    if ($use_savepoint) {
        sql_query("SAVEPOINT sp_battle");
    }

    // 선공 처리 + 로그
    list($fir,$fir_wa,$sec,$sec_wa)=waza_success_result($atk,$def,$atk_wa,$def_wa,$type);
    $fir_wa['damage']=intval($fir_wa['damage']??0);
    $sec_wa['damage']=intval($sec_wa['damage']??0);

    $fir_target=null;$fir_dmg=0;
    if($fir_wa['damage']>0&&$fir_wa['wa']['wa_name']!='혼란'){
        $fir_target=&$sec;$fir_dmg=$fir_wa['damage'];
        if($fir_wa['wa']['wh_id']==99999){//발버둥
            $fir['hp_now']=max(0,min($fir['hp_max'],$fir['hp_now']-floor($fir['hp_max']/4)));
        }
    }
    else if($fir_wa['damage']<0||$fir_wa['wa']['wa_name']=='혼란'){$fir_target=&$fir;$fir_dmg=$fir_wa['damage'];}
    if($fir_target){
        $fir_target['hp_now']=max(0,min($fir_target['hp_max'],$fir_target['hp_now']-$fir_dmg));
    }

    $result=judge_battle_result($fir,$sec,$type);

    $fir_log_res=insert_battle_log(
        $now_turn,1,
        ['ph'=>$fir,'wa'=>$fir_wa['wa'],'point'=>$fir_wa['point']??0,'damage'=>$fir_wa['damage'],'msg'=>$fir_wa['msg']??'','log_text'=>$fir_wa['log_text']??''],
        ['ph'=>$sec,'wa'=>$sec_wa['wa'],'msg'=>$sec_wa['msg']??'','log_text'=>$sec_wa['log_text']??''],
        '','',$type
    );
    if(!$fir_log_res){
        if($use_savepoint) sql_query("ROLLBACK TO SAVEPOINT sp_battle");
        return false;
    }

    // 후공 처리 + 로그
    $sec_log_res=null;
    if(!$result){
        $sec_target=null;$sec_dmg=0;
        if($sec_wa['damage']>0&&$sec_wa['wa']['wa_name']!='혼란'){
            $sec_target=&$fir;$sec_dmg=$sec_wa['damage'];
            if($sec_wa['wa']['wh_id']==99999){//발버둥
                $sec['hp_now']=max(0,min($sec['hp_max'],$sec['hp_now']-floor($sec['hp_max']/4)));
            }
        }
        elseif($sec_wa['damage']<0||$sec_wa['wa']['wa_name']=='혼란'){$sec_target=&$sec;$sec_dmg=$sec_wa['damage'];}
        if($sec_target){
        $sec_target['hp_now']=max(0,min($sec_target['hp_max'],$sec_target['hp_now']-$sec_dmg));
        }
        $result=judge_battle_result($fir,$sec,$type);
        if(!$result&&$now_turn>=$max_turn&&$max_turn>0){
            $result=['end_type' => 't', 'win' => $fir, 'lose' => $sec];
        }
        $sec_log_res=insert_battle_log(
            $now_turn,2,
            ['ph'=>$sec,'wa'=>$sec_wa['wa'],'point'=>$sec_wa['point']??0,'damage'=>$sec_wa['damage'],'msg'=>$sec_wa['msg']??'','log_text'=>$sec_wa['log_text']??''],
            ['ph'=>$fir,'wa'=>$fir_wa['wa'],'msg'=>$fir_wa['msg']??'','log_text'=>$fir_wa['log_text']??''],
            '','',$type
        );
        if(!$sec_log_res){
            if($use_savepoint) sql_query("ROLLBACK TO SAVEPOINT sp_battle");
            return false;
        }
    }

    // 실제 HP 반영
    $fir_hp=$fir['hp_now'];
    $sec_hp=$sec['hp_now'];
    $q1="UPDATE {$g5['pokemon_battle_table']} SET hp_now='{$fir_hp}' WHERE ph_id='{$fir['ph_id']}'";
    $q2="UPDATE {$g5['pokemon_battle_table']} SET hp_now='{$sec_hp}' WHERE ph_id='{$sec['ph_id']}'";
    if(!sql_query($q1)||!sql_query($q2)){
        if($use_savepoint) sql_query("ROLLBACK TO SAVEPOINT sp_battle");
        return false;
    }

    // 종료/DoT 처리
    if($result){
        // 승패/무승부 처리
        if(!battle_end_result($result['end_type'],$result['win'],$result['lose'],$type,$max_turn,$now_turn)){
            if($use_savepoint) sql_query("ROLLBACK TO SAVEPOINT sp_battle");
            return false;
        }
    }else{
        if(!set_dot_damage($fir,$sec,$now_turn,$type,$max_turn)){
            if($use_savepoint) sql_query("ROLLBACK TO SAVEPOINT sp_battle");
            return false;
        }
    }

    if($use_savepoint){
        sql_query("RELEASE SAVEPOINT sp_battle");
    }
    return true;
}
function pokemon_skill_special_act($atk, $def, $wa, $g5, $wr_id, $bo_table, $type, $buff=[]) {//버프, 상태이상 실제 처리부
    static $wa_effect=["fire"=>"화상을 입었다.", "pois"=>"몸에 독이 퍼졌다.", "paral"=>"마비되어 기술이 나오기 어려워졌다.", "conf"=>"혼란에 빠졌다."];
    $msg='';
    if(isset($buff['val'])) {//버프,디버프시 처리
        if($buff['ph']=='all'){
            $col = $buff['ar'];  
            $allowed = ['a','b','c','d','m','e','f'];
            if (!in_array($col, $allowed, true)) return false;
            $sql = "
                INSERT INTO {$g5['pokemon_battle_buff_table']} (wr_id, bo_table, ph_id, battle_type, `{$col}`)
                SELECT '{$wr_id}', '{$bo_table}', ph.ph_id, '{$type}', {$buff['val']}
                FROM {$g5['pokemon_battle_table']} ph
                WHERE ph.ph_id != 1
                AND ph.hp_now > 0
                ";
            sql_query($sql);
        }else{
            sql_query("INSERT INTO {$g5['pokemon_battle_buff_table']} SET wr_id='{$wr_id}',bo_table='{$bo_table}',ph_id='{$buff['ph']}', battle_type='{$type}' {$buff['sql']} ");
        }
    }elseif($wa['wa_category'] === '랭크초기화') {
        if($wa['wa_type'] === '전체') {
            if($type=='raid') {
                sql_query ("DELETE FROM {$g5['pokemon_battle_buff_table']} WHERE battle_type='raid' and inf=0");
            } else {
                sql_query ("DELETE FROM {$g5['pokemon_battle_buff_table']} WHERE wr_id='{$wr_id}' AND bo_table='{$bo_table}' ");
            }
        } elseif($wa['wa_type'] === '아군') {
            if($type=='raid') {
                sql_query ("DELETE FROM {$g5['pokemon_battle_buff_table']} WHERE battle_type='raid' AND ph_id='{$atk['ph_id']}'");
            } else {
                sql_query ("DELETE FROM {$g5['pokemon_battle_buff_table']} WHERE wr_id='{$wr_id}' AND bo_table='{$bo_table}' AND ph_id='{$atk['ph_id']}'");
            }
        }
    }elseif($wa['wa_category'] == '상태이상') {
        if($wa['wa_type'] == '회복') {
            sql_query ("UPDATE {$g5['pokemon_battle_table']} SET pois = 0, fire = 0, conf = 0, paral = 0, slp = 0  WHERE ph_id='{$atk['ph_id']}'");
        }else{
            $wa_type_list = ['fire','pois','conf','slp','paral'];
            if(!in_array($wa['wa_type'], $wa_type_list, true)) return '';
        
            if($wa['wa_type']=="pois"||$wa['wa_type']=="fire"){
                $sql_set="{$wa['wa_type']}=16";
            }elseif($wa['wa_type']=="conf"){
                $sql_set="{$wa['wa_type']}=FLOOR(RAND()*4+1)";
            }elseif($wa['wa_type']=="slp"){
                $sql_set="{$wa['wa_type']}=FLOOR(RAND()*3+1)";
            }else{
                $sql_set="{$wa['wa_type']}=999";
            }
            
            sql_query("UPDATE {$g5['pokemon_battle_table']} SET {$sql_set} WHERE ph_id='{$def['ph_id']}'");
        }
    }
    return  $msg;
}
function waza_success_result($atk,$def,$atk_wa,$def_wa,$type){//상태이상적용,방어,반사 등 기술 성공 판정
    global $g5, $wr_id, $bo_table;
    $fir_wa = $atk_wa;
    $sec_wa = $def_wa;
    $fir = $atk;
    $sec = $def;

    if (($atk_wa['wa']['wa_type'] === '반사' && $def_wa['wa']['wa_type'] !== '반사')
        ||($atk_wa['wa']['wa_type'] !== '실드' && $def_wa['wa']['wa_type'] === '실드')) {
        list($fir_wa, $sec_wa) = [$def_wa, $atk_wa];
        list($fir, $sec) = [$def, $atk];
    }

    
    $new_ph=sql_fetch (" SELECT * from {$g5['pokemon_battle_table']} where ph_id='{$fir['ph_id']}' ");
    $new_wa=debuff_check($new_ph);
    $fir_suc=$new_wa['attack_suc'];

    
    if($fir_suc){
        if($new_wa['suc_msg']){$fir_wa['msg']['waza_name']=$new_wa['suc_msg'].'<br>'.$fir_wa['msg']['waza_name'];}
        if( ((!empty($fir_wa['buff']['val'])) || ($fir_wa['wa']['wa_category']=="랭크초기화") || ($fir_wa['wa']['wa_category']=="상태이상")) && !$fir_wa['is_missed']) {
            $effect=pokemon_skill_special_act($fir, $sec, $fir_wa['wa'], $g5, $wr_id, $bo_table, $type, $fir_wa['buff']);
            if($effect){$fir_wa['msg']['buff_effect']=$effect;}
        }
    }else{
        $fir_wa=array_replace($fir_wa, $new_wa);
    }


    $fir_type = $fir_wa['wa']['wa_type'] ?? '';
    $sec_name = $sec_wa['wa']['wa_name'] ?? '';
    if($fir_suc){
        if ($fir_wa['is_successful'] && $sec_wa['wa']['wa_category'] !== '방어' && $fir_type === '실드') {
            $sec_wa['is_successful'] = false;
            $sec_wa['log_text'] .= "[실패] 방어당함\n";
            $sec_wa['damage'] = 0;
            $sec_wa['point']++ ;
            $sec_wa['msg']['damage_log'] = "{$fir['po_name']}은(는) 공격으로부터 몸을 지켰다!";
        }
    }


    $new_ph=sql_fetch (" SELECT * from {$g5['pokemon_battle_table']} where ph_id='{$sec['ph_id']}' ");
    $new_wa=debuff_check($new_ph);
    $sec_suc=$new_wa['attack_suc'];

    
    if($sec_suc){
        if($new_wa['suc_msg']){$sec_wa['msg']['waza_name']=$new_wa['suc_msg'].'<br>'.$sec_wa['msg']['waza_name'];}
        if( ((!empty($sec_wa['buff']['val'])) || ($sec_wa['wa']['wa_category']=="랭크초기화") || ($sec_wa['wa']['wa_category']=="상태이상")) && !$sec_wa['is_missed']) {
            $effect=pokemon_skill_special_act($sec, $fir, $sec_wa['wa'], $g5, $wr_id, $bo_table, $type, $sec_wa['buff']);
            if($effect){$sec_wa['msg']['buff_effect']=$effect;}
        }
    }else{
        $sec_wa=array_replace($sec_wa, $new_wa);
    }

    if($sec_suc){
        if (($fir_type !== '특수' && $sec_name === '미러코트') || ($fir_type !== '물리' && $sec_name === '카운터')) {
            $sec_wa['is_successful'] = false;
            $sec_wa['log_text'] .= "[실패] 반사 실패\n";
            $sec_wa['msg']['buff_effect'] = "그러나 실패하고 말았다!";
            if ($fir_wa['wa']['wa_type'] === '반사') {
                $fir_wa['is_successful'] = false;
                $fir_wa['log_text'] .= "[실패] 반사 실패\n";
                $fir_wa['msg']['buff_effect'] = "그러나 실패하고 말았다!";
            }
        } elseif (($fir_type === '특수' && $sec_name === '미러코트') || ($fir_type === '물리' && $sec_name === '카운터')) {
            $sec_wa['damage'] = $fir_wa['damage'] * 2;
            $sec_wa['point']++ ;
            $sec_wa['msg']['damage_log'] = "{$fir['po_name']}에게 {$sec_wa['damage']} 피해를 주었다!";
        }
    }
    return [$fir, $fir_wa, $sec, $sec_wa];
} 
function debuff_check($ph) {//상태이상시 성공판정
    global $g5;

    $damage = 0;
    $attack_suc = true;
    $msg = "";
    $suc_msg=[];

    // 수면 체크
    if ($ph['slp']) {
        if (mt_rand(1, 2) == 1) {
            $msg = "{$ph['po_name']}(은)는 쿨쿨 자고 있다.";
            return [
                'msg' => ['damage_log' => $msg],
                'wa' => ['wa_id' => 9999, 'wa_type' => '', 'wa_name' => '실패', 'wa_category' => ''],
                'is_successful' => false,
                'log_text' => "{$msg}\n",
                'damage' => 0,
                'point' => -1,
                'attack_suc' => false
            ];
        } else {
            $suc_msg[] = "{$ph['po_name']}(은)는 눈을 떴다!";
            sql_query("UPDATE {$g5['pokemon_battle_table']} SET slp=0 WHERE ph_id='{$ph['ph_id']}'");
        }
    }

    // 혼란 체크
    if ($ph['conf']) {
        if (mt_rand(1, 2) == 1) {
            $msg = "{$ph['po_name']}(은)는 혼란에 빠져 있다!<br>영문도 모른 채 자기를 공격했다!";

            $atk_val=$ph['a'];
            $def_val=$ph['b'];    
            $power=20;
            $k1 = 40;
            $max_linear = 100; // 100~110 내외에서 맞추면 최대치 약 300 근처
            $expo = 1.7;
            $c = 1;
            $diff = $atk_val - $def_val;
            if ($diff <= 0) {
                $linear = 0;
            } elseif ($diff <= 70) {
                $linear = ($diff / 70) * 60;
            } else {
                $curve = pow(($diff - 70) / 70, $expo);
                $linear = 60 + $max_linear * $curve;
            }

            $sqrt_term = $k1 * sqrt($atk_val / $def_val) * ($power / 50);
            $damage = $sqrt_term + $linear + $c;

            $rand = mt_rand(85, 100) / 100;
            $damage = round($damage*$rand);

            $damage = max(1, $damage);
            
            return [
                'msg' => ['damage_log' => $msg],
                'wa' => ['wa_id' => 9999, 'wa_type' => '', 'wa_name' => '혼란', 'wa_category' => ''],
                'is_successful' => false,
                'log_text' => "{$msg}\n",
                'damage' => $damage,
                'point' => -1,
                'attack_suc' => false
            ];

        } else {
            $suc_msg[]= "{$ph['po_name']}(은)는 혼란에 빠져 있다!";
        }
    }

    // 마비 체크
    if ($ph['paral']) {
        if (mt_rand(1, 4) == 1) {
            $msg = "{$ph['po_name']}(은)는 몸이 저려 움직일 수 없다.";
            return [
                'msg' => ['damage_log' => $msg],
                'wa' => ['wa_id' => 9999, 'wa_type' => '', 'wa_name' => '실패', 'wa_category' => ''],
                'is_successful' => false,
                'log_text' => "{$msg}\n",
                'damage' => 0,
                'point' => -1,
                'attack_suc' => false
            ];
        }
    }

    // 이상 없거나, 위에서 $attack_suc true 처리
    $suc_msg_final=implode("<br>",$suc_msg);

    return [
        'attack_suc' => $attack_suc,
        'suc_msg' => $suc_msg_final
    ];
}
/*********************전투 결과 처리부**************************/
function judge_battle_result($fir, $sec, $type = 'auto') {//1, 2차 행동불능 판정
    // 타입에 따라 비교 필드 결정

    if ($sec['hp_now'] <= 0) return ['end_type' => 'd', 'win' => $fir, 'lose' => $sec];
    if ($fir['hp_now'] <= 0) return ['end_type' => 'd', 'win' => $sec, 'lose' => $fir];

    return null;
}
function battle_end_result($end_type, $win, $lose, $type = 'auto', $max_turn=3, $turn='') {//전투 결과 출력
    global $g5, $bo_table, $wr_id;

    $end_msg = $end_debug = '';
    if($type!='raid'){$turn = ($end_type == 'd') ? 998 : 999;}
    $draw = false;
    $fir_id = $win['ph_id'];
    $sec_id = $lose['ph_id'];
    $fir_hp = (int)($win['hp_now'] ?? 0);
    $sec_hp = (int)($lose['hp_now'] ?? 0);

    if($max_turn>0){
        if ($end_type == 't') {
            $sql = "
                SELECT ph_id, SUM(lo_point) AS teq,
                    MAX(wa_category='공격')+MAX(wa_category='방어')+MAX(wa_category='보조') AS waza
                FROM {$g5['pokemon_battle_log_table']}
                WHERE wr_id='{$wr_id}' AND bo_table='{$bo_table}' AND (ph_id='{$fir_id}' OR ph_id='{$sec_id}')
                GROUP BY ph_id
            ";
            $res = sql_query($sql);
            $point = [];
            while($row = sql_fetch_array($res)) {
                $point[$row['ph_id']] = [
                    'teq' => (int)$row['teq'],
                    'waza' => (int)$row['waza']
                ];
            }
            $f_teq = $point[$fir_id]['teq'] ?? 0; $s_teq = $point[$sec_id]['teq'] ?? 0;
            $f_waza = $point[$fir_id]['waza'] ?? 0; $s_waza = $point[$sec_id]['waza'] ?? 0;
            $fir_scores = [
                'waza' => ($f_waza > $s_waza) ? 2 : (($f_waza < $s_waza) ? 0 : 1),
                'teq'  => ($f_teq > $s_teq)   ? 2 : (($f_teq < $s_teq)   ? 0 : 1),
                'hp'   => ($fir_hp > $sec_hp) ? 2 : (($fir_hp < $sec_hp) ? 0 : 1)
            ];
            $sec_scores = [
                'waza' => ($f_waza < $s_waza) ? 2 : (($f_waza > $s_waza) ? 0 : 1),
                'teq'  => ($f_teq < $s_teq)   ? 2 : (($f_teq > $s_teq)   ? 0 : 1),
                'hp'   => ($fir_hp < $sec_hp) ? 2 : (($fir_hp > $sec_hp) ? 0 : 1)
            ];
            $fir_total = array_sum($fir_scores);
            $sec_total = array_sum($sec_scores);

            $end_debug = "{$f_waza}|{$f_teq}|{$fir_hp}|{$fir_scores['waza']}|{$fir_scores['teq']}|{$fir_scores['hp']}!!"
                    . "{$s_waza}|{$s_teq}|{$sec_hp}|{$sec_scores['waza']}|{$sec_scores['teq']}|{$sec_scores['hp']}";
            if ($fir_total > $sec_total) {
                $winner = $win; $loser = $lose; $draw = false;
            } elseif ($fir_total < $sec_total) {
                $winner = $lose; $loser = $win; $draw = false;
                $end_debug = "{$s_waza}|{$s_teq}|{$sec_hp}|{$sec_scores['waza']}|{$sec_scores['teq']}|{$sec_scores['hp']}!!"
                            ."{$f_waza}|{$f_teq}|{$fir_hp}|{$fir_scores['waza']}|{$fir_scores['teq']}|{$fir_scores['hp']}";
            } else {
                $winner = $win; $loser = $lose; $draw = true;
            }
            $end_msg = $draw
                ? "무승부!"
                : (($winner['ph_name'] ?? $winner['po_name'] ?? "승자") . " 승리!");
        } else if ($end_type == 'd') {
            $winner = $win; $loser = $lose;
            $end_msg =
                ($lose['po_name'] ?? "패자") . " 행동불능!<br>" .
                ($win['po_name'] ?? "승자") . " 승리!";
        }
        $win['ph'] = $winner;
        $lose['ph'] = $loser;
    }else{
        $winner=$win;$loser=$lose;  
        $end_msg =($lose['po_name'] ?? "패자") . " 행동불능!<br>";        
        if($end_type=='t'){$end_msg .=($win['po_name'] ?? "패자") . " 행동불능!<br>무승부!";}else{$end_msg .=($win['po_name'] ?? "승자") . " 승리!";}
        $win['ph'] = $winner;
        $lose['ph'] = $loser;
    }
    sql_query("UPDATE {$g5['pokemon_battle_table']} SET is_battle=0, conf=0, paral=0, pois=0, slp=0, fire=0, battle_hp_now=hp_max, hp_now=hp_max WHERE ph_id IN ('{$loser['ph_id']}', '{$winner['ph_id']}')");
    sql_query("UPDATE {$g5['pokemon_waza_has_table']} SET pp_now=pp_max WHERE ph_id IN ('{$loser['ph_id']}', '{$winner['ph_id']}')");
    
    insert_battle_log($turn, 4, $win, $lose, $end_msg, $end_debug, $type);
    return true;
}
function set_dot_damage($atk,$def,$turn,$type,$max_turn=0){ // order=3 고정
    global $g5;
    static $dot_list=["conf","paral","pois","fire","slp"];

    // 최신 상태만 갱신해서 계산
    $new_atk=sql_fetch("SELECT conf,paral,pois,fire,slp FROM {$g5['pokemon_battle_table']} WHERE ph_id='{$atk['ph_id']}'");
    $new_def=sql_fetch("SELECT conf,paral,pois,fire,slp FROM {$g5['pokemon_battle_table']} WHERE ph_id='{$def['ph_id']}'");
    $atk=array_replace($atk,$new_atk); $def=array_replace($def,$new_def);
    $hp_ar = 'hp_now';
    $targets=[['ph'=>$atk,'dot'=>[],'dmg'=>0,'msg'=>''],['ph'=>$def,'dot'=>[],'dmg'=>0,'msg'=>'']];
    foreach($targets as $i=>&$t){
        foreach($dot_list as $k){
            if(!$t['ph'][$k]) continue;
            $t['dot'][]="$k=$k-1";
            if($k==='fire'||$k==='pois'||$k==='slp'){
                if($k==='pois'){ $lev=17-$t['ph'][$k]; if($lev>=15)$lev=15; $damage=round($t['ph']['hp_max']/16*$lev);
                }else{$damage=round($t['ph']['hp_max']/16);}

                $t['dmg']+=$damage;
                $label=($k==='fire'?'화상':($k==='pois'?'독':'악몽'));
                $t['msg'].="{$t['ph']['po_name']}(은)는 {$label}의 대미지를 받고 있다.<br>";
            }
        }
        if($t['dmg']>0){
            $min=0;
            $new_hp=max($min,$t['ph'][$hp_ar]-$t['dmg']);
            $t['ph'][$hp_ar]=$new_hp;
            $t['dot'][]="{$hp_ar}={$new_hp}";
            sql_query("UPDATE {$g5['pokemon_battle_table']} SET ".implode(',',$t['dot'])." WHERE ph_id='{$t['ph']['ph_id']}'");
        }
    } unset($t);

    if($targets[0]['msg']||$targets[1]['msg']){
        $msg=$targets[0]['msg'].$targets[1]['msg'];

        $atk['ph']=$targets[0]['ph']; $def['ph']=$targets[1]['ph'];
        insert_battle_log($turn,3,$atk,$def,$msg,'',$type);

        // DoT로 종료 시 즉시 턴엔드
        if($atk['ph'][$hp_ar]<=0 || $def['ph'][$hp_ar]<=0){
            if($atk['ph'][$hp_ar]<=0 && $def['ph'][$hp_ar]<=0){
                $end_type='t';
                $win  = $atk['ph'];
                $lose = $def['ph'];
            }else{
                if($atk['ph'][$hp_ar]<=0){
                    $lose=$atk['ph']; $win=$def['ph'];
                }else{
                    $win=$atk['ph']; $lose=$def['ph'];
                }
                $end_type='d';
            }
            battle_end_result($end_type,$win,$lose,$type,$max_turn,$turn);
        }
    }
    return true;
}
function insert_battle_log($turn,$order,$atk=[],$def=[],$end_msg='',$end_debug='',$type='auto'){
    global $g5,$bo_table,$wr_id;

    // 필수값 방어
    $wr_id    = (int)$wr_id;
    $bo_table = trim((string)$bo_table);
    $type     = trim((string)$type);
    $turn     = (int)$turn;
    $order    = (int)$order;

    $ph_id     = (int)($atk['ph']['ph_id'] ?? 0);
    $re_ph_id  = (int)($def['ph']['ph_id'] ?? 0);
    $wa_category = trim((string)($atk['wa']['wa_category'] ?? ''));
    if(!$wa_category){$wa_category='none';}
    $lo_point  = (int)($atk['point'] ?? 0);
    $lo_dmg    = (int)($atk['damage'] ?? 0);
    $ph_hp_now = (int)($atk['ph']['hp_now'] ?? 0);
    $re_hp_now = (int)($def['ph']['hp_now'] ?? 0);
    $ch_id     = (int)($atk['ph']['ch_id'] ?? 0);

    $lo_msg   = $end_msg ?: (is_array($atk['msg']) ? implode('<br>', array_filter($atk['msg'])) : (string)($atk['msg'] ?? ''));
    $lo_debug = $end_debug ?: (string)($def['log_text'] ?? ($atk['log_text'] ?? ''));

    $lo_msg_esc   = addslashes($lo_msg);
    $lo_debug_esc = addslashes($lo_debug);
    $bo_table_esc = addslashes($bo_table);
    $type_esc     = addslashes($type);
    $wa_cat_esc   = addslashes($wa_category);

    $sql = "
        INSERT INTO {$g5['pokemon_battle_log_table']}
        (wr_id, bo_table, battle_type, lo_turn, lo_order,
        ph_id, re_ph_id, ch_id, wa_category, lo_point,
        lo_msg, lo_debug, lo_dmg, ph_hp_now, re_hp_now)
        VALUES
        ('{$wr_id}','{$bo_table_esc}','{$type_esc}','{$turn}','{$order}',
        '{$ph_id}','{$re_ph_id}','{$ch_id}','{$wa_cat_esc}','{$lo_point}',
        '{$lo_msg_esc}','{$lo_debug_esc}','{$lo_dmg}','{$ph_hp_now}','{$re_hp_now}')
    ";
    $ok = sql_query($sql, false);
    if(!$ok){
        return 0;
    }
    return (int)sql_insert_id(); 
}
/**********기타************** */
function claim_next_turn($bo_table, $wr_id, $battle_type){//턴 배정
    global $g5;
    $bo   = addslashes($bo_table);
    $wr   = (int)$wr_id;
    $type = addslashes($battle_type);

    $sql = "INSERT INTO {$g5['pokemon_battle_state_table']} (bo_table, wr_id, battle_type, next_turn)
            VALUES ('{$bo}', '{$wr}', '{$type}', 0)
            ON DUPLICATE KEY UPDATE next_turn=next_turn";
    sql_query($sql, false);

    $ok = sql_query("UPDATE {$g5['pokemon_battle_state_table']}
                        SET next_turn = LAST_INSERT_ID(next_turn+1)
                    WHERE bo_table='{$bo}' AND wr_id='{$wr}' AND battle_type='{$type}'", false);
    if(!$ok) return 0;

    $row = sql_fetch("SELECT LAST_INSERT_ID() AS turn ");
    return (int)($row['turn'] ?? 0);
}
function insert_event_log(
    $event_id, $lo_content, $lo_msg, $lo_type,
    $lo_1='', $lo_2='', $lo_3='', $lo_4='', $lo_5='', $lo_6='', $lo_7='', $lo_8='', $lo_9='', $lo_10='',
    $rel_id=''
){
    global $g5, $character, $bo_table, $wr_id;

    $ch_id   = (int)$character['ch_id'];
    $wr_id_i = (int)$wr_id;
    $event_i = (int)$event_id;
    $rel_i   = ($rel_id === '' ? 'NULL' : (int)$rel_id);

    $lo_msg   = mb_substr((string)$lo_msg, 0, 255, 'UTF-8');

    $esc = static function($v){ return sql_real_escape_string((string)$v); };

    $time     = G5_TIME_YMDHIS;
    $lo_type  = $esc($lo_type);
    $lo_cont  = $esc($lo_content);
    $lo_msg_e = $esc($lo_msg);
    $bo_table = $esc($bo_table);
    $time_e   = $esc($time);

    $lo_1e = $esc($lo_1);
    $lo_2e = $esc($lo_2);
    $lo_3e = $esc($lo_3);
    $lo_4e = $esc($lo_4);
    $lo_5e = $esc($lo_5);
    $lo_6e = $esc($lo_6);
    $lo_7e = $esc($lo_7);
    $lo_8e = $esc($lo_8);
    $lo_9e = $esc($lo_9);
    $lo_10e= $esc($lo_10);

    $ma_id_e = $lo_1e;
    $me_id_e = $lo_2e;

    $sql = "
        INSERT INTO {$g5['pokemon_event_log_table']}
        SET
            ch_id       = {$ch_id},
            event_id    = {$event_i},
            ma_id       = '{$ma_id_e}',
            me_id       = '{$me_id_e}',
            lo_type     = '{$lo_type}',
            lo_content  = '{$lo_cont}',
            lo_msg      = '{$lo_msg_e}',
            lo_datetime = '{$time_e}',
            wr_id       = {$wr_id_i},
            bo_table    = '{$bo_table}',
            lo_1        = '{$lo_1e}',
            lo_2        = '{$lo_2e}',
            lo_3        = '{$lo_3e}',
            lo_4        = '{$lo_4e}',
            lo_5        = '{$lo_5e}',
            lo_6        = '{$lo_6e}',
            lo_7        = '{$lo_7e}',
            lo_8        = '{$lo_8e}',
            lo_9        = '{$lo_9e}',
            lo_10       = '{$lo_10e}',
            rel_id      = {$rel_i}
    ";
    $ok = sql_query($sql, false); 

    if ($ok === false) {
        return 0;
    }

    return (int)sql_insert_id();
}


?>
