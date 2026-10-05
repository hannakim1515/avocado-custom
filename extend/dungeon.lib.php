<?php
if (!defined('_GNUBOARD_')) exit;
require_once G5_PATH.'/lib/monster.lib.php';
$g5['dungeon_table'] = G5_TABLE_PREFIX.'dungeon';
$g5['dungeon_read_table'] = monster_dungeon_read_ready() ? monster_dungeon_read_table() : $g5['dungeon_table'];
$g5['dungeon_item_table'] = G5_TABLE_PREFIX.'dungeon_item';
$g5['dungeon_state_table'] = G5_TABLE_PREFIX.'dungeon_state';
define('G5_DUNGEON_URL', G5_URL.'/dungeon');
define('G5_DUNGEON_PATH', G5_PATH.'/dungeon');
$use_dungeon_map = !empty($config['cf_dungeon_open']) && !empty($config['cf_dungeon_map']);
// Schema changes belong to the installer, never page requests.
function set_reset_dungeon($retry_missing = false) {
    global $g5,$config;
    if (!maze_installed()) return $config['cf_dungeon_reset']??'';
    $hours=max(1,(int)$config['cf_dungeon_time']);
    if (empty($config['cf_dungeon_reset']) || $config['cf_dungeon_reset']==='0000-00-00 00:00:00')
        $config['cf_dungeon_reset']=date('Y-m-d H:00:00',time()-$hours*3600);
    $next=strtotime($config['cf_dungeon_reset'])+$hours*3600;
    if ($next>time() && !$retry_missing) return $config['cf_dungeon_reset'];
    // Explicit repair may retry an empty current cycle without changing its time.
    $retry_current=$retry_missing && $next>time();
    $reset=$retry_current ? $config['cf_dungeon_reset'] : date('Y-m-d H:i:s',$next+floor((time()-$next)/($hours*3600))*$hours*3600);
    $lock=sql_fetch("SELECT GET_LOCK('".sql_escape_string(G5_TABLE_PREFIX."dungeon_reset")."',0) acquired");
    if(empty($lock['acquired']))return $config['cf_dungeon_reset'];
    try {
        $current=sql_fetch('SELECT cf_dungeon_reset FROM `'.$g5['config_table'].'`');
        if($current['cf_dungeon_reset']>$reset)return $current['cf_dungeon_reset'];
        if($current['cf_dungeon_reset']===$reset) {
            if(!$retry_current)return $reset;
            // Cleared or closed instances also count: never reopen a completed cycle.
            $generated=sql_fetch('SELECT ds_id FROM `'.$g5['dungeon_state_table'].'` WHERE created_at>='.monster_quote($reset).' OR ds_datetime>='.monster_quote($reset).' LIMIT 1');
            if($generated)return $reset;
        }
        maze_expire_waiting();
        $seed=random_int(0,100);$candidates=array();
        if(!empty($config['cf_dungeon_map'])) {
            // A region without a matching dungeon must not consume an opening slot.
            $eligible_maps='SELECT m.ma_id FROM `'.$g5['map_table'].'` m WHERE m.ma_use=1 AND m.ma_use_dungeon=1 AND EXISTS (SELECT 1 FROM `'.$g5['dungeon_table'].'` d WHERE d.dg_use=1 AND d.ma_ids LIKE CONCAT(\'%||\',m.ma_id,\'||%\') AND d.dg_per_s<='.$seed.' AND d.dg_per_e>='.$seed.') ORDER BY RAND() LIMIT '.max(0,(int)$config['cf_dungeon_count']);
            foreach(monster_rows($eligible_maps) as $map) {
                $rows=monster_rows('SELECT * FROM `'.$g5['dungeon_table'].'` WHERE dg_use=1 AND ma_ids LIKE '.monster_quote('%||'.(int)$map['ma_id'].'||%').' AND dg_per_s<='.$seed.' AND dg_per_e>='.$seed.' ORDER BY RAND() LIMIT 1');
                foreach($rows as $d)$candidates[]=array($d,(int)$map['ma_id']);
            }
        } else foreach(monster_rows('SELECT * FROM `'.$g5['dungeon_table'].'` WHERE dg_use=1 AND dg_per_s<='.$seed.' AND dg_per_e>='.$seed.' ORDER BY RAND() LIMIT '.max(0,(int)$config['cf_dungeon_count'])) as $d)$candidates[]=array($d,0);
        inventory_boundary_query('START TRANSACTION');
        try {
            inventory_boundary_query('UPDATE `'.$g5['dungeon_state_table']."` SET ds_state='E',phase='CLOSED',finished_at=NOW() WHERE snapshot IS NULL AND ds_state<>'E'");
            foreach($candidates as $candidate) {
                list($d,$map)=$candidate;
                inventory_boundary_query('INSERT INTO `'.$g5['dungeon_state_table'].'` (dg_id,dg_title,ds_ma_id,ds_state,ds_datetime,created_at) VALUES ('.(int)$d['dg_id'].','.monster_quote($d['dg_title']).','.$map.",'S',NOW(),NOW())");
            }
            inventory_boundary_query('UPDATE `'.$g5['config_table'].'` SET cf_dungeon_reset='.monster_quote($reset));
            inventory_boundary_query('COMMIT');
        }catch(Throwable $e){sql_query('ROLLBACK',false);throw $e;}
        $config['cf_dungeon_reset']=$reset;
        return $reset;
    }finally{sql_query("SELECT RELEASE_LOCK('".sql_escape_string(G5_TABLE_PREFIX."dungeon_reset")."')",false);}
}

