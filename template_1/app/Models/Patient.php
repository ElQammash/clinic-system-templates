<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\Gender;
use App\Enums\Permission;
use App\Support\ArabicText;
use App\Support\Format;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'file_number', 'name', 'phone', 'phone_alt', 'gender', 'birth_date', 'birth_date_is_estimated',
    'national_id', 'address', 'occupation', 'blood_type', 'allergies', 'chronic_diseases', 'notes',
    'custom_data', 'created_by',
])]
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /** Columns that are medical information (hidden from users without medical access). */
    public const MEDICAL_ATTRIBUTES = ['blood_type', 'allergies', 'chronic_diseases'];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'blood_type' => BloodType::class,
            'birth_date' => 'date',
            'birth_date_is_estimated' => 'boolean',
            'custom_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            $patient->search_name = ArabicText::normalize($patient->name);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('patients')
            ->logFillable()
            ->logExcept(['custom_data'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientAttachment::class)->latest();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(LabRequest::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Amount still owed across all non-void invoices, in pounds. */
    public function balance(): float
    {
        return round(((int) $this->invoices()->notVoid()->sum('balance')) / 100, 2);
    }

    public function age(): string
    {
        return Format::age($this->birth_date);
    }

    public function ageInYears(): ?int
    {
        return $this->birth_date ? (int) $this->birth_date->diffInYears(now()) : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/u', trim($this->name)))
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('');
    }

    /** @return list<string> allergies as a list (entered comma / newline separated) */
    public function allergyList(): array
    {
        return self::splitList($this->allergies);
    }

    /** @return list<string> */
    public function chronicDiseaseList(): array
    {
        return self::splitList($this->chronic_diseases);
    }

    /** @return list<string> */
    protected static function splitList(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,،\n]+/u', (string) $value))));
    }

    /**
     * Fast search by file number, phone (any part) or name (Arabic-normalised, every word must match).
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) Format::normalizeDigits($term));

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $digits = preg_replace('/\D/', '', $term);

            $q->where('file_number', $term);

            if (strlen($digits) >= 3 && strlen($digits) === strlen(preg_replace('/[\s\-+]/', '', $term))) {
                $q->orWhere('file_number', 'like', "%{$digits}")
                    ->orWhere('phone', 'like', "%{$digits}%")
                    ->orWhere('phone_alt', 'like', "%{$digits}%")
                    ->orWhere('national_id', $digits);
            }

            $words = array_filter(explode(' ', ArabicText::normalize($term)));
            if ($words !== []) {
                $q->orWhere(function (Builder $names) use ($words) {
                    foreach ($words as $word) {
                        $names->where('search_name', 'like', '%'.addcslashes($word, '%_').'%');
                    }
                });
            }
        });
    }

    /**
     * Patients whose medical record the user may open. Doctors only see their own patients
     * unless the clinic shares records (Settings) or they have the records.view_all permission.
     */
    public function scopeMedicallyVisibleTo(Builder $query, User $user): void
    {
        if (self::userSeesAllRecords($user)) {
            return;
        }

        $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id);

            foreach (self::$ownershipResolvers as $resolver) {
                $resolver($q, $user);
            }
        });
    }

    public function isMedicallyVisibleTo(User $user): bool
    {
        return self::userSeesAllRecords($user)
            || self::query()->whereKey($this->id)->medicallyVisibleTo($user)->exists();
    }

    public static function userSeesAllRecords(User $user): bool
    {
        return $user->can(Permission::RecordsViewAll->value)
            || ($user->isDoctor() && settings('doctors_share_records'));
    }

    /**
     * Extra "this doctor treats this patient" rules registered by other modules
     * (appointments, visits) so this model does not depend on them.
     *
     * @var array<string, callable(Builder, User): void>
     */
    protected static array $ownershipResolvers = [];

    /** Registering the same key again replaces the resolver (safe across app reboots). */
    public static function resolveOwnershipUsing(string $key, callable $resolver): void
    {
        self::$ownershipResolvers[$key] = $resolver;
    }
}
