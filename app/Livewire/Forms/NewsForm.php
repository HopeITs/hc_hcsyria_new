<?php

namespace App\Livewire\Forms;

use App\Queries\ContentLookups;
use App\Support\Admin\LegacyValue;
use App\Support\SafeHtml;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/*
 * The create and edit form of a newsletter item: its fields, its rules and what is
 * handed to SaveNews. One object for both pages. As in legacy: a kind, a place, a date,
 * a title, text, and at least one tag; a video item needs its video link. The tags are
 * ones that exist, and new ones typed in the box (separated by commas), which are made
 * when the item is saved. The attachment is on the page itself (it is an upload).
 *
 * Fields left empty are sent as null; SaveNews stores legacy's "0".
 */
class NewsForm extends Form
{
    public string $type = '0';

    public string $place_id = '';

    public string $edition_id = '';

    public string $rank = '1';

    public string $date = '';

    public string $video = '';

    public string $title = '';

    public string $description = '';

    public string $content = '';

    /** @var list<string> ids of the existing tags ticked */
    public array $tags = [];

    /** New tags, separated by commas. */
    public string $newTags = '';

    /**
     * Fill from AdminNewsQuery::find() and ::tags(); legacy's "0" means empty.
     *
     * @param  object{type: int, place_id: int, edition_id: int|null, rank: int|null, date: string|null, video: string, name: string, description: string|null, content: string}  $news
     * @param  iterable<object{id: int}>  $tags
     */
    public function fillFrom(object $news, iterable $tags): void
    {
        $this->type = (string) $news->type;
        $this->place_id = (string) $news->place_id;
        $this->edition_id = (string) $news->edition_id;
        $this->rank = (string) $news->rank;
        $this->date = (string) $news->date;
        $this->video = LegacyValue::read($news->video);
        $this->title = (string) $news->name;
        $this->description = LegacyValue::read($news->description);
        $this->content = (string) $news->content;
        $this->tags = array_values(array_map(fn (object $tag): string => (string) $tag->id, [...$tags]));
    }

    /**
     * The new tag names typed in, trimmed and without repeats.
     *
     * @return list<string>
     */
    public function newTagNames(): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $this->newTags)), fn (string $name): bool => $name !== '')));
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_map('strval', array_keys(ContentLookups::NEWS_TYPES)))],
            'place_id' => ['required', Rule::in(ContentLookups::places()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
            'edition_id' => ['nullable', Rule::in(ContentLookups::editions()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
            'rank' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'date' => ['required', 'date'],
            'video' => [Rule::requiredIf((int) $this->type === ContentLookups::NEWS_VIDEO), 'nullable', 'url:http,https', 'max:100'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:900'],
            'content' => ['required', 'string', 'max:500000', fn (string $attribute, mixed $value, \Closure $fail) => trim(strip_tags((string) $value)) === '' ? $fail('The content is required.') : null],
            'tags' => ['array'],
            'tags.*' => [Rule::in(ContentLookups::tags()->pluck('id')->map(fn ($id): string => (string) $id)->all())],
            'newTags' => ['nullable', 'string', 'max:500', fn (string $attribute, mixed $value, \Closure $fail) => collect($this->newTagNames())->contains(fn (string $name): bool => mb_strlen($name) > 50) ? $fail('A tag can have at most 50 characters.') : null],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'place_id' => 'place',
            'edition_id' => 'edition',
            'tags' => 'tags',
            'newTags' => 'new tags',
        ];
    }

    /**
     * What SaveNews takes: validated, typed, the content cut to the tags the editor makes, and the tags as
     * ids that exist and names to create. At least one tag is needed, as in legacy.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = $this->validate();
        $empty = fn (?string $value): ?string => $value === null || trim($value) === '' ? null : trim($value);

        if ($data['tags'] === [] && $this->newTagNames() === []) {
            throw ValidationException::withMessages(['tags' => 'Choose at least one tag, or type a new one.']);
        }

        return [
            'type' => (int) $data['type'],
            'place_id' => (int) $data['place_id'],
            'edition_id' => $empty($data['edition_id']) === null ? null : (int) $data['edition_id'],
            'rank' => $empty($data['rank']) === null ? 1 : (int) $data['rank'],
            'date' => $data['date'],
            'video' => $empty($data['video']),
            'title' => trim($data['title']),
            'description' => $empty($data['description']),
            'content' => SafeHtml::text($data['content']),
            'tag_ids' => array_values(array_unique(array_map('intval', $data['tags']))),
            'new_tags' => $this->newTagNames(),
        ];
    }
}
