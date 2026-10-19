<?php

namespace App\Livewire\Concerns;

use App\Actions\Admin\ChangeContentState;
use App\Actions\Admin\WriteActivityLog;
use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Exceptions\AdminActionRefused;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/*
 * The things a staff member does to a piece of content from a list, the
 * dashboard queue or its own page: move it between states, or delete it. Written
 * once, for every kind of content (the page says which: contentKind()), so every
 * page offers them by the same rules: each call finds the item, asks its policy
 * (the page's buttons are only a convenience, the call is what is enforced), does
 * the work through the actions, and answers with an alert.
 */
trait ManagesContent
{
    use SendsAlerts;

    /** What this page manages. A page of another kind overrides it. */
    protected function contentKind(): ContentKind
    {
        return ContentKind::Project;
    }

    public function moveItem(int $id, string $name): void
    {
        $item = $this->contentKind()->modelClass()::query()->find($id);

        if ($item === null) {
            $this->alert('gone', type: 'error');

            return;
        }

        $transition = ContentTransition::tryFrom($name);

        if ($transition === null || ! $this->contentKind()->supports($transition) || ! $this->mayDo($transition, $item)) {
            $this->alert('forbidden', type: 'error');

            return;
        }

        if (! $transition->appliesTo($item)) {
            return;
        }

        try {
            app(ChangeContentState::class)->handle($item, $transition, Auth::user());
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), $refused->params, 'error');

            return;
        }

        $inverse = $transition->inverse();
        $this->alert(
            'state.'.$transition->value,
            ['number' => $this->contentKind()->number($item)],
            undo: $inverse === null ? null : ['moveItem', $id, $inverse->value],
        );
    }

    /**
     * The same move on several items (the list's selection). Those that are not
     * allowed, not ready or already there are skipped, and counted in the alert.
     *
     * @param  list<int|string>  $ids
     */
    public function transitionMany(array $ids, string $name): void
    {
        $transition = ContentTransition::tryFrom($name);
        $items = $this->contentKind()->modelClass()::query()->whereKey(array_map('intval', $ids))->get();
        $done = 0;

        foreach ($items as $item) {
            if ($transition === null || ! $this->contentKind()->supports($transition) || ! $this->mayDo($transition, $item) || ! $transition->appliesTo($item)) {
                continue;
            }

            try {
                app(ChangeContentState::class)->handle($item, $transition, Auth::user());
                $done++;
            } catch (AdminActionRefused) {
                // Counted as skipped.
            }
        }

        $count = count($ids);
        $skipped = $count - $done;

        match (true) {
            $done === 0 => $this->alert('state_many.none', ['count' => $count], 'error'),
            $skipped === 0 => $this->alert('state_many.done', ['count' => $done]),
            default => $this->alert('state_many.partly', ['done' => $done, 'count' => $count, 'skipped' => $skipped], 'warning'),
        };
    }

    public function deleteItem(int $id): void
    {
        $kind = $this->contentKind();
        $item = $kind->modelClass()::query()->find($id);

        if ($item === null) {
            $this->alert('gone', type: 'error');

            return;
        }

        if (Auth::user()->cannot('delete', $item)) {
            $this->alert('forbidden', type: 'error');

            return;
        }

        $number = $kind->number($item);

        try {
            app($kind->deleteAction())->handle($id);
        } catch (AdminActionRefused $refused) {
            $this->alert($refused->alertKey(), [...$refused->params, 'number' => $number], 'error');

            return;
        }

        app(WriteActivityLog::class)->item(Auth::user(), $kind, $id, $kind->deleteOperation());

        $this->afterDeleted($item);
    }

    /** What a page does once an item is gone: the list just shows an alert, the item's own page leaves. */
    protected function afterDeleted(Model $item): void
    {
        $this->alert('deleted', ['number' => $this->contentKind()->number($item)]);
    }

    private function mayDo(ContentTransition $transition, Model $item): bool
    {
        try {
            return Auth::user()->can($transition->ability(), $item);
        } catch (AuthorizationException) {
            return false;
        }
    }
}
