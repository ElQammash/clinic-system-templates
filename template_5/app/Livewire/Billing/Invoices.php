<?php

namespace App\Livewire\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Invoices')]
class Invoices extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Invoice::class);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    protected function filtered(): Builder
    {
        return Invoice::query()
            ->visibleTo(auth()->user())
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('number', 'like', '%'.addcslashes($this->search, '%_').'%')
                ->orWhereHas('patient', fn ($p) => $p->withTrashed()->search($this->search))))
            ->when(InvoiceStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s->value))
            ->when($this->from, fn ($q) => $q->whereDate('issued_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('issued_at', '<=', $this->to));
    }

    public function render()
    {
        $user = auth()->user();
        $showTotals = $user->can('invoices.create') || $user->can('reports.view_financial');

        // Cash drawer for today: what was collected per payment method.
        $today = $showTotals
            ? Payment::query()->whereDate('paid_at', today())->selectRaw('method, SUM(amount) as total, COUNT(*) as count')->groupBy('method')->get()
                ->mapWithKeys(fn ($row) => [$row->method->value => ['total' => $row->total / 100, 'count' => $row->count]])
            : collect();

        return view('livewire.billing.invoices', [
            'invoices' => $this->filtered()->with('patient')->latest('issued_at')->latest('id')->paginate(20),
            'statuses' => InvoiceStatus::cases(),
            'showTotals' => $showTotals,
            'outstanding' => $showTotals ? ((int) Invoice::query()->withBalance()->sum('balance')) / 100 : 0,
            'today' => $today,
            'methods' => PaymentMethod::options(),
        ]);
    }
}
