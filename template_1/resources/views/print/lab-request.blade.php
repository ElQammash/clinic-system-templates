@php
    use App\Support\Format;
    $patient = $labRequest->patient;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[190mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $labRequest->doctor])

        <h1 class="mt-4 text-center text-base font-bold text-slate-900">{{ __('Lab & imaging request') }}</h1>

        <div class="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs">
            <p><span class="text-slate-500">{{ __('Patient') }}:</span> <span class="font-semibold">{{ $patient->name }}</span></p>
            <p class="text-end"><span class="text-slate-500">{{ __('Date') }}:</span> <span class="font-semibold">{{ Format::date($labRequest->requested_at) }}</span></p>
            <p><span class="text-slate-500">{{ __('Age') }}:</span> <span class="font-semibold">{{ $patient->age() ?: '—' }}</span> · {{ $patient->gender?->label() }}</p>
            <p class="text-end"><span class="text-slate-500">{{ __('File no.') }}:</span> <span class="font-semibold" dir="ltr">{{ $patient->file_number }}</span></p>
        </div>

        <ol class="mt-5 flex-1 list-inside list-decimal space-y-2 text-sm">
            @foreach ($labRequest->items as $item)
                <li class="border-b border-dashed border-slate-200 pb-1.5 font-semibold text-slate-900">{{ $item->name }}</li>
            @endforeach
        </ol>

        @if ($labRequest->notes)
            <p class="mt-3 rounded-lg border border-slate-200 px-3 py-2 text-xs">{{ $labRequest->notes }}</p>
        @endif

        <div class="mt-6 flex justify-end">
            <div class="w-40 text-center">
                <div class="h-12 border-b border-slate-400"></div>
                <p class="mt-1 text-[10px] text-slate-500">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => null])
    </div>
</x-print-layout>
