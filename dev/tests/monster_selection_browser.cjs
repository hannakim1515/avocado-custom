const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const fs=require('fs'),path=require('path');const root=path.resolve(__dirname,'../..'),base='http://127.0.0.1:38082';
const check=(ok,label)=>{if(!ok)throw Error('FAIL '+label);console.log('PASS '+label);};
(async()=>{const b=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await b.newPage({viewport:{width:1440,height:1000}});await p.goto(base+'/_fixture_login.php');await p.request.get(base+'/_fixture_shared_probe.php?op=selection');await p.goto(base+'/dungeon/maze.php?ds_id=1');
 const buttons=p.locator('#maze-controls button[data-action]');check(await buttons.count()===4 && await p.locator('#maze-controls button[aria-pressed=true]').count()===0,'no default attack highlight');
 const press=async code=>{await p.locator(`[data-action=${code}]`).click();await p.waitForFunction(code=>document.querySelector(`[data-action=${code}]`)?.getAttribute('aria-pressed')==='true'&&!document.querySelector(`[data-action=${code}]`).disabled,code);check(await p.locator('#maze-controls button[aria-pressed=true]').count()===1,code+' alone remains selected after POST and polling');};
 await press('GUARD');const color=await p.locator('[data-action=GUARD]').evaluate(e=>getComputedStyle(e).backgroundColor),normal=await p.locator('[data-action=ATTACK]').evaluate(e=>getComputedStyle(e).backgroundColor);check(color!==normal,'selected action has a distinct background');
 await p.locator('#chat-message').fill('입력 유지 검증');await p.waitForTimeout(3000);check(await p.locator('#chat-message').inputValue()==='입력 유지 검증','polling preserves chat draft');
 await p.reload();check(await p.locator('[data-action=GUARD]').getAttribute('aria-pressed')==='true','server action restores selection after reload');
 await press('HEAL');await press('SKIP');
 await p.route('**/dungeon/maze.php',route=>route.request().method()==='POST'?route.fulfill({status:400,contentType:'application/json',body:'{"error":"검증 실패"}'}):route.continue());
 await p.locator('[data-action=ATTACK]').click();await p.waitForFunction(()=>document.querySelector('#maze-error').textContent.includes('검증 실패'));await p.waitForFunction(()=>document.querySelector('[data-action=SKIP]')?.getAttribute('aria-pressed')==='true'&&!document.querySelector('[data-action=SKIP]').disabled);check(await p.locator('[data-action=SKIP]').getAttribute('aria-pressed')==='true' && await p.locator('[data-action=ATTACK]').getAttribute('aria-pressed')==='false','failed POST retains confirmed selection');
 await p.screenshot({path:path.join(root,'artifacts/monster-action-selection.png'),fullPage:true});
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1)});
