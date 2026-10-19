<?php

use App\Support\Highlight;

it('marks every occurrence of the term, whatever its case', function () {
    expect((string) Highlight::text('Hope and more HOPE', 'hope'))
        ->toBe('<span class="highlight">Hope</span> and more <span class="highlight">HOPE</span>');
});

it('escapes the text and the term, and takes the term literally', function () {
    expect((string) Highlight::text('<b>a.b</b> axb', 'a.b'))->toBe('&lt;b&gt;<span class="highlight">a.b</span>&lt;/b&gt; axb')
        ->and((string) Highlight::text('say <script>x</script>', '<script>'))->toBe('say <span class="highlight">&lt;script&gt;</span>x&lt;/script&gt;')
        ->and((string) Highlight::text('a (b) c', '(b'))->toBe('a <span class="highlight">(b</span>) c');
});

it('never cuts into an entity or a tag', function () {
    expect((string) Highlight::text('Tom & Jerry', 'amp'))->toBe('Tom &amp; Jerry')
        ->and((string) Highlight::html('<p class="note">a note</p>', 'note'))->toBe('<p class="note">a <span class="highlight">note</span></p>')
        ->and((string) Highlight::html('Tom &amp; Jerry', 'tom'))->toBe('<span class="highlight">Tom</span> &amp; Jerry');
});

it('leaves the text alone when there is nothing to mark', function () {
    expect((string) Highlight::text('Hope', ''))->toBe('Hope')
        ->and((string) Highlight::text('Hope', null))->toBe('Hope')
        ->and((string) Highlight::text(null, 'x'))->toBe('');
});
