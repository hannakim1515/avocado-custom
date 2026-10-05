<?php
if (!defined('_GNUBOARD_')) exit;
require_once G5_PATH.'/lib/item_effect.lib.php';
function monster_status_table() { return G5_TABLE_PREFIX.'dungeon_maze_status'; }
/** Keep the existing component JSON and status identifiers. */
function monster_status_save($id, $input) {
    global $g5;
    $components=json_decode(stripslashes($input['components']??''),true);
    $duration=filter_var($input['duration']??'',FILTER_VALIDATE_INT);
    $name=trim(stripslashes($input['name']??''));
    if (!is_array($components) || !$components || $name==='' || $duration===false || $duration<1)
        throw new RuntimeException('상태 이름·지속 턴·효과가 필요합니다.');
    if ($id && !sql_fetch('SELECT status_id FROM `'.monster_status_table().'` WHERE status_id='.(int)$id)) throw new RuntimeException('수정할 상태이상이 없습니다.');
    foreach ($components as $component) {
        if (!is_array($component) || !isset($component['type'],$component['value']) || !is_numeric($component['value']) || !in_array($component['type'],array('STAT_MODIFIER','DOT_HP','ACTION_DISABLE','HEAL_MODIFIER'),true)) throw new RuntimeException('상태 효과 종류와 수치를 확인하세요.');
        if ($component['type']==='STAT_MODIFIER') {
            if (!isset($component['mode']) || !in_array($component['mode'],array('flat','percent','final'),true)) throw new RuntimeException('스탯 효과의 적용 방식을 선택하세요.');
            if (empty($component['stat_id']) || !sql_fetch('SELECT st_id FROM `'.$g5['status_config_table'].'` WHERE st_id='.(int)$component['stat_id'])) throw new RuntimeException('효과 대상 스탯을 선택하세요.');
        }
        if ($component['type']==='DOT_HP' && $component['value']<0) throw new RuntimeException('지속 피해는 0 이상이어야 합니다.');
        if ($component['type']==='ACTION_DISABLE' && (float)$component['value']!==1.0) throw new RuntimeException('행동 제한 값은 1이어야 합니다.');
    }
    $values=array('name'=>$name,'description'=>stripslashes($input['description']??''),'duration'=>$duration,'enabled'=>!empty($input['enabled'])?1:0,'components'=>json_encode($components,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $sets=array();foreach($values as $key=>$value)$sets[]='`'.$key.'`='.monster_quote($value);
    if($id)sql_query('UPDATE `'.monster_status_table().'` SET '.implode(',',$sets).' WHERE status_id='.(int)$id);
    else {sql_query('INSERT INTO `'.monster_status_table().'` SET '.implode(',',$sets));$id=(int)sql_insert_id();}
    return $id;
}
