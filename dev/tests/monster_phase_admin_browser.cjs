const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const {execFileSync}=require('child_process');
const path=require('path');
const base='http://127.0.0.1:38082';
const inspect=id=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/admin_catalog_fixture.php','monster',String(id)],{encoding:'utf8'}));
const inspectMaze=()=>JSON.parse(execFileSync('C:/xampp/php/php.exe',['dev/tests/admin_ux_fixture.php','inspect','1'],{encoding:'utf8'})).settings;
function check(ok,label){if(!ok)throw Error('FAIL '+label);console.log('PASS '+label);}
(async()=>{
  const browser=await chromium.launch({channel:'chrome',headless:true});
  try {
    const page=await browser.newPage({viewport:{width:1280,height:900}}),errors=[];
    page.on('pageerror',error=>errors.push(error.message));
    await page.goto(base+'/_fixture_login.php');
    await page.goto(base+'/adm/monster_form.php');
    check(await page.locator('[data-monster-tab]').count()===4,'monster editor uses four tabs');
    await page.locator('#name').fill('단계 검증 '+Date.now());
    await page.getByRole('tab',{name:'기본 정보',exact:true}).focus();
    await page.keyboard.press('ArrowRight');
    check(await page.getByRole('tab',{name:'행동 패턴',exact:true}).getAttribute('aria-selected')==='true','monster tabs support keyboard navigation');
    await page.getByRole('button',{name:'HP 단계 추가'}).click();
    await page.locator('#phase-below-1').fill('50');
    check((await page.locator('.monster-phase-example').nth(1).innerText()).includes('HP 50 이하'),'phase example uses current maximum HP and threshold');
    await page.screenshot({path:path.join(__dirname,'../../artifacts/monster-phase-current.png'),fullPage:true});
    const catalog=await page.locator('#monster-phase-list select').first().locator('option').count();
    if(catalog>1)await page.locator('#monster-phase-list select').first().selectOption({index:1});
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'저장',exact:true}).click()]);
    const id=Number(new URL(page.url()).searchParams.get('monster_id'));
    check(id>0,'phase monster saved');
    const profile=JSON.parse(inspect(id).profile);
    check(profile.shared.phases.length===2 && profile.shared.phases[1].below===50,'HP threshold persisted in common monster definition');
    for(const width of [390,320]) {
      await page.setViewportSize({width,height:844});
      for(const name of ['기본 정보','행동 패턴','능력치','전투 특성']) {
        await page.getByRole('tab',{name,exact:true}).click();
        check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'monster '+width+' '+name+' fits viewport');
        check(await page.locator('#fmonsterform [type=submit]').evaluate(e=>{const r=e.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight}),'monster save remains visible on mobile');
      }
    }
    await page.setViewportSize({width:390,height:844});await page.getByRole('tab',{name:'행동 패턴',exact:true}).click();
    await page.screenshot({path:path.join(__dirname,'../../artifacts/monster-phase-mobile-current.png'),fullPage:true});
    await page.setViewportSize({width:1280,height:900});
    await page.goto(base+'/adm/monster.php');
    check((await page.locator(`a[href="monster_form.php?monster_id=${id}"]`).first().locator('..').locator('..').innerText()).includes('HP 구간 2단계'),'monster list shows the saved phase pattern');
    await page.goto(base+'/adm/dungeon_form.php?w=u&dg_id=1&mode=maze');
    check(await page.locator('[data-dungeon-tab]').count()===4,'dungeon editor uses basic, monster, maze, reward tabs');
    await page.getByRole('tab',{name:'탐험 설정',exact:true}).click();
    check(await page.locator('#maze-settings').isVisible(),'maze settings shown in common dungeon form');
    check(await page.locator('.maze-advanced').getAttribute('open')===null,'advanced maze values collapsed initially');
    await page.locator('.maze-advanced summary').click();
    await page.screenshot({path:path.join(__dirname,'../../artifacts/dungeon-maze-form-current.png'),fullPage:true});
    check(await page.locator('[name=branches_min]').isVisible(),'advanced maze values can be edited');
    await page.getByRole('tab',{name:'보상 설정',exact:true}).click();
    check(await page.locator('#anc_003').isVisible(),'reward tab can be opened');
    await Promise.all([page.waitForNavigation(),page.locator('#fdungeonform input[type=submit]').click()]);
    check((await page.locator('.local_desc[role=status]').innerText()).includes('저장'),'one save applies dungeon and maze settings');
    check(!('monsters' in inspectMaze())&&!('rewards' in inspectMaze()),'maze stores monster references without duplicated definitions');
    await page.goto(base+'/adm/dungeon_maze_common.php');
    check(await page.locator('#action_timeout').count()===1,'maze action timeout moved to one common settings page');
    await page.locator('#action_timeout').fill('90');
    await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'저장',exact:true}).click()]);
    check(await page.locator('#action_timeout').inputValue()==='90','common timeout saved');
    check(!errors.length,'no admin JavaScript errors: '+errors.join('; '));
  } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
