{{-- Listens for Livewire `toast` events and session('toast') flashes. Tablet-First: large, high-contrast banner above the tab bar. --}}
<div
    x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4000);
        },
    }"
    x-init="@if (session('toast')) add(@js(session('toast'))) @endif"
    x-on:toast.window="add($event.detail.message ?? $event.detail[0]?.message, $event.detail.type ?? 'success')"
    class="pointer-events-none fixed inset-x-0 bottom-28 z-[60] flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            :class="toast.type === 'error' ? 'bg-rose-700' : 'bg-ink'"
            class="pointer-events-auto flex w-full max-w-lg items-center gap-4 rounded-2xl px-5 py-4 text-lg font-bold text-white shadow-(--shadow-lift)"
            x-on:click="toasts = toasts.filter(t => t.id !== toast.id)"
        >
            <span :class="toast.type === 'error' ? 'bg-white/20' : 'bg-emerald-500'" class="grid size-10 shrink-0 place-items-center rounded-xl">
                <x-icon name="check" class="size-6" x-show="toast.type !== 'error'" />
                <x-icon name="warning" class="size-6" x-show="toast.type === 'error'" />
            </span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
