@php use App\Support\Format; @endphp
<div>
    <x-page-header emoji="stethoscope" :title="__('Visits')" :subtitle="__('Medical records of past and current visits.')" />

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-stone-400" />
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search by name, phone or file number') }}" class="form-control ps-10">
            </div>
            <div class="flex items-center gap-2">
                <input wire:model.live="from" type="date" class="form-control w-40" aria-label="{{ __('From') }}">
                <span class="text-muted">—</span>
                <input wire:model.live="to" type="date" class="form-control w-40" aria-label="{{ __('To') }}">
            </div>
            @if (auth()->user()->can('records.view_all') || settings('doctors_share_records') || ! auth()->user()->isDoctor())
                <select wire:model.live="doctorId" class="form-control lg:w-52">
                    <option value="0">{{ __('All doctors') }}</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}">{{ $doctor->displayName() }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Patient') }}</th>
                        <th class="hidden md:table-cell">{{ __('Diagnosis') }}</th>
                        <th class="hidden lg:table-cell">{{ __('Doctor') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($visits as $visit)
                        <tr wire:key="v-{{ $visit->id }}" class="cursor-pointer" onclick="Livewire.navigate('{{ route('visits.show', $visit) }}')">
                            <td class="whitespace-nowrap"><span class="font-medium text-ink">{{ Format::date($visit->visited_at) }}</span><br><span class="text-xs text-muted ltr-nums">{{ Format::time($visit->visited_at) }}</span></td>
                            <td><span class="font-semibold text-ink">{{ $visit->patient->name }}</span><br><span class="text-xs text-muted ltr-nums">#{{ $visit->patient->file_number }}</span></td>
                            <td class="hidden max-w-xs truncate md:table-cell">{{ $visit->diagnosis ?: '—' }}</td>
                            <td class="hidden lg:table-cell">{{ $visit->doctor->displayName() }}</td>
                            <td>
                                @if ($visit->isCompleted())<x-badge color="emerald">{{ __('Completed') }}</x-badge>@else<x-badge color="violet">{{ __('In progress') }}</x-badge>@endif
                                @if ($visit->prescription)<x-badge color="primary" class="ms-1">℞</x-badge>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-14 text-center text-muted">{{ __('No visits found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visits->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $visits->links() }}</div>
        @endif
    </div>
</div>
