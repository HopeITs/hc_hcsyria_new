<?php

/*
 * Works out which legacy controller methods are actually reachable, so the
 * rewrite ports live code only.
 *
 *   php scripts/analyze-controllers.php [path-to-legacy-project]
 *
 * Entry points are the route files. From there it follows $this->method()
 * and self::method() calls inside each controller to find everything those
 * routes can reach. Anything left over is dead.
 *
 * Uses PHP's tokenizer rather than regex, so commented-out routes and
 * commented-out method bodies are excluded properly — the legacy code has a
 * lot of both.
 */

/* Positional argument is the legacy project path; --flags are not. */
$positional = array_values(array_filter(
    array_slice($argv, 1),
    fn ($a) => ! str_starts_with($a, '--')
));

$legacy = $positional[0] ?? 'E:/03-Website/hcsyria.org Website/hcsyria.org-website';
$controllerDir = $legacy.'/app/Http/Controllers';

if (! is_dir($controllerDir)) {
    exit("not a legacy project: $controllerDir\n");
}

/** Strips comments and whitespace, returning significant tokens only. */
function significantTokens(string $php): array
{
    return array_values(array_filter(
        token_get_all($php),
        fn ($t) => ! is_array($t) || ! in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)
    ));
}

function tokenIs($token, int $type): bool
{
    return is_array($token) && $token[0] === $type;
}

/**
 * Method names declared in a class, the methods each one calls, and the
 * views each one names with a literal view('...') call.
 *
 * @return array{methods: array<string,int>, calls: array<string, string[]>, views: array<string, string[]>}
 */
function parseController(string $file): array
{
    $tokens = significantTokens(file_get_contents($file));
    $methods = [];
    $calls = [];
    $views = [];
    $current = null;
    $depth = 0;

    foreach ($tokens as $i => $token) {
        // Track brace depth so we know when a method body ends.
        // "{$var}" and "${var}" inside strings open with a token of their own
        // but close with a plain '}', so they must count as an opening brace.
        if ($token === '{' || tokenIs($token, T_CURLY_OPEN) || tokenIs($token, T_DOLLAR_OPEN_CURLY_BRACES)) {
            $depth++;

            continue;
        }
        if ($token === '}') {
            $depth--;
            if ($current !== null && $depth <= 1) {
                $current = null;
            }

            continue;
        }

        // A named function at class level is a method. An anonymous function
        // is followed by '(' instead of a name.
        if (tokenIs($token, T_FUNCTION) && isset($tokens[$i + 1]) && tokenIs($tokens[$i + 1], T_STRING)) {
            $current = $tokens[$i + 1][1];
            $methods[$current] = $token[2]; // line number
            $calls[$current] ??= [];

            continue;
        }

        if ($current === null) {
            continue;
        }

        // $this->name(  /  self::name(  /  static::name(
        $isThisCall = tokenIs($token, T_VARIABLE) && $token[1] === '$this'
            && isset($tokens[$i + 1]) && tokenIs($tokens[$i + 1], T_OBJECT_OPERATOR)
            && isset($tokens[$i + 2]) && tokenIs($tokens[$i + 2], T_STRING)
            && ($tokens[$i + 3] ?? null) === '(';

        $isStaticCall = (tokenIs($token, T_STRING) && in_array(strtolower($token[1]), ['self', 'static'], true))
            && ($tokens[$i + 1] ?? null) !== null && tokenIs($tokens[$i + 1], T_DOUBLE_COLON)
            && isset($tokens[$i + 2]) && tokenIs($tokens[$i + 2], T_STRING)
            && ($tokens[$i + 3] ?? null) === '(';

        if ($isThisCall || $isStaticCall) {
            $calls[$current][] = $tokens[$i + 2][1];
        }

        // view('literal.name' — dynamic names cannot be checked and are skipped.
        if (tokenIs($token, T_STRING) && $token[1] === 'view'
            && ($tokens[$i + 1] ?? null) === '('
            && isset($tokens[$i + 2]) && tokenIs($tokens[$i + 2], T_CONSTANT_ENCAPSED_STRING)) {
            $views[$current][] = trim($tokens[$i + 2][1], "'\"");
        }
    }

    foreach ($calls as $k => $v) {
        $calls[$k] = array_values(array_unique($v));
    }

    return ['methods' => $methods, 'calls' => $calls, 'views' => $views];
}

/* ---- 1. Parse every controller ---------------------------------------- */

$controllers = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir)) as $f) {
    if ($f->getExtension() !== 'php') {
        continue;
    }
    $name = $f->getBasename('.php');
    if ($name === 'Controller') {
        continue;
    } // base class
    $controllers[$name] = parseController($f->getPathname()) + ['path' => $f->getPathname()];
}

/* ---- 2. Entry points from the route files ------------------------------ */

$entry = [];            // controller => [method, ...]
$frameworkDriven = [];  // classes whose routes come from framework traits
$routeCount = 0;

