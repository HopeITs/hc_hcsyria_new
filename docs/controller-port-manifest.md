# What to port from the legacy controllers

Only code that a live route can actually reach gets rebuilt. This is the list.

Regenerate it with:

```bash
php scripts/analyze-controllers.php --manifest=docs/controller-manifest.json
```

The analyzer takes the route files as entry points and follows `$this->method()`
and `self::method()` calls inside each controller. It parses with PHP's
tokenizer rather than regex, because the legacy code has 41 commented-out
routes and large commented-out method bodies that a regex would count as live.

## Totals

| | Count |
|---|---:|
| Controllers | 34 |
| Methods declared | 344 |
| Reachable from a route | 311 |
| Dead — no route reaches them | 33 |
| Excluded by policy | 57 |
| **To port** | **254** |

Exclusions are declared in `docs/port-exclusions.json` and applied by the
analyzer, so they survive a regeneration rather than living in someone's head.

## Do not port: no reachable method

Five controllers cannot be reached from any route.

| Controller | Methods |
|---|---:|
| `SuccessController` | 11 |
| `InitiativeImagesRehabilitationController` | 4 |
| `StudentsController` | 2 |
| `MicroArController` | 1 |
| `WebcamController` | 0 |

`SuccessController` is the notable one: its behaviour was superseded by the
`success_*` methods now living inside `StudyController` and `DiscController`.

## Do not port: excluded by policy

Reachable, but deliberately not carried over.

### French — 5 controllers, 37 methods

`frenchController`, `InitiativesFrenchController`, `NewsFrenchController`,
`SuccessFrenchController`, `TestimoniesFrenchController`. Their routes go too.

### Student and visit-entry tracking — 15 methods

The study-zone student and visit-entry approach has been retired. From
`StudyController`: `studentsJSon`, `studentsEntriesJSon`, `students_create`,
`students_destroy`, `students_edit`, `students_entries`, `students_store`,
`students_update`, `students_visit`, `students_visit_create`,
`studentsentry_destroy`, plus the form helpers `filterEducation`,
`filterEducationDegree`, `checkNational` and `checkEmail`.

Fifteen routes under `/admin/student/*` and `/admin/studententry/*` retire with
them, along with `/admin/study/studentsjson` and `/admin/study/studentsentryjson`.

`StudyController::index` is a **mixed screen** and needs splitting rather than
dropping: it returns `new.admin.study.index` with study-zone initiatives
(`iniStudy`, `iniPending`, `sucstudy`, `sucpending`) *and* student data
(`students`, `students_count`, `studentsentry_count`, and fifteen per-centre
visit-count queries). Port the initiative half only.

That split removes all of that method's raw SQL: every one of its 18 raw calls
belongs to the student half.

The `/admin/study` and `/admin/study/successes` areas are unaffected, so
`StudyController` still ports — 27 methods instead of 42.

The `students` and `student_entries` tables stay in the database untouched, as
the French tables do. Nothing here changes the schema.

### Authentication — `RegisterController`, 5 methods

Routed by `Auth::routes()` but **cannot work**:

- it declares no `create()` or `validator()`, which the `RegistersUsers` trait
  requires
- it references `Admin` and `Trainee` models that do not exist anywhere
- its constructor calls `middleware('guest:admin')` and `middleware('guest:trainee')`
  for guards that are not defined in `config/auth.php`

Any request reaching it fails. `Auth::routes()` publishes `/register` and
`/password/reset` as live URLs, so this is worth closing off deliberately rather
than leaving to rot. Rebuild authentication on the current Laravel starter.

Separately, `/admin/student/index` is routed at `routes/web.php:293` to
`StudyController@students_index`, a method that does not exist — that URL
already returns a 500. It retires with the rest.

## Port: live controllers

Dead methods listed per controller are excluded.

| Controller | Live methods | Dead methods to drop |
|---|---:|---|
| `PublicController` | 46 | `get_event_copy`, `get_initiatives_IDs`, `get_project_copy2`, `get_projects_IDs`, `get_study_copy`, `micro_project_new`, `news_by_edition`, `news_by_what`, `news_by_who`, `search_list_beneficiaries`, `search_list_initiatives`, `search_list_projects`, `studyzone_all` |
| `StudyController` | 27 | `checkEmail`, `checkNational`, `filterEducation`, `filterEducationDegree`, `studentsEntriesJSon`, `studentsJSon`, `students_create`, `students_destroy`, `students_edit`, `students_entries`, `students_store`, `students_update`, `students_visit`, `students_visit_create`, `studentsentry_destroy` |
| `DiscController` | 23 | — |
| `NewsController` | 22 | — |
| `projectsController` | 16 | `show_projects` |
| `SchoolController` | 14 | — |
| `ChildrenController` | 13 | — |
| `LeapController` | 13 | — |
| `InitiativesController` | 11 | — |
| `ContentController` | 10 | — |
| `UserController` | 9 | — |
| `RootsController` | 8 | — |
| `TeamController` | 8 | — |
| `LogController` | 6 | — |
| `CoverController` | 5 | — |
| `InitiativeImagesController` | 5 | — |
| `NewsImagesController` | 5 | — |
| `TestimoniesController` | 5 | — |
| `LoginController` | 3 | — |
| `TrainingController` | 3 | `set_locale` |
| `HomeController` | 2 | — |

`ForgotPasswordController` and `ResetPasswordController` declare no methods of
their own — everything comes from framework traits — and are covered by the
authentication rebuild above.

## Why this matters more than it looks

`PublicController::get_projects_IDs()` is dead. Its only caller,
`micro_project_new()`, was commented out of the routes; the live route for
`/projects/micro-projects` points at `micro_project_new_test()`, which renders
the `MicroProjects` Livewire component. **The real micro-project search lives in
`app/Livewire/MicroProjects.php`, not in the controller.**

The two searches are not the same query. The dead one matched four columns plus
an exact project-number match; the live one matches two columns and excludes
empty video strings. Porting the controller method would have quietly changed
what users find.

The lesson generalises: for anything with a `_test`, `_new` or `_old` suffix,
check which one the route names before reading the code.

## Where the logic actually lives

Live business logic is spread across three places, not one:

- **controllers** — the 254 methods above
- **`app/Livewire/`** — `MicroProjects` (the micro-project search) and `Offline`
- **stored procedures** — `get_statistics`, called from `homepage()`

All three need to be read before a page is rebuilt.
