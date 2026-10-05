const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const check=(ok,label)=>{if(!ok)throw Error(label);console.log('PASS '+label);};
(async()=>{const b=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await b.newPage({viewport:{width:1440,height:1000}}),errors=[];p.on('pageerror',e=>errors.push(e.message));
 const base='http://127.0.0.1:38082';await p.goto(base+'/_fixture_login.php');
 await p.goto(base+'/adm/dungeon_maze.php');
 check(p.url().includes('dungeon_list.php')&&await p.locator('#fsidelist').count()===1,'old Maze entry opens the shared Dungeon list');
 check(await p.locator('.anchor,select[name=dg_id],[name=operation][value=settings]').count()===0,'Maze list has no anchor buttons, selector or inline settings');
 check((await p.locator('#bo_add').getAttribute('href')).includes('dungeon_form.php'),'one Dungeon add button opens the shared editor');
 await p.getByRole('link',{name:'수정',exact:true}).first().click();
 check(p.url().includes('dungeon_form.php')&&await p.locator('#fdungeonform').getAttribute('enctype')==='multipart/form-data','Maze edit opens its own multipart form');
 check(await p.locator('#fdungeonform').count()===1,'Maze editor uses the common Dungeon form');
 await p.goto(base+'/adm/dungeon_pool.php');
 check(p.url().includes('dungeon_list.php'),'old Dungeon pool list opens the Dungeon list');
 await p.goto(base+'/adm/dungeon_pool.php?dg_id=1');
 check(p.url().includes('dungeon_form.php')&&await p.locator('#fdungeonform [name^="pool["]').count()>0,'old Dungeon pool editor opens the common Dungeon form');
 for(const file of ['monster.php','monster_status.php','monster_skill.php']){
   await p.goto(base+'/adm/'+file);check(await p.locator('.btn_add01.btn_add.btn_list #bo_add').count()===1,file+' uses a native add button');
   await p.locator('#bo_add').click();check(p.url().includes('_form.php')&&await p.locator('form[enctype="multipart/form-data"]').count()===1,file+' opens a separate multipart form');
 }
 check(errors.length===0,'no admin JavaScript errors');
}finally{await b.close();}})().catch(e=>{console.error(e);process.exit(1);});
