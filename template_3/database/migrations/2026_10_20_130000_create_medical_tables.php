<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->dateTime('visited_at');
            $table->text('chief_complaint')->nullable();
            $table->text('examination')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();
            // Vitals
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->decimal('weight', 5, 2)->nullable();   // kg
            $table->decimal('height', 5, 1)->nullable();   // cm
            $table->decimal('bmi', 4, 1)->nullable();      // calculated on save
            $table->json('custom_data')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->foreignId('follow_up_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'visited_at']);
            $table->index(['doctor_id', 'visited_at']);
        });

        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('form', 20)->nullable();
            $table->string('strength', 50)->nullable();
            $table->string('default_dose', 100)->nullable();
            $table->string('default_frequency', 100)->nullable();
            $table->string('default_duration', 100)->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_prescribed')->default(0); // for "most used first" suggestions
            $table->timestamps();

            $table->index('name');
            $table->index('generic_name');
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('issued_at');
            $table->timestamps();

            $table->index(['patient_id', 'issued_at']);
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medicine_name');   // snapshot, the library entry may change later
            $table->string('dose', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('instructions')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->cascadeOnDelete(); // null = shared
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('medicine_name');
            $table->string('dose', 100)->nullable();
            $table->string('frequency', 100)->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('instructions')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // Lab & imaging (optional module)
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 10)->default('lab'); // lab | imaging
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('requested');
            $table->text('notes')->nullable();
            $table->text('result_summary')->nullable();
            $table->dateTime('requested_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_at']);
            $table->index('patient_id');
        });

        Schema::create('lab_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('result')->nullable();
            $table->timestamps();
        });

        // Attachments created in Phase 3 reference visits / lab requests: add the real foreign keys now.
        Schema::table('patient_attachments', function (Blueprint $table) {
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
            $table->foreign('lab_request_id')->references('id')->on('lab_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_attachments', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropForeign(['lab_request_id']);
        });

        Schema::dropIfExists('lab_request_items');
        Schema::dropIfExists('lab_requests');
        Schema::dropIfExists('lab_tests');
        Schema::dropIfExists('prescription_template_items');
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('visits');
    }
};
