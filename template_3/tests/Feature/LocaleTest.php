<?php

use App\Enums\Role;

it('switches a logged-in user to English (LTR) and remembers it', function () {
    $user = actingAsRole(Role::Doctor);

    $this->from(route('dashboard'))->post(route('locale.switch', 'en'))->assertRedirect(route('dashboard'));

    expect($user->fresh()->locale)->toBe('en');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertSee('Dashboard');
});

it('lets guests switch the login page language', function () {
    $this->post(route('locale.switch', 'en'));

    $this->get(route('login'))->assertSee('dir="ltr"', false)->assertSee('Welcome back');
});

it('uses the clinic default language for users without a preference', function () {
    settings()->set(['default_locale' => 'en']);
    actingAsRole(Role::Receptionist);

    $this->get(route('dashboard'))->assertSee('lang="en"', false);
});

it('rejects unsupported locales', function () {
    $this->post(route('locale.switch', 'fr'))->assertNotFound();
});
