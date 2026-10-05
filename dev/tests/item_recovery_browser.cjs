const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process'),path=require('path');
const fixture=(...args)=>JSON.parse(execFileSync('C:/xampp/php/php.exe',[path.join(__dirname,'item_recovery_fixture.php'),...args.map(String)],{encoding:'utf8'}));
function check(ok,msg){if(!ok)throw Error('FAIL '+msg);console.log('PASS '+msg);}
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
const data=fixture('seed'),p=await browser.newPage(),errors=[],dialogs=[];p.on('pageerror',e=>errors.push(e.message));p.on('dialog',async d=>{dialogs.push(d.message());await d.accept()});
const base='http://127.0.0.1:38082';await p.goto(base+'/_fixture_login.php');await p.goto(base+'/adm/system_install.php');
const mazeModule=p.locator('[data-module=labyrinth]');const token=await mazeModule.locator('[name=token]').inputValue();
await p.request.post(base+'/adm/system_install.php',{form:{module:'labyrinth',action:'apply',token}});
check(fixture('status').module.version==='1.2.0' && !fixture('inspect',data.legacy_id).legacy_table,'Maze update migrates effects and removes the old table');
await p.reload();
await p.goto(base+'/adm/item_form.php?w=u&it_id='+data.legacy_id);
check(await p.locator('[name=it_type]').inputValue()==='부활' && await p.locator('#it_value').inputValue()==='25','migrated effect loads into ordinary item form');
await p.locator('#it_value').fill('30');await p.getByRole('button',{name:'확인',exact:true}).first().click();await p.waitForURL('**/item_list.php*');
let saved=fixture('inspect',data.legacy_id);check(saved.item.it_type==='부활' && saved.effect.value===30 && !saved.legacy_table,'native item update stores effect and retires old per-Maze table');
check(saved.item.it_2==="기존 '추가' 값" && saved.item.it_3==='스탯 보존' && saved.item.it_4==='2|3' && saved.item.it_5==='4|5','unexposed auxiliary settings preserved');
await p.goto(base+'/adm/item_form.php');await p.locator('[name=it_type]').selectOption('상태이상치료');
check(await p.locator('#it_value').isHidden() && await p.locator('#recovery_status_id').isVisible(),'status names replace raw numeric input');
await p.locator('#recovery_status_id').selectOption(String(data.status_id));const name='치료 아이템 '+Date.now();await p.locator('#it_name').fill(name);await p.locator('[name=it_content]').fill('상태이상 치료 아이템');await p.locator('label[for=it_use_battle_able]').click();
await p.getByRole('button',{name:'확인',exact:true}).first().click();await p.waitForURL('**/item_list.php*');const cure=fixture('find',name).it_id;saved=fixture('inspect',cure);
check(saved.effect.kind==='CLEANSE' && saved.effect.status_id===data.status_id,'item creation POST stores named treatment in existing value column');
await p.goto(base+'/adm/item_form.php?w=u&it_id='+cure);check(await p.locator('#recovery_status_id').inputValue()===String(data.status_id),'saved treatment reloads as named selection');
// Bypass browser select validation to check the server still rejects foreign ids.
await p.evaluate(()=>{const s=document.querySelector('#recovery_status_id');s.add(new Option('invalid','2147483647'));s.value='2147483647'});await p.getByRole('button',{name:'확인',exact:true}).first().click();await p.waitForTimeout(300);
check(dialogs.some(x=>x.includes('치료할 상태이상을 선택')) && fixture('inspect',cure).effect.status_id===data.status_id,'invalid direct POST status rejected without changing stored item');
await p.waitForURL('**/item_form.php*');await p.waitForLoadState('load');
const result=fixture('runtime',data.legacy_id,cure);
check(result[0].hp===30 && result[0].consumed===1 && result[1].hp===50 && !result[1].effects['status:'+data.status_id] && result[1].effects['status:999999'] && result[1].consumed===1,'real item application revives, cures chosen status and consumes once');
check(result[2].original_effect.kind==='REVIVE' && result[3].original_effect.kind==='CLEANSE','ordinary inventory entry snapshots both item effects');
await p.goto(base+'/adm/dungeon_maze.php?dg_id=1');check(await p.locator('[name=operation][value=item_effect]').count()===0 && !(await p.locator('body').innerText()).includes('부활 / 상태 치료 아이템'),'Maze duplicate configuration removed');
check(errors.filter(error=>error!=='$ is not defined').length===0,'no item or maze JavaScript errors');
}finally{await browser.close()}})().catch(e=>{console.error(e);process.exit(1)});
