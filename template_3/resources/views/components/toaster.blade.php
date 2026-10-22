{{-- Listens for Livewire `toast` events and session('toast') flashes. Warm & Friendly: bouncy bubbles at the bottom centre. --}}
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
    class="pointer-events-none fixed inset-x-0 bottom-5 z-[60] flex flex-col items-center gap-2 px-4"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="ease-[cubic-bezier(.34,1.56,.64,1)] duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            :class="toast.type === 'error' ? 'border-rose-200' : 'border-primary-200'"
            class="pointer-events-auto flex max-w-md items-center gap-3 rounded-full border-2 bg-white py-2 ps-2 pe-5 text-[15px] font-bold shadow-(--shadow-lift)"
        >
            <span :class="toast.type === 'error' ? 'bg-rose-100' : 'bg-primary-100'" class="emoji grid size-9 place-items-center rounded-full text-lg" x-text="toast.type === 'error' ? '😕' : '🎉'"></span>
            <span x-text="toast.message" class="text-ink"></span>
        </div>
    </template>
</div>
