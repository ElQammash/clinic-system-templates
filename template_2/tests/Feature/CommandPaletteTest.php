<?php

// Template 2 only: the Ctrl/⌘ + K command palette (App\Livewire\Ui\CommandPalette).

use App\Enums\Role;
use App\Livewire\Ui\CommandPalette;
use App\Models\Patient;
use App\Models\User;
use Livewire\Livewire;

it('finds patients and pages the user may open', function () {
    $patient = Patient::factory()->create(['name' => 'فاطمة أحمد مصطفى']);
    actingAsRole(Role::Receptionist);

    $results = Livewire::test(CommandPalette::class)->set('query', 'فاطمه')->instance()->results();

    expect(collect($results)->pluck('url'))->toContain(route('patients.show', $patient));
});

it('does not list patients or forbidden pages for users without access', function () {
    Patient::factory()->create(['name' => 'فاطمة أحمد مصطفى']);
    $this->actingAs(User::factory()->create()); // no role, no permissions

    $results = collect(Livewire::test(CommandPalette::class)->set('query', 'فاطمه')->instance()->results());

    expect($results->where('icon', 'user'))->toBeEmpty()
        ->and($results->pluck('url'))->not->toContain(route('settings.users'));
});
