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
    static $aliases = array('config'=>'config_table','member'=>'member_table','point'=>'point_table','character'=>'character_table',
        'character_class'=>'class_table','character_side'=>'side_table','item'=>'item_table','inventory'=>'inventory_table',
        'status'=>'status_config_table','status_character'=>'status_table','level_setting'=>'level_table',
        'character_title'=>'title_table','has_title'=>'title_has_table','exp'=>'exp_table');
    $name = isset($aliases[$suffix], $g5[$aliases[$suffix]]) ? $g5[$aliases[$suffix]] : G5_TABLE_PREFIX.$suffix;
    system_install_ident($name);
    return $name;
}
function system_install_quote($value) {
    global $g5;
    return "'".mysqli_real_escape_string($g5['connect_db'], (string)$value)."'";
}
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
    if (preg_match('/^\s*(CREATE|ALTER|DROP|RENAME)\b/i', $sql)) $GLOBALS['system_install_schema_cache'] = array();
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
        $contract = array();
        foreach (system_install_registry() as $module=>$info) {
            if (!empty($info['manual'])) continue;
            $path = __DIR__.'/../install/system/'.$module.'/schema-'.$info['version'].'.json';
            $spec = json_decode(file_get_contents($path), true);
            if (!is_array($spec) || !$spec) throw new RuntimeException($module.': 설치 스키마 파일을 읽을 수 없습니다.');
            $contract[$module] = $spec;
        }
    }
    return $contract;
}
function system_install_registry() {
    static $registry;
    if ($registry === null) $registry = require __DIR__.'/../install/system/modules.php';
    return $registry;
}
function system_install_schema($table) {
    if (isset($GLOBALS['system_install_schema_cache']) && array_key_exists($table, $GLOBALS['system_install_schema_cache'])) return $GLOBALS['system_install_schema_cache'][$table];
    $row = system_install_one('SELECT ENGINE,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='.system_install_quote($table));
    if (!$row) { $GLOBALS['system_install_schema_cache'][$table] = null; return null; }
    $row['columns'] = array(); $row['indexes'] = array();
    foreach (system_install_rows('SHOW FULL COLUMNS FROM '.system_install_ident($table)) as $column) $row['columns'][$column['Field']] = $column;
    foreach (system_install_rows('SHOW INDEX FROM '.system_install_ident($table)) as $index) {
        $key = $index['Key_name'];
        $row['indexes'][$key]['unique'] = !(int)$index['Non_unique'];
        $row['indexes'][$key]['type'] = $index['Index_type'];
        $row['indexes'][$key]['columns'][(int)$index['Seq_in_index']] = $index['Column_name'].($index['Sub_part'] !== null ? '('.$index['Sub_part'].')' : '');
    }
    foreach ($row['indexes'] as &$index) { ksort($index['columns']); $index['columns'] = array_values($index['columns']); } unset($index);
    $GLOBALS['system_install_schema_cache'][$table] = $row;
    return $row;
}
function system_install_type($type) {
    $type = strtolower(trim($type));
    $type = preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/', '$1', $type);
    return str_replace('integer', 'int', $type);
}
function system_install_column_ok($actual, $ddl) {
    // MySQL marks expression defaults (including CURRENT_TIMESTAMP) with
    // DEFAULT_GENERATED. Those are writable columns, unlike computed columns.
    if (preg_match('/\b(?:VIRTUAL|STORED|PERSISTENT)\s+GENERATED\b/i', $actual['Extra'])) return false;
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
/** Expand short unindexed strings, preserving existing data and attributes. */
function system_install_column_widening($actual, $ddl, $indexes, $column) {
    if (!preg_match('/^varchar\((\d+)\)$/i', $actual['Type'], $found)
        || (int)$found[1] > 4096 || trim($actual['Extra']) !== '') return null;
    if (!preg_match('/^(text|varchar\((\d+)\))(?=\s|$)/i', $ddl, $wanted)) return null;
    if (strtolower($wanted[1]) !== 'text' && (int)$wanted[2] <= (int)$found[1]) return null;
    $nullable = stripos($ddl, 'NOT NULL') === false;
    if ($actual['Null'] === 'YES' && !$nullable) return null;
    foreach ($indexes as $index) foreach ($index['columns'] as $indexed) {
        if (preg_replace('/\(\d+\)$/', '', $indexed) === $column) return null;
    }
    $definition = $wanted[1];
    if (!empty($actual['Collation'])) $definition .= ' COLLATE '.system_install_ident($actual['Collation']);
    $definition .= $nullable ? ' NULL' : ' NOT NULL';
    if ($actual['Default'] !== null) {
        // MariaDB SHOW FULL COLUMNS can return a quoted SQL literal.
        $default = $actual['Default'];
        if (strlen($default) >= 2 && $default[0] === "'" && substr($default,-1) === "'")
            $default = str_replace("''", "'", substr($default,1,-1));
        $definition .= ' DEFAULT '.system_install_quote($default);
    } elseif ($nullable) $definition .= ' DEFAULT NULL';
    if ($actual['Comment'] !== '') $definition .= ' COMMENT '.system_install_quote($actual['Comment']);
    return $definition;
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
            $sql = !empty($spec['create_like']) ? 'CREATE TABLE '.$q.' LIKE '.system_install_ident(system_install_table($spec['create_like'])) : system_install_create($suffix, $spec);
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
            if (!system_install_column_ok($actual['columns'][$column], $ddl)) {
                $widen = system_install_column_widening($actual['columns'][$column], $ddl, $actual['indexes'], $column);
                if ($widen !== null) {
                    $label = $table.'.'.$column.': 기존 값을 유지하며 문자열 저장 범위 확장';
                    $plan['missing'][] = $label;
                    $plan['steps'][] = array('label'=>$label,'sql'=>'ALTER TABLE '.$q.' MODIFY COLUMN '.system_install_ident($column).' '.$widen);
                } else {
                    $found = $actual['columns'][$column];
                    $description = $found['Type'].' '.($found['Null'] === 'YES' ? 'NULL 허용' : 'NOT NULL');
                    $description .= ' / 기본값 '.($found['Default'] === null ? '없음 또는 NULL' : $found['Default']);
                    if ($found['Extra'] !== '') $description .= ' / 속성 '.$found['Extra'];
                    $plan['manual'][] = $table.'.'.$column.': 구조 불일치 (현재 '.$description.', 필요 '.$ddl.')';
                }
            }
            continue;
        }
        $plan['missing'][] = $table.'.'.$column.': 컬럼 없음';
        if ($populated === null) $populated = (bool)system_install_one('SELECT 1 FROM '.$q.' LIMIT 1');
        $identity = stripos($ddl, 'AUTO_INCREMENT') !== false;
        $no_default = stripos($ddl, 'DEFAULT') === false && stripos($ddl, 'NOT NULL') !== false;
        $safe_empty = !empty($spec['safe_empty_add']) && in_array($column, $spec['safe_empty_add'], true);
        if (($identity || ($no_default && !$safe_empty)) && $populated) {
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
    $meta = system_install_schema(system_install_table('system_migration_meta'));
    if ($meta && isset($meta['columns']['module'], $meta['columns']['version'])) {
        $record = system_install_one('SELECT version AS v FROM '.system_install_ident(system_install_table('system_migration_meta')).' WHERE module='.system_install_quote($module));
        if ($record) return (string)$record['v'];
    }
    $native = system_install_native_version($module);
    list($suffix,$column,$where) = $native ?: array('system_migration_meta','version','module='.system_install_quote($module));
    $schema = system_install_schema(system_install_table($suffix));
    if (!$schema || !isset($schema['columns'][$column])) return '0';
    $key = $native ? ($module === 'labyrinth' ? 'singleton' : 'cf_id') : 'module';
    if (!isset($schema['columns'][$key])) return '0';
    $row = system_install_one('SELECT '.system_install_ident($column).' AS v FROM '.system_install_ident(system_install_table($suffix)).' WHERE '.$where);
    $version = $row ? (string)$row['v'] : '0';
    return $module === 'labyrinth' && $version === '1' ? '1.0.0' : $version;
}
function system_install_inspect($module) {
    $registry = system_install_registry();
    if (!isset($registry[$module])) throw new RuntimeException('등록되지 않은 모듈입니다.');
    $plan = array('steps'=>array(),'missing'=>array(),'manual'=>array(),'present'=>0,'owned_present'=>0,'blocked'=>array(),'data_missing'=>array());
    if ($module === 'inventory_engine') {
        $schema = system_install_schema(system_install_table('inventory'));
        if (!$schema || strcasecmp($schema['ENGINE'], 'InnoDB') !== 0) $plan['manual'][] = system_install_table('inventory').': '.($schema ? $schema['ENGINE'] : '없음').' → InnoDB';
        if ($schema && (!isset($schema['indexes']['PRIMARY']) || $schema['indexes']['PRIMARY']['columns'] !== array('in_id') || !isset($schema['columns']['in_id']) || stripos($schema['columns']['in_id']['Extra'], 'auto_increment') === false)) {
            $plan['manual'][] = 'inventory: 단일 in_id AUTO_INCREMENT PRIMARY KEY 필요';
        }
        $plan['version'] = $plan['manual'] ? '0' : '1.0.0';
    } else {
        $contract = system_install_contract();
        foreach ($contract[$module] as $suffix=>$spec) {
            system_install_plan_table($suffix, $spec, $plan);
            $owned = $module === 'base' ? in_array($suffix, array('status_extra','battle_config','battle_log','skill','skill_has','skill_level'), true) : $spec['create'];
            if ($owned && system_install_schema(system_install_table($suffix))) $plan['owned_present']++;
        }
        $plan['version'] = system_install_version($module);
        if (!preg_match('/^\d+(?:\.\d+){0,2}$/D', $plan['version'])) $plan['manual'][] = '올바르지 않은 버전 기록입니다. 수동 확인이 필요합니다.';
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
            if ($inventory && $quest && array_keys($inventory['columns']) !== array_keys($quest['columns'])) {
                // The qu_id ADD is safe only when it restores the exact order.
                $expected = array_keys($inventory['columns']); $actual = array_keys($quest['columns']);
                if (!in_array('qu_id', $expected, true)) $expected[] = 'qu_id';
                if (!in_array('qu_id', $actual, true)) $actual[] = 'qu_id';
                if ($actual !== $expected) $plan['manual'][] = 'k_quest_inven: inventory와 컬럼 순서가 달라 아이템 이동이 안전하지 않습니다. 수동 확인 필요';
            }
        }
        if (!$plan['missing'] && !$plan['manual']) {
            $plan['data_missing'] = system_install_missing_defaults($module);
            $native = system_install_native_version($module);
            if ($native) {
                list($suffix,$column,$where) = $native;
                $row = system_install_one('SELECT '.system_install_ident($column).' AS v FROM '.system_install_ident(system_install_table($suffix)).' WHERE '.$where);
                if ($row) {
                    $native_version = $module === 'labyrinth' && (string)$row['v'] === '1' ? '1.0.0' : (string)$row['v'];
                    if (!preg_match('/^\d+(?:\.\d+){0,2}$/D', $native_version) || version_compare($native_version, $registry[$module]['version'], '>')) $plan['manual'][] = $suffix.'.'.$column.': 지원하지 않는 버전 '.$native_version;
                    // Maze's native integer tracks the major version; metadata tracks minor updates.
                    elseif ($module === 'labyrinth' ? (string)$row['v'] !== explode('.', $plan['version'])[0] : version_compare($native_version, $plan['version'], '!=')) $plan['data_missing'][] = $suffix.'.'.$column.': 설치 버전 기록과 일치하지 않습니다.';
                }
            }
        }
    }
    $plan['ready'] = !$plan['missing'] && !$plan['manual'];
    $plan['installed'] = $plan['ready'] && !$plan['data_missing'] && $plan['version'] !== '0';
    $plan['status'] = $plan['installed'] ? (version_compare($plan['version'], $registry[$module]['version'], '<') ? '업데이트 필요' : '설치됨') : '미설치';
    if ($plan['version'] !== '0' && version_compare($plan['version'], $registry[$module]['version'], '<')) $plan['status'] = '업데이트 필요';
    if ($plan['manual'] || (!$plan['ready'] && version_compare($plan['version'], $registry[$module]['version'], '>=')) || ($plan['data_missing'] && $plan['version'] !== '0')) $plan['status'] = '구조 이상';
    if ($plan['version'] === '0' && $plan['owned_present'] && !$plan['ready'] && !$plan['manual']) $plan['status'] = '구조 이상';
    if ($module === 'inventory_engine' && !$plan['installed']) $plan['status'] = '준비 필요';
    return $plan;
}
function system_install_all_status() {
    $GLOBALS['system_install_schema_cache'] = array();
    $result = array();
    $meta_issues = array();
    if (system_install_schema(system_install_table('system_migration_meta'))) {
        $meta = array('steps'=>array(),'missing'=>array(),'manual'=>array(),'present'=>0);
        system_install_plan_table('system_migration_meta', system_install_meta_spec(), $meta);
        $meta_issues = array_merge($meta['missing'], $meta['manual']);
    }
    foreach (system_install_registry() as $module=>$info) {
        try { $result[$module] = system_install_inspect($module); }
        catch (Throwable $e) { $result[$module] = array('ready'=>false,'installed'=>false,'status'=>'구조 이상','version'=>'?','steps'=>array(),'missing'=>array(),'data_missing'=>array(),'manual'=>array($e->getMessage()),'blocked'=>array()); }
        foreach ($info['deps'] as $dep) {
            if (!$result[$dep]['installed'] || $result[$dep]['blocked']) $result[$module]['blocked'][] = $dep;
        }
        if ($result[$module]['blocked'] && $result[$module]['version'] === '0' && empty($result[$module]['owned_present'])) {
            $only_prerequisites = true;
            foreach ($result[$module]['manual'] as $reason) if (strpos($reason, '선행 기본 설치 테이블이 없습니다') === false) $only_prerequisites = false;
            if ($only_prerequisites) $result[$module]['status'] = '선행 기능 필요';
        }
        if ($meta_issues) {
            $result[$module]['manual'] = array_merge($result[$module]['manual'], $meta_issues);
            $result[$module]['ready'] = $result[$module]['installed'] = false;
            $result[$module]['status'] = '구조 이상';
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
    return 'system_schema_'.sha1($db['d'].'|'.G5_TABLE_PREFIX);
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
        $native_version = $module === 'labyrinth' ? explode('.', $version)[0] : $version;
        if (!system_install_one('SELECT 1 FROM '.$table.' WHERE '.$where)) {
            system_install_query(system_install_default_row($suffix, array($module === 'labyrinth' ? 'singleton' : 'cf_id'=>1, $column=>$native_version)));
        } else system_install_query('UPDATE '.$table.' SET '.system_install_ident($column).'='.system_install_quote($native_version).' WHERE '.$where);
    }
    system_install_query('INSERT INTO '.system_install_ident(system_install_table('system_migration_meta')).' (module,version,applied_at) VALUES ('.system_install_quote($module).','.system_install_quote($version).',NOW()) ON DUPLICATE KEY UPDATE version=VALUES(version),applied_at=VALUES(applied_at)');
    $log[] = $module.': 버전 '.$version.' 기록';
}
/** Serializes installers only; GET_LOCK does not lock gameplay tables. DDL commits itself. */
function system_install_run($requested, $action = 'apply') {
    system_install_authorize();
    $registry = system_install_registry();
    if (!is_string($requested) || !is_string($action) || !in_array($action, array('apply','inventory_engine'), true)) throw new RuntimeException('실행할 수 없는 요청입니다.');
    if ($action === 'inventory_engine') {
        if ($requested !== 'inventory_engine' || ($_POST['backup_confirm'] ?? '') !== '1' || ($_POST['writers_confirm'] ?? '') !== '1') throw new RuntimeException('백업·복원 확인과 인벤토리 쓰기 중단 확인이 필요합니다.');
    } elseif ($requested !== 'all' && (!isset($registry[$requested]) || !empty($registry[$requested]['manual']))) throw new RuntimeException('실행할 수 없는 모듈입니다.');
    $lock = system_install_quote(system_install_lock_name());
    $acquired = system_install_one('SELECT GET_LOCK('.$lock.',0) AS acquired');
    if (!$acquired || (int)$acquired['acquired'] !== 1) throw new RuntimeException('다른 설치 요청이 실행 중입니다. 완료 후 전체 점검을 눌러 주세요.');
    $result = array('success'=>array(),'errors'=>array(),'skipped'=>array()); $step = '사전 점검';
    $old = null;
    try {
        $old = system_install_one('SELECT @@session.lock_wait_timeout AS ddl_wait, @@session.innodb_lock_wait_timeout AS row_wait');
        system_install_query('SET SESSION lock_wait_timeout=5');
        system_install_query('SET SESSION innodb_lock_wait_timeout=5');
        $GLOBALS['system_install_schema_cache'] = array();
        if ($action === 'inventory_engine') {
            $step = 'inventory_engine v1.0.0: 엔진 전환';
            system_install_convert_inventory($result['success']);
            return $result;
        }
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
                if ($plan['installed'] && version_compare($plan['version'], $info['version'], '==')) { $result['skipped'][] = $module.': 이미 최신'; continue; }
                if (!system_install_schema(system_install_table('system_migration_meta'))) {
                    $step = 'migration metadata 테이블 생성';
                    system_install_query(system_install_create('system_migration_meta', system_install_meta_spec()));
                    $result['success'][] = $step;
                }
                $migrations = isset($info['migrations']) ? $info['migrations'] : array($info['version']=>null);
                uksort($migrations, 'version_compare');
                // Fresh/adopted installations use the latest snapshot directly.
                if ($plan['version'] === '0') $migrations = array($info['version']=>null);
                foreach ($migrations as $target=>$file) {
                    if (version_compare($target, $plan['version'], '<=')) continue;
                    $step = $module.' v'.$target.': '.($plan['version'] === '0' ? '설치' : '마이그레이션');
                    $contract = $file === null ? system_install_contract()[$module] : require __DIR__.'/../install/system/'.$file;
                    system_install_apply_contract($module, $target, $contract, $result['success'], $step);
                }
                // Also permits resuming an interrupted install whose native version
                // column was created with a default, without rerunning legacy SQL.
                system_install_apply_contract($module, $info['version'], system_install_contract()[$module], $result['success'], $step);
                $step = $module.' v'.$info['version'].': 적용 후 구조 검증';
                $verified = system_install_inspect($module);
                if (!$verified['ready']) throw new RuntimeException(implode('; ', array_merge($verified['missing'], $verified['manual'])));
                $step = $module.' v'.$info['version'].': 필수 설정/버전 기록';
                system_install_seed_defaults($module, $result['success']);
                $missing_defaults = system_install_missing_defaults($module);
                if ($missing_defaults) throw new RuntimeException(implode('; ', $missing_defaults));
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
    require_once __DIR__.'/dungeon_unify_migration.lib.php';
    if ($module === 'dungeon') system_install_dungeon_storage($log);
    if ($module === 'labyrinth') {
        $old_effect=system_install_table('dungeon_maze_item_effect');
        if (system_install_schema($old_effect)) {
            $old=system_install_ident($old_effect);
            $items=system_install_ident(system_install_table('item'));
            $entries=system_install_rows('SELECT e.it_id,e.kind,e.status_id,e.value,i.it_id AS live_id,i.it_type,i.it_value FROM '.$old.' e LEFT JOIN '.$items.' i ON i.it_id=e.it_id ORDER BY e.it_id');
            foreach ($entries as $entry) {
                // Orphan rows were only old catalog data; no live item can use them.
                if ($entry['live_id'] === null) continue;
                $kind=$entry['kind'];
                if ($kind === 'REVIVE' && (int)$entry['value']>=1 && (int)$entry['value']<=100) {
                    $type='부활'; $value=(string)(int)$entry['value'];
                } elseif ($kind === 'CLEANSE' && (int)$entry['status_id']>0) {
                    $type='상태이상치료'; $value=(string)(int)$entry['status_id'];
                } else throw new RuntimeException('기존 미궁 아이템 효과 '.(int)$entry['it_id'].'의 종류 또는 값이 올바르지 않습니다.');
                if ((string)$entry['it_type']!=='' && (string)$entry['it_type']!==$type && (string)$entry['it_type']!=='일반')
                    throw new RuntimeException('아이템 '.(int)$entry['it_id'].'의 기존 종류와 미궁 효과가 충돌합니다.');
                if ((string)$entry['it_type']===$type && (string)$entry['it_value']!=='' && (string)$entry['it_value']!==$value)
                    throw new RuntimeException('아이템 '.(int)$entry['it_id'].'의 기존 효과 값과 미궁 효과가 충돌합니다.');
                $id=(int)$entry['it_id'];
                system_install_query('UPDATE '.$items.' SET it_type='.system_install_quote($type).',it_value='.system_install_quote($value).' WHERE it_id='.$id);
                $saved=system_install_one('SELECT it_type,it_value FROM '.$items.' WHERE it_id='.$id);
                if (!$saved || $saved['it_type']!==$type || (string)$saved['it_value']!==$value)
                    throw new RuntimeException('아이템 '.(int)$entry['it_id'].'의 효과 이전 검증에 실패했습니다.');
            }
            system_install_query('DROP TABLE '.$old);
            unset($GLOBALS['system_install_schema_cache'][$old_effect]);
            $log[]='기존 미궁 아이템 효과를 공용 아이템으로 옮기고 중복 테이블 삭제';
        }
    }
    if ($module === 'monsters') {
        system_install_monster_encounter_counts($log);
        $master=system_install_ident(system_install_table('monster_master'));
        $raid=system_install_ident(system_install_table('k_battle_monster'));
        $old_profile=system_install_table('monster_dungeon_profile');
        if (system_install_schema($old_profile)) {
            $old=system_install_ident($old_profile);
            if (system_install_one('SELECT 1 FROM '.$old.' p LEFT JOIN '.$master.' m ON m.monster_id=p.monster_id WHERE m.monster_id IS NULL LIMIT 1'))
                throw new RuntimeException('연결되지 않은 기존 몬스터 프로필이 있어 자동 정리를 중단했습니다.');
            system_install_query('UPDATE '.$master.' m JOIN '.$old.' p ON p.monster_id=m.monster_id SET m.profile=p.profile WHERE m.profile IS NULL');
            if (system_install_one('SELECT 1 FROM '.$old.' p JOIN '.$master.' m ON m.monster_id=p.monster_id WHERE m.profile IS NULL OR BINARY m.profile <> BINARY p.profile LIMIT 1'))
                throw new RuntimeException('기존 몬스터 프로필 복사 결과가 달라 자동 정리를 중단했습니다.');
            system_install_query('DROP TABLE '.$old);
            unset($GLOBALS['system_install_schema_cache'][$old_profile]);
            $log[]='기존 몬스터 프로필을 공용 Master로 옮기고 중복 테이블 삭제';
        }
        $old_target=system_install_table('dungeon_skill_target');
        if (system_install_schema($old_target)) {
            $old=system_install_ident($old_target);
            $skills=system_install_ident(system_install_table('skill'));
            if (system_install_one('SELECT 1 FROM '.$old.' d LEFT JOIN '.$skills.' s ON s.sk_id=d.sk_id WHERE s.sk_id IS NULL OR d.target_mode NOT IN (\'SINGLE\',\'ALL\',\'RANDOM\') LIMIT 1'))
                throw new RuntimeException('기존 던전 스킬 공격 범위에 연결되지 않은 스킬 또는 잘못된 값이 있습니다. 수동 확인 필요');
            if (system_install_one('SELECT 1 FROM '.$old.' d JOIN '.$skills.' s ON s.sk_id=d.sk_id WHERE s.sk_dungeon_target_mode <> \'SINGLE\' AND s.sk_dungeon_target_mode <> d.target_mode LIMIT 1'))
                throw new RuntimeException('던전 스킬 공격 범위가 두 저장소에서 다릅니다. 수동 확인 필요');
            system_install_query('UPDATE '.$skills.' s JOIN '.$old.' d ON d.sk_id=s.sk_id SET s.sk_dungeon_target_mode=d.target_mode');
            if (system_install_one('SELECT 1 FROM '.$old.' d JOIN '.$skills.' s ON s.sk_id=d.sk_id WHERE BINARY s.sk_dungeon_target_mode <> BINARY d.target_mode LIMIT 1'))
                throw new RuntimeException('던전 스킬 공격 범위 이전 검증에 실패했습니다.');
            system_install_query('DROP TABLE '.$old);
            $log[]='던전 스킬 공격 범위를 공용 skill로 옮기고 중복 테이블 삭제';
        }
        system_install_monster_raid_view($log);
        system_install_monster_dungeon_view($log);
    }
    if (in_array($module, array('k_battle','labyrinth'), true)) {
        list($suffix,$column,$where) = system_install_native_version($module);
        if (!system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table($suffix)).' WHERE '.$where)) {
            system_install_query(system_install_default_row($suffix, array($module === 'labyrinth' ? 'singleton' : 'cf_id'=>1, $column=>0)));
            $log[] = $module.': 기본 설정 행 추가';
        }
    }
    if ($module === 'labyrinth') {
        if (system_install_schema(system_install_table('dungeon_maze_config'))) system_install_maze_monster_references($log);
        system_install_dungeon_unify($log);
    }
    if ($module === 'k_battle') {
        $table = system_install_ident(system_install_table('k_battle_skill_info'));
        $defaults = system_install_k_defaults();
        foreach ($defaults as $row) {
            if (!system_install_one('SELECT 1 FROM '.$table.' WHERE si_code='.system_install_quote($row['si_code']))) {
                system_install_query(system_install_default_row('k_battle_skill_info', $row));
                $log[] = 'K 기본 스킬 코드 추가: '.$row['si_code'];
            }
        }
    }
    if ($module === 'quest' && !system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table('k_quest_config')).' LIMIT 1')) {
        system_install_query(system_install_default_row('k_quest_config', array('qc_id'=>1, 'qc_last_reset_date'=>date('Y-m-d'))));
        $log[] = '퀘스트 기본 설정 추가';
    }
}

