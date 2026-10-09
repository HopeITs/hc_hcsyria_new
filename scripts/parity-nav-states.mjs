/*
 * Compares the navigation's interactive states between the legacy page and
 * the new one: menus opened by hover and by click, an item hovered inside an
 * open menu, a link hovered, and the phone menu closed, open, and with each
 * dropdown expanded. docs/upgrade-plan.md §7.
 *
 *   node scripts/parity-nav-states.mjs [--path=/projects/micro-projects]
 *        [--legacy=http://localhost:8124] [--new=http://localhost:8123] [--max-diff=0.5]
 *
 * Only the top 560px (the navigation and what it opens) is compared, so the
 * page below it may differ without failing this check; scripts/parity-check.mjs
 * covers the whole page. Writes legacy/new/diff images to storage/parity/states/.
 * Exits 1 when any state differs by more than --max-diff percent of pixels.
 *
 * Hover steps wait 1.3s so the 250ms-delay + 400ms slide of the legacy menus
 * has finished on both sides. Run on pages where the menus behave as the
 * header's `menu` mode says (see resources/js/site-nav.js).
 */

import { chromium } from 'playwright';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import fs from 'node:fs';

const args = Object.fromEntries(process.argv.slice(2).map((a) => {
    const [k, ...v] = a.replace(/^--/, '').split('=');
    return [k, v.join('=') || 'true'];
}));
const pagePath = args.path ?? '/this-page-does-not-exist';
const LEG = (args.legacy ?? 'http://localhost:8124').replace(/\/$/, '') + pagePath;
const NEW = (args.new ?? 'http://localhost:8123').replace(/\/$/, '') + pagePath;
const maxDiff = Number(args['max-diff'] ?? 0.5);
const only = args.only;

// [name, width, legacy steps, new steps]; a step is hover=sel or click=sel
const nth = (n) => `ul.navbar-nav > li:nth-child(${n})`;
const states = [
  ['what-hover-1440', 1440, [`hover=${nth(5)}`], [`hover=.site-nav__list > li:nth-child(5)`]],
  ['pub-hover-1440', 1440, [`hover=${nth(8)}`], [`hover=.site-nav__list > li:nth-child(7)`]],
  ['what-hover-item-1440', 1440, [`hover=${nth(5)}`, `hover=${nth(5)} a[href="/projects/disc"]`], [`hover=.site-nav__list > li:nth-child(5)`, `hover=.site-nav__list > li:nth-child(5) a[href="/projects/disc"]`]],
  ['home-hover-1440', 1440, [`hover=${nth(1)} a`], [`hover=.site-nav__list > li:nth-child(1) a`]],
  ['what-click-768', 768, [`click=${nth(5)} > a`], [`click=.site-nav__list > li:nth-child(5) > a`]],
  ['pub-click-768', 768, [`click=${nth(8)} > a`], [`click=.site-nav__list > li:nth-child(7) > a`]],
  ['mobile-open-375', 375, ['click=#my-navbar-toggle'], ['click=.site-nav__toggler']],
  ['mobile-what-375', 375, ['click=#my-navbar-toggle', `click=${nth(5)} > a`], ['click=.site-nav__toggler', 'click=.site-nav__list > li:nth-child(5) > a']],
  ['mobile-pub-375', 375, ['click=#my-navbar-toggle', `click=${nth(8)} > a`], ['click=.site-nav__toggler', 'click=.site-nav__list > li:nth-child(7) > a']],
];
const b = await chromium.launch();
const shot = async (url, width, steps) => {
  const c = await b.newContext({ viewport: { width, height: 900 }, reducedMotion: 'reduce' }); const p = await c.newPage();
  await p.goto(url, { waitUntil: 'networkidle' }); await p.addStyleTag({ content: '*{animation:none!important;transition:none!important}' }); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(500);
  for (const s of steps) { const i = s.indexOf('='); const [k, sel] = [s.slice(0, i), s.slice(i + 1)]; await (k === 'hover' ? p.hover(sel) : p.click(sel)); await p.waitForTimeout(1300); }
  const buf = await p.screenshot({ clip: { x: 0, y: 0, width, height: 560 } }); await c.close(); return PNG.sync.read(buf);
};
fs.mkdirSync('storage/parity/states', { recursive: true });
let failed = false;
for (const [name, width, ls, ns] of states) {
  if (only && !name.includes(only)) continue;
  const a = await shot(LEG, width, ls), n = await shot(NEW, width, ns); const d = new PNG({ width: a.width, height: a.height });
  const px = pixelmatch(a.data, n.data, d.data, a.width, a.height, { threshold: 0.1 });
  fs.writeFileSync(`storage/parity/states/${name}-legacy.png`, PNG.sync.write(a)); fs.writeFileSync(`storage/parity/states/${name}-new.png`, PNG.sync.write(n)); fs.writeFileSync(`storage/parity/states/${name}-diff.png`, PNG.sync.write(d));
  const percent = (px / (a.width * a.height)) * 100;
  if (percent > maxDiff) failed = true;
  console.log(`${percent <= maxDiff ? 'PASS' : 'FAIL'}  ${name.padEnd(24)} ${percent.toFixed(3)}%`);
}
await b.close();
process.exit(failed ? 1 : 0);
