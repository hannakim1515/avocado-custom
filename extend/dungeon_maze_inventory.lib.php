<?php
if (!defined('_GNUBOARD_')) exit;

/** Caller holds the session row and ALL participant owner guards in its transaction. */
function maze_inventory_isolate($session, $participants) {
    global $g5;
    $owners = array(); $by_owner = array();
    foreach ($participants as $participant) {
        $owners[] = (int)$participant['ch_id'];
        $by_owner[(int)$participant['ch_id']] = $participant;
    }
    if (!$owners) throw new RuntimeException('참가자가 없습니다.');
    sort($owners, SORT_NUMERIC);
    // Lock the original rows first; definitions/equipment are read only afterwards.
    // This is the same PK order as external multi-item consumption.
    $owner_sql = implode(',', array_map('inventory_boundary_quote', $owners));
    $rows = inventory_boundary_rows("SELECT i.* FROM `{$g5['inventory_table']}` i JOIN `{$g5['item_table']}` it ON it.it_id=i.it_id WHERE i.ch_id IN ({$owner_sql}) AND i.in_use='' AND it.it_use_battle_able=1 AND it.it_type<>'장비(K)' ORDER BY i.in_id FOR UPDATE");
    if (!$rows) return;
    $item_ids = array_unique(array_map('intval', array_column($rows, 'it_id')));
    $items = array();
    foreach (inventory_boundary_rows("SELECT * FROM `{$g5['item_table']}` WHERE it_id IN (".implode(',', $item_ids).')') as $item) $items[(int)$item['it_id']] = $item;
    $ids = array_map('intval', array_column($rows, 'in_id'));
    $equipment = array();
    // Exclude every equipment reference, including temporarily unequipped gear.
    foreach (inventory_boundary_rows("SELECT in_id FROM `{$g5['k_ch_equip_table']}` WHERE in_id IN (".implode(',', $ids).')') as $row) $equipment[(int)$row['in_id']] = true;
    $effects = array();
    foreach (inventory_boundary_rows('SELECT * FROM `'.maze_table('item_effect').'` WHERE it_id IN ('.implode(',', $item_ids).')') as $row) $effects[(int)$row['it_id']] = $row;
    $remove = array(); $escrow = array();
    foreach ($rows as $row) {
        $id = (int)$row['in_id']; $it_id = (int)$row['it_id'];
        if (!empty($row['in_use']) || isset($equipment[$id]) || empty($items[$it_id]['it_use_battle_able']) || $items[$it_id]['it_type'] === '장비(K)') continue;
        if ($items[$it_id]['it_type'] !== '스탯회복' && !isset($effects[$it_id])) continue;
        $actor = $by_owner[(int)$row['ch_id']];
        $snapshot = $items[$it_id];
        $snapshot['maze_effect'] = isset($effects[$it_id]) ? $effects[$it_id] : null;
        $escrow[] = array('ds_id' => $session['ds_id'], 'dm_id' => $actor['dm_id'], 'ch_id' => $actor['ch_id'],
            'it_id' => $it_id, 'origin' => 'ORIGINAL', 'original_id' => $id, 'active_original_id' => $id,
            'original_row' => inventory_boundary_json($row), 'item_snapshot' => inventory_boundary_json($snapshot));
        $remove[] = $id;
    }
    maze_insert_many('inventory',$escrow);
    if ($remove) inventory_boundary_query("DELETE FROM `{$g5['inventory_table']}` WHERE in_id IN (".implode(',', $remove).')');
}

/** Newly found items never enter the ordinary inventory before settlement. */
function maze_inventory_gain($session, $participant, $item, $count = 1) {
    if ($count < 1 || $count > 100 || empty($item['it_id'])) throw new RuntimeException('획득 아이템 설정이 잘못되었습니다.');
    static $effect_cache = array();
    $key = (int)$item['it_id'];
    if (!array_key_exists($key,$effect_cache)) $effect_cache[$key] = maze_one('SELECT * FROM `'.maze_table('item_effect').'` WHERE it_id='.$key);
    $effect = $effect_cache[$key];
    $item['maze_effect'] = $effect;
    $gains=array();
    for ($i = 0; $i < $count; $i++) $gains[]=array('ds_id' => $session['ds_id'], 'dm_id' => $participant['dm_id'],
        'ch_id' => $participant['ch_id'], 'it_id' => $item['it_id'], 'origin' => 'FOUND',
        'original_row' => inventory_boundary_json(array()), 'item_snapshot' => inventory_boundary_json($item));
    maze_insert_many('inventory',$gains);
}

/** Session lock serializes actions; explicit ownership is still mandatory. */
function maze_inventory_owned($session, $actor, $inventory_id) {
    $row = maze_one('SELECT * FROM `'.maze_table('inventory').'` WHERE inventory_id='.(int)$inventory_id.' FOR UPDATE');
    if (!$row || (int)$row['ds_id'] !== (int)$session['ds_id'] || (int)$row['dm_id'] !== (int)$actor['dm_id']
        || (int)$row['ch_id'] !== (int)$actor['ch_id'] || $row['consumed'] || $row['restored']) {
        throw new RuntimeException('현재 참가자의 사용 가능한 미궁 아이템이 아닙니다.');
    }
    return $row;
}

