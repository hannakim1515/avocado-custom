<?php
if (!defined('_GNUBOARD_')) exit;

function maze_table($name) { return G5_TABLE_PREFIX.($name === 'session' ? 'dungeon_state' : 'dungeon_maze_'.$name); }
require_once __DIR__.'/../lib/dungeon_reward.lib.php';
function maze_json($value) {
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new RuntimeException('미궁 데이터를 저장할 수 없습니다.');
    return $json;
}
function maze_data($value) {
    $data = json_decode((string)$value, true);
    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('미궁 저장 데이터 형식을 확인할 수 없습니다.');
    return $data;
}
function maze_one($sql) { $rows = inventory_boundary_rows($sql); return $rows ? $rows[0] : null; }
function maze_update($table, $data, $where) {
    if($table==='session' && isset($data['phase']))$data['ds_state']=in_array($data['phase'],array('CLEAR','FAILED','CLOSED'),true)?'E':'S';
    $sets = array();
    foreach ($data as $key => $value) $sets[] = '`'.$key.'`='.inventory_boundary_quote($value);
    if ($table === 'session') $sets[] = '`version`=`version`+1';
    inventory_boundary_query('UPDATE `'.maze_table($table).'` SET '.implode(',', $sets).' WHERE '.$where);
}
function maze_insert($table, $data) {
    $keys = array(); $values = array();
    foreach ($data as $key => $value) { $keys[] = '`'.$key.'`'; $values[] = inventory_boundary_quote($value); }
    inventory_boundary_query('INSERT INTO `'.maze_table($table).'` ('.implode(',', $keys).') VALUES ('.implode(',', $values).')');
    return (int)sql_insert_id();
}
/** Insert bounded batches, preserving one surrounding gameplay transaction. */
function maze_insert_many($table, $rows) {
    if (!$rows) return;
    $columns = array_keys($rows[0]);
    $prefix = 'INSERT INTO `'.maze_table($table).'` (`'.implode('`,`',$columns).'`) VALUES ';
    foreach (array_chunk($rows,100) as $chunk) {
        $values=array();
        foreach($chunk as $row) {
            if(array_keys($row)!==$columns) throw new RuntimeException('일괄 저장 컬럼이 일치하지 않습니다.');
            $values[]='('.implode(',',array_map('inventory_boundary_quote',array_values($row))).')';
        }
        inventory_boundary_query($prefix.implode(',',$values));
    }
}
function maze_log($ds_id, $dm_id, $kind, $message) {
    maze_insert('log', array('ds_id' => (int)$ds_id, 'dm_id' => (int)$dm_id, 'kind' => $kind, 'message' => $message, 'created_at' => date('Y-m-d H:i:s')));
}
function maze_installed() {
    static $exists = null;
    if ($exists === null) $exists = maze_readiness(false)['ready'];
    return $exists;
}
function maze_session($ds_id, $lock = false) {
    if (!maze_installed()) return null;
    return maze_one('SELECT * FROM `'.maze_table('session').'` WHERE snapshot IS NOT NULL AND ds_id='.(int)$ds_id.($lock ? ' FOR UPDATE' : ''));
}
function maze_entry_label($ds_id, $ch_id, $session = null) {
    if (!maze_installed()) return '입장하기';
    if ($session === null) $session = maze_one('SELECT phase FROM `'.maze_table('session').'` WHERE snapshot IS NOT NULL AND ds_id='.(int)$ds_id);
    if (!$session || (array_key_exists('snapshot',$session) && $session['snapshot'] === null) || $session['phase'] === 'WAITING') return '입장하기';
    if (array_key_exists('maze_member_state',$session)) return $session['maze_member_state'] === 'ACTIVE' ? '미궁 이어하기' : '';
    $person = maze_one('SELECT state FROM `'.maze_table('member').'` WHERE ds_id='.(int)$ds_id.' AND ch_id='.(int)$ch_id);
    return $person && $person['state'] === 'ACTIVE' ? '미궁 이어하기' : '';
}
function maze_readiness($full = true) {
    static $result = null, $version_result = null;
    if (!$full) {
        if ($version_result !== null) return $version_result;
        $version_result = inventory_boundary_readiness(false);
        $row = sql_fetch('SELECT version FROM `'.G5_TABLE_PREFIX."system_migration_meta` WHERE module='labyrinth'", false);
        if (!$row || version_compare((string)$row['version'], '1.7.0', '<')) $version_result['errors'][] = '미궁 시스템 DB migration 미완료';
        $version_result['ready'] = !$version_result['errors'];
        return $version_result;
    }
    if ($result !== null) return $result;
    $result = inventory_boundary_readiness();
    $server=sql_fetch('SELECT VERSION() AS version',false);
    $version=$server ? $server['version'] : '';
    $persistent=false;
    if(stripos($version,'MariaDB')!==false && preg_match('/(\d+\.\d+\.\d+)-MariaDB/i',$version,$match)) $persistent=version_compare($match[1],'10.2.4','>=');
    elseif(preg_match('/^(\d+\.\d+\.\d+)/',$version,$match)) $persistent=version_compare($match[1],'8.0.0','>=');
    if(!$persistent)$result['errors'][]='원본 ID 보관에는 재시작 후 AUTO_INCREMENT가 유지되는 DB가 필요합니다 (MariaDB 10.2.4+ / MySQL 8.0+).';
    $contract = array(
        'meta' => 'singleton schema_version action_timeout',
        'session' => 'ds_id dg_id phase version room_id battle_id snapshot expires_at started_at finished_at created_at',
        'member' => 'dm_id ds_id ch_id mb_id name state ready hp max_hp stats skills effects settled joined_at exited_at',
        'room' => 'ds_id room_id parent_id direction depth kind is_exit is_boss visited resolved encounter_done payload result_text',
        'battle' => 'battle_id ds_id room_id encounter_id state turn_no deadline_at monster hp weak_count weak_turn',
        'action' => 'battle_id turn_no dm_id action target_id monster_id reference_id escape_vote',
        'inventory' => 'inventory_id ds_id dm_id ch_id it_id origin original_id active_original_id original_row item_snapshot consumed restored',
        'vote' => 'ds_id target_id voter_id', 'log' => 'log_id ds_id dm_id kind message created_at',
        'reward' => 'ds_id dm_id reward_type state mb_id amount completed_at',
        'status' => 'status_id name description duration enabled components'
    );
    $keys = array('meta' => array('singleton'), 'session' => array('ds_id'),
        'member' => array('ds_id','ch_id'), 'room' => array('ds_id','room_id'), 'battle' => array('battle_id'),
        'action' => array('battle_id','turn_no','dm_id'), 'inventory' => array('active_original_id'),
        'vote' => array('ds_id','target_id','voter_id'), 'log' => array('log_id'), 'reward' => array('ds_id','dm_id','reward_type'),
        'status' => array('status_id'));
    $names = array();
    foreach ($contract as $name => $fields) $names[] = inventory_boundary_quote(maze_table($name));
    $filter = ' TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.implode(',', $names).')';
    $engines = array(); $columns = array(); $indexes = array();
    foreach (inventory_boundary_rows('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE'.$filter) as $row) $engines[$row['TABLE_NAME']] = strtoupper($row['ENGINE']);
    foreach (inventory_boundary_rows('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE'.$filter) as $row) $columns[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
    $unique_indexes=array();
    foreach (inventory_boundary_rows('SELECT TABLE_NAME,INDEX_NAME,COLUMN_NAME,SEQ_IN_INDEX,NON_UNIQUE FROM information_schema.STATISTICS WHERE'.$filter) as $row) {
        $indexes[$row['TABLE_NAME']][$row['INDEX_NAME']][(int)$row['SEQ_IN_INDEX']] = $row['COLUMN_NAME'];
        if(!(int)$row['NON_UNIQUE'])$unique_indexes[$row['TABLE_NAME']][$row['INDEX_NAME']][(int)$row['SEQ_IN_INDEX']] = $row['COLUMN_NAME'];
    }
    $access_paths=array('session'=>array(array('phase','expires_at'),array('dg_id','ds_id')),
        'member'=>array(array('ch_id','state'),array('ds_id','state','dm_id'),array('ch_id','dm_id'),array('ch_id','settled','exited_at')),
        'room'=>array(array('ds_id','parent_id'),array('ds_id','visited','room_id')),
        'battle'=>array(array('ds_id','battle_id')),'inventory'=>array(array('ds_id','dm_id','consumed','restored'),array('it_id','restored')),
        'log'=>array(array('ds_id','log_id')),'reward'=>array(array('state','ds_id')));
    foreach ($contract as $name => $fields) {
        $table = maze_table($name);
        if (!isset($engines[$table]) || $engines[$table] !== 'INNODB') { $result['errors'][] = $table.': InnoDB required'; continue; }
        if (array_diff(explode(' ', $fields), $columns[$table])) $result['errors'][] = $table.': missing columns';
        $table_indexes = isset($unique_indexes[$table]) ? $unique_indexes[$table] : array();
        foreach ($table_indexes as &$index) { ksort($index); $index = array_values($index); } unset($index);
        $identity = array('member'=>'dm_id','inventory'=>'inventory_id');
        if (isset($identity[$name]) && !in_array(array($identity[$name]),$table_indexes,true)) $result['errors'][]=$table.': missing identity unique index';
        if (!in_array($keys[$name], $table_indexes, true)) $result['errors'][] = $table.': missing unique index';
        if(isset($access_paths[$name])) foreach($access_paths[$name] as $required) {
            $found=false;
            foreach(isset($indexes[$table])?$indexes[$table]:array() as $index){ksort($index);if(array_slice(array_values($index),0,count($required))===$required)$found=true;}
            if(!$found)$result['errors'][]=$table.': missing lookup index ('.implode(',',$required).')';
        }
    }
    $result['ready'] = !$result['errors'];
    return $result;
}
function maze_require_ready() {
    $ready = maze_readiness(false);
    if (!$ready['ready']) throw new RuntimeException('미궁 시스템 DB migration이 적용되지 않았음');
}
function maze_defaults($dg, $rewards = null, $pool = null) {
    global $g5;
    if ($rewards === null) $rewards=!empty($dg['dg_id']) ? dungeon_rewards($dg['dg_id']) : array();
    $monsters=array();$boss=false;$encounters=false;
    if ($pool === null) $pool=!empty($dg['dg_id']) ? monster_rows('SELECT * FROM `'.monster_table('dungeon_monster_pool').'` WHERE dg_id='.(int)$dg['dg_id'].' ORDER BY monster_id') : array();
    foreach($pool as $entry) {
        $monsters[]=array('monster_id'=>(int)$entry['monster_id'],'weight'=>(int)$entry['weight'],'enabled'=>(bool)$entry['enabled'],'boss'=>(bool)$entry['is_boss'],'min_count'=>(int)($entry['min_count']??1),'max_count'=>(int)($entry['max_count']??1));
        if($entry['enabled'] && $entry['weight']>0){if($entry['is_boss'])$boss=true;else $encounters=true;}
    }
    return array('background'=>'','title'=>$dg['dg_title']??'미궁','description'=>$dg['dg_description']??'', 'min_players' => 1, 'max_players' => max(1, (int)$dg['dg_count']),
        'rooms_min' => 9, 'rooms_max' => 15, 'branches_min' => 1, 'branches_max' => 3,
        'weights' => array('EMPTY' => 3, 'SEARCH' => 3, 'TREASURE' => 2, 'TRAP' => 1),
        'dead_end_percent' => 20, 'encounter_percent' => $encounters?30:0, 'escape_percent' => 50, 'trap_damage' => 10,
        'boss' => $boss, 'points' => (int)$dg['dg_point'], 'monsters' => $monsters,
        'events' => array(), 'rewards' => $rewards);
}
function maze_weighted($rows, $field = 'weight') {
    $sum = 0;
    foreach ($rows as $row) $sum += max(0, (int)$row[$field]);
    if ($sum < 1) return null;
    $roll = random_int(1, $sum);
    foreach ($rows as $row) { $roll -= max(0, (int)$row[$field]); if ($roll <= 0) return $row; }
    return null;
}
/** The departure pool authorizes every encounter, including event results. */
function maze_pool_monster($id,$monsters) {
    $id=(int)$id;if($id<1)return null;
    foreach($monsters as $entry)if((int)($entry['monster_id']??$entry['data']['monster_id']??0)===$id && !empty($entry['enabled']) && (int)($entry['weight']??1)>0)return $entry;
    return null;
}
function maze_event_monster_id($event) { return (int)($event['monster_id']??$event['monster']['monster_id']??0); }
function maze_event_result_allowed($event,$monsters) {
    return ($event['type']??'')!=='MONSTER' || maze_pool_monster(maze_event_monster_id($event),$monsters)!==null;
}
/** Preserve choice keys so already generated rooms keep their request indexes. */
function maze_event_for_pool($event,$monsters) {
    if(!$event || (array_key_exists('enabled',$event) && empty($event['enabled'])))return null;
    if(!empty($event['choices'])) {
        $event['choices']=array_filter($event['choices'],function($choice)use($monsters){return maze_event_result_allowed($choice,$monsters);});
        if(!$event['choices'])return null;
    } elseif(!maze_event_result_allowed($event,$monsters))return null;
    return $event;
}
/** A rooted tree with exactly B branching nodes; no graph search on refresh. */
function maze_generate($settings) {
    $count = random_int((int)$settings['rooms_min'], (int)$settings['rooms_max']);
    $branch_max = min((int)$settings['branches_max'], (int)floor(($count - 1) / 2));
    if ($branch_max < (int)$settings['branches_min']) throw new RuntimeException('방 수와 갈림길 수 설정이 맞지 않습니다.');
    $branches = random_int((int)$settings['branches_min'], $branch_max);
    $rooms = array(1 => array('room_id' => 1, 'parent_id' => 0, 'direction' => 'forward', 'depth' => 0));
    $leaves = array(1); $branch_nodes = array(); $directions = array(); $next_id = 2;
    $deepest_index = function () use (&$rooms, &$leaves) {
        $depth = max(array_map(function ($id) use (&$rooms) { return $rooms[$id]['depth']; }, $leaves));
        $indices = array_keys(array_filter($leaves, function ($id) use (&$rooms, $depth) { return $rooms[$id]['depth'] === $depth; }));
        return $indices[random_int(0, count($indices) - 1)];
    };
    $add = function ($parent, $direction) use (&$rooms, &$next_id, &$directions) {
        $id = $next_id++;
        $rooms[$id] = array('room_id' => $id, 'parent_id' => $parent, 'direction' => $direction, 'depth' => $rooms[$parent]['depth'] + 1);
        $directions[$parent][] = $direction;
        return $id;
    };
    for ($i = 0; $i < $branches; $i++) {
        $index = $deepest_index(); $parent = $leaves[$index];
        array_splice($leaves, $index, 1); $branch_nodes[] = $parent;
        $leaves[] = $add($parent, 'forward'); $leaves[] = $add($parent, random_int(0,1) ? 'left' : 'right');
    }
    while (count($rooms) < $count) {
        // Reserve a route reaching at least half the generated rooms in depth.
        // Additional short dead ends must not consume that route's room budget.
        $deep_index = $deepest_index();
        $route_deep_enough = $rooms[$leaves[$deep_index]]['depth'] >= (int)ceil(($count - 1) / 2);
        $has_deep_leaf = false;
        foreach ($leaves as $leaf) if ($rooms[$leaf]['depth'] >= 2) $has_deep_leaf = true;
        $candidates = array_values(array_filter($branch_nodes, function ($id) use ($directions) { return count($directions[$id]) === 2; }));
        if ($route_deep_enough && $candidates && (!$settings['boss'] || $has_deep_leaf) && random_int(1,100) <= $settings['dead_end_percent']) {
            $parent = $candidates[random_int(0,count($candidates)-1)];
            $remaining = array_values(array_diff(array('forward','left','right'),$directions[$parent]));
            $leaves[] = $add($parent,$remaining[0]);
        } else {
            $index = $deep_index; $parent = $leaves[$index];
            $leaves[$index] = $add($parent,'forward');
        }
    }
    $leaves = array(); $parents = array_column($rooms, 'parent_id');
    foreach ($rooms as $id => $room) if (!in_array($id, $parents) && (!$settings['boss'] || $room['depth'] >= 2)) $leaves[] = $id;
    $deepest = max(array_map(function ($id) use ($rooms) { return $rooms[$id]['depth']; }, $leaves));
    $leaves = array_values(array_filter($leaves, function ($id) use ($rooms, $deepest) { return $rooms[$id]['depth'] === $deepest; }));
    $exit = $leaves[random_int(0, count($leaves) - 1)];
    $boss = $settings['boss'] ? $rooms[$exit]['parent_id'] : 0;
    $weights = array(); foreach ($settings['weights'] as $kind => $weight) $weights[] = array('kind' => $kind, 'weight' => $weight);
    $monsters = array(); $bosses = array();
    foreach ($settings['monsters'] as $monster) if (!empty($monster['enabled'])) { if (!empty($monster['boss'])) $bosses[] = $monster; else $monsters[] = $monster; }
    foreach ($rooms as $id => &$room) {
        $type = maze_weighted($weights);
        $room += array('kind' => $id === 1 || $id === $exit || $id === $boss ? 'EMPTY' : $type['kind'],
            'is_exit' => (int)($id === $exit), 'is_boss' => (int)($id === $boss), 'visited' => (int)($id === 1),
            'resolved' => (int)($id === 1), 'encounter_done' => 0, 'result_text' => '');
        $events = array();
        foreach ($settings['events'] as $index => $event) if (!empty($event['enabled']) && $event['room_kind'] === $room['kind']) {
            if(maze_event_for_pool($event,$settings['monsters']))$events[]=array('index'=>$index,'weight'=>$event['weight']);
        }
        $monster = maze_weighted($id === $boss ? $bosses : ($id !== 1 && $id !== $exit && random_int(1,100) <= $settings['encounter_percent'] ? $monsters : array()));
        $count = $monster ? maze_monster_count_range($monster) : array(1,1);
        $room['payload'] = array('event_index' => maze_weighted($events)['index']??null, 'monster_count' => $monster ? random_int($count[0],$count[1]) : 0) + ($monster ? array('monster_id'=>$monster['monster_id']) : array());
    }
    unset($room);
    return array_values($rooms);
}
/** Read-only encounter lookup shared by gameplay and the isolated state endpoint. */
function maze_battle_group($session,$leader) {
    if(!array_key_exists('encounter_id',$leader))return array((int)$leader['battle_id']=>$leader);
    return array_column(inventory_boundary_rows('SELECT * FROM `'.maze_table('battle').'` WHERE ds_id='.(int)$session['ds_id'].' AND (battle_id='.(int)$leader['battle_id'].' OR encounter_id='.(int)$leader['battle_id'].') ORDER BY battle_id'),null,'battle_id');
}
function maze_party($ds_id, $active_only = true) {
    return inventory_boundary_rows('SELECT * FROM `'.maze_table('member').'` WHERE ds_id='.(int)$ds_id.($active_only ? " AND state='ACTIVE'" : '').' ORDER BY dm_id');
}
function maze_actor($session, $alive = false) {
    global $member, $character;
    inventory_boundary_owner((int)$character['ch_id']);
    $actor = maze_one('SELECT * FROM `'.maze_table('member').'` WHERE ds_id='.(int)$session['ds_id'].' AND ch_id='.(int)$character['ch_id']);
    if (!$actor || $actor['mb_id'] !== $member['mb_id'] || $actor['state'] !== 'ACTIVE') throw new RuntimeException('현재 미궁 참가자가 아닙니다.');
    if ($alive && (int)$actor['hp'] <= 0) throw new RuntimeException('전투불능 상태에서는 실행할 수 없습니다.');
    return $actor;
}
function maze_deadline() {
    $meta = maze_one('SELECT action_timeout FROM `'.maze_table('meta').'` WHERE singleton=1');
    return $meta['action_timeout'] === null ? null : date('Y-m-d H:i:s', time() + max(1, (int)$meta['action_timeout']));
}
