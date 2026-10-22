<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'locale', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly(['name', 'email', 'phone', 'is_active', 'locale'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function isDoctor(): bool
    {
        return $this->hasRole(Role::Doctor->value);
    }

    public function primaryRole(): ?Role
    {
        $name = $this->roles->first()?->name;

        return $name ? Role::tryFrom($name) : null;
    }

    /** Display name with the doctor title prefix ("د. أحمد") when relevant. */
    public function displayName(): string
    {
        return $this->isDoctor() && ! Str::startsWith($this->name, ['د.', 'Dr'])
            ? __('Dr.').' '.$this->name
            : $this->name;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/u', trim($this->name)))
            ->reject(fn ($part) => in_array($part, ['د.', 'Dr.', 'Dr'], true))
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('');
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: settings('default_locale', config('app.locale'));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeDoctors(Builder $query): void
    {
        $query->role(Role::Doctor->value);
    }
}
