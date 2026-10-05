const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const b=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await b.newPage(),errors=[];p.on('pageerror',e=>errors.push(p.url()+" "+e.stack));const base='http://127.0.0.1:38082';
 await p.goto(base+'/_fixture_login.php');await p.goto(base+'/adm/dungeon_state_list.php');
 check(await p.getByRole('link',{name:'상세보기',exact:true}).count()===0,'separate details button removed');
 const link=p.locator('a[href="./dungeon_log.php?ds_id=31"]');await Promise.all([p.waitForNavigation(),link.click()]);
 check(await p.locator('#session-detail').count()===1,'session detail integrated in log page');
 check(await p.locator('#log-panel-combat').isVisible()&&!await p.locator('#log-panel-history').isVisible(),'combat tab shown alone');
 await p.locator('#log-tab-history').click();check(await p.locator('#log-panel-history').isVisible()&&!await p.locator('#log-panel-combat').isVisible(),'history tab shown alone');
 await p.goto(base+'/adm/dungeon_log.php?ds_id=29&tab=history');check(await p.locator('#log-panel-history').isVisible(),'history tab restored by query');
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'대기 포인트 지급 재시도',exact:true}).click()]);
 check(p.url().includes('dungeon_log.php')&&(await p.locator('[role=status]').innerText()).includes('처리했습니다'),'admin POST returns to unified log page');
 await p.goto(base+'/dungeon/maze.php?ds_id=31');await p.waitForSelector('#maze-leave:not([hidden])');
 check(await p.locator('.maze-exit-actions a').isVisible()&&await p.locator('#maze-leave').isEnabled(),'exit controls visible at top');
 let operation='';
 await p.route('**/dungeon/maze.php',async route=>{
  if(route.request().method()!=='POST')return route.continue();
  operation=new URLSearchParams(route.request().postData()).get('operation');
  await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({ok:true})});
 });
 await p.route(base+'/dungeon/',route=>route.fulfill({status:200,contentType:'text/html',body:'<p>Dungeon list destination</p>'}));
 p.once('dialog',d=>d.accept());await Promise.all([p.waitForURL(base+'/dungeon/'),p.locator('#maze-leave').click()]);
 check(operation==='leave','confirmed exit uses existing leave action and returns to list');
 check(errors.length===0,'no JavaScript errors');
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1);});
