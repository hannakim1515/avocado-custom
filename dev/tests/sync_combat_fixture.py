from pathlib import Path
import json,shutil,subprocess
root=Path(__file__).resolve().parents[2]
site=Path(json.loads((root/'artifacts/combat-fixture.json').read_text(encoding='utf-8'))['site']).resolve()
assert site.is_relative_to((root/'artifacts').resolve())
paths=[]
for args in [('diff','--name-only'),('ls-files','--others','--exclude-standard')]:
 paths+=subprocess.check_output(['git','-c','core.quotepath=false',*args],cwd=root,text=True,encoding='utf-8',stderr=subprocess.DEVNULL).splitlines()
for name in set(paths):
 source=root/name
 if name.startswith(('dev/','tests/','.','artifacts/','data/')):
  continue
 target=(site/name).resolve()
 assert target.is_relative_to(site)
 if source.is_file():
  target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(source,target)
 elif not source.exists() and target.is_file():
  target.unlink()
print('Updated isolated runtime fixture')
