@php use App\Support\Format; @endphp
<div>
    <x-page-header :title="__('Expenses')" :subtitle="$monthLabel">
        <x-slot:actions>
            @can('expenses.manage')
                <button type="button" wire:click="edit" class="btn btn-primary"><x-icon name="plus" class="size-5" /> {{ __('Add expense') }}</button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <button type="button" wire:click="shiftMonth(-1)" class="btn btn-ghost p-2"><x-icon name="chevron-right" class="size-5 ltr:rotate-180" /></button>
                <p class="font-semibold text-ink">{{ $monthLabel }}</p>
                <button type="button" wire:click="shiftMonth(1)" class="btn btn-ghost p-2"><x-icon name="chevron-left" class="size-5 ltr:rotate-180" /></button>
            </div>
            <p class="mt-4 text-sm text-muted">{{ __('Total this month') }}</p>
            <p class="mt-1 text-3xl font-bold text-ink">{{ money($monthTotal) }}</p>
        </div>
        <div class="card p-5 lg:col-span-2">
            <p class="mb-3 text-sm text-muted">{{ __('By category') }}</p>
            <div class="space-y-2">
                @forelse ($byCategory as $row)
                    @php $share = $monthTotal > 0 ? ($row->total / 100) / $monthTotal * 100 : 0; @endphp
                    <button type="button" wire:click="$set('categoryId', {{ $categoryId === $row->expense_category_id ? 0 : $row->expense_category_id }})" class="block w-full text-start">
                        <div class="flex justify-between text-sm">
                            <span @class(['font-medium', 'text-primary-700' => $categoryId === $row->expense_category_id])>{{ $row->category?->name() }}</span>
                            <span class="font-semibold">{{ money($row->total / 100) }}</span>
                        </div>
                        <div class="mt-1 h-1.5 rounded-full bg-subtle"><div class="h-1.5 rounded-full bg-primary-500" style="width: {{ $share }}%"></div></div>
                    </button>
                @empty
                    <p class="text-sm text-muted">{{ __('No expenses this month.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="table-clean">
            <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Description') }}</th><th class="hidden md:table-cell">{{ __('Category') }}</th><th class="text-end">{{ __('Amount') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-line">
                @forelse ($expenses as $expense)
                    <tr wire:key="exp-{{ $expense->id }}">
                        <td class="whitespace-nowrap text-muted">{{ Format::date($expense->spent_at, 'j M') }}</td>
                        <td><span class="font-medium text-ink">{{ $expense->description }}</span><br><span class="text-xs text-muted">{{ $expense->payment_method->label() }} · {{ $expense->creator?->name }}</span></td>
                        <td class="hidden md:table-cell"><x-badge>{{ $expense->category->name() }}</x-badge></td>
                        <td class="text-end font-semibold">{{ money($expense->amount) }}</td>
                        <td class="text-end whitespace-nowrap">
                            @can('expenses.manage')
                                <button type="button" wire:click="edit({{ $expense->id }})" class="btn btn-ghost btn-sm"><x-icon name="edit" class="size-4" /></button>
                                <button type="button" wire:click="delete({{ $expense->id }})" wire:confirm="{{ __('Delete this expense?') }}" class="btn btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4" /></button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-14 text-center text-muted">{{ __('No expenses this month.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($expenses->hasPages())
            <div class="border-t border-line px-4 py-3">{{ $expenses->links() }}</div>
        @endif
    </div>

    <x-modal wire:model="showForm" :title="$editingId ? __('Edit expense') : __('Add expense')" max-width="max-w-lg">
        <form id="exp-form" wire:submit="save" class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Description')" for="e-desc" error="expense.description" required class="sm:col-span-2"><input wire:model="expense.description" id="e-desc" class="form-control" placeholder="{{ __('e.g. October rent') }}"></x-field>
            <x-field :label="__('Amount')" for="e-amount" error="expense.amount" required><input wire:model="expense.amount" id="e-amount" type="number" min="0.01" step="0.01" dir="ltr" class="form-control text-start"></x-field>
            <x-field :label="__('Date')" for="e-date" error="expense.spent_at" required><input wire:model="expense.spent_at" id="e-date" type="date" max="{{ today()->toDateString() }}" class="form-control"></x-field>
            <x-field :label="__('Category')" for="e-cat" error="expense.expense_category_id">
                <select wire:model="expense.expense_category_id" id="e-cat" class="form-control">
                    @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name() }}</option>@endforeach
                </select>
            </x-field>
            <x-field :label="__('Payment method')" for="e-method" error="expense.payment_method">
                <select wire:model="expense.payment_method" id="e-method" class="form-control">
                    @foreach ($methods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </x-field>
            <div class="sm:col-span-2">
                <p class="form-label">{{ __('New category') }}</p>
                <div class="flex gap-2">
                    <input wire:model="newCategory" class="form-control" placeholder="{{ __('Category name') }}">
                    <button type="button" wire:click="addCategory" class="btn btn-secondary">{{ __('Add') }}</button>
                </div>
                @error('newCategory') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </form>
        <x-slot:footer>
            <button type="button" x-on:click="open = false" class="btn btn-secondary">{{ __('Cancel') }}</button>
            <button type="submit" form="exp-form" class="btn btn-primary">{{ __('Save') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
