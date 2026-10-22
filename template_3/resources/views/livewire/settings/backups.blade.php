@php use App\Support\Format; @endphp
<div>
    <x-page-header emoji="backup" :title="__('Backups')" :subtitle="__('A full copy of the database and patient files is made every night at 2:00.')">
        <x-slot:actions>
            <button type="button" wire:click="runNow" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading.remove wire:target="runNow"><x-icon name="backup" class="inline size-4" /> {{ __('Back up now') }}</span>
                <span wire:loading wire:target="runNow">{{ __('Backing up… this can take a minute') }}</span>
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($output)
        <div @class(['mb-6 rounded-2xl border px-4 py-3 text-sm', 'border-emerald-200 bg-emerald-50 text-emerald-800' => $ok, 'border-rose-200 bg-rose-50 text-rose-800' => ! $ok])>
            <pre class="overflow-x-auto text-xs whitespace-pre-wrap" dir="ltr">{{ $output }}</pre>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <section class="card overflow-hidden">
            <ul class="divide-y divide-line">
                @forelse ($backups as $backup)
                    <li class="flex items-center gap-4 px-5 py-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-primary-50 text-primary-600"><x-icon name="backup" class="size-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-mono text-sm" dir="ltr">{{ $backup['name'] }}</p>
                            <p class="text-xs text-muted">{{ Format::dateTime($backup['date']) }} · {{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</p>
                        </div>
                        <a href="{{ route('settings.backups.download', $backup['name']) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> {{ __('Download') }}</a>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-muted">{{ __('No backups yet.') }}</li>
                @endforelse
            </ul>
        </section>
        <aside class="card space-y-3 p-5 text-sm leading-relaxed text-stone-600">
            <p>{{ __('Backups are kept for :days days, then thinned out automatically.', ['days' => Format::number($keepDays)]) }}</p>
            <p>{{ __('For safety, download a copy regularly and keep it outside the clinic computer (USB drive or cloud).') }}</p>
            <p class="text-xs text-muted">{{ __('Automatic backups need the scheduler running on the server — see the README.') }}</p>
        </aside>
    </div>
</div>
