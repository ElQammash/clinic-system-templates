@php
    use App\Enums\AppointmentStatus;
    use App\Support\Format;
    $a = $this->appointment;
@endphp
<div>
    <x-modal wire:model="open" :title="__('Appointment')" max-width="max-w-lg">
        @if ($a)
            <div class="space-y-5">
                <div class="flex items-start gap-3">
                    <span class="grid size-12 shrink-0 place-items-center rounded bg-primary-50 text-base font-bold text-primary-700">{{ $a->patient->initials() }}</span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('patients.show', $a->patient) }}" wire:navigate class="block truncate text-lg font-bold text-ink hover:text-primary-700">{{ $a->patient->name }}</a>
                        <p class="text-sm text-muted"><span class="ltr-nums">#{{ $a->patient->file_number }}</span> · <span class="ltr-nums">{{ $a->patient->phone }}</span></p>
                    </div>
                    <x-badge :color="$a->status->color()" class="shrink-0">{{ $a->status->label() }}</x-badge>
                </div>

                <dl class="grid grid-cols-2 gap-4 rounded bg-subtle p-4 text-sm">
                    <div><dt class="text-xs text-muted">{{ __('Date') }}</dt><dd class="mt-0.5 font-semibold">{{ Format::longDate($a->starts_at) }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Time') }}</dt><dd class="mt-0.5 font-semibold ltr-nums">{{ Format::time($a->starts_at) }} – {{ Format::time($a->ends_at) }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Doctor') }}</dt><dd class="mt-0.5 font-semibold">{{ $a->doctor->displayName() }}</dd></div>
                    <div><dt class="text-xs text-muted">{{ __('Visit type') }}</dt><dd class="mt-0.5 font-semibold">{{ $a->visitType?->name() ?? '—' }}</dd></div>
                    @if ($a->queue_number)
                        <div><dt class="text-xs text-muted">{{ __('Queue no.') }}</dt><dd class="mt-0.5 font-semibold">{{ Format::number($a->queue_number) }}</dd></div>
                    @endif
                    <div><dt class="text-xs text-muted">{{ __('Booked via') }}</dt><dd class="mt-0.5">{{ $a->source->label() }} @if ($a->creator) · {{ $a->creator->name }} @endif</dd></div>
                    @if ($a->notes)
                        <div class="col-span-2"><dt class="text-xs text-muted">{{ __('Notes') }}</dt><dd class="mt-0.5">{{ $a->notes }}</dd></div>
                    @endif
                    @if ($a->cancel_reason)
                        <div class="col-span-2"><dt class="text-xs text-muted">{{ __('Cancellation reason') }}</dt><dd class="mt-0.5 text-rose-700">{{ $a->cancel_reason }}</dd></div>
                    @endif
                </dl>

                @if ($a->isActive() && $a->patient->phone && auth()->user()->can('notifications.send'))
                    <button type="button" wire:click="sendReminder" class="flex w-full items-center justify-center gap-2 rounded border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">
                        <x-icon name="whatsapp" class="size-5" /> {{ __('Send WhatsApp reminder') }}
                        @if ($a->reminder_sent_at)<span class="text-xs font-normal">· {{ __('Sent :time', ['time' => $a->reminder_sent_at->diffForHumans()]) }}</span>@endif
                    </button>
                @endif

                @error('status') <div class="rounded-sm bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</div> @enderror

                @if ($cancelling)
                    <div class="space-y-3 rounded border border-rose-200 bg-rose-50/50 p-4">
                        <x-field :label="__('Cancellation reason')" for="cancel-reason" error="cancelReason">
                            <input wire:model="cancelReason" id="cancel-reason" class="form-control" placeholder="{{ __('Optional') }}">
                        </x-field>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="$set('cancelling', false)" class="btn btn-secondary btn-sm">{{ __('Back') }}</button>
                            <button type="button" wire:click="changeStatus('cancelled')" class="btn btn-danger btn-sm">{{ __('Cancel appointment') }}</button>
                        </div>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                @if ($invoiceUrl)
                    <a href="{{ $invoiceUrl }}" wire:navigate class="btn btn-secondary"><x-icon name="receipt" class="size-4" /> {{ __('Invoice') }}</a>
                @endif
                @if ($visitUrl)
                    <a href="{{ $visitUrl }}" wire:navigate class="btn btn-primary"><x-icon name="stethoscope" class="size-4" /> {{ __('Open visit') }}</a>
                @elseif ($canStartVisit && in_array($a->status, [AppointmentStatus::Arrived, AppointmentStatus::InProgress, AppointmentStatus::Confirmed, AppointmentStatus::Booked], true) && $a->starts_at->isToday())
                    <form method="post" action="{{ route('visits.start') }}">
                        @csrf
                        <input type="hidden" name="appointment_id" value="{{ $a->id }}">
                        <button type="submit" class="btn btn-primary"><x-icon name="stethoscope" class="size-4" /> {{ __('Start visit') }}</button>
                    </form>
                @endif
                @if ($canUpdate && $a->isActive() && $a->status !== AppointmentStatus::InProgress)
                    <button type="button" wire:click="reschedule" class="btn btn-secondary me-auto"><x-icon name="calendar" class="size-4" /> {{ __('Reschedule') }}</button>
                @endif
                @if ($canChangeStatus && ! $cancelling)
                    @foreach ($a->status->allowedTransitions() as $next)
                        @if ($next === AppointmentStatus::Cancelled)
                            <button type="button" wire:click="$set('cancelling', true)" class="btn btn-ghost text-rose-600">{{ $next->actionLabel() }}</button>
                        @elseif ($next === AppointmentStatus::NoShow)
                            <button type="button" wire:click="changeStatus('{{ $next->value }}')" wire:confirm="{{ __('Mark this patient as a no-show?') }}" class="btn btn-ghost text-orange-700">{{ $next->actionLabel() }}</button>
                        @else
                            <button type="button" wire:click="changeStatus('{{ $next->value }}')" @class(['btn', 'btn-primary' => $loop->first, 'btn-secondary' => ! $loop->first])>{{ $next->actionLabel() }}</button>
                        @endif
                    @endforeach
                @endif
            </x-slot:footer>
        @endif
    </x-modal>
</div>
