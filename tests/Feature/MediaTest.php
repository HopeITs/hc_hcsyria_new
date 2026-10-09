<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/hcsyria-media-'.uniqid();

    File::ensureDirectoryExists("{$this->root}/images/home/Micro Projects");
    File::ensureDirectoryExists("{$this->root}/video");
    File::put("{$this->root}/images/home/photo.jpg", 'jpeg-bytes');
    File::put("{$this->root}/images/home/Micro Projects/été 1.png", 'png-bytes');
    File::put("{$this->root}/images/home/logo.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    File::put("{$this->root}/images/home/shell.php", '<?php echo "owned";');
    File::put("{$this->root}/images/home/backup.log", 'log');
    File::put("{$this->root}/video/clip.mp4", str_repeat('x', 100));
    File::put("{$this->root}/secret.jpg", 'not in any area');

    config(['hcsyria.media_root' => $this->root]);
});

afterEach(fn () => File::deleteDirectory($this->root));

it('serves a file from the media root with its type', function () {
    $this->get('/images/home/photo.jpg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('serves names with spaces and non-ASCII characters', function () {
    $this->get('/images/home/'.rawurlencode('Micro Projects').'/'.rawurlencode('été 1.png'))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

it('serves video from its own area', function () {
    $this->get('/video/clip.mp4')->assertOk()->assertHeader('Content-Type', 'video/mp4');
});

it('never serves code, logs or anything that is not a published file type', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/images/home/shell.php', '/images/home/backup.log', '/images/home/Shell.PHP']);

it('does not leave the area it was asked for', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'encoded dots' => '/images/%2e%2e/secret.jpg',
    'encoded slash' => '/images/home%2f..%2f..%2fsecret.jpg',
    'other area' => '/video/../images/home/photo.jpg',
]);

it('sandboxes SVG so a script inside cannot run', function () {
    $this->get('/images/home/logo.svg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
});

it('answers 404 for a file that is not there', function () {
    $this->get('/images/home/missing.jpg')->assertNotFound();
});

it('answers 404 for everything when no media root is configured', function () {
    config(['hcsyria.media_root' => null]);

    $this->get('/images/home/photo.jpg')->assertNotFound();
});

it('only answers for the three media areas', function () {
    $this->get('/secrets/home/photo.jpg')->assertNotFound();
});
