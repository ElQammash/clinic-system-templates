<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['number', 'invoice_id', 'patient_id', 'amount', 'method', 'reference', 'paid_at', 'received_by', 'notes'])]
class Payment extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'amount' => Money::class,
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('billing')
            ->logOnly(['number', 'amount', 'method', 'invoice_id'])
            ->dontLogEmptyChanges();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
