<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->unsignedBigInteger('default_price')->default(0);      // minor units (piastres)
            $table->unsignedSmallInteger('default_duration')->nullable(); // minutes; null = doctor's slot length
            $table->string('color', 7)->default('#0d9488');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // Several rows per day allow split shifts (e.g. 10:00-14:00 and 17:00-22:00).
        // doctor_id = null rows are the clinic default used by doctors without their own hours.
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday … 6 = Saturday (Carbon)
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->index(['doctor_id', 'day_of_week']);
        });

        // Vacations, conferences, public holidays. doctor_id = null blocks the whole clinic.
        Schema::create('blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['doctor_id', 'starts_at', 'ends_at']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('visit_type_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('booked');
            $table->string('source', 20)->default('booked');
            $table->unsignedSmallInteger('queue_number')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['doctor_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
            $table->index(['patient_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('blocked_periods');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('visit_types');
    }
};