/** Move the K monster catalog into monster_master, retaining the old SELECT shape as a view. */
function system_install_monster_raid_view(&$log) {
    require_once __DIR__.'/monster.lib.php';
    $source_name=system_install_table('k_battle_monster');
    $backup_name=$source_name.'_legacy_migration';
    $source=system_install_schema($source_name);
    $backup=system_install_schema($backup_name);
    if ($source && $source['TABLE_TYPE']==='VIEW' && !$backup) return;
    if ($source && $source['TABLE_TYPE']==='BASE TABLE' && $backup) throw new RuntimeException('K 몬스터 이전 임시 테이블이 이미 있습니다. 수동 확인 필요');
    if ($source && $source['TABLE_TYPE']==='BASE TABLE') {
        system_install_query('RENAME TABLE '.system_install_ident($source_name).' TO '.system_install_ident($backup_name));
        $backup=system_install_schema($backup_name);
        $source=null;
    }
    if (!$backup && $source) throw new RuntimeException('K 몬스터 조회 객체 유형을 확인하세요.');
    $master=system_install_ident(system_install_table('monster_master'));
    $old=system_install_ident($backup_name);
    try {
        if ($backup) {
            $legacy=system_install_rows('SELECT * FROM '.$old.' ORDER BY mo_id');
            if (system_install_one('SELECT m.raid_id FROM '.$master.' m LEFT JOIN '.$old.' r ON r.mo_id=m.raid_id WHERE m.raid_id IS NOT NULL AND r.mo_id IS NULL LIMIT 1'))
                throw new RuntimeException('K 원본에 없는 공용 몬스터 raid_id가 있어 자동 이전을 중단했습니다.');
            foreach ($legacy as $k) {
                $rid=(int)$k['mo_id'];
                $row=system_install_one('SELECT * FROM '.$master.' WHERE raid_id='.$rid);
                if (!$row) {
                    system_install_query('INSERT INTO '.$master.' (name,image,description,enabled,raid_id) VALUES ('.system_install_quote($k['mo_name']).','.system_install_quote($k['mo_thumb']).','.system_install_quote($k['mo_1']).',1,'.$rid.')');
                    $row=system_install_one('SELECT * FROM '.$master.' WHERE raid_id='.$rid);
                }
                $profile=$row['profile']===null || $row['profile']==='' ? array() : json_decode($row['profile'],true);
                if (!is_array($profile)) throw new RuntimeException('K 몬스터 '.$rid.'의 공용 프로필 JSON이 올바르지 않습니다.');
                if ($row['name']!==$k['mo_name'] && $row['profile']!==null) throw new RuntimeException('K 몬스터 '.$rid.'의 이름이 두 저장소에서 다릅니다.');
                if ($row['image']!=='' && $row['image']!==null && $row['image']!==$k['mo_thumb']) throw new RuntimeException('K 몬스터 '.$rid.'의 이미지가 두 저장소에서 다릅니다.');
                if (isset($profile['dg_mon_hp']) && (int)$profile['dg_mon_hp']>0 && (int)$profile['dg_mon_hp']!==(int)$k['mo_hp']) throw new RuntimeException('K 몬스터 '.$rid.'의 HP가 두 저장소에서 다릅니다.');
                if (isset($profile['shared']['mp']) && (int)$profile['shared']['mp']!==(int)$k['mo_mp']) throw new RuntimeException('K 몬스터 '.$rid.'의 MP가 두 저장소에서 다릅니다.');
                $profile['dg_mon_hp']=(int)$k['mo_hp'];
                if (!isset($profile['dg_defence'])) $profile['dg_defence']=0;
                if (!isset($profile['shared'])) $profile['k_mp']=(int)$k['mo_mp'];
                else { if(!isset($profile['shared']['mp']))$profile['shared']['mp']=(int)$k['mo_mp'];unset($profile['k_mp']); }
                $profile['k_stats']=array();for($i=1;$i<=10;$i++)$profile['k_stats'][]=(int)$k['st_'.$i];
                $profile['k_raid_type']=$k['raid_type'];
                $profile['k_pattern']=(int)$k['mo_pattern'];
                $profile['k_default_act']=(int)$k['mo_default_act'];
                $profile['k_description']=$k['mo_1'];
                $name=$row['profile']===null?$k['mo_name']:$row['name'];
                $image=$row['image']==='' || $row['image']===null?$k['mo_thumb']:$row['image'];
                system_install_query('UPDATE '.$master.' SET name='.system_install_quote($name).',image='.system_install_quote($image).',profile='.system_install_quote(json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).' WHERE monster_id='.(int)$row['monster_id']);
            }
            $max=system_install_one('SELECT GREATEST(COALESCE(MAX(monster_id),0),COALESCE(MAX(raid_id),0))+1 next_id FROM '.$master);
            system_install_query('ALTER TABLE '.$master.' AUTO_INCREMENT='.(int)$max['next_id']);
        }
        if (!$source) system_install_query(monster_raid_view_sql());
        if ($backup) {
            foreach ($legacy as $k) {
                $view=system_install_one('SELECT * FROM '.system_install_ident($source_name).' WHERE mo_id='.(int)$k['mo_id']);
                if (!$view) throw new RuntimeException('K 몬스터 '.(int)$k['mo_id'].'의 조회 뷰 검증에 실패했습니다.');
                foreach ($k as $field=>$value) if ((string)$view[$field] !== (string)$value)
                    throw new RuntimeException('K 몬스터 '.(int)$k['mo_id'].'의 '.$field.' 이전 값이 다릅니다.');
            }
            system_install_query('DROP TABLE '.$old);
            $log[]='K 몬스터 '.count($legacy).'종을 공용 monster_master로 옮기고 중복 테이블을 조회 뷰로 전환';
        } else $log[]='공용 몬스터의 K 레이드 조회 뷰 생성';
    } catch (Throwable $error) {
        // DDL is not transactional; recover the original table when possible.
        if ($backup && system_install_schema($backup_name)) {
            $current=system_install_schema($source_name);
            if ($current && $current['TABLE_TYPE']==='VIEW') system_install_query('DROP VIEW '.system_install_ident($source_name));
            if (!system_install_schema($source_name)) system_install_query('RENAME TABLE '.$old.' TO '.system_install_ident($source_name));
        }
        throw $error;
    }
}

