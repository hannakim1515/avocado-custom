const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');const fs=require('fs');const path=require('path');
const root=path.resolve(__dirname,'../..'), fixture=JSON.parse(fs.readFileSync(path.join(root,'artifacts/combat-fixture.json'),'utf8'));
const base='http://127.0.0.1:38082',url=`${base}/dungeon/maze.php?ds_id=${fixture.ds_id}`;
const control=(...args)=>execFileSync('C:/xampp/php/php.exe',[path.join(__dirname,'combat_fixture_control.php'),...args],{encoding:'utf8'}).trim();
function check(ok,label){if(!ok)throw Error('FAIL '+label);console.log('PASS '+label);}
(async()=>{
const browser=await chromium.launch({channel:'chrome',headless:true});const errors=[];
try {
 const c1=await browser.newContext({viewport:{width:1440,height:1000}}),c2=await browser.newContext({viewport:{width:390,height:844}});
 const a=await c1.newPage(),b=await c2.newPage();for(const p of [a,b])p.on('pageerror',e=>errors.push(e.message));
 await a.goto(base+'/_fixture_login.php');await b.goto(base+'/_fixture_login.php?member=1');await a.goto(url);await b.goto(url);
 await a.waitForSelector('#maze-party .party-member');
 check((await a.locator('#maze-phase').innerText()).includes('대기'),'WAITING shell');
 check((await a.locator('#maze-party').innerText()).includes('정신'),'HP and actual resource HUD');
 check((await a.locator('#maze-scene').evaluate(e=>getComputedStyle(e).backgroundImage)).includes('fixture-background'),'maze background in waiting');
 await a.screenshot({path:path.join(root,'artifacts/combat-waiting.png'),fullPage:true});await b.screenshot({path:path.join(root,'artifacts/combat-mobile.png'),fullPage:true});
 check(await b.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'mobile no horizontal overflow');
 const digest=control('digest');await a.waitForTimeout(3100);check(control('digest')===digest,'poll endpoint does not mutate session, inventory, logs or member');
 await a.locator('#chat-message').fill('입력 유지');await a.locator('#chat-message').focus();
 await b.getByRole('button',{name:'READY',exact:true}).click();await a.waitForTimeout(2900);
 check(await a.locator('#chat-message').inputValue()==='입력 유지' && await a.locator('#chat-message').evaluate(e=>e===document.activeElement),'poll preserves chat draft and focus');
 await b.locator('#chat-message').fill('다른 참가자 대화');await b.locator('#maze-chat-form button').click();await a.waitForFunction(()=>document.querySelector('#maze-chat').textContent.includes('다른 참가자 대화'));
 check(await a.locator('#maze-chat .maze-message').count()===1,'chat delta appended once');
 await a.getByRole('button',{name:'READY',exact:true}).click();await a.getByRole('button',{name:'출발 →',exact:true}).click();
 await a.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('탐색'));
 check(await a.locator('#maze-choices button').count()>0,'WAITING to EXPLORE central choices');
 await a.screenshot({path:path.join(root,'artifacts/combat-explore.png'),fullPage:true});
 await a.locator('#maze-choices button').first().click();await a.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('전투'));
 check(await a.locator('#maze-enemies .maze-token').count()===1,'EXPLORE to BATTLE monster token');
 await a.getByRole('button',{name:'스킬',exact:true}).click();check((await a.locator('#maze-controls').innerText()).includes('정신 4'),'skill cost label');
 control('resource','0');await a.waitForTimeout(3000);check(await a.locator('.skill-choice').first().isDisabled(),'insufficient skill disabled after polling');
 const rejected=await a.evaluate(async()=>{const boot=JSON.parse(document.querySelector('#maze-boot').textContent);const s=await(await fetch('maze_state.php?ds_id='+boot.ds_id)).json();const r=await fetch('maze.php',{method:'POST',body:new URLSearchParams({ajax:'1',token:boot.token,ds_id:boot.ds_id,operation:'submit',battle_id:s.battle.id,turn_no:s.battle.turn,action:'SKILL',reference_id:s.skills[0].id,target_id:s.actor})});return await r.json();});check(rejected.error?.includes('자원 부족'),'direct POST insufficient resource rejected');
 control('resource','20');await a.waitForTimeout(2800);await a.locator('.skill-choice').first().click();await a.getByRole('button',{name:'강격 제출'}).click();
 await b.waitForFunction(()=>document.querySelector('#maze-phase').textContent.includes('전투'));await b.getByRole('button',{name:'일반 공격',exact:true}).click();
 await a.waitForFunction(()=>document.querySelector('#maze-turn').textContent.includes('2'));
 check((await a.locator('#maze-party').innerText()).includes('16 / 20'),'resource consumed and synchronized');
 check(await a.locator('.skill-choice').first().isDisabled(),'cooldown displayed after action');
 await a.getByRole('tab',{name:'전투 기록'}).click();check((await a.locator('#maze-log').innerText()).includes('DAMAGE'),'structured damage log in player panel');
 const inspect=JSON.parse(control('inspect'));check(inspect.combat.some(e=>e.actor==='아린'&&e.action==='강격'&&Number(e.amount)===26),'actor target skill damage turn persisted');
 check(inspect.combat.some(e=>e.action==='몸통박치기'),'monster action logged');
 await a.screenshot({path:path.join(root,'artifacts/combat-battle.png'),fullPage:true});
 await a.goto(base+'/adm/dungeon_log.php?ds_id='+fixture.ds_id);check((await a.locator('body').innerText()).includes('강격'),'administrator reads Maze combat source');
 check(errors.length===0,'no browser JavaScript errors');
 console.log('COMPLETE combat browser');
}finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
