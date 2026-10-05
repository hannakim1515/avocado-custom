const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');const path=require('path');
const root=path.resolve(__dirname,'../..'),base='http://127.0.0.1:38082';
const inspect=(op,id)=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/admin_catalog_fixture.php',op,String(id)],{encoding:'utf8'}));
function check(ok,label){if(!ok)throw Error(label);console.log('PASS '+label);}
async function save(p,button){await Promise.all([p.waitForNavigation(),button.click()]);}
(async()=>{const b=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await b.newPage({viewport:{width:1440,height:1000}}),errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/_fixture_login.php');await p.goto(base+'/adm/monster_skill.php');
 check(await p.locator('#bo_add').count()===1&&await p.locator('form [name=skill_id]').count()===0,'skill list separated from writing form');
 await p.locator('#bo_add').click();await p.waitForSelector('#fmonsterskillform');check(await p.locator('#anc_001 .tbl_frm01 table').count()===2&&await p.locator('#anc_001 th[rowspan="5"]').count()===1,'skill form follows original anc_001 grouped table');
 const name='Form skill '+Date.now();await p.locator('#name').fill(name);await p.locator('#cue').fill('Prepares attack');
 await p.locator('#min').fill('9');await p.locator('#max').fill('13');await p.locator('#mp').fill('2');
 await save(p,p.getByRole('button',{name:'확인',exact:true}));const sid=Number(new URL(p.url()).searchParams.get('skill_id'));
 check(sid>0&&(await p.locator('[role=status]').innerText()).includes('저장'),'skill creation POST');
 await p.screenshot({path:path.join(root,'artifacts/monster-skill-native-form.png'),fullPage:true});
 await p.locator('#cue').fill('Updated cue');await save(p,p.getByRole('button',{name:'확인',exact:true}));
 await p.reload();check(await p.locator('#cue').inputValue()==='Updated cue','skill modification retained');
 await p.goto(base+'/adm/monster_skill.php?stx='+encodeURIComponent(name));check(await p.locator('.tbl_head01 tbody tr').count()===1,'native skill list search');
 await p.screenshot({path:path.join(root,'artifacts/monster-skill-native-list.png'),fullPage:true});
 const ids=[];
 for(let i=0;i<2;i++){
  await p.goto(base+'/adm/monster_form.php');await p.locator('#name').fill('Form monster '+Date.now()+' '+i);
  await p.getByRole('button',{name:'스킬 추가',exact:true}).click();
  await p.getByLabel('추가할 스킬',{exact:true}).selectOption(String(sid));await p.getByRole('button',{name:'보유 스킬 추가',exact:true}).click();
  check(await p.getByLabel('공용 스킬',{exact:true}).inputValue()===String(sid),'selected skill added to ownership row');
  await p.getByRole('button',{name:'1번째 스킬 수정',exact:true}).click();
  await p.getByLabel('사용 턴 (1이면 매 턴)',{exact:true}).fill('3');await p.getByLabel('HP가 이 비율 이하일 때 (%)',{exact:true}).fill('70');
  await save(p,p.getByRole('button',{name:'저장',exact:true}));const id=Number(new URL(p.url()).searchParams.get('monster_id'));ids.push(id);
  const data=JSON.parse(inspect('monster',id).profile);check(data.shared.skills[0].skill_id===sid&&data.shared.skills[0].turn===3&&data.shared.skills[0].hp_below===70,'Monster ownership and conditions saved');
 }
 const marker=JSON.parse(inspect('monster_mark',ids[1]).profile).test_preserve;
 await p.goto(base+'/adm/monster_form.php?monster_id='+ids[1]);
 await p.getByRole('button',{name:'1번째 항목 삭제',exact:true}).click();await p.getByRole('button',{name:'스킬 추가',exact:true}).click();await p.getByRole('button',{name:'보유 스킬 추가',exact:true}).click();
 await save(p,p.getByRole('button',{name:'저장',exact:true}));
 check(JSON.parse(inspect('monster',ids[1]).profile).test_preserve===marker,'existing Monster profile metadata preserved');
 await p.goto(base+'/adm/monster.php?stx='+encodeURIComponent('Form monster'));check(await p.getByRole('link',{name:'스킬 수정',exact:true}).count()===0&&await p.getByRole('link',{name:'관리',exact:true}).count()>=2,'ownership editor reached through Monster management');
 await p.goto(base+'/adm/monster_form.php?monster_id='+ids[1]);
 const section=await p.locator('#monster-abilities').boundingBox(),table=await p.locator('#monster-abilities table').boundingBox();
 check(table.width>section.width*.9,'ownership table spans full section width');
 check(await p.locator('#monster-abilities tbody select:visible').count()===0,'owned skills initially display as a list');
 check(await p.locator('#monster-abilities .btn_list01.btn_list [data-add]').innerText()==='스킬 추가','native add button below ownership list');
 check(await p.locator('.dungeon-json-editor textarea').count()===0&&(await p.locator('body').innerText()).includes('JSON')===false,'Monster JSON is not exposed');
 await p.goto(base+'/adm/dungeon_pool.php?dg_id=1');check(await p.locator('#dungeon-pool-settings table').last().locator('thead th').count()===6,'unified Dungeon pool uses native column table');
 await p.goto(base+'/adm/dungeon_maze.php');check(await p.getByRole('table',{name:'최근 미궁 진행 세션'}).count()===1,'Maze sessions use native list table');
 check(await p.locator('.dungeon-json-editor textarea').count()===0&&!(await p.locator('body').innerText()).includes('JSON'),'Maze JSON is not exposed');
 await p.goto(base+'/adm/monster_status_form.php');check(await p.locator('.dungeon-json-editor textarea').count()===0&&await p.locator('[data-editor-value][name=components]').count()===1,'status effect form keeps hidden storage without JSON editor');
 await p.goto(base+'/adm/system_install.php');check(await p.locator('.tbl_head01 tr[data-module]').count()>0,'installer uses native admin table');
 await p.setViewportSize({width:1024,height:900});await p.goto(base+'/adm/monster_skill_form.php?skill_id='+sid);await p.screenshot({path:path.join(root,'artifacts/monster-skill-native-narrow.png'),fullPage:true});
 check(errors.length===0,'no admin JavaScript errors');
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1);});
