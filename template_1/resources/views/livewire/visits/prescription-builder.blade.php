<section class="card">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
        <h2 class="flex items-center gap-2 font-semibold text-ink">
            <span class="grid size-8 place-items-center rounded-lg bg-primary-600 font-serif text-lg font-bold text-white italic">℞</span>
            {{ __('Prescription') }}
        </h2>
        <div class="flex flex-wrap gap-2">
            @if ($canPrescribe && $templates->isNotEmpty())
                <div x-data="{ open: false }" class="relative">
                    <button type="button" x-on:click="open = !open" class="btn btn-secondary btn-sm"><x-icon name="document" class="size-4" /> {{ __('Templates') }}</button>
                    <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute end-0 z-20 mt-1 max-h-72 w-64 overflow-y-auto rounded-xl border border-line bg-white py-1 shadow-(--shadow-lift)">
                        @foreach ($templates as $template)
                            <button type="button" wire:click="applyTemplate({{ $template->id }})" x-on:click="open = false" class="block w-full px-4 py-2 text-start text-sm hover:bg-primary-50">
                                {{ $template->name }} @if (! $template->doctor_id)<span class="text-xs text-muted">· {{ __('shared') }}</span>@endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($prescription)
                <a href="{{ route('print.prescription', $prescription) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="print" class="size-4" /> {{ __('Print') }}</a>
                <a href="{{ route('print.prescription', [$prescription, 'format' => 'pdf']) }}" target="_blank" class="btn btn-ghost btn-sm">PDF</a>
            @endif
        </div>
    </div>

    <div class="space-y-3 p-5">
        @forelse ($items as $i => $item)
            <div class="rounded-xl border border-line p-3" wire:key="rx-item-{{ $i }}-{{ md5($item['medicine_name']) }}">
                <div class="flex items-start gap-2">
                    <span class="mt-2 grid size-6 shrink-0 place-items-center rounded-full bg-primary-50 text-xs font-bold text-primary-700">{{ $i + 1 }}</span>
                    <div class="grid flex-1 gap-2 sm:grid-cols-12">
                        <input wire:model="items.{{ $i }}.medicine_name" dir="auto" @disabled(! $canPrescribe) class="form-control font-semibold sm:col-span-12" placeholder="{{ __('Medicine') }}">
                        <input wire:model="items.{{ $i }}.dose" list="rx-dose" @disabled(! $canPrescribe) class="form-control sm:col-span-3" placeholder="{{ __('Dose') }}">
                        <input wire:model="items.{{ $i }}.frequency" list="rx-frequency" @disabled(! $canPrescribe) class="form-control sm:col-span-3" placeholder="{{ __('Frequency') }}">
                        <input wire:model="items.{{ $i }}.duration" list="rx-duration" @disabled(! $canPrescribe) class="form-control sm:col-span-2" placeholder="{{ __('Duration') }}">
                        <input wire:model="items.{{ $i }}.instructions" list="rx-instructions" @disabled(! $canPrescribe) class="form-control sm:col-span-4" placeholder="{{ __('Instructions') }}">
                    </div>
                    @if ($canPrescribe)
                        <div class="flex flex-col">
                            <button type="button" wire:click="moveItem({{ $i }}, -1)" class="p-1 text-slate-400 hover:text-primary-600" @disabled($loop->first)><x-icon name="up" class="size-4" /></button>
                            <button type="button" wire:click="removeItem({{ $i }})" class="p-1 text-slate-400 hover:text-rose-600" aria-label="{{ __('Remove') }}"><x-icon name="close" class="size-4" /></button>
                            <button type="button" wire:click="moveItem({{ $i }}, 1)" class="p-1 text-slate-400 hover:text-primary-600" @disabled($loop->last)><x-icon name="down" class="size-4" /></button>
                        </div>
                    @endif
                </div>
                @error("items.$i.medicine_name") <p class="form-error">{{ $message }}</p> @enderror
            </div>
        @empty
            <p class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-muted">{{ __('No medicines yet. Search the library below or type a medicine name.') }}</p>
        @endforelse

        @if ($canPrescribe)
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input wire:model.live.debounce.250ms="medicineSearch" wire:keydown.enter.prevent="addMedicine" dir="auto" autocomplete="off"
                       class="form-control ps-10" placeholder="{{ __('Add medicine: type a name, e.g. Augmentin') }}">
                @if (mb_strlen($medicineSearch) >= 2)
                    <ul class="absolute inset-x-0 top-full z-20 mt-1 max-h-72 overflow-y-auto rounded-xl border border-line bg-white py-1 shadow-(--shadow-lift)">
                        @foreach ($this->medicineResults as $medicine)
                            <li>
                                <button type="button" wire:click="addMedicine({{ $medicine->id }})" class="flex w-full items-center justify-between gap-3 px-4 py-2 text-start hover:bg-primary-50">
                                    <span dir="ltr" class="text-sm font-semibold text-ink">{{ $medicine->displayName() }}</span>
                                    <span class="truncate text-xs text-muted">{{ $medicine->form?->label() }} @if ($medicine->generic_name)· <span dir="ltr">{{ $medicine->generic_name }}</span>@endif</span>
                                </button>
                            </li>
                        @endforeach
                        <li>
                            <button type="button" wire:click="addMedicine" class="flex w-full items-center gap-2 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50">
                                <x-icon name="plus" class="size-4" /> {{ __('Add “:name” as written', ['name' => $medicineSearch]) }}
                            </button>
                        </li>
                    </ul>
                @endif
            </div>

            <x-field :label="__('Instructions for the patient')" for="rx-notes" error="notes">
                <textarea wire:model="notes" id="rx-notes" rows="2" class="form-control" placeholder="{{ __('e.g. plenty of fluids, rest for 3 days') }}"></textarea>
            </x-field>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div>
                    @can('prescription_templates.manage')
                        @if ($showSaveTemplate)
                            <div class="flex flex-wrap items-center gap-2">
                                <input wire:model="templateName" class="form-control w-48 py-1.5" placeholder="{{ __('Template name') }}">
                                <label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" wire:model="templateShared" class="form-check"> {{ __('Share with all doctors') }}</label>
                                <button type="button" wire:click="saveAsTemplate" class="btn btn-secondary btn-sm">{{ __('Save') }}</button>
                                <button type="button" wire:click="$set('showSaveTemplate', false)" class="btn btn-ghost btn-sm">{{ __('Cancel') }}</button>
                            </div>
                            @error('templateName') <p class="form-error">{{ $message }}</p> @enderror
                        @elseif ($items)
                            <button type="button" wire:click="$set('showSaveTemplate', true)" class="btn btn-ghost btn-sm">{{ __('Save as template') }}</button>
                        @endif
                    @endcan
                </div>
                <button type="button" wire:click="save" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save"><x-icon name="check" class="size-4" /> {{ __('Save prescription') }}</button>
            </div>
        @elseif ($notes)
            <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm">{{ $notes }}</p>
        @endif
    </div>

    @foreach ($suggestions as $key => $values)
        <datalist id="rx-{{ $key }}">
            @foreach ($values as $value)<option value="{{ $value }}"></option>@endforeach
        </datalist>
    @endforeach
</section>