/** Move inline maze monsters to the common catalog and remove linked snapshots. */
function system_install_maze_monster_references(&$log) {
    $config=system_install_ident(system_install_table('dungeon_maze_config'));
    $master=system_install_ident(system_install_table('monster_master'));
    $result=system_install_query('SELECT dg_id,settings FROM '.$config);
    while ($row=mysqli_fetch_assoc($result)) {
        $settings=json_decode($row['settings'],true);
        if (!is_array($settings)) throw new RuntimeException('미궁 설정 JSON을 읽을 수 없습니다: '.(int)$row['dg_id']);
        $changed=false;$created=0;$dg_id=(int)$row['dg_id'];
        system_install_query('START TRANSACTION');
        try {
            $link=function(&$entry,$field)use($master,$dg_id,&$changed,&$created) {
                if (!is_array($entry)) throw new RuntimeException('미궁 몬스터 설정 형식이 올바르지 않습니다: '.$dg_id);
                $id=(int)($entry['monster_id']??0);
                if ($id>0) {
                    if (!system_install_one('SELECT monster_id FROM '.$master.' WHERE monster_id='.$id.' AND profile IS NOT NULL'))
                        throw new RuntimeException('미궁에서 찾을 수 없는 공용 몬스터를 사용 중입니다: '.$id);
                } else {
                    $profile=$entry[$field]??null;
                    if (!is_array($profile) || (int)($profile['dg_mon_hp']??0)<1)
                        throw new RuntimeException('미궁 '. $dg_id .'의 인라인 몬스터 HP를 확인해 주세요.');
                    $name=trim((string)($profile['dg_mon_name']??''));
                    if ($name==='') $name='미궁 '.$dg_id.' 몬스터 '.($created+1);
                    $image=(string)($profile['dg_mon_img']??'');
                    $description=(string)($profile['dg_mon_descript']??'');
                    foreach (array('dg_id','dg_title','dg_count','dg_use','dg_rank','dg_point','ma_id','ma_ids','dg_per_s','dg_per_e','dg_status','dg_status_type','dg_status_value','dg_description','dg_mon_name','dg_mon_img','dg_mon_descript') as $key) unset($profile[$key]);
                    if (!isset($profile['k_raid_type'])) $profile['k_raid_type']='';
                    $json=json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    if ($json===false) throw new RuntimeException('미궁 몬스터 프로필 재인코딩 실패: '.$dg_id);
                    system_install_query('INSERT INTO '.$master.' (name,image,description,enabled,profile) VALUES ('.system_install_quote($name).','.system_install_quote($image).','.system_install_quote($description).',1,'.system_install_quote($json).')');
                    global $g5;
                    $id=(int)mysqli_insert_id($g5['connect_db']);
                    $entry['monster_id']=$id;$created++;$changed=true;
                }
                if (isset($entry[$field])) { unset($entry[$field]); $changed=true; }
            };
            if (!empty($settings['monsters'])) foreach ($settings['monsters'] as &$monster) $link($monster,'data');
            unset($monster);
            if (!empty($settings['events'])) foreach ($settings['events'] as &$event) {
                if (($event['type']??'')==='MONSTER' || !empty($event['monster_id']) || isset($event['monster'])) $link($event,'monster');
                if (!empty($event['choices'])) foreach ($event['choices'] as &$choice)
                    if (($choice['type']??'')==='MONSTER' || !empty($choice['monster_id']) || isset($choice['monster'])) $link($choice,'monster');
                unset($choice);
            }
            unset($event);
            foreach (array('title','description','max_players','points') as $field)
                if (array_key_exists($field,$settings)) {unset($settings[$field]);$changed=true;}
            if ($changed) {
                $json=json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                if ($json===false) throw new RuntimeException('미궁 설정 JSON 재인코딩 실패: '.$dg_id);
                system_install_query('UPDATE '.$config.' SET settings='.system_install_quote($json).' WHERE dg_id='.$dg_id);
                $log[]='미궁 '.$dg_id.': 인라인 몬스터 '.$created.'종 이전 및 중복 정의/기본 정보 제거';
            }
            system_install_query('COMMIT');
        } catch (Throwable $error) { system_install_query('ROLLBACK'); throw $error; }
    }
}

