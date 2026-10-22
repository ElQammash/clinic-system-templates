<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/** Create a user with the given role and log in as them. */
function actingAsRole(Role $role, array $attributes = []): User
{
    $user = User::factory()->role($role)->create($attributes);
    test()->actingAs($user);

    return $user;
}
