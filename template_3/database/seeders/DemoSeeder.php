<?php

namespace Database\Seeders;

use App\Actions\Billing\ManagePayments;
use App\Actions\Billing\SaveInvoice;
use App\Actions\Prescriptions\SavePrescription;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitType;
use App\Services\Scheduling\Availability;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\ActivityLogStatus;

/**
 * Realistic Arabic demo data for showing the system to clinics.
 * All demo accounts use the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, SchedulingSeeder::class, MedicalLibrarySeeder::class]);

        // Demo data is not real activity: keep the audit log clean (and seeding much faster).
        $logStatus = app(ActivityLogStatus::class);
        $logStatus->disable();

        try {
            $this->seedStaff();
            $this->seedPatients();
            $this->seedAppointments();
            $this->seedVisits();
            $this->seedBilling();
            $this->seedExpenses();
        } finally {
            $logStatus->enable();
        }
    }

    /** Invoices for completed appointments: most paid, some partially, a few still owed. */
    protected function seedBilling(): void
    {
        if (Invoice::count() > 0) {
            return;
        }

        $this->call(BillingSeeder::class);
        $saver = app(SaveInvoice::class);
        $payments = app(ManagePayments::class);
        $receptionist = User::query()->where('email', 'reception@demo.test')->first() ?? User::first();
        $extras = Service::query()->whereIn('category', ['إجراءات', 'فحوصات'])->get();

        Appointment::query()
            ->where('status', AppointmentStatus::Completed)
            ->with('visitType.service')
            ->orderBy('starts_at')
            ->get()
            ->each(function (Appointment $appointment) use ($saver, $payments, $receptionist, $extras) {
                $type = $appointment->visitType;
                $items = [[
                    'service_id' => $type?->service_id,
                    'description' => $type?->service?->name_ar ?? 'كشف',
                    'quantity' => 1,
                    'unit_price' => $type?->service?->price ?? 300,
                ]];

                if (random_int(1, 100) <= 20 && $extras->isNotEmpty()) {
                    $extra = $extras->random();
                    $items[] = ['service_id' => $extra->id, 'description' => $extra->name_ar, 'quantity' => 1, 'unit_price' => $extra->price];
                }

                $this->travelTo($appointment->ends_at, function () use ($saver, $payments, $receptionist, $appointment, $items) {
                    $invoice = $saver->handle(null, [
                        'patient_id' => $appointment->patient_id,
                        'appointment_id' => $appointment->id,
                        'visit_id' => Visit::query()->where('appointment_id', $appointment->id)->value('id'),
                        'doctor_id' => $appointment->doctor_id,
                        'discount_type' => 'percent',
                        'discount_value' => random_int(1, 100) <= 10 ? 10 : 0,
                    ], $items, $receptionist);

                    $roll = random_int(1, 100);
                    if ($roll <= 85) {
                        $payments->pay($invoice, $invoice->total, fake()->randomElement([PaymentMethod::Cash, PaymentMethod::Cash, PaymentMethod::Cash, PaymentMethod::InstaPay, PaymentMethod::Wallet, PaymentMethod::Card]), $receptionist);
                    } elseif ($roll <= 95) {
                        $payments->pay($invoice, floor($invoice->total / 2), PaymentMethod::Cash, $receptionist);
                    }
                });
            });
    }

    protected function seedExpenses(): void
    {
        if (Expense::count() > 0) {
            return;
        }

        $categories = ExpenseCategory::query()->pluck('id', 'name_ar');
        $admin = User::query()->role('admin')->first();

        foreach (range(2, 0) as $monthsAgo) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            $entries = [
                ['إيجار', 8000, 'إيجار العيادة', 1],
                ['مرتبات', 12000, 'مرتبات الموظفين', 28],
                ['كهرباء ومياه وإنترنت', random_int(900, 1400), 'فاتورة الكهرباء', 10],
                ['كهرباء ومياه وإنترنت', 450, 'باقة الإنترنت', 5],
                ['مستلزمات طبية', random_int(1500, 3500), 'جوانتي وسرنجات وقطن', 12],
                ['أدوات مكتبية ونظافة', random_int(300, 700), 'منظفات وورق', 15],
                ['دعاية وتسويق', random_int(500, 1500), 'إعلان فيسبوك', 20],
            ];

            foreach ($entries as [$category, $amount, $description, $day]) {
                $date = $month->copy()->day(min($day, $month->daysInMonth));
                if ($date->isFuture() || ! isset($categories[$category])) {
                    continue;
                }

                Expense::create([
                    'expense_category_id' => $categories[$category],
                    'amount' => $amount,
                    'spent_at' => $date,
                    'description' => $description,
                    'payment_method' => 'cash',
                    'created_by' => $admin?->id,
                ]);
            }
        }
    }

    /** Run a callback "as of" a past moment so numbers and timestamps look natural. */
    protected function travelTo(CarbonInterface $moment, callable $callback): void
    {
        Carbon::setTestNow($moment);
        try {
            $callback();
        } finally {
            Carbon::setTestNow();
        }
    }

    /** Visits with vitals, diagnosis and a prescription for completed appointments. */
    protected function seedVisits(): void
    {
        if (Visit::count() > 0) {
            return;
        }

        $cases = [
            ['التهاب حلق وسخونية', 'احتقان بالحلق، حرارة 38.5', 'التهاب لوزتين صديدي', ['Augmentin', 'Panadol', 'Telfast']],
            ['كحة ورشح من 3 أيام', 'صدر سليم، رشح أنفي', 'نزلة برد', ['Panadol Extra', 'Otrivin Drops', 'Mucosol']],
            ['إسهال وقيء', 'جفاف بسيط', 'نزلة معوية', ['Antinal', 'Rani 2', 'Motilium']],
            ['ألم بالمعدة بعد الأكل', 'ألم عند الضغط أعلى البطن', 'التهاب معدة', ['Nexium', 'Buscopan']],
            ['طفح جلدي وحكة', 'بقع حمراء بالذراعين', 'حساسية جلدية', ['Zyrtec', 'Betaderm Cream']],
            ['صداع وارتفاع ضغط', 'ضغط 150/95', 'ارتفاع ضغط الدم', ['Concor', 'Aspocid']],
            ['متابعة سكر', 'سكر صائم 160', 'سكري النوع الثاني', ['Glucophage', 'Neurobion']],
        ];

        $medicines = Medicine::query()->get()->keyBy('name');
        $action = app(SavePrescription::class);

        Appointment::query()
            ->where('status', AppointmentStatus::Completed)
            ->with('patient', 'doctor')
            ->inRandomOrder()
            ->limit(400)
            ->get()
            ->each(function (Appointment $appointment) use ($cases, $medicines, $action) {
                [$complaint, $exam, $diagnosis, $drugs] = fake()->randomElement($cases);
                $age = $appointment->patient->ageInYears() ?? 30;
                $child = $age < 14;

                $visit = Visit::create([
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $appointment->doctor_id,
                    'appointment_id' => $appointment->id,
                    'visited_at' => $appointment->started_at ?? $appointment->starts_at,
                    'completed_at' => $appointment->completed_at ?? $appointment->ends_at,
                    'chief_complaint' => $complaint,
                    'examination' => $exam,
                    'diagnosis' => $diagnosis,
                    'bp_systolic' => $child ? null : random_int(105, 150),
                    'bp_diastolic' => $child ? null : random_int(65, 95),
                    'pulse' => random_int(70, 110),
                    'temperature' => fake()->randomFloat(1, 36.6, 38.8),
                    'weight' => $child ? fake()->randomFloat(1, 8, 45) : fake()->randomFloat(1, 55, 105),
                    'height' => $child ? random_int(70, 150) : random_int(150, 190),
                ]);

                $action->handle($visit, collect($drugs)->filter(fn ($name) => $medicines->has($name))->map(fn ($name) => [
                    'medicine_id' => $medicines[$name]->id,
                    'medicine_name' => $medicines[$name]->displayName(),
                    'dose' => $medicines[$name]->default_dose,
                    'frequency' => $medicines[$name]->default_frequency,
                    'duration' => $medicines[$name]->default_duration,
                ])->values()->all(), fake()->optional(0.3)->randomElement(['سوائل كثيرة وراحة', 'متابعة بعد أسبوع', 'تجنب الأكل الحار']), $appointment->doctor);
            });
    }

    /**
     * ~60 days of history plus the next two weeks, laid out on each doctor's real working
     * slots so the calendar and the waiting room look like a busy clinic.
     */
    protected function seedAppointments(): void
    {
        if (Appointment::count() > 0) {
            return;
        }

        $availability = app(Availability::class);
        $patients = Patient::query()->pluck('id');
        $visitTypes = VisitType::query()->get();
        $receptionist = User::query()->where('email', 'reception@demo.test')->first();

        foreach (User::query()->doctors()->with('doctorProfile')->get() as $doctor) {
            $queue = [];

            for ($offset = -60; $offset <= 14; $offset++) {
                $day = today()->addDays($offset);

                foreach ($availability->windows($doctor, $day) as [$from, $to]) {
                    $step = $doctor->doctorProfile->slot_minutes;

                    for ($slot = $from; $slot->addMinutes($step)->lessThanOrEqualTo($to); $slot = $slot->addMinutes($step)) {
                        // Busier today and in the coming days, a little lighter in the past.
                        if (random_int(1, 100) > ($offset === 0 ? 70 : ($offset > 0 ? 45 : 55))) {
                            continue;
                        }

                        $status = $this->demoStatus($slot, $step);

                        // Only one patient at a time can be with the doctor.
                        if ($status === AppointmentStatus::InProgress) {
                            Appointment::query()->where('doctor_id', $doctor->id)->where('status', AppointmentStatus::InProgress)
                                ->update(['status' => AppointmentStatus::Completed, 'completed_at' => $slot]);
                        }

                        $queueNumber = null;
                        $checkedIn = null;

                        if (in_array($status, [AppointmentStatus::Arrived, AppointmentStatus::InProgress, AppointmentStatus::Completed], true)) {
                            $key = $slot->toDateString();
                            $queueNumber = $queue[$key] = ($queue[$key] ?? 0) + 1;
                            $checkedIn = $slot->subMinutes(random_int(5, 25));
                            $checkedIn = $checkedIn->greaterThan(now()) ? now()->subMinutes(random_int(1, 15)) : $checkedIn;
                        }

                        Appointment::create([
                            'patient_id' => $patients->random(),
                            'doctor_id' => $doctor->id,
                            'visit_type_id' => $visitTypes->random()->id,
                            'starts_at' => $slot,
                            'ends_at' => $slot->addMinutes($step),
                            'status' => $status,
                            'source' => fake()->randomElement([AppointmentSource::Booked, AppointmentSource::Phone, AppointmentSource::Phone, AppointmentSource::FollowUp]),
                            'queue_number' => $queueNumber,
                            'checked_in_at' => $checkedIn,
                            'started_at' => in_array($status, [AppointmentStatus::InProgress, AppointmentStatus::Completed], true) ? $slot : null,
                            'completed_at' => $status === AppointmentStatus::Completed ? $slot->addMinutes($step) : null,
                            'cancelled_at' => $status === AppointmentStatus::Cancelled ? $slot->subDay() : null,
                            'cancel_reason' => $status === AppointmentStatus::Cancelled ? fake()->randomElement(['ظروف سفر', 'تحسن المريض', 'تعارض مع موعد آخر']) : null,
                            'created_by' => $receptionist?->id,
                        ]);
                    }
                }
            }
        }
    }

    protected function demoStatus(CarbonInterface $slot, int $step): AppointmentStatus
    {
        $now = now();

        // Finished before the current slot started.
        if ($slot->addMinutes($step)->lessThanOrEqualTo($now)) {
            return fake()->randomElement([...array_fill(0, 8, AppointmentStatus::Completed), AppointmentStatus::NoShow, AppointmentStatus::Cancelled]);
        }

        // The slot running right now.
        if ($slot->lessThanOrEqualTo($now)) {
            return AppointmentStatus::InProgress;
        }

        if ($slot->lessThan($now->copy()->addMinutes(45)) && $slot->isToday()) {
            return AppointmentStatus::Arrived;
        }

        return fake()->boolean(40) ? AppointmentStatus::Confirmed : AppointmentStatus::Booked;
    }

    protected function seedPatients(): void
    {
        if (Patient::count() >= 50) {
            return;
        }

        $staff = User::query()->pluck('id');

        Patient::factory()
            ->count(80)
            ->sequence(fn () => [
                'created_by' => $staff->random(),
                'created_at' => now()->subDays(random_int(0, 365))->setTime(random_int(9, 21), random_int(0, 59)),
            ])
            ->create();
    }

    protected function seedStaff(): void
    {
        $staff = [
            ['name' => 'د. أحمد عبد الرحمن', 'email' => 'doctor@demo.test', 'role' => Role::Doctor,
                'profile' => ['title_ar' => 'استشاري طب الأطفال', 'title_en' => 'Pediatrics Consultant', 'specialty' => 'pediatrics', 'color' => '#0d9488', 'slot_minutes' => 15, 'consultation_fee' => 300]],
            ['name' => 'د. منى السيد', 'email' => 'doctor2@demo.test', 'role' => Role::Doctor,
                'profile' => ['title_ar' => 'أخصائي الجلدية', 'title_en' => 'Dermatology Specialist', 'specialty' => 'dermatology', 'color' => '#7c3aed', 'slot_minutes' => 20, 'consultation_fee' => 400]],
            ['name' => 'سارة محمود', 'email' => 'reception@demo.test', 'role' => Role::Receptionist],
            ['name' => 'خالد إبراهيم', 'email' => 'accountant@demo.test', 'role' => Role::Accountant],
        ];

        foreach ($staff as $member) {
            $user = User::query()->updateOrCreate(
                ['email' => $member['email']],
                ['name' => $member['name'], 'password' => 'password', 'phone' => '010'.random_int(10000000, 99999999), 'is_active' => true],
            );
            $user->syncRoles([$member['role']->value]);

            if (isset($member['profile'])) {
                $user->doctorProfile()->updateOrCreate([], $member['profile']);
            }
        }
    }
}
