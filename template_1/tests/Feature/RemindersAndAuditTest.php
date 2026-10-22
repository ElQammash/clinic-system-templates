<?php

use App\Contracts\MessageChannel;
use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Livewire\Notifications\Reminders;
use App\Livewire\Settings\ActivityLog;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Services\Messaging\AppointmentReminders;
use App\Support\Messaging\MessageResult;
use App\Support\Messaging\OutgoingMessage;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-03 12:00'));
    settings()->set(['clinic_name' => 'عيادة الشفاء', 'phone' => '0223456789']);
    $this->appointment = Appointment::factory()
        ->for(Patient::factory()->state(['name' => 'منى أحمد', 'phone' => '01012345678']))
        ->at('2026-11-04 17:30')
        ->status(AppointmentStatus::Booked)
        ->create();
});

it('builds an Arabic WhatsApp reminder link with the details filled in', function () {
    $user = actingAsRole(Role::Receptionist);
    $reminders = app(AppointmentReminders::class);

    $message = $reminders->message($this->appointment);
    expect($message)->toContain('منى أحمد')->toContain('عيادة الشفاء')->toContain('5:30')->toContain('0223456789');

    $result = $reminders->send($this->appointment, $user);

    expect($result->status)->toBe('prepared')
        ->and($result->url)->toStartWith('https://wa.me/201012345678?text=')
        ->and(rawurldecode(substr($result->url, strpos($result->url, '=') + 1)))->toBe($message)
        ->and($this->appointment->fresh()->reminder_sent_at)->not->toBeNull()
        ->and(NotificationLog::where('appointment_id', $this->appointment->id)->where('channel', 'whatsapp_link')->exists())->toBeTrue();
});

it('sends reminders from the reminders page', function () {
    actingAsRole(Role::Receptionist);

    Livewire::test(Reminders::class)
        ->assertSet('date', '2026-11-04')
        ->assertSee('منى أحمد')
        ->call('send', $this->appointment->id)
        ->assertDispatched('open-url');
});

it('uses a pluggable messaging provider', function () {
    $fake = new class implements MessageChannel
    {
        public array $sent = [];

        public function name(): string
        {
            return 'fake_sms';
        }

        public function sendsAutomatically(): bool
        {
            return true;
        }

        public function send(OutgoingMessage $message): MessageResult
        {
            $this->sent[] = $message;

            return MessageResult::sent('MSG-1');
        }
    };
    app()->instance(MessageChannel::class, $fake);

    $result = app(AppointmentReminders::class)->send($this->appointment, actingAsRole(Role::Admin));

    expect($result->status)->toBe('sent')
        ->and($fake->sent[0]->to)->toBe('201012345678')
        ->and(NotificationLog::first()->provider_reference)->toBe('MSG-1');
});

it('keeps the activity log for admins only', function (Role $role, int $status) {
    actingAsRole($role);

    $this->get(route('activity-log'))->assertStatus($status);
})->with([[Role::Admin, 200], [Role::Doctor, 403], [Role::Receptionist, 403], [Role::Accountant, 403]]);

it('shows sensitive actions in the activity log', function () {
    actingAsRole(Role::Admin);
    activity('medical')->event('viewed')->performedOn($this->appointment->patient)->log('medical record viewed');

    Livewire::test(ActivityLog::class)->set('log', 'medical')->assertSee('منى أحمد');
});

it('schedules the daily database backup', function () {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

    expect($commands)->toContain('backup:run')->toContain('backup:clean');
});

it('backs up patient attachments and the database', function () {
    expect(config('backup.backup.source.files.include'))->toContain(storage_path('app/private/patients'))
        ->and(config('backup.backup.source.databases'))->toBe([config('database.default')]);
});

it('protects the backups page and downloads', function () {
    actingAsRole(Role::Receptionist);
    $this->get(route('settings.backups'))->assertForbidden();

    actingAsRole(Role::Admin);
    $this->get(route('settings.backups'))->assertOk();
    $this->get(route('settings.backups.download', '../../.env'))->assertNotFound();
});
