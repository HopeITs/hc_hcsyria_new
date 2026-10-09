<?php

/*
 * Builds docs/port-ledger.json: every public GET route of the legacy app, with
 * everything a page is made of — the views it renders (layouts, includes,
 * components, Livewire), and the CSS, JS, images and fonts those load.
 *
 *   php scripts/build-port-ledger.php [path-to-legacy-project]
 *
 * The ledger is three things at once:
 *   - the Phase 2 work list (one entry per public page, with a status)
 *   - the input to scripts/legacy-drift.php (which legacy files feed a page)
 *   - the list of assets that are actually used, so nothing else is ported
 *
 * The route table comes from the legacy app's own router (`route:list --json`),
 * not from reading routes/web.php, because duplicate definitions there are
 * resolved by the router (the later one wins) and only it knows the result.
 * That command only reads; the legacy repository is never written to.
 *
 * Regenerating keeps each route's status, notes and ported_from, so the
 * hand-maintained parts survive.
 *
 * Needs docs/controller-manifest.json (scripts/analyze-controllers.php).
 */

$positional = array_values(array_filter(array_slice($argv, 1), fn ($a) => ! str_starts_with($a, '--')));
$legacy = $positional[0] ?? 'E:/03-Website/hcsyria.org Website/hcsyria.org-website';
$ledgerPath = __DIR__.'/../docs/port-ledger.json';
$manifestPath = __DIR__.'/../docs/controller-manifest.json';

if (! is_dir("$legacy/resources/views")) {
    exit("not a legacy project: $legacy\n");
}
if (! is_file($manifestPath)) {
    exit("run analyze-controllers.php --manifest=docs/controller-manifest.json first\n");
}
$manifest = json_decode(file_get_contents($manifestPath), true);

/* ---- 1. Public GET routes from the legacy router ------------------------- */

$cwd = getcwd();
chdir($legacy);
// --sort=definition is registration order, which is the order the router
// tries routes in; the prober needs it to attribute URLs correctly.
$json = shell_exec('php artisan route:list --json --sort=definition 2>&1');
chdir($cwd);
$routes = json_decode((string) $json, true);
if (! is_array($routes)) {
    exit("route:list failed:\n$json\n");
}

/* Signed-in areas and framework endpoints are not part of the public site. */
$privatePrefixes = ['admin', 'dashboard', 'training', 'api', 'livewire', '_ignition', 'sanctum', 'storage', 'up'];

$publicRoutes = [];
foreach ($routes as $order => $route) {
    $route['order'] = $order;
    if (! str_contains($route['method'], 'GET')) {
        continue;
    }

    $uri = $route['uri'];
    $first = explode('/', $uri)[0];
    if (in_array($first, $privatePrefixes, true)) {
        continue;
    }

    $middleware = implode(' ', $route['middleware'] ?? []);
    if (str_contains($middleware, 'Authenticate') && ! str_contains($middleware, 'RedirectIfAuthenticated')) {
        continue;
    }

    $publicRoutes['/'.ltrim($uri, '/')] = $route;
}
ksort($publicRoutes);

/* Route::view() targets are not in route:list output; read them from the file. */
$viewRoutes = [];
foreach (glob("$legacy/routes/*.php") as $file) {
    preg_match_all("#Route::view\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]#", file_get_contents($file), $m, PREG_SET_ORDER);
    foreach ($m as [, $uri, $view]) {
        $viewRoutes['/'.ltrim($uri, '/')] = $view;
    }
}

/* ---- 2. Walk a view's dependencies -------------------------------------- */

function viewFile(string $legacy, string $name): string
{
    return "$legacy/resources/views/".str_replace('.', '/', $name).'.blade.php';
}

function studly(string $name): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $name)));
}

/**
 * Every view, Livewire class and asset reachable from the given views.
 *
 * @return array{views: string[], missing_views: string[], livewire: string[], assets: string[]}
 */
