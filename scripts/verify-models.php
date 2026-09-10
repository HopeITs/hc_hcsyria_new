<?php

/*
 * Boots every model in app/Models and checks it against the live schema:
 * the table exists, every $fillable column exists, every cast column exists,
 * and every relationship method builds a runnable query.
 *
 *   php scripts/verify-models.php
 *
 * Exits non-zero if anything fails.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

$failures = [];
$stats    = ['models' => 0, 'relations' => 0, 'columns' => 0];

foreach (glob(__DIR__ . '/../app/Models/*.php') as $file) {
    $class = 'App\\Models\\' . basename($file, '.php');

    if (!class_exists($class)) {
        $failures[] = "$class: class does not autoload";
        continue;
    }

    $model = new $class();
    if (!$model instanceof Model) {
        $failures[] = "$class: not an Eloquent model";
        continue;
    }

    $stats['models']++;
    $table = $model->getTable();

    if (!Schema::hasTable($table)) {
        $failures[] = "$class: table '$table' does not exist";
        continue;
    }

    $actual = Schema::getColumnListing($table);

    foreach ($model->getFillable() as $column) {
        $stats['columns']++;
        if (!in_array($column, $actual, true)) {
            $failures[] = "$class: \$fillable column '$column' missing from '$table'";
        }
    }

    foreach (array_keys($model->getCasts()) as $column) {
        if ($column === $model->getKeyName()) continue;
        if (!in_array($column, $actual, true)) {
            $failures[] = "$class: cast column '$column' missing from '$table'";
        }
    }

    // Every public no-arg method with a relation return type must resolve.
    $reflection = new ReflectionClass($model);
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->class !== $class || $method->getNumberOfParameters() > 0) continue;

        $type = $method->getReturnType();
        if (!$type instanceof ReflectionNamedType || !is_subclass_of($type->getName(), Relation::class)) {
            continue;
        }

        $stats['relations']++;
        try {
            $relation = $model->{$method->getName()}();
            $related  = $relation->getRelated();

            if (!Schema::hasTable($related->getTable())) {
                $failures[] = "$class::{$method->getName()}(): related table '{$related->getTable()}' missing";
                continue;
            }
            // Force query compilation so bad keys surface here, not in production.
            $relation->toBase()->toSql();
        } catch (Throwable $e) {
            $failures[] = "$class::{$method->getName()}(): " . $e->getMessage();
        }
    }
}

printf(
    "%d models, %d relationships, %d fillable columns checked\n",
    $stats['models'], $stats['relations'], $stats['columns']
);

if ($failures === []) {
    echo "ALL CHECKS PASSED\n";
    exit(0);
}

printf("\n%d FAILURE(S):\n", count($failures));
foreach ($failures as $f) echo "  $f\n";
exit(1);