/** Caller holds session + sorted owner guards. All exit reasons use this function. */
function maze_settle_member($session, $participant, $reason, $clear_reward = false) {
    $id = (int)$participant['dm_id'];
    $current = maze_one('SELECT * FROM `'.maze_table('member').'` WHERE dm_id='.$id.' AND ds_id='.(int)$session['ds_id'].' FOR UPDATE');
    if (!$current) throw new RuntimeException('정산할 참가자가 없습니다.');
    if ($current['settled']) return false;
    $rows = inventory_boundary_rows('SELECT * FROM `'.maze_table('inventory').'` WHERE ds_id='.(int)$session['ds_id'].' AND dm_id='.$id.' AND restored=0 ORDER BY inventory_id FOR UPDATE');
    $restore_rows = array();
    foreach ($rows as $row) {
        if (!$row['consumed']) {
            if ($row['origin'] === 'ORIGINAL') {
                $original = inventory_boundary_decode($row['original_row']);
                if ((int)$original['in_id'] !== (int)$row['original_id'] || (int)$original['ch_id'] !== (int)$current['ch_id']) throw new RuntimeException('원본 아이템의 정합성을 확인할 수 없습니다.');
                $restore_rows[]=$original;
            } elseif ($row['origin'] === 'FOUND') {
                $item = inventory_boundary_decode($row['item_snapshot']);
                $restore_rows[]=array('ch_id' => $current['ch_id'], 'ch_name' => $current['name'], 'it_id' => $row['it_id'], 'it_name' => $item['it_name']);
            } else throw new RuntimeException('아이템 출처를 확인할 수 없습니다.');
        }
    }
    inventory_boundary_restore_rows($restore_rows);
    maze_update('inventory', array('restored' => 1, 'active_original_id' => null), 'ds_id='.(int)$session['ds_id'].' AND dm_id='.$id.' AND restored=0');
    $reward_summary = array();
    $eligible = $clear_reward && (int)$current['hp'] > 0;
    if ($eligible) {
        $settings = maze_data($session['snapshot']);
        // A removed catalog definition must not become an invisible reward row.
        // Cache the same party-wide reward check within this request only.
        global $g5;
        static $reward_catalog = array();
        $reward_ids=array_unique(array_map('intval',array_column($settings['rewards'],'it_id')));
        $catalog_key=implode(',',$reward_ids);
        if($reward_ids && !isset($reward_catalog[$catalog_key])) {
            $live=inventory_boundary_rows('SELECT it_id FROM `'.$g5['item_table'].'` WHERE it_id IN ('.$catalog_key.')');
            if(count($live)!==count($reward_ids)) throw new RuntimeException('클리어 보상 아이템 정의가 삭제되었습니다. 관리자 확인 후 다시 정산해 주세요.');
            $reward_catalog[$catalog_key]=true;
        }
        foreach ($settings['rewards'] as $reward) {
            if (random_int(1, 10000) > (int)round($reward['percent'] * 100)) continue;
            $item = isset($settings['reward_items'][(int)$reward['it_id']]) ? $settings['reward_items'][(int)$reward['it_id']] : get_item((int)$reward['it_id']);
            if (empty($item['it_id'])) throw new RuntimeException('클리어 보상 아이템 정의가 없습니다.');
            $reward_summary[] = $item['it_name'].' × '.(int)$reward['count'];
            inventory_boundary_restore_rows(array_fill(0,(int)$reward['count'],array('ch_id' => $current['ch_id'], 'ch_name' => $current['name'], 'it_id' => $item['it_id'], 'it_name' => $item['it_name'])));
        }
        if ((int)$settings['points'] > 0) maze_insert('reward', array('ds_id' => $session['ds_id'], 'dm_id' => $id, 'reward_type' => 'POINT', 'mb_id' => $current['mb_id'], 'amount' => (int)$settings['points']));
    }
    maze_update('member', array('settled' => 1, 'state' => $reason, 'reward_eligible' => (int)$eligible, 'exited_at' => date('Y-m-d H:i:s')), 'dm_id='.$id);
    maze_log($session['ds_id'], $id, 'SETTLEMENT', $reason.($eligible ? ' · 클리어 보상: '.implode(', ',$reward_summary).' · 포인트 '.(int)$settings['points'] : ' · 클리어 보상 없음'));
    return true;
}

/** Run AFTER gameplay COMMIT. The journal row serializes deterministic point keys. */
function maze_pay_rewards($ds_id) {
    $pending = inventory_boundary_rows('SELECT dm_id FROM `'.maze_table('reward').'` WHERE ds_id='.(int)$ds_id." AND state='PENDING' AND reward_type='POINT'");
    foreach ($pending as $pending_row) {
        inventory_boundary_query('START TRANSACTION');
        try {
            $where = 'ds_id='.(int)$ds_id.' AND dm_id='.(int)$pending_row['dm_id']." AND reward_type='POINT'";
            $reward = maze_one('SELECT * FROM `'.maze_table('reward').'` WHERE '.$where.' FOR UPDATE');
            if ($reward['state'] !== 'DONE') {
                $paid = insert_point($reward['mb_id'], (int)$reward['amount'], '미궁 클리어 보상', '@maze', (string)$reward['dm_id'], 'clear:'.(int)$ds_id);
                if ($paid !== 1 && $paid !== -1) throw new RuntimeException('클리어 포인트 지급이 대기 중입니다.');
                maze_update('reward', array('state' => 'DONE', 'completed_at' => date('Y-m-d H:i:s')), $where);
            }
            inventory_boundary_query('COMMIT');
        } catch (Throwable $error) { sql_query('ROLLBACK', false); throw $error; }
    }
}
