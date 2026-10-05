"""Real common.php/admin authentication and POST/Redirect/GET, isolated fixture only."""
import http.cookiejar
import json
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
BASE = 'http://127.0.0.1:38081'


def snapshot():
    return json.loads(subprocess.check_output(['php', str(ROOT / 'dev/tests/installer_web_probe.php')], cwd=ROOT))


def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def fetch(opener, path, data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        response = opener.open(BASE + path, body)
    except urllib.error.HTTPError as response:
        return response.code, response.read().decode('utf-8')
    return response.status, response.read().decode('utf-8')


def check(value, label):
    assert value, label
    print('PASS:', label)


before = snapshot()
admin = client()
status, html = fetch(admin, '/_fixture_login.php')
check(status == 200 and '시스템 설치 / 업데이트' in html, 'real admin GET renders')
check('<b>Warning</b>' not in html and '<b>Fatal error</b>' not in html, 'admin GET has no PHP warnings')
check(snapshot()['schema_hash'] == before['schema_hash'], 'admin GET does not create or alter schema')
check('web_forbidden_extend' not in snapshot()['tables'] and 'web_forbidden_theme' not in snapshot()['tables'], 'legacy and theme bootstrap probes never run')
token = re.search(r'name="token" value="([a-f0-9]+)"', html).group(1)
member = client()
status, _ = fetch(member, '/_fixture_login.php?member=1')
check(status == 403, 'ordinary member denied by real admin route')

for data, label in [
    ({'action':'apply','module':'base'}, 'missing CSRF'),
    ({'action':'apply','module':'base','token':'bad'}, 'invalid CSRF'),
    ({'action':'reinstall','module':'base','token':token}, 'unknown action'),
    ({'action':'apply','module':'unknown','token':token}, 'unknown module'),
    ({'action':'apply','module[]':'base','token':token}, 'array module'),
    ({'action':'inventory_engine','module':'inventory_engine','token':token}, 'missing engine confirmation'),
]:
    status, html = fetch(admin, '/adm/system_install.php', data)
    check(status == 200 and '작업 중 확인이 필요합니다' in html, label + ' reported after redirect')
    check(snapshot()['schema_hash'] == before['schema_hash'], label + ' leaves schema unchanged')

fetch(admin, '/adm/system_install.php?action=apply&module=base')
check(snapshot()['schema_hash'] == before['schema_hash'], 'GET action parameters cannot install')
status, html = fetch(admin, '/adm/system_install.php', {'action':'apply','module':'base','token':token})
after = snapshot()
check(after['states']['base']['installed'] and after['schema_hash'] != before['schema_hash'], 'authorized POST installs through real route')
check(not re.search(r'data-module="base"[\s\S]*?<form[\s\S]*?</section>', html.split('data-module="map"')[0]), 'latest module has no install button')
fetch(admin, '/adm/system_install.php')
fetch(admin, '/adm/system_install.php', {'action':'apply','module':'base','token':token})
check(snapshot()['schema_hash'] == after['schema_hash'], 'refresh and repeat POST are idempotent')
fetch(admin, '/adm/980_k_data_install.php', {'action':'reinstall','token':token})
check(snapshot()['schema_hash'] == after['schema_hash'], 'old destructive POST redirects without execution')
check(not after['states']['inventory_engine']['installed'], 'normal HTTP install leaves MyISAM inventory unchanged')
# Preserve the original base-only fixture flow used by the browser test.
if '--full-storage' not in sys.argv:
    print('COMPLETE: HTTP installer')
    raise SystemExit(0)

# Verify every installation step through the authenticated production route.
retired = {'dungeon_maze_config', 'dungeon_maze_session', 'dungeon_monster_config',
           'dungeon_state_monster', 'dungeon_member', 'dungeon_log',
           'monster_dungeon_profile', 'dungeon_skill_target',
           'dungeon_maze_item_effect', 'k_battle_monster_legacy_migration'}
for module in ('map', 'field', 'room', 'quest', 'dungeon', 'k_battle',
               'unified', 'monsters', 'realtime', 'inventory_boundary', 'labyrinth'):
    if module == 'inventory_boundary':
        _, html = fetch(admin, '/adm/system_install.php', {
            'action':'inventory_engine', 'module':'inventory_engine', 'token':token,
            'backup_confirm':'1', 'writers_confirm':'1'})
        check(snapshot()['states']['inventory_engine']['installed'],
              'explicit HTTP inventory conversion enables exploration dependencies')
    _, html = fetch(admin, '/adm/system_install.php', {
        'action':'apply', 'module':module, 'token':token})
    state = snapshot()
    check(state['states'][module]['installed'] and '작업 중 확인이 필요합니다' not in html,
          module + ' installs through the authenticated page')
    check(not ({'web_' + name for name in retired} & set(state['tables'])),
          module + ' creates no retired dungeon or monster storage')
    if module == 'k_battle':
        check('web_k_battle_monster' not in state['tables'],
              'HTTP K install creates no separate monster catalog')

complete = snapshot()
types = {obj['name']:obj['type'] for obj in complete['objects']}
check(types['web_k_battle_monster'] == types['web_dungeon_monster_read'] == 'VIEW',
      'HTTP full install uses shared monster views')
for _ in range(2):
    fetch(admin, '/adm/system_install.php')
    fetch(admin, '/adm/system_install.php', {'action':'apply', 'module':'all', 'token':token})
    check(snapshot()['schema_hash'] == complete['schema_hash'],
          'full page refresh and repeated all-module POST change no schema')
check('web_forbidden_extend' not in snapshot()['tables'] and
      'web_forbidden_theme' not in snapshot()['tables'],
      'full install still skips legacy and theme bootstrap')
print('COMPLETE: HTTP installer')
