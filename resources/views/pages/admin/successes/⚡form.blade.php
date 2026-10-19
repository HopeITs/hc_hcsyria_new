<?php

use App\Actions\Admin\SaveSuccess;
use App\Enums\ContentKind;
use App\Exceptions\AdminActionRefused;
use App\Livewire\Concerns\ReceivesPictures;
use App\Livewire\Forms\SuccessForm;
use App\Models\Success;
use App\Queries\AdminSuccessQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * /admin/successes/create and /admin/successes/{id}/edit: the one form for a
 * success story, with its text in a small rich-text box in place of CKEditor 12
 * and its picture checked by SecureUpload and re-encoded. What it asks depends on
 * the category (DISC: kind and place; Study Zone: place and centre; micro
 * projects: the project). The rules are SuccessForm, the saving is SaveSuccess.
 * Replaces legacy's three create and edit forms.
 */
new #[Layout('layouts::admin', ['title' => 'Success'])] class extends Component
{
    use ReceivesPictures;
    use WithFileUploads;

    public SuccessForm $form;

    #[Locked]
    public ?int $successId = null;

    public mixed $picture = null;

    /** What was typed to find a micro project to link. */
    public string $projectQuery = '';

    protected function contentKind(): ContentKind
    {
        return ContentKind::Success;
    }

    public function mount(?Success $success = null): void
    {
        if ($success === null) {
            $this->authorize('create', Success::class);

            return;
        }

        $this->authorize('update', $success);
        $this->successId = $success->getKey();
        $this->form->fillFrom(AdminSuccessQuery::find($this->successId));
    }

    public function chooseProject(int $projectId): void
    {
        $this->form->project_id = (string) $projectId;
        $this->projectQuery = '';
    }

    public function save(): void
    {
        $success = $this->successId === null ? null : Success::query()->findOrFail($this->successId);
        $success === null ? $this->authorize('create', Success::class) : $this->authorize('update', $success);

        try {
            $data = $this->form->payload();
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        if ($this->picture !== null && ! $this->acceptPictures('picture')) {
            return;
        }

        if ($success === null && $this->picture === null) {
            $this->addError('picture', 'Choose a picture for the story.');
            $this->alert('invalid', type: 'error');

            return;
        }

        try {
            $saved = app(SaveSuccess::class)->handle($data, Auth::user(), $this->picture, $success);
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $this->flashAlert($success === null ? 'created' : 'saved', ['number' => $saved->getKey()]);
        $this->redirectRoute('admin.successes.show', $saved->getKey(), navigate: true);
    }

    public function with(): array
    {
        $editing = $this->successId !== null;
        $category = $this->form->category();
        $linked = $this->form->project_id === '' ? null : ContentLookups::projectLabel((int) $this->form->project_id);

        return [
            'editing' => $editing,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Successes' => route('admin.successes.index')]
                + ($editing ? ['#'.$this->successId => route('admin.successes.show', $this->successId), 'Edit' => null] : ['New' => null]),
            'categories' => ContentLookups::successCategories()->pluck('name', 'id')->all(),
            'serves' => ContentLookups::whoCategories()->pluck('name', 'id')->all(),
            'kinds' => ContentLookups::discKinds()->pluck('name', 'id')->all(),
            'places' => ContentLookups::places()->pluck('name', 'id')->all(),
            'centres' => ContentLookups::studyCenters()->pluck('name', 'id')->all(),
            'needsPlace' => in_array($category, [ContentLookups::DISC, ContentLookups::STUDY_ZONE], true),
            'isDisc' => $category === ContentLookups::DISC,
            'isStudy' => $category === ContentLookups::STUDY_ZONE,
            'isMicro' => $category === ContentLookups::MICRO_PROJECTS,
            'linked' => $linked,
            'matches' => trim($this->projectQuery) === '' ? collect() : ContentLookups::linkableProjects($this->projectQuery),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header
        :title="$editing ? 'Edit success #'.$successId : 'New success'"
        :subtitle="$editing ? $form->title : 'Fill in what you know. Nothing goes online until someone publishes it.'"
        :crumbs="$crumbs" />

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate>
        <x-admin.form-section title="Basics" hint="What the story is about and who it serves.">
            <x-admin.field label="Category" name="form.what_category_id" required full>
                <x-admin.segmented name="form.what_category_id" :options="$categories" live />
            </x-admin.field>
            <x-admin.field label="Serves" name="form.who_category_id" required full>
                <x-admin.segmented name="form.who_category_id" :options="$serves" />
            </x-admin.field>
            @if ($isDisc)
                <x-admin.field label="Kind of DISC story" name="form.sub_what_category_id" required full>
                    <x-admin.segmented name="form.sub_what_category_id" :options="$kinds" />
                </x-admin.field>
            @endif
            @if ($needsPlace)
                <x-admin.field label="Place" name="form.place_id" required full>
                    <x-admin.segmented name="form.place_id" :options="$places" />
                </x-admin.field>
            @endif
            @if ($isStudy)
                <x-admin.field label="Study Zone centre" name="form.sz_center_id" required full>
                    <x-admin.segmented name="form.sz_center_id" :options="$centres" />
                </x-admin.field>
            @endif
            @if ($isMicro)
                <x-admin.field label="Linked micro project" name="form.project_id" required full help="Type a name or number to find an online project.">
                    @if ($linked)
                        <div class="row-s"><span class="chip">#{{ $linked->number }} {{ $linked->name }}</span></div>
                    @endif
                    <input id="f-form.project_id" class="inp" wire:model.live.debounce.300ms="projectQuery" placeholder="Find a project…" autocomplete="off">
                    @foreach ($matches as $match)
                        <button type="button" class="btn btn-o s" style="justify-content: flex-start" wire:click="chooseProject({{ $match->id }})" wire:key="match-{{ $match->id }}">#{{ $match->number }} {{ $match->name }}@if ($match->city) · {{ $match->city }}@endif</button>
                    @endforeach
                </x-admin.field>
            @endif
        </x-admin.form-section>

        <x-admin.form-section title="Story" hint="The title, the picture and the words.">
            <x-admin.field label="Title" name="form.title" required full>
                <input id="f-form.title" class="inp" wire:model="form.title" maxlength="100">
            </x-admin.field>
            <x-admin.field label="Picture" name="picture" :required="! $editing" full :help="$editing ? 'Leave empty to keep the current picture.' : 'A square picture; it is cut to 500 × 500.'">
                <input id="f-picture" type="file" class="inp" wire:model="picture" accept="image/jpeg,image/png,image/webp" style="height: auto; padding: 6px 8px">
            </x-admin.field>
            <x-admin.field label="Text" name="form.text" required full>
                <x-admin.rich-text model="form.text" />
            </x-admin.field>
            <x-admin.field label="Video link" name="form.video" full>
                <input id="f-form.video" type="url" class="inp" placeholder="https://www.youtube.com/embed/…" wire:model="form.video" maxlength="100">
            </x-admin.field>
        </x-admin.form-section>

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ $editing ? route('admin.successes.show', $successId) : route('admin.successes.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create success' }}
            </button>
        </div>
    </form>
</div>
