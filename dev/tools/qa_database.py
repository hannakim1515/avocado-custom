"""Local QA backup, guarded clone and portable export. Never uploads a database."""
import argparse
import json
import os
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parents[2]
MYSQL = Path('C:/xampp/mysql/bin')


def config():
    code = "define('_GNUBOARD_',true); require 'data/dbconfig.php'; echo json_encode([G5_MYSQL_HOST,G5_MYSQL_USER,G5_MYSQL_PASSWORD,G5_MYSQL_DB,G5_TABLE_PREFIX]);"
    return json.loads(subprocess.check_output(['php', '-r', code], cwd=ROOT))


def connection(fixture=False):
    host, user, password, database, prefix = config()
    if fixture:
        host, user, password = '127.0.0.1', 'root', ''
    return ['--no-defaults', '--host='+host, '--user='+user,
            '--port='+('33381' if fixture else '3306'), '--default-character-set=utf8mb4'], dict(os.environ, MYSQL_PWD=password)


def run_sql(sql, database=None, fixture=False):
    args, env = connection(fixture)
    if database:
        args.append(database)
    return subprocess.check_output([str(MYSQL/'mysql.exe'), *args, '--batch', '--skip-column-names'], input=sql.encode('utf8'), env=env).decode('utf8').strip()


def guarded(database):
    if not re.fullmatch(r'codex_qa_[a-z0-9_]+', database):
        raise RuntimeError('Disposable codex_qa_ database required')
    if 'codex-system-installer-' not in run_sql('SELECT @@datadir', fixture=True).replace('\\', '/'):
        raise RuntimeError('Isolated server required')


def dump(path, database=None, fixture=False):
    args, env = connection(fixture)
    database = database or config()[3]
    path = path.resolve()
    if path.exists():
        raise RuntimeError('Refusing to overwrite an existing export: '+str(path))
    path.parent.mkdir(parents=True, exist_ok=True)
    subprocess.run([str(MYSQL/'mysqldump.exe'), *args, '--lock-all-tables', '--routines', '--events',
                    '--triggers', '--hex-blob', '--result-file='+str(path), database], env=env, check=True)
    return path


def restore(path, database):
    guarded(database)
    if run_sql("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='"+database+"'", fixture=True):
        raise RuntimeError('Clone destination already exists')
    run_sql('CREATE DATABASE `'+database+'` CHARACTER SET utf8mb4', fixture=True)
    args, env = connection(True)
    with path.open('rb') as source:
        subprocess.run([str(MYSQL/'mysql.exe'), *args, database], stdin=source, env=env, check=True)


def portable(source, target):
    if target.exists():
        raise RuntimeError('Portable output already exists')
    # Transform only mysqldump DDL lines; never touch strings in INSERT data.
    with source.open('r', encoding='utf8') as src, target.open('w', encoding='utf8', newline='\n') as dst:
        dst.write('-- QA server test data. Import into the selected database; same application code and avo_ prefix required.\n')
        for line in src:
            if line.startswith('/*!50013 DEFINER='):
                line = re.sub(r'DEFINER=`(?:[^`]|``)+`@`(?:[^`]|``)+`\s+', '', line, count=1)
            elif line.startswith('/*!50003 CREATE*/') or line.startswith('/*!50106 CREATE*/') or line.startswith('CREATE '):
                line = re.sub(r'DEFINER=`(?:[^`]|``)+`@`(?:[^`]|``)+`\s+', '', line, count=1)
            dst.write(line)


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('action', choices=['backup', 'clone', 'export', 'roundtrip'])
    p.add_argument('--file', required=True, type=Path)
    p.add_argument('--database')
    p.add_argument('--source', type=Path)
    a = p.parse_args()
    if a.action == 'backup':
        print(dump(a.file))
    elif a.action in ('clone', 'roundtrip'):
        restore(a.file, a.database)
        print('Imported guarded fixture '+a.database)
    elif a.action == 'export':
        if a.source is None:
            raise RuntimeError('--source raw dump required')
        portable(a.source, a.file)
        print(a.file.resolve())
