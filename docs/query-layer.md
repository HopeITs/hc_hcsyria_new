# The query layer

## Why it exists

The legacy application has **182 raw SQL calls** inside 35 controllers — 100
`DB::table`, 82 `DB::select` — concentrated in `StudyController` (61) and
`PublicController` (53). Most are built by string concatenation.

In a TALL rewrite, controllers do not survive: Livewire components replace them.
But those 182 queries cannot move straight into components, or the mud moves
with them. So query logic is extracted first, into its own layer, and Livewire
components consume it.

```
legacy controller  ->  app/Queries/*  ->  Livewire component
   (deleted)            (the real work)     (thin, UI only)
```

## The rule about models

**Models in `app/Models` are generated artifacts. Do not hand-edit them.**
`scripts/generate-models.php --force` rewrites them all, so a scope added to a
model would be silently lost the next time the schema changes.

Query logic therefore lives in `app/Queries`, never in model scopes. The single
exception is `App\Models\User`, which is hand-written because it must extend
`Authenticatable`; the generator skips it.

## Shape of a query class

Each class is a small fluent builder over one question the application asks:

```php
$ids = ProjectSearch::make()
    ->search($request->input('search'))
    ->forDonor($donorId)
    ->inCity($cityId)
    ->onlyWithVideo($video)
    ->ids();
```

- `make()` — constructor, no arguments
- filter methods — accept `null`/`''`/`'all'` and treat them as "no filter",
  because that is how the request data actually arrives
- `builder()` — returns the Eloquent builder, so callers can paginate or extend
- `ids()`/`get()` — terminal methods for the common cases

`builder()` matters for Livewire: components need `->paginate()`, which a class
that only returns arrays cannot give them.

## What is done

| Class | Replaces | Verified |
|---|---|---|
| `ProjectSearch` | `PublicController::get_projects_IDs()` | 384 input combinations |
| `InitiativeSearch` | `PublicController::get_initiatives_IDs()` | 9 search terms |

```bash
php scripts/verify-queries.php
```

This runs the **original legacy SQL** and the new query class side by side
against the real database and compares the results. Both are currently exact.

## What the extraction fixed

**Bound parameters.** The legacy search interpolated the user's term into SQL
with only `addslashes()` in front of it — not MySQL's supported escaping
mechanism. Every value is now bound.

**Escaped LIKE wildcards.** A user searching for `50%` previously injected a
wildcard into the query. Verified: `%` now returns 0 results rather than
matching all 2,901 projects.

**A stable sort order.** 220 publish dates are shared by more than one project,
the largest group being 13. The legacy query ordered only by
`MPE_PublishDate`, leaving tied rows in whatever order MySQL produced — two
semantically identical queries were observed returning ties in different orders.
Under pagination that means a project can appear on two pages or on none.
`ProjectSearch` adds `id` as a tiebreaker. `InitiativeSearch` adds an order
where the legacy query had none at all.

**Language branching removed.** The legacy methods threaded a `$langTable`
parameter through every call — 36 references in `PublicController` alone — to
switch between `projects_english` and `projects_french`. With French dropped
there is one content table, and the parameter is gone.

## Deliberate behaviour differences

Recorded so they are not mistaken for bugs:

- `LOWER(column) LIKE ...` became a plain `LIKE`. The columns collate as
  `utf8mb4_unicode_ci`, which is already case-insensitive, so results are
  identical — but the column is no longer wrapped in a function, which leaves
  MySQL free to use an index if one is added later.
- `InitiativeSearch` preserves a legacy quirk on purpose: `organization` is
  matched as `'% term %'`, with spaces, while `title`, `description` and
  `content` match as `'%term%'`. It is a crude word-boundary match, and
  "fixing" it would silently change which initiatives are found.

## Still to extract

`StudyController` holds 61 raw queries, the largest remaining concentration,
including the reporting queries over `student_entries` (404,426 rows). Several
are genuine multi-table reports that should stay as SQL inside a dedicated query
class rather than being forced into Eloquent.

`PublicController::homepage()` calls the `get_statistics` stored procedure,
which reads three other databases by hardcoded name and returns `NULL`s when
they are missing. That needs a decision about the wider database landscape
before it can be extracted — see `docs/schema-audit.md`.
