/*
 * Records what a legacy page region actually renders, as a porting spec.
 *
 *   node scripts/style-spec.mjs --url=http://localhost:8124/x --selector="nav.navbar" [--widths=375,768,1440,1920]
 *                               [--hover=selector] [--out=name]
 *
 * For every element inside the selector it writes the element's tag, classes,
 * own text, box (x, y, width, height) and the computed styles that affect how
 * it looks, at each width, to storage/parity/spec/<name>-<width>.json.
 * --hover hovers that element first, to record hover and open states.
 *
 * Porting from computed styles rather than from the 125 legacy stylesheets is
 * the method that made the navbar exact (docs/upgrade-plan.md §4.3): the
 * stylesheets override each other, and only the browser knows the result.
 */

import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((a) => {
    const [k, ...v] = a.replace(/^--/, '').split('=');
    return [k, v.join('=') || 'true'];
}));

if (! args.url || ! args.selector) {
    console.error('usage: --url=... --selector=... [--widths=...] [--hover=...] [--out=...]');
    process.exit(2);
}

const widths = (args.widths ?? '375,768,1440,1920').split(',').map(Number);
const name = args.out ?? args.selector.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '');
const outDir = path.resolve('storage/parity/spec');
fs.mkdirSync(outDir, { recursive: true });

const props = [
    'display', 'position', 'top', 'right', 'bottom', 'left', 'z-index', 'float',
    'flex-direction', 'flex-wrap', 'justify-content', 'align-items', 'gap',
    'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'border-top', 'border-right', 'border-bottom', 'border-left', 'border-radius',
    'box-shadow', 'background-color', 'background-image', 'opacity',
    'color', 'font-family', 'font-size', 'font-weight', 'font-style', 'line-height',
    'letter-spacing', 'text-transform', 'text-align', 'text-decoration-line', 'white-space',
    'list-style-type', 'cursor', 'overflow', 'transform', 'visibility',
];

const browser = await chromium.launch();

for (const width of widths) {
    const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width, height: 900 } });
    const page = await context.newPage();
    await page.goto(args.url, { waitUntil: 'networkidle', timeout: 120000 });
    await page.addStyleTag({ content: '*,*::before,*::after{animation:none!important;transition:none!important}[data-aos]{opacity:1!important;transform:none!important}' });
    await page.evaluate(() => document.fonts.ready);
    if (args.hover) {
        await page.hover(args.hover).catch(() => {});
        await page.waitForTimeout(300);
    }
    // --click=a,b clicks each selector in turn (e.g. the hamburger, then a menu).
    for (const target of (args.click ?? '').split(',').filter(Boolean)) {
        await page.click(target).catch((e) => console.error(`click ${target}: ${e.message.split('\n')[0]}`));
        await page.waitForTimeout(400);
    }

    const spec = await page.evaluate(({ selector, props }) => {
        const root = document.querySelector(selector);
        if (! root) return { error: `no element matches ${selector}` };
        const out = [];
        const walk = (el, depth) => {
            const cs = getComputedStyle(el);
            const r = el.getBoundingClientRect();
            const ownText = [...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent.trim()).join(' ').trim();
            const styles = {};
            for (const p of props) styles[p] = cs.getPropertyValue(p);
            const before = getComputedStyle(el, '::before');
            const pseudo = before.content && ! ['none', 'normal'].includes(before.content)
                ? { content: before.content, color: before.color, 'font-family': before.fontFamily, 'font-size': before.fontSize }
                : undefined;
            out.push({
                depth,
                tag: el.tagName.toLowerCase(),
                id: el.id || undefined,
                class: el.className && typeof el.className === 'string' ? el.className : undefined,
                text: ownText || undefined,
                href: el.getAttribute('href') ?? undefined,
                box: [Math.round(r.x * 10) / 10, Math.round((r.y + scrollY) * 10) / 10, Math.round(r.width * 10) / 10, Math.round(r.height * 10) / 10],
                visible: cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0,
                styles,
                before: pseudo,
            });
            for (const child of el.children) walk(child, depth + 1);
        };
        walk(root, 0);
        return out;
    }, { selector: args.selector, props });

    /*
     * Authored values: for each element, the declaration that wins the cascade
     * for each property, as written (e.g. "4.1vw", not "59.04px"), with the
     * stylesheet and selector it came from. Read through the DevTools
     * protocol, which resolves the cascade exactly as the browser did.
     */
    if (Array.isArray(spec)) {
        const cdp = await context.newCDPSession(page);
        const sheets = {};
        // Registered before CSS.enable, which replays styleSheetAdded for every existing sheet.
        cdp.on('CSS.styleSheetAdded', (e) => { sheets[e.header.styleSheetId] = e.header.sourceURL || (e.header.isInline ? 'inline <style>' : 'injected'); });
        await cdp.send('DOM.enable');
        await cdp.send('CSS.enable');
        const { root } = await cdp.send('DOM.getDocument', { depth: -1 });
        const { nodeIds } = await cdp.send('DOM.querySelectorAll', { nodeId: root.nodeId, selector: `${args.selector}, ${args.selector} *` });

        for (let i = 0; i < nodeIds.length && i < spec.length; i++) {
            const matched = await cdp.send('CSS.getMatchedStylesForNode', { nodeId: nodeIds[i] });
            const winners = {};
            // Resolve on longhands, as the browser does: `padding: 1vw 4.1vw` is
            // four padding-* entries, so a later `padding-bottom` can win one.
            const consider = (style, origin) => {
                const shorthands = new Set((style.shorthandEntries ?? []).map((s) => s.name));
                for (const p of style.cssProperties ?? []) {
                    if (shorthands.has(p.name) || p.disabled || p.parsedOk === false || p.value === '') continue;
                    const current = winners[p.name];
                    if (current?.important && ! p.important) continue;
                    winners[p.name] = { value: p.value, important: !! p.important, from: origin };
                }
            };
            for (const m of matched.matchedCSSRules ?? []) {
                // Skip the browser's own defaults and the animation freeze this script injects.
                if (m.rule.origin !== 'regular' || (sheets[m.rule.styleSheetId] ?? 'injected') === 'injected') continue;
                const media = (m.rule.media ?? []).map((x) => x.text).join(' and ');
                const file = (sheets[m.rule.styleSheetId] ?? 'inline').replace(/^.*\/(css|js)\//, '$1/').replace(/^https?:\/\/[^/]+/, '');
                consider(m.rule.style, `${file} ${m.rule.selectorList.text}${media ? ` @media ${media}` : ''}`);
            }
            if (matched.inlineStyle) consider(matched.inlineStyle, 'style=""');
            spec[i].authored = winners;
        }
    }

    const file = path.join(outDir, `${name}-${width}.json`);
    fs.writeFileSync(file, JSON.stringify(spec, null, 1));
    console.log(`${width}px: ${Array.isArray(spec) ? spec.length + ' elements' : spec.error} -> ${path.relative(process.cwd(), file)}`);
    await context.close();
}

await browser.close();
