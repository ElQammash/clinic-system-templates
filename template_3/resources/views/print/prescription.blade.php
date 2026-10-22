@php
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[190mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $prescription->doctor])

        {{-- Patient chips --}}
        <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
            <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-extrabold text-primary-700">{{ $patient->name }}</span>
            <span class="chip">{{ __('Age') }}: <b>{{ $patient->age() ?: '—' }}</b></span>
            @if ($visit?->weight)<span class="chip">{{ __('Weight') }}: <b dir="ltr">{{ Format::number($visit->weight, 1) }} kg</b></span>@endif
            <span class="chip">{{ __('File no.') }}: <b dir="ltr">{{ $patient->file_number }}</b></span>
            <span class="chip ms-auto">{{ Format::date($prescription->issued_at) }}</span>
        </div>

        {{-- Rx --}}
        <div class="mt-4 flex-1">
            <div class="mb-3 flex items-center gap-2">
                <span class="grid size-11 place-items-center rounded-full bg-primary-500 font-serif text-2xl font-bold text-white italic">℞</span>
                @if ($visit?->diagnosis)<span class="text-sm text-muted">{{ __('Diagnosis') }}: <b class="text-ink">{{ $visit->diagnosis }}</b></span>@endif
            </div>
            <ol class="space-y-2">
                @foreach ($prescription->items as $item)
                    <li class="flex gap-3 rounded-2xl border-2 border-line px-3 py-2">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-cream text-xs font-extrabold text-primary-700">{{ $loop->iteration }}</span>
                        <div class="flex-1">
                            <p class="text-[15px] font-extrabold"><bdi>{{ $item->medicine_name }}</bdi></p>
                            @if ($item->directions())<p class="text-[13px] text-stone-700">{{ $item->directions() }}</p>@endif
                            @if ($item->instructions)<p class="text-xs text-muted">{{ $item->instructions }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($prescription->notes)
                <div class="mt-3 rounded-2xl bg-cream/60 px-3 py-2 text-xs">📝 {{ $prescription->notes }}</div>
            @endif

            @if ($visit?->follow_up_date)
                <p class="mt-3 inline-block rounded-full bg-primary-50 px-3 py-1 text-xs font-extrabold text-primary-700">📅 {{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</p>
            @endif
        </div>

        <div class="mt-5 flex items-end justify-between">
            <p class="text-sm font-extrabold text-primary-600">{{ __('Get well soon!') }} 💐</p>
            <div class="w-40 text-center">
                <div class="h-12 border-b-2 border-dashed border-line"></div>
                <p class="mt-1 text-[10px] text-muted">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('prescription_footer')])
    </div>
</x-print-layout>
