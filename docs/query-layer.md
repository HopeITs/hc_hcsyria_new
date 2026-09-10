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
| `ProjectSearch` | `App\Livewire\MicroProjects::loadProjects()` | 384 input combinations |

```bash
php scripts/verify-queries.php
```

This runs the **live legacy query** and the new query class side by side against
the real database and compares the results. Currently exact.

Extract only what a live route reaches — see `docs/controller-port-manifest.md`.
`ProjectSearch` was first written against `PublicController::get_projects_IDs()`,
which turned out to be dead code: its only caller was commented out of the
routes, and the live micro-project search is the Livewire component. The two
queries differ, so it has been rewritten against the live one.

## What the extraction fixed

**Escaped LIKE wildcards.** The live search binds its values through Eloquent,
but it does not escape LIKE metacharacters, so a user searching for `50%` was
injecting a wildcard. Verified: `%` now returns 0 results rather than matching
all 2,901 projects.

Worth being precise about the injection risk, because the two versions differ.
The *dead* `get_projects_IDs()` built SQL by concatenation behind `addslashes()`,
which is not MySQL's supported escaping mechanism. The *live* Livewire query
does not — it uses the query builder, so its values were already bound. Binding
is preserved here; it was not a defect being fixed.

**A stable sort order.** 220 publish dates are shared by more than one project,
the largest group being 13. The legacy query ordered only by `MPE_PublishDate`,
leaving tied rows in whatever order MySQL produced. That listing loads more rows
by raising a limit on the same query, so ties could shift between loads and show
a project twice or not at all. `ProjectSearch` adds `id` as a tiebreaker, and
the verification asserts that the first 20 of a 50-row fetch equal a 20-row
fetch exactly.

**Language branching removed.** The legacy code switched content tables at
runtime — a `$langTable` parameter threaded through the controller methods (36
references in `PublicController` alone) and a `$this->language` property in the
Livewire component. With French dropped there is one content table, so the
`content` relation is fixed and the branching is gone.

## Deliberate behaviour differences

Recorded so they are not mistaken for bugs:

- The legacy search put both conditions inside one `whereHas` on the content
  relation, even though `MPE_BeneficiaryName` lives on `projects`. MySQL
  resolved that column against the outer query, so it worked by accident. It is
  written out explicitly now; the result is identical because every project has
  exactly one content row.

## Still to extract

`StudyController` holds 61 raw queries, the largest remaining concentration,
including the reporting queries over `student_entries` (404,426 rows). Several
are genuine multi-table reports that should stay as SQL inside a dedicated query
class rather than being forced into Eloquent.

`PublicController::homepage()` calls the `get_statistics` stored procedure,
which reads three other databases by hardcoded name and returns `NULL`s when
they are missing. That needs a decision about the wider database landscape
before it can be extracted — see `docs/schema-audit.md`.
