"""Check seeded entry forms and newly supplied boards through real common.php."""
import http.cookiejar
import json
import re
import urllib.parse
import urllib.request
from pathlib import Path

ROOT=Path(__file__).resolve().parents[2]
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
base='http://127.0.0.1:38083'

def fetch(path,data=None):
    response=client.open(base+path,urllib.parse.urlencode(data).encode() if data is not None else None)
    html=response.read().decode('utf8')
    (ROOT/'artifacts'/('qa-http-'+re.sub(r'[^a-zA-Z0-9]','_',path)+'.html')).write_text(html,encoding='utf8')
    if response.status!=200 or re.search(r'<b>(?:Fatal error|Warning|Parse error)</b>',html):
        raise RuntimeError('Page failed: '+path)
    return html

html=fetch('/_qa_login.php')
html=fetch('/map/map_actions.php',{'ma_id':1})
assert '01 동시 출현 시험' in html and '02 턴 기술 우선순위' in html
assert '입장하기' in html
print('PASS: seeded dungeons have visible map entry buttons')
forms=re.findall(r'<form\b[^>]*action="[^"]*/dungeon/maze.php"[\s\S]*?</form>',html)
assert forms
data=dict(re.findall(r'<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"',forms[0]))
html=fetch('/dungeon/maze.php',data)
boot=re.search(r'<script id="maze-boot" type="application/json">([\s\S]*?)</script>',html)
assert boot and json.loads(boot.group(1))['state']['phase']=='WAITING'
assert '참가자가 아닙니다' not in html
print('PASS: map POST entry opens the actual waiting room')
for path,label in [('/adm/dungeon_list.php','dungeon admin'),('/adm/monster.php','monster admin'),('/bbs/board.php?bo_table=qa_mmb','MMB board'),('/bbs/board.php?bo_table=qa_battle','1:1 board'),('/k_battle/raid.php?raid_type=list','raid lobby')]:
    fetch(path)
    print('PASS: '+label+' renders with seeded data')
