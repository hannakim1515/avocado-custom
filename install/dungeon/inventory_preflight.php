<?php
/**
 * Read-only CLI inventory inspection. Does not bootstrap common.php (extensions
 * may execute DDL on load). No table is modified by this program.
 * PHP 7.4: php install/dungeon/inventory_preflight.php [--config=/path/dbconfig.php]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', array('help', 'config:'));
if (isset($options['help'])) {
    echo "Read-only inventory schema inspection (no DDL/DML).\n";
    echo "php install/dungeon/inventory_preflight.php [--config=/path/dbconfig.php]\n";
    echo "Exit codes: 0 inspection completed; 1 connection/query failure; 2 configuration missing.\n";
    echo "A successful inspection is NOT approval to enable labyrinth start.\n";
    exit(0);
}
if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "mysqli extension is required.\n");
    exit(2);
}
$configuration = isset($options['config']) ? $options['config'] : dirname(__DIR__, 2).'/data/dbconfig.php';
if (!is_file($configuration)) {
    fwrite(STDERR, "Database configuration file was not found.\n");
    exit(2);
}
define('_GNUBOARD_', true);
require $configuration;
foreach (array('G5_MYSQL_HOST', 'G5_MYSQL_USER', 'G5_MYSQL_PASSWORD', 'G5_MYSQL_DB', 'G5_TABLE_PREFIX') as $constant) {
    if (!defined($constant)) {
        fwrite(STDERR, "Database configuration is incomplete.\n");
        exit(2);
    }
}

mysqli_report(MYSQLI_REPORT_OFF);
$connection = @new mysqli(G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);
if ($connection->connect_errno) {
    // Do not print credentials, host names or connection error details.
    fwrite(STDERR, "Database connection unavailable; no schema was inspected or changed.\n");
    exit(1);
}
if (!$connection->set_charset('utf8mb4')) {
    fwrite(STDERR, "Could not set connection character set.\n");
    exit(1);
}
$inventory = isset($g5['inventory_table']) ? $g5['inventory_table'] : G5_TABLE_PREFIX.'inventory';
if (!preg_match('/^[A-Za-z0-9_]+$/D', $inventory)) {
    fwrite(STDERR, "Unsupported inventory table identifier.\n");
    exit(2);
}
$escaped = $connection->real_escape_string($inventory);
$queries = array(
    'server' => 'SELECT VERSION() AS version, @@sql_mode AS sql_mode',
    'table' => "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, AUTO_INCREMENT, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH
        FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$escaped}'",
    'columns' => "SHOW FULL COLUMNS FROM `{$inventory}`",
    'indexes' => "SHOW INDEX FROM `{$inventory}`",
    'foreign_keys' => "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE()
        AND (REFERENCED_TABLE_NAME = '{$escaped}' OR (TABLE_NAME = '{$escaped}' AND REFERENCED_TABLE_NAME IS NOT NULL))",
    'triggers' => "SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING
        FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '{$escaped}'",
    'definition' => "SHOW CREATE TABLE `{$inventory}`"
);
$report = array('read_only' => true, 'inventory' => $inventory);
foreach ($queries as $label => $query) {
    $result = $connection->query($query);
    if (!$result) {
        fwrite(STDERR, "Inspection query failed: {$label} (database error ".$connection->errno.").\n");
        exit(1);
    }
    $report[$label] = array();
    while ($row = $result->fetch_assoc()) {
        $report[$label][] = $row;
    }
    $result->free();
}
$report['notes'] = array(
    'No migration was applied. TABLE_ROWS is an engine estimate.',
    'Foreign key output does not include application-only references such as k_battle_equip_ch.in_id.',
    'Review server version, persistent AUTO_INCREMENT behavior, triggers, references and all custom columns before same-ID restoration.',
    'InnoDB alone does not prevent legacy read-effect-delete requests from racing with inventory isolation.',
    'Labyrinth readiness must additionally validate every new table, column, index, schema version and the cross-endpoint concurrency policy.'
);
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Inspection output could not be encoded.\n");
    exit(1);
}
echo $json.PHP_EOL;
$connection->close();