foreach (glob($legacy.'/routes/*.php') as $routeFile) {
    $tokens = significantTokens(file_get_contents($routeFile));

    foreach ($tokens as $i => $token) {
        // 'Controller@method' as a single string literal
        if (tokenIs($token, T_CONSTANT_ENCAPSED_STRING)) {
            $value = trim($token[1], "'\"");
            if (str_contains($value, '@')) {
                [$c, $m] = explode('@', $value, 2);
                $c = basename(str_replace('\\', '/', $c));
                if (isset($controllers[$c])) {
                    $entry[$c][] = $m;
                    $routeCount++;
                }
            }
        }

        // [Controller::class, 'method']
        if (tokenIs($token, T_STRING) && isset($controllers[$token[1]])
            && ($tokens[$i + 1] ?? null) !== null && tokenIs($tokens[$i + 1], T_DOUBLE_COLON)
            && isset($tokens[$i + 2]) && tokenIs($tokens[$i + 2], T_CLASS)
            && ($tokens[$i + 3] ?? null) === ','
            && isset($tokens[$i + 4]) && tokenIs($tokens[$i + 4], T_CONSTANT_ENCAPSED_STRING)) {
            $entry[$token[1]][] = trim($tokens[$i + 4][1], "'\"");
            $routeCount++;
        }

        /*
         * Auth::routes() registers the scaffolding auth controllers without
         * naming them, so their methods are reachable even though no route
         * line mentions them. Most of those methods come from framework
         * traits rather than being declared in the class.
         */
        if (tokenIs($token, T_STRING) && $token[1] === 'Auth'
            && ($tokens[$i + 1] ?? null) !== null && tokenIs($tokens[$i + 1], T_DOUBLE_COLON)
            && isset($tokens[$i + 2]) && tokenIs($tokens[$i + 2], T_STRING)
            && $tokens[$i + 2][1] === 'routes') {
            foreach ([
                'LoginController' => ['showLoginForm', 'login', 'logout'],
                'RegisterController' => ['showRegistrationForm', 'register'],
                'ForgotPasswordController' => ['showLinkRequestForm', 'sendResetLinkEmail'],
                'ResetPasswordController' => ['showResetForm', 'reset'],
            ] as $controller => $methods) {
                if (! isset($controllers[$controller])) {
                    continue;
                }
                foreach ($methods as $m) {
                    $entry[$controller][] = $m;
                }

                /*
                 * These classes barely declare the methods the routes call —
                 * those live in framework traits, which in turn call back into
                 * the class (validator(), create(), redirectTo). Static call
                 * following cannot see that, so treat every declared method as
                 * live and flag the class for manual review instead.
                 */
                $frameworkDriven[$controller] = true;
                foreach (array_keys($controllers[$controller]['methods']) as $m) {
                    $entry[$controller][] = $m;
                }
                $routeCount++;
            }
        }
    }
}

/* ---- 3. Follow internal calls from each entry point --------------------- */

$reachable = [];
foreach ($entry as $controller => $methods) {
    $queue = array_unique($methods);
    $seen = [];

    while ($queue) {
        $method = array_shift($queue);
        if (isset($seen[$method])) {
            continue;
        }
        $seen[$method] = true;

        foreach ($controllers[$controller]['calls'][$method] ?? [] as $called) {
            if (isset($controllers[$controller]['methods'][$called]) && ! isset($seen[$called])) {
                $queue[] = $called;
            }
        }
    }
    $reachable[$controller] = $seen;
}

/* ---- 3b. Apply the port exclusions -------------------------------------- */

/*
 * Some code is reachable but deliberately not being carried over — French, and
 * the retired student/visit-entry tracking. Reachability cannot know that, so
 * it is declared in docs/port-exclusions.json.
 */
$exclusions = ['controllers' => [], 'methods' => []];
$exclusionsFile = __DIR__.'/../docs/port-exclusions.json';
if (is_file($exclusionsFile)) {
    $decoded = json_decode(file_get_contents($exclusionsFile), true);
    if (is_array($decoded)) {
        $exclusions['controllers'] = $decoded['controllers'] ?? [];
        $exclusions['methods'] = $decoded['methods'] ?? [];
    }
}

$excludedMethodCount = 0;
foreach ($reachable as $controller => $methods) {
    $retired = $exclusions['methods'][$controller]['list'] ?? [];
    foreach ($retired as $method) {
        if (isset($reachable[$controller][$method])) {
            unset($reachable[$controller][$method]);
            $excludedMethodCount++;
        }
    }
}

/* ---- 4. Report ---------------------------------------------------------- */

$totalMethods = $totalLive = 0;
$deadControllers = [];
$rows = [];

/*
 * Magic methods are never called by name. __construct in particular runs on
 * every request that hits the controller and is where the legacy code applies
 * middleware, so it must not be reported as dead when the class is in use.
 */
$magic = ['__construct', '__invoke', '__get', '__set', '__call', '__callStatic'];

$excludedControllers = [];

