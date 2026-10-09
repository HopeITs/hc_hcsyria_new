# Security findings in the legacy application

Found while extracting the query layer. These affect the **live site**, not only
the rewrite, so they are listed separately from the migration notes.

**Decision 2026-10-09: the old site is not changed.** The patches suggested
below are recorded for whoever maintains it, but they are not part of this
project. The new app does not have these defects, and they close at cutover
(`docs/upgrade-plan.md` §1).

## 1. SQL injection on four public routes — confirmed exploitable

**Severity: high. Unauthenticated, on the public site.**

Four `PublicController` methods resolve a URL slug to a record by splicing the
URL segment straight into raw SQL:

```php
// PublicController.php:854, 912, 947, 1519
WhatCategory::whereRaw('replace(lower(name)," ","-") LIKE "' . $type . '"')->first();
```

There is no binding and no escaping — not even `addslashes`. The value arrives
from the URL:

| Route | Method | Line |
|---|---|---:|
| `/projects/{type}` | `initiatives_by_type` | 854 |
| `/projects/disc/{type}/{id}-{slug}` | `initiativeOfProject` | 912 |
| `/projects/study-zone/{type}` | `oneTypeOfproject` | 947 |
| `/projects/disc/{place}` | `discByPlace` | 1519 |

Verified against the real database, read-only:

```
legit  slug "school-to-school"            -> School to School
control slug "nothing-matches"            -> (none)
hostile slug 'nothing-matches" OR "1"="1' -> Social activities   <-- should be (none)
```

A slug that matches nothing returns a row, so the attacker controls the `WHERE`
clause. That is enough for boolean-based extraction, and the injection point sits
inside a double-quoted string with no filtering, so `UNION` is reachable too.

**The new application binds these** — `CategoryQuery::bySlug()` and
`PartnerQuery::placeBySlug()`, both asserted in `scripts/verify-queries.php`.

**The legacy application is still serving these routes.** The rewrite will take
months; this should not wait for it. The minimal patch, applied to all four
sites, is to bind the value:

```php
// before
->whereRaw('replace(lower(name)," ","-") LIKE "' . $type . '"')
// after
->whereRaw('replace(lower(name), " ", "-") LIKE ?', [$type])
```

## 2. `/about` is a broken route with a latent former-staff bug

**Severity: low. Corrected 2026-10-09 — an earlier version of this note said
the page publishes 81 former staff. It does not: it has never rendered.**

`/about` returns `view('public.about')`, and that view does not exist in the
legacy repository and never has (`git log --all` finds no such file). The live
site answers `https://hcsyria.org/about` with a 500, confirmed 2026-10-09.
Nothing links to it; the team page is `/who-we-are`, served by `about_us()`,
which filters correctly with `whereIn`.

The query inside `about()` is still wrong, and would have exposed former staff
had the view existed. It builds its filter as:

```php
DB::table('profile')->where('inactive', '0')
    ->where('belong_to', 'homs')
    ->orWhere('belong_to', 'study')
    ->orWhere('belong_to', 'disc')
```

The `orWhere` calls are not grouped, so they escape the `inactive` filter.
`inactive` is not a boolean despite the name — it stores a departure date such
as `31/03/2023`.

Measured on live data: the query returns **111 profiles where 30 are current
staff** — 81 former staff of the study and DISC teams, rows that also carry
`email` and `phone`.

Not ported: `/about` is dead (no view, no inbound link). The rewrite answers it
with a 301 to `/who-we-are`. `TeamMemberQuery` groups the alternatives, so the
bug cannot come back through the query layer either.

The lesson for the reachability analyzer: a route that reaches a method is not
enough — the view the method returns has to exist too.

## 3. Deleting a project leaves every image file on disk

**Severity: medium. Storage leak, and deleted content stays retrievable.**

`projectsController::delete()` deletes the `projects` row first, then queries
`images` for the file paths to unlink. But `images.project_id` is
`ON DELETE CASCADE`, so those rows are already gone — the query returns nothing
and the unlink loop never runs.

Even if it did, it calls `File::delete($path, $thumbnail)`, and that method takes
a single argument, so the thumbnail was ignored regardless.

Net effect: **no image file is ever removed**. Every image of every deleted
project remains on disk and reachable by direct URL.

`App\Actions\DeleteProject` collects the paths before deleting, lets the
database cascade do its job, wraps the whole thing in a transaction, and removes
both the image and its thumbnail. It also reports the
`successes.project_id` `ON DELETE RESTRICT` case as a clear message rather than
a raw database error.

## 4. `Auth::routes()` publishes a broken registration flow

**Severity: low-to-medium, depending on what those URLs expose.**

`routes/web.php:508` calls `Auth::routes()` with no arguments, publishing
`/register`, `/password/reset` and the rest. `RegisterController` cannot serve
them: it declares neither `create()` nor `validator()` for the `RegistersUsers`
trait, references `Admin` and `Trainee` models that do not exist, and its
constructor requires `guest:admin` and `guest:trainee` guards that are not
configured.

The practical result is 500s on public URLs rather than an open registration
hole, but it should be closed deliberately —
`Auth::routes(['register' => false])` at minimum.

## 5. A remote database password is committed to git

`config/database.php` in the legacy repository hardcodes the `mysql_second`
password as an `env()` fallback default, and that file is tracked. The
credential should be rotated and the fallback removed.

The new project takes no default: `env('DB_LEGACY_PASSWORD', '')`.

## Not a finding

Worth recording so it is not re-raised: the **live** micro-project search in
`app/Livewire/MicroProjects.php` binds its values through the query builder and
is not injectable. The `addslashes`-based string building sits in
`PublicController::get_projects_IDs()`, which is dead code — its only caller was
commented out of the routes.