/** Move the old one-monster dungeon fields into the common catalog and project reads. */
function system_install_monster_dungeon_view(&$log) {
    require_once __DIR__.'/monster.lib.php';
    $table_name=system_install_table('dungeon');
    $table=system_install_ident($table_name);
    $schema=system_install_schema($table_name);
    if (!$schema || !isset($schema['columns']['dg_description'])) throw new RuntimeException('던전 설명 컬럼을 먼저 설치하세요.');
    $view_name=monster_dungeon_read_table();
    $view=system_install_schema($view_name);
    if ($view && $view['TABLE_TYPE']!=='VIEW') throw new RuntimeException('던전 몬스터 읽기 뷰 이름에 다른 테이블이 있습니다.');
    $old_fields=array_intersect_key(monster_dungeon_legacy_fields(),$schema['columns']);
    if ($old_fields) {
        $master=system_install_ident(system_install_table('monster_master'));
        $pool=system_install_ident(system_install_table('dungeon_monster_pool'));
        foreach (system_install_rows('SELECT * FROM '.$table.' ORDER BY dg_id') as $dg) {
            $id=(int)$dg['dg_id'];
            $old_description=(string)($dg['dg_mon_descript']??'');
            if ($old_description!=='' && (string)$dg['dg_description']!=='' && (string)$dg['dg_description']!==$old_description)
                throw new RuntimeException('던전 '.$id.'의 설명 값이 두 저장 위치에서 다릅니다.');
            if ($old_description!=='' && (string)$dg['dg_description']==='')
                system_install_query('UPDATE '.$table.' SET dg_description='.system_install_quote($old_description).' WHERE dg_id='.$id);
            $existing=system_install_one('SELECT m.* FROM '.$pool.' p JOIN '.$master.' m ON m.monster_id=p.monster_id WHERE p.dg_id='.$id.' AND p.enabled=1 AND m.enabled=1 ORDER BY p.is_boss DESC,p.monster_id LIMIT 1');
            $meaningful=(string)($dg['dg_mon_name']??'')!=='' || (string)($dg['dg_mon_img']??'')!=='' || (int)($dg['dg_mon_hp']??0)>0;
            foreach ($old_fields as $field=>$ddl) {
                if(in_array($field,array('dg_mon_name','dg_mon_img','dg_mon_descript','dg_mon_hp'),true))continue;
                if((string)($dg[$field]??'')!=='' && (string)($dg[$field]??'')!=='0')$meaningful=true;
            }
            if (!$meaningful)continue;
            if ($existing) {
                $profile=json_decode($existing['profile']??'{}',true);
                if(!is_array($profile))throw new RuntimeException('던전 '.$id.'의 대표 몬스터 JSON이 잘못되었습니다.');
                if((string)($dg['dg_mon_name']??'')!=='' && $dg['dg_mon_name']!==$existing['name'])throw new RuntimeException('던전 '.$id.'의 구형 몬스터 이름과 공용 출현 목록이 다릅니다.');
                if((string)($dg['dg_mon_img']??'')!=='' && $dg['dg_mon_img']!==$existing['image'])throw new RuntimeException('던전 '.$id.'의 구형 몬스터 이미지와 공용 출현 목록이 다릅니다.');
                foreach($old_fields as $field=>$ddl) {
                    if(in_array($field,array('dg_mon_name','dg_mon_img','dg_mon_descript'),true))continue;
                    $value=(string)($dg[$field]??'');if($value==='' || $value==='0')continue;
                    if((string)($profile[$field]??'')!==$value)throw new RuntimeException('던전 '.$id.'의 구형 '.$field.' 값과 공용 몬스터가 다릅니다.');
                }
                continue;
            }
            if (trim((string)($dg['dg_mon_name']??''))==='' || (int)($dg['dg_mon_hp']??0)<1)
                throw new RuntimeException('던전 '.$id.'의 구형 몬스터 이름과 HP를 확인하세요.');
            $profile=array();
            foreach($old_fields as $field=>$ddl)if(!in_array($field,array('dg_mon_name','dg_mon_img','dg_mon_descript'),true))
                $profile[$field]=preg_match('/^int\(/',$ddl)?(int)($dg[$field]??0):(string)($dg[$field]??'');
            system_install_query('INSERT INTO '.$master.' (name,image,description,enabled,profile) VALUES ('.system_install_quote($dg['dg_mon_name']).','.system_install_quote($dg['dg_mon_img']??'').',\'\',1,'.system_install_quote(json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).')');
            $monster_id=(int)mysqli_insert_id($GLOBALS['g5']['connect_db']);
            system_install_query('INSERT INTO '.$pool.' (dg_id,monster_id,weight,enabled,is_boss) VALUES ('.$id.','.$monster_id.',1,1,0)');
        }
        if ($view) system_install_query('DROP VIEW '.system_install_ident($view_name));
        $drop=array();foreach(array_keys($old_fields) as $field)$drop[]='DROP COLUMN '.system_install_ident($field);
        system_install_query('ALTER TABLE '.$table.' '.implode(',',$drop));
        $log[]='던전의 구형 몬스터 컬럼을 공용 몬스터 정의와 던전 설명으로 이전';
        $view=null;
    }
    if (!$view) { system_install_query(monster_dungeon_read_view_sql());$log[]='공용 몬스터 기반 던전 전투 읽기 뷰 생성'; }
}

