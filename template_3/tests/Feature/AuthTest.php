<?php

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('renders the login page in Arabic and RTL by default', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('تسجيل الدخول');
});

it('logs in an active user', function () {
    $user = User::factory()->role(Role::Receptionist)->create(['email' => 'sara@clinic.test']);

    Livewire::test(Login::class)
        ->set('email', 'sara@clinic.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects wrong passwords', function () {
    User::factory()->create(['email' => 'sara@clinic.test']);

    Livewire::test(Login::class)
        ->set('email', 'sara@clinic.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('does not let deactivated users log in', function () {
    User::factory()->inactive()->create(['email' => 'old@clinic.test']);

    Livewire::test(Login::class)
        ->set('email', 'old@clinic.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('logs out users that were deactivated during their session', function () {
    $user = actingAsRole(Role::Receptionist);
    $user->update(['is_active' => false]);

    $this->get('/')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('throttles repeated failed logins', function () {
    User::factory()->create(['email' => 'sara@clinic.test']);

    $component = Livewire::test(Login::class)->set('email', 'sara@clinic.test')->set('password', 'wrong');

    foreach (range(1, 5) as $_) {
        $component->call('login');
    }

    $component->set('password', 'password')->call('login')->assertHasErrors('email');
    $this->assertGuest();
});

it('logs out', function () {
    actingAsRole(Role::Doctor);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
