const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');
const inspect=()=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/admin_ux_fixture.php','inspect'],{encoding:'utf8'})).settings;
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
  const p=await browser.newPage();let posts=0;
  p.on('request',r=>{if(r.method()==='POST'&&r.url().includes('dungeon_form_update.php'))posts++;});
  await p.goto('http://127.0.0.1:38082/_fixture_login.php');
  await p.goto('http://127.0.0.1:38082/adm/dungeon_maze.php?dg_id=1');
  const before=inspect(),form=p.locator('#fdungeonform');
  for(const [name,value] of Object.entries({rooms_min:7,rooms_max:9,branches_min:1,branches_max:10}))await form.locator('[name='+name+']').fill(String(value));
  await form.locator('input[type=submit]').first().click();
  check(posts===0&&await form.locator('[name=branches_max]').evaluate(e=>!e.validity.valid&&e===document.activeElement),'invalid branch count explains and focuses field');
  await form.locator('[name=branches_max]').fill('2');
  await Promise.all([p.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('dungeon_form_update.php')),form.locator('input[type=submit]').first().click()]);
  await p.locator('[role=status]').waitFor();
  check((await p.locator('[role=status]').innerText()).includes('저장했습니다'),'administrator POST succeeds');
  const after=inspect();
  check(after.rooms_min===7&&after.rooms_max===9&&after.branches_min===1&&after.branches_max===2,'room and branch counts persisted');
  check(JSON.stringify(after.events)===JSON.stringify(before.events)&&JSON.stringify(after.rewards)===JSON.stringify(before.rewards),'existing events and rewards preserved');
  check(await p.locator('[name=rooms_min]').inputValue()==='7','saved values shown on page');
  const rejected=await form.evaluate(async f=>{
    const data=new FormData(f);data.set('rooms_max','6');
    const html=await(await fetch(location.href,{method:'POST',body:data})).text();
    const doc=new DOMParser().parseFromString(html,'text/html');
    return {message:doc.querySelector('[role=status]')?.textContent,min:doc.querySelector('[name=rooms_min]')?.value,max:doc.querySelector('[name=rooms_max]')?.value};
  });
  check(rejected.message&&!rejected.message.includes('저장했습니다')&&rejected.min==='7'&&rejected.max==='6'&&inspect().rooms_max===9,'server rejects invalid POST and keeps entered values without changing DB');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1);});
