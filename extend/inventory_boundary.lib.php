<?php
/** Inventory write boundaries shared by legacy consumers and labyrinth start.
 * No DDL on load. Mixed-engine effects use a durable journal, never blind retry.
 */
if (!defined('_GNUBOARD_')) exit;
class InventoryStorageException extends RuntimeException {}

function inventory_boundary_token() {
    $token = get_session('ss_inventory_boundary_token');
    if (!is_string($token) || strlen($token) !== 64) {
        $token = bin2hex(random_bytes(32));
        set_session('ss_inventory_boundary_token', $token);
    }
    return $token;
}
function inventory_boundary_check_token() {
    $saved = get_session('ss_inventory_boundary_token');
    $sent = isset($_POST['token']) ? $_POST['token'] : '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($saved) || strlen($saved) !== 64 || !is_string($sent) || !hash_equals($saved, $sent)) throw new RuntimeException('요청 인증에 실패했습니다. 화면을 새로고침해 주세요.');
}

function inventory_boundary_table($name) {
    return G5_TABLE_PREFIX.'inventory_'.$name;
}
function inventory_boundary_query($sql) {
    $result = sql_query($sql, false);
    if ($result === false) throw new InventoryStorageException('아이템 처리에 실패했습니다. 처리 기록을 확인해 주세요.');
    return $result;
}
function inventory_boundary_rows($sql) {
    $result = inventory_boundary_query($sql);
    $rows = array();
    while ($row = sql_fetch_array($result)) $rows[] = $row;
    return $rows;
}
function inventory_boundary_quote($value) {
    return $value === null ? 'NULL' : "'".sql_escape_string((string)$value)."'";
}
function inventory_boundary_json($value) {
    // Base64 keeps even legacy non-UTF8/custom binary column values intact.
    return base64_encode(serialize($value));
}
function inventory_boundary_decode($value) {
    $bytes = base64_decode($value, true);
    if ($bytes === false) throw new RuntimeException('보관 데이터가 손상되었습니다.');
    $data = unserialize($bytes, array('allowed_classes' => false));
    if (!is_array($data)) throw new RuntimeException('보관 데이터를 확인할 수 없습니다.');
    return $data;
}
function inventory_boundary_readiness() {
    global $g5;
    static $result = null;
    if ($result !== null) return $result;
    $tables = array($g5['inventory_table'], inventory_boundary_table('guard'), inventory_boundary_table('journal'));
    $quoted = array_map('inventory_boundary_quote', $tables);
    $query = sql_query('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.implode(',', $quoted).')', false);
    $engines = array();
    if ($query) while ($row = sql_fetch_array($query)) $engines[$row['TABLE_NAME']] = strtoupper($row['ENGINE']);
    $errors = array();
    foreach ($tables as $table) if (!isset($engines[$table]) || $engines[$table] !== 'INNODB') $errors[] = $table.': InnoDB migration 필요';
    if (!$errors) {
        $inventory_columns = inventory_boundary_rows('SHOW COLUMNS FROM `'.$g5['inventory_table'].'`');
        $has_identity = false;
        foreach ($inventory_columns as $column) if ($column['Field'] === 'in_id' && $column['Key'] === 'PRI' && strpos($column['Extra'],'auto_increment') !== false) $has_identity = true;
        if (!$has_identity) $errors[] = $g5['inventory_table'].': in_id AUTO_INCREMENT PRIMARY KEY 필요';
        $primary = array();
        foreach (inventory_boundary_rows('SHOW INDEX FROM `'.$g5['inventory_table'].'`') as $index) if ($index['Key_name']==='PRIMARY') $primary[(int)$index['Seq_in_index']]=$index['Column_name'];
        ksort($primary);
        if (array_values($primary)!==array('in_id')) $errors[]=$g5['inventory_table'].': 단일 in_id 기본키 필요';
        $owner_index = false;
        foreach (inventory_boundary_rows('SHOW INDEX FROM `'.$g5['inventory_table'].'`') as $index) if ((int)$index['Seq_in_index'] === 1 && $index['Column_name'] === 'ch_id' && $index['Sub_part'] === null) $owner_index = true;
        if (!$owner_index) $errors[] = $g5['inventory_table'].': ch_id 선두 인덱스 필요 (004 수동 migration)';
        $required = array('guard' => array('ch_id','active_journal'), 'journal' => array('journal_id','ch_id','mb_id','operation','state','request_key','originals','intent','result_note','created_at','finished_at'));
        foreach ($required as $name => $columns) {
            $table = inventory_boundary_table($name);
            $fields = array();
            $query = sql_query("SHOW COLUMNS FROM `{$table}`", false);
            if ($query) while ($row = sql_fetch_array($query)) $fields[] = $row['Field'];
            if (array_diff($columns, $fields)) $errors[] = $table.': schema migration 미완료';
            $indexes = array();
            $query = sql_query("SHOW INDEX FROM `{$table}`", false);
            if ($query) while ($row = sql_fetch_array($query)) {
                if (!(int)$row['Non_unique']) $indexes[$row['Key_name']][(int)$row['Seq_in_index']] = $row['Column_name'];
            }
            foreach ($indexes as &$index) { ksort($index); $index = array_values($index); } unset($index);
            if ($name==='journal' && !in_array(array('journal_id'),$indexes,true)) $errors[]=$table.': journal identity index 누락';
            $key = $name === 'guard' ? array('ch_id') : array('request_key');
            if (!in_array($key, $indexes, true)) $errors[] = $table.': unique index 누락';
        }
    }
    $result = array('ready' => !$errors, 'errors' => $errors);
    return $result;
}
function inventory_boundary_require_ready() {
    $check = inventory_boundary_readiness();
    if (!$check['ready']) throw new RuntimeException('아이템 안전 처리 DB migration이 적용되지 않았습니다. 관리자에게 문의해 주세요.');
}
function inventory_boundary_owner($ch_id) {
    global $member, $character;
    $ch_id = (int)$ch_id;
    if (!$ch_id || empty($member['mb_id']) || empty($character['ch_id']) || (int)$character['ch_id'] !== $ch_id) {
        throw new RuntimeException('현재 캐릭터의 아이템만 처리할 수 있습니다.');
    }
    $owner = get_character($ch_id);
    if (!$owner || $owner['mb_id'] !== $member['mb_id']) throw new RuntimeException('아이템 소유권을 확인할 수 없습니다.');
    return $owner;
}
/** Caller must own an open transaction. Acquire owner guards in ascending order. */
function inventory_boundary_lock_owners($ids, $parent_journal = 0) {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    sort($ids, SORT_NUMERIC);
    $table = inventory_boundary_table('guard');
    foreach ($ids as $id) {
        if ($id <= 0) throw new RuntimeException('잘못된 캐릭터입니다.');
        inventory_boundary_query("INSERT INTO `{$table}` (ch_id) VALUES ({$id}) ON DUPLICATE KEY UPDATE ch_id=VALUES(ch_id)");
        $rows = inventory_boundary_rows("SELECT * FROM `{$table}` WHERE ch_id={$id} FOR UPDATE");
        if (!empty($rows[0]['active_journal']) && (int)$rows[0]['active_journal'] !== (int)$parent_journal) throw new RuntimeException('이 캐릭터의 이전 아이템 처리가 진행 중이거나 확인을 기다리고 있습니다.');
    }
}

