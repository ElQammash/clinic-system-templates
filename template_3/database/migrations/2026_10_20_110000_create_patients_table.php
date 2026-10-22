<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 20);            // patient | visit
            $table->string('key', 60);
            $table->string('label_ar');
            $table->string('label_en')->nullable();
            $table->string('type', 20);              // CustomFieldType
            $table->json('options')->nullable();     // for select / multiselect
            $table->boolean('is_required')->default(false);
            $table->string('specialty', 50)->nullable(); // null = all clinic types
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['entity', 'key']);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('file_number', 30)->unique();
            $table->string('name');
            $table->string('search_name')->index();   // normalised Arabic for fast search
            $table->string('phone', 20)->nullable()->index();
            $table->string('phone_alt', 20)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->boolean('birth_date_is_estimated')->default(false);
            $table->string('national_id', 20)->nullable()->unique();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_diseases')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });

        Schema::create('patient_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            // Foreign keys for visits / lab requests are added by their own migrations.
            $table->unsignedBigInteger('visit_id')->nullable()->index();
            $table->unsignedBigInteger('lab_request_id')->nullable()->index();
            $table->string('category', 20);
            $table->string('title');
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->date('taken_at')->nullable();     // date of the X-ray / test
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_attachments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('custom_fields');
    }
};
