<?php

/*
 * Finds out which public pages of the live site actually work.
 *
 *   php scripts/probe-live-site.php [--base=https://hcsyria.org] [--max=250] [--delay-ms=500] [--seeds=file]
 *
 * --seeds names a file of extra paths, one per line, for pages the crawl cannot
 * reach because their links are built by JavaScript (team cards, news grid).
 *
 * Static analysis cannot see everything: /hope renders a view that exists and
 * still answers 500. So this crawls the live site from /home, following only
 * same-host links, and matches every URL it meets against the routes in
 * docs/port-ledger.json. For each route it records up to three real example
 * URLs with their HTTP status, plus an overall live_status.
 *
 * GET requests only, one at a time, with a pause between them. Redirects are
 * recorded, not followed blindly. Routes the crawl never reaches are requested
 * directly when they take no parameters.
 *
 * Writes `live_status` and `examples` into docs/port-ledger.json; nothing else.
 */

$options = getopt('', ['base::', 'max::', 'delay-ms::', 'seeds::']);
$base = rtrim($options['base'] ?? 'https://hcsyria.org', '/');
$maxPages = (int) ($options['max'] ?? 250);
$delayMs = (int) ($options['delay-ms'] ?? 500);
$host = parse_url($base, PHP_URL_HOST);

$ledgerPath = __DIR__.'/../docs/port-ledger.json';
$ledger = json_decode(file_get_contents($ledgerPath), true);

/* ---- Route patterns ----------------------------------------------------- */

/*
 * Laravel parameters never match '/'. Routes are tried in registration order
 * (the ledger's `order`), because that is what the router does: the first
 * registered route that matches wins, however specific a later one is.
 */
$patterns = [];
foreach (array_keys($ledger['routes']) as $uri) {
    if ($uri === '/{fallbackPlaceholder}') {
        continue;
    }
    $regex = preg_quote($uri, '#');
    $regex = preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', $regex);
    $patterns[$uri] = '#^'.$regex.'$#u';
}
uksort($patterns, fn ($a, $b) => $ledger['routes'][$a]['order'] <=> $ledger['routes'][$b]['order']);

function matchRoute(array $patterns, string $path): ?string
{
    foreach ($patterns as $uri => $regex) {
        if (preg_match($regex, $path)) {
            return $uri;
        }
    }

    return null;
}

/* ---- HTTP --------------------------------------------------------------- */

/** @return array{status: int, location: ?string, body: string} */
function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'HCSyria-rewrite-parity-probe/1.0 (read-only)',
        CURLOPT_HEADER => true,
    ]);
    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($raw, 0, $headerSize);
    $location = preg_match('#^location:\s*(.+)$#mi', $headers, $m) ? trim($m[1]) : null;

    return ['status' => $status, 'location' => $location, 'body' => substr($raw, $headerSize)];
}

function normalizePath(string $href, string $base, string $host): ?string
{
    $href = html_entity_decode(trim($href));
    if ($href === '' || preg_match('#^(mailto:|tel:|javascript:|\#|data:)#i', $href)) {
        return null;
    }

    $parts = parse_url($href);
    if ($parts === false) {
        return null;
    }
    if (isset($parts['host']) && strcasecmp(preg_replace('#^www\.#', '', $parts['host']), preg_replace('#^www\.#', '', $host)) !== 0) {
        return null;
    }

    $path = rawurldecode($parts['path'] ?? '/');
    if (! str_starts_with($path, '/')) {
        return null;
    } // relative links are rare here; skip rather than guess
    if (preg_match('#\.(jpe?g|png|gif|svg|webp|pdf|mp4|mov|css|js|ico|otf|woff2?|zip|docx?|xlsx?)$#i', $path)) {
        return null;
    }

    return rtrim($path, '/') ?: '/';
}

/* ---- Crawl -------------------------------------------------------------- */

$queue = ['/home', '/'];
if (isset($options['seeds'])) {
    $seedLines = file($options['seeds'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($seedLines as $line) {
        $queue[] = trim($line);
    }
}
$seen = [];
$results = [];   // route uri => [[url, status, location], ...]
$unmatched = [];
$failures = []; // every non-200 seen, beyond the three examples
$opened = [];
$fetched = 0;

while ($queue && $fetched < $maxPages) {
    $path = array_shift($queue);
    if (isset($seen[$path])) {
        continue;
    }
    $seen[$path] = true;

    $route = matchRoute($patterns, $path);
    // Open up to 8 pages per route to discover links; keep 3 as examples.
    if ($route !== null && ($opened[$route] = ($opened[$route] ?? 0) + 1) > 8) {
        continue;
    }

    $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
    $response = fetch($base.$encoded);
    $fetched++;
    usleep($delayMs * 1000);

    $entry = ['url' => $path, 'status' => $response['status']];
    if ($response['location']) {
        $entry['location'] = $response['location'];
    }

    if ($route !== null) {
        if (count($results[$route] ?? []) < 3) {
            $results[$route][] = $entry;
        }
        if ($response['status'] !== 200) {
            $failures[$route][] = $entry;
        }
    } else {
        $unmatched[$path] = $response['status'];
    }
    printf("%3d  %-55s %s\n", $response['status'], mb_strimwidth($path, 0, 55), $route ?? '(no route: fallback)');

    if ($response['location'] && ($next = normalizePath($response['location'], $base, $host))) {
        $queue[] = $next;
    }
    if ($response['status'] === 200 && preg_match_all('#href\s*=\s*["\']([^"\']+)["\']#i', $response['body'], $m)) {
        foreach ($m[1] as $href) {
            $next = normalizePath($href, $base, $host);
            if ($next !== null && ! isset($seen[$next])) {
                $queue[] = $next;
            }
        }
    }
}

/* Parameterless routes the crawl never reached: ask for them directly. */
foreach (array_keys($patterns) as $uri) {
    if (isset($results[$uri]) || str_contains($uri, '{')) {
        continue;
    }
    $response = fetch($base.implode('/', array_map('rawurlencode', explode('/', $uri))));
    usleep($delayMs * 1000);
    $entry = ['url' => $uri, 'status' => $response['status'], 'not_linked' => true];
    if ($response['location']) {
        $entry['location'] = $response['location'];
    }
    $results[$uri][] = $entry;
    printf("%3d  %-55s %s (direct, not linked)\n", $response['status'], $uri, $uri);
}

/* ---- Write back --------------------------------------------------------- */

foreach ($ledger['routes'] as $uri => &$route) {
    if (! isset($results[$uri])) {
        $route['live_status'] = 'not reached';
        $route['examples'] = [];

        continue;
    }
    $codes = array_unique(array_column([...$results[$uri], ...($failures[$uri] ?? [])], 'status'));
    sort($codes);
    $route['live_status'] = implode('/', $codes);
    $route['examples'] = $results[$uri];
    $route['failures'] = array_values(array_filter($failures[$uri] ?? [], fn ($f) => ! in_array($f, $results[$uri], true)));
}
unset($route);

$ledger['live_probe'] = [
    'base' => $base,
    'date' => date('Y-m-d'),
    'pages_fetched' => $fetched,
    'unmatched_paths' => $unmatched,
];

file_put_contents($ledgerPath, json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

echo "\nfetched $fetched pages\n";
foreach ($ledger['routes'] as $uri => $route) {
    printf("  %-12s %-45s\n", $route['live_status'], $uri);
}
