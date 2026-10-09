/*
 * Proves a ported page matches the legacy one — or shows exactly where it
 * does not. docs/upgrade-plan.md §7.
 *
 *   node scripts/parity-check.mjs [--legacy=http://localhost:8124] [--new=http://localhost:8123]
 *                                 [--paths=/a,/b] [--widths=375,768,1440,1920] [--max-diff=0.5]
 *
 * Without --paths it checks every route the port ledger marks "ported", at
 * that route's first live example URL.
 *
 * Compare against the legacy code running on the SAME database as the new
 * app (a clone of the legacy repo served locally), not against production:
 * the local database is a snapshot, so production content differs.
 *
 * For each page and width it records, under storage/parity/<page>/:
 *   - a full-page pixel diff (legacy.png, new.png, diff.png) and its ratio
 *   - visible-text differences, line by line
 *   - link / image / script target differences
 *   - the font faces each side actually loaded
 *   - console errors and CSP violations on the new page
 *
 * Animations, transitions and AOS fade-ins are frozen on both sides so that
 * timing cannot produce differences. Caveat: browsers use locally installed
 * fonts, so on a machine with Lato installed a page that never loads Lato
 * still renders it. Run on a machine without local Lato for a visitor's view.
 *
 * Exits 1 when any page exceeds --max-diff (percent of pixels) or differs in
 * text, links or fonts, or the new page logs an error.
 */

import { chromium } from 'playwright';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import fs from 'node:fs';
import path from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((a) => {
    const [k, ...v] = a.replace(/^--/, '').split('=');
    return [k, v.join('=') || 'true'];
}));

const legacyBase = (args.legacy ?? 'http://localhost:8124').replace(/\/$/, '');
const newBase = (args.new ?? 'http://localhost:8123').replace(/\/$/, '');
const widths = (args.widths ?? '375,768,1440,1920').split(',').map(Number);
const maxDiff = Number(args['max-diff'] ?? 0.5);
const outRoot = path.resolve('storage/parity');

const ledger = JSON.parse(fs.readFileSync('docs/port-ledger.json', 'utf8'));

/* Approved differences (docs/parity-allowlist.json): each one has a reason. */
const allow = JSON.parse(fs.readFileSync('docs/parity-allowlist.json', 'utf8'));
const notAllowed = (kind) => (line) => ! Object.hasOwn(allow[kind] ?? {}, line);
const paths = args.paths
    ? args.paths.split(',')
    : Object.entries(ledger.routes)
        .filter(([, r]) => r.status === 'ported' && r.examples?.[0]?.status === 200)
        .map(([, r]) => r.examples[0].url);

if (paths.length === 0) {
    console.log('No pages to check: none is marked "ported" in docs/port-ledger.json. Pass --paths=/a,/b.');
    process.exit(0);
}

/* Applied to both pages before capture: freeze everything time-dependent. */
const freezeCss = `
    *, *::before, *::after { animation: none !important; transition: none !important; caret-color: transparent !important; }
    [data-aos] { opacity: 1 !important; transform: none !important; }
    html { scroll-behavior: auto !important; }
`;

/* A fresh context per capture: a cold cache, like a first visit, every time. */
async function capture(browser, url, width) {
    const context = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
    // A failed resource is recorded by its URL. Chrome also logs a page that
    // answers 404 (the 404 page itself) as a console error; that is the
    // status being tested, not a fault, so console "Failed to load resource"
    // lines are dropped in favour of the URL-level record.
    page.on('console', (m) => {
        if (m.type() === 'error' && ! m.text().startsWith('Failed to load resource')) errors.push(`console: ${m.text()}`);
    });
    page.on('response', (r) => {
        if (r.status() >= 400 && r.request().resourceType() !== 'document') errors.push(`HTTP ${r.status()} ${r.url()}`);
    });
    await page.addInitScript(() => {
        document.addEventListener('securitypolicyviolation', (e) =>
            console.error(`CSP violation: ${e.violatedDirective} blocked ${e.blockedURI}`));
    });

    await page.setViewportSize({ width, height: 900 });
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
    await page.addStyleTag({ content: freezeCss });
    await page.evaluate(async () => {
        await document.fonts.ready;
        // Lazy images: scroll through once so every image is requested.
        for (let y = 0; y < document.body.scrollHeight; y += innerHeight) {
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 50));
        }
        window.scrollTo(0, 0);
        await document.fonts.ready;
    });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    const facts = await page.evaluate(() => {
        const norm = (u) => { try { const x = new URL(u, location.href); return x.origin === location.origin ? x.pathname + x.search : x.href; } catch { return u; } };
        return {
            text: document.body.innerText.split('\n').map((l) => l.replace(/\s+/g, ' ').trim()).filter(Boolean),
            links: [...new Set([
                ...[...document.querySelectorAll('a[href]')].map((a) => 'a ' + norm(a.getAttribute('href'))),
                ...[...document.querySelectorAll('img[src]')].map((i) => 'img ' + norm(i.getAttribute('src'))),
                ...[...document.querySelectorAll('iframe[src]')].map((f) => 'iframe ' + norm(f.getAttribute('src'))),
            ])].sort(),
            fonts: [...new Set([...document.fonts].filter((f) => f.status === 'loaded')
                .map((f) => `${f.family.replace(/["']/g, '')} ${f.weight} ${f.style}`))].sort(),
            title: document.title,
        };
    });

    const png = PNG.sync.read(await page.screenshot({ fullPage: true }));
    await context.close();

    return { status: response?.status() ?? 0, png, errors, ...facts };
}

