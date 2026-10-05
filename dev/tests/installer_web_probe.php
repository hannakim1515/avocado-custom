<?php
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$fixture = json_decode(file_get_contents($root.'/artifacts/system-installer-web.json'), true);
if (!preg_match('/^codex_install_web_[a-f0-9]+$/D', $fixture['database'])) throw new RuntimeException('Invalid fixture DB');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', $fixture['database'], 33381);
if (strpos(str_replace('\\','/',$db->query('SELECT @@datadir AS d')->fetch_assoc()['d']), '/codex-system-installer-') === false) throw new RuntimeException('Wrong server');
$db->set_charset('utf8mb4');
define('_GNUBOARD_', true); define('G5_TABLE_PREFIX', 'web_');
$g5 = array('connect_db'=>$db);
require $fixture['site'].'/lib/system_installer.lib.php';
if (($argv[1] ?? '') === 'install') {
    $is_admin = 'super'; $_SESSION = array(); $_POST = array('token'=>system_install_token()); $_SERVER['REQUEST_METHOD'] = 'POST';
    $r = system_install_run('all');
    if ($r['errors']) throw new RuntimeException(implode('; ', $r['errors']));
    $_POST['backup_confirm'] = $_POST['writers_confirm'] = '1';
    $r = system_install_run('inventory_engine', 'inventory_engine');
    if ($r['errors']) throw new RuntimeException(implode('; ', $r['errors']));
    $r = system_install_run('all');
    if ($r['errors']) throw new RuntimeException(implode('; ', $r['errors']));
}
$tables = system_install_rows('SHOW TABLES');
$schema = array();
foreach ($tables as $table) {
    $name = reset($table);
    $ddl = system_install_one('SHOW CREATE TABLE '.system_install_ident($name));
    $schema[$name] = $ddl['Create Table'] ?? $ddl['Create View'];
}
ksort($schema);
$objects = system_install_rows('SELECT TABLE_NAME name,TABLE_TYPE type FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME');
echo json_encode(array('schema_hash'=>hash('sha256', json_encode($schema)), 'tables'=>array_keys($schema), 'objects'=>$objects, 'states'=>system_install_all_status()), JSON_UNESCAPED_UNICODE);
