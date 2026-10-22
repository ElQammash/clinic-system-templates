<?php

namespace App\Providers;

use App\Contracts\MessageChannel;
use App\Enums\Module;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabRequest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Messaging\WhatsAppLinkChannel;
use App\Services\NumberSequence;
use App\Services\PatientTimeline;
use App\Services\Settings;
use App\Support\TimelineEntry;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Settings::class);
        $this->app->singleton(NumberSequence::class);

        // Messaging provider chosen in config/clinic.php (WhatsApp click-to-send link by default).
        $this->app->bind(MessageChannel::class, fn ($app) => $app->make(
            config('clinic.messaging.channels.'.config('clinic.messaging.channel'), WhatsAppLinkChannel::class)
        ));
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // The clinic owner can do everything; their role cannot be locked out by editing permissions.
        Gate::before(fn (User $user) => $user->hasRole(Role::Admin->value) ? true : null);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(
            mb_strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        $this->registerAuthAuditTrail();
        $this->registerPatientExtensions();
    }

    /** Hooks other modules add to the patient file (ownership rules, timeline entries). */
    protected function registerPatientExtensions(): void
    {
        // A doctor "treats" a patient once they have an appointment together.
        Patient::resolveOwnershipUsing('appointments', fn (Builder $query, User $user) => $query->orWhereHas(
            'appointments', fn (Builder $q) => $q->where('doctor_id', $user->id)
        ));

        Patient::resolveOwnershipUsing('visits', fn (Builder $query, User $user) => $query->orWhereHas(
            'visits', fn (Builder $q) => $q->where('doctor_id', $user->id)
        ));

        // Medical entries are only added for users allowed to read the medical record.
        PatientTimeline::extend('visits', fn (Patient $patient, User $user) => ! $user->can('viewMedical', $patient) ? [] : $patient->visits()
            ->with('doctor', 'prescription.items')
            ->get()
            ->map(fn (Visit $v) => new TimelineEntry(
                type: 'visit',
                date: $v->visited_at,
                title: $v->diagnosis ? str($v->diagnosis)->limit(80)->toString() : __('Visit'),
                subtitle: $v->doctor->displayName().($v->prescription ? ' · '.trans_choice(':count medicine|:count medicines', $v->prescription->items->count(), ['count' => $v->prescription->items->count()]) : ''),
                icon: 'stethoscope',
                color: 'primary',
                url: route('visits.show', $v),
            )));

        PatientTimeline::extend('lab', fn (Patient $patient, User $user) => ! Module::Lab->enabled() || ! $user->can('lab.view') ? [] : $patient->labRequests()
            ->with('items')
            ->get()
            ->map(fn (LabRequest $r) => new TimelineEntry(
                type: 'lab',
                date: $r->requested_at,
                title: $r->items->pluck('name')->implode('، '),
                subtitle: $r->status->label(),
                icon: 'lab',
                color: $r->status->color(),
            )));

        PatientTimeline::extend('invoices', fn (Patient $patient, User $user) => ! $user->can('viewAny', Invoice::class) ? [] : $patient->invoices()
            ->visibleTo($user)
            ->get()
            ->map(fn (Invoice $i) => new TimelineEntry(
                type: 'invoice',
                date: $i->issued_at,
                title: __('Invoice').' '.$i->number,
                subtitle: $i->status->label(),
                icon: 'invoice',
                color: $i->status->color(),
                url: route('invoices.show', $i),
                amount: money($i->total),
            )));

        PatientTimeline::extend('appointments', fn (Patient $patient, User $user) => $patient->appointments()
            ->with('doctor', 'visitType')
            ->visibleTo($user)
            ->get()
            ->map(fn (Appointment $a) => new TimelineEntry(
                type: 'appointment',
                date: $a->starts_at,
                title: $a->visitType?->name() ?? __('Appointment'),
                subtitle: $a->doctor->displayName().' · '.$a->status->label(),
                icon: 'calendar',
                color: $a->status->color(),
            )));
    }

    protected function registerAuthAuditTrail(): void
    {
        Event::listen(function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            activity('auth')->causedBy($event->user)->performedOn($event->user)->event('login')->log('login');
        });

        Event::listen(function (Logout $event) {
            if ($event->user) {
                activity('auth')->causedBy($event->user)->performedOn($event->user)->event('logout')->log('logout');
            }
        });

        Event::listen(function (Failed $event) {
            activity('auth')
                ->withProperties(['email' => $event->credentials['email'] ?? null, 'ip' => request()->ip()])
                ->event('login_failed')
                ->log('login_failed');
        });
    }
}
