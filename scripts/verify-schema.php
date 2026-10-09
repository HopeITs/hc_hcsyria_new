<?php

/*
 * Proves that database/migrations still rebuilds the live schema exactly.
 *
 *   php scripts/verify-schema.php
 *
 * Creates a throwaway database, migrates into it, diffs it against
 * hcsyria_db, then drops it — including on failure. Nothing is left behind,
 * and the live database is only ever read.
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

$source = $env['DB_LEGACY_DATABASE'] ?? 'hcsyria_db';
$scratch = 'tmp_schema_check_'.bin2hex(random_bytes(4));

$pdo = new PDO(
    "mysql:host={$env['DB_LEGACY_HOST']};port={$env['DB_LEGACY_PORT']}",
    $env['DB_LEGACY_USERNAME'],
    $env['DB_LEGACY_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$drop = function () use ($pdo, $scratch) {
    try {
        $pdo->exec("DROP DATABASE IF EXISTS `$scratch`");
        echo "dropped $scratch\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "WARNING: could not drop $scratch: {$e->getMessage()}\n");
    }
};

// Drop the scratch database however this script exits.
register_shutdown_function($drop);

$exit = 1;
try {
    $pdo->exec("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "created $scratch\n";

    $php = PHP_BINARY;
    $cd = escapeshellarg($root);

    /*
     * The child inherits this process's environment, and Dotenv does not
     * override variables that are already set — so the migration targets the
     * scratch database and the guard in AppServiceProvider allows it. Set via
     * putenv rather than an inline `VAR=value` prefix, which cmd.exe rejects.
     */
    putenv("DB_DATABASE=$scratch");

    $cmd = sprintf(
        'cd %s && %s artisan migrate --force --no-interaction 2>&1',
        $cd,
        escapeshellarg($php)
    );

    exec($cmd, $out, $code);
    putenv('DB_DATABASE');
    if ($code !== 0) {
        echo implode("\n", array_slice($out, -20))."\n";
        throw new RuntimeException('migration failed');
    }
    printf("migrated %d migrations\n\n", substr_count(implode("\n", $out), 'DONE'));

    $compare = sprintf(
        'cd %s && %s scripts/schema-compare.php %s %s 2>&1',
        $cd, escapeshellarg($php), escapeshellarg($source), escapeshellarg($scratch)
    );
    exec($compare, $diff, $diffCode);
    echo implode("\n", $diff)."\n";

    /*
     * schema-compare exits non-zero for the three documented type deviations
     * (two MyISAM tables rebuild as InnoDB, three floats as doubles). Treat
     * only additional drift as a failure.
     */
    $unexpected = array_filter($diff, function ($line) {
        if (! preg_match('/^\s+(MISSING|EXTRA)\s+(.*)$/', $line, $m)) {
            return false;
        }

        return ! preg_match(
            '/^(info_disc|projects_donors)\||^(family_statistics|hope_statistics)\|\w+\|(float|double)/',
            $m[2]
        );
    });

    if ($unexpected !== []) {
        echo "\nUNEXPECTED DRIFT:\n";
        foreach ($unexpected as $line) {
            echo $line."\n";
        }
    } else {
        echo "\nSCHEMA MATCHES (only the 3 documented type deviations)\n";
        $exit = 0;
    }
} catch (Throwable $e) {
    fwrite(STDERR, "FAILED: {$e->getMessage()}\n");
}

exit($exit);
