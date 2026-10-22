@php
    use App\Support\Format;
    $patient = $labRequest->patient;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[194mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $labRequest->doctor])

        <p class="mt-4 text-2xl font-extrabold">{{ __('Lab & imaging request') }}</p>

        <div class="mt-2 grid grid-cols-[1fr_auto] gap-2">
            <div class="box">
                <p class="label">{{ __('Patient') }}</p>
                <p class="text-lg leading-tight font-extrabold">{{ $patient->name }}</p>
                <p class="text-xs font-semibold">{{ __('Age') }}: {{ $patient->age() ?: '—' }} @if ($patient->gender)· {{ $patient->gender->label() }}@endif · {{ Format::date($labRequest->requested_at) }}</p>
            </div>
            <div class="grid place-content-center rounded-xl bg-slate-900 px-4 text-center text-white">
                <p class="text-[10px] font-bold opacity-80">{{ __('File no.') }}</p>
                <p class="text-2xl leading-none font-extrabold" dir="ltr">{{ $patient->file_number }}</p>
            </div>
        </div>

        <ol class="mt-4 flex-1 space-y-2">
            @foreach ($labRequest->items as $item)
                <li class="flex items-center gap-3 rounded-xl border-2 border-slate-300 p-2.5 text-[17px] font-extrabold">
                    <span class="grid size-8 place-items-center rounded-lg bg-primary-100 text-base text-primary-900">{{ $loop->iteration }}</span>
                    {{ $item->name }}
                </li>
            @endforeach
        </ol>

        @if ($labRequest->notes)
            <p class="box mt-3 text-sm font-semibold">{{ $labRequest->notes }}</p>
        @endif

        <div class="mt-5 flex justify-end">
            <div class="w-44 text-center">
                <div class="h-12 border-b-2 border-slate-900"></div>
                <p class="label mt-1">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => null])
    </div>
</x-print-layout>