/** Returning an original quest item is a transfer, not a newly generated reward.
 * Keep its source row in the same durable journal before mixed-engine effects.
 */
function inventory_boundary_quest_release($qu_id, $reward) {
    global $g5, $character, $member;
    inventory_boundary_require_ready();
    if ($reward) inventory_boundary_owner((int)$character['ch_id']);
    $qu_id = (int)$qu_id;
    $source_sql = "SELECT * FROM `{$g5['k_quest_inven_table']}` WHERE qu_id={$qu_id} ORDER BY in_id".($reward ? ' LIMIT 1' : '');
    $preview = inventory_boundary_rows($source_sql);
    if (!$preview) return null;
    $owners = array_map('intval', array_column($preview, 'ch_id'));
    if ($reward) $owners[] = (int)$character['ch_id'];
    $parent = 0;
    if (!empty($GLOBALS['inventory_boundary_pending'])) {
        if (count($GLOBALS['inventory_boundary_pending']) !== 1) throw new RuntimeException('기존 아이템 처리가 완료되지 않았습니다.');
        if (in_array('ATOMIC', $GLOBALS['inventory_boundary_pending'], true)) throw new RuntimeException('진행 중인 원자적 소비 작업에 퀘스트 이동을 추가할 수 없습니다.');
        $parent = (int)key($GLOBALS['inventory_boundary_pending']);
    }
    inventory_boundary_query('START TRANSACTION');
    try {
        inventory_boundary_lock_owners($owners, $parent);
        $rows = inventory_boundary_rows($source_sql);
        if (array_column($rows, 'in_id') !== array_column($preview, 'in_id')) throw new RuntimeException('퀘스트 아이템이 다른 요청에서 먼저 처리되었습니다.');
        $originals = array();
        foreach ($rows as $row) $originals[] = array('row' => $row, 'deleted' => false, 'source' => 'quest');
        $journal = inventory_boundary_table('journal');
        if ($parent) {
            $saved = inventory_boundary_rows("SELECT originals FROM `{$journal}` WHERE journal_id={$parent} AND state='PROCESSING' FOR UPDATE");
            if (!$saved) throw new RuntimeException('원본 처리 기록이 없습니다.');
            $packed = inventory_boundary_json(array_merge(inventory_boundary_decode($saved[0]['originals']), $originals));
            inventory_boundary_query("UPDATE `{$journal}` SET originals=".inventory_boundary_quote($packed)." WHERE journal_id={$parent}");
            $id = $parent;
        } else {
            $intent = inventory_boundary_json(array('qu_id' => $qu_id, 'reward' => (bool)$reward, 'receiver_ch_id' => $reward ? (int)$character['ch_id'] : null));
            inventory_boundary_query("INSERT INTO `{$journal}` (ch_id,mb_id,operation,state,request_key,originals,intent,result_note,created_at) VALUES (".(int)$owners[0].','.inventory_boundary_quote(isset($member['mb_id']) ? $member['mb_id'] : '').",'quest.release','PROCESSING','".hash('sha256',random_bytes(32))."',".inventory_boundary_quote(inventory_boundary_json($originals)).','.inventory_boundary_quote($intent).",'',NOW())");
            $id = (int)sql_insert_id();
        }
        inventory_boundary_query('UPDATE `'.inventory_boundary_table('guard').'` SET active_journal='.$id.' WHERE ch_id IN ('.implode(',',array_unique($owners)).')');
        inventory_boundary_query('COMMIT');
        $GLOBALS['inventory_boundary_pending'][$id] = true;
        return array('journal_id' => $id, 'ch_id' => (int)$owners[0], 'rows' => $rows, 'parent' => (bool)$parent);
    } catch (Throwable $error) { sql_query('ROLLBACK',false); throw $error; }
}
/** No effects here; fresh original rows only, never trust client row data. */
function inventory_boundary_lock_rows($ch_id, $ids) {
    global $g5;
    $ids = array_map('intval', $ids);
    if (!$ids || min($ids) <= 0 || count($ids) !== count(array_unique($ids))) throw new RuntimeException('아이템 목록이 올바르지 않습니다.');
    sort($ids, SORT_NUMERIC);
    $rows = inventory_boundary_rows("SELECT * FROM `{$g5['inventory_table']}` WHERE in_id IN (".implode(',', $ids).') ORDER BY in_id ASC FOR UPDATE');
    if (count($rows) !== count($ids)) throw new RuntimeException('이미 사용되었거나 던전에 보관된 아이템입니다.');
    foreach ($rows as $row) if ((int)$row['ch_id'] !== (int)$ch_id) throw new RuntimeException('현재 캐릭터 소유의 아이템이 아닙니다.');
    return $rows;
}
/**
 * Select all material rows before removing any. $needs is [item ID => count].
 * Call inside the same transaction and after locking the owner guard.
 */