function walk(string $legacy, array $startViews): array
{
    $views = $missing = $livewire = $assets = [];
    $queue = $startViews;

    while ($queue) {
        $name = array_shift($queue);
        if (isset($views[$name]) || isset($missing[$name])) {
            continue;
        }

        $file = viewFile($legacy, $name);
        if (! is_file($file)) {
            $missing[$name] = true;

            continue;
        }
        $views[$name] = true;
        $blade = file_get_contents($file);

        // Blade comments are not rendered, so nothing inside them is a dependency.
        $blade = preg_replace('/\{\{--.*?--\}\}/s', '', $blade);

        // @extends / @include / @includeIf / @includeWhen(cond, 'x') / @component / @each
        preg_match_all("#@(?:extends|include|includeIf|includeFirst|component|each)\s*\(\s*['\"]([^'\"]+)['\"]#", $blade, $m);
        preg_match_all("#@includeWhen\s*\([^,]+,\s*['\"]([^'\"]+)['\"]#", $blade, $m2);
        foreach ([...$m[1], ...$m2[1]] as $v) {
            $queue[] = $v;
        }

        // <x-foo.bar> anonymous components
        preg_match_all('#<x-([a-z0-9\-\.:]+)#i', $blade, $m);
        foreach ($m[1] as $c) {
            if (str_starts_with($c, 'slot')) {
                continue;
            }
            $queue[] = 'components.'.$c;
        }

        // Livewire: @livewire('name') and <livewire:name>
        preg_match_all("#@livewire\(\s*['\"]([^'\"]+)['\"]|<livewire:([a-z0-9\-\.]+)#i", $blade, $m);
        foreach (array_filter([...$m[1], ...$m[2]]) as $lw) {
            $class = 'app/Livewire/'.str_replace('.', '/', implode('/', array_map('studly', explode('.', $lw)))).'.php';
            $livewire[$class] = true;
            if (is_file("$legacy/$class")
                && preg_match("#view\(\s*['\"]([^'\"]+)['\"]#", file_get_contents("$legacy/$class"), $vm)) {
                $queue[] = $vm[1];
            }
        }

        foreach (assetsIn($blade) as $a) {
            $assets[$a] = true;
        }
    }

    // Stylesheets pull in images and fonts of their own.
    $cssQueue = array_filter(array_keys($assets), fn ($a) => str_ends_with($a, '.css'));
    $seenCss = [];
    while ($cssQueue) {
        $css = array_shift($cssQueue);
        if (isset($seenCss[$css]) || ! is_file("$legacy/public/$css")) {
            continue;
        }
        $seenCss[$css] = true;
        $dir = dirname($css);
        preg_match_all('#url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)|@import\s+[\'"]([^\'"]+)[\'"]#', file_get_contents("$legacy/public/$css"), $m);
        foreach (array_filter([...$m[1], ...$m[2]]) as $ref) {
            if (preg_match('#^(data:|https?:|//|\#)#', $ref)) {
                continue;
            }
            $ref = preg_replace('#[?\#].*$#', '', $ref);
            $path = str_starts_with($ref, '/') ? ltrim($ref, '/') : normalize("$dir/$ref");
            $assets[$path] = true;
            if (str_ends_with($path, '.css')) {
                $cssQueue[] = $path;
            }
        }
    }

    ksort($views);
    ksort($missing);
    ksort($livewire);
    ksort($assets);

    return [
        'views' => array_keys($views),
        'missing_views' => array_keys($missing),
        'livewire' => array_keys($livewire),
        'assets' => array_keys($assets),
    ];
}

/** Literal local asset paths in a Blade file: asset('x'), src="/x", href="/x", url(/x). */
function assetsIn(string $blade): array
{
    $found = [];
    $patterns = [
        "#asset\(\s*['\"]([^'\"{}$]+)['\"]#",
        "#(?:src|href|data-src|poster)\s*=\s*['\"](/[^'\"{}$?\#]+)['\"]#",
        "#url\(\s*['\"]?(/[^'\")?{}$]+)#",
    ];
    foreach ($patterns as $p) {
        preg_match_all($p, $blade, $m);
        foreach ($m[1] as $path) {
            $path = ltrim($path, '/');
            if (preg_match('#^(css|js|images|fonts|video|files|build)/#', $path)) {
                $found[] = $path;
            }
        }
    }

    return $found;
}