/* Pads both images to the same size (white) so a height change shows as diff. */
function pixelDiff(a, b) {
    const width = Math.max(a.width, b.width);
    const height = Math.max(a.height, b.height);
    const pad = (img) => {
        if (img.width === width && img.height === height) return img;
        const out = new PNG({ width, height });
        out.data.fill(255);
        PNG.bitblt(img, out, 0, 0, img.width, img.height, 0, 0);
        return out;
    };
    const [pa, pb] = [pad(a), pad(b)];
    const diff = new PNG({ width, height });
    const changed = pixelmatch(pa.data, pb.data, diff.data, width, height, { threshold: 0.1 });

    return { diff, ratio: (changed / (width * height)) * 100, heights: [a.height, b.height] };
}

/* Lines present on one side only, in order (LCS-based). */
function lineDiff(a, b) {
    const n = a.length, m = b.length;
    const dp = Array.from({ length: n + 1 }, () => new Uint32Array(m + 1));
    for (let i = n - 1; i >= 0; i--) {
        for (let j = m - 1; j >= 0; j--) {
            dp[i][j] = a[i] === b[j] ? dp[i + 1][j + 1] + 1 : Math.max(dp[i + 1][j], dp[i][j + 1]);
        }
    }
    const out = [];
    let i = 0, j = 0;
    while (i < n && j < m) {
        if (a[i] === b[j]) { i++; j++; } else if (dp[i + 1][j] >= dp[i][j + 1]) { out.push(`- ${a[i++]}`); } else { out.push(`+ ${b[j++]}`); }
    }
    while (i < n) out.push(`- ${a[i++]}`);
    while (j < m) out.push(`+ ${b[j++]}`);

    return out;
}

const setDiff = (a, b) => [...a.filter((x) => !b.includes(x)).map((x) => `- ${x}`), ...b.filter((x) => !a.includes(x)).map((x) => `+ ${x}`)];

const browser = await chromium.launch();

const report = [];
let failed = false;

for (const pagePath of paths) {
    const slug = pagePath.replace(/^\//, '').replace(/[^a-z0-9]+/gi, '-').replace(/-$/, '') || 'root';
    fs.mkdirSync(path.join(outRoot, slug), { recursive: true });

    for (const width of widths) {
        const legacy = await capture(browser, legacyBase + pagePath, width);
        const fresh = await capture(browser, newBase + pagePath, width);
        const { diff, ratio, heights } = pixelDiff(legacy.png, fresh.png);

        const dir = path.join(outRoot, slug);
        fs.writeFileSync(path.join(dir, `${width}-legacy.png`), PNG.sync.write(legacy.png));
        fs.writeFileSync(path.join(dir, `${width}-new.png`), PNG.sync.write(fresh.png));
        fs.writeFileSync(path.join(dir, `${width}-diff.png`), PNG.sync.write(diff));

        const entry = {
            path: pagePath,
            width,
            status: [legacy.status, fresh.status],
            pixelDiffPercent: Number(ratio.toFixed(3)),
            heights,
            text: width === widths[0] ? lineDiff(legacy.text, fresh.text).filter(notAllowed('text')) : undefined,
            links: width === widths[0] ? setDiff(legacy.links, fresh.links).filter(notAllowed('links')) : undefined,
            fonts: setDiff(legacy.fonts, fresh.fonts).filter(notAllowed('fonts')),
            newPageErrors: fresh.errors,
        };
        const pass = entry.status[0] === entry.status[1]
            && entry.pixelDiffPercent <= maxDiff
            && (entry.text?.length ?? 0) === 0
            && (entry.links?.length ?? 0) === 0
            && entry.fonts.length === 0
            && entry.newPageErrors.length === 0;
        entry.pass = pass;
        failed ||= ! pass;
        report.push(entry);

        console.log(`${pass ? 'PASS' : 'FAIL'}  ${pagePath} @${width}  pixels ${entry.pixelDiffPercent}%  height ${heights.join('→')}`
            + (entry.text?.length ? `  text ${entry.text.length}` : '')
            + (entry.links?.length ? `  links ${entry.links.length}` : '')
            + (entry.fonts.length ? `  fonts ${entry.fonts.length}` : '')
            + (entry.newPageErrors.length ? `  errors ${entry.newPageErrors.length}` : ''));
    }
}

await browser.close();
fs.writeFileSync(path.join(outRoot, 'report.json'), JSON.stringify(report, null, 2));
console.log(`\nreport: storage/parity/report.json — images under storage/parity/<page>/`);
process.exit(failed ? 1 : 0);
