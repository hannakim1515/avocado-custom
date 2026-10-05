<?php
/** Runs only on the disposable server started by dev/tools/system_test_db.ps1. */
if (PHP_SAPI !== 'cli') exit(1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', '', 33381);
$datadir = str_replace('\\', '/', $db->query('SELECT @@datadir AS d')->fetch_assoc()['d']);
if (strpos($datadir, '/codex-system-installer-') === false) throw new RuntimeException('Refusing a non-disposable server');
$prefix = isset($argv[1]) ? $argv[1] : 'avo_';
if (!preg_match('/^[a-z][a-z0-9_]*_$/D', $prefix)) throw new RuntimeException('Invalid fixture prefix');
$database = 'codex_install_test_'.bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');
$db->select_db($database);
$db->set_charset('utf8mb4');
define('_GNUBOARD_', true);
define('G5_TABLE_PREFIX', $prefix);
$g5 = array('connect_db'=>$db);
$is_admin = 'super';
$runtime_root = isset($argv[2]) ? realpath($argv[2]) : dirname(__DIR__, 2);
if (!$runtime_root || !is_file($runtime_root.'/lib/system_installer.lib.php')) throw new RuntimeException('Invalid runtime root');
$_SESSION = array();
$_SERVER['REQUEST_METHOD'] = 'POST';
require $runtime_root.'/lib/system_installer.lib.php';
$_POST = array('token'=>system_install_token());
function check($condition, $message) {
    if (!$condition) throw new LogicException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}
function fresh_schema() { $GLOBALS['system_install_schema_cache'] = array(); }
function run_ok($module) {
    $r = system_install_run($module);
    if ($r['errors'] || $r['skipped']) throw new RuntimeException($module.' '.implode('; ', array_merge($r['errors'], $r['skipped'])));
    check(system_install_inspect($module)['installed'], $module.' installed and version recorded');
    return $r;
}
function rejects($call, $label) {
    $failed = false;
    try { $call(); } catch (RuntimeException $e) { $failed = true; }
    check($failed, $label);
}
function row($sql) { return system_install_one($sql); }
function table_q($suffix) { return system_install_ident(system_install_table($suffix)); }
function version_set($module, $version) {
    system_install_query('UPDATE '.table_q('system_migration_meta').' SET version='.system_install_quote($version).' WHERE module='.system_install_quote($module));
    $native = system_install_native_version($module);
    if ($native) system_install_query('UPDATE '.table_q($native[0]).' SET '.system_install_ident($native[1]).'='.system_install_quote($version).' WHERE '.$native[2]);
}
function retired_storage_absent($stage) {
    $retired = array('dungeon_maze_config','dungeon_maze_session','dungeon_monster_config',
        'dungeon_state_monster','dungeon_member','dungeon_log','monster_dungeon_profile',
        'dungeon_skill_target','dungeon_maze_item_effect','k_battle_monster_legacy_migration');
    foreach ($retired as $suffix) if (system_install_schema(system_install_table($suffix))) {
        throw new LogicException($stage.': retired storage returned: '.$suffix);
    }
    check(true, $stage.': retired dungeon and monster storage is absent');
}
function schema_fingerprint() {
    $schema = array();
    foreach (system_install_rows('SHOW FULL TABLES') as $table) {
        $name = reset($table); $ddl = system_install_one('SHOW CREATE TABLE '.system_install_ident($name));
        $schema[$name] = $ddl['Create Table'] ?? $ddl['Create View'];
    }
    ksort($schema);return hash('sha256', json_encode($schema));
}
try {
    // The actual base-install SQL is allowed only inside this newly created DB.
    $sql = file_get_contents($runtime_root.'/install/gnuboard5.sql');
    $sql = str_replace('`avo_', '`'.$prefix, $sql);
    $db->query("SET SESSION sql_mode=''");
    $db->multi_query($sql);
    do { if ($r = $db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
    system_install_query(system_install_default_row('config', array('cf_admin'=>'fixture_admin')));
    system_install_query(system_install_default_row('member', array('mb_id'=>'fixture_admin', 'mb_level'=>10)));
    $db->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
    fresh_schema();
    $initial = system_install_all_status();
    check(!$initial['map']['installed'] && $initial['map']['status'] === '선행 기능 필요' && $initial['k_battle']['status'] === '선행 기능 필요', 'fresh DB shows missing prerequisite without structural error');
    $before = count(system_install_rows('SHOW TABLES'));
    system_install_all_status();
    check(count(system_install_rows('SHOW TABLES')) === $before && !system_install_schema(system_install_table('system_migration_meta')), 'read-only inspection creates no metadata or tables');
    $blocked = system_install_run('realtime');
    check(!$blocked['success'] && $blocked['skipped'] && !system_install_schema(system_install_table('k_battle_realtime')), 'realtime blocked without K battle');

    $_SERVER['REQUEST_METHOD'] = 'GET'; rejects(function() { system_install_run('base'); }, 'GET rejected');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $is_admin = ''; rejects(function() { system_install_run('base'); }, 'ordinary member rejected'); $is_admin = 'super';
    $token = $_POST['token']; $_POST['token'] = 'wrong'; rejects(function() { system_install_run('base'); }, 'CSRF rejected'); $_POST['token'] = $token;
    rejects(function() { system_install_run('../base'); }, 'unknown module rejected');
    rejects(function() { system_install_run(array('base')); }, 'array module rejected');
    rejects(function() { system_install_run('base', 'reinstall'); }, 'destructive action rejected');

    foreach (array('base','map','field','room','quest','dungeon','k_battle','unified','monsters','realtime') as $module) {
        run_ok($module);retired_storage_absent('fresh '.$module);
        if ($module === 'k_battle') check(!system_install_schema(system_install_table('k_battle_monster')), 'fresh K installation never creates a separate monster table');
    }
    check(system_install_schema(system_install_table('k_battle_monster'))['TABLE_TYPE'] === 'VIEW', 'fresh monsters install exposes the shared catalog through a view');
    check(!system_install_schema(system_install_table('dungeon_skill_target')) && isset(system_install_schema(system_install_table('skill'))['columns']['sk_dungeon_target_mode']), 'new install keeps dungeon target mode in skill');
    system_install_query(system_install_default_row('skill', array('sk_name'=>'migration skill')));
    $target_skill=(int)$db->insert_id;
    system_install_query('CREATE TABLE '.table_q('dungeon_skill_target').' (sk_id int NOT NULL PRIMARY KEY,target_mode varchar(12) NOT NULL) ENGINE=InnoDB');
    system_install_query('INSERT INTO '.table_q('dungeon_skill_target').' VALUES ('.$target_skill.',\'ALL\')');
    version_set('monsters','1.3.0');
    run_ok('monsters');
    check(row('SELECT sk_dungeon_target_mode AS mode FROM '.table_q('skill').' WHERE sk_id='.$target_skill)['mode'] === 'ALL' && !system_install_schema(system_install_table('dungeon_skill_target')), 'existing dungeon target values migrate before redundant table removal');
    system_install_query(system_install_default_row('inventory', array('in_memo'=>"보존; 'quoted' \\ text", 'ch_id'=>7)));
    $original_inventory = row('SELECT * FROM '.table_q('inventory').' LIMIT 1');
    $original_member = row('SELECT * FROM '.table_q('member').' LIMIT 1');
    check(strcasecmp(system_install_schema(system_install_table('inventory'))['ENGINE'], 'MyISAM') === 0, 'ordinary installation preserves inventory engine');
    check((int)row('SELECT COUNT(*) AS n FROM '.table_q('k_battle_skill_info'))['n'] === 6, 'six default skill codes seeded');
    check(system_install_schema(system_install_table('inventory'))['columns'] === system_install_schema(system_install_table('k_quest_inven'))['columns'], 'quest inventory columns and order match inventory');
    $before = count(system_install_rows('SHOW TABLES'));
    $again = system_install_run('all');
    check(!$again['errors'] && !$again['success'] && count(system_install_rows('SHOW TABLES')) === $before, 'idempotent repeat has no changes; engine-dependent modules skipped');
    check(strcasecmp(system_install_schema(system_install_table('inventory'))['ENGINE'], 'MyISAM') === 0, 'all never converts inventory engine');
    rejects(function() { system_install_run('inventory_engine', 'inventory_engine'); }, 'engine change requires separate confirmations');
    $_POST['backup_confirm'] = $_POST['writers_confirm'] = '1';
    $conversion = system_install_run('inventory_engine', 'inventory_engine');
    check(!$conversion['errors'] && system_install_inspect('inventory_engine')['installed'], 'explicit inventory conversion works');
    run_ok('inventory_boundary'); run_ok('labyrinth');
    retired_storage_absent('full fresh install');
    $schema_before_repeat = schema_fingerprint();
    $full_repeat = system_install_run('all');
    check(!$full_repeat['errors'] && !$full_repeat['success'] && schema_fingerprint() === $schema_before_repeat, 'fully installed repeat changes no tables, columns or views');
    retired_storage_absent('full install repeat');
    check(row('SELECT * FROM '.table_q('inventory').' LIMIT 1') === $original_inventory, 'inventory rows survive engine conversion and boundary installation');
    check(row('SELECT * FROM '.table_q('member').' LIMIT 1') === $original_member && strcasecmp(system_install_schema(system_install_table('member'))['ENGINE'], 'MyISAM') === 0 && strcasecmp(system_install_schema(system_install_table('point'))['ENGINE'], 'MyISAM') === 0, 'member data and member/point engines preserved');
    check(!system_install_qa_status(system_install_all_status()), 'QADATA schema ready');

    // Replay MySQL 8 SHOW FULL COLUMNS metadata on the real field contract.
    // MariaDB leaves Extra empty for the same CURRENT_TIMESTAMP definition.
    $field_dates = array('field_player'=>'fp_datetime','field_log'=>'fl_datetime');
    foreach ($field_dates as $suffix=>$column) {
        $table = system_install_table($suffix);
        $original = system_install_schema($table)['columns'][$column];
        $mysql = $original; $mysql['Extra'] = 'DEFAULT_GENERATED';
        $GLOBALS['system_install_schema_cache'][$table]['columns'][$column] = $mysql;
        $field = system_install_inspect('field');
        check($field['installed'] && !$field['manual'] && !$field['steps'], $column.': MySQL expression default is accepted without DDL');
        $ddl = system_install_contract()['field'][$suffix]['columns'][$column];
        $mysql['Extra'] = 'DEFAULT_GENERATED on update CURRENT_TIMESTAMP';
        check(system_install_column_ok($mysql, $ddl), $column.': combined MySQL default/update metadata is accepted');
        foreach (array('VIRTUAL GENERATED','STORED GENERATED','PERSISTENT GENERATED') as $extra) {
            $mysql['Extra'] = $extra;
            check(!system_install_column_ok($mysql, $ddl), $column.': computed '.$extra.' remains incompatible');
        }
        $GLOBALS['system_install_schema_cache'][$table]['columns'][$column] = $original;
    }
    // An imported installation without migration metadata needs registration,
    // not a structural repair of its already valid datetime columns.
    system_install_query('DELETE FROM '.table_q('system_migration_meta')." WHERE module='field'");
    $field = system_install_inspect('field');
    check($field['ready'] && !$field['installed'] && !$field['manual'] && !$field['steps'] && $field['version'] === '0', 'valid unregistered field schema is ready for version registration');
    run_ok('field');

    // Preserve real nullability errors, and report them in the page summary.
    system_install_query('ALTER TABLE '.table_q('field_player').' MODIFY fp_datetime datetime NULL DEFAULT CURRENT_TIMESTAMP');
    $states = system_install_all_status();
    check($states['field']['status'] === '구조 이상' && strpos(implode(' ', $states['field']['manual']), 'NULL 허용') !== false, 'real nullable datetime remains a clearly described structural error');
    check(in_array(system_install_registry()['field']['name'].' — 구조 이상', system_install_qa_status($states), true), 'field error prevents the whole-installation ready summary');
    if (!defined('G5_SYSTEM_INSTALLER')) define('G5_SYSTEM_INSTALLER', true);
    if (!defined('G5_ADMIN_URL')) define('G5_ADMIN_URL', '/adm');
    function system_install_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    $registry = system_install_registry(); $result = null;
    $inventory = system_install_inventory_preflight(); $qa_missing = system_install_qa_status($states);
    ob_start(); include $runtime_root.'/adm/system_install.view.php'; $html = ob_get_clean();
    $summary = substr($html, strpos($html, '<section class="si-qa"'));
    check(strpos($summary, '전체 설치 상태') !== false && strpos($summary, '필드 탐색 / 낚시 — 구조 이상') !== false && strpos($summary, '전체 기능의 설치 구조와 버전 확인 완료') === false, 'rendered footer lists field error and does not claim readiness');
    system_install_query('ALTER TABLE '.table_q('field_player').' MODIFY fp_datetime datetime NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $healthy = system_install_all_status();
    check(!system_install_qa_status($healthy), 'restored field schema returns the summary to ready');
    foreach (array('room','quest','monsters') as $module) {
        $broken = $healthy; $broken[$module]['installed'] = false; $broken[$module]['status'] = '구조 이상';
        check(in_array($registry[$module]['name'].' — 구조 이상', system_install_qa_status($broken), true), $module.': errors cannot be omitted from whole-installation summary');
    }
    check((int)row('SELECT schema_version FROM '.table_q('dungeon_maze_meta').' WHERE singleton=1')['schema_version'] === 1, 'native maze schema version remains 1');
    system_install_query('UPDATE '.table_q('dungeon_maze_meta').' SET schema_version=2 WHERE singleton=1');
    check(system_install_inspect('labyrinth')['status'] === '구조 이상' && system_install_run('labyrinth')['skipped'], 'native future maze version cannot be hidden by registry version');
    system_install_query('UPDATE '.table_q('dungeon_maze_meta').' SET schema_version=1 WHERE singleton=1');

    function sql_query($sql, $error = true) { global $db; try { return $db->query($sql); } catch (Throwable $e) { if ($error) throw $e; return false; } }
    function sql_fetch($sql, $error = true) { $r = sql_query($sql, $error); return $r ? $r->fetch_assoc() : false; }
    function sql_fetch_array($r) { return $r->fetch_assoc(); }
    function sql_escape_string($s) { global $db; return $db->real_escape_string($s); }
    $g5['inventory_table'] = system_install_table('inventory');
    require $runtime_root.'/extend/inventory_boundary.lib.php';
    require $runtime_root.'/extend/dungeon_maze.lib.php';
    check(maze_readiness()['ready'], 'actual maze runtime accepts installed contracts');

    // A real historical K delta, then its latest additive reconciliation.
    version_set('k_battle', '1.0.0');
    system_install_query('ALTER TABLE '.table_q('k_battle_skill').' DROP INDEX idx_raid_type, DROP COLUMN unit_type');
    check(system_install_inspect('k_battle')['status'] === '업데이트 필요', 'old version shows update');
    $update = run_ok('k_battle');
    retired_storage_absent('legacy K version update');
    check(system_install_schema(system_install_table('k_battle_monster'))['TABLE_TYPE'] === 'VIEW', 'legacy K update preserves the shared monster view');
    $log = implode("\n", $update['success']);
    check(strpos($log, 'k_battle v2.0.0:') < strpos($log, 'k_battle v2.0.2:'), 'migrations run in version order');
    check(system_install_version('k_battle') === '2.1.0', 'version advances only after verification');

    // Inject a real DB failure after DDL, then retry without losing rows.
    version_set('k_battle', '1.0.0');
    system_install_query('DELETE FROM '.table_q('k_battle_skill_info')." WHERE si_code='atk'");
    define('G5_MYSQL_PASSWORD', 'fixture-secret-never-render');
    system_install_query('CREATE TRIGGER '.system_install_ident($prefix.'fail_seed').' BEFORE INSERT ON '.table_q('k_battle_skill_info')." FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture-secret-never-render'");
    $failed = system_install_run('k_battle');
    check($failed['errors'] && system_install_version('k_battle') === '1.0.0', 'DB failure leaves version unchanged');
    check(strpos(implode(' ', $failed['errors']), G5_MYSQL_PASSWORD) === false && strpos(implode(' ', $failed['errors']), 'v2.1.0') !== false, 'failure includes module/version/step and redacts credentials');
    system_install_query('DROP TRIGGER '.system_install_ident($prefix.'fail_seed'));
    run_ok('k_battle');
    check((int)row('SELECT COUNT(*) AS n FROM '.table_q('k_battle_skill_info'))['n'] === 6, 'retry restores only missing defaults without duplicates');

    system_install_query('ALTER TABLE '.table_q('newmap_config').' DROP COLUMN ma_npc_name');
    check(system_install_inspect('map')['status'] === '구조 이상', 'latest version with missing column is structural error');
    run_ok('map');
    system_install_query('ALTER TABLE '.table_q('newmap_config').' MODIFY ma_npc_name int NOT NULL DEFAULT 0');
    $mismatch = system_install_run('map');
    check($mismatch['skipped'] && !$mismatch['success'] && system_install_schema(system_install_table('newmap_config'))['columns']['ma_npc_name']['Type'] === 'int(11)', 'type mismatch never automatically modified');
    system_install_query('ALTER TABLE '.table_q('newmap_config')." MODIFY ma_npc_name varchar(255) NOT NULL DEFAULT ''");

    system_install_query('ALTER TABLE '.table_q('k_quest_has').' DROP INDEX uk_qu_ch');
    system_install_query('INSERT INTO '.table_q('k_quest_has').' (qu_id,ch_id) VALUES (41,42),(41,42)');
    $duplicate = system_install_run('quest');
    check($duplicate['skipped'] && strpos(implode(' ', $duplicate['skipped']), '중복') !== false && (int)row('SELECT COUNT(*) AS n FROM '.table_q('k_quest_has'))['n'] === 2, 'duplicate UNIQUE is reported without deleting rows');

    $db2 = new mysqli('127.0.0.1', 'root', '', $database, 33381);
    $name = system_install_lock_name();
    $db2->query("SELECT GET_LOCK('".$db2->real_escape_string($name)."',0)");
    rejects(function() { system_install_run('base'); }, 'concurrent installer rejected under advisory lock');
    $db2->query("SELECT RELEASE_LOCK('".$db2->real_escape_string($name)."')"); $db2->close();
    check(system_install_run('base')['skipped'], 'lock released for subsequent request');
    system_install_query('ALTER TABLE '.table_q('system_migration_meta').' DROP PRIMARY KEY');
    check(system_install_all_status()['base']['status'] === '구조 이상', 'damaged version registry is not reported healthy');
    check(system_install_run('base')['errors'], 'damaged version registry blocks schema changes');
    echo 'COMPLETE: '.$prefix."\n";
} finally {
    $db->query('DROP DATABASE `'.$database.'`');
    $db->close();
}
