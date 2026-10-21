# Schema and the French removal

## The rule

**The schema in `database/migrations` mirrors the live `hcsyria_db` exactly.
French is removed in application code only — never in the database.**

The migration plan is strangler-fig: the new application and the legacy one run
side by side against the *same* database while routes move across one at a time.
The moment this schema diverges from the live one, the legacy application breaks.
So the database is treated as fixed, and every simplification happens in code.

## The cutover migrations

`database/cutover/` holds the changes to apply **once, at cutover**, when the
legacy site is switched off. They are not in `database/migrations`, so the rule
above, `scripts/verify-schema.php` and the legacy site are unaffected until then.
The application code already uses the new names, so it runs only against a
database those migrations have been applied to.

1. `drop_unused_tables`: the students module (`students`, `student_entries`,
   `street`, `genders`, `religions`, `educationlevel/field/degree`), the five
   `*_french` tables and ten tables nothing reads. **Deletes data: back up first.**
2. `rename_tables`: 23 tables to plural snake_case (`profile` to `team_members`,
   `users_roles` to `role_user`, `news_english` to `news_contents` ...). Rows,
   keys and column names are kept.
3. `tidy_indexes`: unique keys on pairs that must not repeat (existing repeats
   are removed first), one-row-per-owner keys on the content tables, and keys on
   the columns the site filters and sorts by, including `logs`.

Try them on a copy, never on `hcsyria_db` itself (the guard below refuses):

```bash
mysqldump --routines hcsyria_db | mysql hcsyria_new_db
DB_DATABASE=hcsyria_new_db php artisan migrate --path=database/cutover
```

Run against a copy this way, the suite passes except the four `MigrationGuardTest`
cases, which expect the protected name `hcsyria_db`. Applying them to the real
database needs the guard lifted for that one run.

## One database, not several

The application points at `hcsyria_db` directly — the same database the legacy
application uses. There is no separate development database, and creating one
is not needed:

- **Session, cache and queue drivers are `file`/`file`/`sync`**, matching the
  legacy application. Laravel 12 defaults all three to `database`, which is the
  only thing that would require `sessions`, `cache` and `jobs` tables the shared
  database does not have. With file drivers the new app needs **no extra tables
  at all**.
- Because nothing is copied, there is no sync step and no drift between what
  the two applications see.

`database/migrations` therefore describes the schema; it is not run against
`hcsyria_db`. Its job is to make the schema reviewable in git and rebuildable
from scratch elsewhere.

## Schema capture

107 migrations reverse-engineered from the live database: 69 tables, 61 foreign
keys, 2 stored procedures.

Verified by rebuilding into a throwaway database and diffing every table, column
and foreign key against the source:

| | Live | Rebuilt |
|---|---:|---:|
| Tables | 69 | 69 |
| Columns | 472 | 472 |
| Foreign keys | 61 | **61 exact** |

```bash
php scripts/verify-schema.php   # creates a temp db, migrates, diffs, drops it
```

The temp database is removed on every exit path, including failure, so
verification never leaves databases behind.

Three deliberate deviations, all from the generator's type mapping rather than
from choice: `info_disc` and `projects_donors` rebuild as InnoDB instead of
MyISAM, and three `float` columns rebuild as `double`. Stored-procedure
`DEFINER` clauses are stripped so they are portable.

## How French is removed

French lives in 5 tables (1,431 rows) and 12 columns. **All of it stays in the
database.** The legacy application still reads it, and it is the only copy.

Removal happens at the model layer:

- **No French models are generated.** `scripts/generate-models.php` skips
  `news_french`, `initiatives_french`, `projects_french`, `successes_french`
  and `testimonies_french`, so no class can reach those tables.
- **No French column is fillable or cast.** `french_translation`,
  `french_name`, `french_description`, `name_french`, `position_french`,
  `MPE_French` and `translation_paid` are excluded, so the new code cannot read
  or write them through a model.

Verify with `grep -riE "french" app/Models/` — it returns nothing.

`english_check` is deliberately kept on `news`, `initiatives`, `projects` and
`successes`. Despite the name it is a publish-state flag, not a language flag,
and the legacy application reads it in 76 places.

## The `*_english` tables are content, not translations

Worth stating plainly because the names mislead. The base tables hold metadata
and relationships only — `news`, `initiatives`, `successes` and `testimonies`
have **no text columns at all**. Every title, description and body lives in the
`*_english` table. French was the only actual translation.

So with French gone there is one language, and `*_english` is simply "the
content". It is modelled as a one-to-one relation rather than merged into the
base table, because merging would change the schema:

```php
$news = News::with('content')->find($id);
$news->content->title;
```

The content models are named for their role — `NewsContent`,
`InitiativeContent`, `ProjectContent`, `SuccessContent`, `TestimonyContent` —
each with an explicit `$table` pointing at the underlying `*_english` table.

### Merging later, if the database is ever forked

If the new application eventually gets its own database, merging `*_english`
into the base tables is a clean win. It has already been proven lossless
against real data — 3,677 rows across all five pairs, with MD5 checksums of
every merged column matching, and zero orphans or duplicate keys.

That is a cutover-time step, not a step to bake into this schema now.

## Protection against destroying the shared database

Because the application points at `hcsyria_db`, a stray `php artisan migrate`
is a live risk rather than a hypothetical one — `migrate:fresh` would drop every
table in it.

`AppServiceProvider` refuses `migrate`, `migrate:*`, `db:wipe`, `db:seed` and
`schema:dump` whenever the target is one of the twelve live databases. It hooks
`CommandStarting`, which fires before Laravel creates its `migrations` table;
hooking `MigrationsStarted` instead is too late, and lets that table be created
in the target first.

The target is the connection named by `--database` when one is given, not
only the default: `db:wipe --database=legacy` with a scratch default would
otherwise have wiped `hcsyria_db` through the `legacy` connection.

**Laravel does not fire `CommandStarting` when `APP_ENV=testing`.** Until
2026-10-09 that meant `APP_ENV=testing php artisan migrate:fresh` — or any test
using `RefreshDatabase` — would run `db:wipe` against `hcsyria_db` before the
backstop below could fire. The provider now reroutes the command events itself
in every environment. `tests/Feature/MigrationGuardTest.php` covers the CLI,
`APP_ENV=testing`, in-process `Artisan::call` and `--database` paths, using only
commands that are harmless if the guard ever fails.

A second `MigrationsStarted` listener stays as a backstop for in-process
migrations. Note that this event carries only `$method` and `$options` —
it has no `$connection` property — so the connection is resolved from
`options['database']`, falling back to the default connection.

The guard is a code-level control. In production the new app should also
connect as a database user without `DROP`/`ALTER` on the legacy tables, so a
bug here cannot destroy data either.

`scripts/verify-schema.php` still works because it overrides `DB_DATABASE` for
its child process, so the guard sees a scratch database and allows it.

## Open decision

The legacy `users` table uses an `int` primary key and adds an `active` column,
unlike Laravel's stock `bigint`. It is kept as-is because six tables hold
foreign keys against `users.id`. `App\Models\User` is hand-written for this
reason and is skipped by the generator.
