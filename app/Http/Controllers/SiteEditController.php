<?php

namespace App\Http\Controllers;

use App\Actions\Admin\EditSitePage;
use App\Enums\AdminCapability;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/*
 * The visual editor's saves when it runs on the public site itself, for a signed-in account that may
 * change the site (the dashboard's own "Edit the page" saves through its Livewire page instead). Both go
 * through EditSitePage. Each answers JSON {ok, message}; the editor shows the message.
 */
class SiteEditController
{
    public function __construct(private readonly EditSitePage $edit) {}

    public function text(Request $request): JsonResponse
    {
        $data = $request->validate(['where' => ['required', 'string', 'max:200'], 'original' => ['required', 'string', 'max:1500'], 'text' => ['required', 'string', 'max:1500']]);

        return response()->json($this->edit->text($data['where'], $data['original'], $data['text'], $this->user()));
    }

    public function file(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot' => ['nullable', 'string', 'max:100'], 'key' => ['nullable', 'string', 'max:300'], 'src' => ['nullable', 'string', 'max:500'],
            'video' => ['nullable', 'boolean'], 'file' => ['required', 'file'],
        ]);

        return response()->json($this->edit->file((string) ($data['slot'] ?? ''), (string) ($data['key'] ?? ''), (string) ($data['src'] ?? ''), (bool) ($data['video'] ?? false), $request->file('file'), $this->user()));
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:300']]);

        return response()->json($this->edit->reset($data['key'], $this->user()));
    }

    private function user(): User
    {
        Gate::authorize('admin.'.AdminCapability::ManageSite->value);

        /** @var User */
        return auth()->user();
    }
}
