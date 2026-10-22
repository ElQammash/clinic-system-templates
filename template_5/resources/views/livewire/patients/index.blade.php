@php use App\Support\Format; @endphp
<div>
    <x-page-header :title="__('Patients')" :subtitle="trans_choice(':count patient file|:count patient files', $totalPatients, ['count' => Format::number($totalPatients)])">
        <x-slot:actions>
            @can('create', App\Models\Patient::class)
                <button type="button" x-on:click="$dispatch('create-patient')" class="btn btn-primary">
                    <x-icon name="user-plus" class="size-5" /> {{ __('New patient') }}
                </button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-slate-600" />
                <input wire:model.live.debounce.300ms="search" type="search" autofocus
                       placeholder="{{ __('Search by name, phone, file number or national ID') }}" class="form-control ps-10">
                <div wire:loading wire:target="search" class="absolute end-3 top-1/2 -translate-y-1/2">
                    <span class="block size-4 animate-spin rounded-full border-2 border-primary-200 border-t-primary-600"></span>
                </div>
            </div>
            <select wire:model.live="gender" class="form-control sm:w-40">
                <option value="">{{ __('All genders') }}</option>
                @foreach ($genders as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @can('patients.delete')
                <label class="flex shrink-0 items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" wire:model.live="trashed" class="form-check"> {{ __('Deleted patients') }}
                </label>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-28">{{ __('File no.') }}</th>
                        <th>{{ __('Patient') }}</th>
                        <th class="hidden sm:table-cell">{{ __('Phone') }}</th>
                        <th class="hidden md:table-cell">{{ __('Age') }}</th>
                        <th class="hidden lg:table-cell">{{ __('Registered') }}</th>
                        <th><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($patients as $patient)
                        <tr wire:key="patient-{{ $patient->id }}" class="cursor-pointer" @unless ($patient->trashed()) onclick="if (! event.target.closest('a,button')) Livewire.navigate('{{ route('patients.show', $patient) }}')" @endunless>
                            <td><span class="ltr-nums rounded-md bg-slate-100 px-2 py-1 font-mono text-sm font-semibold text-slate-700">{{ $patient->file_number }}</span></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'grid size-9 shrink-0 place-items-center rounded-full text-sm font-semibold',
                                        'bg-sky-100 text-sky-700' => $patient->gender?->value === 'male',
                                        'bg-pink-100 text-pink-700' => $patient->gender?->value === 'female',
                                        'bg-slate-100 text-slate-600' => ! $patient->gender,
                                    ])>{{ $patient->initials() }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('patients.show', $patient) }}" wire:navigate class="block truncate font-semibold text-ink hover:text-primary-700">{{ $patient->name }}</a>
                                        <p class="text-sm text-muted sm:hidden"><span class="ltr-nums">{{ $patient->phone }}</span></p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell"><span class="ltr-nums">{{ $patient->phone ?? '—' }}</span></td>
                            <td class="hidden md:table-cell">
                                {{ $patient->age() ?: '—' }}
                                @if ($patient->gender)<span class="text-muted">· {{ $patient->gender->label() }}</span>@endif
                            </td>
                            <td class="hidden text-sm text-muted lg:table-cell">{{ Format::date($patient->created_at) }}</td>
                            <td class="text-end whitespace-nowrap">
                                @if ($patient->trashed())
                                    <button type="button" wire:click="restore({{ $patient->id }})" class="btn btn-secondary btn-sm"><x-icon name="restore" class="size-4" /> {{ __('Restore') }}</button>
                                @else
                                    <a href="{{ route('patients.show', $patient) }}" wire:navigate class="btn btn-ghost btn-sm">{{ __('Open file') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="mx-auto grid size-14 place-items-center rounded-full bg-primary-50 text-primary-600"><x-icon name="patients" class="size-7" /></div>
                                <p class="mt-3 font-semibold text-ink">{{ $search ? __('No patients match “:term”.', ['term' => $search]) : __('No patients yet.') }}</p>
                                @if ($search)
                                    @can('create', App\Models\Patient::class)
                                        <button type="button" x-on:click="$dispatch('create-patient', { name: @js(preg_match('/\d/', $search) ? '' : $search), phone: @js(preg_match('/^\d+$/', $search) ? $search : null) })" class="btn btn-primary mt-4">
                                            <x-icon name="user-plus" class="size-5" /> {{ __('Register as a new patient') }}
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($patients->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $patients->links() }}</div>
        @endif
    </div>

    <livewire:patients.patient-editor />
</div>