foreach ($controllers as $name => $data) {
    // Reachable, but declared out of scope in docs/port-exclusions.json.
    if (isset($exclusions['controllers'][$name])) {
        $excludedControllers[$name] = [
            'reason' => $exclusions['controllers'][$name],
            'methods' => count($data['methods']),
        ];

        continue;
    }

    $all = array_keys($data['methods']);
    $live = array_keys($reachable[$name] ?? []);
    $live = array_values(array_intersect($live, $all));

    if ($live !== []) {
        $live = array_values(array_unique(array_merge(
            $live,
            array_intersect($magic, $all)
        )));
    }

    $dead = array_values(array_diff($all, $live));

    $totalMethods += count($all);
    $totalLive += count($live);

    if ($live === [] && ! isset($frameworkDriven[$name])) {
        $deadControllers[] = $name;
    }

    $rows[$name] = ['all' => $all, 'live' => $live, 'dead' => $dead];
}

/*
 * A route that reaches a method is not enough: the view it returns must exist.
 * /about reached PublicController::about(), which returns a view that was
 * never committed, so the live site answers it with a 500. Methods like that
 * are reported as broken and must not be ported.
 */
$broken = [];

// A route naming a method the controller does not declare (e.g. /tt) is a 500.
foreach ($entry as $name => $methods) {
    if (isset($frameworkDriven[$name])) {
        continue;
    }
    foreach (array_unique($methods) as $method) {
        if (! isset($controllers[$name]['methods'][$method])) {
            $broken[$name][$method][] = '(method does not exist)';
        }
    }
}

foreach ($rows as $name => $r) {
    foreach ($r['live'] as $method) {
        foreach ($controllers[$name]['views'][$method] ?? [] as $view) {
            $file = $legacy.'/resources/views/'.str_replace('.', '/', $view).'.blade.php';
            if (! is_file($file)) {
                $broken[$name][$method][] = $view;
            }
        }
    }
}

echo "ROUTE ENTRY POINTS: $routeCount\n";
printf("CONTROLLERS: %d   METHODS: %d   REACHABLE: %d   DEAD: %d (%d%%)\n\n",
    count($controllers), $totalMethods, $totalLive, $totalMethods - $totalLive,
    $totalMethods ? round(($totalMethods - $totalLive) / $totalMethods * 100) : 0);

echo "=== CONTROLLERS TO PORT ===\n";
uasort($rows, fn ($a, $b) => count($b['live']) <=> count($a['live']));
foreach ($rows as $name => $r) {
    if ($r['live'] === []) {
        continue;
    }
    printf("\n%s  (%d of %d methods live)\n", $name, count($r['live']), count($r['all']));
    sort($r['live']);
    foreach ($r['live'] as $m) {
        echo "    + $m\n";
    }
    if ($r['dead']) {
        sort($r['dead']);
        printf("    - dead (%d): %s\n", count($r['dead']), implode(', ', $r['dead']));
    }
}

if ($excludedControllers || $exclusions['methods']) {
    echo "\n=== EXCLUDED BY POLICY (do not port) ===\n";
    foreach ($excludedControllers as $name => $info) {
        printf("    %-38s %2d methods  %s\n", $name, $info['methods'], $info['reason']);
    }
    foreach ($exclusions['methods'] as $controller => $spec) {
        printf("    %-38s %2d methods  %s\n",
            $controller.' (partial)', count($spec['list'] ?? []), $spec['_reason'] ?? '');
    }
}

if ($broken) {
    echo "\n=== BROKEN: reachable but the view does not exist (do not port) ===\n";
    foreach ($broken as $controller => $methods) {
        foreach ($methods as $method => $views) {
            printf("    %-42s missing %s\n", "$controller::$method", implode(', ', array_unique($views)));
        }
    }
}

if ($deadControllers) {
    echo "\n=== CONTROLLERS WITH NO REACHABLE METHOD (do not port) ===\n";
    sort($deadControllers);
    foreach ($deadControllers as $c) {
        printf("    %-42s %d methods\n", $c, count($rows[$c]['all']));
    }
}

/* ---- 5. Optional machine-readable manifest ------------------------------ */

$manifestPath = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--manifest=')) {
        $manifestPath = substr($arg, strlen('--manifest='));
    }
}

if ($manifestPath !== null) {
    $manifest = [
        'generated_from' => $legacy,
        'route_entry_points' => $routeCount,
        'totals' => [
            'controllers' => count($controllers),
            'methods' => $totalMethods,
            'reachable' => $totalLive,
            'dead' => $totalMethods - $totalLive,
        ],
        'port' => [],
        'skip' => [],
        'broken' => $broken,
    ];

    foreach ($rows as $name => $r) {
        sort($r['live']);
        sort($r['dead']);

        if ($r['live'] === [] && ! isset($frameworkDriven[$name])) {
            $manifest['skip'][$name] = ['reason' => 'no reachable method', 'methods' => $r['all']];

            continue;
        }

        $views = [];
        foreach ($r['live'] as $method) {
            if (! empty($controllers[$name]['views'][$method])) {
                $views[$method] = array_values(array_unique($controllers[$name]['views'][$method]));
            }
        }

        $manifest['port'][$name] = [
            'framework_driven' => isset($frameworkDriven[$name]),
            'live' => $r['live'],
            'dead' => $r['dead'],
            'views' => $views,
        ];
    }

    file_put_contents(
        $manifestPath,
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
    );
    echo "\nmanifest written to $manifestPath\n";
}