function get_map_dungeon($ma_id) {
	global $character;
	$rows = get_map_open_dungeons($ma_id);
	// Prefer the player's running party, then a waiting opening. Never let an
	// older running instance hide a new opening in the same region.
	foreach($rows as $row) {
		if(!empty($character['ch_id']) && maze_entry_label((int)$row['ds_id'], (int)$character['ch_id']) === '미궁 이어하기') return $row;
	}
	foreach($rows as $row) if(empty($row['snapshot']) || $row['phase'] === 'WAITING') return $row;
	return $rows ? $rows[0] : array();
}

/*
 * Return every currently open dungeon for a map.  When $include_descendants is
 * true, open dungeons in child areas are included as well.  The global map
 * renders parent areas only, so this keeps the map marker in sync with the
 * actual ds_ma_id stored by the dungeon generator.
 */
function get_map_open_dungeons($ma_id, $include_descendants = false) {
	global $g5, $config;

	$ma_id = (int)$ma_id;
	if($ma_id <= 0 || empty($g5['dungeon_state_table']) || empty($g5['dungeon_table'])) {
		return array();
	}

	$map_ids = array($ma_id);
	if($include_descendants && !empty($g5['map_table'])) {
		$map_rows = array();
		$map_sql = sql_query("select ma_id, ma_parent from {$g5['map_table']}", false);
		if($map_sql) {
			for($i = 0; $row = sql_fetch_array($map_sql); $i++) {
				$parent_id = (int)$row['ma_parent'];
				if(!isset($map_rows[$parent_id])) $map_rows[$parent_id] = array();
				$map_rows[$parent_id][] = (int)$row['ma_id'];
			}

			for($i = 0; $i < count($map_ids); $i++) {
				$current_id = (int)$map_ids[$i];
				if(empty($map_rows[$current_id])) continue;
				foreach($map_rows[$current_id] as $child_id) {
					if($child_id !== $current_id && !in_array($child_id, $map_ids)) {
						$map_ids[] = $child_id;
					}
				}
			}
		}
	}

	$map_ids = array_values(array_unique(array_map('intval', $map_ids)));
	if(!count($map_ids)) return array();

	$map_id_sql = implode(',', $map_ids);
	$result = array();
	$location = "ds.ds_ma_id in ({$map_id_sql})";
	if(empty($config['cf_dungeon_map'])) $location .= ' OR ds.ds_ma_id = 0';
	$sql = sql_query("select ds.*, dg.* from {$g5['dungeon_state_table']} ds, {$g5['dungeon_read_table']} dg where ds.dg_id = dg.dg_id and dg.dg_use = 1 and ds.ds_state = 'S' and ({$location}) order by ds.ds_id desc", false);
	if($sql) {
		for($i = 0; $row = sql_fetch_array($sql); $i++) {
			$result[] = $row;
		}
	}
	return $result;
}

function get_dungeon($dg_id) {
	global $g5;

	$result = sql_fetch("select * from {$g5['dungeon_state_table']} ds, {$g5['dungeon_read_table']} dg where ds.dg_id = dg.dg_id and dg.dg_id = '{$dg_id}'");
	return $result;
}

function get_dungeon_state($ds_id) {
    global $g5;
    return sql_fetch('SELECT ds.*,dg.* FROM `'.$g5['dungeon_state_table'].'` ds JOIN `'.$g5['dungeon_read_table'].'` dg ON dg.dg_id=ds.dg_id WHERE ds.ds_id='.(int)$ds_id);
}

function get_dungeon_member($ds_id,$state='S') {
    global $g5;
    if(!maze_installed())return array();
    $where=$state==='S'?" AND m.state='ACTIVE'":($state==='E'?" AND m.state<>'ACTIVE'":'');
    return monster_rows('SELECT m.*,c.ch_name,c.ch_thumb,IF(m.state=\'ACTIVE\',\'S\',\'E\') dm_state FROM `'.maze_table('member').'` m LEFT JOIN `'.$g5['character_table'].'` c ON c.ch_id=m.ch_id WHERE m.ds_id='.(int)$ds_id.$where.' ORDER BY m.dm_id');
}
function get_dungeon_member_count($ds_id) { return count(get_dungeon_member($ds_id)); }
function get_dungeon_character($ds_id,$ch_id) {
    foreach(get_dungeon_member($ds_id,'') as $person)if((int)$person['ch_id']===(int)$ch_id)return $person;
    return array();
}
function is_has_dungeon($ch_id,$not_ds_id=0,$ds_id=0) {
    if(!maze_installed())return false;
    $where=$not_ds_id?' AND ds_id<>'.(int)$not_ds_id:($ds_id?' AND ds_id='.(int)$ds_id:'');
    return (bool)maze_one('SELECT dm_id FROM `'.maze_table('member').'` WHERE ch_id='.(int)$ch_id." AND state='ACTIVE'".$where.' LIMIT 1');
}
