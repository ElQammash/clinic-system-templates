@php
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[190mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $prescription->doctor])

        {{-- Patient line --}}
        <div class="mt-3 grid grid-cols-3 gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs">
            <p class="col-span-2"><span class="text-slate-500">{{ __('Patient') }}:</span> <span class="font-semibold text-slate-900">{{ $patient->name }}</span></p>
            <p class="text-end"><span class="text-slate-500">{{ __('Date') }}:</span> <span class="font-semibold">{{ Format::date($prescription->issued_at) }}</span></p>
            <p><span class="text-slate-500">{{ __('Age') }}:</span> <span class="font-semibold">{{ $patient->age() ?: '—' }}</span></p>
            <p>@if ($visit?->weight)<span class="text-slate-500">{{ __('Weight') }}:</span> <span class="font-semibold">{{ Format::number($visit->weight, 1) }} kg</span>@endif</p>
            <p class="text-end"><span class="text-slate-500">{{ __('File no.') }}:</span> <span class="font-semibold" dir="ltr">{{ $patient->file_number }}</span></p>
        </div>

        {{-- Rx --}}
        <div class="mt-4 flex-1">
            <p class="mb-2 font-serif text-3xl leading-none font-bold text-primary-600 italic">℞</p>
            <ol class="space-y-3">
                @foreach ($prescription->items as $item)
                    <li class="flex gap-3 border-b border-dashed border-slate-200 pb-2">
                        <span class="text-sm font-bold text-primary-600">{{ $loop->iteration }}.</span>
                        <div class="flex-1">
                            <p class="text-[15px] font-bold text-slate-900"><bdi>{{ $item->medicine_name }}</bdi></p>
                            @if ($item->directions())<p class="mt-0.5 text-[13px] text-slate-700">{{ $item->directions() }}</p>@endif
                            @if ($item->instructions)<p class="text-xs text-slate-500">{{ $item->instructions }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($prescription->notes)
                <div class="mt-4 rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700">{{ $prescription->notes }}</div>
            @endif

            @if ($visit?->follow_up_date)
                <p class="mt-3 text-xs font-semibold text-primary-700">{{ __('Follow-up') }}: {{ Format::longDate($visit->follow_up_date) }}</p>
            @endif
        </div>

        {{-- Signature --}}
        <div class="mt-6 flex justify-end">
            <div class="w-40 text-center">
                <div class="h-12 border-b border-slate-400"></div>
                <p class="mt-1 text-[10px] text-slate-500">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('prescription_footer')])
    </div>
</x-print-layout>
