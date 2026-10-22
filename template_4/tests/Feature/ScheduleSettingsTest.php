<?php

use App\Enums\Role;
use App\Livewire\Settings\Schedule;
use App\Models\User;
use App\Models\VisitType;
use App\Models\WorkingHour;
use Database\Seeders\SchedulingSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(SchedulingSeeder::class));

it('seeds default visit types and clinic hours once', function () {
    $this->seed(SchedulingSeeder::class);

    expect(VisitType::count())->toBe(2)
        ->and(WorkingHour::whereNull('doctor_id')->count())->toBe(6)
        ->and(WorkingHour::where('day_of_week', 5)->exists())->toBeFalse(); // Friday off
});

it('saves split shifts for a doctor', function () {
    actingAsRole(Role::Admin);
    $doctor = User::factory()->doctor()->create();

    Livewire::test(Schedule::class)
        ->set('hoursFor', $doctor->id)
        ->set('shifts.1', [['from' => '10:00', 'to' => '13:00'], ['from' => '18:00', 'to' => '22:00']])
        ->call('saveHours')
        ->assertHasNoErrors();

    expect(WorkingHour::where('doctor_id', $doctor->id)->count())->toBe(7) // 5 clinic days copied + Monday's 2 shifts
        ->and(WorkingHour::where('doctor_id', $doctor->id)->where('day_of_week', 1)->count())->toBe(2)
        ->and(WorkingHour::whereNull('doctor_id')->count())->toBe(6); // clinic hours untouched
});

it('rejects overlapping shifts', function () {
    actingAsRole(Role::Admin);

    Livewire::test(Schedule::class)
        ->set('shifts.1', [['from' => '10:00', 'to' => '14:00'], ['from' => '13:00', 'to' => '18:00']])
        ->call('saveHours')
        ->assertHasErrors('shifts.1');
});

it('manages visit types', function () {
    actingAsRole(Role::Admin);

    Livewire::test(Schedule::class)
        ->call('editType')
        ->set('typeNameAr', 'جلسة ليزر')
        ->set('typePrice', 750)
        ->set('typeDuration', '30')
        ->call('saveType')
        ->assertHasNoErrors();

    $type = VisitType::where('name_ar', 'جلسة ليزر')->firstOrFail();
    expect($type->default_price)->toBe(750.0)
        ->and($type->getRawOriginal('default_price'))->toBe(75000)
        ->and($type->default_duration)->toBe(30);
});

it('labels each weekday correctly, starting with Saturday', function () {
    actingAsRole(Role::Admin);

    Livewire::test(Schedule::class)->assertSeeInOrder(['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة']);
});

it('forbids reception from the schedule settings', function () {
    actingAsRole(Role::Receptionist);

    $this->get(route('settings.schedule'))->assertForbidden();
});
