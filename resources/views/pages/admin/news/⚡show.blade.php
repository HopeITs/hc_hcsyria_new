<?php

use App\Enums\ContentKind;
use App\Enums\ProjectStatus;
use App\Livewire\Concerns\ManagesContent;
use App\Models\News;
use App\Queries\AdminNewsQuery;
use App\Queries\AdminProjectQuery;
use App\Queries\ContentLookups;
use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/news/{id}: one newsletter item, for staff: its text, tags and details,
 * whether its English is checked, and what was done to it. Replaces legacy's news
 * view. The buttons are the shared ones. Its cover pictures, status
 * and gallery are panels of their own.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter item'])] class extends Component
{
    use ManagesContent;

    #[Locked]
    public int $newsId;

    protected function contentKind(): ContentKind
    {
        return ContentKind::News;
    }

    public function mount(News $news): void
    {
        $this->authorize('view', $news);
        $this->newsId = $news->getKey();
    }

    protected function afterDeleted(Model $item): void
    {
        $this->flashAlert('deleted', ['number' => $this->contentKind()->number($item)]);
        $this->redirectRoute('admin.news.index', navigate: true);
    }

    public function with(): array
    {
        $news = AdminNewsQuery::find($this->newsId);

        if ($news === null) {
            $this->flashAlert('gone', type: 'error');
            $this->redirectRoute('admin.news.index', navigate: true);

            return ['news' => null];
        }

        return [
            'news' => $news,
            'status' => ProjectStatus::of($news->published, 1, $news->hidden),
            'tags' => AdminNewsQuery::tags($this->newsId),
            'history' => AdminProjectQuery::history($this->newsId, kind: ContentKind::News),
        ];
    }
};
?>

<div class="stack">
    @if ($news)
        <x-admin.page-header :title="$news->name" :crumbs="['Dashboard' => route('admin.dashboard'), 'Newsletter' => route('admin.news.index'), '#'.$news->number => null]">
            <x-slot:actions>
                <a href="{{ route('admin.news.index') }}" wire:navigate class="btn btn-o"><x-admin.icon name="chev" class="i16" />Back</a>
                <x-admin.content-actions :item="$news" kind="news" size="" />
            </x-slot:actions>
        </x-admin.page-header>

        <div class="row-s">
            <x-admin.status-pill :status="$status" />
            @unless ($news->english_check)<x-admin.status-pill tone="act" label="English not checked" />@endunless
            @if ($news->priority === 1)<x-admin.status-pill tone="crit" label="Important" />@elseif ($news->priority === 2)<x-admin.status-pill tone="grey" label="Pinned" />@endif
            <span class="muted">{{ ContentLookups::NEWS_TYPES[$news->type] ?? '' }}@if ($news->place !== 'All') · {{ $news->place }}@endif</span>
        </div>

        <div class="cols">
            <div class="stack">
                <x-admin.panel title="Details">
                    <dl class="facts" style="margin: 0">
                        @foreach ([
                            'Number' => '#'.$news->number,
                            'Kind' => ContentLookups::NEWS_TYPES[$news->type] ?? $news->type,
                            'Date' => $news->date ? \Illuminate\Support\Carbon::parse($news->date)->format('j M Y') : '—',
                            'Place' => $news->place !== 'All' ? $news->place : '—',
                            'Edition' => $news->edition ?? '—',
                            'Rank' => $news->rank,
                            'Video' => $news->video !== '0' ? $news->video : '—',
                            'Attachment' => $news->file !== '0' ? basename($news->file) : '—',
                            'Views' => number_format((int) $news->total_views),
                        ] as $label => $value)
                            <div class="fact"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($tags->isNotEmpty())
                        <div class="chips" style="padding: 12px 16px; border-top: 1px solid var(--line)">@foreach ($tags as $tag)<span class="chip">{{ $tag->name }}</span>@endforeach</div>
                    @endif
                </x-admin.panel>

                @if ($news->description !== '0' && $news->description !== null)
                    <x-admin.panel title="Short description"><div class="story">{{ $news->description }}</div></x-admin.panel>
                @endif

                <x-admin.panel title="Content"><div class="story">{!! SafeHtml::text($news->content) !!}</div></x-admin.panel>

                <livewire:admin.news-pictures :news-id="$news->id" :key="'pictures-'.$news->id" />

                <livewire:admin.content-photos kind="news" :item-id="$news->id" :key="'photos-'.$news->id" />
            </div>

            <aside class="stack" aria-label="Status">
                <x-admin.panel title="History"><x-admin.history :entries="$history" /></x-admin.panel>
            </aside>
        </div>
    @endif
</div>