function normalize(string $path): string
{
    $out = [];
    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($out);

            continue;
        }
        $out[] = $part;
    }

    return implode('/', $out);
}

/* ---- 3. Assemble the ledger, keeping hand-maintained fields -------------- */

$previous = is_file($ledgerPath) ? json_decode(file_get_contents($ledgerPath), true) : [];
$previousRoutes = $previous['routes'] ?? [];
$broken = $manifest['broken'] ?? [];

$ledger = [
    '_comment' => 'Generated by scripts/build-port-ledger.php. status, notes and ported_from are hand-maintained and survive regeneration. status: pending | ported | broken | excluded | redirect | later (admin phase).',
    'legacy_path' => $legacy,
    'legacy_commit' => trim((string) shell_exec('git -C '.escapeshellarg($legacy).' rev-parse HEAD')),
    'routes' => [],
];

foreach ($publicRoutes as $uri => $route) {
    $action = preg_replace('#^App\\\\Http\\\\Controllers\\\\#', '', $route['action']);
    $startViews = [];
    $controller = $method = null;

    if ($route['action'] === 'Illuminate\\Routing\\ViewController') {
        $action = 'view:'.($viewRoutes[$uri] ?? '?');
        if (isset($viewRoutes[$uri])) {
            $startViews[] = $viewRoutes[$uri];
        }
    } elseif (str_contains($action, '@')) {
        [$controller, $method] = explode('@', $action);
        $controller = basename(str_replace('\\', '/', $controller));
        $startViews = $manifest['port'][$controller]['views'][$method] ?? [];
    }

    $deps = walk($legacy, $startViews);
    $files = array_values(array_unique(array_filter([
        $controller ? 'app/Http/Controllers/'.str_replace('\\', '/', preg_replace('#@.*$#', '', $action)).'.php' : null,
        ...array_map(fn ($v) => 'resources/views/'.str_replace('.', '/', $v).'.blade.php', $deps['views']),
        ...$deps['livewire'],
    ])));

    $old = $previousRoutes[$uri] ?? [];
    $isBroken = $controller && isset($broken[$controller][$method]);

    $ledger['routes'][$uri] = [
        'action' => $action,
        'name' => $route['name'],
        'order' => $route['order'],
        'status' => $old['status'] ?? ($isBroken ? 'broken' : 'pending'),
        'notes' => $old['notes'] ?? ($isBroken ? 'Static analysis: '.implode(', ', $broken[$controller][$method]) : null),
        'ported_from' => $old['ported_from'] ?? null,
        'live_status' => $old['live_status'] ?? null,
        'examples' => $old['examples'] ?? [],
        'failures' => $old['failures'] ?? [],
        'views_unresolved' => $startViews === [],
        'missing_views' => $deps['missing_views'],
        'legacy_files' => $files,
        'assets' => $deps['assets'],
    ];
}

file_put_contents($ledgerPath, json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

/* ---- 4. Summary --------------------------------------------------------- */

$byStatus = array_count_values(array_column($ledger['routes'], 'status'));
$allAssets = array_unique(array_merge(...array_column($ledger['routes'], 'assets')));
printf("%d public routes  %s\n", count($ledger['routes']), json_encode($byStatus));
printf("%d distinct assets referenced by public pages\n", count($allAssets));
foreach ($ledger['routes'] as $uri => $r) {
    $flag = $r['views_unresolved'] ? '  [views unresolved]' : '';
    $flag .= $r['missing_views'] ? '  [missing: '.implode(', ', $r['missing_views']).']' : '';
    printf("  %-8s %-45s %s%s\n", $r['status'], $uri, $r['action'], $flag);
}
echo "ledger written to docs/port-ledger.json\n";
