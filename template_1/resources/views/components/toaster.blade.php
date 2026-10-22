{{-- Listens for Livewire `toast` events and session('toast') flashes. --}}
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
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4 sm:bottom-6 sm:items-end sm:px-6"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl border border-line bg-white px-4 py-3 text-sm font-medium shadow-(--shadow-lift)"
        >
            <span :class="toast.type === 'error' ? 'bg-rose-100 text-rose-600' : 'bg-primary-100 text-primary-700'" class="grid size-7 place-items-center rounded-full">
                <x-icon name="check" class="size-4" x-show="toast.type !== 'error'" />
                <x-icon name="warning" class="size-4" x-show="toast.type === 'error'" />
            </span>
            <span x-text="toast.message" class="text-ink"></span>
        </div>
    </template>
</div>
