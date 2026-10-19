{{-- The newsletter's own sections as tabs: its items, and the lists that go with them. Each shows only to whoever may open it. --}}
@php
    $user = auth()->user();
    $tabs = array_filter([
        'Items' => ['admin.news.*', 'admin.news.index', true],
        'Tags' => ['admin.news.tags', 'admin.news.tags', true],
        'Editions' => ['admin.news.editions', 'admin.news.editions', true],
        'Subscribers' => ['admin.news.subscribers', 'admin.news.subscribers', $user->can('admin.'.\App\Enums\AdminCapability::ViewSubscribers->value)],
    ], fn (array $tab): bool => $tab[2]);
@endphp
<nav class="tabs" aria-label="Newsletter sections" style="border: 1px solid var(--line); border-radius: 12px; background: var(--board)">
    @foreach ($tabs as $label => [$match, $route])
        <a href="{{ route($route) }}" wire:navigate class="tab" aria-selected="{{ request()->routeIs($match) && ! ($route === 'admin.news.index' && request()->routeIs('admin.news.tags', 'admin.news.editions', 'admin.news.subscribers')) ? 'true' : 'false' }}">{{ $label }}</a>
    @endforeach
</nav>
