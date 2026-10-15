# hcsyria.org — full upgrade plan to the TALL stack

**Goal:** rebuild hcsyria.org on Tailwind 4 (Oxide engine) + Alpine + Laravel +
Livewire with a component structure, clean architecture and the security
baseline of the Tracking app (`E:/07-ProjectTracking/hc-tracking`) — while the
public site's **content, look and UX stay exactly as they are**, and nothing
unused (file, function, route, image, font, library) is carried over.

**Two different rules for two halves of the site** (decided 2026-10-09):

| | Public site | Admin dashboard |
|---|---|---|
| Look | Pixel parity with legacy | **Full redesign** with the `impeccable` skill |
| UX | Unchanged | Improved — errors and weak spots fixed |
| What must not change | Content, layout, behaviour | What staff can *do*: every legacy admin action still exists |
| Verified by | Visual/text/style diff against legacy (§7) | Workflow checklist against legacy + `impeccable` audit |

Written 2026-10-09 against:

- legacy app: `E:/03-Website/hcsyria.org Website/hcsyria.org-website` @ `6d45ef9`
- this repo: `phase-0/legacy-schema-capture` @ `ebfce56`
- security reference: `hc-tracking` @ `5f63398`

---

## Progress

Public side first; the admin dashboard comes after (decided 2026-10-09).

| Phase 1 step | Status |
|---|---|
| 1. Re-baseline against the canonical legacy repo | **Done** — manifest regenerated (276 methods to port), analyzer now flags broken routes |
| 2. `ProjectSearch` up to current legacy behaviour | **Done** — verified on 162 filter combinations; the micro-projects *page* still needs a full port (§9 Phase 2) |
| 3. Port ledger + drift detector | **Done** — `docs/port-ledger.json`: 53 public routes, 30 to port, 229 assets; `scripts/legacy-drift.php` |
| 4. Laravel 13 / PHP 8.4, Pest, Larastan, Pint | **Done** — Laravel 13.35, platform pinned to 8.4.21, Larastan level 7 clean, Pint clean, 19 tests |
| 5. Security middleware from Tracking | **Done for the public side** — headers + CSP, HTTPS redirect, HSTS, cookieless throttle, guest and Livewire limiters. `app_` tables wait for the admin phase. |
| 6. Analyzer checks views exist | **Done** |
| 7. Fonts to `.woff2`, tokens | **Done** — Lato 400/700/900 + Archivo 400 self-hosted, Google Fonts gone from layout and CSP, unused tokens removed. Icon glyphs move to inline SVG with each page port. |
| 8. Verification harness (§7) | **Done** — `npm run parity`: pixel, text, link, font and console checks at 4 widths against a local legacy reference on the same database; approved differences in `docs/parity-allowlist.json`. |

