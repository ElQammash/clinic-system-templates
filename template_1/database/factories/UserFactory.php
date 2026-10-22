<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '010'.fake()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'locale' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function role(Role $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole($role->value);

            if ($role === Role::Doctor) {
                $user->doctorProfile()->create(['slot_minutes' => 15, 'consultation_fee' => 300]);
            }
        });
    }

    public function admin(): static
    {
        return $this->role(Role::Admin);
    }

    public function doctor(): static
    {
        return $this->role(Role::Doctor);
    }

    public function receptionist(): static
    {
        return $this->role(Role::Receptionist);
    }

    public function accountant(): static
    {
        return $this->role(Role::Accountant);
    }
}
