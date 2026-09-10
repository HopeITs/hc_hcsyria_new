<?php

/*
 * Inventories the query work inside the legacy controllers, restricted to the
 * methods that a live route can actually reach.
 *
 *   php scripts/inventory-queries.php [path-to-legacy-project]
 *
 * Reads docs/controller-manifest.json for the live method list, so it never
 * reports work for dead code. Prints, per controller, which live methods use
 * raw SQL and which tables they touch.
 */

$positional = array_values(array_filter(array_slice($argv, 1), fn($a) => ! str_starts_with($a, '--')));
$legacy = $positional[0] ?? 'E:/03-Website/hcsyria_website';
$controllerDir = $legacy . '/app/Http/Controllers';

$manifestPath = __DIR__ . '/../docs/controller-manifest.json';
if (! is_file($manifestPath)) {
    exit("run analyze-controllers.php --manifest=docs/controller-manifest.json first\n");
}
$manifest = json_decode(file_get_contents($manifestPath), true);

/* French controllers are reachable but obsolete; never report work for them. */
$frenchControllers = [
    'frenchController', 'InitiativesFrenchController', 'NewsFrenchController',
    'SuccessFrenchController', 'TestimoniesFrenchController',
];

/** Raw-SQL signals, in call order. */
const RAW_MARKERS = ['DB::table', 'DB::select', 'DB::statement', 'DB::raw', 'whereRaw', 'selectRaw', 'DB::unprepared'];

function significantTokens(string $php): array
{
    return array_values(array_filter(
        token_get_all($php),
        fn($t) => ! is_array($t) || ! in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)
    ));
}

/**
 * Splits a controller into methods and returns the source text of each,
 * comments already stripped, so raw-SQL counting ignores commented-out code.
 *
 * @return array<string, string>
 */
function methodBodies(string $file): array
{
    $tokens = significantTokens(file_get_contents($file));
    $bodies = [];
    $current = null;
    $depth = 0;
    $buffer = '';

    foreach ($tokens as $i => $token) {
        $text = is_array($token) ? $token[1] : $token;

        if (is_array($token) && $token[0] === T_FUNCTION
            && isset($tokens[$i + 1]) && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING) {
            if ($current !== null) $bodies[$current] = $buffer;
            $current = $tokens[$i + 1][1];
            $buffer = '';
            continue;
        }

        if ($current === null) continue;

        if ($token === '{') $depth++;
        if ($token === '}') {
            $depth--;
            if ($depth <= 0) {
                $bodies[$current] = $buffer;
                $current = null;
                $depth = 0;
                $buffer = '';
                continue;
            }
        }

        $buffer .= $text . ' ';
    }

    if ($current !== null) $bodies[$current] = $buffer;

    return $bodies;
}

/* ---- walk the live controllers ----------------------------------------- */

$report = [];
$totalRaw = $totalMethods = $methodsWithRaw = 0;
$tableHits = [];

foreach ($manifest['port'] as $controller => $info) {
    if (in_array($controller, $frenchControllers, true)) continue;

    $file = $controllerDir . '/' . $controller . '.php';
    if (! is_file($file)) {
        $file = $controllerDir . '/Auth/' . $controller . '.php';
        if (! is_file($file)) continue;
    }

    $bodies = methodBodies($file);
    $rows = [];

    foreach ($info['live'] as $method) {
        if (! isset($bodies[$method])) continue;
        $totalMethods++;

        $body = $bodies[$method];
        $counts = [];
        foreach (RAW_MARKERS as $marker) {
            $needle = str_replace('::', ' :: ', $marker);
            $n = substr_count($body, $marker) + substr_count($body, $needle);
            if ($n > 0) $counts[$marker] = $n;
        }

        $raw = array_sum($counts);
        if ($raw === 0) continue;

        $methodsWithRaw++;
        $totalRaw += $raw;

        // Which tables the raw SQL names, for grouping into query classes.
        preg_match_all('/\b(?:from|join|into|update|DB\s*::\s*table\s*\(\s*[\'"])\s*[\'"]?([a-z_]{3,})/i', $body, $m);
        $tables = array_values(array_unique(array_map('strtolower', $m[1])));
        foreach ($tables as $t) $tableHits[$t] = ($tableHits[$t] ?? 0) + 1;

        $rows[] = ['method' => $method, 'raw' => $raw, 'markers' => $counts, 'tables' => $tables];
    }

    if ($rows === []) continue;

    usort($rows, fn($a, $b) => $b['raw'] <=> $a['raw']);
    $report[$controller] = $rows;
}

uasort($report, fn($a, $b) => array_sum(array_column($b, 'raw')) <=> array_sum(array_column($a, 'raw')));

printf(
    "LIVE METHODS: %d   WITH RAW SQL: %d   RAW CALLS: %d\n",
    $totalMethods, $methodsWithRaw, $totalRaw
);
echo "(French controllers excluded - not being ported)\n\n";

foreach ($report as $controller => $rows) {
    printf("%s  (%d raw calls across %d live methods)\n",
        $controller, array_sum(array_column($rows, 'raw')), count($rows));
    foreach ($rows as $r) {
        printf("    %-34s %2d  %s\n",
            $r['method'], $r['raw'],
            $r['tables'] ? implode(', ', array_slice($r['tables'], 0, 6)) : '');
    }
    echo "\n";
}

arsort($tableHits);
echo "TABLES MOST OFTEN IN RAW SQL:\n";
foreach (array_slice($tableHits, 0, 15, true) as $table => $n) {
    printf("    %-32s %d method(s)\n", $table, $n);
}