function inventory_boundary_material_rows($ch_id, $needs, $ungifted = false) {
    global $g5;
    $ch_id = (int)$ch_id;
    $item_ids = array_map('intval', array_keys($needs));
    if (!$item_ids || min($item_ids) <= 0) throw new RuntimeException('재료 설정이 올바르지 않습니다.');
    $filter = $ungifted ? " AND se_ch_id=''" : '';
    $rows = inventory_boundary_rows("SELECT * FROM `{$g5['inventory_table']}` WHERE ch_id='{$ch_id}' AND it_id IN (".implode(',', $item_ids)."){$filter} ORDER BY in_id ASC FOR UPDATE");
    $selected = array();
    foreach ($rows as $row) {
        $id = (int)$row['it_id'];
        if (isset($needs[$id]) && $needs[$id] > 0) { $selected[] = $row; $needs[$id]--; }
    }
    foreach ($needs as $count) if ($count != 0) throw new RuntimeException('필요한 재료가 부족하거나 다른 요청에서 먼저 처리되었습니다.');
    return $selected;
}
function inventory_boundary_equipment($rows) {
    global $g5;
    $result = array();
    if (!$rows || empty($g5['k_ch_equip_table'])) return $result;
    $ids = array_map('intval', array_column($rows, 'in_id'));
    foreach (inventory_boundary_rows('SELECT * FROM `'.$g5['k_ch_equip_table'].'` WHERE in_id IN ('.implode(',', $ids).')') as $equipment) $result[(int)$equipment['in_id']][] = $equipment;
    return $result;
}
function inventory_boundary_all_innodb($tables) {
    if (!$tables) return false;
    $tables=array_values(array_unique($tables));sort($tables);
    static $cache=array();$key=implode('|',$tables);
    if(isset($cache[$key]))return $cache[$key];
    $rows=inventory_boundary_rows('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.implode(',',array_map('inventory_boundary_quote',$tables)).')');
    $ready=count($rows)===count($tables);
    foreach($rows as $row)if(strtoupper($row['ENGINE'])!=='INNODB')$ready=false;
    return $cache[$key]=$ready;
}
/**
 * Commit consumption and the complete originals before mixed-engine effects.
 * The owner guard remains marked until explicit completion. Fatal/partial effect
 * failures leave REVIEW instead of refunding potentially rewarded items.
 * $mode: consume respects it_use_ever; remove deletes even permanent items.
 */
