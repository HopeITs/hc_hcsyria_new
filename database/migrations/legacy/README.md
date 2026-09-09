# Legacy schema capture (`hcsyria_db`)

Phase 0 deliverable: the legacy database schema, reverse-engineered into
version-controlled migrations. Before this, ~40 Eloquent models were backed by
only 5 stock Laravel migrations — the schema existed **only** on a running
MySQL server and could not be rebuilt from the repository.

Source: `hcsyria_db` on MariaDB 10.4.28, captured 2026-09-09 from the live
Laravel 11 app at `E:/03-Website/hcsyria_website`.
Generated with `kitloong/laravel-migrations-generator` v7.4.

## What is here

| | Count |
|---|---|
| `*_create_*_table.php` | 69 |
| `*_add_foreign_keys_to_*.php` | 36 (61 foreign keys) |
| `*_proc.php` (stored procedures) | 2 |
| **Total** | **107** |

## How to run

These are **not** picked up by a bare `php artisan migrate` — they live in a
subdirectory on purpose (see "Open decisions"). Run them explicitly:

```bash
php artisan migrate --path=database/migrations/legacy
```

## Verification

Rebuilt into an empty database and diffed against the source, comparing every
table, column (type, nullability, default, extra) and foreign key:

| | Source | Rebuilt | Result |
|---|---|---|---|
| Tables | 69 | 69 | match |
| Columns | 472 | 472 | 3 deliberate deviations, below |
| Foreign keys | 61 | 61 | exact match |

Reproduce with `scripts/schema-compare` (see `docs/schema-audit.md`).

## Deliberate deviations from the source

Three, all recorded rather than silently accepted:

1. **`info_disc` and `projects_donors`: MyISAM → InnoDB.** MyISAM supports
   neither transactions nor foreign keys. Both tables are tiny (3 and 28 rows).
   Kept as InnoDB; revert with `$table->engine('MyISAM')` if something depends
   on the old behaviour.
2. **`family_statistics.statis_num`, `hope_statistics.stat1_num`,
   `hope_statistics.stat2_num`: `float` → `double`.** Laravel's `$table->float()`
   emits `double` on MariaDB. Wider type, no data loss.
3. **Stored-procedure `DEFINER` clauses stripped.** The source pinned both
   procedures to `` `hcsyria`@`%` ``, which fails on any host lacking that user.
   They now inherit the connecting user.

## Defects found in the source schema (NOT fixed here)

Captured faithfully so the diff stays honest. These need decisions:

- **`info_disc` and `youthprofiles` have no primary key.**
- **The `get_statistics` procedure queries three other databases**
  (`hcsyria_disc_a`, `hcsyria_disc_h`, `hcsyria_disc_d`) by hardcoded name.
  It has a blanket `EXIT HANDLER FOR SQLEXCEPTION` that returns `NULL`s, so
  when those databases are missing it fails silently rather than erroring —
  the public statistics simply render blank.
- **`hcsyria_db` is one of ~16 databases on this server.** See
  `docs/schema-audit.md`.

## Open decisions (Phase 1)

- **`users` table collision.** Both this capture and Laravel 12's stock
  `0001_01_01_000000_create_users_table.php` create `users`. Running both paths
  into one database will fail. The legacy table is close to stock but uses an
  `int` primary key and adds an `active` column; the stock migration also brings
  `password_reset_tokens` and `sessions`, which the legacy database lacks
  entirely.
- **Whether this becomes the new app's canonical schema.** It is held in a
  subdirectory precisely because that is undecided — in particular the
  `*_english` / `*_french` table split (`news`/`news_english`/`news_french`,
  and the same for `projects`, `initiatives`, `successes`, `testimonies`)
  is a candidate for consolidation into a translations model.
