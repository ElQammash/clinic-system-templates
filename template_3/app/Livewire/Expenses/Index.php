<?php

namespace App\Livewire\Expenses;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Expenses')]
class Index extends Component
{
    use WithPagination;

    /** YYYY-MM */
    #[Url]
    public string $month = '';

    #[Url(as: 'category', except: 0)]
    public int $categoryId = 0;

    public bool $showForm = false;

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $expense = [];

    public string $newCategory = '';

    public function mount(): void
    {
        $this->authorize('expenses.view');
        $this->month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month : now()->format('Y-m');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function shiftMonth(int $by): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonths($by)->format('Y-m');
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('expenses.manage');
        $model = $id ? Expense::findOrFail($id) : null;

        $this->editingId = $model?->id;
        $this->expense = [
            'expense_category_id' => $model?->expense_category_id ?? ExpenseCategory::where('is_active', true)->value('id'),
            'amount' => $model?->amount ?? '',
            'spent_at' => $model?->spent_at?->toDateString() ?? today()->toDateString(),
            'description' => $model?->description ?? '',
            'payment_method' => $model?->payment_method?->value ?? PaymentMethod::Cash->value,
        ];
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('expenses.manage');

        $data = $this->validate([
            'expense.expense_category_id' => ['required', Rule::exists('expense_categories', 'id')],
            'expense.amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'expense.spent_at' => ['required', 'date', 'before_or_equal:today'],
            'expense.description' => ['required', 'string', 'max:255'],
            'expense.payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ], attributes: ['expense.amount' => __('Amount'), 'expense.description' => __('Description')])['expense'];

        $data['amount'] = InvoiceCalculator::toMinor($data['amount']) / 100;

        $this->editingId
            ? Expense::findOrFail($this->editingId)->update($data)
            : Expense::create($data + ['created_by' => auth()->id()]);

        $this->showForm = false;
        $this->dispatch('toast', message: __('Expense saved.'));
    }

    public function delete(int $id): void
    {
        $this->authorize('expenses.manage');
        Expense::findOrFail($id)->delete();
        $this->dispatch('toast', message: __('Expense deleted.'));
    }

    public function addCategory(): void
    {
        $this->authorize('expenses.manage');
        $this->validate(['newCategory' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'name_ar')]], attributes: ['newCategory' => __('Category')]);

        $category = ExpenseCategory::create(['name_ar' => $this->newCategory]);
        $this->expense['expense_category_id'] = $category->id;
        $this->reset('newCategory');
    }

    public function render()
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $base = Expense::query()->whereBetween('spent_at', [$start->toDateString(), $end->toDateString()]);

        return view('livewire.expenses.index', [
            'expenses' => (clone $base)->when($this->categoryId, fn ($q) => $q->where('expense_category_id', $this->categoryId))
                ->with('category', 'creator')->latest('spent_at')->latest('id')->paginate(25),
            'monthTotal' => ((int) (clone $base)->sum('amount')) / 100,
            'byCategory' => (clone $base)->selectRaw('expense_category_id, SUM(amount) as total')->groupBy('expense_category_id')
                ->with('category')->get()->sortByDesc('total')->values(),
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name_ar')->get(),
            'monthLabel' => $start->translatedFormat('F Y'),
            'methods' => PaymentMethod::options(),
        ]);
    }
}
