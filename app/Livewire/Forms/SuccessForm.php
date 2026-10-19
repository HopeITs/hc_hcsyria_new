<?php

namespace App\Livewire\Forms;

use App\Queries\ContentLookups;
use App\Support\Admin\LegacyValue;
use App\Support\SafeHtml;
use Illuminate\Validation\Rule;
use Livewire\Form;

/*
 * The create and edit form of a success story: its fields, its rules and what is
 * handed to SaveSuccess. One object for both pages. What a story needs depends on
 * its category, as in legacy: a DISC story says what it is about and where; a Study
 * Zone story where and at which centre; a micro-project story which project. The
 * picture is on the page itself (it is an upload).
 *
 * Fields left empty are sent as null; SaveSuccess stores legacy's "0".
 */
class SuccessForm extends Form
{
    public string $what_category_id = '';

    public string $who_category_id = '';

    /** DISC only. */
    public string $sub_what_category_id = '';

    /** DISC and Study Zone. */
    public string $place_id = '';

    /** Study Zone only. */
    public string $sz_center_id = '';

    /** Micro projects only: the id of the linked project. */
    public string $project_id = '';

    public string $title = '';

    public string $text = '';

    public string $video = '';

    /**
     * Fill from AdminSuccessQuery::find(); legacy's "0" means empty.
     *
     * @param  object{category_id: int, serves_id: int, kind_id: int|null, place_id: int, centre_id: int|null, project_id: int|null, name: string, text: string, video: string}  $success
     */
    public function fillFrom(object $success): void
    {
        $this->what_category_id = (string) $success->category_id;
        $this->who_category_id = (string) $success->serves_id;
        $this->sub_what_category_id = (string) $success->kind_id;
        $this->place_id = (string) $success->place_id;
        $this->sz_center_id = (string) $success->centre_id;
        $this->project_id = (string) $success->project_id;
        $this->title = (string) $success->name;
        $this->text = (string) $success->text;
        $this->video = LegacyValue::read($success->video);
    }

    public function category(): int
    {
        return (int) $this->what_category_id;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $category = $this->category();
        $needsPlace = in_array($category, [ContentLookups::DISC, ContentLookups::STUDY_ZONE], true);

        return [
            'what_category_id' => ['required', Rule::in(ContentLookups::SUCCESS_CATEGORIES)],
            'who_category_id' => ['required', Rule::in(ContentLookups::whoCategories()->pluck('id')->all())],
            'sub_what_category_id' => [Rule::requiredIf($category === ContentLookups::DISC), 'nullable', Rule::in(ContentLookups::discKinds()->pluck('id')->all())],
            'place_id' => [Rule::requiredIf($needsPlace), 'nullable', Rule::in(ContentLookups::places()->pluck('id')->all())],
            'sz_center_id' => [Rule::requiredIf($category === ContentLookups::STUDY_ZONE), 'nullable', Rule::in(ContentLookups::studyCenters()->pluck('id')->all())],
            'project_id' => [Rule::requiredIf($category === ContentLookups::MICRO_PROJECTS), 'nullable', 'integer', Rule::exists('projects', 'id')],
            'title' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:200000', fn (string $attribute, mixed $value, \Closure $fail) => trim(strip_tags((string) $value)) === '' ? $fail('The text is required.') : null],
            'video' => ['nullable', 'url:http,https', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'what_category_id' => 'category',
            'who_category_id' => 'who it serves',
            'sub_what_category_id' => 'kind',
            'place_id' => 'place',
            'sz_center_id' => 'centre',
            'project_id' => 'micro project',
        ];
    }

    /**
     * What SaveSuccess takes: validated, typed, with only what the category uses and the text cut to the tags the editor makes.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validate();
        $category = (int) $data['what_category_id'];
        $empty = fn (string $value): ?string => trim($value) === '' ? null : trim($value);

        return [
            'what_category_id' => $category,
            'who_category_id' => (int) $data['who_category_id'],
            'sub_what_category_id' => $category === ContentLookups::DISC ? (int) $data['sub_what_category_id'] : null,
            'place_id' => in_array($category, [ContentLookups::DISC, ContentLookups::STUDY_ZONE], true) ? (int) $data['place_id'] : 0,
            'sz_center_id' => $category === ContentLookups::STUDY_ZONE ? (int) $data['sz_center_id'] : null,
            'project_id' => $category === ContentLookups::MICRO_PROJECTS ? (int) $data['project_id'] : null,
            'title' => trim($data['title']),
            'text' => SafeHtml::text($data['text']),
            'video' => $empty($data['video']),
        ];
    }
}
