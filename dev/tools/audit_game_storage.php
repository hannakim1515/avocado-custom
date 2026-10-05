<?php
/** Read-only inventory of game tables. Run: php dev/tools/audit_game_storage.php */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('_GNUBOARD_', true);
require dirname(__DIR__, 2).'/data/dbconfig.php';

mysqli_report(MYSQLI_REPORT_OFF);
$db = @mysqli_connect(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(2);
}
if (!mysqli_set_charset($db, 'utf8mb4')) { fwrite(STDERR, "Database character set failed.\n"); exit(2); }
$prefix = G5_TABLE_PREFIX;
$families = array('dungeon', 'monster', 'k_battle', 'k_raid', 'battle', 'skill', 'status', 'unified');
$result = mysqli_query($db, 'SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
if (!$result) { fwrite(STDERR, "Database table inspection failed.\n"); exit(2); }
$tables = array();
while ($row = mysqli_fetch_assoc($result)) {
    $name = $row['TABLE_NAME'];
    if (strpos($name, $prefix) !== 0) continue;
    $suffix = substr($name, strlen($prefix));
    $relevant = false;
    foreach ($families as $family) {
        if (strpos($suffix, $family) === 0) { $relevant = true; break; }
    }
    if (!$relevant) continue;
    $count = mysqli_query($db, 'SELECT COUNT(*) AS n FROM `'.str_replace('`', '``', $name).'`');
    $tables[] = array('table'=>$suffix, 'rows'=>$count ? (int)mysqli_fetch_assoc($count)['n'] : null, 'engine'=>$row['ENGINE']);
}
echo json_encode($tables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