function system_install_k_defaults() {
    static $defaults;
    if ($defaults === null) {
        $defaults = json_decode(file_get_contents(__DIR__.'/../install/system/k_battle/defaults.json'), true);
        if (!is_array($defaults) || count($defaults) !== 6) throw new RuntimeException('K 기본 스킬 자료를 읽을 수 없습니다.');
    }
    return $defaults;
}

function system_install_missing_defaults($module) {
    $missing = array();
    if ($module === 'monsters' && system_install_schema(system_install_table('monster_master'))) {
        require_once __DIR__.'/monster.lib.php';
        $raid_view=system_install_schema(system_install_table('k_battle_monster'));
        if (!$raid_view || $raid_view['TABLE_TYPE']!=='VIEW') $missing[]='K 몬스터 공용 조회 뷰 없음';
        $dungeon_view=system_install_schema(monster_dungeon_read_table());
        if (!$dungeon_view || $dungeon_view['TABLE_TYPE']!=='VIEW') $missing[]='던전 몬스터 공용 조회 뷰 없음';
    }
    $native = system_install_native_version($module);
    if ($native) {
        list($suffix,$column,$where) = $native;
        if (!system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table($suffix)).' WHERE '.$where)) $missing[] = $module.': 기본 설정 행 없음';
    }
    if ($module === 'k_battle') foreach (system_install_k_defaults() as $row) {
        if (!system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table('k_battle_skill_info')).' WHERE si_code='.system_install_quote($row['si_code']))) $missing[] = 'K 기본 스킬 코드 없음: '.$row['si_code'];
    }
    if ($module === 'quest' && !system_install_one('SELECT 1 FROM '.system_install_ident(system_install_table('k_quest_config')).' LIMIT 1')) $missing[] = '퀘스트 기본 설정 행 없음';
    return $missing;
}

