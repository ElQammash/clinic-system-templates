@php
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[194mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $prescription->doctor])

        {{-- Patient grid + file-number block --}}
        <div class="mt-3 flex border border-neutral-900">
            <div class="grid flex-1 grid-cols-3 text-[11px]">
                <div class="cell col-span-2 border-e border-b"><p class="cap">{{ __('Patient') }}</p><p class="font-semibold">{{ $patient->name }}</p></div>
                <div class="cell border-b"><p class="cap">{{ __('Date') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $prescription->issued_at->format('Y-m-d') }}</p></div>
                <div class="cell border-e"><p class="cap">{{ __('Age') }}</p><p class="font-semibold">{{ $patient->age() ?: '—' }}</p></div>
                <div class="cell border-e"><p class="cap">{{ __('Gender') }}</p><p class="font-semibold">{{ $patient->gender?->label() ?? '—' }}</p></div>
                <div class="cell"><p class="cap">{{ __('Weight') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $visit?->weight ? Format::number($visit->weight, 1).' kg' : '—' }}</p></div>
            </div>
            <div class="flex w-24 flex-col items-center justify-center border-s border-neutral-900 bg-neutral-950 px-2 text-white">
                <p class="text-[8px] tracking-[0.2em] text-neutral-400 uppercase">{{ __('File no.') }}</p>
                <p class="mt-0.5 font-mono text-lg leading-none font-bold" dir="ltr">{{ $patient->file_number }}</p>
            </div>
        </div>

        @if ($visit?->diagnosis)
            <p class="mt-2 text-[11px]"><span class="cap">{{ __('Diagnosis') }}</span> <span class="font-semibold">{{ $visit->diagnosis }}</span></p>
        @endif

        {{-- Rx --}}
        <div class="mt-3 flex-1">
            <div class="flex items-center gap-2 border-b-2 border-neutral-900 pb-1">
                <span class="font-mono text-xl leading-none font-bold">Rx</span>
                <span class="ms-auto font-mono text-[9px] text-neutral-500">{{ __('Items') }}: {{ $prescription->items->count() }}</span>
            </div>
            <ol>
                @foreach ($prescription->items as $item)
                    <li class="flex gap-3 border-b border-neutral-300 py-2">
                        <span class="w-5 shrink-0 font-mono text-xs font-bold text-primary-600">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="flex-1">
                            <p class="text-[14px] font-bold"><bdi>{{ $item->medicine_name }}</bdi></p>
                            @if ($item->directions())<p class="text-[12px] text-neutral-800">{{ $item->directions() }}</p>@endif
                            @if ($item->instructions)<p class="text-[11px] text-neutral-500">{{ $item->instructions }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($prescription->notes)
                <div class="mt-3 border-s-2 border-neutral-900 ps-2.5 text-[11px] text-neutral-800">{{ $prescription->notes }}</div>
            @endif

            @if ($visit?->follow_up_date)
                <p class="mt-3 inline-block border border-neutral-900 px-2 py-1 text-[11px] font-semibold">{{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</p>
            @endif
        </div>

        <div class="mt-5 flex justify-end">
            <div class="w-40">
                <div class="h-10 border-b border-neutral-900"></div>
                <p class="cap mt-1 text-center">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('prescription_footer')])
    </div>
</x-print-layout>
