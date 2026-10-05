"""Prepare an isolated HTTP copy; the login shortcut is never part of the release."""
from pathlib import Path
import re
import shutil

ROOT=Path(__file__).resolve().parents[2]
site=ROOT/'artifacts/qa-http-site-20261005'
if (site/'data/dbconfig.php').exists():
    raise RuntimeError('Initialized fixture site already exists')
shutil.copytree(ROOT/'artifacts/qa-server-code-20261005',site,dirs_exist_ok=True)
(site/'data/session').mkdir(parents=True,exist_ok=True)
(site/'data/cache').mkdir(parents=True,exist_ok=True)
settings=(ROOT/'data/dbconfig.php').read_text(encoding='utf8')
settings=re.sub(r"define\('G5_MYSQL_HOST',\s*'[^']*'\)","define('G5_MYSQL_HOST', '127.0.0.1:33381')",settings)
settings=re.sub(r"define\('G5_MYSQL_DB',\s*'[^']*'\)","define('G5_MYSQL_DB', 'codex_qa_verify_20261005')",settings)
settings=re.sub(r"define\('G5_MYSQL_USER',\s*'[^']*'\)","define('G5_MYSQL_USER', 'root')",settings)
settings=re.sub(r"define\('G5_MYSQL_PASSWORD',\s*'[^']*'\)","define('G5_MYSQL_PASSWORD', '')",settings)
(site/'data/dbconfig.php').write_text(settings,encoding='utf8')
runtime_config=(site/'config.php').read_text(encoding='utf8')
runtime_config=runtime_config.replace("define('G5_DOMAIN', '');", "define('G5_DOMAIN', 'http://127.0.0.1:38083');")
(site/'config.php').write_text(runtime_config,encoding='utf8')
(site/'_qa_login.php').write_text('''<?php
if(PHP_SAPI!=='cli-server'||!in_array($_SERVER['REMOTE_ADDR'],array('127.0.0.1','::1'),true))exit;
require __DIR__.'/common.php';
$check=sql_fetch('SELECT DATABASE() d,@@port p');
if($check['d']!=='codex_qa_verify_20261005'||(int)$check['p']!==33381)exit;
$admin=sql_fetch("SELECT * FROM {$g5['member_table']} WHERE mb_id='test'");
set_session('ss_mb_id',$admin['mb_id']);set_session('ss_mb_key',md5($admin['mb_datetime'].$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']));
header('Location: /map/');
''',encoding='utf8')
print(site)
