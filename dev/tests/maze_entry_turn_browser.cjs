const { chromium } = require('../../artifacts/system-browser/node_modules/playwright');
const ds = Number(process.argv[2]);
if (!ds) throw Error('Provide an isolated fixture session ID');
const base = 'http://127.0.0.1:38082';
function check(value, label) { if (!value) throw Error(label); console.log('PASS ' + label); }
(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  try {
    const page = await browser.newPage();
    const errors = []; page.on('pageerror', e => errors.push(e.message));
    await page.goto(base + '/_fixture_login.php');
    await page.goto(base + '/dungeon/applicate.php?ds_id=' + ds);
    check(page.url().includes('/maze.php'), 'configured Maze entry route');
    await page.locator('button').filter({ hasText: '대기실 입장' }).click();
    await page.waitForSelector('#maze-boot', { state: 'attached' });
    check((await page.locator('#maze-phase').innerText()).includes('대기'), 'WAITING room');
    await page.getByRole('button', { name: 'READY', exact: true }).click();
    await page.getByRole('button', { name: '준비 취소', exact: true }).waitFor();
    await page.getByRole('button', { name: '출발 →', exact: true }).click();
    await page.waitForFunction(() => document.querySelector('#maze-phase').textContent.includes('탐색'));
    check((await page.locator('#maze-phase').innerText()).includes('탐색'), 'WAITING to EXPLORE');
    // The fixture's forward branch can be the exit; take its side branch.
    await page.locator('#maze-choices button').filter({ hasText: /왼쪽으로 간다|오른쪽으로 간다/ }).first().click();
    await page.waitForFunction(() => document.querySelector('#maze-phase').textContent.includes('전투'));
    const read = () => page.evaluate(async () => {
      const boot = JSON.parse(document.querySelector('#maze-boot').textContent);
      return (await fetch('maze_state.php?ds_id=' + boot.ds_id)).json();
    });
    const before = await read();
    check(!before.monsters[0].boss, 'first encounter is not boss');
    await page.getByRole('button', { name: '일반 공격', exact: true }).click();
    await page.waitForFunction(() => document.querySelector('#maze-turn-status').textContent.includes('제출 완료'));
    const submitted = await read();
    check(submitted.battle.turn === before.battle.turn && submitted.monsters[0].hp === before.monsters[0].hp, 'selection submits without immediate damage');
    check(await page.getByRole('button', { name: '일반 공격', exact: true }).getAttribute('aria-pressed') === 'true', 'selected action marked');
    check(!(await page.locator('#maze-turn-resolve').isDisabled()), 'explicit time skip enabled when all submitted');
    check((await page.locator('#maze-turn-status').innerText()).includes('초'), 'remaining seconds visible');
    await page.locator('#maze-turn-resolve').click();
    await page.waitForFunction(turn => Number(document.querySelector('#maze-turn').textContent.replace('TURN', '').trim()) > turn, before.battle.turn);
    const resolved = await read();
    check(resolved.battle.turn === before.battle.turn + 1 && resolved.monsters[0].hp < before.monsters[0].hp, 'explicit turn completion deals damage');
    check(resolved.logs.some(row => row.message.includes('DAMAGE')), 'combat damage log JSON');
    check(await page.locator('#maze-error').isHidden() && errors.length === 0, 'no JSON or browser errors');
  } finally { await browser.close(); }
})().catch(e => { console.error(e); process.exit(1); });
