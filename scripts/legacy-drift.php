<?php

/*
 * Lists legacy changes that a ported page has not caught up with.
 *
 *   php scripts/legacy-drift.php
 *
 * For every route in docs/port-ledger.json with status "ported", it asks git
 * which legacy commits since `ported_from` touched that page's legacy files
 * (controller, views, layouts, includes, Livewire classes) or the CSS/JS it
 * loads. Each one is a change the new page may need to mirror.
 *
 * Read-only: it runs `git log` in the legacy repository and nothing else.
 * Exits 1 when there is drift, so it can gate a release.
 */

$ledger = json_decode(file_get_contents(__DIR__.'/../docs/port-ledger.json'), true);
$legacy = $ledger['legacy_path'];

$drifted = 0;
$checked = 0;

foreach ($ledger['routes'] as $uri => $route) {
    if ($route['status'] !== 'ported') {
        continue;
    }
    $checked++;

    if (empty($route['ported_from'])) {
        printf("%-45s ported, but ported_from is not recorded\n", $uri);
        $drifted++;

        continue;
    }

    $paths = [...$route['legacy_files'], ...array_map(fn ($a) => "public/$a", $route['assets'])];
    $command = sprintf(
        'git -C %s log --oneline --no-merges %s..HEAD -- %s',
        escapeshellarg($legacy),
        escapeshellarg($route['ported_from']),
        implode(' ', array_map('escapeshellarg', $paths))
    );
    $log = trim((string) shell_exec($command));

    if ($log === '') {
        continue;
    }

    $drifted++;
    echo "$uri  (ported from {$route['ported_from']})\n";
    foreach (explode("\n", $log) as $line) {
        echo "    $line\n";
    }
}

printf("\n%d ported pages checked, %d with legacy changes to mirror\n", $checked, $drifted);
exit($drifted > 0 ? 1 : 0);
