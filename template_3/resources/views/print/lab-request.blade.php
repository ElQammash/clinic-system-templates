@php
    use App\Support\Format;
    $patient = $labRequest->patient;
@endphp
<x-print-layout :title="$title" size="a5">
    <div class="flex min-h-[190mm] flex-col">
        @include('print.partials.letterhead', ['doctor' => $labRequest->doctor])

        <h1 class="mt-4 text-center text-xl font-extrabold">🧪 {{ __('Lab & imaging request') }}</h1>

        <div class="mt-3 flex flex-wrap justify-center gap-1.5 text-xs">
            <span class="rounded-full bg-primary-50 px-3 py-1 text-sm font-extrabold text-primary-700">{{ $patient->name }}</span>
            <span class="chip">{{ __('Age') }}: <b>{{ $patient->age() ?: '—' }}</b></span>
            @if ($patient->gender)<span class="chip">{{ $patient->gender->label() }}</span>@endif
            <span class="chip">{{ __('File no.') }}: <b dir="ltr">{{ $patient->file_number }}</b></span>
            <span class="chip">{{ Format::date($labRequest->requested_at) }}</span>
        </div>

        <ol class="mt-5 flex-1 space-y-2 text-sm">
            @foreach ($labRequest->items as $item)
                <li class="flex items-center gap-3 rounded-2xl border-2 border-line px-3 py-2 font-extrabold">
                    <span class="grid size-6 place-items-center rounded-full bg-cream text-xs text-primary-700">{{ $loop->iteration }}</span>
                    {{ $item->name }}
                </li>
            @endforeach
        </ol>

        @if ($labRequest->notes)
            <p class="mt-3 rounded-2xl bg-cream/60 px-3 py-2 text-xs">📝 {{ $labRequest->notes }}</p>
        @endif

        <div class="mt-6 flex justify-end">
            <div class="w-40 text-center">
                <div class="h-12 border-b-2 border-dashed border-line"></div>
                <p class="mt-1 text-[10px] text-muted">{{ __('Doctor\'s signature') }}</p>
            </div>
        </div>

        @include('print.partials.footer', ['footerText' => null])
    </div>
</x-print-layout>
