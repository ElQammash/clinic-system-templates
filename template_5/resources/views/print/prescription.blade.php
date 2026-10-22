@php
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[194mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $prescription->doctor])

        <div class="mt-3 grid grid-cols-[1fr_auto] gap-2">
            <div class="box">
                <p class="label">{{ __('Patient') }}</p>
                <p class="text-lg leading-tight font-extrabold">{{ $patient->name }}</p>
                <p class="mt-0.5 text-xs font-semibold">
                    {{ __('Age') }}: {{ $patient->age() ?: '—' }}
                    @if ($visit?->weight) · {{ __('Weight') }}: <span dir="ltr">{{ Format::number($visit->weight, 1) }} kg</span>@endif
                    · {{ Format::date($prescription->issued_at) }}
                </p>
            </div>
            <div class="grid place-content-center rounded-xl bg-slate-900 px-4 text-center text-white">
                <p class="text-[10px] font-bold opacity-80">{{ __('File no.') }}</p>
                <p class="text-2xl leading-none font-extrabold" dir="ltr">{{ $patient->file_number }}</p>
            </div>
        </div>

        <div class="mt-4 flex-1">
            <div class="mb-2 flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-primary-600 text-xl font-extrabold text-white">Rx</span>
                @if ($visit?->diagnosis)<p class="text-sm"><span class="label">{{ __('Diagnosis') }}:</span> <b>{{ $visit->diagnosis }}</b></p>@endif
            </div>
            <ol class="space-y-2">
                @foreach ($prescription->items as $item)
                    <li class="flex gap-3 rounded-xl border-2 border-slate-300 p-2.5">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary-100 text-base font-extrabold text-primary-900">{{ $loop->iteration }}</span>
                        <div class="flex-1">
                            <p class="text-[17px] leading-tight font-extrabold"><bdi>{{ $item->medicine_name }}</bdi></p>
                            @if ($item->directions())<p class="mt-0.5 text-[15px] font-semibold">{{ $item->directions() }}</p>@endif
                            @if ($item->instructions)<p class="text-[13px] text-slate-700">{{ $item->instructions }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($prescription->notes)
                <div class="box mt-3 text-sm font-semibold">{{ $prescription->notes }}</div>
            @endif

            @if ($visit?->follow_up_date)
                <p class="mt-3 inline-block rounded-xl bg-primary-600 px-3 py-1.5 text-sm font-extrabold text-white">{{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</p>
            @endif
        </div>

        <div class="mt-5 flex justify-end">
            <div class="w-44 text-center">
                <div class="h-12 border-b-2 border-slate-900"></div>
                <p class="label mt-1">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('prescription_footer')])
    </div>
</x-print-layout>
