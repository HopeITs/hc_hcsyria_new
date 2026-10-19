<?php

namespace App\Livewire\Forms;

use Livewire\Form;

/*
 * The create and edit form of a magazine edition: a title and the link to its online
 * (flipbook) version, both required as in legacy. The cover and the PDF are uploads
 * on the page itself. The columns are short (title 50, link 100): legacy checked 150 and
 * the database then refused or cut the text; the real limits are checked here.
 */
class RootsForm extends Form
{
    public string $title = '';

    public string $url = '';

    /** @param object{name: string, url: string} $edition from AdminRootsQuery::find() */
    public function fillFrom(object $edition): void
    {
        $this->title = (string) $edition->name;
        $this->url = (string) $edition->url;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50'],
            'url' => ['required', 'url:https,http', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['url' => 'online link'];
    }

    /** @return array{title: string, url: string} */
    public function payload(): array
    {
        $data = $this->validate();

        return ['title' => trim($data['title']), 'url' => trim($data['url'])];
    }
}
