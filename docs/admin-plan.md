# hcsyria.org — admin dashboard plan

The staff dashboard under `/admin`, rebuilt on the TALL stack. This is the admin
companion of `docs/upgrade-plan.md` (which covers the public pages): what is
decided, what is built, how it is built, and what is left.

Status as of **2026-10-19**, `main` at `e9f6854`. 544 Pest tests, Larastan level 7
clean, Pint clean.

---

## 1. The rule for the admin

| | Public site | Admin dashboard |
|---|---|---|
| Look | Pixel parity with legacy | **Redesigned** in the Tracking app's design (white sidebar, red current item, Somar Sans, light and dark) |
| UX | Unchanged | Improved: errors and weak spots fixed |
| What must not change | Content, layout, behaviour | What staff can **do**: every legacy action still exists |
| Verified by | Visual/text/style diff against legacy | Pest tests of every action and every role, plus a look in the browser on real data |

The design is `docs/mockups/admin-dashboard.html` (a clickable mockup, real
figures from `hcsyria_db`, names and personal data withheld).

---

## 2. Sections

Legacy admin routes: about 290 (`routes/web.php`, `/admin/...`). Status of each
section:

| Section | Legacy routes | Status | Branch it was built on |
|---|---:|---|---|
| Shell, sign-in landing, Ctrl K, alerts, dark mode | — | **Done** | `admin-dashboard-and-micro-projects` |
| Dashboard `/admin/home` | 1 | **Done** (city breakdown, work queue; stat tiles dropped 2026-10-19) | same |
| Micro projects: list, show, create, edit, state moves, delete | 21 + 5 | **Done** | same |
| Micro projects: photos (upload, reorder, remove) | in the above | **Done** | `admin-micro-project-photos` |
| Initiatives: list, show, create, edit, state moves, delete | 35 | **Done** | `admin-initiatives` |
| Initiatives: logo, gallery, before-and-after pairs, testimonies | in the above | **Done** | `admin-initiative-images-and-testimonies` |
| Successes: list, show, create, edit, state moves, delete | 6 + the programme copies | **Done** | `admin-successes` |
| Newsletter: items (list, show, create, edit, state moves, delete, PDF) | 29 | **Done** | `admin-newsletter` |
| Newsletter: thumbnail, Important and Pinned pictures, status, gallery | in the above | **Done** | `admin-newsletter-pictures` |
| Newsletter: tags, editions, subscribers | in the above | **Done** | `admin-newsletter-tags-subscribers-editions` |
| Study Zone (initiatives, centres, students, successes) | 27 + 11 | **Next** | — |
| DISC | 25 | To do | — |
| School to School | 15 | To do | — |
| LEAP | 15 | To do | — |
| Children University | 15 | To do | — |
| Roots of Hope (editions of the magazine) | 8 | To do | — |
| Our Team | 7 | To do | — |
| Cover photos | 6 | To do | — |
| Page content (`content`) | 10 | To do | — |
| Users, roles, permissions, places | 5 + 3 | To do | — |
| Activity log and notifications (the bell) | 2 | To do | — |
| French translation screens | ~30 | **Not planned** (the French site is out of scope) | — |

Every branch is merged into `main`; `main` is pushed (2026-10-19).

### What a "done" section means

- list with tabs, filters, search and a selection for bulk moves
- show page with details, what it still needs, and its history
- create and edit in the same form
- every state move and delete, through one set of shared actions
- pictures and files where the legacy section had them
- a test for every role and every action, run against `hcsyria_db` inside a
  rolled-back transaction
- looked at in the browser on the real data

---

## 3. Roles and what they can do

Legacy roles (`roles`, held per person in `users_roles`) and page permissions
(`permissions`, `users_permissions`) are kept as they are. One table says who
may do what: `app/Enums/AdminCapability.php`. The sidebar, the buttons and the
server all ask it.

| Capability | Admin | Publisher | English Editor | English Checker | Photos Editor | Viewer |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| View (open the section) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create, edit text | ✓ | | ✓ | | | |
| Delete, take offline | ✓ | | | | | |
| Publish, hide | ✓ | ✓ | | | | |
| Mark English checked | ✓ | | | ✓ | | |
| Manage photos | ✓ | ✓ | | | ✓ | |
| See newsletter subscribers | ✓ | ✓ | | | | |
| Remove a subscriber | ✓ | | | | | |

(French Editor can only view; the French screens are not planned.)

- **Cities.** A project's city must be among the cities the account is limited to
  (`users_places`); an empty list or the place "All" means every city. Only micro
  projects belong to a city.
- **Two things differ from legacy** (needs sign-off, see section 8): Publisher and
  English Checker can open the content (legacy gave them only a home page), and
  publishing needs more than the English check for a micro project (a photo and a
  donor).
