<?php
if (!defined('_GNUBOARD_')) exit;

function maze_table($name) { return G5_TABLE_PREFIX.'dungeon_maze_'.$name; }
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
    $sets = array();
    foreach ($data as $key => $value) $sets[] = '`'.$key.'`='.inventory_boundary_quote($value);
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
    if ($exists === null) {
        $table = sql_escape_string(maze_table('session'));
        $row = sql_fetch("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'", false);
        $exists = !empty($row['TABLE_NAME']) && maze_readiness()['ready'];
    }
    return $exists;
}
function maze_session($ds_id, $lock = false) {
    if (!maze_installed()) return null;
    return maze_one('SELECT * FROM `'.maze_table('session').'` WHERE ds_id='.(int)$ds_id.($lock ? ' FOR UPDATE' : ''));
}
function maze_routes_instance($ds_id) {
    global $g5;
    if (maze_session((int)$ds_id)) return true;
    $legacy = sql_fetch('SELECT dm_id FROM `'.$g5['dungeon_member_table'].'` WHERE ds_id='.(int)$ds_id.' LIMIT 1');
    return empty($legacy['dm_id']);
}
function maze_entry_label($ds_id, $ch_id) {
    $session = maze_session((int)$ds_id);
    if (!$session || $session['phase'] === 'WAITING') return '입장하기';
    $person = maze_one('SELECT state FROM `'.maze_table('member').'` WHERE ds_id='.(int)$ds_id.' AND ch_id='.(int)$ch_id);
    return $person && $person['state'] === 'ACTIVE' ? '미궁 이어하기' : '';
}
function maze_sync_legacy_state($ds_id) {
    global $g5;
    $session = maze_session((int)$ds_id);
    if ($session && in_array($session['phase'],array('EXPLORE','BATTLE','BOSS_RESULT'),true)) inventory_boundary_query('UPDATE `'.$g5['dungeon_state_table'].'` SET ds_state=\'S\' WHERE ds_id='.(int)$ds_id." AND ds_state<>'S'");
    if ($session && in_array($session['phase'],array('CLEAR','FAILED','CLOSED'),true)) inventory_boundary_query('UPDATE `'.$g5['dungeon_state_table'].'` SET ds_state=\'E\' WHERE ds_id='.(int)$ds_id." AND ds_state<>'E'");
}
function maze_readiness() {
    static $result = null;
    if ($result !== null) return $result;
    $result = inventory_boundary_readiness();
    $server=sql_fetch('SELECT VERSION() AS version',false);
    $version=$server ? $server['version'] : '';
    $persistent=false;
    if(stripos($version,'MariaDB')!==false && preg_match('/(\d+\.\d+\.\d+)-MariaDB/i',$version,$match)) $persistent=version_compare($match[1],'10.2.4','>=');
    elseif(preg_match('/^(\d+\.\d+\.\d+)/',$version,$match)) $persistent=version_compare($match[1],'8.0.0','>=');
    if(!$persistent)$result['errors'][]='원본 ID 보관에는 재시작 후 AUTO_INCREMENT가 유지되는 DB가 필요합니다 (MariaDB 10.2.4+ / MySQL 8.0+).';
    $contract = array(
        'meta' => 'singleton schema_version action_timeout', 'config' => 'dg_id settings',
        'session' => 'ds_id dg_id phase version room_id battle_id snapshot expires_at started_at finished_at created_at',
        'member' => 'dm_id ds_id ch_id mb_id name state ready hp max_hp stats skills effects settled reward_eligible joined_at exited_at',
        'room' => 'ds_id room_id parent_id direction depth kind is_exit is_boss visited resolved encounter_done payload result_text',
        'battle' => 'battle_id ds_id room_id state turn_no deadline_at monster hp weak_count weak_turn',
        'action' => 'battle_id turn_no dm_id action target_id reference_id escape_vote',
        'inventory' => 'inventory_id ds_id dm_id ch_id it_id origin original_id active_original_id original_row item_snapshot consumed restored',
        'vote' => 'ds_id target_id voter_id', 'log' => 'log_id ds_id dm_id kind message created_at',
        'reward' => 'ds_id dm_id reward_type state mb_id amount completed_at',
        'status' => 'status_id name description duration enabled components', 'item_effect' => 'it_id kind status_id value'
    );
    $keys = array('meta' => array('singleton'), 'config' => array('dg_id'), 'session' => array('ds_id'),
        'member' => array('ds_id','ch_id'), 'room' => array('ds_id','room_id'), 'battle' => array('battle_id'),
        'action' => array('battle_id','turn_no','dm_id'), 'inventory' => array('active_original_id'),
        'vote' => array('ds_id','target_id','voter_id'), 'log' => array('log_id'), 'reward' => array('ds_id','dm_id','reward_type'),
        'status' => array('status_id'), 'item_effect' => array('it_id'));
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
    if (!$result['errors']) {
        $meta = maze_one('SELECT * FROM `'.maze_table('meta').'` WHERE singleton=1');
        if (!$meta || (int)$meta['schema_version'] !== 1) $result['errors'][] = 'schema 버전 미완료';
    }
    $result['ready'] = !$result['errors'];
    return $result;
}
function maze_require_ready() {
    $ready = maze_readiness();
    if (!$ready['ready']) throw new RuntimeException('미궁 시스템 DB migration이 적용되지 않았음');
}
function maze_defaults($dg) {
    global $g5;
    $rewards=array();
    if(!empty($g5['dungeon_item_table']) && !empty($dg['dg_id'])) foreach(inventory_boundary_rows('SELECT it_id,di_count,di_per_s,di_per_e FROM `'.$g5['dungeon_item_table'].'` WHERE dg_id='.(int)$dg['dg_id']) as $reward) {
        $outcomes=max(0,min(100,(int)$reward['di_per_e'])-max(0,(int)$reward['di_per_s'])+1);
        if((int)$reward['di_count']>0)$rewards[]=array('it_id'=>(int)$reward['it_id'],'count'=>(int)$reward['di_count'],'percent'=>round($outcomes*100/101,2));
    }
    return array('min_players' => 1, 'max_players' => max(1, (int)$dg['dg_count']),
        'rooms_min' => 9, 'rooms_max' => 15, 'branches_min' => 1, 'branches_max' => 3,
        'weights' => array('EMPTY' => 3, 'SEARCH' => 3, 'TREASURE' => 2, 'TRAP' => 1),
        'dead_end_percent' => 20, 'encounter_percent' => 30, 'escape_percent' => 50, 'trap_damage' => 10,
        'boss' => false, 'points' => (int)$dg['dg_point'], 'monsters' => array(array('weight' => 1, 'boss' => false, 'enabled' => true, 'data' => $dg)),
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
/** A rooted tree with exactly B branching nodes; no graph search on refresh. */
function maze_generate($settings) {
    $count = random_int((int)$settings['rooms_min'], (int)$settings['rooms_max']);
    $branch_max = min((int)$settings['branches_max'], (int)floor(($count - 1) / 2));
    if ($branch_max < (int)$settings['branches_min']) throw new RuntimeException('방 수와 갈림길 수 설정이 맞지 않습니다.');
    $branches = random_int((int)$settings['branches_min'], $branch_max);
    $rooms = array(1 => array('room_id' => 1, 'parent_id' => 0, 'direction' => 'forward', 'depth' => 0));
    $leaves = array(1); $branch_nodes = array(); $directions = array(); $next_id = 2;
    $add = function ($parent, $direction) use (&$rooms, &$next_id, &$directions) {
        $id = $next_id++;
        $rooms[$id] = array('room_id' => $id, 'parent_id' => $parent, 'direction' => $direction, 'depth' => $rooms[$parent]['depth'] + 1);
        $directions[$parent][] = $direction;
        return $id;
    };
    for ($i = 0; $i < $branches; $i++) {
        $index = random_int(0, count($leaves) - 1); $parent = $leaves[$index];
        array_splice($leaves, $index, 1); $branch_nodes[] = $parent;
        $leaves[] = $add($parent, 'forward'); $leaves[] = $add($parent, random_int(0,1) ? 'left' : 'right');
    }
    while (count($rooms) < $count) {
        $has_deep_leaf = false;
        foreach ($leaves as $leaf) if ($rooms[$leaf]['depth'] >= 2) $has_deep_leaf = true;
        $candidates = array_values(array_filter($branch_nodes, function ($id) use ($directions) { return count($directions[$id]) === 2; }));
        if ($candidates && (!$settings['boss'] || $has_deep_leaf) && random_int(1,100) <= $settings['dead_end_percent']) {
            $parent = $candidates[random_int(0,count($candidates)-1)];
            $remaining = array_values(array_diff(array('forward','left','right'),$directions[$parent]));
            $leaves[] = $add($parent,$remaining[0]);
        } else {
            $index = random_int(0,count($leaves)-1); $parent = $leaves[$index];
            $leaves[$index] = $add($parent,'forward');
        }
    }
    $leaves = array(); $parents = array_column($rooms, 'parent_id');
    foreach ($rooms as $id => $room) if (!in_array($id, $parents) && (!$settings['boss'] || $room['depth'] >= 2)) $leaves[] = $id;
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
        foreach ($settings['events'] as $event) if (!empty($event['enabled']) && $event['room_kind'] === $room['kind']) $events[] = $event;
        $event = maze_weighted($events);
        $monster = null;
        if ($id === $boss) $monster = maze_weighted($bosses);
        elseif ($id !== 1 && $id !== $exit && random_int(1,100) <= $settings['encounter_percent']) $monster = maze_weighted($monsters);
        $room['payload'] = array('event' => $event, 'monster' => $monster ? $monster['data'] : null);
    }
    unset($room);
    return array_values($rooms);
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
