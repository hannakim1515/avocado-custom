// Uses a fresh headless Chrome profile; no existing browser sessions are read.
const { chromium } = require('../../artifacts/system-browser/node_modules/playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const root = path.resolve(__dirname, '../..');
const out = path.join(root, 'artifacts/system-installer-ui');
fs.mkdirSync(out, { recursive: true });
(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('http://127.0.0.1:38081/_fixture_login.php', { waitUntil: 'networkidle' });
  await page.screenshot({ path: path.join(out, 'desktop-pending.png'), fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  await page.screenshot({ path: path.join(out, 'mobile-pending.png'), fullPage: true });
  assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'mobile horizontal overflow');
  assert.equal(await page.locator('[data-module="realtime"] button').isDisabled(), true);
  await page.locator('[data-module="map"] summary').click();
  assert.equal(await page.locator('[data-module="map"] details').getAttribute('open'), '');
  // Real click and redirect, with server-side authorization and CSRF.
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.locator('[data-module="map"] button').click()
  ]);
  assert.equal(await page.locator('[data-module="map"] button').count(), 0);
  assert.match(await page.locator('[data-module="map"]').innerText(), /설치됨/);
  execFileSync('php', [path.join(__dirname, 'installer_web_probe.php'), 'install'], { cwd: root });
  await page.reload({ waitUntil: 'networkidle' });
  assert.equal(await page.locator('.si-module button').count(), 0);
  assert.match(await page.locator('.si-qa').innerText(), /시스템 스키마 준비 완료/);
  await page.screenshot({ path: path.join(out, 'mobile-installed.png'), fullPage: true });
  await page.setViewportSize({ width: 1440, height: 1100 });
  await page.screenshot({ path: path.join(out, 'desktop-installed.png'), fullPage: true });
  assert.deepEqual(errors, [], 'browser JS errors');
  fs.writeFileSync(path.join(out, 'result.json'), JSON.stringify({ errors, mobileOverflow: false, realInstallClick: true, allInstalled: true }, null, 2));
  console.log('PASS: desktop/mobile render, dependency button, details, real install click, latest state, QADATA, no overflow/JS errors');
  await browser.close();
})().catch(error => { console.error(error); process.exit(1); });
