@php use App\Support\Format; @endphp
<div>
    <x-page-header emoji="whatsapp" :title="__('Reminders')" :subtitle="__('Send appointment reminders on WhatsApp — one click per patient.')" />

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
                <button type="button" wire:click="$set('date', '{{ today()->toDateString() }}')" @class(['btn btn-sm', 'btn-primary' => $day->isToday(), 'btn-ghost' => ! $day->isToday()])>{{ __('Today') }}</button>
                <button type="button" wire:click="$set('date', '{{ today()->addDay()->toDateString() }}')" @class(['btn btn-sm', 'btn-primary' => $day->isTomorrow(), 'btn-ghost' => ! $day->isTomorrow()])>{{ __('Tomorrow') }}</button>
                <input type="date" wire:model.live="date" class="form-control w-44 py-1.5">
                <span class="ms-auto text-sm text-muted">{{ Format::longDate($day) }}</span>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($appointments as $a)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-3.5" wire:key="rem-{{ $a->id }}">
                        <span class="w-16 shrink-0 font-semibold ltr-nums">{{ Format::time($a->starts_at) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-ink">{{ $a->patient->name }}</p>
                            <p class="text-xs text-muted"><span class="ltr-nums">{{ $a->patient->phone ?? __('No phone') }}</span> · {{ $a->doctor->displayName() }}</p>
                        </div>
                        @if ($a->reminder_sent_at)
                            <x-badge color="emerald"><x-icon name="check" class="size-3.5" /> {{ __('Sent :time', ['time' => $a->reminder_sent_at->diffForHumans()]) }}</x-badge>
                        @endif
                        @if ($a->patient->phone)
                            <button type="button" wire:click="send({{ $a->id }})" @class(['btn btn-sm', 'btn-secondary' => $a->reminder_sent_at, 'btn-primary' => ! $a->reminder_sent_at])>
                                <x-icon name="whatsapp" class="size-4" /> {{ $a->reminder_sent_at ? __('Send again') : __('WhatsApp') }}
                            </button>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-muted">{{ __('No booked appointments on this day.') }}</li>
                @endforelse
            </ul>
        </section>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="mb-3 font-semibold text-ink">{{ __('Message preview') }}</h2>
                @if ($preview)
                    <div class="rounded-3xl rounded-ss-sm bg-[#dcf8c6] px-4 py-3 text-sm leading-relaxed whitespace-pre-line text-stone-800 shadow-none" dir="auto">{{ $preview }}</div>
                @else
                    <p class="text-sm text-muted">{{ __('No appointments to preview.') }}</p>
                @endif
                @can('settings.manage')
                    <a href="{{ route('settings.clinic', ['tab' => 'messages']) }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-primary-700 hover:underline">{{ __('Edit the message') }}</a>
                @endcan
            </div>
            @unless ($automatic)
                <p class="rounded-2xl bg-stone-100 px-4 py-3 text-xs leading-relaxed text-stone-600">{{ __('Each button opens WhatsApp with the message ready — just press send. No paid service is needed. An SMS or WhatsApp API provider can be connected later to send automatically.') }}</p>
            @endunless
        </aside>
    </div>
</div>
