<?php
// PHP's Windows development server exposes backslashes in SCRIPT_FILENAME,
// unlike Apache. Normalize only this fixture, without changing production paths.
if (PHP_SAPI !== 'cli-server') exit(1);
$site = realpath($_SERVER['DOCUMENT_ROOT']);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($site.$path);
if (!$file || strpos($file, $site.DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) return false;
if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') return false;
$_SERVER['SCRIPT_FILENAME'] = str_replace('\\', '/', $file);
$_SERVER['SCRIPT_NAME'] = $path;
chdir(dirname($file));
require $file;