function inventory_boundary_begin($ch_id, $ids, $operation, $intent = array(), $mode = 'consume', $needs = array(), $ungifted = false) {
    global $g5, $member;
    inventory_boundary_require_ready();
    inventory_boundary_owner($ch_id);
    if (!empty($GLOBALS['inventory_boundary_pending']) && in_array('ATOMIC', $GLOBALS['inventory_boundary_pending'], true)) throw new RuntimeException('진행 중인 아이템 작업을 먼저 완료해야 합니다.');
    $ch_id = (int)$ch_id;
    $journal = inventory_boundary_table('journal');
    $guard = inventory_boundary_table('guard');
    inventory_boundary_query('START TRANSACTION');
    try {
        $guard_owners = array($ch_id);
        if ($mode === 'hold' && !empty($intent['receiver_ch_id'])) $guard_owners[] = (int)$intent['receiver_ch_id'];
        inventory_boundary_lock_owners($guard_owners);
        $hold_ids = isset($intent['hold_ids']) ? array_map('intval',$intent['hold_ids']) : array();
        if ($needs && $hold_ids) throw new RuntimeException('재료와 보유 대상 잠금 설정을 확인해 주세요.');
        $locked_rows = $needs ? inventory_boundary_material_rows($ch_id, $needs, $ungifted) : inventory_boundary_lock_rows($ch_id, array_merge($ids,$hold_ids));
        $rows = array();
        foreach ($locked_rows as $row) if (!in_array((int)$row['in_id'],$hold_ids,true)) $rows[]=$row;
        $originals = array(); $items = array();
        $equipment = inventory_boundary_equipment($locked_rows);
        foreach ($locked_rows as $row) {
            $it_id = (int)$row['it_id'];
            if (!isset($items[$it_id])) $items[$it_id] = get_item($it_id);
            if (empty($items[$it_id]['it_id'])) throw new RuntimeException('아이템 정의가 없습니다.');
            $held = in_array((int)$row['in_id'],$hold_ids,true);
            if (!$held && !empty($row['in_use'])) throw new RuntimeException('이미 사용 중인 아이템입니다.');
            if (!$held && isset($intent['expected_it_id']) && $it_id !== (int)$intent['expected_it_id']) throw new RuntimeException('필요한 재료 아이템이 아닙니다.');
            if (!$held && isset($intent['required_type']) && $items[$it_id]['it_type'] !== $intent['required_type']) throw new RuntimeException('사용할 수 없는 아이템 종류입니다.');
            $originals[] = array('row' => $row, 'item' => $items[$it_id], 'equipment' => isset($equipment[(int)$row['in_id']]) ? $equipment[(int)$row['in_id']] : array(), 'deleted' => !$held && $mode !== 'hold' && ($mode === 'remove' || empty($items[$it_id]['it_use_ever'])));
        }
        $request_key = isset($intent['idempotency_key'])
            ? hash('sha256', $ch_id.'|'.$operation.'|'.$intent['idempotency_key'])
            : hash('sha256', random_bytes(32));
        $packed = inventory_boundary_quote(inventory_boundary_json($originals));
        $intent_sql = inventory_boundary_quote(inventory_boundary_json($intent));
        $operation_sql = inventory_boundary_quote(substr($operation, 0, 100));
        $mb_sql = inventory_boundary_quote($member['mb_id']);
        inventory_boundary_query("INSERT INTO `{$journal}` (ch_id,mb_id,operation,state,request_key,originals,intent,result_note,created_at) VALUES ({$ch_id},{$mb_sql},{$operation_sql},'PROCESSING','{$request_key}',{$packed},{$intent_sql},'',NOW())");
        $journal_id = (int)sql_insert_id();
        $delete_ids = array();
        foreach ($originals as $original) if ($original['deleted']) $delete_ids[] = (int)$original['row']['in_id'];
        if ($delete_ids) inventory_boundary_query("DELETE FROM `{$g5['inventory_table']}` WHERE in_id IN (".implode(',', $delete_ids).") AND ch_id='{$ch_id}'");
        inventory_boundary_query("UPDATE `{$guard}` SET active_journal={$journal_id} WHERE ch_id IN (".implode(',', array_map('intval', $guard_owners)).')');
        $atomic = !$equipment && !empty($intent['transactional_effect_tables']) && inventory_boundary_all_innodb($intent['transactional_effect_tables']);
        if (!$atomic) inventory_boundary_query('COMMIT');
        $GLOBALS['inventory_boundary_pending'][$journal_id] = $atomic ? 'ATOMIC' : 'JOURNAL';
        foreach ($delete_ids as $id) $GLOBALS['inventory_boundary_consumed'][$id] = true;
        return array('journal_id' => $journal_id, 'ch_id' => $ch_id, 'rows' => $rows, 'items' => $items, 'deleted_ids' => $delete_ids);
    } catch (Throwable $error) {
        sql_query('ROLLBACK', false);
        throw $error;
    }
}
function inventory_boundary_done($claim, $note = '') {
    global $g5;
    $id = (int)$claim['journal_id']; $ch_id = (int)$claim['ch_id'];
    $journal = inventory_boundary_table('journal'); $guard = inventory_boundary_table('guard');
    $atomic = isset($GLOBALS['inventory_boundary_pending'][$id]) && $GLOBALS['inventory_boundary_pending'][$id] === 'ATOMIC';
    if (!$atomic) inventory_boundary_query('START TRANSACTION');
    try {
        inventory_boundary_rows("SELECT ch_id FROM `{$guard}` WHERE active_journal={$id} ORDER BY ch_id FOR UPDATE");
        $current = inventory_boundary_rows("SELECT state FROM `{$journal}` WHERE journal_id={$id} AND ch_id={$ch_id} FOR UPDATE");
        if (!$current || $current[0]['state'] !== 'PROCESSING') throw new RuntimeException('완료할 수 없는 아이템 처리 상태입니다.');
        if (!empty($claim['deleted_ids']) && !empty($g5['k_ch_equip_table'])) {
            inventory_boundary_query('DELETE FROM `'.$g5['k_ch_equip_table'].'` WHERE in_id IN ('.implode(',',array_map('intval',$claim['deleted_ids'])).')');
        }
        inventory_boundary_query("UPDATE `{$journal}` SET state='DONE',finished_at=NOW(),result_note=".inventory_boundary_quote($note)." WHERE journal_id={$id} AND ch_id={$ch_id} AND state='PROCESSING'");
        inventory_boundary_query("UPDATE `{$guard}` SET active_journal=NULL WHERE active_journal={$id}");
        inventory_boundary_query('COMMIT');
        unset($GLOBALS['inventory_boundary_pending'][$id]);
    } catch (Throwable $error) { sql_query('ROLLBACK', false); throw $error; }
}

