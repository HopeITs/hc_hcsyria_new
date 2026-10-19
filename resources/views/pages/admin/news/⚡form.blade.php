<?php

use App\Actions\Admin\SaveNews;
use App\Enums\ContentKind;
use App\Livewire\Concerns\SendsAlerts;
use App\Livewire\Forms\NewsForm;
use App\Models\News;
use App\Queries\AdminNewsQuery;
use App\Queries\ContentLookups;
use App\Rules\SecureDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * /admin/news/create and /admin/news/{id}/edit: the one form for a newsletter item,
 * with its text in a small rich-text box in place of CKEditor 12, its tags chosen
 * or typed in, and an optional PDF attached (checked by SecureDocument). The rules
 * are NewsForm, the saving is SaveNews. Replaces legacy's news create and edit forms.
 * Pictures and the Important/Pinned status arrive with the images update.
 */
new #[Layout('layouts::admin', ['title' => 'Newsletter item'])] class extends Component
{
    use SendsAlerts;
    use WithFileUploads;

    private const MAX_KILOBYTES = 20480;

    public NewsForm $form;

    #[Locked]
    public ?int $newsId = null;

    public mixed $attachment = null;

    public bool $removeFile = false;

    protected function contentKind(): ContentKind
    {
        return ContentKind::News;
    }

    public function mount(?News $news = null): void
    {
        if ($news === null) {
            $this->authorize('create', News::class);
            $this->form->date = now()->toDateString();

            return;
        }

        $this->authorize('update', $news);
        $this->newsId = $news->getKey();
        $this->form->fillFrom(AdminNewsQuery::find($this->newsId), AdminNewsQuery::tags($this->newsId));
    }

    public function save(): void
    {
        $news = $this->newsId === null ? null : News::query()->findOrFail($this->newsId);
        $news === null ? $this->authorize('create', News::class) : $this->authorize('update', $news);

        try {
            $data = $this->form->payload();
            $this->validate(['attachment' => ['nullable', 'file', 'max:'.self::MAX_KILOBYTES, new SecureDocument]], attributes: ['attachment' => 'attachment']);
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        $saved = app(SaveNews::class)->handle($data, Auth::user(), $this->attachment, $this->removeFile, $news);

        $this->flashAlert($news === null ? 'created' : 'saved', ['number' => $saved->getKey()]);
        $this->redirectRoute('admin.news.show', $saved->getKey(), navigate: true);
    }

    public function with(): array
    {
        $editing = $this->newsId !== null;
        $current = $editing ? AdminNewsQuery::find($this->newsId) : null;

        return [
            'editing' => $editing,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Newsletter' => route('admin.news.index')]
                + ($editing ? ['#'.$this->newsId => route('admin.news.show', $this->newsId), 'Edit' => null] : ['New' => null]),
            'types' => ContentLookups::NEWS_TYPES,
            'places' => ContentLookups::places()->pluck('name', 'id')->all(),
            'editions' => ContentLookups::editions(),
            'allTags' => ContentLookups::tags()->pluck('name', 'id')->all(),
            'isVideo' => (int) $this->form->type === ContentLookups::NEWS_VIDEO,
            'online' => $current !== null && (int) $current->published === 1,
            'file' => $current !== null && $current->file !== '0' ? $current->file : null,
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header
        :title="$editing ? 'Edit newsletter item #'.$newsId : 'New newsletter item'"
        :subtitle="$editing ? $form->title : 'Fill in what you know. Nothing goes online until someone publishes it.'"
        :crumbs="$crumbs" />

    @if ($online)
        <div class="note warn"><x-admin.icon name="warn" /><div>This item is online. Saving a change takes it back to the pending list to be published again.</div></div>
    @endif

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate>
        <x-admin.form-section title="Basics" hint="What kind of item it is, where and when.">
            <x-admin.field label="Kind" name="form.type" required full>
                <x-admin.segmented name="form.type" :options="$types" live />
            </x-admin.field>
            <x-admin.field label="Place" name="form.place_id" required full>
                <x-admin.segmented name="form.place_id" :options="$places" />
            </x-admin.field>
            <x-admin.field label="Date" name="form.date" required>
                <input id="f-form.date" type="date" class="inp" wire:model="form.date">
            </x-admin.field>
            <x-admin.field label="Edition" name="form.edition_id" help="The magazine edition it belongs to, if any.">
                <select id="f-form.edition_id" class="sel" wire:model="form.edition_id">
                    <option value="">None</option>
                    @foreach ($editions as $edition)<option value="{{ $edition->id }}">{{ $edition->name }}</option>@endforeach
                </select>
            </x-admin.field>
            <x-admin.field label="Rank" name="form.rank" help="Lower numbers come first among items of the same date.">
                <input id="f-form.rank" class="inp" inputmode="numeric" wire:model="form.rank">
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Content" hint="The title and the text shown on the news page.">
            <x-admin.field label="Title" name="form.title" required full>
                <input id="f-form.title" class="inp" wire:model="form.title" maxlength="100">
            </x-admin.field>
            <x-admin.field label="Short description" name="form.description" full help="Up to 900 characters; shown on the card.">
                <textarea id="f-form.description" class="ta" wire:model="form.description" maxlength="900"></textarea>
            </x-admin.field>
            <x-admin.field label="Content" name="form.content" required full>
                <x-admin.rich-text model="form.content" />
            </x-admin.field>
            @if ($isVideo)
                <x-admin.field label="Video link" name="form.video" required full>
                    <input id="f-form.video" type="url" class="inp" placeholder="https://www.youtube.com/embed/…" wire:model="form.video" maxlength="100">
                </x-admin.field>
            @endif
        </x-admin.form-section>

        <x-admin.form-section title="Tags" hint="At least one, so readers can find it.">
            <x-admin.field label="Tags" name="form.tags" required full>
                <x-admin.multi-check name="form.tags" :options="$allTags" />
            </x-admin.field>
            <x-admin.field label="New tags" name="form.newTags" full help="Separate several with commas. A tag that already exists is reused.">
                <input id="f-form.newTags" class="inp" wire:model="form.newTags" maxlength="500">
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Attachment" hint="An optional PDF readers can download.">
            <x-admin.field label="PDF" name="attachment" full :help="$file ? 'Choosing a file replaces the current one.' : null">
                @if ($file)<div class="row-s"><span class="chip">{{ basename($file) }}</span><label class="row-s"><input type="checkbox" class="cb" wire:model="removeFile"> Remove it</label></div>@endif
                <input id="f-attachment" type="file" class="inp" wire:model="attachment" accept="application/pdf" style="height: auto; padding: 6px 8px">
            </x-admin.field>
        </x-admin.form-section>

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ $editing ? route('admin.news.show', $newsId) : route('admin.news.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create item' }}
            </button>
        </div>
    </form>
</div>
