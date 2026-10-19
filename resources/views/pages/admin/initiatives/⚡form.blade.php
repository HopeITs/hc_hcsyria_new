<?php

use App\Actions\Admin\SaveInitiative;
use App\Enums\ContentKind;
use App\Livewire\Concerns\SendsAlerts;
use App\Livewire\Forms\InitiativeForm;
use App\Models\Initiative;
use App\Queries\AdminInitiativeQuery;
use App\Queries\ContentLookups;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * /admin/initiatives/create and /admin/initiatives/{id}/edit: the one form for an
 * initiative, with its text in a small rich-text box in place of CKEditor 12.
 * The rules are InitiativeForm, the saving is SaveInitiative, the wording of every
 * alert is lang/en/admin.php. Replaces legacy's initiatives create and edit. The
 * category is chosen once, when it is created. Logo and photos arrive with the
 * images update.
 */
new #[Layout('layouts::admin', ['title' => 'Initiative'])] class extends Component
{
    use SendsAlerts;

    public InitiativeForm $form;

    #[Locked]
    public ?int $initiativeId = null;

    protected function contentKind(): ContentKind
    {
        return ContentKind::Initiative;
    }

    public function mount(?Initiative $initiative = null): void
    {
        if ($initiative === null) {
            $this->authorize('create', Initiative::class);

            return;
        }

        $this->authorize('update', $initiative);
        $this->initiativeId = $initiative->getKey();
        $this->form->fillFrom(AdminInitiativeQuery::find($this->initiativeId), AdminInitiativeQuery::donors($this->initiativeId));
    }

    public function save(): void
    {
        $initiative = $this->initiativeId === null ? null : Initiative::query()->findOrFail($this->initiativeId);
        $initiative === null ? $this->authorize('create', Initiative::class) : $this->authorize('update', $initiative);

        try {
            $data = $this->form->payload();
        } catch (ValidationException $invalid) {
            $this->alert('invalid', type: 'error');

            throw $invalid;
        }

        $saved = app(SaveInitiative::class)->handle($data, Auth::user(), $initiative);

        $this->flashAlert($initiative === null ? 'created' : 'saved', ['number' => $saved->getKey()]);
        $this->redirectRoute('admin.initiatives.show', $saved->getKey(), navigate: true);
    }

    public function with(): array
    {
        $editing = $this->initiativeId !== null;

        return [
            'editing' => $editing,
            'crumbs' => ['Dashboard' => route('admin.dashboard'), 'Initiatives' => route('admin.initiatives.index')]
                + ($editing ? ['#'.$this->initiativeId => route('admin.initiatives.show', $this->initiativeId), 'Edit' => null] : ['New' => null]),
            'categories' => ContentLookups::initiativeCategories()->pluck('name', 'id')->all(),
            'serves' => ContentLookups::whoCategories()->pluck('name', 'id')->all(),
            'kinds' => ContentLookups::familyKinds()->pluck('name', 'id')->all(),
            'donors' => ContentLookups::allDonors()->pluck('name', 'id')->all(),
            'family' => $this->form->isFamilyToFamily(),
        ];
    }
};
?>

<div class="stack">
    <x-admin.page-header
        :title="$editing ? 'Edit initiative #'.$initiativeId : 'New initiative'"
        :subtitle="$editing ? $form->title : 'Fill in what you know. Nothing goes online until someone publishes it.'"
        :crumbs="$crumbs" />

    <form wire:submit="save" class="panel" style="padding: 24px 24px 0; overflow: visible" novalidate>
        <x-admin.form-section title="Basics" hint="What kind of initiative it is and who it serves.">
            <x-admin.field label="Category" name="form.what_category_id" required full>
                @if ($editing)
                    <div><b>{{ $categories[$form->what_category_id] ?? '' }}</b> <span class="muted">(the category is fixed once an initiative is created)</span></div>
                @else
                    <x-admin.segmented name="form.what_category_id" :options="$categories" live />
                @endif
            </x-admin.field>
            @if ($family)
                <x-admin.field label="Family to Family kind" name="form.sub_what_category_id" required full>
                    <x-admin.segmented name="form.sub_what_category_id" :options="$kinds" />
                </x-admin.field>
            @endif
            <x-admin.field label="Serves" name="form.who_category_id" required full>
                <x-admin.segmented name="form.who_category_id" :options="$serves" />
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Details" hint="The title and the lines shown with it.">
            <x-admin.field label="Title" name="form.title" required full>
                <input id="f-form.title" class="inp" wire:model="form.title" maxlength="70">
            </x-admin.field>
            <x-admin.field label="Organization" name="form.organization">
                <input id="f-form.organization" class="inp" wire:model="form.organization" maxlength="70">
            </x-admin.field>
            <x-admin.field label="Date" name="form.date_string" help="Written as it should show, for example “Summer 2024”.">
                <input id="f-form.date_string" class="inp" wire:model="form.date_string" maxlength="70">
            </x-admin.field>
            <x-admin.field label="Video link" name="form.video">
                <input id="f-form.video" type="url" class="inp" placeholder="https://www.youtube.com/embed/…" wire:model="form.video" maxlength="100">
            </x-admin.field>
            <x-admin.field label="Short description" name="form.description" full help="Up to 400 characters.">
                <textarea id="f-form.description" class="ta" wire:model="form.description" maxlength="400"></textarea>
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Content" hint="The full text on the initiative's page.">
            <x-admin.field label="Content" name="form.content" required full>
                <x-admin.rich-text model="form.content" />
            </x-admin.field>
        </x-admin.form-section>

        <x-admin.form-section title="Donors" hint="Choose at least one.">
            <x-admin.field label="Donors" name="form.donors" required full>
                <x-admin.multi-check name="form.donors" :options="$donors" />
            </x-admin.field>
        </x-admin.form-section>

        <div class="sticky-bar">
            <span class="dirty" wire:dirty.class="on"><x-admin.icon name="warn" class="i16" />Unsaved changes</span>
            <a href="{{ $editing ? route('admin.initiatives.show', $initiativeId) : route('admin.initiatives.index') }}" wire:navigate class="btn btn-o">Cancel</a>
            <button type="submit" class="btn btn-p" wire:loading.attr="disabled" wire:target="save">
                <span class="spin" wire:loading wire:target="save"></span><x-admin.icon name="check" wire:loading.remove wire:target="save" />{{ $editing ? 'Save changes' : 'Create initiative' }}
            </button>
        </div>
    </form>
</div>