- Every action asks the policy again on the server; a hidden button is a
  convenience, not the protection.

---

## 4. Architecture

```
routes/admin.php            prefix /admin, middleware web + auth + admin.access
  └─ pages/admin/*          Livewire single-file pages: state and rendering only
       ├─ app/Queries/*     every read   (Admin*IndexQuery, Admin*Query, ContentLookups)
       ├─ app/Actions/*     every write  (Admin\Save*, Admin\Delete*, Admin\Manage*, ChangeContentState)
       ├─ app/Livewire/Forms/*      one form object per kind, for create and edit
       ├─ app/Livewire/Concerns/*   ManagesContent, SendsAlerts, ReceivesPictures
       ├─ app/Policies/*            ContentPolicy and one subclass per kind
       └─ components/admin/*        shared Blade and Livewire components
```

- **`ContentKind`** (`app/Enums/ContentKind.php`) is the one place that says how
  each kind of content is stored, named and logged: Project, Initiative, Success,
  News. The state changes, the actions menu, the policy and the alerts ask it
  instead of knowing about any table. A new kind is one new case.
- **`ContentTransition`**: publish, take offline, hide, unhide, English checked.
  A success story has no hidden state (`ContentKind::supports`).
- **State changes** are one action (`ChangeContentState`), used from every list,
  the dashboard queue and every item's page; bulk moves and Undo use the same.
- **Alerts** (toasts) are one system: the server sends an `admin-alert` event or
  flashes one for the next page; all wording is in `lang/en/admin.php`.
- **Files** go under `MEDIA_ROOT` through `PhotoFiles` (pictures), `DocumentFiles`
  (PDFs) and `PhotoFolders` (where each kind keeps its files). Every upload is
  checked by `SecureUpload` (JPEG, PNG, WebP by content, not by name) or
  `SecureDocument` (PDF), and every picture is **re-encoded** with GD, which also
  drops camera data and GPS position.
- **Activity log**: every write goes through `WriteActivityLog`, in legacy's
  categories (0 projects, 1 initiatives, 2 successes, 3 news, 4 editions,
  6 subscribers, 35 tags) and legacy's wording, so the Log page and the
  notifications keep working.
- **Legacy habits kept in one place** (`LegacyValue`): an empty field is stored as
  the text `"0"`; a text re-written by the rich-text box (other line endings) is
  not an edit.
- **Models in `app/Models` are generated and never hand-edited**; reads are
  query classes, not scopes.

### Shared components (`resources/views/components/admin`)

`icon`, `sprite`, `page-header`, `panel`, `stat-tile`, `status-pill`,
`empty-state`, `field`, `segmented`, `multi-check`, `form-section`, `popover`,
`rich-text`, `tabs`, `filter-bar`, `select-field`, `bulk-bar`, `pager`,
`history`, `content-actions`, `alerts`, `confirm`, `palette`, `news-nav`; and the
Livewire panels `content-photos`, `initiative-logo`, `before-after`,
`initiative-testimonies`, `news-pictures`.

One stylesheet (`resources/css/admin.css`) and one script (`resources/js/admin.js`)
serve every admin page; the public site does not load them.

---

## 5. What changed from legacy, on purpose

Beyond the look, these differ. Each is either a legacy defect fixed or a rule
made explicit.

**Everywhere**
- Files really are deleted when a record, a photo or a logo is: legacy asked
  `File::delete()` for paths like `/images/...` that do not exist on disk, so
  nothing was ever removed. Deleting a micro project, initiative, success or news
  item now removes all its files.
- Uploads are checked by their bytes and re-encoded; legacy trusted the file name
  and kept the original, including its GPS data.
- One upload becomes both file sizes (legacy had staff cut a small and a big copy).
- The photo order is one run of 1, 2, 3, ... (legacy left every new photo at 0).
- A refusal says why (not ready, duplicate, has success stories) instead of a raw
  database error.
- CKEditor 12 from a CDN is replaced by a small rich-text box; the text is cut to
  the few tags it makes (`SafeHtml`) on the way in.
- A migration of legacy bugs in the log wording: the family-members change was
  logged as "DOB Edited"; the sub-category and donor changes were never logged.

**Micro projects**
- Publishing needs the English text checked **and** a photo **and** a donor
  (legacy: only the English check).
- A project that is online goes back to the pending list when its photos change
  (kept from legacy).
- Tabs are filters; 371 online projects are also marked cancelled, so they appear
  in both, as in legacy.

**Initiatives** — category fixed once created (as legacy); Homework Room's
initiatives are excluded; testimonies exist only for DISC and Study Zone.

**Successes** — one form for all categories (legacy had three near copies); the
picture is cut to a 500 px square; a micro-project story links to a project by
search.

**Newsletter**
- One Important item online at a time (kept): publishing an Important item, or
  making an online item Important, makes the old one Regular and logs it.
