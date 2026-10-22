<?php

namespace App\Actions\Prescriptions;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or replaces the prescription of a visit. Medicines typed by hand
 * that are not in the library are added to it, so the library grows with use.
 */
class SavePrescription
{
    /**
     * @param  list<array{medicine_id?: ?int, medicine_name: string, dose?: ?string, frequency?: ?string, duration?: ?string, instructions?: ?string}>  $items
     */
    public function handle(Visit $visit, array $items, ?string $notes, User $actor): ?Prescription
    {
        $items = array_values(array_filter($items, fn ($item) => filled(trim((string) ($item['medicine_name'] ?? '')))));

        if ($items === [] && blank($notes)) {
            // Nothing prescribed: remove an existing empty prescription.
            $visit->prescriptions()->delete();

            return null;
        }

        if (count($items) > 30) {
            throw ValidationException::withMessages(['items' => __('A prescription can contain at most 30 medicines.')]);
        }

        return DB::transaction(function () use ($visit, $items, $notes, $actor) {
            $prescription = $visit->prescription()->first() ?? new Prescription([
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'doctor_id' => $visit->doctor_id,
                'issued_at' => now(),
            ]);

            $prescription->notes = $notes;
            $prescription->save();

            $previousMedicineIds = $prescription->items()->pluck('medicine_id')->filter()->all();
            $prescription->items()->delete();

            foreach ($items as $index => $item) {
                $medicine = $this->resolveMedicine($item);

                $prescription->items()->create([
                    'medicine_id' => $medicine?->id,
                    'medicine_name' => trim($item['medicine_name']),
                    'dose' => $item['dose'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'sort' => $index,
                ]);

                if ($medicine && ! in_array($medicine->id, $previousMedicineIds, true)) {
                    $medicine->increment('times_prescribed');
                }
            }

            activity('medical')->causedBy($actor)->performedOn($prescription)->event('updated')
                ->withProperties(['items' => count($items)])->log('prescription saved');

            return $prescription->load('items');
        });
    }

    /** @param  array<string, mixed>  $item */
    protected function resolveMedicine(array $item): ?Medicine
    {
        if (filled($item['medicine_id'] ?? null) && ($medicine = Medicine::find($item['medicine_id']))) {
            return $medicine;
        }

        $name = trim($item['medicine_name']);

        return Medicine::query()->where('name', $name)->first()
            ?? Medicine::create([
                'name' => $name,
                'default_dose' => $item['dose'] ?? null,
                'default_frequency' => $item['frequency'] ?? null,
                'default_duration' => $item['duration'] ?? null,
            ]);
    }
}
