<?php
if (!defined('_GNUBOARD_')) exit;
function maze_admin_h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function maze_admin_battle($session) {
    if(empty($session['battle_id'])) return null;
    return maze_one('SELECT battle_id,turn_no,state FROM `'.maze_table('battle').'` WHERE battle_id='.(int)$session['battle_id'].' AND ds_id='.(int)$session['ds_id']);
}
function maze_admin_json($value) {
    // common.php escapes incoming form values once; JSON needs the original text.
    $data = json_decode(stripslashes($value), true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('설정 데이터 형식을 확인해 주세요.');
    return $data;
}
function maze_admin_hidden($operation) { global $token,$dg_id,$ds_id; echo '<input type="hidden" name="token" value="'.maze_admin_h($token).'"><input type="hidden" name="operation" value="'.maze_admin_h($operation).'"><input type="hidden" name="dg_id" value="'.$dg_id.'"><input type="hidden" name="ds_id" value="'.$ds_id.'">'; }

function maze_admin_settings($dg,$input,$pool,$rewards) {
    global $g5;
    $saved=sql_fetch('SELECT dg_settings FROM `'.$g5['dungeon_table'].'` WHERE dg_id='.(int)($dg['dg_id']??0));
    $settings=array_replace(maze_defaults($dg),!empty($saved['dg_settings'])?maze_data($saved['dg_settings']):array());
    $settings['title']=$dg['dg_title'];$settings['description']=$dg['dg_description']??'';
            $settings['max_players']=(int)$dg['dg_count'];
            foreach (array('min_players','rooms_min','rooms_max','branches_min','branches_max','dead_end_percent','encounter_percent','escape_percent','trap_damage') as $key) {
                $value=filter_var($input[$key]??null,FILTER_VALIDATE_INT);
                if($value===false)throw new RuntimeException('탐험 설정을 정수로 입력하세요: '.$key);
                $settings[$key]=$value;
            }
            $settings['points']=(int)$dg['dg_point'];
            $settings['boss'] = !empty($input['boss']);
            $settings['background']=monster_image_input('background','background_file');
            foreach ($settings['weights'] as $key => $value) $settings['weights'][$key] = (int)$input['weights'][$key];
            $settings['events'] = maze_admin_json($input['events']??'[]');
            $settings['rewards']=$rewards;
            $settings['monsters']=array();
            foreach($pool['rows']??array() as $row)$settings['monsters'][]=array('monster_id'=>$row['monster_id'],'weight'=>$row['weight'],'enabled'=>true,'boss'=>(bool)$row['is_boss'],'min_count'=>$row['min_count']??1,'max_count'=>$row['max_count']??1);
            foreach ($settings['events'] as $event) {
                if(empty($event['enabled']))continue;
                if (($event['type']??'')==='MONSTER' && empty($event['monster_id']) && empty($event['choices'])) throw new RuntimeException('몬스터 전투 이벤트에서 공용 몬스터를 선택하세요.');
                foreach ($event['choices']??array() as $choice) if (($choice['type']??'')==='MONSTER' && empty($choice['monster_id'])) throw new RuntimeException('몬스터 전투 선택지에서 공용 몬스터를 선택하세요.');
            }
            foreach ($settings['monsters'] as &$monster) $monster['data']=maze_monster_definition(!empty($monster['monster_id'])?array_replace($monster['data']??array(),monster_profile($monster['monster_id'])):$monster['data'],$dg);
            unset($monster);
            foreach ($settings['events'] as &$event) {
                if(empty($event['enabled']))continue;
                if(($event['type']??'')==='MONSTER'&&empty($event['choices'])) {
                    if(!maze_event_result_allowed($event,$settings['monsters']))throw new RuntimeException('방 이벤트의 전투 몬스터는 몬스터 출현 목록에서 선택하세요. 이벤트 사용을 끄거나 몬스터를 변경할 수 있습니다.');
                    $event['monster']=maze_monster_definition(!empty($event['monster_id'])?monster_profile($event['monster_id']):$event['monster'],$dg);
                }
                if(!empty($event['choices'])) foreach($event['choices'] as &$choice) if(($choice['type']??'')==='MONSTER') {
                    if(!maze_event_result_allowed($choice,$settings['monsters']))throw new RuntimeException('조사 선택지의 전투 몬스터는 몬스터 출현 목록에서 선택하세요.');
                    $choice['monster']=maze_monster_definition(!empty($choice['monster_id'])?monster_profile($choice['monster_id']):$choice['monster'],$dg);
                }
                unset($choice);
            } unset($event);
            maze_validate_config($settings);
            // Linked monsters are definitions in monster_master, not copies in config JSON.
            foreach ($settings['monsters'] as &$monster) if (!empty($monster['monster_id'])) unset($monster['data']);
            unset($monster);
            foreach ($settings['events'] as &$event) {
                if (!empty($event['monster_id'])) unset($event['monster']);
                if (!empty($event['choices'])) foreach ($event['choices'] as &$choice) if (!empty($choice['monster_id'])) unset($choice['monster']);
                unset($choice);
            }
            unset($event);
            unset($settings['title'],$settings['description'],$settings['max_players'],$settings['points'],$settings['rewards'],$settings['monsters']);
    return $settings;
}
