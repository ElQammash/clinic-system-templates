@php
    use App\Support\Format;
    $patient = $labRequest->patient;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[188mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $labRequest->doctor])

        <p class="cap mt-8 text-center">{{ __('Lab & imaging request') }}</p>

        <dl class="mt-4 grid grid-cols-3 border-y border-line py-3 text-center text-[12px]">
            <div class="border-e border-line"><dt class="cap">{{ __('Patient') }}</dt><dd class="mt-1 font-serif text-base">{{ $patient->name }}</dd></div>
            <div class="border-e border-line"><dt class="cap">{{ __('Age') }}</dt><dd class="mt-1">{{ $patient->age() ?: '—' }} @if ($patient->gender)· {{ $patient->gender->label() }}@endif</dd></div>
            <div><dt class="cap">{{ __('Date') }}</dt><dd class="mt-1">{{ Format::date($labRequest->requested_at) }}</dd></div>
        </dl>
        <p class="cap mt-2 text-center">{{ __('File no.') }} <span dir="ltr">{{ $patient->file_number }}</span></p>

        <ol class="mt-8 flex-1 space-y-3">
            @foreach ($labRequest->items as $item)
                <li class="grid grid-cols-[1.5rem_1fr] border-b border-line pb-2">
                    <span class="font-serif text-primary-600">{{ $loop->iteration }}.</span>
                    <span class="font-serif text-lg">{{ $item->name }}</span>
                </li>
            @endforeach
        </ol>

        @if ($labRequest->notes)
            <p class="mt-4 font-serif text-sm italic">{{ $labRequest->notes }}</p>
        @endif

        <div class="mt-8 flex justify-end">
            <div class="w-40 text-center">
                <div class="h-12 border-b border-ink"></div>
                <p class="cap mt-1.5">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => null])
    </div>
</x-print-layout>
