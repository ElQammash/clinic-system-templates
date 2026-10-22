<?php

namespace App\Actions\Visits;

use App\Actions\Appointments\ChangeAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Enums\CustomFieldEntity;
use App\Models\User;
use App\Models\Visit;
use App\Services\CustomFields;
use Illuminate\Support\Facades\DB;

class SaveVisit
{
    public function __construct(
        protected CustomFields $customFields,
        protected ChangeAppointmentStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated visit attributes
     * @param  array<string, mixed>  $custom  custom field input
     */
    public function handle(Visit $visit, array $data, array $custom): Visit
    {
        $visit->fill($data);
        $visit->custom_data = $this->customFields->clean(CustomFieldEntity::Visit, $custom, $visit->custom_data ?? []);
        $visit->save();

        return $visit;
    }

    /** Finish the visit: the appointment is completed and the patient leaves the queue. */
    public function complete(Visit $visit, User $actor): Visit
    {
        return DB::transaction(function () use ($visit, $actor) {
            $visit->update(['completed_at' => $visit->completed_at ?? now()]);

            $appointment = $visit->appointment()->first();
            if ($appointment && $appointment->status === AppointmentStatus::InProgress) {
                $this->status->handle($appointment, AppointmentStatus::Completed, $actor);
            }

            return $visit;
        });
    }
}
