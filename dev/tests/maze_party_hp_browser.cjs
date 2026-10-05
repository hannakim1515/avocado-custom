const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const ds=Number(process.argv[2]);if(!ds)throw Error('Provide isolated fixture session ID');
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
  const p=await browser.newPage();await p.goto('http://127.0.0.1:38082/_fixture_login.php');await p.goto('http://127.0.0.1:38082/dungeon/maze.php?ds_id='+ds);
  const read=()=>p.evaluate(async id=>(await fetch('maze_state.php?ds_id='+id)).json(),ds);
  const before=await read();const mine=before.party.find(r=>r.id===before.actor);
  await p.getByRole('button',{name:'대기',exact:true}).click();
  await p.waitForFunction(()=>document.querySelector('#maze-turn-status').textContent.includes('제출 완료'));
  await p.locator('#maze-turn-resolve').click();
  await p.waitForFunction(turn=>Number(document.querySelector('#maze-turn').textContent.replace('TURN','').trim())>turn,before.battle.turn);
  const after=await read(),actor=after.party.find(r=>r.id===after.actor);
  check(actor.hp===mine.hp-13,'lowercase damage reduces persisted party HP');
  check(after.logs.some(row=>row.message.includes('13 DAMAGE')),'log reports applied damage');
  check((await p.locator('#maze-party').innerText()).includes(`${actor.hp} / ${actor.max_hp}`),'action synchronization updates HP HUD');
  await p.reload();await p.waitForSelector('#maze-party .party-member');
  check((await p.locator('#maze-party').innerText()).includes(`${actor.hp} / ${actor.max_hp}`),'reload retains actual HP');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1);});
