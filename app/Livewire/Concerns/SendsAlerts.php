<?php

namespace App\Livewire\Concerns;

/*
 * The one way a dashboard page tells the staff member what happened: a toast.
 * The wording is looked up under admin.alerts in lang/en/admin.php, so no page
 * words a message itself.
 *
 *   $this->alert('saved', ['number' => 3071]);
 *   $this->alert('refusals.not_ready', [...], 'error');   // a key outside 'alerts' starts with its group
 *   $this->flashAlert('deleted', [...]);                  // shown on the next page, after a redirect
 *
 * `undo` is a call on this same component, [method, ...args]; the toast shows an
 * Undo button that makes it. The method checks permission again, as for any call.
 */
trait SendsAlerts
{
    /**
     * @param  array<string, scalar>  $params
     * @param  list<scalar>|null  $undo
     */
    protected function alert(string $key, array $params = [], string $type = 'success', ?array $undo = null): void
    {
        $this->dispatch('admin-alert', ...$this->alertPayload($key, $params, $type, $undo));
    }

    /** @param array<string, scalar> $params */
    protected function flashAlert(string $key, array $params = [], string $type = 'success'): void
    {
        session()->flash('admin-alert', $this->alertPayload($key, $params, $type, null));
    }

    /**
     * @param  array<string, scalar>  $params
     * @param  list<scalar>|null  $undo
     * @return array{type: string, message: string, undo: array{component: string, call: list<scalar>}|null}
     */
    private function alertPayload(string $key, array $params, string $type, ?array $undo): array
    {
        $line = str_contains($key, '.') && ! str_starts_with($key, 'state.') && ! str_starts_with($key, 'state_many.')
            ? 'admin.'.$key
            : 'admin.alerts.'.$key;

        return [
            'type' => $type,
            'message' => __($line, $params),
            'undo' => $undo === null ? null : ['component' => $this->getId(), 'call' => $undo],
        ];
    }
}
