<?php
if (!defined('_GNUBOARD_')) exit;

// 필드 기능에서 사용하는 테이블명입니다.
// 실제 테이블 생성은 필요한 화면/요청에서만 field_install_tables()로 실행합니다.
// 이렇게 두면 사이트의 다른 페이지를 열 때마다 불필요한 DESC 쿼리가 돌지 않습니다.
$g5['field_player_table']    = G5_TABLE_PREFIX.'field_player';
$g5['field_log_table']       = G5_TABLE_PREFIX.'field_log';
$g5['field_map_table']       = G5_TABLE_PREFIX.'field_map';
$g5['field_event_table']     = G5_TABLE_PREFIX.'field_event';
$g5['field_fishing_table']   = G5_TABLE_PREFIX.'field_fishing';
$g5['field_encounter_table'] = G5_TABLE_PREFIX.'field_encounter';

function field_install_tables() {
	global $g5;
	static $installed = false;
	if($installed) return;
	$installed = true;

	if(!sql_query(" DESC {$g5['field_player_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_player_table']}` (
			`fp_id` int(11) NOT NULL AUTO_INCREMENT,
			`ch_id` int(11) NOT NULL default '0',
			`fm_id` varchar(50) NOT NULL default 'demo_forest',
			`fp_x` int(11) NOT NULL default '2',
			`fp_y` int(11) NOT NULL default '10',
			`fp_dir` varchar(10) NOT NULL default 'down',
			`fp_datetime` datetime NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (`fp_id`),
			UNIQUE KEY `idx_ch_id` (`ch_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	}

	if(!sql_query(" DESC {$g5['field_log_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_log_table']}` (
			`fl_id` int(11) NOT NULL AUTO_INCREMENT,
			`ch_id` int(11) NOT NULL default '0',
			`fm_id` varchar(50) NOT NULL default '',
			`fl_key` varchar(80) NOT NULL default '',
			`fl_type` varchar(30) NOT NULL default '',
			`fl_datetime` datetime NOT NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY (`fl_id`),
			KEY `idx_field_log` (`ch_id`, `fm_id`, `fl_key`),
			KEY `idx_field_log_type` (`ch_id`, `fm_id`, `fl_type`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	} else if(!field_index_exists($g5['field_log_table'], 'idx_field_log_type')) {
		sql_query(" ALTER TABLE `{$g5['field_log_table']}` ADD KEY `idx_field_log_type` (`ch_id`, `fm_id`, `fl_type`) ", false);
	}

	if(!sql_query(" DESC {$g5['field_map_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_map_table']}` (
			`fm_id` varchar(50) NOT NULL default '',
			`fm_name` varchar(255) NOT NULL default '',
			`fm_use` int(11) NOT NULL default '1',
			`fm_img` varchar(255) NOT NULL default '',
			`fm_tile_size` int(11) NOT NULL default '32',
			`fm_start_x` int(11) NOT NULL default '1',
			`fm_start_y` int(11) NOT NULL default '1',
			`fm_start_dir` varchar(10) NOT NULL default 'down',
			`fm_tiles` text NOT NULL,
			`fm_order` int(11) NOT NULL default '0',
			PRIMARY KEY (`fm_id`),
			KEY `idx_fm_use` (`fm_use`, `fm_order`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	}

	if(!sql_query(" DESC {$g5['field_event_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_event_table']}` (
			`fe_id` int(11) NOT NULL AUTO_INCREMENT,
			`fm_id` varchar(50) NOT NULL default '',
			`fe_type` varchar(30) NOT NULL default 'npc',
			`fe_x` int(11) NOT NULL default '0',
			`fe_y` int(11) NOT NULL default '0',
			`fe_name` varchar(255) NOT NULL default '',
			`fe_text` text NOT NULL,
			`fe_it_id` int(11) NOT NULL default '0',
			`fe_url` varchar(255) NOT NULL default '',
			`fe_use` int(11) NOT NULL default '1',
			`fe_order` int(11) NOT NULL default '0',
			PRIMARY KEY (`fe_id`),
			KEY `idx_field_event` (`fm_id`, `fe_x`, `fe_y`, `fe_use`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	}

	if(!sql_query(" DESC {$g5['field_fishing_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_fishing_table']}` (
			`ff_id` int(11) NOT NULL AUTO_INCREMENT,
			`fm_id` varchar(50) NOT NULL default '',
			`ff_name` varchar(255) NOT NULL default '',
			`ff_text` varchar(255) NOT NULL default '',
			`ff_rate` int(11) NOT NULL default '0',
			`ff_it_id` int(11) NOT NULL default '0',
			`ff_use` int(11) NOT NULL default '1',
			`ff_order` int(11) NOT NULL default '0',
			PRIMARY KEY (`ff_id`),
			KEY `idx_field_fishing` (`fm_id`, `ff_use`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	}

	if(!sql_query(" DESC {$g5['field_encounter_table']} ", false)) {
		sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['field_encounter_table']}` (
			`fc_id` int(11) NOT NULL AUTO_INCREMENT,
			`fm_id` varchar(50) NOT NULL default '',
			`fc_tile` varchar(10) NOT NULL default 'G',
			`fc_name` varchar(255) NOT NULL default '',
			`fc_rate` int(11) NOT NULL default '0',
			`fc_url` varchar(255) NOT NULL default '',
			`fc_use` int(11) NOT NULL default '1',
			`fc_order` int(11) NOT NULL default '0',
			PRIMARY KEY (`fc_id`),
			KEY `idx_field_encounter` (`fm_id`, `fc_tile`, `fc_use`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);
	}
}

function field_index_exists($table, $index_name) {
	$table = sql_escape_string($table);
	$index_name = sql_escape_string($index_name);
	$row = sql_fetch(" SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index_name}' ", false);
	return isset($row['Key_name']) && $row['Key_name'] === $index_name;
}

function field_sample_map() {
	return array(
		'id' => 'demo_forest',
		'name' => '샘플 숲길',
		'use' => 1,
		'img' => '',
		'tile_size' => 32,
		'start' => array('x' => 2, 'y' => 10, 'dir' => 'down'),
		'tiles' => array(
			'####################',
			'#....GGGG.....WWWW.#',
			'#..N.GGGG.....WWWW.#',
			'#....GGGG..######..#',
			'#...............#..#',
			'#..####.........#..#',
			'#..#..#....I....#..#',
			'#..#..#.........#..#',
			'#..#..#####..####..#',
			'#..#..............P#',
			'#.S#....GGGG.......#',
			'#.......GGGG.......#',
			'#.......GGGG.......#',
			'####################'
		),
		'events' => array(
			'3,2' => array(
				'type' => 'npc',
				'name' => '숲지기',
				'text' => '풀숲을 걸으면 몬스터가 튀어나올 수 있어. 물가에서는 조사 키로 낚시도 해봐.'
			),
			'11,6' => array(
				'type' => 'item',
				'name' => '반짝이는 조각',
				'text' => '반짝이는 조각을 주웠다.',
				'it_id' => 0
			),
			'18,9' => array(
				'type' => 'portal',
				'name' => '큰 지도',
				'text' => '기존 지도 화면으로 돌아갈 수 있는 길이다.',
				'url' => G5_URL.'/map'
			)
		),
		'encounters' => array(
			'G' => array(
				'rate' => 18,
				'monsters' => array(
					array('name' => '새끼 슬라임', 'rate' => 8, 'url' => G5_URL.'/dungeon'),
					array('name' => '숲 박쥐', 'rate' => 6, 'url' => G5_URL.'/dungeon'),
					array('name' => '들쥐 몬스터', 'rate' => 4, 'url' => G5_URL.'/dungeon')
				)
			)
		),
		'fishing' => array(
			'rate' => 55,
			'catches' => array(
				array('name' => '작은 은빛 물고기', 'text' => '', 'rate' => 30, 'it_id' => 0),
				array('name' => '낡은 병', 'text' => '', 'rate' => 15, 'it_id' => 0),
				array('name' => '물풀', 'text' => '', 'rate' => 10, 'it_id' => 0)
			)
		)
	);
}

function field_get_maps() {
	field_install_tables();

	$maps = field_get_db_maps();
	if(count($maps) > 0) return $maps;

	$sample = field_sample_map();
	return array($sample['id'] => $sample);
}

function field_get_db_maps() {
	global $g5;

	$maps = array();
	$result = sql_query("select * from {$g5['field_map_table']} where fm_use = '1' order by fm_order asc, fm_id asc", false);
	if(!$result) return $maps;

	for($i=0; $row=sql_fetch_array($result); $i++) {
		$map = field_build_map_from_row($row);
		if($map) $maps[$map['id']] = $map;
	}

	return $maps;
}

function field_get_map($fm_id) {
	global $g5;
	static $cache = array();

	field_install_tables();

	$fm_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$fm_id);
	$cache_key = $fm_id ? $fm_id : '__first__';
	if(isset($cache[$cache_key])) return $cache[$cache_key];

	$row = array();
	if($fm_id) {
		$row = sql_fetch("select * from {$g5['field_map_table']} where fm_id = '".sql_escape_string($fm_id)."' and fm_use = '1'", false);
	}
	if(!$row || !isset($row['fm_id']) || !$row['fm_id']) {
		$row = sql_fetch("select * from {$g5['field_map_table']} where fm_use = '1' order by fm_order asc, fm_id asc limit 1", false);
	}

	$map = ($row && isset($row['fm_id']) && $row['fm_id']) ? field_build_map_from_row($row) : null;
	if(!$map) $map = field_sample_map();

	$cache[$cache_key] = $map;
	return $map;
}

function field_build_map_from_row($row) {
	$tiles = field_parse_tiles($row['fm_tiles']);
	if(count($tiles) < 1) return null;

	return array(
		'id' => $row['fm_id'],
		'name' => $row['fm_name'],
		'use' => (int)$row['fm_use'],
		'img' => $row['fm_img'],
		'tile_size' => max(16, (int)$row['fm_tile_size']),
		'start' => array(
			'x' => (int)$row['fm_start_x'],
			'y' => (int)$row['fm_start_y'],
			'dir' => $row['fm_start_dir'] ? $row['fm_start_dir'] : 'down'
		),
		'tiles' => $tiles,
		'events' => field_get_db_events($row['fm_id']),
		'encounters' => field_get_db_encounters($row['fm_id']),
		'fishing' => field_get_db_fishing($row['fm_id'])
	);
}

function field_parse_tiles($tiles) {
	$tiles = str_replace("\r\n", "\n", trim($tiles));
	$rows = $tiles === '' ? array() : explode("\n", $tiles);
	$result = array();
	$width = 0;

	for($i=0; $i<count($rows); $i++) {
		$row = trim($rows[$i]);
		if($row === '') continue;
		$width = max($width, strlen($row));
		$result[] = $row;
	}

	if($width < 1) return array();

	for($i=0; $i<count($result); $i++) {
		$result[$i] = str_pad($result[$i], $width, '#');
	}

	return $result;
}

function field_tiles_to_text($tiles) {
	return is_array($tiles) ? implode("\n", $tiles) : (string)$tiles;
}

function field_get_db_events($fm_id) {
	global $g5;

	$events = array();
	$fm_id = sql_escape_string($fm_id);
	$result = sql_query("select * from {$g5['field_event_table']} where fm_id = '{$fm_id}' and fe_use = '1' order by fe_order asc, fe_id asc", false);
	if(!$result) return $events;

	for($i=0; $row=sql_fetch_array($result); $i++) {
		$key = field_event_key($row['fe_x'], $row['fe_y']);
		$events[$key] = array(
			'type' => $row['fe_type'],
			'name' => $row['fe_name'],
			'text' => $row['fe_text'],
			'it_id' => (int)$row['fe_it_id'],
			'url' => $row['fe_url']
		);
	}

	return $events;
}

function field_get_db_fishing($fm_id) {
	global $g5;

	$fm_id = sql_escape_string($fm_id);
	$result = sql_query("select * from {$g5['field_fishing_table']} where fm_id = '{$fm_id}' and ff_use = '1' order by ff_order asc, ff_id asc", false);
	$catches = array();
	$total_rate = 0;

	if($result) {
		for($i=0; $row=sql_fetch_array($result); $i++) {
			$rate = max(0, (int)$row['ff_rate']);
			$total_rate += $rate;
			$catches[] = array(
				'name' => $row['ff_name'],
				'text' => $row['ff_text'],
				'rate' => $rate,
				'it_id' => (int)$row['ff_it_id']
			);
		}
	}

	return array('rate' => min(100, $total_rate), 'catches' => $catches);
}

function field_get_db_encounters($fm_id) {
	global $g5;

	$fm_id = sql_escape_string($fm_id);
	$result = sql_query("select * from {$g5['field_encounter_table']} where fm_id = '{$fm_id}' and fc_use = '1' order by fc_order asc, fc_id asc", false);
	$by_tile = array();

	if($result) {
		for($i=0; $row=sql_fetch_array($result); $i++) {
			$tile = $row['fc_tile'] ? $row['fc_tile'] : 'G';
			if(!isset($by_tile[$tile])) {
				$by_tile[$tile] = array('rate' => 0, 'monsters' => array());
			}
			$by_tile[$tile]['rate'] += max(0, (int)$row['fc_rate']);
			$by_tile[$tile]['monsters'][] = array(
				'name' => $row['fc_name'],
				'rate' => max(0, (int)$row['fc_rate']),
				'url' => $row['fc_url']
			);
		}
	}

	foreach($by_tile as $tile => $data) {
		$by_tile[$tile]['rate'] = min(100, (int)$data['rate']);
	}

	return $by_tile;
}

function field_map_width($map) {
	return strlen($map['tiles'][0]);
}

function field_map_height($map) {
	return count($map['tiles']);
}

function field_tile_at($map, $x, $y) {
	if($y < 0 || $y >= field_map_height($map)) return '#';
	if($x < 0 || $x >= field_map_width($map)) return '#';
	return substr($map['tiles'][$y], $x, 1);
}

function field_tile_info($tile) {
	$list = array(
		'#' => array('name' => '벽', 'walkable' => false, 'class' => 'wall'),
		'.' => array('name' => '길', 'walkable' => true, 'class' => 'path'),
		'S' => array('name' => '시작점', 'walkable' => true, 'class' => 'path'),
		'G' => array('name' => '풀숲', 'walkable' => true, 'class' => 'grass'),
		'W' => array('name' => '물가', 'walkable' => false, 'class' => 'water'),
		'N' => array('name' => 'NPC', 'walkable' => false, 'class' => 'npc'),
		'I' => array('name' => '아이템', 'walkable' => false, 'class' => 'item'),
		'P' => array('name' => '이동 지점', 'walkable' => true, 'class' => 'portal')
	);

	return isset($list[$tile]) ? $list[$tile] : array('name' => '길', 'walkable' => true, 'class' => 'path');
}

function field_effective_tile($map, $ch_id, $x, $y) {
	$tile = field_tile_at($map, $x, $y);
	$key = field_event_key($x, $y);

	// 획득 완료한 아이템 칸은 빈 길처럼 취급합니다.
	if($tile == 'I' && isset($map['events'][$key]) && $map['events'][$key]['type'] == 'item') {
		if(field_has_log($ch_id, $map['id'], $key)) return '.';
	}

	return $tile;
}

function field_event_key($x, $y) {
	return (int)$x.','.(int)$y;
}

function field_get_player($ch_id) {
	global $g5;

	field_install_tables();

	$ch_id = (int)$ch_id;
	$row = sql_fetch("select * from {$g5['field_player_table']} where ch_id = '{$ch_id}'");
	if(isset($row['ch_id']) && $row['ch_id']) return $row;

	$map = field_get_map('');
	$start = $map['start'];
	sql_query(" insert into {$g5['field_player_table']}
		set ch_id = '{$ch_id}',
			fm_id = '{$map['id']}',
			fp_x = '{$start['x']}',
			fp_y = '{$start['y']}',
			fp_dir = '{$start['dir']}',
			fp_datetime = '".G5_TIME_YMDHIS."' ");

	return sql_fetch("select * from {$g5['field_player_table']} where ch_id = '{$ch_id}'");
}

function field_set_player($ch_id, $fm_id, $x, $y, $dir) {
	global $g5;

	$ch_id = (int)$ch_id;
	$fm_id = sql_escape_string($fm_id);
	$x = (int)$x;
	$y = (int)$y;
	$dir = in_array($dir, array('up','down','left','right')) ? $dir : 'down';

	sql_query(" update {$g5['field_player_table']}
		set fm_id = '{$fm_id}',
			fp_x = '{$x}',
			fp_y = '{$y}',
			fp_dir = '{$dir}',
			fp_datetime = '".G5_TIME_YMDHIS."'
		where ch_id = '{$ch_id}' ");
}

function field_has_log($ch_id, $fm_id, $key) {
	global $g5;

	$ch_id = (int)$ch_id;
	$fm_id = sql_escape_string($fm_id);
	$key = sql_escape_string($key);
	$row = sql_fetch("select count(*) as cnt from {$g5['field_log_table']} where ch_id = '{$ch_id}' and fm_id = '{$fm_id}' and fl_key = '{$key}'");
	return (int)$row['cnt'] > 0;
}

function field_add_log($ch_id, $fm_id, $key, $type) {
	global $g5;

	$ch_id = (int)$ch_id;
	$fm_id = sql_escape_string($fm_id);
	$key = sql_escape_string($key);
	$type = sql_escape_string($type);

	if(field_has_log($ch_id, $fm_id, $key)) return;

	// 영구 저장은 필요한 플래그만 남깁니다. 현재는 아이템 획득 여부가 주 용도입니다.
	sql_query(" insert into {$g5['field_log_table']}
		set ch_id = '{$ch_id}',
			fm_id = '{$fm_id}',
			fl_key = '{$key}',
			fl_type = '{$type}',
			fl_datetime = '".G5_TIME_YMDHIS."' ");
}

function field_get_collected_keys($ch_id, $fm_id) {
	global $g5;

	$keys = array();
	$ch_id = (int)$ch_id;
	$fm_id = sql_escape_string($fm_id);
	$result = sql_query("select fl_key from {$g5['field_log_table']} where ch_id = '{$ch_id}' and fm_id = '{$fm_id}' and fl_type = 'item'", false);
	if(!$result) return $keys;

	for($i=0; $row=sql_fetch_array($result); $i++) {
		$keys[] = $row['fl_key'];
	}

	return $keys;
}

function field_public_state($ch_id, $include_map = true) {
	$player = field_get_player($ch_id);
	$map = field_get_map($player['fm_id']);

	$state = array(
		'player' => array(
			'x' => (int)$player['fp_x'],
			'y' => (int)$player['fp_y'],
			'dir' => $player['fp_dir']
		),
		'collected' => field_get_collected_keys($ch_id, $map['id'])
	);

	if($include_map) $state['map'] = $map;

	return $state;
}

function field_try_encounter($map, $tile) {
	if(!isset($map['encounters'][$tile])) return null;

	$enc = $map['encounters'][$tile];
	$rate = (int)$enc['rate'];
	if($rate <= 0 || rand(1, 100) > $rate) return null;

	$selected = field_pick_weighted($enc['monsters'], 'rate');
	if(!$selected) return null;

	$name = is_array($selected) ? $selected['name'] : $selected;
	$url = is_array($selected) && $selected['url'] ? $selected['url'] : G5_URL.'/dungeon';

	return array(
		'type' => 'encounter',
		'title' => '몬스터 조우',
		'text' => $name.'가 나타났다!',
		'monster' => $name,
		'battle_url' => $url
	);
}

function field_pick_weighted($list, $rate_key) {
	if(!is_array($list) || count($list) < 1) return null;

	$total = 0;
	for($i=0; $i<count($list); $i++) {
		if(is_array($list[$i])) $total += max(0, (int)$list[$i][$rate_key]);
	}

	if($total <= 0) return $list[array_rand($list)];

	$seed = rand(1, $total);
	$cursor = 0;
	for($i=0; $i<count($list); $i++) {
		$cursor += max(0, (int)$list[$i][$rate_key]);
		if($seed <= $cursor) return $list[$i];
	}

	return $list[count($list) - 1];
}

function field_json($data) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
	exit;
}
?>
