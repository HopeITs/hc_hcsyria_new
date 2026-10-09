<?php

/*
 * Generates Eloquent models for app/Models from the canonical schema.
 *
 *   php scripts/generate-models.php [--force]
 *
 * Relationships are derived from the database's own foreign keys rather than
 * from the legacy models: the schema has 51 enforced FKs, while 34 of the 61
 * legacy models declared no relationships at all. Casts, $fillable and
 * $timestamps come from column metadata.
 *
 * Existing files are left alone unless --force is passed, so hand-written
 * scopes and accessors added after generation are never clobbered.
 */

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$force = in_array('--force', $argv, true);
$outDir = __DIR__.'/../app/Models';
$sep = chr(92);
$schema = DB::connection()->getDatabaseName();

/*
 * Framework-owned tables get no generated model. `users` is excluded too:
 * App\Models\User is hand-written because it must extend Authenticatable,
 * which this generator does not emit. --force must not clobber it.
 */
$skip = [
    'migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
    'sessions', 'password_reset_tokens', 'personal_access_tokens', 'users',
];

/*
 * French is no longer a supported language. The tables and columns stay in the
 * database untouched — the legacy application still reads them — but the new
 * application gets no French models and never lists a French column as
 * fillable, so nothing here can read or write French content.
 */
$frenchTables = [
    'news_french', 'initiatives_french', 'projects_french',
    'successes_french', 'testimonies_french',
];

$frenchColumns = [
    'french_translation', 'french_name', 'french_description',
    'name_french', 'position_french', 'MPE_French', 'translation_paid',
];

/*
 * The study-zone student and visit-entry tracking is retired
 * (docs/port-exclusions.json). Its tables stay in the database, untouched, but
 * get no model and no relation, so nothing in the new app can reach them.
 */
$retiredTables = ['students', 'student_entries'];

/* Tables with no model: no relation may point at them either. */
$noModelTables = array_merge($frenchTables, $retiredTables);

$skip = array_merge($skip, $noModelTables);

/*
 * The *_english tables are not translations — they hold the only copy of the
 * text content, while the base tables hold metadata and relationships. They are
 * modelled as a one-to-one `content` relation rather than merged, so this
 * schema stays identical to the live database.
 */
$contentTables = [
    'news' => 'news_english',
    'initiatives' => 'initiatives_english',
    'projects' => 'projects_english',
    'successes' => 'successes_english',
    'testimonies' => 'testimonies_english',
];

/*
 * Table names in this schema mix conventions (`educationdegree`,
 * `studyzonecenters`, `sub_what_categories`), so the ones a generic
 * singulariser cannot word-break correctly are named explicitly.
 * `backgrounds => Cover` preserves the domain term the application uses.
 */
$classMap = [
    'backgrounds' => 'Cover',
    'category' => 'Category',
    'childrenspecializations' => 'ChildrenSpecialization',
    'disc_course_type' => 'DiscCourseType',
    'educationdegree' => 'EducationDegree',
    'educationfield' => 'EducationField',
    'educationlevel' => 'EducationLevel',
    'familypartners' => 'FamilyPartner',
    'family_statis_type' => 'FamilyStatisType',
    'hope_projects' => 'HopeProject',
    'hope_projects_media' => 'HopeProjectMedia',
    'info_disc' => 'InfoDisc',
    'initiatives_donors' => 'InitiativeDonor',
    'initiatives_images' => 'InitiativeImage',
    'initiatives_images_rehabilitation' => 'InitiativeImageRehabilitation',
    'newsletter_subscribers' => 'Subscriber',
    'projects_donors' => 'ProjectDonor',
    'projecttype' => 'ProjectType',
    'rootsofhope' => 'RootsOfHope',
    'searchtype' => 'SearchType',
    'street' => 'Street',
    'studyzonecenters' => 'StudyzoneCenter',
    'studyzone_types' => 'StudyzoneType',
    'studyzone_types_description' => 'StudyzoneTypeDescription',
    'subcategory' => 'ProjectSubCategory',
    'users_centers' => 'UserCenter',
    'users_permissions' => 'UserPermission',
    'users_places' => 'UserPlace',
    'users_roles' => 'UserRole',
    'youthprofiles' => 'YouthProfile',

    // Content tables. Named for what they hold rather than for the language
    // in the table name, which is now the only language.
    'news_english' => 'NewsContent',
    'initiatives_english' => 'InitiativeContent',
    'projects_english' => 'ProjectContent',
    'successes_english' => 'SuccessContent',
    'testimonies_english' => 'TestimonyContent',
];

