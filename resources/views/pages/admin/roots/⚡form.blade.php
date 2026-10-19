<?php

use App\Actions\Admin\SaveMagazine;
use App\Enums\ContentKind;
use App\Livewire\Concerns\SendsAlerts;
use App\Livewire\Forms\RootsForm;
use App\Models\RootsOfHope;
use App\Queries\AdminRootsQuery;
use App\Rules\SecureDocument;
use App\Rules\SecureUpload;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * /admin/roots/create and /admin/roots/{id}/edit: the one form for a magazine edition:
 * a title, the link to its online version, a cover (checked by SecureUpload and
 * re-encoded) and an optional PDF (checked by SecureDocument). The rules are RootsForm, the
 * saving is SaveMagazine. Replaces legacy's create and edit forms.
 */
new #[Layout('layouts::admin', ['title' => 'Magazine edition'])] class extends Component
{
    use SendsAlerts;
    use WithFileUploads;

    private const COVER_KILOBYTES = 10240;

    private const PDF_KILOBYTES = 51200;

    public RootsForm $form;

    #[Locked]
    public ?int $editionId = null;

    public mixed $cover = null;

    public mixed $pdf = null;

    public bool $removeFile = false;

    protected function contentKind(): ContentKind
    {
        return ContentKind::Roots;
    }

    public function mount(?RootsOfHope $edition = null): void
    {
        if ($edition === null) {
            $this->authorize('create', RootsOfHope::class);

            return;
        }

        $this->authorize('update', $edition);
        $this->editionId = $edition->getKey();
        $this->form->fillFrom(AdminRootsQuery::find($this->editionId));
    }

    public function save(): void
    {
        $edition = $this->editionId === null ? null : RootsOfHope::query()->findOrFail($this->editionId);
        $edition === null ? $this->authorize('create', RootsOfHope::class) : $this->authorize('update', $edition);

        try {
            $data = $this->form->payload();
            $this->validate([
                'cover' => ['nullable', 'file', 'max:'.self::COVER_KILOBYTES, new SecureUpload],
                'pdf' => ['nullable', 'file', 'max:'.self::PDF_KILOBYTES, new SecureDocument],
            ], attributes: ['pdf' => 'PDF']);
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        if ($this->cover !== null && ! PhotoFiles::available()) {
            $this->alert('photos.unavailable', type: 'error');

            return;
        }

        $saved = app(SaveMagazine::class)->handle($data, Auth::user(), $this->cover, $this->pdf, $this->removeFile, $edition);

        $this->flashAlert($edition === null ? 'created' : 'saved', ['number' => $saved->getKey()]);
        $this->redirectRoute('admin.roots.show', $saved->getKey(), navigate: true);
    }

    public function with(): array
    {
        $editing = $this->editionId !== null;
        $current = $editing ? AdminRootsQuery::find($this->editionId) : null;

        return [
            'editing' => $editing,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Roots of Hope' => route('admin.roots.index')]
                + ($editing ? ['#'.$this->editionId => route('admin.roots.show', $this->editionId), 'Edit' => null] : ['New' => null]),
            'coverPath' => $current !== null && $current->image_path !== '0' ? $current->image_path : null,
            'file' => $current !== null && $current->file_path !== '0' ? $current->file_path : null,
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header
        :title="$editing ? 'Edit edition #'.$editionId : 'New magazine edition'"
        :subtitle="$editing ? $form->title : 'Nothing goes online until someone publishes it, and an edition needs a cover first.'"
        :crumbs="$crumbs" />

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate>
        <x-admin.form-section title="Edition" hint="The name readers see, and where the magazine can be read online.">
            <x-admin.field label="Title" name="form.title" required full>
                <input id="f-form.title" class="inp" wire:model="form.title" maxlength="50">
            </x-admin.field>
            <x-admin.field label="Online link" name="form.url" required full help="The address of the page that shows the magazine as a flipbook.">
                <input id="f-form.url" type="url" class="inp" placeholder="https://…" wire:model="form.url" maxlength="100">
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Cover and PDF" hint="The cover is scaled down to a page; the PDF is optional.">
            <x-admin.field label="Cover" name="cover" full :help="$coverPath ? 'Choosing a picture replaces the current cover.' : 'A JPG, PNG or WebP of the front page.'">
                @if ($cover)
                    <img src="{{ $cover->temporaryUrl() }}" alt="" style="max-height: 160px; border-radius: 8px; margin-bottom: 8px">
                @elseif ($coverPath)
                    <img src="{{ $coverPath }}" alt="Current cover" style="max-height: 160px; border-radius: 8px; margin-bottom: 8px">
                @endif
                <input id="f-cover" type="file" class="inp" wire:model="cover" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 6px 8px">
            </x-admin.field>
            <x-admin.field label="PDF" name="pdf" full :help="$file ? 'Choosing a file replaces the current one.' : null">
                @if ($file)<div class="row-s"><span class="chip">{{ basename($file) }}</span><label class="row-s"><input type="checkbox" class="cb" wire:model="removeFile"> Remove it</label></div>@endif
                <input id="f-pdf" type="file" class="inp" wire:model="pdf" accept="application/pdf" style="height: auto; padding: 6px 8px">
            </x-admin.field>
        </x-admin.form-section>

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ $editing ? route('admin.roots.show', $editionId) : route('admin.roots.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create edition' }}
            </button>
        </div>
    </form>
</div>
