<?php

namespace App\Actions\Lab;

use App\Actions\Patients\StoreAttachment;
use App\Enums\AttachmentCategory;
use App\Enums\LabRequestStatus;
use App\Models\LabRequest;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageLabRequest
{
    public function __construct(protected StoreAttachment $attachments) {}

    /**
     * @param  list<int>  $testIds  tests from the catalog
     * @param  list<string>  $otherTests  free-text tests not in the catalog
     */
    public function create(Patient $patient, ?Visit $visit, User $doctor, array $testIds, array $otherTests = [], ?string $notes = null): LabRequest
    {
        $tests = LabTest::query()->whereIn('id', $testIds)->get();
        $otherTests = array_values(array_filter(array_map('trim', $otherTests)));

        if ($tests->isEmpty() && $otherTests === []) {
            throw ValidationException::withMessages(['tests' => __('Choose at least one test.')]);
        }

        return DB::transaction(function () use ($patient, $visit, $doctor, $tests, $otherTests, $notes) {
            $request = LabRequest::create([
                'patient_id' => $patient->id,
                'visit_id' => $visit?->id,
                'doctor_id' => $visit?->doctor_id ?? $doctor->id,
                'status' => LabRequestStatus::Requested,
                'notes' => $notes,
                'requested_at' => now(),
            ]);

            foreach ($tests as $test) {
                $request->items()->create(['lab_test_id' => $test->id, 'name' => $test->name]);
            }
            foreach ($otherTests as $name) {
                $request->items()->create(['name' => $name]);
            }

            return $request->load('items');
        });
    }

    /**
     * Record results: a value per test, an optional summary and result files (PDF / images).
     *
     * @param  array<int, ?string>  $results  item id => result
     * @param  list<UploadedFile>  $files
     */
    public function complete(LabRequest $request, array $results, ?string $summary, array $files, User $actor): LabRequest
    {
        $request->loadMissing('items', 'patient');

        return DB::transaction(function () use ($request, $results, $summary, $files, $actor) {
            foreach ($request->items as $item) {
                if (array_key_exists($item->id, $results)) {
                    $item->update(['result' => filled($results[$item->id]) ? trim($results[$item->id]) : null]);
                }
            }

            foreach ($files as $file) {
                $this->attachments->handle($request->patient, $file, [
                    'category' => AttachmentCategory::Lab->value,
                    'title' => $request->items->pluck('name')->implode('، '),
                    'visit_id' => $request->visit_id,
                    'lab_request_id' => $request->id,
                    'taken_at' => now()->toDateString(),
                ], $actor);
            }

            $request->update([
                'status' => LabRequestStatus::Completed,
                'result_summary' => $summary,
                'completed_at' => $request->completed_at ?? now(),
            ]);

            return $request;
        });
    }

    public function cancel(LabRequest $request): LabRequest
    {
        $request->update(['status' => LabRequestStatus::Cancelled]);

        return $request;
    }
}
