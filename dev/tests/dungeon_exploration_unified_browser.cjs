const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');
const path=require('path');
const base='http://127.0.0.1:38082';
const inspect=(op,id=0)=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/exploration_fixture.php',op,String(id)],{encoding:'utf8'}));
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  const p=await browser.newPage({viewport:{width:1366,height:900}}),errors=[];
  p.on('pageerror',e=>errors.push(e.message));inspect('catalog');await p.goto(base+'/_fixture_login.php');
  for(const session of inspect('active')) {
    await p.goto(base+'/adm/dungeon_log.php?ds_id='+session.ds_id);
    await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'실패 종료',exact:true}).click()]);
  }
  await p.goto(base+'/adm/monster_form.php');await p.locator('#name').fill('탐험 통합 몬스터 '+Date.now());await p.locator('[name=hp]').fill('1');
  await p.locator('[name=attack_min]').fill('0');await p.locator('[name=attack_max]').fill('0');
  await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'저장',exact:true}).click()]);
  const mid=Number(new URL(p.url()).searchParams.get('monster_id'));check(mid>0,'shared monster created');
  await p.goto(base+'/adm/dungeon_maze_form.php');
  check(p.url().includes('dungeon_form.php'),'old Maze editor resolves to the same form');
  const f=p.locator('#fdungeonform');
  check(await f.locator('[name=maze_enabled],[name=rewards],[name=monsters],[name=di_per_s],[name=di_per_e]').count()===0,'one exploration mode, one pool and one percentage reward editor');
  const title="통합 '던전' "+Date.now();await f.locator('[name=dg_title]').fill(title);await f.locator('[name=dg_count]').fill('1');
  await p.getByRole('tab',{name:'몬스터 출현',exact:true}).click();await p.locator('#pool-add-toggle').click();await p.locator('#pool-add-choice').selectOption(String(mid));await p.locator('#pool-add-confirm').click();
  await p.getByRole('tab',{name:'탐험 설정',exact:true}).click();
  await f.locator('[name=rooms_min]').fill('3');await f.locator('[name=rooms_max]').fill('3');await f.locator('[name=encounter_percent]').fill('100');
  await f.locator('.maze-advanced summary').click();await f.locator('[name=branches_min]').fill('0');await f.locator('[name=branches_max]').fill('0');
  await p.getByRole('tab',{name:'보상 설정',exact:true}).click();await f.locator('[name=dg_point]').fill('17');
  const choices=await p.locator('#it_name_list option').evaluateAll(rows=>rows.map(r=>r.value));const items=['통합 보상 100%','통합 보상 0%'];check(items.every(name=>choices.includes(name)),'reward item choices available');
  for(let i=0;i<2;i++) {await p.locator('.btn-item-add').click();const row=p.locator('#itemAddTable tbody tr:not(.original)').nth(i);await row.locator('[name="it_name[]"]').fill(items[i]);await row.locator('[name="di_count[]"]').fill(String(i+1));await row.locator('[name="di_percent[]"]').fill(i===0?'100':'0');}
  await Promise.all([p.waitForNavigation(),f.locator('input[type=submit]').click()]);const dg=Number(new URL(p.url()).searchParams.get('dg_id'));check(dg>0,'all four tabs save once');
  let data=inspect('inspect',dg),settings=JSON.parse(data.definition.dg_settings);
  check(data.definition.dg_title===title&&settings.rooms_min===3&&data.pool.length===1&&data.rewards.length===2,'base, exploration, pool and rewards save together');
  check(!('rewards' in settings)&&!('monsters' in settings)&&!('title' in settings)&&data.retired.length===0,'duplicated definitions and six retired tables absent');
  await p.getByRole('tab',{name:'보상 설정',exact:true}).click();
  check((await p.locator('[name="di_percent[]"]:enabled').evaluateAll(rows=>rows.map(r=>Number(r.value)))).join(',')==='100,0','single reward list reloads exact probabilities');
  if(!process.env.CODEX_SKIP_SCREENSHOTS)await p.screenshot({path:path.join(__dirname,'../../artifacts/dungeon-unified-rewards-desktop.png'),fullPage:true});
  await p.setViewportSize({width:390,height:844});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'admin mobile page fits viewport with scrolling tables');if(!process.env.CODEX_SKIP_SCREENSHOTS)await p.screenshot({path:path.join(__dirname,'../../artifacts/dungeon-unified-rewards-mobile.png'),fullPage:true});await p.setViewportSize({width:1366,height:900});
  const before=JSON.stringify(data.definition),pool=JSON.stringify(data.pool),rewards=JSON.stringify(data.rewards);
  inspect('fail_save');
  try {const response=await f.evaluate(async form=>{const body=new FormData(form);body.set('dg_title','rollback marker');body.set('dg_point','99');return(await fetch(form.action,{method:'POST',body})).text();});check(response.includes('저장할 수 없습니다'),'write failure reported');}
  finally{inspect('allow_save');}
  data=inspect('inspect',dg);check(JSON.stringify(data.definition)===before&&JSON.stringify(data.pool)===pool&&JSON.stringify(data.rewards)===rewards,'failed save rolls back every definition table');
  const entry=inspect('prepare',dg),baseline=inspect('inspect',dg);
  await p.goto(base+'/dungeon/applicate.php?ds_id='+entry.ds_id);check(p.url().includes('/maze.php'),'every dungeon enters the exploration controller');
  await p.getByRole('button',{name:'대기실 입장',exact:true}).click();await p.waitForSelector('#maze-boot',{state:'attached'});
  await p.getByRole('button',{name:'READY',exact:true}).click();await p.getByRole('button',{name:'출발 →',exact:true}).click();
  await p.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('탐색'));
  await p.getByRole('button',{name:'앞으로 간다',exact:true}).click();await p.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('전투'));
  await p.getByRole('button',{name:'일반 공격',exact:true}).click();await p.waitForFunction(()=>document.querySelector('#maze-turn-status').textContent.includes('제출 완료'));await p.locator('#maze-turn-resolve').click();
  await p.waitForFunction(()=>/탐색|조사/.test(document.querySelector('#maze-phase').textContent));
  const investigate=p.getByRole('button',{name:/^(조사한다|상자를 연다)$/});
  if(await investigate.count()) {await investigate.click();await p.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('탐색'));}
  await p.getByRole('button',{name:'앞으로 간다',exact:true}).click();await p.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('클리어'));
  const settled=inspect('inspect',dg);check(settled.points-baseline.points===17,'exploration clear pays the common point reward');
  const count=(rows,id)=>rows.filter(r=>Number(r.it_id)===Number(id)).length;
  check(count(settled.inventory,data.rewards[0].it_id)-count(baseline.inventory,data.rewards[0].it_id)===1&&count(settled.inventory,data.rewards[1].it_id)===count(baseline.inventory,data.rewards[1].it_id),'100% item paid and 0% item excluded from the same stored list');
  await p.reload();const repeated=inspect('inspect',dg);check(repeated.points===settled.points&&repeated.inventory.length===settled.inventory.length,'reopening clear does not pay twice');
  check(!errors.length,'no admin or exploration JavaScript errors');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
