const {chromium}=require('../../artifacts/system-browser/node_modules/playwright');
const fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'../..'),base='http://127.0.0.1:38082';
const fixture=JSON.parse(fs.readFileSync(path.join(root,'artifacts/combat-fixture.json'),'utf8'));
if(!/^codex_install_web_[a-f0-9]+$/.test(fixture.database))throw Error('Isolated fixture required');
fs.copyFileSync(path.join(__dirname,'monster_resource_probe.php'),path.join(fixture.site,'_fixture_resource_probe.php'));
function check(ok,label){if(!ok)throw Error('FAIL '+label);console.log('PASS '+label);}
(async()=>{const browser=await chromium.launch({channel:'chrome',headless:true});try{
 const p=await browser.newPage({viewport:{width:1440,height:1000}}),errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.goto(base+'/_fixture_login.php');
 const request=async query=>{const r=await p.request.get(base+'/_fixture_resource_probe.php?'+query);const t=await r.text();try{return JSON.parse(t);}catch{throw Error(t.slice(0,2500));}};
 await p.goto(base+'/adm/monster_skill_form.php');
 check(await p.locator('[name=mp],[name=cooldown]').count()===0,'monster skill editor has no MP or cooldown controls');
 await p.locator('[name=name]').fill('공용 강격');await p.locator('[name=min]').fill('26');await p.locator('[name=max]').fill('26');await p.getByRole('button',{name:'확인',exact:true}).click();
 check((await p.locator('[role=status]').innerText()).includes('저장했습니다'),'common monster skill creation POST');
 const skill=await p.locator('[name=skill_id]').inputValue();
 await p.goto(base+'/adm/monster_form.php');
 check(await p.locator('[name=raid_id],[name=dungeon_profile],[name=mp]').count()===0,'one monster definition with no MP controls');
 await p.locator('[name=name]').fill("공용 '몬스터'");await p.locator('[name=hp]').fill('120');await p.locator('[name=attack_min]').fill('7');await p.locator('[name=attack_max]').fill('7');await p.locator('[name=pattern_mode]').selectOption('turns');
 await p.getByRole('tab',{name:'능력치'}).click();await p.locator('[name=defense]').fill('0');
 await p.getByRole('tab',{name:'행동 패턴'}).click();await p.getByRole('button',{name:'스킬 추가',exact:true}).click();await p.getByLabel('추가할 스킬',{exact:true}).selectOption(skill);await p.getByRole('button',{name:'보유 스킬 추가',exact:true}).click();await p.getByRole('button',{name:'저장',exact:true}).click();
 check((await p.locator('[role=status]').innerText()).includes('같은 몬스터'),'single monster creation POST');const id=await p.locator('[name=monster_id]').inputValue();
 const saved=await request('op=inspect&id='+id);
 check(saved.master.raid_id&&saved.profile.dg_mon_hp===120&&saved.raid.mo_hp==='120'&&saved.profile.shared.skills[0].name==='공용 강격'&&!('mp' in saved.profile.shared),'one saved definition supplies HP stats and skills without MP');
 await p.goto(base+'/adm/monster_form.php');await p.locator('[name=name]').fill('공용 스킬을 공유하는 다른 몬스터');await p.locator('[name=pattern_mode]').selectOption('turns');await p.getByRole('tab',{name:'행동 패턴'}).click();await p.getByRole('button',{name:'스킬 추가',exact:true}).click();await p.getByLabel('추가할 스킬',{exact:true}).selectOption(skill);await p.getByRole('button',{name:'보유 스킬 추가',exact:true}).click();await p.getByRole('button',{name:'저장',exact:true}).click();
 const secondId=await p.locator('[name=monster_id]').inputValue();const second=await request('op=inspect&id='+secondId);
 check(secondId!==id&&second.profile.shared.skills[0].id===saved.profile.shared.skills[0].id&&second.master.raid_id!==saved.master.raid_id,'one skill is held by two independent common monsters');
 await p.goto(base+'/adm/monster_form.php?monster_id='+id);await p.locator('[name=hp]').fill('125');await p.getByRole('button',{name:'저장',exact:true}).click();
 check((await request('op=inspect&id='+id)).raid.mo_hp==='125','monster edit updates common definition');
 const maze=await request('op=maze&id='+id);
 check(maze.damage.join(',')==='26,26,26'&&!('mp' in maze.runtime)&&!('cooldowns' in maze.runtime),'Dungeon exploration repeats all three skills despite old zero MP and cooldown 999');
 for(const mode of ['realtime','mmbraid']){
  const raid=await request('op=raid&id='+id+'&mode='+mode);
  check(raid.damage.join(',')==='26,26,26'&&!('mp' in raid.runtime)&&!('cooldowns' in raid.runtime)&&Number(raid.monster.mp_now)===0&&Number(raid.monster.mp_max)===0,mode+' executes every pattern turn without MP or cooldown');
  check(raid.legacy.results.every(r=>r===false)&&raid.legacy.hp===86,mode+' legacy K skills also repeat with MP zero and cost/cooldown 999');
  check(raid.player_result==='mp가 부족합니다.',mode+' player MP restriction remains active');
  check(raid.log.some(l=>JSON.stringify(l).includes('공용 강격')),mode+' combat log names the shared skill');
  const phase=await request('op=raid&id='+id+'&mode='+mode+'&phase=1');
  check(phase.damage.join(',')==='26,0,0'&&phase.hp.join(',')==='125,61,79'&&phase.runtime.phase_index===1,mode+' HP phase switches at 35% and repeats healing after HP recovers above threshold');
 }
 const effects=await request('op=effects&id='+id);check(effects.HEAL.hp===68&&!effects.MP_HEAL.has_mp&&effects.GUARD.guard===50,'monster healing and guard work without recreating MP');
 await p.goto(base+'/adm/980_k_monster.php');check(new URL(p.url()).pathname.endsWith('/monster.php'),'old Monster editor resolves to common editor');
 await p.goto(base+'/adm/dungeon_form.php?w=u&dg_id=1');check(await p.locator('[name=dg_mon_hp],[name=dg_d_attack_min]').count()===0,'Dungeon configuration links common monsters');
 check(errors.length===0,'no administrator JavaScript errors');fs.writeFileSync(path.join(root,'artifacts/monster-shared-test.json'),JSON.stringify({id,skill,ds_id:1}));
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exit(1)});
