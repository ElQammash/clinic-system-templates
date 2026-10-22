@php
    use App\Support\Format;
    $patient = $prescription->patient;
    $visit = $prescription->visit;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[188mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $prescription->doctor])

        <dl class="mt-6 grid grid-cols-4 border-y border-line py-3 text-center text-[12px]">
            <div class="col-span-2 border-e border-line"><dt class="cap">{{ __('Patient') }}</dt><dd class="mt-1 font-serif text-base">{{ $patient->name }}</dd></div>
            <div class="border-e border-line"><dt class="cap">{{ __('Age') }}</dt><dd class="mt-1">{{ $patient->age() ?: '—' }}</dd></div>
            <div><dt class="cap">{{ __('Date') }}</dt><dd class="mt-1">{{ Format::date($prescription->issued_at) }}</dd></div>
        </dl>
        <p class="cap mt-2 text-center">
            {{ __('File no.') }} <span dir="ltr">{{ $patient->file_number }}</span>
            @if ($visit?->weight) · {{ __('Weight') }} <span dir="ltr">{{ Format::number($visit->weight, 1) }} kg</span>@endif
        </p>

        <div class="mt-6 flex-1">
            <p class="font-serif text-4xl leading-none text-primary-600 italic">℞</p>
            <ol class="mt-4 space-y-4">
                @foreach ($prescription->items as $item)
                    <li class="grid grid-cols-[1.5rem_1fr] gap-2">
                        <span class="font-serif text-base text-primary-600">{{ $loop->iteration }}.</span>
                        <div>
                            <p class="font-serif text-lg leading-tight"><bdi>{{ $item->medicine_name }}</bdi></p>
                            @if ($item->directions())<p class="mt-0.5 text-[12px] text-neutral-700">{{ $item->directions() }}</p>@endif
                            @if ($item->instructions)<p class="text-[11px] text-muted italic">{{ $item->instructions }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($prescription->notes)
                <p class="mt-6 font-serif text-sm text-neutral-700 italic">{{ $prescription->notes }}</p>
            @endif

            @if ($visit?->follow_up_date)
                <p class="cap mt-4">{{ __('Follow-up') }} — <span class="font-serif text-xs tracking-normal text-ink normal-case">{{ Format::longDate($visit->follow_up_date) }}</span></p>
            @endif
        </div>

        <div class="mt-8 flex justify-end">
            <div class="w-40 text-center">
                <div class="h-12 border-b border-ink"></div>
                <p class="cap mt-1.5">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => settings()->localized('prescription_footer')])
    </div>
</x-print-layout>