| Phase 2 step | Status |
|---|---|
| Layout, header, footer (default variant) | **Done** — measured against the running legacy pages at 375/768/1440/1920px; menus, hover fills and the phone menu compared state by state (`npm run parity:nav`, 9 states, all under 0.25% pixel difference) |
| Every error page (401 403 404 419 429 500 503 + any 4xx/5xx) | **Done** — see §9, Error pages |
| Home page `/home` | **Done** — matches legacy at 375/768/1440/1920px (≤0.45% of pixels) and as a phone (0.23%); 18 interactive nav states; scroll switch and preloader behaviour checked; 63 tests |
| Who we are `/who-we-are` (+ `/who-we-are/partners`) | **Done** — matches legacy at 375/768/1440/1920px (≤0.32%) and as a phone (0.42%); desktop and tablet nav states pass, the phone ones differ only in text anti-aliasing (every box identical); 70 tests. New shared pieces: carousel, page title, red rule, section title, torn edge, showcase, partner logos |
| Micro-projects `/projects/micro-projects` (visitor view) | **Done** — matches legacy at 375/768/1440/1920px (≤0.41%) and as a phone (0.42%); all 9 nav states; 79 tests. Brief, statistics (Chart.js replaced by an SVG ring), funders, sign-in gate and the micro footer. The sign-in works (Fortify `POST /login`, five tries a minute per account and address, deactivated accounts refused, safe return to the page; the `view-micro-projects` gate by the Micro Public Page permission or the Admin/Publisher role; 13 tests). **Member view done** (applied filters and the view are in the address — `?city=homs&donor=caritas-poland&video=1&search=&view=grid`, cities and donors by slug — so a filtered list can be shared and the back button steps through filters; the open details window is `?projectDetailsModal=3033-pick-up-truck` (id and name; legacy's bare-id links still open and are rewritten); Clear filters; the lightbox puts the open slide in the address (`#tile-3044-2`, `#details-3044-2`, as fancybox did) and opens it from a link; the details window keeps keyboard focus inside and returns it; search suggestions cached 10 minutes) (legacy's `livewire:micro-projects`): filters (city, donor, video, search box with suggestions, view switch, Apply), tiles and rows, load-more 15 at a time, the details window with the `?projectDetailsModal=` deep link, the Leaflet map of the filtered set and the signed-in account badge with Sign out. Matches legacy for an authorised member at 1440px (tiles 0.14%, rows 0.21%, details window pixel-aligned), 375/768/1920px within layout noise, 24 tests. Deliberate differences: the Livewire component re-checks the `view-micro-projects` gate on every request; a deep link to a project that is not on the public list opens nothing (legacy loaded any id); no extra tile is appended for a deep-linked project; the map has one pin per project (legacy added duplicates on Load More); swipe-up on the details window closes it (legacy showed the hint but never wired it); the reload skeleton is one set of shapes, not 36 |
| DISC `/projects/disc` | **Done** — matches legacy at 375/768/1440/1920px (≤0.57%, the rest being the YouTube player's own icons) and as a phone; the learner rings checked against the legacy chart with test figures. New shared pieces: category showcase, testimonials, map of Syria with centre statistics, activity tabs and cards, pager, lightbox (fancybox replaced), page accent colour (`--accent`), the YouTube variant of the brief |
| Study Zone `/projects/study-zone` | **Done** — section heights match legacy at 768 and 1440px (375px within 2px); pixel diffs left are slideshow timing and legacy's unfinished 5s map-outline animation. New shared pieces: radial bars (SVG), search box (typeahead replaced), zone map, statistics band, centre spread with Leaflet. Needs `DB_STUDYZONE_*` and `STADIA_API_KEY` in `.env`; without the former the statistics band is left out. Deliberate differences: gender rings labelled by the actual gender, search uses bound queries, map shows its tile attribution |
| School to School `/projects/school-to-school` | **Done** — section heights match legacy at 375 (within 4px), 768, 1440 and 1920px; the five hover cards match legacy (1%). Remaining pixel differences are the cover slideshow and legacy's random photo order. New shared pieces: mailbox (sticky letters), statistics scene with hover cards. Media to ship: `images/home/school-statistics.svg` (the scene, extracted from the legacy page). Not ported: a phone figures list that never showed |
| Activities section, shared by Study Zone, DISC and the micro-projects list (branch `study-zone-shared-sections-with-micro`) | **Done, by request, not legacy parity**: the activities of Study Zone and DISC are one Livewire component (`activity-list`) with a tiles view (default) and the legacy rows, an instant view switch, Load More six at a time instead of the pager, and the micro-projects filter bar in place of the town tabs: a city, a donor (the donors of that programme's activities), a video (no activity has one yet, so it finds none), the search box with Apply, and Clear filters. The applied filters and the view are in the address (`?city=homs&donor=...&search=...&view=grid`; legacy `?ini_H=2` still picks the city). Both programmes now have the search box. Shared with micro-projects: `x-site.project-filters`, `x-ui.view-switch`, `x-ui.load-more`, `x-ui.card-skeleton`, the card grid and the search box. Rows are the previous cards unchanged; per-town alternation now counts from each list's first card |
| Family to Family `/projects/family-to-family` | **Done** — matches legacy at 375/768/1440/1920px (≤0.13%; the rest is slideshow timing). New shared pieces: the mirrored brief (`project-brief :mirror`), the verse beside a video, the survey band, the partner showcase (twin carousels sharing a slide), the distribution map |
| Footer variants (micro-projects and DISC done; children, LEAP) | The shared `site.footer.bar` and `site.footer.centers` are ready for the two that remain, with their pages |
| The 30 public pages | Next |

Found on the way: the Phase 0 migration guard did not run under
`APP_ENV=testing`, so `migrate:fresh` (or `RefreshDatabase`) could have wiped
`hcsyria_db`. Fixed and tested — see `docs/legacy-schema-capture.md`.

---

## 1. Where we stand

Phase 0 is done and verified (see the other files in `docs/`):

| Done | Evidence |
|---|---|
| Schema of `hcsyria_db` captured as 107 migrations (69 tables, 61 FKs, 2 procs) | `scripts/verify-schema.php` diffs a rebuild against live: exact |
| Models generated, French removed in code only | `scripts/verify-models.php` |
| Query layer: 9 query classes replacing ~70 legacy call sites | `scripts/verify-queries.php` diffs against legacy results |
| Reachability analysis: 254 of 344 controller methods need porting | `docs/controller-port-manifest.md` |
| Shared-DB guard: `migrate`/`db:wipe` refused against live databases | `AppServiceProvider` |
| First page ported: `/projects/micro-projects` (header, footer, card, dropdown) | commits `cb50033`…`ebfce56` |
| Five security findings in the live site documented | `docs/security-findings.md` |

**The old website is not touched.** Decided 2026-10-09: no change is made to the
legacy code, its server configuration or its files. It is read-only reference
material — read to port from, measured to compare against, never edited.

Consequences, applied throughout this plan:

- **One cutover instead of route-by-route.** Moving routes across one at a time
  needs a routing rule in front of the old site, which is a change to it. The
  new app is built and verified on a staging address against the same
  `hcsyria_db`, and the domain switches to it once (Phase 5).
- **Legacy tables are fixed;** the new app may only add `app_` tables (§5.4).
- **Known holes in the old site stay open until cutover** (§2.2–2.3). They close
  when the new app replaces it, which makes time-to-cutover a security metric.

---

## 2. Findings that change the plan

These came out of today's audit and have to be dealt with before more pages are
ported.

### 2.1 Phase 0 analysed a stale copy of the legacy code — **blocking**

Phase 0 read `E:/03-Website/hcsyria_website` (last commit 2026-05-04). The
repository actually deployed is `E:/03-Website/hcsyria.org Website/hcsyria.org-website`,
which is **162 commits and 222 files ahead** (13,598 lines added, 6,353 removed).
Both point at the same GitLab remote; the first is an old clone.

What changed that affects work already done:

- **Micro-projects search.** The legacy search now matches a numeric term exactly
  against `MicroProject_ID`, and otherwise searches beneficiary, both partner
  names, project name, `MPE_ExpSkill` and `MPE_Suffering`. Images are ordered by
  `image_order`. The typeahead lists project IDs and partner names too.
  `App\Queries\ProjectSearch` reproduces the *old* behaviour, so the page already
  ported is behind.
- **New features:** `NewsPage` Livewire component (news grid with load-more), a
  locked "online" section and a login for micro-projects, the hidden-content
  state (`hidden` column + Hide/Unhide in eight admin screens), a YouTube API
  integration, a redesigned login page, dynamic footer, night mode, a new
  homepage cover design, Roots of Hope magazine changes, new donor logos.
- **Schema:** the `hidden` columns and indexes are already in our migrations
  (they were captured from the live DB). Two data migrations
  (`add_region_sud_donor`, `point_paris_donor_to_new_logo`) change rows, not
  schema — nothing to do but to be aware.
- `scripts/analyze-controllers.php` and `scripts/inventory-queries.php` default
  to the stale path.

**And it will keep happening:** the legacy app gained 162 commits in five months.
A port that is correct today drifts tomorrow. See §8 for the drift protocol.

### 2.2 The live server shows signs of compromise

Recorded so the new app is built not to repeat any of it. Per the decision in
§1, none of it is fixed in the old site; it all goes away at cutover.


- Commit `5e2b78e` (2026-06-16) — *"REMOVE: sh.php malicious file"*. A web shell
  was in `public/`. Nothing in the repository shows how it got there or whether
  anything else was planted; deleting the file from git does not clean the server.
- Uploaded files are stored inside the web root (`public/images/**`, 11 GB,
  43,000+ files). There are stray PHP upload temp files inside image folders
  (`public/images/home/Micro projects/Brief/*.jpg/php4F59.tmp`) and
  `public/.htaccess` does nothing to stop PHP running from upload folders. That
  is the textbook path for planting a shell.
- Upload validation checks names and sizes, not contents: most image fields are
  validated only with `max:`; three rules use `image|mimes`, which trust the
  client.
- `public/php.ini` sets `display_errors=On`, `upload_max_filesize=5000M`,
  `memory_limit=4000M`, `max_execution_time=10000` in production.
- Backup-tool logs are publicly downloadable: `public/images/_gsdata_/*.log`.

### 2.3 Earlier security findings are still live

From `docs/security-findings.md`, all still open in the legacy app: SQL
injection on four public routes, the broken public `/register` flow, a database
password committed to git. The new app does not have them (bound queries, no
registration, no secrets in config), and they disappear at cutover. Credentials
the old server held are rotated at cutover (Phase 5), since the new app should
not start life with secrets an attacker may already have.

(Corrected 2026-10-09: `/about` was listed as publishing 81 former staff. It
does not — the view it renders does not exist, and the live site answers it
with a 500. It is a dead route; the new app 301s it to `/who-we-are`.)

### 2.4 Where "change nothing visible" collides with something else

The parity rule is strict on the public site. These are the places where obeying
it fully means choosing between two goods.

| Collision | Detail |
|---|---|
| Keyboard focus ring and skip link | Already added in the new app (`app.css`). Invisible to mouse users, visible on Tab. Recommended to keep (accessibility), but it is a deviation. |
| Legacy sizing is in `vw` | Text scales with the window, so it is tiny on phones. Parity keeps that. Improving it is a visual change. |
| Three slightly different reds (`#BC1818`, `#CF142B`, `#bc1212`) | Parity keeps all three as separate tokens. Merging them would be cleaner but visible. |
| Dead or broken public URLs (`/about` → 500) | Not ported. A 301 to the live equivalent replaces a 500 — the only visible difference. |

**Avenir — requested, never loaded.** (Corrected 2026-10-09 after measuring the
live pages; an earlier version said nothing used it.) `.quote span` in
`main-new.css` — the decorative “ on `/who-we-are` — and two headings on
`/projects/family-to-family` ask for `font-family: Avenir`, but no Avenir face
ever loads, so visitors without Avenir installed see the browser's default
font. Ported rules keep naming Avenir and nothing is loaded: identical result,
no licence question. The `.otf` in `public/fonts` and the `fonts.cdnfonts.com`
link are dropped. **Lato is the site's text face**; Martel is linked but never
drawn. See §4.4 for the full measurement.

---

## 3. Target stack

Aligned with the Tracking app so both projects share tooling, habits and code.

| Layer | Choice | Notes |
|---|---|---|
| PHP | **8.4** — production runs 8.4.21 | `composer.json` pins `"config": {"platform": {"php": "8.4.21"}}` so the lockfile can never pull a package that needs 8.5. Tracking runs 8.5, but none of its code uses 8.5-only syntax (no `\|>`, clone-with, `#[NoDiscard]`; checked), so its classes copy across unchanged. |
| Framework | Laravel 13 (needs PHP ^8.3) | This repo is on 12; upgrade in Phase 1 while it is still small. |
| Reactivity | Livewire 4 | Single-file `⚡` components, as already used. |
| JS | Alpine.js **as bundled by Livewire** | Never installed separately: one copy, one version. |
| CSS | Tailwind 4 via `@tailwindcss/vite` | v4's engine is Oxide. Only utilities that are used are emitted. |
| Build | Vite (same major as Tracking) | All JS/CSS/fonts/static images go through Vite: hashed, cached, no CDN. |
| Auth | Laravel Fortify | Login, password reset, 2FA, passkeys — as in Tracking. No public registration. |
| Authorization | Gates + Policies over the **legacy** `roles`/`permissions` tables | `spatie/laravel-permission` needs its own tables; adopt it only once the DB is forked (§9). |
| Tests | Pest 5 (needs PHP ^8.4) | Replaces PHPUnit scaffolding. |
| Admin UI | Tailwind components designed with the `impeccable` skill | Public-site components are parity ports; admin components are new designs. The two sets stay separate (`components/site` vs `components/admin`). |
| Static analysis | Larastan level 7 (as Tracking), raise later | `phpstan.neon` copied from Tracking. |
| Style | Pint, `laravel` preset | `pint.json` copied from Tracking. |

Removed from the stack: jQuery, Bootstrap CSS/JS, Bootstrap RTL, DataTables,
fancybox, prettyPhoto, isotope, twentytwenty, jquery.mobile, html5shiv, head.js,
Font Awesome 4.7, Glyphicons, Bootstrap Icons, axios, every CDN include, and the
duplicate copies of chart.js (three versions), AOS (two versions) and Bootstrap
(local + 5.0.2 CDN). Libraries that do something Alpine cannot reproduce
exactly — GSAP for the magazine animation, Leaflet for maps, one charting library
— stay, installed **once** via npm and loaded only on the pages that use them.

---

## 4. Architecture

### 4.1 Layers

```
Request
  └─ route (routes/web.php, routes/admin.php)
       └─ Livewire page component        UI state + rendering only, no SQL
            ├─ app/Queries/*             every read  (exists)
            ├─ app/Actions/*             every write (exists: DeleteProject)
            └─ Blade components          markup, no logic
                 └─ Models (generated)   never hand-edited (exists)
```

Rules, enforced in review and by Larastan:

1. **No SQL outside `app/Queries` and `app/Actions`.** A component never calls
   `DB::`, `->where()` or a model's static query methods.
2. **One page = one Livewire full-page component.** Static pages too, so there is
   one pattern instead of two. No controllers except Fortify's.
3. **Blade components own markup; they take data, they never fetch it.**
4. **Models are generated.** Logic that would be a scope goes in a Query class.
5. **Every write is an Action:** validates, authorizes, runs in a transaction,
   writes the audit log. Livewire calls the Action.

### 4.2 Folder structure

```
app/
  Actions/                 one class per use case: PublishNews, HideProject, UploadProjectImages …
  Queries/                 fluent read builders (existing pattern, docs/query-layer.md)
  Models/                  generated — do not edit
  Livewire/Forms/          Livewire form objects for admin create/edit
  Policies/                one per content type, over legacy roles
  Rules/SecureUpload.php   from Tracking
  Support/                 BackoffThrottle, Turnstile, UploadName, MediaPath, HtmlSanitizer
  Http/Middleware/         SecurityHeaders, RedirectToHttps, ThrottleRequestsWithoutSession,
                           EnsureRequestCameThroughTrustedProxy (all from Tracking)
resources/
  views/
    layouts/               app (public), admin, auth
    components/
      site/                header, footer, nav-dropdown, newsletter …   (public chrome)
      ui/                  dropdown, modal, tabs, pagination, icon …     (generic)
      project/ news/ initiative/ team/ …                                 (domain cards & blocks)
      admin/               table, form-field, image-uploader, state-tabs …
    pages/
      ⚡home.blade.php, ⚡about.blade.php, projects/⚡micro-projects.blade.php …
      admin/news/⚡index.blade.php …
  css/
    app.css                @import of the partials below, @theme tokens
    base/                  fonts.css, reset parity rules
    components/            one file per component family, all as @utility
  fonts/                   self-hosted .woff2 only
  images/                  design assets (logos, icons, backgrounds) — through Vite
  js/app.js                Livewire/Alpine boot + page-specific dynamic imports
lang/en/                   only keys that a ported view uses
storage/app/media/         user uploads (see §6) — never under public/
```

### 4.3 Styling parity — how

- Each legacy CSS rule a **live** view uses is ported as a Tailwind `@utility`
  with a comment pointing at the source file and selector (the pattern already
  in `app.css`). Utilities are emitted only when used, so unused CSS cannot ship.
- Repeated literal values become `@theme` tokens. Near-duplicates stay separate
  (§2.4).
- Values are copied from `getComputedStyle` on the running legacy page, not
  read from the stylesheet. 125 stylesheets with overrides on overrides mean the
  source rarely says what the browser renders. (This is what fixed the navbar in
  `ebfce56`.)
- Hover, open, focus and active states are checked the same way, not just the
  resting state.

### 4.4 Fonts — `.woff2`, self-hosted, only what is used

**Done (2026-10-09).** Measured on all 28 reachable public pages with
`document.fonts` and computed styles, including `::before` icon glyphs:

| Face | Live site | New site |
|---|---|---|
| Lato 400 / 700 / 900 | Loaded on 24 pages; **not loaded on `/home` and `/projects/disc`** (those fall back to Arial/Helvetica for visitors without Lato) | Self-hosted, every page — decided: fix the two pages |
| Lato 100 / 500 / 600 / 750 / 800 | Requested by CSS, never loaded; browsers draw the nearest of 400/700/900 | Not added — same rendering |
| Archivo 400 | `/our-news` and articles | Self-hosted; fetched only on pages that use it |
| Martel, Caudex, Archivo Black | Linked, never drawn | Dropped |
| Avenir | Requested, never loaded | Name kept in ported rules, nothing loaded |
| `system-ui`, Lucida Console, Helvetica Neue | System fonts (`/qr/disc`, a few elements) | Nothing to load |
| FontAwesome 4.7 (all pages), Font Awesome 6 (articles), bootstrap-icons (404) | Icon fonts | Inline SVG per glyph, ported with each page |

Eight `.woff2` files (Google's own files and `unicode-range` subsets, latin +
latin-ext; 113 KB, ~69 KB on a typical page), `resources/css/fonts.css`, Lato
400 preloaded, Google Fonts removed from the layout and the CSP. The body now
computes as live: `Lato, sans-serif`, `#212529`, no font-smoothing override.

**Testing caveat:** the development PC has every Lato weight installed, so
pages there can look right while visitors see a fallback. The verification
harness (§7) must run in a browser profile without locally installed Lato.

Original method, kept for reference:

1. On each live legacy page, list the `font-family` + `font-weight` + `font-style`
   combinations actually rendered (`document.fonts` and computed styles). Lato
   is the text face and Martel the display face; the stylesheets also request
   Archivo, Archivo Black, Almarai, Caudex, Margarine, Love Ya Like A Sister,
   Aclonica, Noto Serif and PT Serif on some pages — each is kept only if a live
   page draws it. Avenir is already known to be unused (§2.4).

   Weights matter as much as families: Lato is loaded at 400/700/900, yet some
   rules ask for `font-weight: 100`, which the browser draws with the nearest
   loaded weight. Self-host **exactly the weights legacy loads** — adding Lato
   100 would make that text visibly thinner than it is today.
2. For each one used: take the `.woff2` Google serves (byte-identical to what
   visitors render today), subset it to the characters the site uses (Latin, plus
   Arabic for Almarai if any Arabic text remains), store it under
   `resources/fonts/<family>/`.
3. One `@font-face` per weight with `font-display: swap` — the Tracking pattern.
   `<link rel="preload">` for the one or two faces above the fold.
4. Remove the Google Fonts `<link>` from `layouts/app.blade.php`. CSP then
   allows `font-src 'self'` only.
5. Icon fonts (Font Awesome 4.7, Glyphicons, Bootstrap Icons) go too: each glyph
   actually used becomes an inline SVG through one `<x-ui.icon name="…"/>`
   component, with the same glyph outline and size. Three icon fonts are
   replaced by a few kilobytes of SVG.

---

## 5. Security baseline

Everything Tracking does, mapped to this site. "Adapt" means the reason it
cannot be copied as-is is written next to it.

### 5.1 HTTP layer

| Control (Tracking file) | Here |
|---|---|
| `SecurityHeaders` — nosniff, frame options, referrer, permissions, COOP, HSTS, CSP, strip `X-Powered-By` | **Adapt.** CSP gets `frame-src` for the YouTube embed (`youtube-nocookie.com`) and `img-src` for the map tile server, nothing else, because every CDN is gone. |
| `RedirectToHttps` + HSTS from config, rollback via `max-age=0` | As-is |
| `EnsureRequestCameThroughTrustedProxy` | As-is if Cloudflare (or another proxy) is in front, otherwise off |
| `ThrottleRequestsWithoutSession` + `throttle:guest` | **Adapt.** Sessions are `file` here (shared DB has no `sessions` table), so a cookieless flood fills the disk instead of a table. The same fix applies. |
| Named rate limiters for login, search, newsletter, Livewire updates | Add limiters for the public search box and newsletter signup |
| 403s recorded by `DeniedAccessRecorder` | As-is, written to `app_denied_access` (§5.4) |
| Error pages without stack traces; `APP_DEBUG=false` | As-is. The new app ships no `public/php.ini`; PHP limits are set per host, `display_errors=Off`. |

### 5.2 Authentication and authorization

| Control | Here |
|---|---|
| Fortify login, password reset, no registration | As-is. Fixes finding 4 (broken `/register`). |
| 2FA (TOTP + emailed codes), passkeys, `EnsureTwoFactorIsEnabled` for admins | **Adapt:** secrets go in the `app_user_security` side table, not new columns on `users` (§5.4). |
| `BackoffThrottle` (lockout doubles each time) | As-is |
| `AlertOnLockoutSurge` (emails admins on a guessing run) | As-is |
| Turnstile on the login page, CSP opened for it on that route only | As-is. Also on the public newsletter and contact forms, with a honeypot field. |
| Policies per content type | **Adapt:** over the legacy `roles`, `permissions`, `users_roles`, `users_places`, `users_centers` tables, reproducing today's rules (e.g. "Admin or English Editor may publish news", place-scoped editors). |
| Session cookie `secure`, `http_only`, `same_site=lax`, encrypted | As-is |
| `ReencryptSecrets` + `APP_PREVIOUS_KEYS` for key rotation | As-is |

### 5.3 Data and content

- **Uploads:** `SecureUpload` (checks the bytes, not the name) on every upload
  field; random names from `UploadName`; stored on a disk outside `public/`;
  images re-encoded on upload, which strips metadata. **Check whether existing
  beneficiary photos carry GPS EXIF data** — for photos taken in Syria, that is a
  safety problem, not just privacy.
- **Rich text:** the admin editor is CKEditor 5 **v12.4 from 2019** off a CDN.
  Replace it with a current, npm-installed editor, and sanitize stored HTML on
  save and on output (Symfony HtmlSanitizer). Audit every `{!! !!}` in ported
  views — each one either goes through the sanitizer or is removed.
- **Queries:** everything bound; LIKE wildcards escaped (already done in
  `ProjectSearch`). No `whereRaw` with interpolation, enforced by a test that
  scans `app/`.
- **Mass assignment:** the generated `$fillable` stays the only gate.
- **Secrets:** nothing in config defaults; `.env` never committed (it is
  gitignored here).

### 5.4 New-app tables live in `hcsyria_db`, additive only

Decided 2026-10-09: 2FA secrets, passkeys, the denied-access log and other
new-app data go into the same database, `hcsyria_db`. The legacy app keeps
working because it never sees them. The rule changes from "the schema is fixed"
to **"legacy tables are fixed; new tables may be added"**:

| Rule | Why |
|---|---|
| New tables only, all prefixed `app_` (`app_user_security`, `app_passkeys`, `app_denied_access` …) | Cannot collide with a legacy table, now or later. Easy to list and drop. |
| **Never** `ALTER` a legacy table | The legacy app reads them; that is where breakage comes from. |
| 2FA secrets in `app_user_security` (one row per user), **not** new columns on `users` | Legacy `User::$hidden` hides only `password` and `remember_token`. Any legacy code that turns a user into JSON would publish a new `two_factor_secret` column. A side table cannot leak that way. |
| Own migrations folder `database/migrations/app/` and own tracking table `app_migrations` (`database.migrations.table`) | The legacy app runs its own migrations on `hcsyria_db` (e.g. `add_hidden_to_content_tables`) and has its own `migrations` table. Sharing it would mix the histories, and our 107 captured migrations would look "not yet run" and try to create every table. |
| The `AppServiceProvider` guard is narrowed, not removed | On a protected database it allows exactly `migrate --path=database/migrations/app` with the `app_migrations` table, and still refuses `migrate:fresh`, `migrate:reset`, `migrate:rollback` past the app batch, `db:wipe` and `schema:dump`. |
| Sessions and cache stay on `file` for now | Fewer moving parts during coexistence; revisit at cutover. |

The database backups (SqlBackupAndFTP) now cover the new tables automatically.

### 5.5 Supply chain and CI

`composer audit` and `npm audit` fail the build on high-severity findings.
Lockfiles are committed. No runtime CDN — the CSP blocks third-party scripts
anyway. Pint, Larastan and Pest run on every push.

---

## 6. Media: 11 GB, and most of it may be unused

The legacy `public/` is 11 GB: `images/` 11 GB (43,134 jpg, 242 png, 237 svg,
45 jpeg, 36 gif), `files/` 76 MB, `js/` 11 MB, `video/` 6.7 MB, `css/` 5.1 MB.

It is two different things mixed together:

| Kind | Example | Goes to |
|---|---|---|
| **Design assets** — part of the site's look | logos, mission icons, backgrounds, preloader | `resources/images/`, through Vite (hashed, cache-forever). Only files a ported view references. |
| **Content media** — uploaded through the admin, path stored in the DB | project, news, initiative, team, cover photos | the `media` disk outside `public/`, served at the **same URLs** |

### Procedure

1. **Inventory.** Every file path that any DB column references (`images`,
   `news_images`, `initiatives_images`, `covers`, `profile`, `hope_projects_media`,
   donor logos …) plus every literal path in a live legacy view or CSS file.
2. **Diff against disk.** Referenced and present → keep. Present but not
   referenced → orphan. Referenced but missing → broken link (report; this is a
   bug that exists today).
3. **Copy, never move.** The legacy folder is not touched (§1). The new app's
   `media` disk is filled by copying **only referenced files** from it. Orphans
   are simply not copied — including every image of every project deleted so far
   (finding 3: deleting a project never removed its files) — so nothing has to be
   deleted to get rid of them.
4. **Nothing executable crosses over.** The copy takes images, PDFs and videos
   whose bytes match their extension (the `SecureUpload` checks, run in bulk), so
   `php*.tmp`, `desktop.ini`, `_gsdata_/*.log` and anything planted alongside the
   June web shell stay behind. Mismatches are reported, not copied.
5. **Optimize without visible change.** Keep originals. Generate WebP/AVIF
   derivatives at visually lossless quality, serve them with `<picture>` and the
   original as fallback, and check them with the visual diff (§7).
6. **Before cutover** the copy is re-run as a delta (new uploads since the last
   run), so the new app starts with exactly what the live site shows.
7. **On staging** the new app reads media from its own copy; URLs are the same
   paths as today, so stored paths in the DB work unchanged.

---

## 7. Verification: proving "nothing changed"

Saying the port matches is not enough. Each page is measured.

| Check | Tool | Pass when |
|---|---|---|
| Pixels | Playwright screenshots of legacy vs new at 375, 768, 1440 and 1920 px; hover/open states scripted | diff under a small threshold, every difference explained |
| Computed styles | script comparing `getComputedStyle` of matched elements | key elements identical |
| Text | rendered DOM text of both pages, normalized, diffed | identical (except approved fixes) |
| Data | `scripts/verify-queries.php` (exists) | identical result sets |
| Links | crawl both, compare every `href`/`src` | same targets, no new 404s |
| Behaviour | Pest feature tests + Livewire tests | green |
| Console/CSP | Playwright collects console errors and CSP violations | none |
| Performance | Lighthouse | not worse than legacy on any metric |

The harness is built once in Phase 1 and reused for every page.

### Running it

`npm run parity -- --paths=/projects/micro-projects` (or no `--paths` to check
every route the ledger marks `ported`). It writes `storage/parity/report.json`
and, per page and width, `legacy.png`, `new.png` and `diff.png`. Approved
differences live in `docs/parity-allowlist.json`, each with its reason; any
other difference fails.

Two more tools sit beside it:

- `npm run parity:nav -- --path=/some/page` compares the navigation's
  interactive states (menus opened by hover and click, an item hovered, the
  phone menu) between the two pages.
- `node scripts/style-spec.mjs --url=… --selector=…` records what a legacy
  region renders — box, computed styles, **and the authored value that won
  the cascade with the stylesheet and media query it came from** — at four
  widths. Porting from that, not from the 125 stylesheets, is what keeps the
  numbers right (`4.1vw`, not `59.04px`).

Method notes learned the hard way: measure a menu's *final* state after its
animation (legacy slides over 650ms); compare icon baselines by measurement
(Chrome snaps glyph baselines to whole pixels); a flaky pass or fail on a
legacy page that loads Google Fonts is usually the font arriving late — rerun.

**Compare against the legacy code on the same database, not production.** The
local `hcsyria_db` is a snapshot, so production content differs. The reference
is a throwaway clone — the legacy repository itself is never touched:

```bash
git clone "E:/03-Website/hcsyria.org Website/hcsyria.org-website" legacy-ref
cd legacy-ref && composer install --no-dev --no-scripts
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
# .env: APP_KEY=, the same DB_* as the new app, and DB_SECOND_HOST=127.0.0.1
#       so nothing ever connects to the remote studyzone server
php artisan key:generate --force
# public/images: a junction to the legacy public/images folder (read-only use)
php artisan serve --port=8124
```

First result (2026-10-09), `/projects/micro-projects` at 1440 px: 12% of pixels
differ, the new page is 2,725 px tall against 4,023 px, 157 text lines and 52
links differ — the Phase 0 port lacks the hero slider, Project Brief and
Statistics sections and the new "Our News" link. Confirms that page needs a
full port.

---

## 8. "No dead code, no duplicates" — enforced, not hoped for

| What | How it is kept out |
|---|---|
| Unreachable PHP | Only methods in the reachability manifest get ported (exists). |
| Legacy variants | `_old`, `_new`, `test`, `2`, `copy` views: port only the one a live route renders. 45 of 263 legacy views have such names. |
| Unused CSS | `@utility` only — Tailwind emits nothing unused. No raw CSS blocks outside `@utility`/`@theme`. |
| Unused JS / npm packages | `knip` in CI. |
| Unused Composer packages | `composer-unused` in CI. |
| Unused Blade components | CI script: every file in `components/` must be referenced. |
| Unused lang keys | CI script: every key in `lang/en/*.php` must be used. |
| Unused images/fonts | Vite only bundles what is imported. A CI script checks `resources/images` and `resources/fonts`. |
| Duplicated markup | Repeated markup goes into a component. Header, footer, card and dropdown are already components. |
| Duplicated queries | Every read goes through `app/Queries`, which is where duplicates collapse (e.g. 25 call sites → `CategoryQuery`). |

**Drift protocol** (because the legacy app keeps changing):

- Each ported page records the legacy commit it was ported from, in
  `docs/port-ledger.json`: route → legacy files it depends on → commit.
- `scripts/legacy-drift.php` lists, for every ported page, the legacy commits
  since then that touched its files. It reads the legacy repository and writes
  nothing to it. It runs weekly and before cutover.
- What happens when it finds something depends on the policy chosen in §11.

### Clean-up in this repo, now

- `scripts/analyze-controllers.php` and `scripts/inventory-queries.php` default to
  the stale legacy path. Point them at the current repo.
- `resources/js/bootstrap.js` exists only to load axios, which nothing uses.
  Remove both.
- `tests/*/ExampleTest.php` and `database/database.sqlite` are scaffolding.
- `APP_NAME=Laravel` in `.env.example`.
- `kitloong/laravel-migrations-generator` is only needed for re-capture; remove it
  once the schema is final.

---

## 9. Phases

Effort is rough, assuming one full-time developer, and it is the part of this
plan most likely to move.

### Phase 1 — Re-baseline and foundation (≈2–3 weeks)

1. Re-run the reachability analysis and query inventory against the current
   legacy repo. Regenerate the manifest.
2. Bring `ProjectSearch` and the micro-projects page up to the current legacy
   behaviour (§2.1); re-verify.
3. Start `docs/port-ledger.json` and `scripts/legacy-drift.php`.
4. Upgrade to Laravel 13 on PHP 8.4 (platform pinned to 8.4.21); Pest,
   Larastan, Pint, CI.
5. Port Tracking's security middleware, rate limiters and headers; set up the
   `app_` tables in `hcsyria_db` and narrow the migration guard (§5.4).
6. Teach the reachability analyzer to check that the view a method returns
   exists, so dead routes like `/about` are flagged rather than ported.
7. Fonts to `.woff2`, design tokens (done — §4.4). Icon glyphs become inline SVG as each page is ported.
8. Build the verification harness (§7). Run it on the micro-projects page first.

**Exit:** micro-projects passes every check in §7 against today's legacy page.

### Phase 2 — Public site (≈6–8 weeks)

`PublicController` has 46 live methods. Port in this order — chrome first, then
by dependencies, with pages that change rarely in legacy ahead of busy ones (the
port ledger shows which; a frozen page gets no live fixes until cutover, §11):

1. Layout chrome: header (including night mode, "our news" link), footer
   (dynamic), newsletter block, 404/500 pages.
2. Home (stats need a decision on the `get_statistics` procedure, see
   `docs/schema-audit.md`), who we are, what we do, who we serve.
3. Programme hubs: DISC, Study Zone, School to School, Family to Family,
   Children, Youth, LEAP, Roots of Hope, micro-projects (done in Phase 1).
4. News (the `NewsPage` grid), initiatives, successes, article pages.
5. Donate, contact, team pages.
6. The micro-projects login and locked online section.

Each page is done when it meets the Definition of Done below. Pages are
accepted on the staging address; nothing goes live before cutover.

#### Error pages

The legacy site had one error page that worked. `new/errors/404.blade.php` is
a designed page (a giant 404, a message, a "Back to Homepage" button, inside
the normal navbar and footer). Its 500 twin crashed on an undefined
`$edition`, so visitors to a failing page got Symfony's bare "Oops! An Error
Occurred"; every other status (401, 403, 419, 429, 503 and the rest) showed
Laravel's or Symfony's plain default.

The new site gives **every status the one legacy design**:

| Status | Heading | Notes |
|---|---|---|
| 401, 403, 404, 419, 429, 500 | per status, in the legacy 404's voice | 404 and 500 use the legacy wording unchanged |
| 503 | "We’ll be back soon!" | the legacy maintenance page (`public/indexM.php`), now inside the site chrome |
| any other 4xx / 5xx | generic 4xx or 5xx wording | the real status shows in the large type |

Rules: an error page must render with **nothing working behind it** — no
database, no session — because a 500 is often caused by exactly that
(`tests/Feature/ErrorPagesTest.php` proves it with an unreachable database);
it keeps the security headers; JSON requests still get JSON; the status is
never rendered inside a `try` that could fail. Wording lives in
`lang/en/errors.php`, one view per status in `resources/views/errors/`,
one component (`x-site.error-page`) for the design.

Decisions for you: the wording for 401, 403, 419, 429 and the generic pages
is new (written in the 404's voice) because the legacy site had none — edit
`lang/en/errors.php` if you want different words.

#### Shared building blocks (built with the home page)

Each page is a thin composition of these, so the next pages reuse them and the
CSS does not grow with each page. Page-specific CSS goes in
`resources/css/pages/`; anything two pages need goes in `ui/` or `site/`.

| Piece | Where | Replaces (legacy) | Used by |
|---|---|---|---|
| `x-site.preloader` + `preloader.js` | `site/preloader.css` | `.preloader` markup + a jQuery script pushed from the navbar | 19 pages |
| `x-ui.reveal` + `reveal.js` | `ui/reveal.css` | AOS (`aos.js`, `aos.css`, `data-aos` ×609) | all pages |
| `x-ui.rule-heading` | `ui/rule-heading.css` | `.date` | home, programme pages |
| `x-site.program-card` | `ui/program-card.css` | `.panel > .item` | home, programme lists |
| header `overlayUntil` | `site/navbar.css` | `.navbar_reverse` + scroll script | home (+ pages with a hero) |
| `.page-margin` | `site/page.css` | `.page-margin` (17 pages) | most pages |
| `RecordPageView` action | `app/Actions` | 13 copies of the same 8 lines in `PublicController` | every counted page |
| `Device::isPhone()` | `app/Support` | `jenssegers/agent` | pages with a phone-specific image |
| `ServeMedia` | route `/images`, `/files`, `/video` | files inside `public/` | everywhere |

#### Images are not in the repository

(Decided 2026-10-09.) The earlier idea of moving design assets into
`resources/images` is dropped: **no image, video or file is committed.** They
live on the server and are served at the same URLs from `MEDIA_ROOT`
(outside `public/`) by `ServeMedia`, which refuses everything but the file
types the site publishes and never lets a request leave its folder. For local
work, point `MEDIA_ROOT` at a folder of your own (git-ignored); the home
cover's four photos are kept in `storage/app/media`.

Side effect worth knowing: `LEAP.jpg` (18 MB) and `matters.mp4` (15 MB) are
downloaded in full by the home page today. Resizing them is a content change
for you to decide, not something this port does.

#### Static pages are not Livewire components

Revised from §4.1. A Livewire component is for a page with state (the
micro-projects filters, news load-more, search). The home page uses one only
because it needs request data (device, session) at mount; pure content pages
will be Blade views through `Route::view`.

### Phase 3 — Admin dashboard, redesigned (≈9–11 weeks)

Unlike the public site, the admin is **redesigned**, using the `impeccable`
skill. What is preserved is capability, not appearance.

1. **Inventory.** List every admin screen and action in the legacy dashboard
   (from the manifest: create, edit, publish, hide/unhide, English-check, image
   order, cover order, user and role management, logs …) with who may do it.
   This list is the contract — nothing on it may be lost.
2. **Audit the legacy admin with `impeccable`.** Errors, confusing flows,
   missing feedback, accessibility, empty and error states, mobile use. The
   findings feed the design.
3. **Design system for the admin** with `impeccable`: tokens (sharing the
   brand red with the public site), layout shell, table, form, filters, state
   tabs (published / pending / hidden), image uploader and orderer, modals,
   toasts, empty states. Built as `components/admin/*`.
4. Fortify auth with 2FA for every admin account, passkeys, lockout alerts —
   a new login screen in the same design.
5. Policies reproducing the legacy role and place rules exactly.
6. Screens per content type — news, initiatives (five programmes), projects,
   successes, images, covers, team, roots, content, users, logs — each a
   Livewire component over Actions, including the hidden-state transitions.
   DataTables become server-side Livewire tables.
7. The upload pipeline (§5.3) and the rich-text editor replacement.
8. `impeccable` audit pass on the finished admin before it goes live.

Staging shares the live `hcsyria_db`, so an admin save on staging is a real
edit. Admin writes are therefore proven by Pest against a scratch database
rebuilt from the migrations (`scripts/verify-schema.php` already does that
rebuild); on staging, staff accept the admin in the final week with real
edits they intend to make anyway.

### Phase 4 — Media (≈1–2 weeks, can overlap Phase 3)

The procedure in §6: copy referenced, verified files into the new app's media
disk, outside its web root.

### Phase 5 — Cutover (≈1–2 weeks)

1. **Fresh deployment.** The new app goes onto a clean directory — not on top of
   the old site's files, which may still hold whatever came with the June web
   shell.
2. **New secrets.** The new app gets its own `APP_KEY`, its own database user,
   and fresh mail and YouTube API credentials. Nothing the old server held is
   reused.
3. Final media delta copy (§6) and a last `legacy-drift.php` run.
4. Full verification run (§7) on staging, every page.
5. **Switch the domain** to the new app. The old site is left exactly as it is,
   just no longer reachable at hcsyria.org.
6. Watch 404s and errors for one release cycle.

### Phase 6 — Optional, after cutover

With no legacy app reading `hcsyria_db`, the fixed-schema rule lifts: merge
`*_english` into the base tables (already proven lossless), drop the French
tables, move to `spatie/laravel-permission`, fix the PascalCase columns, add
primary keys to `info_disc` and `youthprofiles`, set a retention policy on
`logs` (524k rows) and `student_entries` (404k rows).

### Definition of Done — every page

Both halves:

- [ ] In the reachability manifest; legacy commit recorded in the port ledger
- [ ] All reads via `app/Queries`, writes via `app/Actions`, verified against legacy
- [ ] No CDN, no inline event handlers, no CSP violations, no console errors
- [ ] Pest feature test; Larastan and Pint clean
- [ ] No new file, key, component or asset that is not used

Public pages, additionally:

- [ ] Visual, style, text and link diffs against legacy pass (§7)
- [ ] Lighthouse not worse than legacy

Admin screens, additionally:

- [ ] Every action from the legacy inventory for this screen works, for the same roles
- [ ] Policy tests: each role allowed and refused as in legacy
- [ ] `impeccable` audit passes; keyboard and screen-reader usable

---

## 10. Decisions

### Settled 2026-10-09

| # | Question | Decision |
|---|---|---|
| 1 | `/about` | Dead route (500, no view). Not ported; 301 to `/who-we-are`. |
| 2 | Admin dashboard | Full redesign with the `impeccable` skill; capabilities preserved (Phase 3). |
| 3 | PHP | 8.4.21 in production. Laravel 13 + Pest 5 both run on it; platform pinned. |
| 4 | Fonts | Lato 400/700/900 on every page (including /home and /projects/disc, which never loaded it), Archivo 400 for news. Martel is never drawn; Avenir is requested but never loaded (§4.4). |
| 5 | Database | Same `hcsyria_db`; new tables only, `app_` prefix, legacy tables never altered (§5.4). |
| — | The old website | Not touched: no code, config or file changes. Single cutover; holes close then (§1). |

### Still open

| # | Question | Recommendation |
|---|---|---|
| 6 | How legacy changes are handled during the rewrite | Option B, per-page freeze — full comparison in §11. |
| 7 | Hosting: where does the staging address live, who controls DNS for the switch, is Cloudflare in front? (`php.ini` suggests SureSupport shared hosting.) | Needed before Phase 2 staging. |
| 8 | The 15 other databases and the remote `studyzone_db` — in scope? | Out of scope; integrate through the query layer. |

---

## 11. Legacy changes during the rewrite — comparison

This plan makes no change to the old site (§1). Whoever maintains it may still
change it, though, and the new app has to keep up with whatever they do. This
section compares the policies they could follow; the only work it creates in
*this* project is mirroring.

### What the numbers say

Since the stale clone (2026-05-04): 141 commits excluding merges.

| | |
|---|---:|
| May–June 2026 | 125 commits (~62/month) |
| July–October 2026 | 16 commits (~4/month) |
| Bug fixes (`FIX:`) / features (`ADD:`) | 96 / 29 |
| Touched public views or CSS | 74 |
| Touched admin views | 60 |
| Touched controllers | 65 |
| Touched the database | 2 |
| By one developer | 129 of 141 |

Two things follow. The pace now is low, but it was fifteen times higher two
months ago and can be again. And most legacy changes are fixes to things the
rewrite is about to replace anyway.

### The three options

- **A — Full freeze now.** From an agreed date, nobody changes the old site.
  Everything waits for the new app.
- **B — Per-page freeze (recommended).** A page or admin screen freezes in legacy
  the day its port is accepted on staging. Pages not yet ported keep changing in
  legacy as normal — the port simply takes their latest version. New features
  are built only in the new app and arrive at cutover.
- **C — No freeze, mirror everything.** Legacy keeps changing freely; every
  change to an already-ported page is ported again.

### Side by side

| | A — Full freeze | B — Per-page freeze | C — Mirror everything |
|---|---|---|---|
| Cost of a legacy change | None (nothing changes) | None for unported pages; ported pages don't change | Each change done twice, plus the parity check re-run for that page |
| Estimated mirror effort | 0 | ~0 (only urgent fixes to frozen pages) | ~2–4 days/month at today's pace; weeks/month at May–June pace |
| Drift between old and new | None | None on ported pages; unported pages drift harmlessly | Constant; the drift script becomes a to-do list |
| Public parity check (§7) | Stable baseline | Stable once a page is accepted | Baseline moves; a page "done" last month can fail today |
| Bug fixes reach users | Only at cutover | Immediately on unported pages; on ported pages, at cutover | Immediately everywhere, but slower to make |
| New features | Wait, or go into the new app | Go into the new app — which is where they should end up | Built in legacy, then rebuilt |
| Admin (being redesigned) | Staff wait months for any admin fix | Unported admin screens keep getting fixes; each fix adds to the capability inventory | Legacy admin changes must also be redesigned |
| Time to cutover | Shortest | Short | Longest — every legacy change pushes it out |
| If the rewrite stalls | Worst: the site is frozen and nothing improves | Moderate: ported pages are frozen, unported ones kept evolving | Best: legacy never stopped |
| Developer knowledge | One developer holds most legacy knowledge; must move to the new app at once | That developer ports the pages they know, one at a time | Split between two codebases indefinitely |
| Discipline needed | Low | A clear list of what is frozen (`docs/port-ledger.json` is that list) | High; nothing is ever finished |

### Recommendation

**B.** It costs almost nothing extra, it keeps the site improving while the
rewrite runs, and the port ledger plus `scripts/legacy-drift.php` (§8) already
give the frozen list and the alarm when someone edits a frozen file.

The trade-off B carries with a single cutover: a ported page gets no fixes on
the live site until cutover. That is why Phase 2 ports pages in order of how
rarely they change, and why the time between a page's acceptance and cutover
should stay short. If anyone does change a frozen page, `legacy-drift.php`
flags it and the new app mirrors the change the same week.
