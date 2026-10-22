<div
    x-data="{
        open: false,
        active: 0,
        show() { this.open = true; this.active = 0; this.$nextTick(() => this.$refs.input.focus()); },
        go() { const item = this.$refs.list?.querySelectorAll('a')[this.active]; if (item) { this.open = false; Livewire.navigate(item.href); } },
        move(by) { const n = this.$refs.list?.querySelectorAll('a').length || 0; if (n) this.active = (this.active + by + n) % n; },
    }"
    x-on:keydown.window.prevent.ctrl.k="show()"
    x-on:keydown.window.prevent.meta.k="show()"
    x-on:open-palette.window="show()"
>
    <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center bg-black/60 px-4 pt-[12vh]" x-on:click.self="open = false" x-on:keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity.duration.100ms class="w-full max-w-xl overflow-hidden rounded-(--radius-card) border border-line-strong bg-surface shadow-(--shadow-lift)">
            <div class="flex items-center gap-3 border-b border-line px-4">
                <x-icon name="search" class="size-4 text-muted" />
                <input x-ref="input" wire:model.live.debounce.200ms="query" type="text" autocomplete="off"
                       x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()"
                       x-on:input="active = 0"
                       placeholder="{{ __('Search patients or jump to a page…') }}"
                       class="h-12 w-full border-0 bg-transparent px-0 text-sm text-ink placeholder:text-muted focus:ring-0">
                <span class="kbd">Esc</span>
            </div>
            <div x-ref="list" class="max-h-80 overflow-y-auto py-1">
                @forelse ($this->results as $i => $result)
                    <a href="{{ $result['url'] }}" wire:key="cmd-{{ $i }}-{{ md5($result['url']) }}"
                       x-on:mouseenter="active = {{ $i }}" x-on:click="open = false"
                       :class="active === {{ $i }} ? 'bg-primary-500/12 text-ink' : 'text-ink-soft'"
                       class="flex items-center gap-3 px-4 py-2 text-[13px]">
                        <x-icon :name="$result['icon']" class="size-4 text-muted" />
                        <span class="min-w-0 flex-1 truncate">{{ $result['label'] }}</span>
                        <span class="truncate font-mono text-[11px] text-muted" dir="ltr">{{ $result['hint'] }}</span>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-xs text-muted">{{ __('No results.') }}</p>
                @endforelse
            </div>
            <div class="flex items-center gap-4 border-t border-line bg-subtle px-4 py-2 text-[11px] text-muted">
                <span><span class="kbd">↑</span> <span class="kbd">↓</span> {{ __('navigate') }}</span>
                <span><span class="kbd">↵</span> {{ __('open') }}</span>
            </div>
        </div>
    </div>
</div>
