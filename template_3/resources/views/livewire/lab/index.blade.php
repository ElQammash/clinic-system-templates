@php use App\Enums\LabRequestStatus; use App\Support\Format; @endphp
<div>
    <x-page-header emoji="lab" :title="__('Lab & imaging')" :subtitle="__('Requested tests and their results.')" />

    <div class="mb-4 inline-flex rounded-2xl bg-white p-1 shadow-(--shadow-soft)">
        @foreach (['requests' => __('Requests'), 'catalog' => __('Tests catalog')] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition', 'bg-primary-600 text-white shadow-sm' => $tab === $key, 'text-muted hover:text-ink' => $tab !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'requests')
        <div class="card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="{{ __('Search by name, phone or file number') }}" class="form-control flex-1">
                <div class="flex gap-1">
                    @foreach ($statuses as $s)
                        <button type="button" wire:click="$set('status', '{{ $s->value }}')" @class(['btn btn-sm', 'btn-primary' => $status === $s->value, 'btn-ghost' => $status !== $s->value])>{{ $s->label() }}</button>
                    @endforeach
                </div>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($requests as $request)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4" wire:key="lab-{{ $request->id }}">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('patients.show', $request->patient) }}" wire:navigate class="font-semibold text-ink hover:text-primary-700">{{ $request->patient->name }}</a>
                            <p class="truncate text-sm text-stone-600">{{ $request->items->pluck('name')->implode('، ') }}</p>
                            <p class="text-xs text-muted">{{ Format::dateTime($request->requested_at) }} · {{ $request->doctor->displayName() }}</p>
                        </div>
                        <x-badge :color="$request->status->color()">{{ $request->status->label() }}</x-badge>
                        <a href="{{ route('print.lab-request', $request) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="print" class="size-4" /></a>
                        @if ($canManage && $request->status !== LabRequestStatus::Cancelled)
                            <button type="button" wire:click="openResults({{ $request->id }})" class="btn btn-secondary btn-sm">{{ $request->status === LabRequestStatus::Completed ? __('Edit results') : __('Enter results') }}</button>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-muted">{{ __('No requests.') }}</li>
                @endforelse
            </ul>
            @if ($requests->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $requests->links() }}</div>
            @endif
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            @if ($canManage)
                <form wire:submit="addTest" class="card space-y-4 p-5">
                    <h2 class="font-semibold text-ink">{{ __('Add test') }}</h2>
                    <x-field :label="__('Name')" for="t-name" error="testName"><input wire:model="testName" id="t-name" class="form-control"></x-field>
                    <x-field :label="__('Type')" for="t-type" error="testType">
                        <select wire:model="testType" id="t-type" class="form-control">
                            @foreach (App\Enums\LabTestType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach
                        </select>
                    </x-field>
                    <button type="submit" class="btn btn-primary w-full">{{ __('Add') }}</button>
                </form>
            @endif
            <div class="card overflow-hidden lg:col-span-2">
                <ul class="divide-y divide-line">
                    @foreach ($catalog as $test)
                        <li class="flex items-center gap-3 px-5 py-3" wire:key="lt-{{ $test->id }}">
                            <span @class(['flex-1 text-sm', 'text-ink' => $test->is_active, 'text-muted line-through' => ! $test->is_active])>{{ $test->name }}</span>
                            <x-badge :color="$test->type->value === 'lab' ? 'violet' : 'blue'">{{ $test->type->label() }}</x-badge>
                            @if ($canManage)
                                <button type="button" wire:click="toggleTest({{ $test->id }})" class="btn btn-ghost btn-sm">{{ $test->is_active ? __('Disable') : __('Enable') }}</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($editing)
        <div>
            <x-modal wire:model="showResults" :title="__('Results') . ' · ' . $editing->patient->name">
                <form id="results-form" wire:submit="saveResults" class="space-y-4">
                    @foreach ($editing->items as $item)
                        <x-field :label="$item->name" :for="'r-'.$item->id" :error="'results.'.$item->id">
                            <input wire:model="results.{{ $item->id }}" id="r-{{ $item->id }}" class="form-control" placeholder="{{ __('Result value, e.g. Hb 12.5 g/dL') }}">
                        </x-field>
                    @endforeach
                    <x-field :label="__('Summary')" for="r-summary" error="summary">
                        <textarea wire:model="summary" id="r-summary" rows="2" class="form-control"></textarea>
                    </x-field>
                    <x-field :label="__('Result files (PDF / images)')" for="r-files" error="files">
                        <input wire:model="files" id="r-files" type="file" multiple class="form-control">
                    </x-field>
                    <div wire:loading wire:target="files" class="text-sm text-primary-700">{{ __('Uploading…') }}</div>
                </form>
                <x-slot:footer>
                    <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" form="results-form" class="btn btn-primary" wire:loading.attr="disabled">{{ __('Save results') }}</button>
                </x-slot:footer>
            </x-modal>
        </div>
    @endif
</div>
