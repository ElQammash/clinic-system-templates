<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['expense_category_id', 'amount', 'spent_at', 'description', 'payment_method', 'receipt_path', 'created_by'])]
class Expense extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'spent_at' => 'date',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('expenses')->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
