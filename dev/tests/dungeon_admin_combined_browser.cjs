const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await browser.newPage(),errors=[];p.on('pageerror',e=>errors.push(e.message));
 const base='http://127.0.0.1:38082';await p.goto(base+'/_fixture_login.php');
 await p.goto(base+'/adm/dungeon_list.php');
 check(await p.locator('#bo_add').getAttribute('href')==='./dungeon_form.php','Dungeon add opens the shared exploration editor');
 check(await p.locator('#gnb a[href$="/dungeon_maze.php"]').count()===0,'no separate Maze navigation');
 const definition=p.locator('#fsidelist tbody tr').filter({hasText:'방 탐험 · 방'}).first();
 await definition.locator('[name="chk[]"]').check();
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'선택수정',exact:true}).click()]);
 check(p.url().includes('/dungeon_list.php')&&await p.locator('#fsidelist tbody tr').filter({hasText:'방 탐험 · 방'}).count()>0,'Dungeon bulk save retains exploration settings');
 await Promise.all([p.waitForNavigation(),p.locator('#bo_add').click()]);check(await p.getByRole('tab').count()===4&&await p.locator('#anc_001').isVisible(),'Dungeon add starts with basic settings and four shared tabs');
 check((await p.locator('.btn_confirm a').first().getAttribute('href')).includes('dungeon_list.php'),'form returns to Dungeon list');
 await p.goto(base+'/adm/dungeon_state_list.php');
 check(await p.getByRole('link',{name:'로그확인',exact:true}).count()>0,'Maze sessions included in occurrence list');
 const row=p.locator('tr').filter({has:p.getByRole('link',{name:'로그확인',exact:true})}).first();
 check(await row.locator('[name="chk[]"]').isDisabled(),'Maze history protected from legacy bulk actions');
 check((await row.innerText()).includes('HP'),'participant HP shown in occurrence list');
 await Promise.all([p.waitForNavigation(),row.getByRole('link',{name:'로그확인',exact:true}).click()]);
 check(await p.getByRole('button',{name:'실패 종료',exact:true}).count()===1,'session controls available in occurrence page');
 await p.goto(base+'/adm/dungeon_state_list.php?ds_id=29');
 await Promise.all([p.waitForNavigation(),p.getByRole('button',{name:'대기 포인트 지급 재시도',exact:true}).click()]);
 check((await p.locator('[role=status]').innerText()).includes('처리했습니다'),'session admin POST succeeds on integrated page');
 for(const [url,dest] of [['dungeon_maze.php','dungeon_list.php'],['dungeon_maze.php?ds_id=29','dungeon_log.php'],['dungeon_maze.php?dg_id=1','dungeon_form.php']]){
  await p.goto(base+'/adm/'+url);check(p.url().includes('/adm/'+dest),'legacy route forwards to '+dest);
 }
 check(errors.length===0,'no JavaScript errors');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1);});
