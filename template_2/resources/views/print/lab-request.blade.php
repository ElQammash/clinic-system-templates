@php
    use App\Support\Format;
    $patient = $labRequest->patient;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[194mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $labRequest->doctor])

        <p class="cap mt-4">{{ __('Lab & imaging request') }}</p>

        <div class="mt-1 flex border border-neutral-900">
            <div class="grid flex-1 grid-cols-2 text-[11px]">
                <div class="cell border-e border-b"><p class="cap">{{ __('Patient') }}</p><p class="font-semibold">{{ $patient->name }}</p></div>
                <div class="cell border-b"><p class="cap">{{ __('Date') }}</p><p class="font-mono font-semibold" dir="ltr">{{ $labRequest->requested_at->format('Y-m-d') }}</p></div>
                <div class="cell border-e"><p class="cap">{{ __('Age') }}</p><p class="font-semibold">{{ $patient->age() ?: '—' }}</p></div>
                <div class="cell"><p class="cap">{{ __('Gender') }}</p><p class="font-semibold">{{ $patient->gender?->label() ?? '—' }}</p></div>
            </div>
            <div class="flex w-24 flex-col items-center justify-center border-s border-neutral-900 bg-neutral-950 px-2 text-white">
                <p class="text-[8px] tracking-[0.2em] text-neutral-400 uppercase">{{ __('File no.') }}</p>
                <p class="mt-0.5 font-mono text-lg leading-none font-bold" dir="ltr">{{ $patient->file_number }}</p>
            </div>
        </div>

        <ol class="mt-4 flex-1">
            @foreach ($labRequest->items as $item)
                <li class="flex items-center gap-3 border-b border-neutral-300 py-1.5 text-[13px]">
                    <span class="size-3 shrink-0 border border-neutral-900"></span>
                    <span class="w-5 font-mono text-[10px] text-neutral-500">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="font-semibold">{{ $item->name }}</span>
                </li>
            @endforeach
        </ol>

        @if ($labRequest->notes)
            <p class="mt-3 border-s-2 border-neutral-900 ps-2.5 text-[11px]">{{ $labRequest->notes }}</p>
        @endif

        <div class="mt-5 flex justify-end">
            <div class="w-40">
                <div class="h-10 border-b border-neutral-900"></div>
                <p class="cap mt-1 text-center">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => null])
    </div>
</x-print-layout>