/** Re-plan after CREATE LIKE / partial DDL. Every pass must make progress. */
function system_install_apply_contract($module, $version, $contract, &$log, &$step) {
    for ($pass = 0; $pass < 3; $pass++) {
        $step = $module.' v'.$version.': 스키마 재검증';
        $plan = array('steps'=>array(),'missing'=>array(),'manual'=>array(),'present'=>0);
        foreach ($contract as $suffix=>$spec) system_install_plan_table($suffix, $spec, $plan);
        if ($plan['manual']) throw new RuntimeException(implode('; ', $plan['manual']));
        if (!$plan['missing']) { $log[] = $module.' v'.$version.': 스키마 확인 완료'; return; }
        if (!$plan['steps']) throw new RuntimeException(implode('; ', $plan['missing']));
        foreach ($plan['steps'] as $change) {
            $step = $module.' v'.$version.': '.$change['label'];
            system_install_query($change['sql']);
            $log[] = $step;
        }
    }
    throw new RuntimeException($module.' v'.$version.': 부분 적용 후 구조를 다시 확인해 주세요.');
}

function system_install_inventory_preflight() {
    $table = system_install_table('inventory');
    $schema = system_install_schema($table);
    $reasons = array();
    if (!$schema || $schema['TABLE_TYPE'] !== 'BASE TABLE') $reasons[] = '인벤토리 테이블이 없습니다.';
    elseif (!in_array(strtolower($schema['ENGINE']), array('myisam','innodb'), true)) $reasons[] = '지원하지 않는 엔진입니다: '.$schema['ENGINE'];
    if ($schema) {
        if (!isset($schema['indexes']['PRIMARY'], $schema['columns']['in_id']) || $schema['indexes']['PRIMARY']['columns'] !== array('in_id') || stripos($schema['columns']['in_id']['Extra'], 'auto_increment') === false) $reasons[] = 'in_id 단일 AUTO_INCREMENT PRIMARY KEY가 필요합니다.';
        foreach ($schema['indexes'] as $index) if ($index['type'] === 'FULLTEXT') $reasons[] = 'FULLTEXT 인덱스가 있어 개별 검토가 필요합니다.';
        if (system_install_one('SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND EVENT_OBJECT_TABLE='.system_install_quote($table).' LIMIT 1')) $reasons[] = '트리거가 있어 개별 검토가 필요합니다.';
    }
    return array('table'=>$table, 'engine'=>$schema ? $schema['ENGINE'] : '없음', 'reasons'=>$reasons);
}