/** A narrow deletion boundary for existing administration/character deletion. */
function inventory_boundary_delete_scope($column, $ids) {
    global $g5, $is_admin, $member;
    inventory_boundary_require_ready();
    if (!in_array($column,array('in_id','it_id','ch_id'),true)) throw new RuntimeException('삭제 대상 조건이 잘못되었습니다.');
    $ids=array_values(array_unique(array_map('intval',$ids)));
    if (!$ids || min($ids)<1) throw new RuntimeException('삭제 대상이 없습니다.');
    if (!$is_admin) {
        if ($column!=='ch_id' || count($ids)!==1) throw new RuntimeException('관리자 권한이 필요합니다.');
        inventory_boundary_owner($ids[0]);
    }
    $filter='`'.$column.'` IN ('.implode(',',array_map('inventory_boundary_quote',$ids)).')';
    $source="SELECT * FROM `{$g5['inventory_table']}` WHERE {$filter} ORDER BY in_id";
    $preview=inventory_boundary_rows($source); $owners=array_map('intval',array_column($preview,'ch_id'));
    if ($column==='ch_id') $owners=array_merge($owners,$ids);
    if (function_exists('maze_installed') && maze_installed()) {
        $escrow_column=$column==='in_id'?'original_id':$column;
        $escrow_filter='`'.$escrow_column.'` IN ('.implode(',',$ids).') AND restored=0';
        $escrow=inventory_boundary_rows('SELECT ch_id FROM `'.maze_table('inventory').'` WHERE '.$escrow_filter);
        $owners=array_merge($owners,array_map('intval',array_column($escrow,'ch_id')));
    }
    $owners=array_values(array_unique($owners)); sort($owners,SORT_NUMERIC);
    if (!$owners) return null;
    inventory_boundary_query('START TRANSACTION');
    try {
        inventory_boundary_lock_owners($owners);
        if (isset($escrow_filter) && maze_one('SELECT inventory_id FROM `'.maze_table('inventory').'` WHERE '.$escrow_filter.' LIMIT 1')) throw new RuntimeException('미궁에 보관된 아이템이 있습니다. 정산을 완료한 뒤 삭제해 주세요.');
        if ($column==='ch_id' && function_exists('maze_installed') && maze_installed() && maze_one('SELECT dm_id FROM `'.maze_table('member').'` WHERE ch_id IN ('.implode(',',$ids).") AND state='ACTIVE' LIMIT 1")) throw new RuntimeException('참가 중인 미궁에서 먼저 퇴장해 주세요.');
        $rows=inventory_boundary_rows($source.' FOR UPDATE');
        if (array_diff(array_map('intval',array_column($rows,'ch_id')),$owners)) throw new RuntimeException('아이템 소유자가 변경됐습니다. 다시 확인해 주세요.');
        $originals=array(); $items=array(); $equipment=inventory_boundary_equipment($rows);
        foreach($rows as $row) {
            $item_id=(int)$row['it_id']; if(!isset($items[$item_id]))$items[$item_id]=get_item($item_id);
            $originals[]=array('row'=>$row,'item'=>$items[$item_id],'equipment'=>isset($equipment[(int)$row['in_id']])?$equipment[(int)$row['in_id']]:array(),'deleted'=>true);
        }
        $journal=inventory_boundary_table('journal');
        inventory_boundary_query("INSERT INTO `{$journal}` (ch_id,mb_id,operation,state,request_key,originals,intent,result_note,created_at) VALUES (".$owners[0].','.inventory_boundary_quote($member['mb_id']).",'administration.delete','PROCESSING','".hash('sha256',random_bytes(32))."',".inventory_boundary_quote(inventory_boundary_json($originals)).','.inventory_boundary_quote(inventory_boundary_json(array('column'=>$column,'ids'=>$ids))).",'',NOW())");
        $id=(int)sql_insert_id(); $delete_ids=array_map('intval',array_column($rows,'in_id'));
        if($delete_ids) inventory_boundary_query("DELETE FROM `{$g5['inventory_table']}` WHERE in_id IN (".implode(',',$delete_ids).')');
        inventory_boundary_query('UPDATE `'.inventory_boundary_table('guard').'` SET active_journal='.$id.' WHERE ch_id IN ('.implode(',',$owners).')');
        inventory_boundary_query('COMMIT'); $GLOBALS['inventory_boundary_pending'][$id]=true;
        return array('journal_id'=>$id,'ch_id'=>$owners[0],'deleted_ids'=>$delete_ids);
    } catch(Throwable $error) {sql_query('ROLLBACK',false);throw $error;}
}
function inventory_boundary_delete_or_alert($column, $ids) {
    try {return inventory_boundary_delete_scope($column,$ids);}
    catch(Throwable $error){alert($error->getMessage());exit;}
}
function inventory_boundary_shutdown() {
    if (empty($GLOBALS['inventory_boundary_pending'])) return;
    $pending = $GLOBALS['inventory_boundary_pending'];
    $GLOBALS['inventory_boundary_pending'] = array();
    $table = inventory_boundary_table('journal');
    foreach ($pending as $id => $unused) {
        $id = (int)$id;
        if ($unused === 'ATOMIC') { sql_query('ROLLBACK', false); continue; }
        sql_query("UPDATE `{$table}` SET state='REVIEW',result_note='효과 완료가 확인되지 않았습니다. 원본과 처리 결과를 확인한 뒤 보정하세요.' WHERE journal_id={$id} AND state='PROCESSING'", false);
    }
}
register_shutdown_function('inventory_boundary_shutdown');

