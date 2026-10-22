<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Money attributes read as major units (pounds) through the Money cast;
 * calculations in App\Services\Billing work on the raw minor units.
 */
#[Fillable([
    'number', 'patient_id', 'visit_id', 'appointment_id', 'doctor_id', 'issued_at', 'subtotal', 'discount_type',
    'discount_value', 'discount_amount', 'total', 'paid_amount', 'balance', 'status', 'voided_at', 'void_reason',
    'notes', 'created_by',
])]
class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
            'subtotal' => Money::class,
            'discount_type' => DiscountType::class,
            'discount_value' => Money::class, // fixed: pounds, percent: percentage (12.5)
            'discount_amount' => Money::class,
            'total' => Money::class,
            'paid_amount' => Money::class,
            'balance' => Money::class,
            'status' => InvoiceStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('billing')
            ->logOnly(['number', 'total', 'discount_amount', 'status', 'void_reason'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class)->withTrashed();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at');
    }

    public function isVoid(): bool
    {
        return $this->status === InvoiceStatus::Void;
    }

    public function scopeNotVoid(Builder $query): void
    {
        $query->where('status', '!=', InvoiceStatus::Void->value);
    }

    public function scopeWithBalance(Builder $query): void
    {
        $query->notVoid()->where('balance', '>', 0);
    }

    /** Doctors see the invoices of their own patients' visits; billing staff see everything. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isDoctor() && ! $user->can('reports.view_financial') && ! $user->can('invoices.create')) {
            $query->where('doctor_id', $user->id);
        }
    }
}
