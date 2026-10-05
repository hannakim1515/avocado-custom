<?php
if (!defined('_GNUBOARD_')) exit;
function da_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function da_monster_auth($action) {
    global $auth,$is_admin;
    if($is_admin==='super')return;
    foreach(array('730150','980700','980401','981401','982401') as $menu)if(strpos($auth[$menu]??'', $action)!==false)return;
    auth_check($auth['730150']??'', $action);
}
function da_dungeon_regions($selected=array()) {
    global $g5;
    static $catalog=null;
    if($catalog===null) {
        $catalog=array();
        $result=sql_query('SELECT ma_id,ma_name,ma_use,ma_use_dungeon FROM `'.$g5['map_table'].'` ORDER BY ma_parent,ma_id');
        while($row=sql_fetch_array($result)) $catalog[(int)$row['ma_id']]=$row;
    }
    $regions=array();
    foreach($catalog as $id=>$row) if($row['ma_use'] && $row['ma_use_dungeon']) $regions[$id]=array('id'=>$id,'name'=>$row['ma_name']);
    foreach(array_filter(array_map('intval',$selected)) as $id) if(!isset($regions[$id])) {
        $regions[$id]=array('id'=>$id,'name'=>isset($catalog[$id])?$catalog[$id]['ma_name'].' (현재 출현 비활성)':'기존 지역 (삭제됨)');
    }
    return array_values($regions);
}
function da_select($name, $options, $value, $empty='사용 안함') {
    if ($empty!==null && (string)$value==='0') $value='';
    echo '<select id="'.da_h($name).'" name="'.da_h($name).'">';
    if ($empty!==null) echo '<option value="">'.da_h($empty).'</option>';
    if ((string)$value!=='' && !array_key_exists((string)$value,$options) && !array_key_exists($value,$options)) $options[(string)$value]='기존 설정 (현재 목록에 없음)';
    foreach($options as $id=>$label) echo '<option value="'.da_h($id).'"'.((string)$id===(string)$value?' selected':'').'>'.da_h($label).'</option>';
    echo '</select>';
}
function da_options() {
    global $g5;
    $options=array('stats'=>array(),'codes'=>array(),'items'=>array(),'monsters'=>array(),'statuses'=>array());
    foreach(monster_rows('SELECT st_id,st_name FROM `'.$g5['status_config_table'].'` ORDER BY st_id') as $r)$options['stats'][$r['st_id']]=$r['st_name'];
    foreach(monster_rows('SELECT ex_name FROM `'.$g5['status_extra_table'].'` ORDER BY ex_id') as $r)$options['codes'][$r['ex_name']]=$r['ex_name'];
    foreach(monster_rows('SELECT it_id,it_name FROM `'.$g5['item_table'].'` ORDER BY it_id') as $r)$options['items'][$r['it_id']]=$r['it_name'];
    if(monster_ready())foreach(monster_rows('SELECT monster_id,name FROM `'.monster_table('monster_master').'` WHERE profile IS NOT NULL ORDER BY monster_id') as $r)$options['monsters'][$r['monster_id']]=$r['name'];
    if(function_exists('maze_table') && sql_fetch('SHOW TABLES LIKE '.monster_quote(maze_table('status')),false))foreach(monster_rows('SELECT status_id,name FROM `'.maze_table('status').'` ORDER BY status_id') as $r)$options['statuses'][$r['status_id']]=$r['name'];
    return $options;
}
function da_json_editor($name,$kind,$value) {
    echo '<div class="dungeon-json-editor" data-kind="'.da_h($kind).'"><div class="dungeon-editor-rows"></div><div class="btn_list01 btn_list"><button type="button" class="btn btn_02" data-add>항목 추가</button></div><input type="hidden" data-editor-value name="'.da_h($name).'" value="'.da_h(json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'"></div>';
}
function da_row($label,$content) { echo '<tr><th scope="row">'.da_h($label).'</th><td>'.$content.'</td></tr>'; }
function da_nav() { echo '<ul class="anchor"><li><a href="monster.php">몬스터 목록</a></li><li><a href="monster_skill.php">몬스터 스킬 목록</a></li><li><a href="dungeon_list.php">던전 관리</a></li><li><a href="dungeon_log.php">전투 기록</a></li></ul>'; }

/** Validate the shared monster selection before changing the dungeon row. */
function da_pool_validate($input) {
    if (!monster_ready()) throw new RuntimeException('시스템 설치 / 업데이트에서 몬스터 관리를 적용하세요.');
    if (!is_array($input) || !is_array($input['pool']??array())) throw new RuntimeException('몬스터 출현 설정을 다시 입력하세요.');
    $rows=array();
    foreach (($input['pool']??array()) as $id=>$entry) {
        if (!is_array($entry)) throw new RuntimeException('몬스터 출현 설정을 다시 입력하세요.');
        if (empty($entry['enabled'])) continue;
        $id=filter_var($id,FILTER_VALIDATE_INT);
        if ($id===false || $id<1) throw new RuntimeException('몬스터를 다시 선택하세요.');
        monster_profile($id);
        $rows[]=array(
            'monster_id'=>$id,
            'weight'=>filter_var($entry['weight']??null,FILTER_VALIDATE_INT),
            'min_count'=>filter_var($entry['min_count']??1,FILTER_VALIDATE_INT),
            'max_count'=>filter_var($entry['max_count']??1,FILTER_VALIDATE_INT),
            'is_boss'=>!empty($entry['boss'])?1:0
        );
    }
    foreach($rows as $row) if($row['weight']===false || $row['weight']<1 || $row['weight']>1000000) throw new RuntimeException('출현 가중치는 1~1,000,000 사이의 정수로 입력하세요.');
    foreach($rows as $row) if($row['min_count']===false || $row['max_count']===false || $row['min_count']<1 || $row['max_count']>20 || $row['min_count']>$row['max_count']) throw new RuntimeException('동시 출현 수는 최소 1~최대 20마리 범위로 입력하고, 최대값은 최소값 이상이어야 합니다.');
    return array('rows'=>$rows);
}

function da_pool_query($sql) {
    if (sql_query($sql,false) === false) throw new RuntimeException('몬스터 출현 설정을 저장할 수 없습니다.');
}
function da_pool_save($dg_id,$selection,$transaction=true) {
    $dg_id=(int)$dg_id;
    if($dg_id<1)throw new RuntimeException('던전을 먼저 저장하세요.');
    $pool='`'.monster_table('dungeon_monster_pool').'`';
    if($transaction)da_pool_query('START TRANSACTION');
    try {
        da_pool_query('DELETE FROM '.$pool.' WHERE dg_id='.$dg_id);
        if($selection!==null) {
            $ids=array();
            foreach($selection['rows'] as $row) {
                $id=(int)$row['monster_id'];$ids[]=$id;
                da_pool_query('INSERT INTO '.$pool.' (dg_id,monster_id,weight,enabled,is_boss,min_count,max_count) VALUES ('.$dg_id.','.$id.','.(int)$row['weight'].',1,'.(int)$row['is_boss'].','.(int)($row['min_count']??1).','.(int)($row['max_count']??1).')');
            }
        }
        if($transaction)da_pool_query('COMMIT');
    } catch(Throwable $error) {
        if($transaction)sql_query('ROLLBACK',false);
        throw $error;
    }
}