/** Explicit opt-in only; caller holds the installer lock and has authorized POST. */
function system_install_convert_inventory(&$log) {
    $states = system_install_all_status();
    if (!$states['base']['installed']) throw new RuntimeException('기본 확장을 먼저 설치해 주세요.');
    $check = system_install_inventory_preflight();
    if ($check['reasons']) throw new RuntimeException(implode('; ', $check['reasons']));
    if (strcasecmp($check['engine'], 'InnoDB') === 0) { $log[] = 'Inventory: 이미 InnoDB입니다.'; return; }
    system_install_query('ALTER TABLE '.system_install_ident($check['table']).' ENGINE=InnoDB');
    if (strcasecmp(system_install_schema($check['table'])['ENGINE'], 'InnoDB') !== 0) throw new RuntimeException('엔진 전환 후 검증 실패');
    $log[] = 'Inventory: InnoDB 전환 완료. 인벤토리 안전 처리와 미궁 설치를 진행할 수 있습니다.';
}

function system_install_qa_status($states) {
    $missing = array();
    // This summary describes the entire installer, including field, room,
    // quest and monster setup. Keep it consistent with the module cards above.
    foreach (system_install_registry() as $module=>$info) {
        if (!isset($states[$module])) $missing[] = $info['name'].' — 상태 확인 필요';
        elseif (!$states[$module]['installed'] || $states[$module]['blocked'] || $states[$module]['status'] !== '설치됨') $missing[] = $info['name'].' — '.$states[$module]['status'];
    }
    // Core Avocado tables are prerequisites, never recreated by this installer.
    foreach (array('config','member','point') as $suffix) {
        if (!system_install_schema(system_install_table($suffix))) $missing[] = $suffix.' 기본 테이블 없음';
    }
    return $missing;
}
