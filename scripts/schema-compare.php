<?php

/*
 * Verifies that database/migrations/legacy rebuilds the legacy schema exactly.
 *
 *   php artisan migrate --path=database/migrations/legacy   (into a scratch DB)
 *   php scripts/schema-compare.php [source_db] [rebuilt_db]
 *
 * Compares every table, column (type/nullability/default/extra) and foreign key.
 * Reads DB_LEGACY_* from .env. Read-only.
 */
$root = dirname(__DIR__);
$env = [];
foreach (file($root.'/.env') as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$src = $argv[1] ?? 'hcsyria_db';
$dst = $argv[2] ?? 'hcsyria_verify_db';

$pdo = new PDO(
    "mysql:host={$env['DB_LEGACY_HOST']};port={$env['DB_LEGACY_PORT']}",
    $env['DB_LEGACY_USERNAME'],
    $env['DB_LEGACY_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$fetch = function (string $db, string $sql) use ($pdo): array {
    $st = $pdo->prepare($sql);
    $st->execute([$db]);

    return array_map(fn ($r) => implode('|', array_map('strval', $r)), $st->fetchAll(PDO::FETCH_NUM));
};

/*
 * Framework tables exist only in the new application, never in the legacy
 * database. They are additive, so they are excluded from the diff rather than
 * reported as drift.
 */
$framework = "'migrations','cache','cache_locks','jobs','job_batches','failed_jobs',
              'sessions','password_reset_tokens','personal_access_tokens'";

$queries = [
    'TABLES' => "SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA=? AND TABLE_TYPE='BASE TABLE' AND TABLE_NAME NOT IN ($framework)
                 ORDER BY TABLE_NAME",
    'COLUMNS' => "SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COALESCE(COLUMN_DEFAULT,'~'),EXTRA
                  FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME NOT IN ($framework)
                  ORDER BY TABLE_NAME,COLUMN_NAME",
    'FKS' => 'SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
              FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL
              ORDER BY TABLE_NAME,COLUMN_NAME',
];

$exit = 0;
foreach ($queries as $label => $sql) {
    $a = $fetch($src, $sql);
    $b = $fetch($dst, $sql);
    $missing = array_diff($a, $b);
    $extra = array_diff($b, $a);
    printf("%-8s source=%-5d rebuilt=%-5d missing=%-3d extra=%d\n",
        $label, count($a), count($b), count($missing), count($extra));
    foreach ($missing as $m) {
        echo "   MISSING  $m\n";
    }
    foreach ($extra as $m) {
        echo "   EXTRA    $m\n";
    }
    if ($missing || $extra) {
        $exit = 1;
    }
}
exit($exit);