$classFor = fn (string $t): string => $classMap[$t] ?? Str::studly(Str::singular($t));

$tables = collect(DB::select('SHOW TABLES'))
    ->map(fn ($r) => array_values((array) $r)[0])
    ->reject(fn ($t) => in_array($t, $skip, true))
    ->values();

/* Column metadata, keyed by table. */
$columns = [];
foreach (DB::select(
    'SELECT TABLE_NAME t, COLUMN_NAME c, DATA_TYPE d, COLUMN_TYPE ct, IS_NULLABLE n, COLUMN_KEY k, EXTRA e
     FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
    [$schema]
) as $r) {
    $columns[$r->t][] = $r;
}

/* Foreign keys, both directions. */
$belongsTo = [];
$hasMany = [];
foreach (DB::select(
    'SELECT TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
    [$schema]
) as $r) {
    $belongsTo[$r->t][] = $r;
    $hasMany[$r->rt][] = $r;
}

/* PHP cast for a column, or null to leave it untouched. */
$castFor = function ($col) {
    if (in_array($col->c, ['created_at', 'updated_at', 'deleted_at'], true)) {
        return null;
    }
    if (str_starts_with($col->ct, 'tinyint(1)')) {
        return 'boolean';
    }

    return match ($col->d) {
        'int', 'smallint', 'mediumint', 'bigint', 'year' => 'integer',
        'decimal' => 'decimal:2',
        'float', 'double' => 'float',
        'date' => 'date',
        'datetime', 'timestamp' => 'datetime',
        'json' => 'array',
        default => null,
    };
};

$written = $skipped = 0;
$report = [];

