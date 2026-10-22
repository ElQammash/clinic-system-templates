{{-- Listens for Livewire `toast` events and session('toast') flashes. Luxury Minimal: a quiet black bar with a gold mark. --}}
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
    class="pointer-events-none fixed inset-x-0 bottom-6 z-[60] flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="pointer-events-auto flex max-w-md items-center gap-4 bg-ink px-6 py-3.5 text-[13px] text-white"
        >
            <span :class="toast.type === 'error' ? 'bg-rose-500' : 'bg-primary-400'" class="h-px w-6"></span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