function inventory_boundary_restore_row($row) {
    global $g5;
    $columns = array(); $values = array();
    foreach ($row as $column => $value) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $column)) throw new RuntimeException('원본 컬럼을 확인할 수 없습니다.');
        $columns[] = '`'.$column.'`'; $values[] = inventory_boundary_quote($value);
    }
    // Deliberately no REPLACE/IGNORE: a conflicting ID must preserve the escrow.
    inventory_boundary_query("INSERT INTO `{$g5['inventory_table']}` (".implode(',', $columns).') VALUES ('.implode(',', $values).')');
}

/** Full original columns and explicit IDs; a conflict fails the entire transaction. */
function inventory_boundary_restore_rows($rows) {
    global $g5;
    $groups=array();
    foreach($rows as $row) {
        foreach(array_keys($row) as $column) if(!preg_match('/^[a-zA-Z0-9_]+$/D',$column)) throw new RuntimeException('원본 컬럼을 확인할 수 없습니다.');
        $key=implode('`,`',array_keys($row));$groups[$key][]=$row;
    }
    foreach($groups as $columns=>$group) foreach(array_chunk($group,100) as $chunk) {
        $values=array();foreach($chunk as $row)$values[]='('.implode(',',array_map('inventory_boundary_quote',array_values($row))).')';
        inventory_boundary_query('INSERT INTO `'.$g5['inventory_table'].'` (`'.$columns.'`) VALUES '.implode(',',$values));
    }
}
/** Administrator confirms the actual external effect before recovery. */
function inventory_boundary_reconcile($id, $restore, $note) {
    global $is_admin, $member, $g5;
    if (!$is_admin || trim($note) === '') throw new RuntimeException('관리자 확인 사유가 필요합니다.');
    inventory_boundary_require_ready();
    $id = (int)$id;
    $journal = inventory_boundary_table('journal'); $guard = inventory_boundary_table('guard');
    inventory_boundary_query('START TRANSACTION');
    try {
        inventory_boundary_rows("SELECT ch_id FROM `{$guard}` WHERE active_journal={$id} ORDER BY ch_id FOR UPDATE");
        $rows = inventory_boundary_rows("SELECT * FROM `{$journal}` WHERE journal_id={$id} FOR UPDATE");
        if (!$rows || $rows[0]['state'] !== 'REVIEW') throw new RuntimeException('확인 대기 중인 처리 기록만 보정할 수 있습니다.');
        if ($restore) {
            $originals = inventory_boundary_decode($rows[0]['originals']);
            foreach ($originals as $original) {
                if (!$original['deleted']) throw new RuntimeException('영구 아이템/이동 작업은 자동 복원이 불가능합니다. 실제 효과와 소유자를 확인해 완료 처리하세요.');
            }
            foreach ($originals as $original) {
                if (!get_item((int)$original['row']['it_id'])) throw new RuntimeException('아이템 정의가 삭제되었습니다. 정의를 먼저 복구해 주세요.');
                inventory_boundary_restore_row($original['row']);
                if (!empty($original['equipment'])) foreach ($original['equipment'] as $equipment) {
                    $existing = inventory_boundary_rows('SELECT * FROM `'.$g5['k_ch_equip_table'].'` WHERE in_id='.(int)$equipment['in_id']);
                    if ($existing) {
                        if (!in_array($equipment, $existing, true)) throw new RuntimeException('장비 개별 상태가 변경되었습니다. 관리자 확인이 필요합니다.');
                    } else {
                        $columns = array(); $values = array();
                        foreach ($equipment as $key => $value) {
                            if (!preg_match('/^[a-zA-Z0-9_]+$/D',$key)) throw new RuntimeException('장비 컬럼이 올바르지 않습니다.');
                            $columns[]='`'.$key.'`';$values[]=inventory_boundary_quote($value);
                        }
                        inventory_boundary_query('INSERT INTO `'.$g5['k_ch_equip_table'].'` ('.implode(',',$columns).') VALUES ('.implode(',',$values).')');
                    }
                }
            }
        }
        $state = $restore ? 'RESTORED' : 'DONE';
        inventory_boundary_query("UPDATE `{$journal}` SET state='{$state}',finished_at=NOW(),result_note=".inventory_boundary_quote($member['mb_id'].': '.$note)." WHERE journal_id={$id}");
        inventory_boundary_query("UPDATE `{$guard}` SET active_journal=NULL WHERE active_journal={$id}");
        inventory_boundary_query('COMMIT');
    } catch (Throwable $error) { sql_query('ROLLBACK', false); throw $error; }
}

/** Small endpoint adapter: legacy alert/redirect responses remain usable. */
function inventory_boundary_begin_or_alert($ids, $operation, $intent = array(), $mode = 'consume', $needs = array(), $ungifted = false) {
    global $character;
    try { return inventory_boundary_begin((int)$character['ch_id'], $ids, $operation, $intent, $mode, $needs, $ungifted); }
    catch (Throwable $error) { alert($error->getMessage()); exit; }
}
