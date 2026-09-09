# Schema audit — Phase 0

Audit of the legacy system's data layer, 2026-09-09. Companion to
`database/migrations/legacy/README.md`, which covers the capture itself.

## Starting position

The legacy app (`E:/03-Website/hcsyria_website`, Laravel 11) had **~40 Eloquent
models and 5 migrations** — and all 5 were stock Laravel scaffolding. None of
the 69 real tables were described anywhere in the repository. The database could
not be recreated from source, and no schema change had a reviewable diff.

That is now fixed for `hcsyria_db`. It is not yet fixed for anything else.

## The schema is better than the application layer

Worth stating plainly, because it changes the migration risk profile: the
database is in good shape. 67 of 69 tables are InnoDB, and there are **61 real
foreign keys** — referential integrity is enforced at the database level, not
merely assumed by application code. Relationships can be trusted when porting.

## Finding: this is 16 databases, not one

`hcsyria_db` is the public website only. The same server hosts:

| Database | Tables | |
|---|---:|---|
| `hcsyria_db` | 69 | **captured** — the Laravel website |
| `hcsyria_micro_h` | 110 | |
| `hcsyria_micro_w` | 110 | |
| `hcsyria_micro_d` | 109 | |
| `hcsyria_f2f_ajudy` | 102 | |
| `hcsyria_micro` | 92 | |
| `hcsyria_disc_a` | 58 | |
| `hcsyria_financial_test` | 57 | |
| `hcsyria_financial` | 52 | |
| `hcsyria_development_test` | 30 | |
| `hcsyria_development`, `hcsyria_disc_d`, `hcsyria_disc_h`, `hcsyria_f2f_ajudy_test`, `hcsyria_f2f_aleppo`, `hcsyria_micro_l` | 0 | empty |

Roughly **790 tables of business data outside the captured schema**, in what
look like separate systems (DISC training, family-to-family, micro-projects,
financial) with per-branch instances (`_a` / `_h` / `_d` / `_w`) and paired
`_test` copies.

Open question for scoping: are these in the migration, or do they stay put and
get integrated? The answer materially changes the size of this project.

## Finding: business logic lives in MySQL

Two stored procedures were found and captured:

- **`get_statistics`** — builds the public statistics figures by querying
  `hcsyria_disc_a`, `hcsyria_disc_h` and `hcsyria_disc_d` directly, by hardcoded
  database name. Two of those three are currently empty. It carries a blanket
  `DECLARE EXIT HANDLER FOR SQLEXCEPTION` that returns `NULL` for every figure,
  so a missing database produces **blank statistics rather than an error** — a
  failure mode nobody would see in logs.
- **`clear_dublicates_entries`** — data cleanup over `student_entries`
  (404,426 rows).

Both were pinned to a `` `hcsyria`@`%` `` definer that does not exist on every
host. Stripped during capture.

No triggers and no views exist — checked, not assumed.

## Finding: the translation split is in the schema, not just the models

The `*English` / `*French` model duplication is backed by real tables:

| Base | English | French |
|---|---:|---:|
| `news` (75) | `news_english` (75) | `news_french` (50) |
| `projects` (2,954) | `projects_english` (2,726) | `projects_french` (901) |
| `initiatives` (384) | `initiatives_english` (356) | `initiatives_french` (170) |
| `successes` (271) | `successes_english` (271) | `successes_french` (129) |
| `testimonies` (0) | `testimonies_english` (0) | `testimonies_french` (0) |

The row counts show the real cost: French coverage runs 30–47% and English is
incomplete too, so **every read path needs a fallback**, and each is hand-written
today. Consolidating this is the single highest-leverage Phase 1 decision.

## Defects recorded, not fixed

- `info_disc` and `youthprofiles` have **no primary key**.
- `info_disc` and `projects_donors` were **MyISAM** — no transactions, no FK
  enforcement. Rebuilt as InnoDB (see the capture README).
- `logs` holds **524,383 rows** and `student_entries` **404,426**, neither
  partitioned or archived. Worth a retention policy before they are carried over.
- `Subscriber` and `Subscribers` both exist as models — likely one is dead.
- Mixed naming conventions throughout: `projecttype` / `projects_donors` /
  `sub_what_categories`, and `projects` uses `PascalCase` columns
  (`MPE_City`, `SubCategory_ID`) while everything else uses `snake_case`.

## Security note

`config/database.php` in the **legacy** repo hardcodes a remote database
password as an `env()` fallback default, and that file is committed to git.
The credential should be rotated and the fallback removed. The new project
takes no default: `env('DB_LEGACY_PASSWORD', '')`.

## Reproducing this

```bash
php artisan migrate --path=database/migrations/legacy   # into a scratch database
php scripts/schema-compare.php hcsyria_db hcsyria_verify_db
```

`hcsyria_verify_db` is a throwaway database used for verification. It holds no
data and can be dropped and rebuilt at any time.
