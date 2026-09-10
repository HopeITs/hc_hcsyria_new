# Schema and the French removal

## The rule

**The schema in `database/migrations` mirrors the live `hcsyria_db` exactly.
French is removed in application code only — never in the database.**

The migration plan is strangler-fig: the new application and the legacy one run
side by side against the *same* database while routes move across one at a time.
The moment this schema diverges from the live one, the legacy application breaks.
So the database is treated as fixed, and every simplification happens in code.

## Schema capture

`database/migrations` holds 107 migrations reverse-engineered from the live
database (69 tables, 61 foreign keys, 2 stored procedures), plus three framework
migrations for tables the legacy database never had (`sessions`,
`password_reset_tokens`, `cache`, `jobs`).

Verified by rebuilding into an empty database and diffing every table, column
and foreign key against the source:

| | Live | Rebuilt |
|---|---:|---:|
| Tables | 69 | 69 |
| Columns | 472 | 472 |
| Foreign keys | 61 | **61 exact** |

```bash
php artisan migrate                                      # into a dev database
php scripts/schema-compare.php hcsyria_db hcsyria_new_db
```

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

## Protection against writing to production

`AppServiceProvider` refuses to run `migrate`, `migrate:*`, `db:wipe` or
`schema:*` when the target database is one of the twelve live databases. It
hooks `CommandStarting`, which fires before Laravel creates its `migrations`
table, with a `MigrationsStarted` listener behind it for in-process calls.

This is not theoretical. `migrate:fresh` against `hcsyria_db` would drop every
table in production; it is now refused.

## Open decision

The legacy `users` table uses an `int` primary key and adds an `active` column,
unlike Laravel's stock `bigint`. It is kept as-is because six tables hold
foreign keys against `users.id`. `App\Models\User` is hand-written for this
reason and is skipped by the generator.
