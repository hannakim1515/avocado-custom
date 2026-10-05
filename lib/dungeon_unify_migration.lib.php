<?php
if (!defined('_GNUBOARD_')) exit;

/** Called under the installer lock after the latest contracts have been added. */
function system_install_dungeon_storage(&$log) {
    foreach (array('dungeon','dungeon_item','dungeon_state') as $suffix) {
        $schema=system_install_schema(system_install_table($suffix));
        if ($schema && strcasecmp($schema['ENGINE'],'InnoDB')!==0) {
            system_install_query('ALTER TABLE '.system_install_ident(system_install_table($suffix)).' ENGINE=InnoDB');
            $log[]=$suffix.': 한 번의 설정 저장을 위한 InnoDB 전환';
        }
    }
}

/** Old quota fields never created groups; do not reinterpret them as pack sizes. */
function system_install_monster_encounter_counts(&$log) {
    if(version_compare(system_install_version('monsters'),'1.7.0','>='))return;
    $name=system_install_table('dungeon_monster_pool');$schema=system_install_schema($name);
    if(!isset($schema['columns']['min_count'],$schema['columns']['max_count']))return;
    $pool=system_install_ident($name);
    system_install_query('UPDATE '.$pool.' SET min_count=1,max_count=1');
    if((string)$schema['columns']['min_count']['Default']!=='1' || (string)$schema['columns']['max_count']['Default']!=='1') {
        system_install_query('ALTER TABLE '.$pool.' MODIFY min_count INT NOT NULL DEFAULT 1,MODIFY max_count INT NOT NULL DEFAULT 1');
        unset($GLOBALS['system_install_schema_cache'][$name]);
    }
    $log[]='구형 미사용 출현 정원을 단일 몬스터 출현 기본값으로 이전';
}
function system_install_dungeon_unify(&$log) {
    system_install_dungeon_storage($log);
    $d=system_install_ident(system_install_table('dungeon'));
    $items=system_install_ident(system_install_table('dungeon_item'));
    $pool=system_install_ident(system_install_table('dungeon_monster_pool'));
    $state=system_install_ident(system_install_table('dungeon_state'));
    $old_config=system_install_schema(system_install_table('dungeon_maze_config'));
    $old_session=system_install_schema(system_install_table('dungeon_maze_session'));
    $item_schema=system_install_schema(system_install_table('dungeon_item'));
    system_install_query('START TRANSACTION');
    try {
        if (isset($item_schema['columns']['di_per_s'],$item_schema['columns']['di_per_e']))
            system_install_query('UPDATE '.$items.' SET di_percent=ROUND(GREATEST(0,LEAST(100,di_per_e)-GREATEST(0,di_per_s)+1)*100/101,2)');
        if ($old_config) foreach (system_install_rows('SELECT dg_id,settings FROM '.system_install_ident(system_install_table('dungeon_maze_config'))) as $row) {
            $id=(int)$row['dg_id'];$settings=json_decode($row['settings'],true);
            if (!is_array($settings)) throw new RuntimeException('던전 '.$id.'의 탐험 설정 JSON이 올바르지 않습니다.');
            if (!system_install_one('SELECT dg_id FROM '.$d.' WHERE dg_id='.$id)) continue;
            // The previously active exploration values take precedence over stale direct-battle values.
            if (array_key_exists('rewards',$settings)) {
                system_install_query('DELETE FROM '.$items.' WHERE dg_id='.$id);
                foreach ($settings['rewards'] as $r) {
                    if ((int)($r['it_id']??0)<1 || (int)($r['count']??0)<1 || !is_numeric($r['percent']??null) || $r['percent']<0 || $r['percent']>100)
                        throw new RuntimeException('던전 '.$id.'의 보상 값을 확인하세요.');
                    system_install_query('INSERT INTO '.$items.' (dg_id,it_id,di_count,di_percent) VALUES ('.$id.','.(int)$r['it_id'].','.(int)$r['count'].','.(float)$r['percent'].')');
                }
            }
            if (array_key_exists('monsters',$settings)) {
                system_install_query('DELETE FROM '.$pool.' WHERE dg_id='.$id);
                foreach ($settings['monsters'] as $m) {
                    $mid=(int)($m['monster_id']??0);
                    if ($mid<1) throw new RuntimeException('던전 '.$id.'의 공용 몬스터 연결이 필요합니다.');
                    $weight=max(0,(int)($m['weight']??1));$enabled=!empty($m['enabled'])?1:0;$boss=!empty($m['boss'])?1:0;
                    // Repeated links have identical definitions; combine their selection weight.
                    system_install_query('INSERT INTO '.$pool.' (dg_id,monster_id,weight,enabled,is_boss) VALUES ('.$id.','.$mid.','.$weight.','.$enabled.','.$boss.') ON DUPLICATE KEY UPDATE weight=weight+VALUES(weight),enabled=GREATEST(enabled,VALUES(enabled)),is_boss=GREATEST(is_boss,VALUES(is_boss))');
                }
            }
            foreach(array('rewards','monsters','title','description','max_players','points') as $key)unset($settings[$key]);
            system_install_query('UPDATE '.$d.' SET dg_settings='.system_install_quote(json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).' WHERE dg_id='.$id);
            $saved=system_install_one('SELECT dg_settings FROM '.$d.' WHERE dg_id='.$id);
            if (json_decode($saved['dg_settings'],true)!==$settings)throw new RuntimeException('던전 설정 이전 검증 실패: '.$id);
            $log[]='던전 '.$id.': 탐험 설정·출현 목록·보상 정의 통합';
        }
        // An unconfigured old dungeon becomes a valid exploration dungeon with defaults.
        system_install_query("UPDATE ".$d." SET dg_settings='{}' WHERE dg_settings IS NULL");
        if ($old_session) {
            $old=system_install_ident(system_install_table('dungeon_maze_session'));
            $fields=array('phase','version','room_id','battle_id','snapshot','expires_at','started_at','finished_at','created_at');
            if(system_install_one('SELECT s.ds_id FROM '.$old.' s LEFT JOIN '.$state.' d ON d.ds_id=s.ds_id WHERE d.ds_id IS NULL LIMIT 1'))
                throw new RuntimeException('던전 발생 정보가 없는 탐험 세션이 있습니다. 참가자와 보관 아이템 확인이 필요합니다.');
            if(system_install_one('SELECT s.ds_id FROM '.$old.' s JOIN '.$state.' d ON d.ds_id=s.ds_id WHERE s.dg_id<>d.dg_id LIMIT 1'))
                throw new RuntimeException('탐험 세션과 던전 발생 정보의 던전 ID가 다릅니다.');
            $sets=array();foreach($fields as $field)$sets[]='d.`'.$field.'`=s.`'.$field.'`';
            system_install_query('UPDATE '.$state.' d JOIN '.$old.' s ON s.ds_id=d.ds_id SET '.implode(',',$sets));
            $different=array();foreach($fields as $field)$different[]='NOT (d.`'.$field.'` <=> s.`'.$field.'`)';
            if(system_install_one('SELECT s.ds_id FROM '.$old.' s JOIN '.$state.' d ON d.ds_id=s.ds_id WHERE '.implode(' OR ',$different).' LIMIT 1'))
                throw new RuntimeException('탐험 진행 정보 이전 검증 실패');
            $log[]='탐험 세션을 dungeon_state에 통합 (참가자·보관 아이템·정산 ID 유지)';
        }
        system_install_query("UPDATE ".$state." SET phase='CLOSED',finished_at=COALESCE(finished_at,NOW()) WHERE snapshot IS NULL AND ds_state='E'");
        // The user retired direct battles and their history; active exploration
        // and raid runtime keys and events retain their own records.
        if(system_install_schema(system_install_table('monster_runtime')))
            system_install_query('DELETE FROM '.system_install_ident(system_install_table('monster_runtime'))." WHERE battle_key LIKE 'dungeon:%'");
        if(system_install_schema(system_install_table('dungeon_combat_event')))
            system_install_query('DELETE FROM '.system_install_ident(system_install_table('dungeon_combat_event'))." WHERE source='dungeon'");
        system_install_query('COMMIT');
    } catch(Throwable $e) {system_install_query('ROLLBACK');throw $e;}
    // Remove legacy probability columns before dropping the import source. A retry
    // after partial DDL must never convert already imported percentage rows again.
    $schema=system_install_schema(system_install_table('dungeon_item'));
    $drops=array();foreach(array('di_per_s','di_per_e') as $field)if(isset($schema['columns'][$field]))$drops[]='DROP COLUMN '.system_install_ident($field);
    if($drops)system_install_query('ALTER TABLE '.$items.' '.implode(',',$drops));
    // Destructive cleanup runs only after the transferred definitions and sessions were verified.
    foreach(array('dungeon_maze_config','dungeon_maze_session','dungeon_monster_config','dungeon_state_monster','dungeon_member','dungeon_log') as $suffix) {
        if(system_install_schema(system_install_table($suffix))) {
            system_install_query('DROP TABLE '.system_install_ident(system_install_table($suffix)));
            $log[]='폐기/통합된 테이블 삭제: '.$suffix;
        }
    }
    foreach(array('dungeon_item'=>array('di_per_s','di_per_e'),
        'dungeon_state'=>array('ds_hp','ds_hurt','ds_weak_count','ds_weak_turn')) as $suffix=>$fields) {
        $schema=system_install_schema(system_install_table($suffix));$drops=array();
        foreach($fields as $field)if(isset($schema['columns'][$field]))$drops[]='DROP COLUMN '.system_install_ident($field);
        if($drops)system_install_query('ALTER TABLE '.system_install_ident(system_install_table($suffix)).' '.implode(',',$drops));
    }
    // CREATE VIEW d.* is fixed at creation time; refresh it after dg_settings was added.
    require_once __DIR__.'/monster.lib.php';
    if(system_install_schema(monster_dungeon_read_table()))system_install_query('DROP VIEW '.system_install_ident(monster_dungeon_read_table()));
    system_install_query(monster_dungeon_read_view_sql());
}
