<?php
// Test-only web fixture. Never include this file from runtime code.
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__, 2);
$fixture_name=$argv[1]??'system-installer-site';
if(!preg_match('/^[a-z0-9-]+$/D',$fixture_name))throw new RuntimeException('Invalid fixture name');
$site = realpath($root.'/artifacts/'.$fixture_name);
if (!$site || !is_dir($site.'/data') || is_file($site.'/data/dbconfig.php')) throw new RuntimeException('Build a fresh system-installer-site release first');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', '', 33381);
$datadir = str_replace('\\', '/', $db->query('SELECT @@datadir AS d')->fetch_assoc()['d']);
if (strpos($datadir, '/codex-system-installer-') === false) throw new RuntimeException('Refusing a non-disposable server');
$database = 'codex_install_web_'.bin2hex(random_bytes(5));
$db->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');
$db->select_db($database);
$db->set_charset('utf8mb4');
$db->query("SET SESSION sql_mode=''");
define('_GNUBOARD_', true); define('G5_TABLE_PREFIX', 'web_');
$g5 = array('connect_db'=>$db);
require $site.'/lib/system_installer.lib.php';
$db->multi_query(str_replace('`avo_', '`web_', file_get_contents($site.'/install/gnuboard5.sql')));
do { if ($r=$db->store_result()) $r->free(); } while ($db->more_results() && $db->next_result());
system_install_query(system_install_default_row('config', array('cf_admin'=>'fixture_admin','cf_title'=>'설치 검증 사이트','cf_open'=>1, 'cf_use_point'=>0, 'cf_theme'=>'installer_fixture')));
foreach (array('fixture_admin'=>10, 'fixture_member'=>2) as $id=>$level) {
    system_install_query(system_install_default_row('member', array('mb_id'=>$id,'mb_level'=>$level,'mb_nick'=>$id,'mb_name'=>$id,'mb_datetime'=>'2026-10-02 00:00:00','mb_today_login'=>date('Y-m-d H:i:s'))));
}
$dbconfig = "<?php\nif (!defined('_GNUBOARD_')) exit;\n";
foreach (array('G5_MYSQL_HOST'=>'127.0.0.1:33381','G5_MYSQL_USER'=>'root','G5_MYSQL_PASSWORD'=>'','G5_MYSQL_DB'=>$database,'G5_MASTER_PW'=>'fixture-only','G5_DB_URL'=>'','G5_MYSQL_SET_MODE'=>true,'G5_TABLE_PREFIX'=>'web_') as $key=>$value) $dbconfig .= 'define('.var_export($key, true).','.var_export($value, true).");\n";
$source = file_get_contents($site.'/install/install_db.php');
preg_match_all("/\\\$g5\\['(\\w+)'\\]\\s*=\\s*G5_TABLE_PREFIX\\s*\\.\\s*'([^']+)'/", $source, $matches, PREG_SET_ORDER);
foreach ($matches as $match) $dbconfig .= '$g5['.var_export($match[1], true).'] = G5_TABLE_PREFIX.'.var_export($match[2], true).";\n";
if (count($matches) < 40) throw new RuntimeException('Fixture table mappings not found');
file_put_contents($site.'/data/dbconfig.php', $dbconfig);
foreach (array('session','cache') as $dir) if (!is_dir($site.'/data/'.$dir)) mkdir($site.'/data/'.$dir);

$login = <<<'PHP'
<?php
if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1'), true)) { http_response_code(403); exit; }
session_save_path(__DIR__.'/data/session'); session_start();
$_SESSION['ss_mb_id'] = isset($_GET['member']) ? 'fixture_member' : 'fixture_admin';
$_SESSION['ss_mb_key'] = md5('2026-10-02 00:00:00'.$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
header('Location: /adm/system_install.php');
PHP;
file_put_contents($site.'/_fixture_login.php', $login);
// These prove that neither common nor theme/head extensions run on installer GET.
file_put_contents($site.'/extend/zz_installer_fixture_probe.php', "<?php sql_query('CREATE TABLE web_forbidden_extend (id int)');");
if (!is_dir($site.'/theme/installer_fixture')) mkdir($site.'/theme/installer_fixture');
file_put_contents($site.'/theme/installer_fixture/theme.config.php', "<?php sql_query('CREATE TABLE web_forbidden_theme (id int)');");
file_put_contents($root.'/artifacts/system-installer-web.json', json_encode(array('database'=>$database,'site'=>$site), JSON_PRETTY_PRINT));
echo $database."\n";
