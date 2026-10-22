@php
    use App\Livewire\Settings\ActivityLog;
    use App\Support\Format;
@endphp
<div>
    <x-page-header emoji="log" :title="__('Activity log')" :subtitle="__('Who opened medical records, changed data, deleted invoices or logged in.')" />

    <div class="card overflow-hidden">
        <div class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-5">
            <select wire:model.live="log" class="form-control">
                <option value="">{{ __('All areas') }}</option>
                @foreach ($logs as $name)<option value="{{ $name }}">{{ __('activity.logs.'.$name) }}</option>@endforeach
            </select>
            <select wire:model.live="event" class="form-control">
                <option value="">{{ __('All actions') }}</option>
                @foreach ($events as $name)<option value="{{ $name }}">{{ trans()->has('activity.'.$name) ? __('activity.'.$name) : $name }}</option>@endforeach
            </select>
            <select wire:model.live="userId" class="form-control">
                <option value="0">{{ __('All users') }}</option>
                @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
            </select>
            <input wire:model.live="from" type="date" class="form-control" aria-label="{{ __('From') }}">
            <input wire:model.live="to" type="date" class="form-control" aria-label="{{ __('To') }}">
        </div>

        <ul class="divide-y divide-line">
            @forelse ($activities as $activity)
                @php
                    $subject = $activity->subject;
                    $subjectLabel = match (true) {
                        $subject instanceof App\Models\Patient => $subject->name,
                        $subject instanceof App\Models\Visit, $subject instanceof App\Models\Prescription => __('Visit').' #'.$subject->id,
                        $subject instanceof App\Models\Invoice => $subject->number,
                        $subject instanceof App\Models\User => $subject->name,
                        $subject instanceof App\Models\Appointment => __('Appointment').' #'.$subject->id,
                        default => null,
                    };
                    $sensitive = in_array($activity->event, ['viewed', 'deleted', 'voided', 'payment_deleted', 'attachment_viewed', 'attachment_downloaded', 'login_failed', 'backup_downloaded'], true);
                @endphp
                <li class="flex flex-wrap items-start gap-4 px-5 py-3" wire:key="act-{{ $activity->id }}">
                    <span @class(['mt-1 size-2.5 shrink-0 rounded-full', 'bg-rose-500' => $sensitive, 'bg-primary-500' => ! $sensitive])></span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p>
                            <span class="font-semibold text-ink">{{ $activity->causer?->name ?? __('System') }}</span>
                            <span class="text-stone-600">{{ ActivityLog::describe($activity) }}</span>
                            @if ($subjectLabel)<span class="font-medium text-ink">— {{ $subjectLabel }}</span>@endif
                        </p>
                        <p class="mt-0.5 text-xs text-muted">{{ Format::dateTime($activity->created_at) }} · {{ __('activity.logs.'.$activity->log_name) }}</p>
                    </div>
                    @if ($activity->attribute_changes?->isNotEmpty() || $activity->properties?->isNotEmpty())
                        <button type="button" wire:click="$set('detailsId', {{ $detailsId === $activity->id ? 'null' : $activity->id }})" class="btn btn-ghost btn-sm">{{ __('Details') }}</button>
                    @endif
                    @if ($details?->id === $activity->id)
                        <pre class="w-full overflow-x-auto rounded-xl bg-stone-50 p-3 text-xs text-stone-700" dir="ltr">{{ json_encode(['changes' => $details->attribute_changes, 'properties' => $details->properties], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                </li>
            @empty
                <li class="px-5 py-14 text-center text-sm text-muted">{{ __('Nothing recorded yet.') }}</li>
            @endforelse
        </ul>
        @if ($activities->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
