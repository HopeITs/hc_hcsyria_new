<?php

use App\Enums\AdminCapability;
use App\Queries\AdminLogQuery;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/*
 * The bell in the top bar, for those who may see the activity log (the Admin). Shows the
 * entries nobody has looked at yet, not those the signed-in person made themselves, and
 * refreshes every 30 seconds as legacy's did. "Mark all as seen" is shared by every
 * Admin, as in legacy. Replaces get_notifications(), get_notifications_count() and
 * clear_notifications().
 */
new class extends Component
{
    public function markSeen(): void
    {
        $this->authorize('admin.'.AdminCapability::ViewLog->value);
        AdminLogQuery::markAllSeen();
    }

    public function with(): array
    {
        $may = Auth::user()->can('admin.'.AdminCapability::ViewLog->value);

        return [
            'may' => $may,
            'count' => $may ? AdminLogQuery::unseenCount(Auth::id()) : 0,
            'latest' => $may ? AdminLogQuery::unseenLatest(Auth::id()) : collect(),
        ];
    }
};
?>

<div wire:poll.30s>
    @if ($may)
        <x-admin.popover label="Notifications" width="22rem">
            <x-slot:trigger class="ibtn">
                <x-admin.icon name="bell" />
                @if ($count > 0)<span class="dot">{{ $count > 99 ? '99+' : $count }}</span>@endif
            </x-slot:trigger>
            <div class="ph"><b>{{ $count > 0 ? $count.' new '.\Illuminate\Support\Str::plural('change', $count) : 'Nothing new' }}</b></div>
            <ol class="tl" style="padding: 8px 16px">
                @foreach ($latest as $entry)
                    <li>{{ $entry->operation }}<small>{{ $entry->username ?? 'Unknown' }} · {{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('j M, H:i') }}</small></li>
                @endforeach
            </ol>
            <div class="sep"></div>
            @if ($count > 0)<button type="button" class="mi" wire:click="markSeen"><x-admin.icon name="check" />Mark all as seen</button>@endif
            <a href="{{ route('admin.log') }}" wire:navigate class="mi"><x-admin.icon name="log" />Open the activity log</a>
        </x-admin.popover>
    @endif
</div>
