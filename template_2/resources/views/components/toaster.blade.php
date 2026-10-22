{{-- Dark Pro toasts: terminal-like strip with a colored edge, bottom start corner. --}}
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
    class="pointer-events-none fixed bottom-4 z-[60] flex w-full flex-col gap-2 px-4 sm:start-4 sm:w-auto sm:px-0"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            :class="toast.type === 'error' ? 'border-s-rose-500' : 'border-s-primary-500'"
            class="pointer-events-auto flex min-w-72 items-center gap-3 rounded-sm border border-s-4 border-line-strong bg-raised px-4 py-2.5 text-[13px] text-ink shadow-(--shadow-lift)"
        >
            <x-icon name="check" class="size-4 text-primary-400" x-show="toast.type !== 'error'" />
            <x-icon name="warning" class="size-4 text-rose-400" x-show="toast.type === 'error'" />
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
