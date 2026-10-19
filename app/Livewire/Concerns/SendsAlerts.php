<?php

namespace App\Livewire\Concerns;

/*
 * The one way a dashboard page tells the staff member what happened: a toast.
 * The wording is looked up under admin.alerts in lang/en/admin.php, so no page
 * words a message itself.
 *
 *   $this->alert('saved', ['number' => 3071]);
 *   $this->alert('refusals.not_ready', [...], 'error');   // the refusals are the one group outside 'alerts'
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
        $line = str_starts_with($key, 'refusals.') ? 'admin.'.$key : 'admin.alerts.'.$key;

        // A message with a count may have a singular and a plural form ("1 photo|2 photos").
        $message = isset($params['count']) ? trans_choice($line, (int) $params['count'], $params) : __($line, $params);

        return [
            'type' => $type,
            'message' => $message,
            'undo' => $undo === null ? null : ['component' => $this->getId(), 'call' => $undo],
        ];
    }
}
