@php use App\Support\Format; @endphp
<section class="card">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 class="flex items-center gap-2 font-semibold text-ink"><x-icon name="lab" class="size-5 text-violet-600" /> {{ __('Lab & imaging') }}</h2>
        @if ($canCreate)
            <button type="button" wire:click="$toggle('showForm')" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> {{ __('Request tests') }}</button>
        @endif
    </div>

    <div class="space-y-4 p-5">
        @if ($showForm)
            <form wire:submit="create" class="space-y-4 rounded-2xl border border-violet-200 bg-violet-50/40 p-4">
                @foreach ($types as $type)
                    @if (isset($tests[$type->value]))
                        <div>
                            <p class="mb-2 text-xs font-semibold text-muted">{{ $type->label() }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tests[$type->value] as $test)
                                    <label class="cursor-pointer rounded-full border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-700 transition has-checked:border-violet-500 has-checked:bg-violet-100 has-checked:text-violet-900">
                                        <input type="checkbox" wire:model="selectedTests" value="{{ $test->id }}" class="sr-only"> {{ $test->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field :label="__('Other tests')" for="lab-other" error="otherTests" :hint="__('Separate with commas.')">
                        <input wire:model="otherTests" id="lab-other" class="form-control">
                    </x-field>
                    <x-field :label="__('Notes')" for="lab-notes" error="notes">
                        <input wire:model="notes" id="lab-notes" class="form-control" placeholder="{{ __('e.g. fasting 8 hours') }}">
                    </x-field>
                </div>
                @error('tests') <p class="form-error">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showForm', false)" class="btn btn-secondary btn-sm">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Create request') }}</button>
                </div>
            </form>
        @endif

        @forelse ($requests as $request)
            <div class="flex flex-wrap items-start gap-3 rounded-2xl border border-line px-4 py-3" wire:key="lr-{{ $request->id }}">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-ink">{{ $request->items->pluck('name')->implode('، ') }}</p>
                    @if ($request->items->whereNotNull('result')->isNotEmpty())
                        <ul class="mt-1 space-y-0.5 text-xs text-stone-600">
                            @foreach ($request->items->whereNotNull('result') as $item)
                                <li>{{ $item->name }}: <span class="font-semibold text-ink">{{ $item->result }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-1 text-xs text-muted">{{ Format::dateTime($request->requested_at) }} @if ($request->notes) · {{ $request->notes }} @endif</p>
                </div>
                <x-badge :color="$request->status->color()">{{ $request->status->label() }}</x-badge>
                <a href="{{ route('print.lab-request', $request) }}" target="_blank" class="btn btn-ghost btn-sm" title="{{ __('Print') }}"><x-icon name="print" class="size-4" /></a>
            </div>
        @empty
            @unless ($showForm)
                <p class="text-center text-sm text-muted">{{ __('No tests requested in this visit.') }}</p>
            @endunless
        @endforelse
    </div>
</section>
