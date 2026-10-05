const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');
const state=value=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/admin_catalog_fixture.php','admin_battle_state','31',value],{encoding:'utf8'}));
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});let saved;try{
 const p=await browser.newPage(),base='http://127.0.0.1:38082';await p.goto(base+'/_fixture_login.php');
 saved=state('WON').state;
 await p.goto(base+'/adm/dungeon_state_list.php');
 check(!(await p.locator('body').innerText()).includes('Fatal error'),'finished battle does not break occurrence list');
 const link=p.locator('a[href="./dungeon_log.php?ds_id=31"]');
 check(await link.count()===1&&(await link.innerText())==='로그확인','details button visible');
 await Promise.all([p.waitForNavigation(),link.click()]);
 check(await p.locator('#session-detail').count()===1&&(await p.locator('#session-detail').innerText()).includes('WON'),'finished battle details render with historical state');
 await p.goto(base+'/adm/dungeon_log.php?ds_id=29');
 check(await p.locator('#session-detail').count()===1&&!(await p.locator('body').innerText()).includes('Fatal error'),'cleared session without battle renders');
 state(saved);saved=null;
 await p.goto(base+'/adm/dungeon_log.php?ds_id=31');
 check((await p.locator('#session-detail').innerText()).includes('ACTIVE'),'active battle details render');
}finally{if(saved)state(saved);await browser.close();}})().catch(e=>{console.error(e);process.exit(1);});
