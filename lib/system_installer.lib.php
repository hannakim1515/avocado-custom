<?php
/** Explicit, additive schema migrations for PHP 7.4. Never include legacy bootstraps here. */
if (!defined('_GNUBOARD_')) exit;

function system_install_ident($name) {
    if (!is_string($name) || !preg_match('/^[a-zA-Z0-9_]+$/D', $name) || strlen($name) > 64) {
        throw new RuntimeException('유효하지 않은 테이블/컬럼 이름입니다.');
    }
    return '`'.$name.'`';
}
function system_install_table($suffix) {
    global $g5;
    static $aliases = array('config'=>'config_table','member'=>'member_table','character'=>'character_table',
        'character_class'=>'class_table','character_side'=>'side_table','item'=>'item_table','inventory'=>'inventory_table',
        'status'=>'status_config_table','status_character'=>'status_table','level_setting'=>'level_table',
        'character_title'=>'title_table','has_title'=>'title_has_table','exp'=>'exp_table');
    $name = isset($aliases[$suffix], $g5[$aliases[$suffix]]) ? $g5[$aliases[$suffix]] : G5_TABLE_PREFIX.$suffix;
    system_install_ident($name);
    return $name;
}
function system_install_quote($value) { return "'".sql_escape_string((string)$value)."'"; }
function system_install_query($sql) {
    global $g5;
    // Use this connection directly: legacy sql_query rewrites some introspection SQL.
    try { $result = mysqli_query($g5['connect_db'], $sql); }
    catch (Throwable $error) { $result = false; }
    if ($result === false) {
        $message = mysqli_error($g5['connect_db']);
        // Duplicate values/user names and credentials must not leak through DB errors.
        $message = preg_replace("/'[^']*'/", "'[redacted]'", $message);
        foreach (array('G5_MYSQL_PASSWORD', 'G5_MYSQL_USER') as $constant) {
            if (defined($constant) && constant($constant) !== '') $message = str_replace(constant($constant), '[redacted]', $message);
        }
        throw new RuntimeException('DB '.mysqli_errno($g5['connect_db']).': '.$message);
    }
    return $result;
}
function system_install_rows($sql) {
    $result = system_install_query($sql); $rows = array();
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_free_result($result);
    return $rows;
}
function system_install_one($sql) { $rows = system_install_rows($sql); return $rows ? $rows[0] : null; }
function system_install_contract() {
    static $contract;
    if ($contract === null) {
        $contract = json_decode(file_get_contents(__DIR__.'/../install/system_schema_v1.json'), true);
        if (!is_array($contract)) throw new RuntimeException('설치 스키마 파일을 읽을 수 없습니다.');
    }
    return $contract;
}
function system_install_registry() {
    return array(
        'base'=>array('name'=>'기본 확장 / 스탯 / 스킬', 'version'=>'1', 'deps'=>array()),
        'map'=>array('name'=>'MAP / NPC 이벤트', 'version'=>'1', 'deps'=>array('base')),
        'npc'=>array('name'=>'NPC 호감도 / 상점', 'version'=>'1', 'deps'=>array('base','map')),
        'dungeon'=>array('name'=>'기존 던전', 'version'=>'1', 'deps'=>array('base')),
        'k_battle'=>array('name'=>'K 전투 / 장비 / 몬스터', 'version'=>'2.0.2', 'deps'=>array('base')),
        'unified'=>array('name'=>'통합 전투 / 스탯·스킬 연결', 'version'=>'1', 'deps'=>array('base','k_battle')),
        'realtime'=>array('name'=>'실시간 레이드', 'version'=>'1.0.0', 'deps'=>array('unified','k_battle')),
        'field'=>array('name'=>'필드 탐색 / 낚시', 'version'=>'1', 'deps'=>array('base')),
        'room'=>array('name'=>'캐릭터 방', 'version'=>'1', 'deps'=>array('base')),
        'quest'=>array('name'=>'퀘스트', 'version'=>'1', 'deps'=>array('base')),
        'inventory_engine'=>array('name'=>'인벤토리 엔진 (001)', 'version'=>'1', 'deps'=>array('base'), 'manual'=>true),
        'inventory_boundary'=>array('name'=>'인벤토리 안전 처리 (002)', 'version'=>'1', 'deps'=>array('inventory_engine')),
        'inventory_index'=>array('name'=>'인벤토리 조회 인덱스 (004)', 'version'=>'1', 'deps'=>array('inventory_engine')),
        'labyrinth'=>array('name'=>'미궁 (003)', 'version'=>'1', 'deps'=>array('inventory_boundary','inventory_index','dungeon','unified'))
    );
}
function system_install_schema($table) {
    $row = system_install_one('SELECT ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.system_install_quote($table));
    if (!$row) return null;
    $row['columns'] = array(); $row['indexes'] = array();
    foreach (system_install_rows('SHOW FULL COLUMNS FROM '.system_install_ident($table)) as $column) $row['columns'][$column['Field']] = $column;
    foreach (system_install_rows('SHOW INDEX FROM '.system_install_ident($table)) as $index) {
        $key = $index['Key_name'];
        $row['indexes'][$key]['unique'] = !(int)$index['Non_unique'];
        $row['indexes'][$key]['type'] = $index['Index_type'];
        $row['indexes'][$key]['columns'][(int)$index['Seq_in_index']] = $index['Column_name'].($index['Sub_part'] !== null ? '('.$index['Sub_part'].')' : '');
    }
    foreach ($row['indexes'] as &$index) { ksort($index['columns']); $index['columns'] = array_values($index['columns']); } unset($index);
    return $row;
}
function system_install_type($type) {
    $type = strtolower(trim($type));
    $type = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $type);
    return str_replace('integer', 'int', $type);
}
function system_install_column_ok($actual, $ddl) {
    preg_match('/^((?:enum|set)\(.*?\)|[a-z]+(?:\([0-9,]+\))?)(\s+unsigned)?/i', $ddl, $m);
    if (!$m) return false;
    $wanted = system_install_type($m[0]); $found = system_install_type($actual['Type']);
    // Admin bootstrap and published SQL have different VARCHAR widths. Wider
    // existing strings retain all data and remain compatible with this contract.
    if (preg_match('/^(var)?char\((\d+)\)$/', $wanted, $w) && preg_match('/^(var)?char\((\d+)\)$/', $found, $f)) {
        if ($w[1] !== $f[1] || (int)$f[2] < (int)$w[2]) return false;
    } elseif ($wanted !== $found) return false;
    if (stripos($ddl, 'AUTO_INCREMENT') !== false && stripos($actual['Extra'], 'auto_increment') === false) return false;
    // Nullable columns in the contract (e.g. active_original_id) are essential.
    $nullable = stripos($ddl, 'NOT NULL') === false;
    return ($actual['Null'] === 'YES') === $nullable;
}
function system_install_index_ok($actual, $wanted, $name) {
    if ($actual['type'] !== 'BTREE') return false;
    if ($wanted['unique'] && !$actual['unique']) return false;
    if ($name === 'PRIMARY' && empty($actual['primary'])) return false;
    $columns = !empty($wanted['prefix_ok']) ? array_slice($actual['columns'], 0, count($wanted['columns'])) : $actual['columns'];
    return $columns === $wanted['columns'];
}
function system_install_index_ddl($name, $index) {
    $columns = isset($index['add_columns']) ? $index['add_columns'] : $index['columns'];
    $keys = array();
    foreach ($columns as $column) {
        if (preg_match('/^(\w+)\((\d+)\)$/', $column, $m)) $keys[] = system_install_ident($m[1]).'('.(int)$m[2].')';
        else $keys[] = system_install_ident($column);
    }
    return ($name === 'PRIMARY' ? 'PRIMARY KEY' : ($index['unique'] ? 'UNIQUE KEY ' : 'KEY ').system_install_ident($name)).' ('.implode(',', $keys).')';
}
function system_install_create($suffix, $spec) {
    $parts = array();
    foreach ($spec['columns'] as $column=>$ddl) $parts[] = system_install_ident($column).' '.$ddl;
    foreach ($spec['indexes'] as $name=>$index) $parts[] = system_install_index_ddl($name, $index);
    return 'CREATE TABLE '.system_install_ident(system_install_table($suffix)).' ('.implode(',', $parts).') ENGINE='.$spec['engine'].' DEFAULT CHARSET='.$spec['charset'];
}
function system_install_meta_spec() {
    return array('create'=>true,'engine'=>'InnoDB','require_engine'=>true,'charset'=>'utf8mb4',
        'columns'=>array('module'=>"varchar(40) NOT NULL",'version'=>"varchar(20) NOT NULL",'applied_at'=>"datetime NOT NULL"),
        'indexes'=>array('PRIMARY'=>array('columns'=>array('module'),'unique'=>true)));
}
/** Return a plan; introspection and duplicate preflight never modify the DB. */
function system_install_plan_table($suffix, $spec, &$plan) {
    $table = system_install_table($suffix); $q = system_install_ident($table);
    $actual = system_install_schema($table);
    if (!$actual) {
        $plan['missing'][] = $table.': 테이블 없음';
        if ($spec['create']) {
            $sql = $suffix === 'k_quest_inven' ? 'CREATE TABLE '.$q.' LIKE '.system_install_ident(system_install_table('inventory')) : system_install_create($suffix, $spec);
            $plan['steps'][] = array('label'=>$table.': 테이블 생성','sql'=>$sql);
        } else $plan['manual'][] = $table.': 선행 기본 설치 테이블이 없습니다';
        return;
    }
    $plan['present']++;
    if ($actual['TABLE_TYPE'] !== 'BASE TABLE') { $plan['manual'][] = $table.': 일반 테이블이 아닙니다'; return; }
    if (!empty($spec['require_engine']) && strcasecmp($actual['ENGINE'], $spec['engine']) !== 0) {
        $plan['manual'][] = $table.': engine '.$actual['ENGINE'].' → '.$spec['engine'].' 수동 검토 필요';
    }
    $populated = null;
    foreach ($spec['columns'] as $column=>$ddl) {
        if (isset($actual['columns'][$column])) {
            if (!system_install_column_ok($actual['columns'][$column], $ddl)) $plan['manual'][] = $table.'.'.$column.': 구조 불일치 (현재 '.$actual['columns'][$column]['Type'].', 필요 '.$ddl.')';
            continue;
        }
        $plan['missing'][] = $table.'.'.$column.': 컬럼 없음';
        if ($populated === null) $populated = (bool)system_install_one('SELECT 1 FROM '.$q.' LIMIT 1');
        $identity = stripos($ddl, 'AUTO_INCREMENT') !== false;
        $no_default = stripos($ddl, 'DEFAULT') === false && stripos($ddl, 'NOT NULL') !== false;
        if (($identity || $no_default) && $populated) {
            $plan['manual'][] = $table.'.'.$column.': 기존 행의 값/식별자를 결정해야 하므로 수동 보완 필요';
            continue;
        }
        // Missing identity + primary on an empty table must be added atomically.
        $extra = '';
        if ($identity) {
            if (isset($actual['indexes']['PRIMARY']) || !isset($spec['indexes']['PRIMARY']) || $spec['indexes']['PRIMARY']['columns'] !== array($column)) {
                $plan['manual'][] = $table.'.'.$column.': AUTO_INCREMENT 키 수동 검토 필요'; continue;
            }
            $extra = ', ADD PRIMARY KEY ('.system_install_ident($column).')';
        }
        $plan['steps'][] = array('label'=>$table.'.'.$column.': 컬럼 추가','sql'=>'ALTER TABLE '.$q.' ADD '.system_install_ident($column).' '.$ddl.$extra);
        if ($identity) $actual['indexes']['PRIMARY'] = array('columns'=>array($column),'unique'=>true,'type'=>'BTREE');
    }
    foreach ($spec['indexes'] as $name=>$index) {
        $found = false;
        foreach ($actual['indexes'] as $key=>$candidate) {
            $candidate['primary'] = $key === 'PRIMARY';
            if (system_install_index_ok($candidate, $index, $name)) $found = true;
        }
        if ($found) continue;
        $plan['missing'][] = $table.'.'.$name.': 인덱스 없음/구조 불일치';
        if (isset($actual['indexes'][$name])) { $plan['manual'][] = $table.'.'.$name.': 같은 이름의 다른 인덱스가 있습니다'; continue; }
        if ($index['unique'] && !array_diff($index['columns'], array_keys($actual['columns']))) {
            $keys = array_map('system_install_ident', $index['columns']);
            $not_null = array_map(function($c) { return $c.' IS NOT NULL'; }, $keys);
            if (system_install_one('SELECT 1 FROM '.$q.' WHERE '.implode(' AND ', $not_null).' GROUP BY '.implode(',', $keys).' HAVING COUNT(*)>1 LIMIT 1')) {
                $plan['manual'][] = $table.'.'.$name.': 중복 데이터가 있어 UNIQUE/PRIMARY 키를 추가할 수 없습니다'; continue;
            }
        }
        $plan['steps'][] = array('label'=>$table.'.'.$name.': 인덱스 추가','sql'=>'ALTER TABLE '.$q.' ADD '.system_install_index_ddl($name, $index));
    }
}
function system_install_native_version($module) {
    $native = array('k_battle'=>array('k_battle_plugin_config','ver_plugin','cf_id=1'),
        'realtime'=>array('k_battle_plugin_config','ver_realtime','cf_id=1'),
        'labyrinth'=>array('dungeon_maze_meta','schema_version','singleton=1'));
    return isset($native[$module]) ? $native[$module] : null;
}
function system_install_version($module) {
    $native = system_install_native_version($module);
    list($suffix,$column,$where) = $native ?: array('system_migration_meta','version','module='.system_install_quote($module));
    $schema = system_install_schema(system_install_table($suffix));
    if (!$schema || !isset($schema['columns'][$column])) return '0';
    $key = $native ? ($module === 'labyrinth' ? 'singleton' : 'cf_id') : 'module';
    if (!isset($schema['columns'][$key])) return '0';
    $row = system_install_one('SELECT '.system_install_ident($column).' AS v FROM '.system_install_ident(system_install_table($suffix)).' WHERE '.$where);
    return $row ? (string)$row['v'] : '0';
}
function system_install_inspect($module) {
    $registry = system_install_registry();
    if (!isset($registry[$module])) throw new RuntimeException('등록되지 않은 모듈입니다.');
    $plan = array('steps'=>array(),'missing'=>array(),'manual'=>array(),'present'=>0,'blocked'=>array());
    if ($module === 'inventory_engine') {
        $schema = system_install_schema(system_install_table('inventory'));
        if (!$schema || strcasecmp($schema['ENGINE'], 'InnoDB') !== 0) $plan['manual'][] = system_install_table('inventory').': '.($schema ? $schema['ENGINE'] : '없음').' → InnoDB';
        if ($schema && (!isset($schema['indexes']['PRIMARY']) || $schema['indexes']['PRIMARY']['columns'] !== array('in_id') || !isset($schema['columns']['in_id']) || stripos($schema['columns']['in_id']['Extra'], 'auto_increment') === false)) {
            $plan['manual'][] = 'inventory: 단일 in_id AUTO_INCREMENT PRIMARY KEY 필요';
        }
        $plan['version'] = $plan['manual'] ? '0' : '1';
    } else {
        $contract = system_install_contract();
        foreach ($contract[$module] as $suffix=>$spec) system_install_plan_table($suffix, $spec, $plan);
        $plan['version'] = system_install_version($module);
        if (version_compare($plan['version'], $registry[$module]['version'], '>')) $plan['manual'][] = '더 높은 스키마 버전입니다. 이 설치 도구로 낮출 수 없습니다.';
        if ($module === 'labyrinth') {
            $server = system_install_one('SELECT VERSION() AS v')['v'];
            $minimum = stripos($server, 'MariaDB') !== false ? '10.2.4' : '8.0.0';
            if (version_compare(preg_replace('/[^0-9.].*$/', '', $server), $minimum, '<')) $plan['manual'][] = '미궁에 필요한 DB 서버 버전: '.$minimum.' 이상';
        }
        // Quest moves copy all inventory columns; custom schemas must also match.
        if ($module === 'quest') {
            $inventory = system_install_schema(system_install_table('inventory'));
            $quest = system_install_schema(system_install_table('k_quest_inven'));
            if ($inventory && $quest) foreach ($inventory['columns'] as $name=>$column) {
                if (!isset($quest['columns'][$name]) || system_install_type($column['Type']) !== system_install_type($quest['columns'][$name]['Type'])) {
                    // qu_id is repaired by the registered additive migration.
                    if ($name !== 'qu_id') $plan['manual'][] = 'k_quest_inven.'.$name.': inventory 사용자 정의 컬럼과의 호환성 확인 필요';
                }
            }
        }
    }
    $plan['ready'] = !$plan['missing'] && !$plan['manual'];
    $plan['status'] = $plan['ready'] ? (version_compare($plan['version'], $registry[$module]['version'], '<') ? '업데이트 필요' : '최신') : ($plan['present'] ? '일부 설치' : '미설치');
    if (!$plan['ready'] && version_compare($plan['version'], $registry[$module]['version'], '>=')) $plan['status'] = '오류 / 구조 불일치';
    elseif ($plan['manual']) $plan['status'] = '수동 조치 필요';
    return $plan;
}
function system_install_all_status() {
    $result = array();
    foreach (system_install_registry() as $module=>$info) {
        try { $result[$module] = system_install_inspect($module); }
        catch (Throwable $e) { $result[$module] = array('ready'=>false,'status'=>'오류 / 구조 불일치','version'=>'?','steps'=>array(),'missing'=>array(),'manual'=>array($e->getMessage()),'blocked'=>array()); }
        foreach ($info['deps'] as $dep) {
            if (!$result[$dep]['ready'] || $result[$dep]['blocked']) $result[$module]['blocked'][] = $dep;
        }
    }
    return $result;
}
function system_install_token() {
    if (empty($_SESSION['system_install_token'])) $_SESSION['system_install_token'] = bin2hex(random_bytes(32));
    return $_SESSION['system_install_token'];
}
function system_install_authorize() {
    global $is_admin;
    if ($is_admin !== 'super') throw new RuntimeException('최고관리자만 실행할 수 있습니다.');
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('POST 요청이 필요합니다.');
    if (empty($_SESSION['system_install_token']) || !isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals($_SESSION['system_install_token'], $_POST['token'])) throw new RuntimeException('요청 인증에 실패했습니다. 화면을 새로고침해 주세요.');
}
function system_install_lock_name() {
    $db = system_install_one('SELECT DATABASE() AS d');
    return 'avo_schema_'.sha1($db['d'].'|'.G5_TABLE_PREFIX);
}
function system_install_default_row($suffix, $values) {
    $schema = system_install_schema(system_install_table($suffix));
    foreach ($schema['columns'] as $name=>$column) {
        if (array_key_exists($name, $values) || $column['Default'] !== null || $column['Null'] === 'YES' || stripos($column['Extra'], 'auto_increment') !== false) continue;
        $values[$name] = preg_match('/^(tinyint|smallint|mediumint|int|bigint|float|double|decimal)/i', $column['Type']) ? 0 : '';
    }
    return 'INSERT INTO '.system_install_ident(system_install_table($suffix)).' ('.implode(',', array_map('system_install_ident', array_keys($values))).') VALUES ('.implode(',', array_map('system_install_quote', array_values($values))).')';
}
function system_install_record($module, $version, &$log) {
    $native = system_install_native_version($module);
    if ($native) {
        list($suffix,$column,$where) = $native;
        $table = system_install_ident(system_install_table($suffix));
        if (!system_install_one('SELECT 1 FROM '.$table.' WHERE '.$where)) {
            system_install_query(system_install_default_row($suffix, array($module === 'labyrinth' ? 'singleton' : 'cf_id'=>1, $column=>$version)));
        } else system_install_query('UPDATE '.$table.' SET '.system_install_ident($column).'='.system_install_quote($version).' WHERE '.$where.' AND '.system_install_ident($column).'<>'.system_install_quote($version));
    } else {
        system_install_query('INSERT INTO '.system_install_ident(system_install_table('system_migration_meta')).' (module,version,applied_at) VALUES ('.system_install_quote($module).','.system_install_quote($version).',NOW()) ON DUPLICATE KEY UPDATE version=VALUES(version),applied_at=VALUES(applied_at)');
    }
    $log[] = $module.': 버전 '.$version.' 기록';
}
/** Serializes installers only; GET_LOCK does not lock gameplay tables. DDL commits itself. */
function system_install_run($requested) {
    system_install_authorize();
    $registry = system_install_registry();
    if ($requested !== 'all' && (!isset($registry[$requested]) || !empty($registry[$requested]['manual']))) throw new RuntimeException('실행할 수 없는 모듈입니다.');
    $lock = system_install_quote(system_install_lock_name());
    $acquired = system_install_one('SELECT GET_LOCK('.$lock.',0) AS acquired');
    if (!$acquired || (int)$acquired['acquired'] !== 1) throw new RuntimeException('다른 설치 요청이 실행 중입니다. 완료 후 전체 점검을 눌러 주세요.');
    $result = array('success'=>array(),'errors'=>array(),'skipped'=>array()); $step = '사전 점검';
    $old = null;
    try {
        $old = system_install_one('SELECT @@session.lock_wait_timeout AS ddl_wait, @@session.innodb_lock_wait_timeout AS row_wait');
        system_install_query('SET SESSION lock_wait_timeout=5');
        system_install_query('SET SESSION innodb_lock_wait_timeout=5');
        $meta = array('steps'=>array(),'missing'=>array(),'manual'=>array(),'present'=>0);
        system_install_plan_table('system_migration_meta', system_install_meta_spec(), $meta);
        if ($meta['manual'] || (system_install_schema(system_install_table('system_migration_meta')) && $meta['missing'])) throw new RuntimeException('마이그레이션 기록 테이블 구조가 다릅니다. 수동 확인 필요');
        foreach ($registry as $module=>$info) {
            if (($requested !== 'all' && $requested !== $module) || !empty($info['manual'])) continue;
            $step = $module.': 사전 점검';
            try {
                $states = system_install_all_status(); $plan = $states[$module];
                if ($plan['blocked'] || $plan['manual']) {
                    $result['skipped'][] = $module.': '.implode('; ', array_merge($plan['manual'], array_map(function($d) { return '선행 모듈 필요: '.$d; }, $plan['blocked'])));
                    continue;
                }
                if ($plan['ready'] && $plan['version'] === $info['version']) { $result['skipped'][] = $module.': 이미 최신'; continue; }
                if (!system_install_schema(system_install_table('system_migration_meta'))) {
                    $step = 'migration metadata 테이블 생성';
                    system_install_query(system_install_create('system_migration_meta', system_install_meta_spec()));
                    $result['success'][] = $step;
                }
                foreach ($plan['steps'] as $action) {
                    $step = $action['label'];
                    system_install_query($action['sql']); $result['success'][] = $step;
                }
                $step = $module.': 적용 후 구조 검증';
                $verified = system_install_inspect($module);
                if (!$verified['ready']) throw new RuntimeException(implode('; ', array_merge($verified['missing'], $verified['manual'])));
                $step = $module.': 필수 설정/버전 기록';
                system_install_seed_defaults($module, $result['success']);
                system_install_record($module, $info['version'], $result['success']);
            } catch (Throwable $error) { $result['errors'][] = $step.' — '.$error->getMessage(); }
        }
    } catch (Throwable $error) { $result['errors'][] = $step.' — '.$error->getMessage(); }
    finally {
        try {
            if ($old) {
                system_install_query('SET SESSION lock_wait_timeout='.(int)$old['ddl_wait']);
                system_install_query('SET SESSION innodb_lock_wait_timeout='.(int)$old['row_wait']);
            }
        } finally { system_install_query('SELECT RELEASE_LOCK('.$lock.')'); }
    }
    return $result;
}
function system_install_seed_defaults($module, &$log) {
    if ($module === 'k_battle') {
        $table = system_install_ident(system_install_table('k_battle_skill_info'));
        $defaults = json_decode(file_get_contents(__DIR__.'/../install/system_defaults_v1.json'), true);
        foreach ($defaults as $row) {
            if (!system_install_one('SELECT 1 FROM '.$table.' WHERE si_code='.system_install_quote($row['si_code']))) {
                system_install_query(system_install_default_row('k_battle_skill_info', $row));
                $log[] = 'K 기본 스킬 코드 추가: '.$row['si_code'];
            }
        }
    }
    if ($module === 'quest' && !system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table('k_quest_config')).' LIMIT 1')) {
        system_install_query(system_install_default_row('k_quest_config', array('qc_id'=>1)));
        $log[] = '퀘스트 기본 설정 추가';
    }
}
