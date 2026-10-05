const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');
const base='http://127.0.0.1:38082';
const fixture=(op,id=0)=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/exploration_fixture.php',op,String(id)],{encoding:'utf8'}));
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  const p=await browser.newPage({viewport:{width:1366,height:900}}),errors=[];
  p.on('pageerror',e=>errors.push(e.message));
  p.on('dialog',async dialog=>{if(dialog.type()==='beforeunload')await dialog.accept();else throw Error(dialog.message());});
  fixture('catalog');await p.goto(base+'/_fixture_login.php');
  const ids=[],names=['검색 슬라임 '+Date.now(),'검색 골렘 '+Date.now()];
  for(const name of names){
   await p.goto(base+'/adm/monster_form.php');await p.locator('#name').fill(name);
   await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'저장',exact:true}).click()]);
   ids.push(Number(new URL(p.url()).searchParams.get('monster_id')));
  }
  await p.goto(base+'/adm/dungeon_form.php');const f=p.locator('#fdungeonform');
  check(await p.getByRole('tab').count()===4,'four freely selectable tabs retained');
  check(!await f.getByRole('button',{name:/다음|이전/}).count(),'no forced step navigation');
  check(!(await p.locator('#dungeon-save-state').innerText()).includes('변경사항'),'opening form does not mark settings dirty');
  check(await f.locator('.dungeon-summary').count()===0,'dungeon summary removed');
  const title="관리자 '입력' 검증 "+Date.now();await f.locator('[name=dg_title]').fill(title);
  check((await p.locator('#dungeon-save-state').innerText()).includes('변경사항'),'editing marks the form as unsaved');
  await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();
  check(await f.locator('[data-monster-row]').count()===0&&await p.locator('#pool-empty').isVisible(),'unassigned masters are absent from the dungeon list');
  await p.locator('#pool-add-toggle').click();
  for(let i=0;i<2;i++){
   await p.locator('#pool-add-search').fill(names[i]);
   check(await p.locator('#pool-add-choice option').count()===1,'search narrows the separate add catalog');
   await p.locator('#pool-add-choice').selectOption(String(ids[i]));await p.locator('#pool-add-confirm').click();
   await f.locator(`[name="pool[${ids[i]}][weight]"]`).fill(i?'3':'1');
  }
  await p.locator('#pool-add-search').fill('');
  check(await f.locator('[data-monster-row]:visible').count()===2&&await p.locator(`#pool-add-choice option[value="${ids[0]}"]`).count()===0,'dungeon list contains only assigned masters and prevents duplicate additions');
  check((await f.locator(`[data-monster-id="${ids[0]}"] .pool-ratio`).innerText()).includes('25%')&&(await f.locator(`[data-monster-id="${ids[1]}"] .pool-ratio`).innerText()).includes('75%'),'current weights show real selection ratios');
  await p.locator('#pool-add-search').fill('no matching monster '+Date.now());
  check(await p.locator('#pool-add-empty').isVisible()&&await p.locator('#pool-add-confirm').isDisabled(),'empty add search provides recovery guidance');
  await p.locator('#pool-add-close').click();
  await p.screenshot({path:'artifacts/admin-creation-pool-desktop.png',fullPage:true});
  await p.getByRole('tab',{name:'탐험 설정',exact:true}).click();
  await f.locator('[name=encounter_percent]').fill('30');
  check((await p.locator('#maze-encounter-example').innerText()).includes('30%'),'exploration explains the current encounter chance');
  await f.locator('[name=min_players]').fill('6');await f.locator('[type=submit]').click();
  check(await p.locator('#dungeon-form-error').isVisible()&&(await p.locator('#dungeon-form-error').innerText()).includes('최소 출발 인원'),'invalid party size points to the affected setting before saving');
  await f.locator('[name=min_players]').fill('1');
  await p.getByRole('tab',{name:'보상 설정',exact:true}).click();
  await f.locator('[name=dg_point]').fill('17');await f.locator('.btn-item-add').click();
  const reward=f.locator('#itemAddTable tbody tr:not(.original)').first();
  await reward.locator('[name="it_name[]"]').fill('통합 보상 100%');await reward.locator('[name="di_count[]"]').fill('2');await reward.locator('[name="di_percent[]"]').fill('30');
  check((await reward.locator('.reward-example').innerText()).includes('2개 · 30%'),'reward row explains its actual quantity and chance');
  for(const width of [390,320]){
   await p.setViewportSize({width,height:844});
   for(const label of ['기본 설정','몬스터 출현','탐험 설정','보상 설정']){
    await p.getByRole('tab',{name:label,exact:true}).click();
    check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile '+width+' '+label+' fits viewport');
    check(await f.locator('[type=submit]').evaluate(e=>{const r=e.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight}),'save control remains visible on mobile');
   }
   await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();await p.locator('#pool-add-toggle').click();
   check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile '+width+' add picker fits viewport');
   await p.locator('#pool-add-close').click();
  }
  await p.getByRole('tab',{name:'보상 설정',exact:true}).click();
  await p.setViewportSize({width:390,height:844});await p.screenshot({path:'artifacts/admin-creation-rewards-mobile.png',fullPage:true});
  await p.getByRole('tab',{name:'기본 설정',exact:true}).click();await p.screenshot({path:'artifacts/admin-creation-basic-mobile.png',fullPage:true});
  await p.setViewportSize({width:1366,height:900});await p.screenshot({path:'artifacts/admin-creation-basic-desktop.png',fullPage:true});
  await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();await p.locator('#pool-add-toggle').click();await p.locator('#pool-add-search').fill(names[0]);
  await Promise.all([p.waitForNavigation(),f.locator('[type=submit]').click()]);
  const id=Number(new URL(p.url()).searchParams.get('dg_id')),saved=fixture('inspect',id);
  check(id>0&&saved.definition.dg_title===title&&saved.pool.length===2,'one save retains assigned masters independently of add search');
  check(saved.pool.map(r=>Number(r.weight)).sort().join(',')==='1,3'&&Number(saved.rewards[0].di_percent)===30&&Number(saved.rewards[0].di_count)===2,'weights and rewards persist without changing storage contracts');
  check(!('monsters' in JSON.parse(saved.definition.dg_settings))&&!('rewards' in JSON.parse(saved.definition.dg_settings)),'UI does not reintroduce duplicated definition storage');
  await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();
  check(await f.locator('[data-monster-row]').count()===2,'reopening lists only this dungeon’s two masters');
  await f.locator(`[data-monster-id="${ids[0]}"] [data-pool-remove]`).click();
  await Promise.all([p.waitForNavigation(),f.locator('[type=submit]').click()]);
  check(fixture('inspect',id).pool.length===1,'excluding a monster removes only its dungeon assignment on save');
  await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();await p.locator('#pool-add-toggle').click();await p.locator('#pool-add-search').fill(names[0]);
  check(await p.locator(`#pool-add-choice option[value="${ids[0]}"]`).count()===1,'excluded master remains available in the common catalog');
  check(!errors.length,'no admin JavaScript errors');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