foreach ($tables as $table) {
    $class = $classFor($table);
    $path = $outDir.'/'.$class.'.php';

    if (file_exists($path) && ! $force) {
        $skipped++;
        $report[] = sprintf('  %-34s %-38s SKIPPED (exists)', $table, $class);

        continue;
    }

    $cols = $columns[$table] ?? [];
    $names = array_column($cols, 'c');
    $pkCols = array_values(array_filter($cols, fn ($c) => $c->k === 'PRI'));
    $pk = count($pkCols) === 1 ? $pkCols[0]->c : null;
    $autoInc = $pk !== null && str_contains($pkCols[0]->e, 'auto_increment');
    $hasStamp = in_array('created_at', $names, true) && in_array('updated_at', $names, true);

    /* Everything except the key and the timestamps is mass-assignable. The
       legacy models declared almost no $fillable, which is why 12 controller
       sites could pass $request->all() straight through. */
    $fillable = array_values(array_diff(
        $names,
        array_filter([$pk, 'created_at', 'updated_at']),
        $frenchColumns
    ));

    $casts = [];
    foreach ($cols as $c) {
        if ($c->c === $pk || in_array($c->c, $frenchColumns, true)) {
            continue;
        }
        if ($cast = $castFor($c)) {
            $casts[$c->c] = $cast;
        }
    }

    $uses = ['use Illuminate\Database\Eloquent\Model;'];
    $body = [];

    if ($table !== Str::snake(Str::pluralStudly($class))) {
        $body[] = "    protected \$table = '$table';";
    }
    if ($pk !== null && $pk !== 'id') {
        $body[] = "    protected \$primaryKey = '$pk';";
    }
    if ($pk !== null && ! $autoInc) {
        $body[] = '    public $incrementing = false;';
    }
    if ($pk === null) {
        $body[] = '    // This table has no primary key in the schema; Eloquent writes';
        $body[] = '    // and route-model binding will not work until one is added.';
        $body[] = '    protected $primaryKey = null;';
        $body[] = '    public $incrementing = false;';
    }
    if (! $hasStamp) {
        $body[] = '    public $timestamps = false;';
    }

    $fillList = implode('', array_map(fn ($f) => "\n        '$f',", $fillable));
    $lead = $body === [] ? '' : "\n";
    $body[] = "$lead    protected \$fillable = [$fillList\n    ];";

    if ($casts) {
        $castList = '';
        foreach ($casts as $k => $v) {
            $castList .= "\n            '$k' => '$v',";
        }
        $body[] = "\n    protected function casts(): array\n    {\n        return [$castList\n        ];\n    }";
    }

    /* belongsTo, from this table's own foreign keys. */
    $usedNames = [];
    foreach ($belongsTo[$table] ?? [] as $fk) {
        if (in_array($fk->rt, $noModelTables, true)) {
            continue;
        }
        $target = $classFor($fk->rt);
        $name = Str::camel(Str::singular(preg_replace('/_id$/', '', $fk->c)));
        if ($name === '' || in_array($name, $usedNames, true)) {
            $name = Str::camel($target);
        }
        if (in_array($name, $usedNames, true)) {
            $name = Str::camel($fk->c);
        }
        $usedNames[] = $name;
        $body[] = "\n    /** @return BelongsTo<$target, \$this> */\n    public function $name(): BelongsTo\n    {\n"
                ."        return \$this->belongsTo($target::class, '{$fk->c}', '{$fk->rc}');\n    }";
        $uses[] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
    }

    /* hasMany, from foreign keys pointing at this table. */
    foreach ($hasMany[$table] ?? [] as $fk) {
        if (in_array($fk->t, $noModelTables, true)) {
            continue; // no model exists to relate to (French or retired)
        }

        $child = $classFor($fk->t);

        // The *_english table is one row per parent, not many.
        if (($contentTables[$table] ?? null) === $fk->t) {
            $body[] = "\n    /** @return HasOne<$child, \$this> */\n    public function content(): HasOne\n    {\n"
                    ."        return \$this->hasOne($child::class, '{$fk->c}', '{$fk->rc}');\n    }";
            $uses[] = 'use Illuminate\Database\Eloquent\Relations\HasOne;';
            $usedNames[] = 'content';

            continue;
        }

        $name = Str::camel(Str::plural($child));
        if (in_array($name, $usedNames, true)) {
            $name = Str::camel(Str::plural($child).Str::studly($fk->c));
        }
        $usedNames[] = $name;
        $body[] = "\n    /** @return HasMany<$child, \$this> */\n    public function $name(): HasMany\n    {\n"
                ."        return \$this->hasMany($child::class, '{$fk->c}', '{$fk->rc}');\n    }";
        $uses[] = 'use Illuminate\Database\Eloquent\Relations\HasMany;';
    }

    $uses = array_values(array_unique($uses));
    sort($uses);

    $php = "<?php\n\nnamespace App".$sep."Models;\n\n"
         .implode("\n", $uses)."\n\n"
         ."class $class extends Model\n{\n"
         .implode("\n", $body)."\n}\n";

    file_put_contents($path, $php);
    $written++;
    $report[] = sprintf('  %-34s %-38s %d fillable, %d rel', $table, $class,
        count($fillable), count($belongsTo[$table] ?? []) + count($hasMany[$table] ?? []));
}

foreach ($report as $line) {
    echo $line."\n";
}
printf("\n%d written, %d skipped, %d tables total\n", $written, $skipped, $tables->count());

/* Format with the project's Pint rules, so a regeneration never leaves style drift behind. */
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../vendor/bin/pint').' '.escapeshellarg(__DIR__.'/../app/Models').' --quiet');
