const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'../..'),base='http://127.0.0.1:38082';
const fixture=JSON.parse(fs.readFileSync(path.join(root,'artifacts/combat-fixture.json'),'utf8'));
if(!/^codex_install_web_[a-f0-9]+$/.test(fixture.database))throw Error('Isolated fixture required');
fs.copyFileSync(path.join(__dirname,'monster_resource_probe.php'),path.join(fixture.site,'_fixture_resource_probe.php'));
function check(ok,label){if(!ok)throw Error('FAIL '+label);console.log('PASS '+label);}
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await browser.newPage({viewport:{width:1366,height:900}}),errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/_fixture_login.php');
 const request=async query=>{const r=await p.request.get(base+'/_fixture_resource_probe.php?'+query);const body=await r.text();try{return JSON.parse(body);}catch{throw Error(body.slice(0,2500));}};
 const skills=[];
 for(const [name,damage] of [['상위 구간',7],['중간 구간',11]]){
  await p.goto(base+'/adm/monster_skill_form.php');await p.locator('[name=name]').fill(name+' '+Date.now());await p.locator('[name=min]').fill(String(damage));await p.locator('[name=max]').fill(String(damage));
  await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'확인',exact:true}).click()]);skills.push(await p.locator('[name=skill_id]').inputValue());
 }
 await p.goto(base+'/adm/monster_form.php');await p.locator('[name=name]').fill('HP 구간 검증 '+Date.now());await p.locator('[name=hp]').fill('1000');await p.locator('[name=default_attack]').uncheck();await p.locator('[name=pattern_mode]').selectOption('turns');await p.getByRole('tab',{name:'행동 패턴',exact:true}).click();
 const rows=()=>p.locator('.maze-editor-abilities tbody > tr');
 for(let i=0;i<2;i++){
  await p.getByRole('button',{name:'스킬 추가',exact:true}).click();await p.getByLabel('추가할 스킬',{exact:true}).selectOption(skills[i]);await p.getByRole('button',{name:'보유 스킬 추가',exact:true}).click();
  const row=rows().nth(i);await row.locator('[data-ability-edit]').click();await row.locator('[data-ability-field=hp_below]').fill(i?'50':'100');await row.locator('[data-ability-field=hp_above]').fill(i?'30':'50');await row.locator('[data-ability-edit]').click();
 }
 check((await rows().nth(0).locator('.monster-hp-range').innerText()).includes('50% 이하가 되면 중단'),'upper skill displays its stop boundary');
 await rows().nth(1).locator('[data-ability-edit]').click();await rows().nth(1).locator('[data-ability-field=hp_above]').fill('50');
 check(await rows().nth(1).locator('[data-ability-field=hp_above]').evaluate(e=>!e.validity.valid),'overlapping endpoints rejected before save');
 await rows().nth(1).locator('[data-ability-edit]').click();await p.getByRole('button',{name:'저장',exact:true}).click();
 check(await p.locator('.dungeon-json-editor > [role=alert]').isVisible(),'invalid hidden row opens editor and explains range error');
 await rows().nth(1).locator('[data-ability-field=hp_above]').fill('30');await rows().nth(1).locator('[data-ability-edit]').click();
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'저장',exact:true}).click()]);
 const id=await p.locator('[name=monster_id]').inputValue(),saved=await request('op=inspect&id='+id),entries=JSON.parse(saved.master.profile).shared.skills;
 check(entries.map(e=>e.hp_below+':'+e.hp_above).join(',')==='100:50,50:30','upper and lower save once in common skill references');
 check(saved.profile.shared.skills.map(e=>e.hp_above).join(',')==='50,30','compiled battle definition preserves lower limits');
 await p.getByRole('tab',{name:'행동 패턴',exact:true}).click();
 check(await rows().nth(0).locator('[data-ability-field=hp_above]').inputValue()==='50','reopening preserves bounded HP condition');
 await p.screenshot({path:path.join(root,'artifacts/monster-hp-range-desktop.png'),fullPage:true});
 for(const width of [390,320]){await p.setViewportSize({width,height:844});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'range editor fits '+width+' px');}
 await p.screenshot({path:path.join(root,'artifacts/monster-hp-range-mobile.png'),fullPage:true});
 const before=saved.master.profile;
 const invalid=await p.locator('#fmonsterform').evaluate(async form=>{const body=new FormData(form),rows=JSON.parse(body.get('abilities'));rows[0].hp_above=100;body.set('abilities',JSON.stringify(rows));return (await fetch(form.action,{method:'POST',body})).text();});
 check(invalid.includes('상한보다 작아야')&&(await request('op=inspect&id='+id)).master.profile===before,'server rejects invalid range without changing common definition');
 const maze=await request('op=maze&id='+id+'&range=1');check(maze.damage.join(',')==='7,7,11,11,0,0','exploration changes skill at 50% and stops it at 30%');
 for(const mode of ['realtime','mmbraid']){
  const raid=await request('op=raid&id='+id+'&range=1&mode='+mode);check(raid.damage.join(',')==='7,7,11,11,0,0',mode+' shares exact range boundaries and stops out-of-range patterns');
 }
 await p.goto(base+'/adm/monster_form.php?monster_id='+id);await p.locator('[name=pattern_mode]').selectOption('phases');await p.getByRole('tab',{name:'행동 패턴',exact:true}).click();
 await p.getByRole('button',{name:'HP 단계 추가',exact:true}).click();await p.locator('#phase-below-1').fill('50');await p.getByRole('button',{name:'HP 단계 추가',exact:true}).click();await p.locator('#phase-below-2').fill('30');
 const headings=await p.locator('.monster-phase-card h3').allTextContents();
 check(headings[0].includes('100% 이하~50% 초과')&&headings[1].includes('50% 이하~30% 초과')&&headings[2].includes('30% 이하~0% 초과'),'phase intervals derive from adjacent thresholds without extra saved bounds');
 await p.setViewportSize({width:1366,height:900});await p.screenshot({path:path.join(root,'artifacts/monster-hp-phase-range-desktop.png'),fullPage:true});
 await p.setViewportSize({width:390,height:844});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'phase interval labels fit mobile');await p.screenshot({path:path.join(root,'artifacts/monster-hp-phase-range-mobile.png'),fullPage:true});
 check(!errors.length,'no admin JavaScript errors');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1)});