- Saving a change to an online item takes it back to pending (kept from legacy).
- At least one tag is needed (kept); tags typed into the form are created, and a
  name differing only in capitals is the same tag.
- The attached PDF is stored under a safe name that cannot replace another file.
- Subscribers are personal data: only the Admin and the Publisher see the list,
  only the Admin removes an address, and the log never holds the address.

**Homework Room** is not part of the dashboard at all (decided 2026-10-19): not a
category of successes, initiatives or testimonies, and its stories and initiatives
are not listed, opened or changed.

---

## 6. How the work is done (the same loop for every section)

1. Read the legacy controller(s) and views for the section; list every action.
2. Read the real data (counts, states, the shapes of paths and sizes).
3. Branch off `main` with a descriptive name (never `phase-N`).
4. Extend `ContentKind` or write the section's queries, form object, save action,
   delete action and pages, reusing the shared pieces. **No section copies code that
   another already has**; if it would, the shared piece is generalised first.
5. Tests: every role, every action, every refusal, the legacy log text, the
   files on disk (a throwaway `MEDIA_ROOT`).
6. `vendor/bin/pint`, `vendor/bin/phpstan analyse`, full `vendor/bin/pest`.
7. Look at it in the browser on the real data (read-only), then commit.
8. Merge into `main` locally. **Push only when asked.**

Commands (PHP needs the GD extension; the local XAMPP has it commented out):

```bash
php -d extension=gd vendor/bin/pest          # the suite, with the picture tests
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint
npm run build                                # tests read the Vite manifest
```

### Cautions

- Feature tests read the **real `hcsyria_db`** inside a rolled-back transaction;
  they never use `RefreshDatabase`, and files go to a temporary folder.
- Looking at a page in the browser needs a signed-in account; use a temporary one
  and delete it afterwards. Do not save through the browser against `hcsyria_db`.
- `.env` must set `MEDIA_ROOT`; the server needs the **GD** extension for any
  picture upload (declare `ext-gd` in production, see section 8).

---

## 7. What is left, in order

1. **Study Zone** (largest: initiatives, centres, students, statistics, successes
   already done) then **DISC**, **School to School**, **Children University**,
   **LEAP**. Most reuse `ContentKind::Initiative`; each needs its own fields.
2. **Roots of Hope** (magazine editions) and **Our Team**.
3. **Cover photos** and **Page content**.
4. **Users, roles, permissions, places** (the Admin-only page; the roles matrix in
   section 3 becomes editable).
5. **Activity log page and the notification bell** (the bell polls the log every
   30 seconds in legacy and marks rows seen).
6. **Dashboard**: widgets for initiatives, successes and news in the work queue
   (today it follows micro projects).
7. **Newsletter**: sending (legacy `sendEmail`, `sendEmailAll`) — not built on
   purpose, it sends mail to real people.
8. **Not planned**: the French translation screens.
9. `impeccable` audit pass over the finished admin; accessibility check; Arabic
   direction if staff need it (the Tracking theme supports it, the admin does not
   yet).

---

## 8. Decisions and open questions

**Needs sign-off (changes what staff can do, or how)**
- Publisher and English Checker can open the content lists and pages.
- Publishing a micro project also needs a photo and a donor.
- Who sees newsletter subscribers (Admin and Publisher) and who removes one
  (Admin).
- Saving a change to an online newsletter item takes it back to pending (kept from
  legacy; say if it should stay).

**Open**
- Which role should edit tags, editions and testimonies: today the English Editor
  and the Admin (create and rename), the Admin (delete), the Publisher and the
  Admin (put an edition online).
- Whether Homework Room data should be hidden from the public site as well; this
  plan only keeps it out of the dashboard.
- Production needs `MEDIA_ROOT`, the GD extension and an upload limit that fits
  8 MB pictures (`upload_max_filesize`, `post_max_size`); add `ext-gd` to
  `composer.json` when the server is known.

**Decided**
- 2026-10-09: the admin is redesigned, with every legacy action kept.
- 2026-10-19: Tracking's design; no two-factor sign-in; sign-in lands on
  `/admin/home`; Homework Room is excluded; `main` pushed to GitLab.

---

## 9. Where things are

| What | Where |
|---|---|
| The design | `docs/mockups/admin-dashboard.html` |
| Routes | `routes/admin.php` |
| Pages | `resources/views/pages/admin/` |
| Components | `resources/views/components/admin/` |
| Wording of every alert | `lang/en/admin.php` |
| Capabilities and cities | `app/Enums/AdminCapability.php`, `app/Queries/AdminAccessQuery.php` |
| Kinds of content | `app/Enums/ContentKind.php` |
| Tests | `tests/Feature/Admin*Test.php`, fixtures in `tests/Support/admin.php` |
